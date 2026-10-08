<?php
require_once __DIR__ . '/helpers.php';

// View chạy trước để khai đầu trang: $pageTitle, $crumbs [[nhãn, link]], $topActions (HTML), $navActive.
ob_start();
require $contentView;
$kwContent = ob_get_clean();

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (!isset($navActive)) {
    $navActive = match (true) {
        str_starts_with($uri, '/packs'), str_starts_with($uri, '/items') => 'packs',
        str_starts_with($uri, '/ai-generator') => 'ai',
        str_starts_with($uri, '/guide') => 'guide',
        str_starts_with($uri, '/docs') => 'docs',
        str_starts_with($uri, '/settings') => 'settings',
        default => 'dashboard',
    };
}
$pageTitle = $pageTitle ?? ($title ?? 'Kids World CMS');
$crumbs = $crumbs ?? [];
$topActions = $topActions ?? '';
try {
    $packCountNav = \KidsWorld\Backend\Models\TopicPack::stats()['total_packs'];
} catch (\Throwable $e) {
    $packCountNav = null;
}

$kwHost = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
$navItems = [
    ['dashboard', '/', 'bi-grid-1x2', 'Tổng quan'],
    ['packs', '/packs', 'bi-collection', 'Gói chủ đề'],
    ['ai', '/ai-generator', 'bi-stars', 'Tạo bằng AI'],
];
$docItems = [
    ['guide', '/guide', 'bi-plug', 'Kết nối app'],
    ['docs', '/docs', 'bi-file-earmark-code', 'Đặc tả dữ liệu'],
];
$flash = null;
if (isset($_GET['created'])) $flash = ['success', 'bi-check-circle-fill', 'Đã tạo xong.'];
elseif (isset($_GET['updated'])) $flash = ['info', 'bi-info-circle-fill', 'Đã lưu và tăng phiên bản của gói.'];
elseif (isset($_GET['deleted'])) $flash = ['warning', 'bi-trash3-fill', 'Đã xoá.'];
?><!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= kw_e(($pageTitle !== 'Kids World CMS' ? $pageTitle . ' · ' : '') . 'Kids World CMS') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="/assets/css/kw-ui.css" rel="stylesheet">
    <link href="/assets/css/kw-bootstrap.css" rel="stylesheet">
</head>
<body class="k-body">
<div class="k-app">
    <div class="k-scrim" data-kw-menu></div>
    <aside class="k-side" aria-label="Điều hướng">
        <a class="k-side-head" href="/"><span class="k-logo"><i class="bi bi-balloon-heart-fill"></i></span><span class="k-side-name">Kids World CMS</span></a>
        <nav class="k-side-nav">
            <?php foreach ($navItems as [$key, $href, $icon, $label]): ?>
                <a class="k-nav" href="<?= $href ?>" <?= $navActive === $key ? 'aria-current="page"' : '' ?>>
                    <i class="bi <?= $icon ?>"></i><?= $label ?>
                    <?php if ($key === 'packs' && $packCountNav !== null): ?><span class="k-count"><?= (int)$packCountNav ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
            <div class="k-nav-label">Tài liệu</div>
            <?php foreach ($docItems as [$key, $href, $icon, $label]): ?>
                <a class="k-nav" href="<?= $href ?>" <?= $navActive === $key ? 'aria-current="page"' : '' ?>><i class="bi <?= $icon ?>"></i><?= $label ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="k-side-foot" style="display:grid;gap:4px">
            <a class="k-nav" href="/settings" <?= $navActive === 'settings' ? 'aria-current="page"' : '' ?>><i class="bi bi-gear"></i>Cài đặt</a>
            <?php $kwUser = \KidsWorld\Backend\Services\Auth::user(); ?>
            <div class="k-api" style="padding:8px 8px 8px 12px">
                <span class="k-stat-icon" style="border-radius:50%;font-weight:600;font-size:13px"><?= kw_e(mb_strtoupper(mb_substr((string)$kwUser, 0, 1))) ?></span>
                <div class="k-truncate" style="flex:1"><div class="k-sm k-truncate" style="font-weight:500"><?= kw_e($kwUser) ?></div><div class="k-xs k-muted k-truncate"><?= kw_e($kwHost) ?></div></div>
                <form method="POST" action="/logout" style="margin:0">
                    <input type="hidden" name="_csrf" value="<?= kw_e(\KidsWorld\Backend\Services\Auth::csrfToken()) ?>">
                    <button class="k-btn k-btn-ghost k-btn-sm k-btn-icon" type="submit" aria-label="Đăng xuất" title="Đăng xuất"><i class="bi bi-box-arrow-right"></i></button>
                </form>
            </div>
        </div>
    </aside>

    <div class="k-main">
        <header class="k-top">
            <button class="k-btn k-btn-ghost k-btn-icon k-burger" type="button" aria-label="Mở menu" data-kw-menu><i class="bi bi-list"></i></button>
            <?php if ($crumbs): ?>
                <nav class="k-crumb" aria-label="Đường dẫn" style="min-width:0">
                    <?php foreach ($crumbs as $i => [$label, $href]): $isFirst = $i === 0 && count($crumbs) > 1; ?>
                        <a class="<?= $isFirst ? 'k-hide-sm' : 'k-truncate' ?>" href="<?= kw_e($href) ?>" <?= $isFirst ? '' : 'style="max-width:240px;display:block;line-height:32px"' ?>><?= kw_e($label) ?></a>
                        <i class="bi bi-chevron-right k-xs <?= $isFirst ? 'k-hide-sm' : '' ?>"></i>
                    <?php endforeach; ?>
                </nav>
            <?php else: ?>
                <h1><?= kw_e($pageTitle) ?></h1>
            <?php endif; ?>
            <?php if ($topActions): ?><div class="k-top-actions"><?= $topActions ?></div><?php endif; ?>
        </header>

        <main class="k-content">
            <?php if ($flash): ?>
                <div class="k-flash k-flash-<?= $flash[0] ?>" role="status"><i class="bi <?= $flash[1] ?>"></i><span style="flex:1"><?= $flash[2] ?></span>
                    <button type="button" class="k-btn k-btn-ghost k-btn-sm k-btn-icon" aria-label="Đóng thông báo" onclick="this.parentElement.remove()"><i class="bi bi-x-lg"></i></button></div>
            <?php endif; ?>
            <?= $kwContent ?>
        </main>
        <?= $kwAfterMain ?? '' ?>
    </div>
</div>

<div class="k-toast" id="kwToast" role="status" aria-live="polite"><i class="bi bi-check2-circle"></i><span id="kwToastMsg"></span></div>

<?php require __DIR__ . '/image_picker_modal.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Menu trượt ở màn hẹp: ☰ mở, bấm lớp phủ hay Esc thì đóng
    document.querySelectorAll('[data-kw-menu]').forEach(el => el.addEventListener('click', () => document.body.toggleAttribute('data-menu-open')));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') document.body.removeAttribute('data-menu-open'); });

    function kwToast(message) {
        const toast = document.getElementById('kwToast');
        document.getElementById('kwToastMsg').textContent = message;
        toast.classList.add('is-show');
        clearTimeout(window.__kwToastTimer);
        window.__kwToastTimer = setTimeout(() => toast.classList.remove('is-show'), 2500);
    }

    function kwCopy(text) {
        navigator.clipboard.writeText(text)
            .then(() => kwToast('Đã chép: ' + text))
            .catch(() => prompt('Sao chép địa chỉ này:', text));
    }
</script>
</body>
</html>
