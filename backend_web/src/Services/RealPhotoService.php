<?php

namespace KidsWorld\Backend\Services;

/**
 * Tìm ảnh chụp thật trên Wikimedia Commons (ảnh có giấy phép mở) và tải về máy chủ.
 * Ảnh lưu ở public/uploads/images, nguồn và tác giả ghi vào data/photo_credits.json để ghi công khi cần.
 */
class RealPhotoService {
    private const API = 'https://commons.wikimedia.org/w/api.php';
    private const USER_AGENT = 'KidsWorldCMS/1.0 (educational flashcards for children)';
    private const TARGET_WIDTH = 1920;
    private const MAX_BYTES = 12 * 1024 * 1024;

    /** Từ trong tên file cho thấy đây không phải ảnh chụp sự vật (bản đồ, sơ đồ, logo, hình vẽ...). */
    private const NOT_PHOTO_WORDS = ['map', 'diagram', 'logo', 'icon', 'drawing', 'illustration', 'clipart', 'cartoon', 'chart',
        'graph', 'flag', 'coat of arms', 'stamp', 'poster', 'sketch', 'painting', 'svg', 'range', 'distribution', 'scheme'];

    /**
     * Tìm ảnh hợp nhất cho tên tiếng Anh, tải về và trả về đường dẫn trên máy chủ.
     * @return array{url: string, source: string, title: string, artist: string, license: string}
     */
    public static function findAndSave(string $nameEn, string $nameVi = ''): array {
        $query = trim($nameEn) !== '' ? trim($nameEn) : trim($nameVi);
        if ($query === '') {
            throw new \InvalidArgumentException('Thẻ chưa có tên để tìm ảnh.');
        }

        // Ảnh đầu bài Wikipedia thường là ảnh tiêu biểu nhất của sự vật; không có thì tìm trên Commons
        $lead = self::wikipediaLeadImage($query);
        $candidates = array_merge($lead ? [$lead] : [], self::search($query));
        if (!$candidates) {
            throw new \RuntimeException('Không tìm thấy ảnh thật phù hợp trên Wikimedia Commons cho "' . $query . '".');
        }

        $lastError = null;
        foreach (array_slice($candidates, 0, 3) as $candidate) {
            try {
                $url = self::download($candidate['download_url'], $query);
                $credit = [
                    'url' => $url,
                    'source' => $candidate['page_url'],
                    'title' => $candidate['title'],
                    'artist' => $candidate['artist'],
                    'license' => $candidate['license'],
                    'query' => $query,
                    'saved_at' => date('c'),
                ];
                self::recordCredit($credit);
                return $credit;
            } catch (\Throwable $e) {
                $lastError = $e; // thử ảnh kế tiếp
            }
        }
        throw new \RuntimeException('Tải ảnh từ Wikimedia thất bại: ' . ($lastError ? $lastError->getMessage() : 'không rõ lý do'));
    }

    /**
     * Ảnh đầu bài của trang Wikipedia tiếng Anh khớp nhất với tên. Chỉ nhận ảnh JPEG nằm trên Commons
     * (ảnh "fair use" chỉ có trên Wikipedia thì bỏ, vì không có giấy phép mở).
     */
    private static function wikipediaLeadImage(string $query): ?array {
        try {
            $search = json_decode(self::httpGet('https://en.wikipedia.org/w/api.php?' . http_build_query([
                'action' => 'query', 'format' => 'json', 'generator' => 'search', 'gsrsearch' => $query,
                'gsrlimit' => 1, 'prop' => 'pageimages', 'piprop' => 'name',
            ])), true);
            $page = array_values($search['query']['pages'] ?? [])[0] ?? null;
            $file = $page['pageimage'] ?? null;
            if (!$file) return null;

            $info = json_decode(self::httpGet(self::API . '?' . http_build_query([
                'action' => 'query', 'format' => 'json', 'titles' => 'File:' . $file, 'prop' => 'imageinfo',
                'iiprop' => 'url|size|mime|extmetadata', 'iiurlwidth' => self::TARGET_WIDTH,
                'iiextmetadatafilter' => 'LicenseShortName|Artist',
            ])), true);
            $filePage = array_values($info['query']['pages'] ?? [])[0] ?? null;
            $ii = $filePage['imageinfo'][0] ?? null;
            if (!$ii || isset($filePage['missing']) || ($ii['mime'] ?? '') !== 'image/jpeg' || (int)($ii['width'] ?? 0) < 600) {
                return null;
            }
            $meta = $ii['extmetadata'] ?? [];
            return [
                'title' => preg_replace('/\.(jpe?g)$/i', '', $file),
                'download_url' => $ii['thumburl'] ?? $ii['url'],
                'page_url' => $ii['descriptionurl'] ?? '',
                'artist' => trim(strip_tags($meta['Artist']['value'] ?? '')),
                'license' => trim(strip_tags($meta['LicenseShortName']['value'] ?? '')),
                'score' => 1000,
            ];
        } catch (\Throwable $e) {
            return null; // lỗi mạng ở bước này thì dùng tìm kiếm Commons
        }
    }

    /** @return list<array{title: string, download_url: string, page_url: string, artist: string, license: string, score: float}> */
    private static function search(string $query): array {
        $params = [
            'action' => 'query',
            'format' => 'json',
            'generator' => 'search',
            'gsrnamespace' => 6,
            'gsrsearch' => 'filetype:bitmap ' . $query,
            'gsrlimit' => 30,
            'prop' => 'imageinfo',
            'iiprop' => 'url|size|mime|extmetadata',
            'iiurlwidth' => self::TARGET_WIDTH,
            'iiextmetadatafilter' => 'LicenseShortName|Artist|ObjectName',
        ];
        $json = self::httpGet(self::API . '?' . http_build_query($params));
        $data = json_decode($json, true);
        $pages = $data['query']['pages'] ?? [];

        $words = array_filter(preg_split('/\s+/', mb_strtolower($query)), fn($w) => mb_strlen($w) > 2);
        $results = [];
        foreach ($pages as $page) {
            $info = $page['imageinfo'][0] ?? null;
            if (!$info || ($info['mime'] ?? '') !== 'image/jpeg') continue; // ảnh chụp gần như luôn là JPEG
            $width = (int)($info['width'] ?? 0);
            $height = (int)($info['height'] ?? 0);
            if ($width < 800 || $height < 500) continue;

            $title = preg_replace('/^File:|\.(jpe?g)$/i', '', $page['title'] ?? '');
            $lowerTitle = mb_strtolower($title);
            foreach (self::NOT_PHOTO_WORDS as $bad) {
                if (str_contains($lowerTitle, $bad)) continue 2;
            }

            $ratio = $width / max(1, $height);
            $score = (float)(30 - (int)($page['index'] ?? 30));             // thứ hạng tìm kiếm của Commons
            $score += ($ratio >= 1.2 && $ratio <= 2.0) ? 15 : ($ratio >= 1.0 ? 6 : -10); // ưu tiên ảnh ngang
            $score += min(10, $width / 400);                               // ảnh lớn hơn rõ hơn
            foreach ($words as $w) {
                if (str_contains($lowerTitle, $w)) $score += 8;            // tên file nhắc đúng sự vật
            }

            $meta = $info['extmetadata'] ?? [];
            $results[] = [
                'title' => $title,
                'download_url' => $info['thumburl'] ?? $info['url'],
                'page_url' => $info['descriptionurl'] ?? '',
                'artist' => trim(strip_tags($meta['Artist']['value'] ?? '')),
                'license' => trim(strip_tags($meta['LicenseShortName']['value'] ?? '')),
                'score' => $score,
            ];
        }
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
        return $results;
    }

    private static function download(string $url, string $query): string {
        if (!preg_match('#^https://(upload|thumb)\.wikimedia\.org/#', $url)) {
            throw new \RuntimeException('Đường dẫn ảnh không thuộc Wikimedia.');
        }
        $bytes = self::httpGet($url, 30);
        if (strlen($bytes) > self::MAX_BYTES) {
            throw new \RuntimeException('Ảnh quá lớn.');
        }
        $info = @getimagesizefromstring($bytes);
        if (!$info || ($info['mime'] ?? '') !== 'image/jpeg') {
            throw new \RuntimeException('Tệp tải về không phải ảnh JPEG.');
        }

        $dir = __DIR__ . '/../../public/uploads/images/';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $slug = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($query)), '_') ?: 'photo';
        $filename = 'real_' . substr($slug, 0, 40) . '_' . date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 6) . '.jpg';
        if (file_put_contents($dir . $filename, $bytes) === false) {
            throw new \RuntimeException('Không ghi được ảnh vào thư mục uploads.');
        }
        return '/uploads/images/' . $filename;
    }

    /** GET có thử lại khi Wikimedia báo 429 (gọi quá nhanh), đợi theo Retry-After, tối đa 2 lần. */
    private static function httpGet(string $url, int $timeout = 15): string {
        // Wikimedia yêu cầu User-Agent nêu rõ ứng dụng và cách liên hệ
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        for ($attempt = 0; ; $attempt++) {
            $retryAfter = 0;
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_USERAGENT => self::USER_AGENT . ' https://' . $host,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_HEADERFUNCTION => function ($ch, $header) use (&$retryAfter) {
                    if (stripos($header, 'retry-after:') === 0) {
                        $retryAfter = (int)trim(substr($header, 12));
                    }
                    return strlen($header);
                },
            ]);
            $res = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($code === 429 && $attempt < 2) {
                sleep(max(2, min(10, $retryAfter ?: 3 * ($attempt + 1))));
                continue;
            }
            if ($res === false || $code !== 200) {
                throw new \RuntimeException($code === 429 ? 'Wikimedia đang giới hạn tốc độ, thử lại sau ít phút' : ($err ?: 'HTTP ' . $code));
            }
            return $res;
        }
    }

    private static function recordCredit(array $credit): void {
        $file = __DIR__ . '/../../data/photo_credits.json';
        $all = is_file($file) ? (json_decode((string)file_get_contents($file), true) ?: []) : [];
        $all[$credit['url']] = $credit;
        file_put_contents($file, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }
}
