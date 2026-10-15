<?php
// Bảng thẻ của một gói, cột Nội dung báo phần còn thiếu. Cần $pack, $items.
?>
<div style="display:flex;gap:8px;flex-wrap:wrap">
    <div class="k-search"><i class="bi bi-search"></i><input class="k-input" type="search" id="itemSearchInput" placeholder="Tìm thẻ" aria-label="Tìm thẻ"></div>
    <div style="margin-left:auto;display:flex;gap:8px">
        <button class="k-btn k-btn-outline" type="button" data-bs-toggle="modal" data-bs-target="#claudeBatchModal" <?= empty($items) ? 'disabled' : '' ?>><i class="bi bi-images"></i>Tạo bộ ảnh</button>
        <a class="k-btn k-btn-outline" href="/packs/<?= urlencode($pack['id']) ?>/items/create"><i class="bi bi-plus-lg"></i>Thêm thẻ</a>
    </div>
</div>
<?php if (!empty($items)) require __DIR__ . '/_claude_batch.php'; ?>

<section class="k-card" style="overflow-x:auto">
    <table class="k-table k-table-stack">
        <thead><tr><th class="k-hide-sm">#</th><th>Thẻ</th><th class="k-hide-sm">Phát âm</th><th>Nội dung</th><th><span class="visually-hidden">Thao tác</span></th></tr></thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="5"><p class="k-empty" style="margin:0">Gói này chưa có thẻ nào. Thêm thẻ để app có nội dung cho bé học.</p></td></tr>
            <?php endif; ?>
            <?php foreach ($items as $idx => $it): $editUrl = '/items/' . urlencode($it['id']) . '/edit'; ?>
                <tr class="item-row" data-search="<?= kw_e(mb_strtolower($it['name_vi'] . ' ' . $it['name_en'] . ' ' . $it['id'])) ?>">
                    <td class="k-muted k-num k-hide-sm" style="width:40px"><?= $idx + 1 ?></td>
                    <td class="k-td-main">
                        <a class="k-cell-main" href="<?= $editUrl ?>">
                            <?= kw_thumb(kw_first_image($it['images_json'] ?? '')) ?>
                            <div class="k-truncate"><div class="k-row-title k-truncate"><?= kw_e($it['name_vi']) ?></div><div class="k-xs k-muted k-truncate"><?= kw_e($it['name_en']) ?></div></div>
                        </a>
                    </td>
                    <td class="k-hide-sm k-muted"><?= $it['phonics_en'] ? kw_e($it['phonics_en']) : '—' ?></td>
                    <td>
                        <div class="k-checks">
                            <?php foreach (kw_item_checks($it) as $label => $ok): ?>
                                <span class="k-check <?= $ok ? 'k-hide-sm' : 'is-missing' ?>"><i class="bi <?= $ok ? 'bi-check2' : 'bi-dash' ?>"></i><?= $label ?></span>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <td class="k-td-actions">
                        <div class="k-row-actions">
                            <a class="k-btn k-btn-ghost k-btn-sm k-btn-icon" href="<?= $editUrl ?>" aria-label="Sửa thẻ" title="Sửa thẻ"><i class="bi bi-pencil"></i></a>
                            <div class="dropdown">
                                <button class="k-btn k-btn-ghost k-btn-sm k-btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Thêm thao tác"><i class="bi bi-three-dots"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item k-item-danger" href="/items/<?= urlencode($it['id']) ?>/delete" onclick="return confirm('Xoá thẻ «<?= kw_e(addslashes($it['name_vi'])) ?>»? Không hoàn tác được.');"><i class="bi bi-trash3"></i>Xoá thẻ</a></li>
                                </ul>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr id="itemNoResult" style="display:none"><td colspan="5"><p class="k-empty" style="margin:0">Không có thẻ nào khớp từ khoá. Thử từ khoá khác.</p></td></tr>
        </tbody>
    </table>
</section>

<script>
    document.getElementById('itemSearchInput').addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();
        let visible = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const show = !query || row.dataset.search.includes(query);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        document.getElementById('itemNoResult').style.display = (visible === 0 && document.querySelectorAll('.item-row').length) ? '' : 'none';
    });
</script>
