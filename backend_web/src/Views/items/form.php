<?php
$item = $item ?? null;
$siblings = $siblings ?? [];
$packEditUrl = '/packs/' . urlencode($pack['id']) . '/edit?tab=items';
$pageTitle = $isEdit ? $item['name_vi'] : 'Thêm thẻ';
$crumbs = [['Gói chủ đề', '/packs'], [$pack['title_vi'], $packEditUrl]];

$position = 0;
$prevItem = $nextItem = null;
foreach ($siblings as $i => $sib) {
    if ($isEdit && $sib['id'] === $item['id']) {
        $position = $i + 1;
        $prevItem = $siblings[$i - 1] ?? null;
        $nextItem = $siblings[$i + 1] ?? null;
    }
}

$primaryImg = kw_first_image($item['images_json'] ?? '');
$realImg = $item['real_image_url'] ?? '';
$outlineImg = $item['coloring_outline_url'] ?? '';

/** Một ô ảnh: khung xem trước + nút chọn từ kho / tạo prompt AI. Giá trị nằm ở input ẩn, modal chọn ảnh ghi vào đó. */
$imageSlot = function (string $label, string $name, string $inputId, string $previewId, string $value, string $style, bool $contain = false): string {
    $has = kw_valid_image_url($value);
    $fit = $contain ? 'object-fit:contain;background:#fff' : '';
    return '<div class="k-image-slot" data-slot>'
        . '<div class="k-label">' . $label . '</div>'
        . '<div class="k-image-box ' . ($has ? '' : 'is-empty') . '" data-slot-box>'
        . '<img id="' . $previewId . '" src="' . ($has ? kw_e($value) : '') . '" alt="" style="' . $fit . '" ' . ($has ? '' : 'hidden') . '>'
        . '<span class="k-xs" data-slot-empty ' . ($has ? 'hidden' : '') . '>' . ($value !== '' && !$has ? 'Đường dẫn ảnh lỗi' : 'Chưa có ảnh') . '</span>'
        . '</div>'
        . '<input type="hidden" id="' . $inputId . '" name="' . $name . '" value="' . kw_e($value) . '" data-slot-input>'
        . '<div style="display:flex;gap:6px">'
        . '<button class="k-btn k-btn-outline k-btn-sm" type="button" style="flex:1" data-slot-pick="upload" data-style="' . $style . '">' . ($has ? 'Đổi ảnh' : 'Chọn ảnh') . '</button>'
        . '<button class="k-btn k-btn-outline k-btn-sm k-btn-icon" type="button" data-slot-pick="ai_prompt" data-style="' . $style . '" aria-label="Tạo prompt ảnh bằng AI" title="Tạo prompt ảnh bằng AI"><i class="bi bi-stars"></i></button>'
        . '</div></div>';
};

ob_start(); ?>
<div class="k-savebar">
    <?php if ($isEdit): ?>
        <?php if ($prevItem): ?><a class="k-btn k-btn-ghost k-sm-icon" href="/items/<?= urlencode($prevItem['id']) ?>/edit" aria-label="Thẻ trước"><i class="bi bi-chevron-left"></i><span class="k-btn-label">Thẻ trước</span></a><?php endif; ?>
        <?php if ($nextItem): ?><a class="k-btn k-btn-ghost k-sm-icon" href="/items/<?= urlencode($nextItem['id']) ?>/edit" aria-label="Thẻ sau"><span class="k-btn-label">Thẻ sau</span><i class="bi bi-chevron-right"></i></a><?php endif; ?>
    <?php endif; ?>
    <div style="margin-left:auto;display:flex;gap:8px">
        <a class="k-btn k-btn-outline" href="<?= $packEditUrl ?>">Huỷ</a>
        <button class="k-btn k-btn-primary" type="submit" form="itemForm"><?= $isEdit ? 'Lưu thẻ' : 'Thêm thẻ' ?></button>
    </div>
</div>
<?php $kwAfterMain = ob_get_clean(); ?>

<div class="k-page-head">
    <div class="k-page-head-text">
        <h1><?= $isEdit ? kw_e($item['name_vi']) : 'Thêm thẻ' ?></h1>
        <div class="k-meta">
            <?php if ($isEdit): ?>
                <span><?= kw_e($item['name_en']) ?></span><span class="k-mono"><?= kw_e($item['id']) ?></span><?php if ($position): ?><span>Thẻ <?= $position ?> / <?= count($siblings) ?></span><?php endif; ?>
            <?php else: ?>
                <span>Vào gói <?= kw_e($pack['title_vi']) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($isEdit): ?>
        <div class="k-page-head-actions"><a class="k-btn k-btn-danger" href="/items/<?= urlencode($item['id']) ?>/delete" onclick="return confirm('Xoá thẻ «<?= kw_e(addslashes($item['name_vi'])) ?>»? Không hoàn tác được.');"><i class="bi bi-trash3"></i>Xoá thẻ</a></div>
    <?php endif; ?>
</div>

<form id="itemForm" method="POST" action="<?= $isEdit ? '/items/' . urlencode($item['id']) . '/update' : '/packs/' . urlencode($pack['id']) . '/items/store' ?>" enctype="multipart/form-data">
<div class="k-split">
    <div class="k-stack">
        <section class="k-card">
            <div class="k-card-head"><h2>Tên và mô tả</h2></div>
            <div class="k-card-body" style="display:grid;gap:16px">
                <?php if (!$isEdit): ?>
                    <div class="k-field"><label class="k-label" for="fItemId">Mã thẻ <span class="k-req">*</span></label><input id="fItemId" class="k-input k-mono" type="text" name="id" placeholder="item_lion" required><span class="k-hint">Viết liền không dấu, vd item_lion</span></div>
                <?php endif; ?>
                <div class="k-grid-2">
                    <div class="k-field"><label class="k-label" for="fNameVi">Tên tiếng Việt <span class="k-req">*</span></label><input id="fNameVi" class="k-input" type="text" name="name_vi" value="<?= kw_e($item['name_vi'] ?? '') ?>" placeholder="Sư Tử" required></div>
                    <div class="k-field"><label class="k-label" for="fNameEn">Tên tiếng Anh <span class="k-req">*</span></label><input id="fNameEn" class="k-input" type="text" name="name_en" value="<?= kw_e($item['name_en'] ?? '') ?>" placeholder="Lion" required></div>
                </div>
                <div class="k-grid-2">
                    <div class="k-field"><label class="k-label" for="fDescVi">Mô tả tiếng Việt</label><textarea id="fDescVi" class="k-textarea" name="description_vi" placeholder="Sư tử dũng mãnh là vua của thảo nguyên"><?= kw_e($item['description_vi'] ?? '') ?></textarea></div>
                    <div class="k-field"><label class="k-label" for="fDescEn">Mô tả tiếng Anh</label><textarea id="fDescEn" class="k-textarea" name="description_en" placeholder="The mighty lion is the king of the savanna"><?= kw_e($item['description_en'] ?? '') ?></textarea></div>
                </div>
            </div>
        </section>

        <section class="k-card">
            <div class="k-card-head"><h2>Phát âm và âm thanh</h2></div>
            <div class="k-card-body" style="display:grid;gap:16px">
                <div class="k-grid-2">
                    <div class="k-field"><label class="k-label" for="fPhonics">Phiên âm tiếng Anh</label><input id="fPhonics" class="k-input" type="text" name="phonics_en" value="<?= kw_e($item['phonics_en'] ?? '') ?>" placeholder="/ˈlaɪən/"></div>
                    <div class="k-field"><label class="k-label" for="fSfx">Tiếng kêu</label><input id="fSfx" class="k-input" type="text" name="sfx_sound" value="<?= kw_e($item['sfx_sound'] ?? '') ?>" placeholder="Gừ gừ... Roar! hoặc URL tệp âm thanh"></div>
                </div>
                <div class="k-grid-2">
                    <div class="k-field"><label class="k-label" for="fAudioVi">Phát âm tiếng Việt</label>
                        <input id="fAudioVi" class="k-input" type="text" name="pronounce_vi_url" value="<?= kw_e($item['pronounce_vi_url'] ?? '') ?>" placeholder="URL tệp .mp3, để trống thì đọc tự động">
                        <input class="k-file" type="file" name="audio_vi_file" accept="audio/*" aria-label="Tải tệp phát âm tiếng Việt từ máy"></div>
                    <div class="k-field"><label class="k-label" for="fAudioEn">Phát âm tiếng Anh</label>
                        <input id="fAudioEn" class="k-input" type="text" name="pronounce_en_url" value="<?= kw_e($item['pronounce_en_url'] ?? '') ?>" placeholder="URL tệp .mp3, để trống thì đọc tự động">
                        <input class="k-file" type="file" name="audio_en_file" accept="audio/*" aria-label="Tải tệp phát âm tiếng Anh từ máy"></div>
                </div>
            </div>
        </section>

        <section class="k-card">
            <div class="k-card-head"><h2>Cùng bé tương tác</h2></div>
            <div class="k-card-body" style="display:grid;gap:16px">
                <div class="k-field"><label class="k-label" for="fFact">Sự thật thú vị</label><textarea id="fFact" class="k-textarea" name="fun_fact_vi" placeholder="Sư tử đực có bờm xù rất oai phong"><?= kw_e($item['fun_fact_vi'] ?? '') ?></textarea></div>
                <div class="k-field"><label class="k-label" for="fQuestion">Câu hỏi cha mẹ đố bé</label><input id="fQuestion" class="k-input" type="text" name="prompt_question_vi" value="<?= kw_e($item['prompt_question_vi'] ?? '') ?>" placeholder="Đố con biết sư tử ngủ bao nhiêu tiếng một ngày?"></div>
                <div class="k-field"><label class="k-label" for="fAction">Gợi ý vận động</label><input id="fAction" class="k-input" type="text" name="action_hint_vi" value="<?= kw_e($item['action_hint_vi'] ?? '') ?>" placeholder="Cùng xoè móng vuốt và gầm vang nhà nào!"></div>
            </div>
        </section>

        <section class="k-card">
            <div class="k-card-head"><h2>Video và vị trí</h2></div>
            <div class="k-card-body" style="display:grid;gap:16px">
                <div class="k-field"><label class="k-label" for="fYoutube">Video YouTube</label><input id="fYoutube" class="k-input" type="text" name="youtube_video_id" value="<?= kw_e($item['youtube_video_id'] ?? '') ?>" placeholder="Link YouTube hoặc mã video 11 ký tự"></div>
                <div class="k-grid-3">
                    <div class="k-field"><label class="k-label" for="fMapX">Toạ độ X</label><input id="fMapX" class="k-input k-num" type="number" step="10" name="map_x" value="<?= kw_e($item['map_x'] ?? 100) ?>"></div>
                    <div class="k-field"><label class="k-label" for="fMapY">Toạ độ Y</label><input id="fMapY" class="k-input k-num" type="number" step="10" name="map_y" value="<?= kw_e($item['map_y'] ?? 100) ?>"></div>
                    <div class="k-field"><label class="k-label" for="fSort">Thứ tự</label><input id="fSort" class="k-input k-num" type="number" name="sort_order" value="<?= kw_e($item['sort_order'] ?? 0) ?>"></div>
                </div>
            </div>
        </section>
    </div>

    <div class="k-sticky">
        <section class="k-card">
            <div class="k-card-head"><h2>Hình ảnh</h2></div>
            <div class="k-card-body k-image-trio" style="display:grid;gap:16px">
                <?= $imageSlot('Ảnh 3D minh hoạ', 'image_url', 'itemFormImgInput', 'itemFormImgPreview', $primaryImg, 'pixar_3d') ?>
                <?= $imageSlot('Ảnh thật', 'real_image_url', 'itemFormRealImgInput', 'itemFormRealImgPreview', $realImg, 'real_photo') ?>
                <?= $imageSlot('Tranh tô màu', 'coloring_outline_url', 'itemFormOutlineInput', 'itemFormOutlinePreview', $outlineImg, 'coloring_outline', true) ?>
            </div>
        </section>
    </div>
</div>
</form>

<script>
    // Ô ảnh: modal chọn ảnh ghi URL vào input ẩn rồi phát sự kiện change, ô tự vẽ lại theo giá trị mới.
    document.querySelectorAll('[data-slot]').forEach(slot => {
        const input = slot.querySelector('[data-slot-input]');
        const img = slot.querySelector('img');
        const empty = slot.querySelector('[data-slot-empty]');
        const box = slot.querySelector('[data-slot-box]');
        const pickButton = slot.querySelector('[data-slot-pick="upload"]');

        function render() {
            const url = input.value.trim();
            const valid = /^https?:\/\//i.test(url) || url.startsWith('/') || url.startsWith('data:');
            img.hidden = !valid;
            if (valid) img.src = url;
            empty.hidden = valid;
            empty.textContent = url && !valid ? 'Đường dẫn ảnh lỗi' : 'Chưa có ảnh';
            box.classList.toggle('is-empty', !valid);
            pickButton.textContent = valid ? 'Đổi ảnh' : 'Chọn ảnh';
        }

        input.addEventListener('change', render);
        slot.querySelectorAll('[data-slot-pick]').forEach(button => button.addEventListener('click', () => {
            // Prompt ảnh tiếng Anh: lấy đúng tên tiếng Anh của thẻ này
            const query = (document.getElementById('fNameEn')?.value || '').trim()
                       || (document.getElementById('fNameVi')?.value || '').trim();
            openImageBrowser({
                inputId: input.id,
                previewId: img.id,
                query: query,
                defaultTab: button.dataset.slotPick,
                preferredStyle: button.dataset.style,
                callback: render
            });
        }));
    });
</script>
