<?php
// Đầu trang của một gói: ảnh, tên, trạng thái, phiên bản. Cần $pack, $items; nút bên phải truyền qua $packHeaderActions.
$isActivePack = !isset($pack['is_active']) || (int)$pack['is_active'] === 1;
?>
<div class="k-page-head">
    <?= kw_thumb($pack['thumbnail_url'] ?? '', 56) ?>
    <div class="k-page-head-text">
        <h1><?= kw_e($pack['title_vi']) ?></h1>
        <div class="k-meta">
            <span class="k-badge <?= $isActivePack ? 'k-badge-success' : 'k-badge-neutral' ?>"><?= $isActivePack ? 'Đang hiện' : 'Tạm ẩn' ?></span>
            <span>v<?= (int)$pack['version'] ?> · <?= count($items ?? []) ?> thẻ · <?= (int)$pack['target_age_min'] ?>–<?= (int)$pack['target_age_max'] ?> tuổi</span>
            <span class="k-mono"><?= kw_e($pack['id']) ?></span>
        </div>
    </div>
    <div class="k-page-head-actions"><?= $packHeaderActions ?? '' ?></div>
</div>
