<?php
$pageTitle = $pack['title_vi'];
$crumbs = [['Gói chủ đề', '/packs']];
$editBase = '/packs/' . urlencode($pack['id']) . '/edit';
$packHeaderActions = '<a class="k-btn k-btn-outline" href="/api/packs/' . urlencode($pack['id']) . '/download" target="_blank" rel="noopener"><i class="bi bi-download"></i>Xuất JSON</a>'
                   . '<a class="k-btn k-btn-primary" href="' . $editBase . '?tab=general">Sửa thông tin gói</a>';
require __DIR__ . '/../packs/_header.php';
?>

<nav class="k-tabs" aria-label="Phần của gói">
    <a class="k-tab" href="<?= $editBase ?>?tab=general">Thông tin chung</a>
    <a class="k-tab" href="<?= $editBase ?>?tab=items" aria-selected="true">Thẻ từ vựng<span class="k-count"><?= count($items) ?></span></a>
    <a class="k-tab" href="<?= $editBase ?>?tab=games">Mini-game</a>
</nav>

<?php require __DIR__ . '/../packs/_items_table.php'; ?>
