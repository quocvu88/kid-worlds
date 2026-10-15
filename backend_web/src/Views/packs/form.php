<?php
$items = $items ?? [];
$pageTitle = $isEdit ? $pack['title_vi'] : 'Tạo gói chủ đề';
$crumbs = [['Gói chủ đề', '/packs']];

$requestedTab = $_GET['tab'] ?? '';
$activeTab = in_array($requestedTab, ['general', 'items', 'games'], true) ? $requestedTab : ($isEdit ? 'items' : 'general');
if (!$isEdit && $activeTab === 'items') $activeTab = 'general';

$currentGames = json_decode($pack['selected_games_json'] ?? '["coloring","memory_match"]', true);
if (!is_array($currentGames) || empty($currentGames)) {
    $currentGames = ['coloring', 'memory_match'];
}
$gameConfig = json_decode($pack['game_config_json'] ?? '{}', true) ?: [];
$coloringPalette = $gameConfig['coloring_palette'] ?? 'candy';
$coloringExport = isset($gameConfig['coloring_export']) ? (int)$gameConfig['coloring_export'] : 1;
$memoryMatrix = $gameConfig['memory_matrix'] ?? '4x6';
$memoryTts = isset($gameConfig['memory_tts']) ? (int)$gameConfig['memory_tts'] : 1;
$memoryTimer = $gameConfig['memory_timer'] ?? 'unlimited';
$shadowCount = $gameConfig['shadow_count'] ?? 4;
$bubbleSpeed = $gameConfig['bubble_speed'] ?? 'normal';
$bubbleVoice = $gameConfig['bubble_voice'] ?? 'bilingual';
$jigsawPieces = $gameConfig['jigsaw_pieces'] ?? 4;
$jigsawGhost = isset($gameConfig['jigsaw_ghost']) ? (int)$gameConfig['jigsaw_ghost'] : 1;
$soundSource = $gameConfig['sound_source'] ?? 'sfx';

$missingLine = count(array_filter($items, fn($it) => trim((string)($it['coloring_outline_url'] ?? '')) === ''));
$games = [
    'coloring' => ['bi-palette', 'Tô màu sáng tạo', $items ? 'Bé tô tranh nét của từng thẻ. ' . (count($items) - $missingLine) . '/' . count($items) . ' thẻ có tranh tô màu' : 'Bé tô tranh nét của từng thẻ', $items && $missingLine > 0],
    'memory_match' => ['bi-grid-3x3-gap', 'Lật hình nhớ cặp', 'Lật thẻ theo ma trận, đọc từ vựng khi ghép đúng', false],
    'shadow_match' => ['bi-circle-half', 'Ghép bóng nhận diện', 'Kéo hình màu khớp với bóng đen', false],
    'bubble_pop' => ['bi-balloon', 'Đập bóng từ vựng', 'Nghe phát âm và chạm bong bóng có hình đúng', false],
    'jigsaw' => ['bi-puzzle', 'Xếp hình', 'Ghép tranh 4, 9 hoặc 16 mảnh', false],
    'sound_linker' => ['bi-volume-up', 'Nối âm thanh và hình', 'Nghe tiếng kêu, chạm đúng hình', false],
];

$cats = [
    'simple' => 'Simple (gói thẻ học tiêu chuẩn)',
    'map_2d' => 'Map 2D (bản đồ khám phá, đang phát triển)',
    'journey' => 'Chuỗi hành trình (đang phát triển)',
    'animals' => 'Động vật (animals)',
    'vehicles' => 'Xe cộ (vehicles)',
    'fruits' => 'Trái cây (fruits)',
    'space' => 'Vũ trụ (space)',
    'general' => 'Tổng hợp (general)',
];
$currentCat = $pack['category'] ?? 'simple';
$thumbUrl = $pack['thumbnail_url'] ?? '';

$saveLabel = $isEdit ? 'Lưu thay đổi' : 'Tạo gói';
if ($isEdit) {
    $packHeaderActions = '<button class="k-btn k-btn-outline" type="button" data-bs-toggle="modal" data-bs-target="#aiRewriteModal"><i class="bi bi-magic"></i>Viết lại bằng AI</button>'
                       . '<div class="dropdown"><button class="k-btn k-btn-outline k-btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Thêm thao tác"><i class="bi bi-three-dots"></i></button>'
                       . '<ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="/api/packs/' . urlencode($pack['id']) . '/download" target="_blank" rel="noopener"><i class="bi bi-download"></i>Xuất JSON</a></li></ul></div>'
                       . '<button class="k-btn k-btn-primary" type="submit" form="packForm">' . $saveLabel . '</button>';
    require __DIR__ . '/_header.php';
} else { ?>
    <div class="k-page-head">
        <div class="k-page-head-text"><h1>Tạo gói chủ đề</h1><p class="k-muted" style="margin:2px 0 0;max-width:55ch">Điền thông tin gói, lưu xong mới thêm được thẻ. Muốn AI soạn sẵn cả gói và thẻ thì <a href="/ai-generator" style="color:var(--foreground);font-weight:500;text-decoration:underline">tạo bằng AI</a>.</p></div>
        <div class="k-page-head-actions"><a class="k-btn k-btn-outline" href="/packs">Huỷ</a><button class="k-btn k-btn-primary" type="submit" form="packForm"><?= $saveLabel ?></button></div>
    </div>
<?php } ?>

<div class="k-tabs" role="tablist">
    <button class="k-tab" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#tab-general" aria-selected="<?= $activeTab === 'general' ? 'true' : 'false' ?>">Thông tin chung</button>
    <?php if ($isEdit): ?>
        <button class="k-tab" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#tab-items" aria-selected="<?= $activeTab === 'items' ? 'true' : 'false' ?>">Thẻ từ vựng<span class="k-count"><?= count($items) ?></span></button>
    <?php endif; ?>
    <button class="k-tab" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#tab-games" aria-selected="<?= $activeTab === 'games' ? 'true' : 'false' ?>">Mini-game<span class="k-count" id="selectedGameCountTab"><?= count($currentGames) ?></span></button>
</div>

<form id="packForm" method="POST" action="<?= $isEdit ? '/packs/' . urlencode($pack['id']) . '/update' : '/packs/store' ?>" enctype="multipart/form-data">
<div class="tab-content">

    <!-- Thông tin chung -->
    <div class="tab-pane fade <?= $activeTab === 'general' ? 'show active' : '' ?>" id="tab-general" role="tabpanel">
        <section class="k-card"><div class="k-card-body" style="display:grid;gap:16px;max-width:880px">
            <div class="k-grid-2">
                <div class="k-field"><label class="k-label" for="fTitleVi">Tên tiếng Việt <span class="k-req">*</span></label><input id="fTitleVi" class="k-input" type="text" name="title_vi" value="<?= kw_e($pack['title_vi'] ?? '') ?>" placeholder="Thế Giới Khủng Long Tiền Sử" required></div>
                <div class="k-field"><label class="k-label" for="fTitleEn">Tên tiếng Anh <span class="k-req">*</span></label><input id="fTitleEn" class="k-input" type="text" name="title_en" value="<?= kw_e($pack['title_en'] ?? '') ?>" placeholder="Prehistoric Dinosaurs" required></div>
            </div>
            <div class="k-grid-2">
                <?php if (!$isEdit): ?>
                    <div class="k-field"><label class="k-label" for="fId">Mã gói <span class="k-req">*</span></label><input id="fId" class="k-input k-mono" type="text" name="id" placeholder="topic_animals" required><span class="k-hint">Viết liền không dấu, vd topic_animals. Không đổi được sau khi tạo.</span></div>
                <?php else: ?>
                    <div class="k-field"><label class="k-label" for="fIdRo">Mã gói</label><input id="fIdRo" class="k-input k-mono" type="text" value="<?= kw_e($pack['id']) ?>" readonly><span class="k-hint">Không đổi được sau khi tạo</span></div>
                <?php endif; ?>
                <div class="k-field"><label class="k-label" for="fCat">Danh mục <span class="k-req">*</span></label>
                    <select id="fCat" name="category" class="k-select" required>
                        <?php foreach ($cats as $key => $name): ?><option value="<?= $key ?>" <?= $currentCat === $key ? 'selected' : '' ?>><?= $name ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="k-grid-2">
                <div class="k-field"><span class="k-label">Độ tuổi</span>
                    <div style="display:flex;gap:8px;align-items:center"><input class="k-input k-num" type="number" name="target_age_min" min="1" max="15" value="<?= (int)($pack['target_age_min'] ?? 3) ?>" aria-label="Tuổi từ"><span class="k-muted">đến</span><input class="k-input k-num" type="number" name="target_age_max" min="1" max="15" value="<?= (int)($pack['target_age_max'] ?? 10) ?>" aria-label="Tuổi đến"></div>
                </div>
                <div class="k-field"><label class="k-label" for="fSize">Dung lượng ước tính (MB)</label><input id="fSize" class="k-input k-num" type="number" step="0.1" name="size_mb" value="<?= kw_e($pack['size_mb'] ?? 1.5) ?>"></div>
            </div>
            <div class="k-field"><label class="k-label" for="packFormThumbInput">Ảnh đại diện</label>
                <div style="display:flex;gap:8px;align-items:center">
                    <span id="packFormThumbPreviewWrap" style="display:contents"><?php if (kw_valid_image_url($thumbUrl)): ?><img id="packFormThumbPreview" class="k-thumb" style="width:40px;height:40px" src="<?= kw_e($thumbUrl) ?>" alt=""><?php else: ?><img id="packFormThumbPreview" class="k-thumb" style="width:40px;height:40px" src="" alt="" hidden><?php endif; ?></span>
                    <input type="text" id="packFormThumbInput" name="thumbnail_url" class="k-input" value="<?= kw_e($thumbUrl) ?>" placeholder="Dán URL ảnh hoặc chọn từ kho">
                    <button type="button" class="k-btn k-btn-outline" onclick="browsePackFormThumb()" style="flex-shrink:0"><i class="bi bi-image"></i>Chọn ảnh</button>
                </div>
                <?php if ($thumbUrl !== '' && !kw_valid_image_url($thumbUrl)): ?><span class="k-hint" style="color:var(--error-text)">Đường dẫn ảnh không hợp lệ, cần bắt đầu bằng https:// hoặc /uploads/.</span><?php endif; ?>
            </div>
            <div class="k-grid-2">
                <div class="k-field"><label class="k-label" for="fBg">Hình nền</label><input id="fBg" class="k-input" type="text" name="background_url" value="<?= kw_e($pack['background_url'] ?? '') ?>" placeholder="assets/backgrounds/bg_green_meadow.jpg hoặc URL"></div>
                <div class="k-field"><label class="k-label" for="themeColorText">Màu chủ đạo</label>
                    <div style="display:flex;gap:8px"><input type="color" class="form-control form-control-color" value="<?= kw_e($pack['theme_color'] ?? '#FF6584') ?>" onchange="document.getElementById('themeColorText').value = this.value" aria-label="Chọn màu"><input type="text" id="themeColorText" name="theme_color" class="k-input k-mono" value="<?= kw_e($pack['theme_color'] ?? '#FF6584') ?>" placeholder="#FF6584"></div>
                </div>
            </div>
            <div class="k-field"><label class="k-label" for="fDescVi">Mô tả tiếng Việt</label><textarea id="fDescVi" name="description_vi" class="k-textarea" placeholder="Giới thiệu nội dung của gói"><?= kw_e($pack['description_vi'] ?? '') ?></textarea></div>
            <div class="k-field"><label class="k-label" for="fDescEn">Mô tả tiếng Anh</label><textarea id="fDescEn" name="description_en" class="k-textarea" placeholder="English description"><?= kw_e($pack['description_en'] ?? '') ?></textarea></div>
            <label class="k-game-row" for="isActiveSwitch" style="padding:0;border:0">
                <div style="flex:1"><div class="k-row-title">Hiện gói trong app</div><div class="k-xs k-muted">Tắt thì app không thấy và không tải được gói này</div></div>
                <span class="form-check form-switch k-switch"><input class="form-check-input" type="checkbox" role="switch" name="is_active" id="isActiveSwitch" value="1" <?= (!isset($pack['is_active']) || $pack['is_active'] == 1) ? 'checked' : '' ?>></span>
            </label>
        </div></section>
    </div>

    <!-- Thẻ từ vựng -->
    <?php if ($isEdit): ?>
        <div class="tab-pane fade <?= $activeTab === 'items' ? 'show active' : '' ?>" id="tab-items" role="tabpanel">
            <div class="k-stack"><?php require __DIR__ . '/_items_table.php'; ?></div>
        </div>
    <?php endif; ?>

    <!-- Mini-game -->
    <div class="tab-pane fade <?= $activeTab === 'games' ? 'show active' : '' ?>" id="tab-games" role="tabpanel">
        <div class="k-stack">
            <section class="k-card">
                <div class="k-card-head"><h2>Game kèm theo gói</h2><span id="gameSelectionBadge" class="k-badge k-badge-primary" style="margin-left:auto">Đã chọn <span id="selectedGameCount"><?= count($currentGames) ?></span>/3</span></div>
                <div class="k-list">
                    <?php foreach ($games as $key => [$icon, $name, $desc, $warn]): ?>
                        <label class="k-game-row game-select-card" for="game_<?= $key ?>">
                            <span class="k-stat-icon"><i class="bi <?= $icon ?>"></i></span>
                            <div style="flex:1;min-width:0"><div class="k-row-title"><?= $name ?></div><div class="k-xs" style="color:<?= $warn ? 'var(--warning)' : 'var(--muted)' ?>"><?= kw_e($desc) ?></div></div>
                            <span class="form-check form-switch k-switch"><input class="form-check-input game-checkbox" type="checkbox" role="switch" id="game_<?= $key ?>" name="selected_games[]" value="<?= $key ?>" <?= in_array($key, $currentGames) ? 'checked' : '' ?>></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>
            <p id="gameMaxWarning" class="k-muted <?= count($currentGames) >= 3 ? '' : 'd-none' ?>" style="margin:0"><i class="bi bi-info-circle"></i> Mỗi gói tối đa 3 game. Tắt bớt một game để chọn game khác.</p>

            <section id="game-config-coloring" class="k-card">
                <div class="k-card-head"><h2>Tô màu sáng tạo</h2></div>
                <div class="k-card-body" style="display:grid;gap:16px">
                    <div class="k-grid-2">
                        <div class="k-field"><label class="k-label" for="gcPalette">Bảng màu cọ vẽ</label>
                            <select id="gcPalette" name="game_config[coloring_palette]" class="k-select">
                                <option value="candy" <?= $coloringPalette === 'candy' ? 'selected' : '' ?>>Kẹo ngọt (pastel dịu mắt)</option>
                                <option value="rainbow" <?= $coloringPalette === 'rainbow' ? 'selected' : '' ?>>Cầu vồng rực rỡ</option>
                                <option value="nature" <?= $coloringPalette === 'nature' ? 'selected' : '' ?>>Thảo nguyên tự nhiên</option>
                            </select></div>
                        <div class="k-field"><label class="k-label" for="gcExport">Lưu tranh vào góc sáng tạo của bé</label>
                            <select id="gcExport" name="game_config[coloring_export]" class="k-select">
                                <option value="1" <?= $coloringExport == 1 ? 'selected' : '' ?>>Bật, có pháo giấy chúc mừng</option>
                                <option value="0" <?= $coloringExport == 0 ? 'selected' : '' ?>>Tắt</option>
                            </select></div>
                    </div>
                    <div class="k-field">
                        <span class="k-label">Tranh tô màu theo thẻ</span>
                        <span class="k-hint">Thẻ chưa có tranh nét thì game dùng ảnh mờ làm gợi ý.</span>
                    </div>
                    <?php if (empty($items)): ?>
                        <p class="k-empty" style="margin:0;padding:12px 0;text-align:left">Gói chưa có thẻ nào. Lưu gói, thêm thẻ rồi quay lại đây gán tranh nét.</p>
                    <?php else: ?>
                        <div class="k-list" style="border:1px solid var(--border);border-radius:12px">
                            <?php foreach ($items as $it):
                                $outline = $it['coloring_outline_url'] ?? '';
                                $cleanItemId = preg_replace('/[^a-zA-Z0-9_]/', '_', $it['id']); ?>
                                <div class="k-list-row" style="flex-wrap:wrap">
                                    <div class="k-cell-main" style="width:200px;flex-shrink:0">
                                        <?= kw_thumb(kw_first_image($it['images_json'] ?? ''), 36) ?>
                                        <div class="k-truncate"><div class="k-truncate" style="font-weight:500"><?= kw_e($it['name_vi']) ?></div><div class="k-xs k-muted k-truncate"><?= kw_e($it['name_en']) ?></div></div>
                                    </div>
                                    <div style="flex:1 1 220px;min-width:0;display:grid;grid-template-columns:minmax(0,1fr);gap:6px">
                                        <div style="display:flex;gap:8px">
                                            <input type="text" id="outline_input_<?= $cleanItemId ?>" name="coloring_outline[<?= kw_e($it['id']) ?>]" class="k-input k-mono" value="<?= kw_e($outline) ?>" placeholder="URL tranh nét hoặc để trống" aria-label="Tranh nét của <?= kw_e($it['name_vi']) ?>">
                                            <button type="button" class="k-btn k-btn-outline" style="flex-shrink:0" onclick="browseOutlineImage('<?= $cleanItemId ?>', '<?= kw_e(addslashes($it['name_en'])) ?>')"><i class="bi bi-image"></i>Chọn</button>
                                        </div>
                                        <input type="file" name="coloring_outline_file_<?= $cleanItemId ?>" class="k-file" accept="image/*" aria-label="Tải tranh nét từ máy" onchange="previewOutlineLocal(this, 'outline_preview_<?= $cleanItemId ?>', 'outline_preview_wrap_<?= $cleanItemId ?>')">
                                    </div>
                                    <div style="width:56px;flex-shrink:0;text-align:center">
                                        <div id="outline_preview_wrap_<?= $cleanItemId ?>" class="<?= empty($outline) ? 'd-none' : '' ?>"><img id="outline_preview_<?= $cleanItemId ?>" src="<?= kw_e($outline) ?>" class="k-thumb" style="object-fit:contain;background:#fff;border:1px solid var(--border)" alt="Tranh nét"></div>
                                        <span id="outline_placeholder_<?= $cleanItemId ?>" class="k-xs k-muted <?= !empty($outline) ? 'd-none' : '' ?>">Ảnh gốc</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section id="game-config-memory_match" class="k-card">
                <div class="k-card-head"><h2>Lật hình nhớ cặp</h2></div>
                <div class="k-card-body k-grid-3">
                    <div class="k-field"><label class="k-label" for="gcMatrix">Ma trận mặc định</label>
                        <select id="gcMatrix" name="game_config[memory_matrix]" class="k-select">
                            <option value="3x4" <?= $memoryMatrix === '3x4' ? 'selected' : '' ?>>3×4, 6 cặp (3–4 tuổi)</option>
                            <option value="4x6" <?= $memoryMatrix === '4x6' ? 'selected' : '' ?>>4×6, 12 cặp (5–7 tuổi)</option>
                            <option value="6x8" <?= $memoryMatrix === '6x8' ? 'selected' : '' ?>>6×8, 24 cặp (8+ tuổi)</option>
                        </select></div>
                    <div class="k-field"><label class="k-label" for="gcTts">Phát âm khi ghép đúng</label>
                        <select id="gcTts" name="game_config[memory_tts]" class="k-select">
                            <option value="1" <?= $memoryTts == 1 ? 'selected' : '' ?>>Đọc tên tiếng Anh và tiếng Việt</option>
                            <option value="0" <?= $memoryTts == 0 ? 'selected' : '' ?>>Chỉ phát tiếng ting</option>
                        </select></div>
                    <div class="k-field"><label class="k-label" for="gcTimer">Đếm giờ</label>
                        <select id="gcTimer" name="game_config[memory_timer]" class="k-select">
                            <option value="unlimited" <?= $memoryTimer === 'unlimited' ? 'selected' : '' ?>>Bấm giờ tăng dần, không giới hạn</option>
                            <option value="60s" <?= $memoryTimer === '60s' ? 'selected' : '' ?>>Đếm ngược 60 giây</option>
                            <option value="90s" <?= $memoryTimer === '90s' ? 'selected' : '' ?>>Đếm ngược 90 giây</option>
                        </select></div>
                </div>
            </section>

            <section id="game-config-shadow_match" class="k-card">
                <div class="k-card-head"><h2>Ghép bóng nhận diện</h2></div>
                <div class="k-card-body k-grid-2">
                    <div class="k-field"><label class="k-label" for="gcShadowCount">Số cặp mỗi lượt</label>
                        <select id="gcShadowCount" name="game_config[shadow_count]" class="k-select">
                            <option value="3" <?= $shadowCount == 3 ? 'selected' : '' ?>>3 cặp (dễ)</option>
                            <option value="4" <?= $shadowCount == 4 ? 'selected' : '' ?>>4 cặp (tiêu chuẩn)</option>
                            <option value="5" <?= $shadowCount == 5 ? 'selected' : '' ?>>5 cặp (nâng cao)</option>
                        </select></div>
                    <div class="k-field"><label class="k-label" for="gcShadowGen">Cách tạo bóng</label>
                        <select id="gcShadowGen" name="game_config[shadow_generator]" class="k-select">
                            <option value="auto">Tự tách bóng từ ảnh thẻ</option>
                            <option value="outline">Dùng đường viền tranh nét nếu có</option>
                        </select></div>
                </div>
            </section>

            <section id="game-config-bubble_pop" class="k-card">
                <div class="k-card-head"><h2>Đập bóng từ vựng</h2></div>
                <div class="k-card-body k-grid-2">
                    <div class="k-field"><label class="k-label" for="gcBubbleSpeed">Tốc độ bóng</label>
                        <select id="gcBubbleSpeed" name="game_config[bubble_speed]" class="k-select">
                            <option value="slow" <?= $bubbleSpeed === 'slow' ? 'selected' : '' ?>>Chậm</option>
                            <option value="normal" <?= $bubbleSpeed === 'normal' ? 'selected' : '' ?>>Bình thường</option>
                            <option value="fast" <?= $bubbleSpeed === 'fast' ? 'selected' : '' ?>>Nhanh</option>
                        </select></div>
                    <div class="k-field"><label class="k-label" for="gcBubbleVoice">Giọng đọc đố bé</label>
                        <select id="gcBubbleVoice" name="game_config[bubble_voice]" class="k-select">
                            <option value="bilingual" <?= $bubbleVoice === 'bilingual' ? 'selected' : '' ?>>Song ngữ Anh–Việt</option>
                            <option value="en_only" <?= $bubbleVoice === 'en_only' ? 'selected' : '' ?>>Chỉ tiếng Anh</option>
                            <option value="sfx_sound" <?= $bubbleVoice === 'sfx_sound' ? 'selected' : '' ?>>Tiếng kêu thực tế</option>
                        </select></div>
                </div>
            </section>

            <section id="game-config-jigsaw" class="k-card">
                <div class="k-card-head"><h2>Xếp hình</h2></div>
                <div class="k-card-body k-grid-2">
                    <div class="k-field"><label class="k-label" for="gcJigsaw">Số mảnh mặc định</label>
                        <select id="gcJigsaw" name="game_config[jigsaw_pieces]" class="k-select">
                            <option value="4" <?= $jigsawPieces == 4 ? 'selected' : '' ?>>4 mảnh (2×2)</option>
                            <option value="9" <?= $jigsawPieces == 9 ? 'selected' : '' ?>>9 mảnh (3×3)</option>
                            <option value="16" <?= $jigsawPieces == 16 ? 'selected' : '' ?>>16 mảnh (4×4)</option>
                        </select></div>
                    <div class="k-field"><label class="k-label" for="gcGhost">Hình mờ gợi ý</label>
                        <select id="gcGhost" name="game_config[jigsaw_ghost]" class="k-select">
                            <option value="1" <?= $jigsawGhost == 1 ? 'selected' : '' ?>>Bật, hình mờ 20% bên dưới</option>
                            <option value="0" <?= $jigsawGhost == 0 ? 'selected' : '' ?>>Tắt</option>
                        </select></div>
                </div>
            </section>

            <section id="game-config-sound_linker" class="k-card">
                <div class="k-card-head"><h2>Nối âm thanh và hình</h2></div>
                <div class="k-card-body k-grid-2">
                    <div class="k-field"><label class="k-label" for="gcSoundSrc">Nguồn âm thanh</label>
                        <select id="gcSoundSrc" name="game_config[sound_source]" class="k-select">
                            <option value="sfx" <?= $soundSource === 'sfx' ? 'selected' : '' ?>>Tiếng kêu thực tế</option>
                            <option value="phonics" <?= $soundSource === 'phonics' ? 'selected' : '' ?>>Ngữ âm phonics</option>
                        </select></div>
                    <div class="k-field"><label class="k-label" for="gcSoundChoices">Số hình lựa chọn mỗi câu</label>
                        <select id="gcSoundChoices" name="game_config[sound_choices]" class="k-select">
                            <option value="3">3 hình</option>
                            <option value="4" selected>4 hình</option>
                        </select></div>
                </div>
            </section>
        </div>
    </div>

</div>
</form>
<?php if ($isEdit) require __DIR__ . '/_ai_rewrite.php'; ?>

<script>
    function browsePackFormThumb() {
        const q = (document.querySelector('input[name="title_en"]')?.value || '').trim()
               || (document.querySelector('input[name="title_vi"]')?.value || '').trim();
        openImageBrowser({
            inputId: 'packFormThumbInput',
            previewId: 'packFormThumbPreview',
            query: q,
            callback: function () {
                document.getElementById('packFormThumbPreview').hidden = false;
            }
        });
    }

    // Chọn hoặc tải tranh nét cho từng thẻ trong game Tô màu
    function browseOutlineImage(cleanItemId, nameEn) {
        openImageBrowser({
            inputId: 'outline_input_' + cleanItemId,
            previewId: 'outline_preview_' + cleanItemId,
            query: nameEn || '',
            defaultTab: 'ai_prompt',
            preferredStyle: 'coloring_outline',
            callback: function () {
                const wrap = document.getElementById('outline_preview_wrap_' + cleanItemId);
                const placeholder = document.getElementById('outline_placeholder_' + cleanItemId);
                if (wrap) wrap.classList.remove('d-none');
                if (placeholder) placeholder.classList.add('d-none');
            }
        });
    }

    function previewOutlineLocal(input, imgId, wrapId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const img = document.getElementById(imgId);
                const wrap = document.getElementById(wrapId);
                const placeholder = document.getElementById(imgId.replace('outline_preview_', 'outline_placeholder_'));
                if (img) img.src = e.target.result;
                if (wrap) wrap.classList.remove('d-none');
                if (placeholder) placeholder.classList.add('d-none');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Tab gạch dưới: đổi aria-selected theo tab Bootstrap đang mở
    document.querySelectorAll('.k-tabs [data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', () => {
            tab.closest('.k-tabs').querySelectorAll('.k-tab').forEach(t => t.setAttribute('aria-selected', String(t === tab)));
        });
    });

    // Tối đa 3 game mỗi gói; game nào bật thì hiện phần cấu hình của game đó
    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = document.querySelectorAll('.game-checkbox');
        const MAX_GAMES = 3;
        const allGameKeys = ['coloring', 'memory_match', 'shadow_match', 'bubble_pop', 'jigsaw', 'sound_linker'];

        function updateGameLimitAndConfigVisibility() {
            const checkedBoxes = document.querySelectorAll('.game-checkbox:checked');
            const count = checkedBoxes.length;
            document.getElementById('selectedGameCount').textContent = count;
            document.getElementById('selectedGameCountTab').textContent = count;

            const atLimit = count >= MAX_GAMES;
            checkboxes.forEach(cb => {
                cb.disabled = atLimit && !cb.checked;
                cb.closest('.game-select-card')?.classList.toggle('is-disabled', cb.disabled);
            });
            document.getElementById('gameMaxWarning').classList.toggle('d-none', !atLimit);

            allGameKeys.forEach(gameKey => {
                const section = document.getElementById('game-config-' + gameKey);
                if (section) section.style.display = Array.from(checkedBoxes).some(cb => cb.value === gameKey) ? '' : 'none';
            });
        }

        checkboxes.forEach(cb => cb.addEventListener('change', updateGameLimitAndConfigVisibility));
        updateGameLimitAndConfigVisibility();
    });
</script>
