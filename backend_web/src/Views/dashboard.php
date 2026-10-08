<?php
$pageTitle = 'Tổng quan';
$topActions = '<a class="k-btn k-btn-outline" href="/ai-generator"><i class="bi bi-stars"></i><span class="k-btn-label">Tạo bằng AI</span></a>'
            . '<a class="k-btn k-btn-primary" href="/packs/create"><i class="bi bi-plus-lg"></i><span class="k-btn-label">Tạo gói</span></a>';

$totalPacks = (int)($stats['total_packs'] ?? 0);
$totalItems = (int)($stats['total_items'] ?? 0);
$avgItems = $totalPacks ? number_format($totalItems / $totalPacks, 1, ',', '.') : '0';

$byCompleteness = $packs;
usort($byCompleteness, fn($a, $b) => kw_pack_pct($a) <=> kw_pack_pct($b));
$recent = $packs;
usort($recent, fn($a, $b) => strcmp((string)($b['updated_at'] ?? ''), (string)($a['updated_at'] ?? '')));
$recent = array_slice($recent, 0, 3);

$isPublicHost = !empty($serverUrl) && !str_contains($serverUrl, 'localhost') && !str_contains($serverUrl, '127.0.0.1') && !str_contains($serverUrl, '10.0.2.2');
$addresses = [];
if ($isPublicHost) $addresses[] = ['Máy chủ trực tuyến', $serverUrl];
$addresses[] = ['Máy ảo Android Studio', 'http://10.0.2.2:8000'];
$addresses[] = ['Thiết bị thật cùng Wi-Fi', 'http://' . ($localIp ?? '192.168.2.6') . ':8000'];
?>

<section class="k-card k-stats" aria-label="Số liệu">
    <?php foreach ([
        ['bi-collection', 'Gói chủ đề', $totalPacks, $gaps['empty_packs'] ? $gaps['empty_packs'] . ' gói chưa có thẻ' : 'Gói nào cũng có thẻ'],
        ['bi-card-text', 'Thẻ từ vựng', $totalItems, 'Trung bình ' . $avgItems . ' thẻ mỗi gói'],
        ['bi-camera', 'Thẻ thiếu ảnh thật', $gaps['missing_real'], $gaps['packs_missing_real'] ? 'Ở ' . $gaps['packs_missing_real'] . ' gói' : 'Đã đủ ảnh thật'],
        ['bi-palette', 'Thẻ thiếu tranh tô màu', $gaps['missing_line'], 'Game Tô màu cần ảnh nét'],
    ] as [$icon, $label, $value, $sub]): ?>
        <div class="k-stat">
            <span class="k-stat-icon"><i class="bi <?= $icon ?>"></i></span>
            <div class="k-truncate">
                <div class="k-stat-label k-truncate"><?= $label ?></div>
                <div class="k-stat-value"><?= (int)$value ?></div>
                <div class="k-stat-sub k-truncate"><?= kw_e($sub) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</section>

<?php if (empty($packs)): ?>
    <section class="k-card">
        <div class="k-card-body" style="display:grid;gap:8px;justify-items:start">
            <h2 class="k-base" style="font-weight:600">Tạo gói chủ đề đầu tiên</h2>
            <p class="k-muted" style="margin:0;max-width:55ch">Mỗi gói là một bộ thẻ từ vựng song ngữ mà app của bé tải về. Nhập một chủ đề, AI soạn sẵn thẻ để bạn duyệt.</p>
            <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap">
                <a class="k-btn k-btn-primary" href="/ai-generator"><i class="bi bi-stars"></i>Tạo bằng AI</a>
                <a class="k-btn k-btn-outline" href="/packs/create">Tạo thủ công</a>
            </div>
        </div>
    </section>
<?php else: ?>
    <div class="k-dash-grid" style="display:grid;grid-template-columns:minmax(0,2fr) minmax(0,1fr);gap:16px;align-items:start">
        <section class="k-card">
            <div class="k-card-head"><h2>Gói cần hoàn thiện</h2><a class="k-link-more" href="/packs">Xem tất cả</a></div>
            <div class="k-list">
                <?php foreach ($byCompleteness as $p): ?>
                    <a class="k-list-row" href="/packs/<?= urlencode($p['id']) ?>/edit?tab=items">
                        <?= kw_thumb($p['thumbnail_url'] ?? '') ?>
                        <div class="k-truncate" style="flex:1">
                            <div class="k-row-title k-truncate"><?= kw_e($p['title_vi']) ?></div>
                            <div class="k-xs k-truncate"><?= kw_pack_missing($p) ?></div>
                        </div>
                        <div class="k-hide-sm" style="width:160px"><?= kw_progress(kw_pack_pct($p)) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="k-stack">
            <section class="k-card">
                <div class="k-card-head"><h2>Kết nối app</h2></div>
                <div class="k-card-body" style="display:grid;gap:12px">
                    <?php foreach ($addresses as $i => [$label, $url]): ?>
                        <div class="k-field">
                            <label class="k-label" for="kwAddr<?= $i ?>"><?= kw_e($label) ?></label>
                            <div style="display:flex;gap:8px">
                                <input id="kwAddr<?= $i ?>" class="k-input k-mono" readonly value="<?= kw_e($url) ?>">
                                <button class="k-btn k-btn-outline k-btn-icon" type="button" aria-label="Chép địa chỉ <?= kw_e($label) ?>" onclick="kwCopy('<?= kw_e($url) ?>')"><i class="bi bi-copy"></i></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <p class="k-muted" style="margin:0">Dán địa chỉ vào Cài đặt phụ huynh › Máy chủ nội dung trong app của bé.</p>
                    <div style="display:flex;align-items:center;gap:8px"><span class="k-dot"></span><span>API đang chạy</span><a class="k-link-more" href="/api/packs" target="_blank" rel="noopener">Xem /api/packs</a></div>
                </div>
            </section>

            <section class="k-card">
                <div class="k-card-head"><h2>Sửa gần đây</h2></div>
                <div class="k-list">
                    <?php foreach ($recent as $p): ?>
                        <a class="k-list-row" href="/packs/<?= urlencode($p['id']) ?>/edit">
                            <?= kw_thumb($p['thumbnail_url'] ?? '', 32) ?>
                            <div class="k-truncate" style="flex:1">
                                <div class="k-truncate"><?= kw_e($p['title_vi']) ?></div>
                                <div class="k-xs k-muted">v<?= (int)$p['version'] ?> · <?= kw_date($p['updated_at'] ?? null) ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>
    <style>@media (max-width:1023px){.k-dash-grid{grid-template-columns:minmax(0,1fr)!important}}</style>
<?php endif; ?>
