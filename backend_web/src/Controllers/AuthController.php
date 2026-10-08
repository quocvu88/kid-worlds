<?php

namespace KidsWorld\Backend\Controllers;

use KidsWorld\Backend\Services\Auth;

class AuthController {
    private function renderLogin(array $data = []): void {
        extract($data);
        $isSetup = !Auth::hasUsers();
        $csrf = Auth::csrfToken();
        require __DIR__ . '/../Views/auth/login.php';
        exit;
    }

    private function safeNext(?string $next): string {
        // Chỉ quay về đường dẫn nội bộ, không cho chuyển hướng ra ngoài
        return ($next && str_starts_with($next, '/') && !str_starts_with($next, '//')) ? $next : '/';
    }

    public function showLogin(): void {
        if (Auth::user()) {
            header('Location: /');
            exit;
        }
        $this->renderLogin(['next' => $this->safeNext($_GET['next'] ?? null)]);
    }

    public function login(): void {
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $next = $this->safeNext($_POST['next'] ?? null);

        if (!Auth::checkCsrf()) {
            $this->renderLogin(['error' => 'Phiên đăng nhập đã hết hạn, vui lòng thử lại.', 'username' => $username, 'next' => $next]);
        }

        // Lần đầu chưa có tài khoản: tạo tài khoản quản trị từ form này
        if (!Auth::hasUsers()) {
            $confirm = (string)($_POST['password_confirm'] ?? '');
            $error = Auth::validateCredentials($username, $password)
                ?? ($password !== $confirm ? 'Hai lần nhập mật khẩu không khớp.' : null);
            if ($error) {
                $this->renderLogin(['error' => $error, 'username' => $username, 'next' => $next]);
            }
            Auth::setPassword($username, $password);
        }

        if (!Auth::attempt($username, $password)) {
            $this->renderLogin(['error' => 'Sai tên đăng nhập hoặc mật khẩu.', 'username' => $username, 'next' => $next]);
        }
        header('Location: ' . $next);
        exit;
    }

    public function logout(): void {
        if (Auth::checkCsrf()) {
            Auth::logout();
        }
        header('Location: /login');
        exit;
    }
}
