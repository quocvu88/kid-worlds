<?php
$pageTitle = 'Gói chủ đề';
$activeCount = count(array_filter($packs, fn($p) => (int)$p['is_active'] === 1));
$hiddenCount = count($packs) - $activeCount;
$categories = array_values(array_unique(array_map(fn($p) => (string)$p['category'], $packs)));
sort($categories);
?>

<div style="display:flex;flex-direction:column;gap:12px">
    <div class="k-tabs" role="tablist" id="packStatusTabs">
        <?php foreach ([['all', 'Tất cả', count($packs)], ['active', 'Đang hiện', $activeCount], ['hidden', 'Tạm ẩn', $hiddenCount]] as $i => [$key, $label, $count]): ?>
            <button class="k-tab" type="button" role="tab" data-status="<?= $key ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" style="background:none;border-top:0;border-left:0;border-right:0"><?= $label ?><span class="k-count"><?= $count ?></span></button>
        <?php endforeach; ?>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <div class="k-search"><i class="bi bi-search"></i><input class="k-input" type="search" id="packSearchInput" placeholder="Tìm theo tên hoặc mã gói" aria-label="Tìm gói"></div>
        <select class="k-select" id="categoryFilter" style="width:auto" aria-label="Danh mục">
            <option value="">Mọi danh mục</option>
            <?php foreach ($categories as $cat): ?><option value="<?= kw_e($cat) ?>"><?= kw_e($cat) ?></option><?php endforeach; ?>
        </select>
        <div style="margin-left:auto;display:flex;gap:8px">
            <a class="k-btn k-btn-outline" href="/ai-generator"><i class="bi bi-stars"></i>Tạo bằng AI</a>
            <a class="k-btn k-btn-primary" href="/packs/create"><i class="bi bi-plus-lg"></i>Tạo gói</a>
        </div>
    </div>
</div>

<section class="k-card" style="overflow-x:auto">
    <table class="k-table k-table-stack">
        <thead>
            <tr><th>Gói</th><th class="k-hide-sm">Danh mục</th><th class="k-hide-sm">Độ tuổi</th><th class="k-right k-hide-sm">Thẻ</th><th>Độ đầy đủ</th><th class="k-hide-sm">Cập nhật</th><th><span class="visually-hidden">Thao tác</span></th></tr>
        </thead>
        <tbody>
            <?php if (empty($packs)): ?>
                <tr><td colspan="7"><p class="k-empty" style="margin:0">Chưa có gói chủ đề nào. <a href="/ai-generator" style="color:var(--foreground);font-weight:500">Tạo gói đầu tiên bằng AI</a></p></td></tr>
            <?php endif; ?>
            <?php foreach ($packs as $p): $editUrl = '/packs/' . urlencode($p['id']) . '/edit'; ?>
                <tr class="pack-row" data-status="<?= (int)$p['is_active'] === 1 ? 'active' : 'hidden' ?>" data-category="<?= kw_e($p['category']) ?>" data-search="<?= kw_e(mb_strtolower($p['title_vi'] . ' ' . $p['title_en'] . ' ' . $p['id'])) ?>">
                    <td class="k-td-main">
                        <a class="k-cell-main" href="<?= $editUrl ?>">
                            <?= kw_thumb($p['thumbnail_url'] ?? '') ?>
                            <div class="k-truncate">
                                <div class="k-row-title k-truncate"><?= kw_e($p['title_vi']) ?></div>
                                <div class="k-xs k-muted k-truncate"><span class="k-mono"><?= kw_e($p['id']) ?></span> · v<?= (int)$p['version'] ?> · <?= kw_e($p['size_mb']) ?> MB<?= (int)$p['is_active'] === 1 ? '' : ' · Tạm ẩn' ?></div>
                            </div>
                        </a>
                    </td>
                    <td class="k-hide-sm"><span class="k-badge k-badge-neutral"><?= kw_e($p['category']) ?></span></td>
                    <td class="k-hide-sm k-num k-nowrap"><?= (int)$p['target_age_min'] ?>–<?= (int)$p['target_age_max'] ?> tuổi</td>
                    <td class="k-right k-num k-hide-sm"><?= (int)$p['item_count'] ?></td>
                    <td style="width:170px"><?= (int)$p['item_count'] ? kw_progress(kw_pack_pct($p)) : '<span class="k-badge k-badge-warning">Chưa có thẻ</span>' ?></td>
                    <td class="k-hide-sm k-muted k-num k-nowrap"><?= kw_date($p['updated_at'] ?? null) ?></td>
                    <td class="k-td-actions">
                        <div class="k-row-actions">
                            <a class="k-btn k-btn-ghost k-btn-sm k-btn-icon" href="/api/packs/<?= urlencode($p['id']) ?>/download" target="_blank" rel="noopener" aria-label="Tải JSON" title="Tải JSON"><i class="bi bi-download"></i></a>
                            <div class="dropdown">
                                <button class="k-btn k-btn-ghost k-btn-sm k-btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Thêm thao tác"><i class="bi bi-three-dots"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="<?= $editUrl ?>"><i class="bi bi-pencil"></i>Sửa gói</a></li>
                                    <li><a class="dropdown-item" href="<?= $editUrl ?>?tab=items"><i class="bi bi-card-text"></i>Quản lý thẻ</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item k-item-danger" href="/packs/<?= urlencode($p['id']) ?>/delete" onclick="return confirm('Xoá gói «<?= kw_e(addslashes($p['title_vi'])) ?>» cùng toàn bộ <?= (int)$p['item_count'] ?> thẻ bên trong? Không hoàn tác được.');"><i class="bi bi-trash3"></i>Xoá gói</a></li>
                                </ul>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr id="packNoResult" style="display:none"><td colspan="7"><p class="k-empty" style="margin:0">Không có gói nào khớp bộ lọc. <button type="button" class="k-btn k-btn-ghost" style="min-height:0;padding:0;color:var(--foreground);text-decoration:underline" onclick="clearPackFilters()">Xoá lọc</button></p></td></tr>
        </tbody>
    </table>
    <?php if (!empty($packs)): ?>
        <div style="padding:12px 20px;border-top:1px solid var(--border)" class="k-muted"><span id="visiblePacksCount"><?= count($packs) ?></span> gói</div>
    <?php endif; ?>
</section>

<script>
    let packStatus = 'all';
    function filterPacks() {
        const query = (document.getElementById('packSearchInput').value || '').trim().toLowerCase();
        const cat = document.getElementById('categoryFilter').value;
        let visible = 0;
        document.querySelectorAll('.pack-row').forEach(row => {
            const show = (packStatus === 'all' || row.dataset.status === packStatus)
                && (!cat || row.dataset.category === cat)
                && (!query || row.dataset.search.includes(query));
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        const counter = document.getElementById('visiblePacksCount');
        if (counter) counter.textContent = visible;
        const noResult = document.getElementById('packNoResult');
        if (noResult) noResult.style.display = (visible === 0 && document.querySelectorAll('.pack-row').length) ? '' : 'none';
    }
    function clearPackFilters() {
        document.getElementById('packSearchInput').value = '';
        document.getElementById('categoryFilter').value = '';
        document.querySelector('#packStatusTabs [data-status="all"]').click();
        document.getElementById('packSearchInput').focus();
    }
    document.querySelectorAll('#packStatusTabs .k-tab').forEach(tab => tab.addEventListener('click', () => {
        document.querySelectorAll('#packStatusTabs .k-tab').forEach(t => t.setAttribute('aria-selected', 'false'));
        tab.setAttribute('aria-selected', 'true');
        packStatus = tab.dataset.status;
        filterPacks();
    }));
    document.getElementById('packSearchInput').addEventListener('input', filterPacks);
    document.getElementById('categoryFilter').addEventListener('change', filterPacks);
</script>
