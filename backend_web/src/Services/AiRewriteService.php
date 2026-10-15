<?php

namespace KidsWorld\Backend\Services;

/**
 * Dùng AI (Gemini hoặc Claude) viết lại nội dung chữ của một gói và các thẻ trong gói.
 * Chỉ trả về đề xuất; người dùng xem trước và chọn chỗ nào áp dụng rồi mới lưu.
 */
class AiRewriteService {
    /** Trường chữ của gói được phép viết lại. */
    public const PACK_FIELDS = [
        'title_vi' => 'Tên tiếng Việt',
        'title_en' => 'Tên tiếng Anh',
        'description_vi' => 'Mô tả tiếng Việt',
        'description_en' => 'Mô tả tiếng Anh',
    ];

    /** Trường chữ của thẻ được phép viết lại. Tên thẻ giữ nguyên vì ảnh và phát âm dựa theo tên. */
    public const ITEM_FIELDS = [
        'description_vi' => 'Mô tả tiếng Việt',
        'description_en' => 'Mô tả tiếng Anh',
        'phonics_en' => 'Phiên âm tiếng Anh',
        'sfx_sound' => 'Tiếng kêu',
        'fun_fact_vi' => 'Sự thật thú vị (VI)',
        'fun_fact_en' => 'Sự thật thú vị (EN)',
        'prompt_question_vi' => 'Câu hỏi đố bé (VI)',
        'prompt_question_en' => 'Câu hỏi đố bé (EN)',
        'action_hint_vi' => 'Gợi ý vận động',
    ];

    /**
     * @return array{pack: array<string,string>, items: list<array<string,string>>, provider: string, model: string}
     */
    public static function rewrite(array $pack, array $items, bool $includePack, bool $includeItems, string $instruction, ?string $provider): array {
        if (!$includePack && !$includeItems) {
            throw new \InvalidArgumentException('Chọn ít nhất một phần để viết lại.');
        }
        $providers = AiService::availableProviders();
        if (!$providers) {
            throw new \RuntimeException('Chưa có API key Gemini hay Claude. Thêm key ở trang Cài đặt.');
        }
        if (!$provider || !isset($providers[$provider])) {
            $provider = array_key_first($providers);
        }
        if ($includeItems && !$items) {
            $includeItems = false;
            if (!$includePack) {
                throw new \InvalidArgumentException('Gói chưa có thẻ nào để viết lại.');
            }
        }

        $current = [];
        if ($includePack) {
            $current['pack'] = array_map(fn($f) => (string)($pack[$f] ?? ''), array_combine(array_keys(self::PACK_FIELDS), array_keys(self::PACK_FIELDS)));
        }
        if ($includeItems) {
            $current['items'] = array_map(function ($it) {
                $row = ['id' => $it['id'], 'name_vi' => $it['name_vi'], 'name_en' => $it['name_en']];
                foreach (array_keys(self::ITEM_FIELDS) as $f) {
                    $row[$f] = (string)($it[$f] ?? '');
                }
                return $row;
            }, $items);
        }

        $ageMin = (int)($pack['target_age_min'] ?? 3);
        $ageMax = (int)($pack['target_age_max'] ?? 8);
        $prompt = self::buildPrompt($pack, $current, $ageMin, $ageMax, trim($instruction));
        $schema = self::schema($includePack, $includeItems);

        $raw = $provider === 'claude' ? self::callClaude($prompt, $schema) : self::callGemini($prompt);
        $model = $provider === 'claude' ? Settings::claudeModel() : Settings::geminiModel();

        // Chỉ giữ trường cho phép, thẻ đúng id đã gửi; bỏ mọi thứ AI tự thêm
        $result = ['pack' => [], 'items' => [], 'provider' => $provider, 'model' => $model];
        if ($includePack && is_array($raw['pack'] ?? null)) {
            foreach (array_keys(self::PACK_FIELDS) as $f) {
                if (isset($raw['pack'][$f]) && is_string($raw['pack'][$f])) {
                    $result['pack'][$f] = trim($raw['pack'][$f]);
                }
            }
        }
        if ($includeItems && is_array($raw['items'] ?? null)) {
            $knownIds = array_column($items, 'id');
            foreach ($raw['items'] as $row) {
                if (!is_array($row) || !in_array($row['id'] ?? null, $knownIds, true)) continue;
                $clean = ['id' => $row['id']];
                foreach (array_keys(self::ITEM_FIELDS) as $f) {
                    if (isset($row[$f]) && is_string($row[$f])) {
                        $clean[$f] = trim($row[$f]);
                    }
                }
                $result['items'][] = $clean;
            }
        }
        if (!$result['pack'] && !$result['items']) {
            throw new \RuntimeException('AI không trả về nội dung hợp lệ. Thử lại.');
        }
        return $result;
    }

    private static function buildPrompt(array $pack, array $current, int $ageMin, int $ageMax, string $instruction): string {
        $json = json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $topic = trim(($pack['title_vi'] ?? '') . ' / ' . ($pack['title_en'] ?? ''), ' /');
        $extra = $instruction !== '' ? "\n\nYÊU CẦU RIÊNG CỦA NGƯỜI BIÊN TẬP (ưu tiên làm theo):\n" . $instruction : '';

        return <<<PROMPT
Bạn là biên tập viên nội dung giáo dục mầm non cho ứng dụng học tiếng Anh song ngữ "Kids World", dành cho bé {$ageMin}–{$ageMax} tuổi.
Gói chủ đề: "{$topic}".

Hãy viết lại nội dung dưới đây cho hay hơn:
- Câu ngắn, rõ, ấm áp, hợp lứa tuổi {$ageMin}–{$ageMax}; từ ngữ đơn giản, gợi hình, khơi gợi tò mò.
- Kiến thức phải ĐÚNG sự thật khoa học; sửa chỗ sai hoặc mơ hồ, không bịa số liệu.
- Trường *_vi viết tiếng Việt có dấu chuẩn; trường *_en viết tiếng Anh tự nhiên, cùng ý với bản tiếng Việt.
- Mô tả 1–2 câu. Sự thật thú vị 1 câu gây bất ngờ. Câu hỏi đố bé là câu hỏi mở để bố mẹ hỏi bé. Gợi ý vận động là một hoạt động an toàn bố mẹ và bé làm cùng.
- phonics_en là phiên âm IPA tách âm cho bé, ví dụ "/ˈlaɪ.ən/". sfx_sound là từ tượng thanh tiếng kêu hoặc tiếng động thật, ví dụ "Gừ gừ... Roar!".
- Trường đang trống thì viết mới. Trường đã tốt thì có thể giữ nguyên hoặc chỉnh nhẹ.
- KHÔNG đổi id, name_vi, name_en (chỉ để biết thẻ nói về gì). Không thêm emoji mới nếu bản gốc không có.{$extra}

Nội dung hiện tại (JSON):
{$json}

Trả về DUY NHẤT JSON cùng cấu trúc (pack và/hoặc items như trên, mỗi thẻ có id và các trường đã viết lại, không cần name_vi/name_en), không markdown, không giải thích.
PROMPT;
    }

    private static function schema(bool $includePack, bool $includeItems): array {
        $str = ['type' => 'string'];
        $object = fn(array $props) => ['type' => 'object', 'properties' => $props, 'required' => array_keys($props), 'additionalProperties' => false];
        $props = [];
        if ($includePack) {
            $props['pack'] = $object(array_fill_keys(array_keys(self::PACK_FIELDS), $str));
        }
        if ($includeItems) {
            $props['items'] = ['type' => 'array', 'items' => $object(['id' => $str] + array_fill_keys(array_keys(self::ITEM_FIELDS), $str))];
        }
        return $object($props);
    }

    private static function callClaude(string $prompt, array $schema): array {
        $model = Settings::claudeModel();
        $client = new \Anthropic\Client(apiKey: Settings::anthropicApiKey());
        try {
            // Beta endpoint để bật fallbacks: nếu model từ chối theo chính sách, server tự thử model thay thế.
            $message = $client->beta->messages->create(
                model: $model,
                maxTokens: 16000,
                messages: [['role' => 'user', 'content' => $prompt]],
                outputConfig: ['effort' => 'medium', 'format' => ['type' => 'json_schema', 'schema' => $schema]],
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
                requestOptions: ['timeout' => 170, 'maxRetries' => 1],
            );
        } catch (\Anthropic\Core\Exceptions\AuthenticationException $e) {
            throw new \RuntimeException('Claude API key không hợp lệ hoặc đã bị thu hồi. Kiểm tra lại ở Cài đặt.');
        } catch (\Anthropic\Core\Exceptions\RateLimitException $e) {
            throw new \RuntimeException('Claude đang giới hạn tốc độ (429). Đợi một lát rồi thử lại.');
        } catch (\Anthropic\Core\Exceptions\APIStatusException $e) {
            throw new \RuntimeException('Claude trả lỗi: ' . $e->getMessage());
        } catch (\Anthropic\Core\Exceptions\APIConnectionException $e) {
            throw new \RuntimeException('Không kết nối được tới Claude: ' . $e->getMessage());
        }
        if ($message->stopReason === 'refusal') {
            throw new \RuntimeException('Claude từ chối viết lại nội dung này.');
        }
        if ($message->stopReason === 'max_tokens') {
            throw new \RuntimeException('Nội dung quá dài, Claude chưa viết xong. Bỏ bớt phần cần viết lại rồi thử lại.');
        }
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $data = json_decode($block->text, true);
                if (is_array($data)) return $data;
            }
        }
        throw new \RuntimeException('Claude không trả về JSON hợp lệ. Thử lại.');
    }

    private static function callGemini(string $prompt): array {
        $model = Settings::geminiModel();
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['responseMimeType' => 'application/json', 'temperature' => 0.6],
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-goog-api-key: ' . Settings::geminiApiKey()],
            CURLOPT_TIMEOUT => 120,
        ]);
        $res = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($res === false) {
            throw new \RuntimeException('Không kết nối được tới Gemini: ' . $err);
        }
        $decoded = json_decode($res, true);
        if ($code !== 200) {
            throw new \RuntimeException('Gemini (' . $model . ') trả lỗi: ' . ($decoded['error']['message'] ?? 'HTTP ' . $code));
        }
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/', '', trim($decoded['candidates'][0]['content']['parts'][0]['text'] ?? ''));
        $data = json_decode($text, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Gemini không trả về JSON hợp lệ. Thử lại.');
        }
        return $data;
    }
}
