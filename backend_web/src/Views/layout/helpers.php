<?php
// Hàm hiển thị dùng chung cho các view CMS (chỉ định dạng, không đụng dữ liệu).

if (!function_exists('kw_e')) {
    function kw_e($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    /** URL ảnh dùng được để hiển thị: http(s) hoặc đường dẫn tuyệt đối trên máy chủ. */
    function kw_valid_image_url(?string $url): bool {
        $url = trim((string)$url);
        return $url !== '' && (preg_match('#^https?://#i', $url) || str_starts_with($url, '/'));
    }

    /** Ảnh nhỏ vuông; URL hỏng (vd dạng markdown "[url](url)") hiện icon cảnh báo. */
    function kw_thumb(?string $url, int $size = 44): string {
        $url = trim((string)$url);
        $style = "width:{$size}px;height:{$size}px";
        if (kw_valid_image_url($url)) {
            return '<img class="k-thumb" style="' . $style . '" src="' . kw_e($url) . '" alt="" loading="lazy">';
        }
        if ($url !== '') {
            return '<span class="k-thumb" style="' . $style . '" title="Đường dẫn ảnh lỗi"><i class="bi bi-exclamation-triangle"></i></span>';
        }
        return '<span class="k-thumb" style="' . $style . '" title="Chưa có ảnh"><i class="bi bi-image"></i></span>';
    }

    function kw_first_image(?string $imagesJson): string {
        $images = json_decode($imagesJson ?? '[]', true);
        return (is_array($images) && !empty($images[0])) ? (string)$images[0] : '';
    }

    /** Thẻ đủ 4 loại nội dung: ảnh 3D, ảnh thật, tranh tô màu, fun fact. */
    function kw_item_checks(array $item): array {
        return [
            'Ảnh 3D' => kw_first_image($item['images_json'] ?? '') !== '',
            'Ảnh thật' => trim((string)($item['real_image_url'] ?? '')) !== '',
            'Tranh tô' => trim((string)($item['coloring_outline_url'] ?? '')) !== '',
            'Fun fact' => trim((string)($item['fun_fact_vi'] ?? '')) !== '',
        ];
    }

    /** Phần trăm đầy đủ của gói, tính từ số trường còn thiếu của các thẻ. */
    function kw_pack_pct(array $pack): int {
        $count = (int)($pack['item_count'] ?? 0);
        if ($count === 0) return 0;
        $missing = (int)($pack['missing_image'] ?? 0) + (int)($pack['missing_real'] ?? 0)
                 + (int)($pack['missing_line'] ?? 0) + (int)($pack['missing_fact'] ?? 0);
        return (int)round(100 * ($count * 4 - $missing) / ($count * 4));
    }

    function kw_progress(int $pct): string {
        return '<div class="k-progress"><div class="k-bar" style="flex:1"><span style="width:' . $pct . '%"></span></div><span class="k-num">' . $pct . '%</span></div>';
    }

    /** Câu "Thiếu ..." của một gói. */
    function kw_pack_missing(array $pack): string {
        if ((int)($pack['item_count'] ?? 0) === 0) {
            return '<span class="k-badge k-badge-warning">Chưa có thẻ</span>';
        }
        $parts = [];
        foreach (['missing_line' => 'tranh tô màu', 'missing_real' => 'ảnh thật', 'missing_image' => 'ảnh 3D', 'missing_fact' => 'fun fact'] as $key => $label) {
            if (!empty($pack[$key])) $parts[] = (int)$pack[$key] . ' ' . $label;
        }
        return $parts
            ? '<span class="k-muted">Thiếu ' . kw_e(implode(', ', $parts)) . '</span>'
            : '<span class="k-badge k-badge-success">Đủ nội dung</span>';
    }

    function kw_date(?string $datetime): string {
        if (!$datetime) return '—';
        $ts = strtotime($datetime);
        return $ts ? date('d/m, H:i', $ts) : '—';
    }
}
