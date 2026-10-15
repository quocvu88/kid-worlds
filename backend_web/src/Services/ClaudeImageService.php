<?php

namespace KidsWorld\Backend\Services;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\RateLimitException;

/**
 * Vẽ ảnh cho thẻ học bằng Claude.
 *
 * Claude không xuất ảnh bitmap, nên ở đây Claude viết một file SVG (ảnh vector) 1920×1080.
 * Trình duyệt của CMS chuyển SVG sang PNG rồi tải lên như ảnh thường, app không cần đọc SVG.
 */
class ClaudeImageService {
    public const STYLES = ['illustration', 'coloring', 'cover'];

    private const WIDTH = 1920;
    private const HEIGHT = 1080;

    private const SYSTEM_PROMPT = <<<'PROMPT'
You are an illustrator for "Kids World", a bilingual (Vietnamese–English) flashcard app for children aged 2–8.
You draw one picture per flashcard as a single standalone SVG file.

Hard requirements for every SVG:
- Root element: <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1920 1080" width="1920" height="1080">. Landscape 16:9, shown full-screen on a phone held sideways.
- Only plain SVG shapes: path, circle, ellipse, rect, polygon, polyline, line, g, defs, linearGradient, radialGradient, stop, clipPath, use (internal #id references only).
- No <text>, no letters or numbers anywhere in the picture, no <image>, no <script>, no <foreignObject>, no external URLs, no CSS @import, no filters.
- The subject is instantly recognisable to a small child: correct body shape, signature features and colours of the real thing (e.g. a T-Rex has tiny arms and a huge head; a zebra has black-and-white stripes).
- One subject, centred, fully inside the frame with generous margins, filling roughly 60–75% of the picture height.
- Friendly and safe: no blood, weapons, scary teeth or aggressive poses.
- Reply with the SVG only: start with <svg and end with </svg>. No explanation, no markdown fences.
PROMPT;

    private const STYLE_PROMPTS = [
        'illustration' => 'Style: cute flat-vector cartoon for a children\'s picture book. Bright, cheerful, slightly pastel colours with soft two-tone shading (a lighter and a darker shade of each colour). Big friendly eyes with a small white highlight, gentle smile. Simple, uncluttered background that hints at the natural setting (a few soft shapes such as ground, sky, leaves or water) in light colours so the subject stands out. Rounded, chunky shapes; aim for a polished, professional look with enough detail to be charming.',
        'cover' => 'This is the COVER picture of a whole topic pack, not a single card. Draw one cheerful group scene that shows 3 to 5 of the listed things together, each clearly recognisable, arranged in a balanced composition across the wide frame (they may overlap slightly; this rule replaces the "one subject" rule). Style: cute flat-vector cartoon for a children\'s picture book, bright cheerful slightly pastel colours with soft two-tone shading, big friendly eyes for living things, a simple background that suits the topic. No text or title on the picture.',
        'coloring' => 'Style: colouring page for toddlers. Pure white background (#FFFFFF rect filling the canvas). Every shape has fill="#FFFFFF" and a solid black outline (stroke="#000000", stroke-width between 6 and 10, stroke-linejoin="round", stroke-linecap="round"). No grey, no colours, no gradients, no shading, no hatching. Every region the child should colour is a fully closed shape, large enough to tap with a finger (no tiny gaps or slivers). Keep detail moderate: clear big areas for body parts, plus a few simple background shapes (ground line, sun, cloud, plants) that are also closed and colourable.',
    ];

    public static function isConfigured(): bool {
        return Settings::anthropicApiKey() !== '';
    }

    /**
     * @return array{svg: string, model: string, input_tokens: int, output_tokens: int}
     */
    /**
     * @param list<string> $members Ảnh đại diện (style cover): tên tiếng Anh các thẻ trong gói để vẽ chung một cảnh.
     */
    public static function drawSvg(string $nameEn, string $nameVi, string $style, array $members = []): array {
        $apiKey = Settings::anthropicApiKey();
        if ($apiKey === '') {
            throw new \RuntimeException('Chưa có Claude API key. Thêm key ở trang Cài đặt.');
        }
        if (!in_array($style, self::STYLES, true)) {
            throw new \InvalidArgumentException('Kiểu ảnh không hợp lệ.');
        }
        $subject = trim($nameEn) !== '' ? trim($nameEn) : trim($nameVi);
        if ($subject === '') {
            throw new \InvalidArgumentException('Thẻ chưa có tên để vẽ.');
        }

        $members = array_values(array_filter(array_map(fn($m) => trim((string)$m), $members)));
        $userPrompt = $style === 'cover'
            ? "Draw the cover picture for the flashcard topic pack \"{$subject}\""
                . (trim($nameVi) !== '' && trim($nameVi) !== $subject ? " (Vietnamese title: \"" . trim($nameVi) . "\")" : '')
                . ($members ? ". The cards in this pack are: " . implode(', ', array_slice($members, 0, 12)) : '')
                . ".\n" . self::STYLE_PROMPTS[$style]
            : "Draw the flashcard picture for: \"{$subject}\""
                . (trim($nameVi) !== '' && trim($nameVi) !== $subject ? " (Vietnamese name: \"" . trim($nameVi) . "\")" : '')
                . ".\n" . self::STYLE_PROMPTS[$style];

        $model = Settings::claudeModel();
        $client = new Client(apiKey: $apiKey);

        try {
            // Beta endpoint để bật fallbacks: nếu model từ chối theo chính sách, server tự thử model thay thế.
            $message = $client->beta->messages->create(
                model: $model,
                maxTokens: 16000,
                system: self::SYSTEM_PROMPT,
                messages: [['role' => 'user', 'content' => $userPrompt]],
                outputConfig: ['effort' => 'medium'],
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
                requestOptions: ['timeout' => 170, 'maxRetries' => 1],
            );
        } catch (AuthenticationException $e) {
            throw new \RuntimeException('Claude API key không hợp lệ hoặc đã bị thu hồi. Kiểm tra lại ở trang Cài đặt.');
        } catch (RateLimitException $e) {
            throw new \RuntimeException('Claude đang giới hạn tốc độ (429). Đợi một lát rồi thử lại.');
        } catch (APIStatusException $e) {
            throw new \RuntimeException('Claude trả lỗi: ' . $e->getMessage());
        } catch (APIConnectionException $e) {
            throw new \RuntimeException('Không kết nối được tới Claude: ' . $e->getMessage());
        }

        if ($message->stopReason === 'refusal') {
            throw new \RuntimeException('Claude từ chối vẽ thẻ này.');
        }
        if ($message->stopReason === 'max_tokens') {
            throw new \RuntimeException('Ảnh quá chi tiết, Claude chưa vẽ xong. Thử lại.');
        }

        $text = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        return [
            'svg' => self::sanitizeSvg($text),
            'model' => (string)$message->model,
            'input_tokens' => (int)$message->usage->inputTokens,
            'output_tokens' => (int)$message->usage->outputTokens,
        ];
    }

    /**
     * Lấy phần <svg>…</svg>, bỏ mọi thứ có thể chạy mã hoặc tải tài nguyên ngoài, ép kích thước 1920×1080.
     */
    public static function sanitizeSvg(string $raw): string {
        if (!preg_match('#<svg\b.*</svg>#is', $raw, $m)) {
            throw new \RuntimeException('Claude không trả về SVG hợp lệ. Thử lại.');
        }

        $previous = libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $loaded = $doc->loadXML($m[0], LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$loaded || !$doc->documentElement || strtolower($doc->documentElement->localName) !== 'svg') {
            throw new \RuntimeException('SVG Claude trả về bị lỗi cú pháp. Thử lại.');
        }

        $forbidden = ['script', 'foreignobject', 'image', 'iframe', 'object', 'embed', 'audio', 'video', 'animate', 'set', 'text'];
        $xpath = new \DOMXPath($doc);
        foreach (iterator_to_array($xpath->query('//*')) as $node) {
            if (in_array(strtolower($node->localName), $forbidden, true)) {
                $node->parentNode?->removeChild($node);
                continue;
            }
            foreach (iterator_to_array($node->attributes) as $attr) {
                $name = strtolower($attr->nodeName);
                $value = trim($attr->nodeValue);
                $isLink = $name === 'href' || str_ends_with($name, ':href');
                if (str_starts_with($name, 'on')
                    || ($isLink && !str_starts_with($value, '#'))
                    || preg_match('/url\(\s*[\'"]?(?!#)/i', $value)
                    || stripos($value, 'javascript:') !== false) {
                    $node->removeAttribute($attr->nodeName);
                }
            }
        }
        foreach (iterator_to_array($xpath->query('//*[local-name()="style"]')) as $style) {
            if (preg_match('/@import|url\(\s*[\'"]?(?!#)|expression\(/i', $style->textContent)) {
                $style->parentNode?->removeChild($style);
            }
        }

        $root = $doc->documentElement;
        if (!$root->hasAttribute('viewBox')) {
            $root->setAttribute('viewBox', '0 0 ' . self::WIDTH . ' ' . self::HEIGHT);
        }
        $root->setAttribute('width', (string)self::WIDTH);
        $root->setAttribute('height', (string)self::HEIGHT);

        return $doc->saveXML($root);
    }
}
