<?php

namespace KidsWorld\Backend\Services;

/**
 * Cài đặt riêng của máy chủ (API key AI, tài khoản quản trị), lưu ở data/secrets.json.
 * File này nằm ngoài thư mục public và đã bị loại khỏi git; không bao giờ trả nội dung của nó ra API.
 */
class Settings {
    private static ?array $cache = null;

    public static function path(): string {
        return __DIR__ . '/../../data/secrets.json';
    }

    public static function all(): array {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $file = self::path();
        $data = is_file($file) ? json_decode((string)file_get_contents($file), true) : null;
        self::$cache = is_array($data) ? $data : [];
        return self::$cache;
    }

    public static function get(string $key, $default = null) {
        return self::all()[$key] ?? $default;
    }

    /** Ghi đè các khoá trong $values; giá trị null thì xoá khoá đó. */
    public static function save(array $values): void {
        $data = self::all();
        foreach ($values as $key => $value) {
            if ($value === null) {
                unset($data[$key]);
            } else {
                $data[$key] = $value;
            }
        }

        $file = self::path();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        // Ghi ra file tạm rồi đổi tên để không bao giờ để lại file hỏng giữa chừng
        $tmp = $file . '.tmp';
        file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        @chmod($tmp, 0600);
        rename($tmp, $file);
        self::$cache = $data;
    }

    /** API key Gemini: ưu tiên file cài đặt, sau đó biến môi trường GEMINI_API_KEY. */
    public static function geminiApiKey(): string {
        return trim((string)(self::get('gemini_api_key') ?: getenv('GEMINI_API_KEY') ?: ''));
    }

    public static function geminiModel(): string {
        return trim((string)(self::get('gemini_model') ?: getenv('GEMINI_MODEL') ?: 'gemini-2.5-flash'));
    }

    /** "AIza…x7Qk" — đủ để nhận ra key nào đang lưu mà không lộ key. */
    public static function maskKey(string $key): string {
        if ($key === '') return '';
        return substr($key, 0, 4) . '…' . substr($key, -4);
    }
}
