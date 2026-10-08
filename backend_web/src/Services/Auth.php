<?php

namespace KidsWorld\Backend\Services;

/**
 * Đăng nhập quản trị CMS bằng session PHP. Tài khoản lưu trong data/secrets.json (mật khẩu đã băm).
 */
class Auth {
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        session_name('kwcms');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    /** @return array<string, array{password_hash: string, created_at: string}> */
    public static function users(): array {
        $users = Settings::get('users', []);
        return is_array($users) ? $users : [];
    }

    public static function hasUsers(): bool {
        return count(self::users()) > 0;
    }

    public static function user(): ?string {
        $username = $_SESSION['kw_user'] ?? null;
        return ($username && isset(self::users()[$username])) ? $username : null;
    }

    public static function attempt(string $username, string $password): bool {
        $users = self::users();
        $hash = $users[$username]['password_hash'] ?? null;
        if (!$hash || !password_verify($password, $hash)) {
            usleep(800000); // làm chậm dò mật khẩu
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['kw_user'] = $username;
        return true;
    }

    public static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function setPassword(string $username, string $password): void {
        $users = self::users();
        $users[$username] = [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => $users[$username]['created_at'] ?? date('c'),
        ];
        Settings::save(['users' => $users]);
    }

    public static function csrfToken(): string {
        if (empty($_SESSION['kw_csrf'])) {
            $_SESSION['kw_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['kw_csrf'];
    }

    public static function checkCsrf(): bool {
        return !empty($_SESSION['kw_csrf']) && hash_equals($_SESSION['kw_csrf'], (string)($_POST['_csrf'] ?? ''));
    }

    /** Lỗi của tên đăng nhập / mật khẩu mới, hoặc null nếu hợp lệ. */
    public static function validateCredentials(string $username, string $password): ?string {
        if (!preg_match('/^[a-zA-Z0-9_.-]{3,32}$/', $username)) {
            return 'Tên đăng nhập 3–32 ký tự, chỉ gồm chữ không dấu, số, dấu chấm, gạch dưới, gạch ngang.';
        }
        if (mb_strlen($password) < 8) {
            return 'Mật khẩu cần ít nhất 8 ký tự.';
        }
        return null;
    }
}
