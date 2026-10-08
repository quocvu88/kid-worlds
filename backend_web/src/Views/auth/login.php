<?php
require_once __DIR__ . '/../layout/helpers.php';
$heading = $isSetup ? 'Tạo tài khoản quản trị' : 'Đăng nhập';
?><!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $heading ?> · Kids World CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/assets/css/kw-ui.css" rel="stylesheet">
    <style>
        .k-auth { min-height: 100vh; display: grid; place-items: center; padding: 24px 16px; }
        .k-auth-card { width: 100%; max-width: 400px; padding: 32px 24px; }
        .k-auth .k-input, .k-auth .k-btn { min-height: 48px; }
        @media (min-width: 640px) { .k-auth-card { padding: 40px 32px; } }
    </style>
</head>
<body class="k-body">
<main class="k-auth">
    <div class="k-card k-auth-card">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:24px">
            <span class="k-logo"><i class="bi bi-balloon-heart-fill"></i></span><span class="k-side-name">Kids World CMS</span>
        </div>
        <h1 style="font-size:20px;font-weight:600;margin:0"><?= $heading ?></h1>
        <p class="k-muted" style="margin:4px 0 24px">
            <?= $isSetup ? 'CMS chưa có tài khoản nào. Tài khoản bạn tạo ở đây sẽ là tài khoản quản trị.' : 'Đăng nhập để quản lý gói chủ đề.' ?>
        </p>

        <?php if (!empty($error)): ?>
            <div class="k-banner" role="alert" style="margin-bottom:16px"><i class="bi bi-exclamation-circle"></i><span><?= kw_e($error) ?></span></div>
        <?php endif; ?>

        <form method="POST" action="/login" style="display:grid;gap:16px">
            <input type="hidden" name="_csrf" value="<?= kw_e($csrf) ?>">
            <input type="hidden" name="next" value="<?= kw_e($next ?? '/') ?>">
            <div class="k-field">
                <label class="k-label" for="username">Tên đăng nhập</label>
                <input class="k-input" id="username" name="username" type="text" autocomplete="username" required autofocus value="<?= kw_e($username ?? '') ?>" placeholder="admin">
            </div>
            <div class="k-field">
                <label class="k-label" for="password">Mật khẩu</label>
                <input class="k-input" id="password" name="password" type="password" autocomplete="<?= $isSetup ? 'new-password' : 'current-password' ?>" required <?= $isSetup ? 'minlength="8"' : '' ?>>
                <?php if ($isSetup): ?><span class="k-hint">Ít nhất 8 ký tự</span><?php endif; ?>
            </div>
            <?php if ($isSetup): ?>
                <div class="k-field">
                    <label class="k-label" for="password_confirm">Nhập lại mật khẩu</label>
                    <input class="k-input" id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required minlength="8">
                </div>
            <?php endif; ?>
            <button class="k-btn k-btn-primary" type="submit"><?= $isSetup ? 'Tạo tài khoản và đăng nhập' : 'Đăng nhập' ?></button>
        </form>
    </div>
</main>
</body>
</html>
