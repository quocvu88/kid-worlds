<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card card-custom p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div class="d-flex align-items-center gap-2.5">
                    <span class="d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px; background: var(--kw-primary-light); border: 1px solid var(--kw-primary-border); border-radius: var(--kw-radius-sm); color: var(--kw-primary);">
                        <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-plus-circle' ?> fs-5"></i>
                    </span>
                    <div>
                        <h5 class="fw-semibold mb-0 text-dark"><?= htmlspecialchars($title) ?></h5>
                        <small class="text-muted">Thuộc gói: <strong class="text-dark"><?= htmlspecialchars($pack['title_vi']) ?></strong> (<code><?= htmlspecialchars($pack['id']) ?></code>)</small>
                    </div>
                </div>
                <a href="/packs/<?= urlencode($pack['id']) ?>/items" class="btn btn-kw-outline btn-sm">
                    <i class="bi bi-arrow-left"></i> Quay Lại
                </a>
            </div>

            <form method="POST" action="<?= $isEdit ? '/items/' . urlencode($item['id']) . '/update' : '/packs/' . urlencode($pack['id']) . '/items/store' ?>" enctype="multipart/form-data">
                <div class="row g-3">
                    <?php if (!$isEdit): ?>
                        <div class="col-md-4">
                            <label class="form-label">Mã Thẻ (ID) <span class="text-danger">*</span></label>
                            <input type="text" name="id" class="form-control" placeholder="item_lion, item_apple..." required>
                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Định danh duy nhất (vd: item_lion)</small>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-<?= $isEdit ? '6' : '4' ?>">
                        <label class="form-label">Tên Tiếng Việt <span class="text-danger">*</span></label>
                        <input type="text" name="name_vi" class="form-control" value="<?= htmlspecialchars($item['name_vi'] ?? '') ?>" placeholder="Sư Tử, Con Voi..." required>
                    </div>

                    <div class="col-md-<?= $isEdit ? '6' : '4' ?>">
                        <label class="form-label">Tên Tiếng Anh <span class="text-danger">*</span></label>
                        <input type="text" name="name_en" class="form-control" value="<?= htmlspecialchars($item['name_en'] ?? '') ?>" placeholder="Lion, Elephant..." required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Mô Tả Tiếng Việt</label>
                        <textarea name="description_vi" class="form-control" rows="2" placeholder="Sư tử dũng mãnh là vua của thảo nguyên xanh..."><?= htmlspecialchars($item['description_vi'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Mô Tả Tiếng Anh</label>
                        <textarea name="description_en" class="form-control" rows="2" placeholder="The mighty lion is known as the king of the jungle..."><?= htmlspecialchars($item['description_en'] ?? '') ?></textarea>
                    </div>

                    <!-- Images Section -->
                    <div class="col-12 p-3 bg-light rounded-3 border" style="border-color: var(--kw-border) !important;">
                        <label class="form-label fw-semibold text-dark mb-1">
                            <i class="bi bi-card-image text-primary me-1"></i> Hình Ảnh Minh Họa Thẻ (Flashcard 3D / Hoạt Họa)
                        </label>
                        <div class="row g-2 align-items-center">
                            <div class="col-md-5">
                                <label class="small text-muted mb-1" style="font-size: 0.75rem;">Tải trực tiếp từ máy (PNG, JPG, WebP):</label>
                                <input type="file" name="image_file" class="form-control" accept="image/*">
                            </div>
                            <div class="col-md-7">
                                <label class="small text-muted mb-1" style="font-size: 0.75rem;">Hoặc URL ảnh / Kho ảnh máy chủ / Prompt AI:</label>
                                <?php 
                                    $images = json_decode($item['images_json'] ?? '[]', true) ?: [];
                                    $primaryImg = !empty($images) ? $images[0] : '';
                                ?>
                                <div class="input-group input-group-sm">
                                    <input type="text" id="itemFormImgInput" name="image_url" class="form-control text-truncate" value="<?= htmlspecialchars($primaryImg) ?>" placeholder="https://... hoặc /uploads/images/...">
                                    <button type="button" class="btn btn-kw-primary" onclick="browseItemFormImg()">
                                        <i class="bi bi-images me-1"></i> Upload / Prompt AI 3D
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2.5 d-flex align-items-center gap-2 <?= empty($primaryImg) ? 'd-none' : '' ?>" id="itemFormImgPreviewWrap">
                            <small class="text-muted" style="font-size: 0.75rem;">Ảnh minh họa hiện tại:</small>
                            <img id="itemFormImgPreview" src="<?= htmlspecialchars($primaryImg) ?>" style="height: 52px; border-radius: var(--kw-radius-sm); border: 1px solid var(--kw-border);" alt="Card Image">
                        </div>
                    </div>

                    <!-- Audio Section -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border h-100" style="border-color: var(--kw-border) !important;">
                            <label class="form-label fw-semibold text-dark mb-1">
                                🇻🇳 Âm Thanh Phát Âm Tiếng Việt
                            </label>
                            <input type="file" name="audio_vi_file" class="form-control mb-2" accept="audio/*">
                            <input type="text" name="pronounce_vi_url" class="form-control" value="<?= htmlspecialchars($item['pronounce_vi_url'] ?? '') ?>" placeholder="Hoặc dán URL file .mp3 / để trống dùng TTS">
                            <?php if (!empty($item['pronounce_vi_url'])): ?>
                                <small class="text-muted d-block mt-1 font-monospace" style="font-size: 0.725rem;">File: <?= htmlspecialchars($item['pronounce_vi_url']) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border h-100" style="border-color: var(--kw-border) !important;">
                            <label class="form-label fw-semibold text-dark mb-1">
                                🇺🇸 Âm Thanh Phát Âm Tiếng Anh
                            </label>
                            <input type="file" name="audio_en_file" class="form-control mb-2" accept="audio/*">
                            <input type="text" name="pronounce_en_url" class="form-control" value="<?= htmlspecialchars($item['pronounce_en_url'] ?? '') ?>" placeholder="Hoặc dán URL file .mp3 / để trống dùng TTS">
                            <?php if (!empty($item['pronounce_en_url'])): ?>
                                <small class="text-muted d-block mt-1 font-monospace" style="font-size: 0.725rem;">File: <?= htmlspecialchars($item['pronounce_en_url']) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Phonics & SFX -->
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="bi bi-spellcheck text-purple me-1"></i> Phonics / Ngữ Âm Tiếng Anh
                        </label>
                        <input type="text" name="phonics_en" class="form-control" value="<?= htmlspecialchars($item['phonics_en'] ?? '') ?>" placeholder="L - /l/ - Lion, A - /æ/ - Apple...">
                        <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Âm ngữ chuẩn giúp bé học đánh vần tự nhiên</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="bi bi-soundwave text-success me-1"></i> Tiếng Kêu / Âm Thanh Thực Tế (SFX)
                        </label>
                        <input type="text" name="sfx_sound" class="form-control" value="<?= htmlspecialchars($item['sfx_sound'] ?? '') ?>" placeholder="Gừừừ... Roaaar!, Bíp bíp! Honk honk! hoặc URL file âm thanh">
                        <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Tiếng động thực tế đặc trưng để bé nghe tương tác</small>
                    </div>

                    <!-- Real Life Photo Section -->
                    <div class="col-12 p-3 bg-light rounded-3 border" style="border-color: var(--kw-border) !important;">
                        <label class="form-label fw-semibold text-dark mb-1">
                            <i class="bi bi-camera text-info me-1"></i> Ảnh Chụp Thực Tế Ngoài Đời (Real-life Photo - National Geographic)
                        </label>
                        <div class="row g-2 align-items-center">
                            <div class="col-md-12">
                                <div class="input-group input-group-sm">
                                    <input type="text" id="itemFormRealImgInput" name="real_image_url" class="form-control text-truncate" value="<?= htmlspecialchars($item['real_image_url'] ?? '') ?>" placeholder="https://... hoặc /uploads/images/...">
                                    <button type="button" class="btn btn-kw-subtle" onclick="browseRealImage()">
                                        <i class="bi bi-camera me-1"></i> Upload / Prompt AI Ảnh Thật
                                    </button>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Giúp bé chuyển đổi so sánh giữa hình 3D minh họa và ảnh chụp thực tế ngoài đời sống</small>
                            </div>
                        </div>
                        <div class="mt-2.5 d-flex align-items-center gap-2 <?= empty($item['real_image_url']) ? 'd-none' : '' ?>" id="itemFormRealImgPreviewWrap">
                            <small class="text-muted" style="font-size: 0.75rem;">Ảnh chụp thật hiện tại:</small>
                            <img id="itemFormRealImgPreview" src="<?= htmlspecialchars($item['real_image_url'] ?? '') ?>" style="height: 52px; border-radius: var(--kw-radius-sm); border: 1px solid var(--kw-border);" alt="Real Image">
                        </div>
                    </div>

                    <!-- Coloring Outline Section -->
                    <div class="col-12 p-3 bg-light rounded-3 border" style="border-color: var(--kw-border) !important;">
                        <label class="form-label fw-semibold text-dark mb-1">
                            <i class="bi bi-brush text-warning me-1"></i> Tranh Nét Tô Màu Cho Game (Line Art / Coloring Outline)
                        </label>
                        <div class="row g-2 align-items-center">
                            <div class="col-md-5">
                                <label class="small text-muted mb-1" style="font-size: 0.75rem;">Tải tranh vẽ nét từ máy (PNG/JPG đen trắng):</label>
                                <input type="file" name="coloring_outline_file" class="form-control" accept="image/*">
                            </div>
                            <div class="col-md-7">
                                <label class="small text-muted mb-1" style="font-size: 0.75rem;">Hoặc URL ảnh / Kho ảnh nét AI:</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" id="itemFormOutlineInput" name="coloring_outline_url" class="form-control text-truncate" value="<?= htmlspecialchars($item['coloring_outline_url'] ?? '') ?>" placeholder="https://... hoặc /uploads/images/...">
                                    <button type="button" class="btn btn-kw-subtle" onclick="browseColoringOutline()">
                                        <i class="bi bi-palette me-1"></i> Kho Tranh Nét
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2.5 d-flex align-items-center gap-2 <?= empty($item['coloring_outline_url']) ? 'd-none' : '' ?>" id="itemFormOutlinePreviewWrap">
                            <small class="text-muted" style="font-size: 0.75rem;">Tranh nét hiện tại:</small>
                            <img id="itemFormOutlinePreview" src="<?= htmlspecialchars($item['coloring_outline_url'] ?? '') ?>" style="height: 52px; border-radius: var(--kw-radius-sm); border: 1px solid var(--kw-border); background: white; padding: 2px;" alt="Coloring Outline">
                        </div>
                    </div>

                    <!-- Parent Guide Coaching Section -->
                    <div class="col-12 p-3 rounded-3 border" style="background: #FFFBEB; border-color: #FDE68A !important;">
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="bi bi-people-fill text-warning me-1"></i> Góc Cha Mẹ & Bé Khám Phá (Parent Coaching Guide)
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark" style="font-size: 0.85rem;">💡 Sự Thật Kỳ Thú (Fun Fact)</label>
                                <textarea name="fun_fact_vi" class="form-control" rows="2" placeholder="Sư tử đực có bờm xù rất oai phong..."><?= htmlspecialchars($item['fun_fact_vi'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark" style="font-size: 0.85rem;">❓ Cha Mẹ Đố Bé (Prompt Question)</label>
                                <textarea name="prompt_question_vi" class="form-control" rows="2" placeholder="Đố con biết chú sư tử ngủ bao nhiêu tiếng một ngày?..."><?= htmlspecialchars($item['prompt_question_vi'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark" style="font-size: 0.85rem;">🤸 Cùng Bé Vận Động (Action Hint)</label>
                                <textarea name="action_hint_vi" class="form-control" rows="2" placeholder="Mẹ và bé hãy cùng xòe móng vuốt và gầm vang nhà nào!..."><?= htmlspecialchars($item['action_hint_vi'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- YouTube Video ID -->
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="bi bi-youtube text-danger me-1"></i> Link Video YouTube Khám Phá
                        </label>
                        <input type="text" name="youtube_video_id" class="form-control" value="<?= htmlspecialchars($item['youtube_video_id'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=... hoặc Video ID">
                        <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Nhập URL hoặc Video ID 11 ký tự của YouTube</small>
                    </div>

                    <!-- Map Coordinates -->
                    <div class="col-md-3">
                        <label class="form-label">Tọa độ Bản đồ X</label>
                        <input type="number" step="10" name="map_x" class="form-control" value="<?= $item['map_x'] ?? 100 ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tọa độ Bản đồ Y</label>
                        <input type="number" step="10" name="map_y" class="form-control" value="<?= $item['map_y'] ?? 100 ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Thứ Tự Sắp Xếp</label>
                        <input type="number" name="sort_order" class="form-control" value="<?= $item['sort_order'] ?? 0 ?>">
                    </div>

                    <div class="col-12 mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="/packs/<?= urlencode($pack['id']) ?>/items" class="btn btn-kw-outline">Hủy Bỏ</a>
                        <button type="submit" class="btn btn-kw-primary px-4">
                            <i class="bi bi-check2"></i> <?= $isEdit ? 'Lưu Thay Đổi' : 'Thêm Thẻ Học' ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function browseItemFormImg() {
        const q = (document.querySelector('input[name="name_vi"]')?.value || '').trim() 
               || (document.querySelector('input[name="name_en"]')?.value || '').trim();
        openImageBrowser({
            inputId: 'itemFormImgInput',
            previewId: 'itemFormImgPreview',
            query: q,
            defaultTab: 'upload',
            preferredStyle: 'pixar_3d',
            callback: function(url) {
                const wrap = document.getElementById('itemFormImgPreviewWrap');
                if (wrap) wrap.classList.remove('d-none');
            }
        });
    }

    function browseRealImage() {
        const q = (document.querySelector('input[name="name_vi"]')?.value || '').trim() 
               || (document.querySelector('input[name="name_en"]')?.value || '').trim();
        openImageBrowser({
            inputId: 'itemFormRealImgInput',
            previewId: 'itemFormRealImgPreview',
            query: q,
            defaultTab: 'ai_prompt',
            preferredStyle: 'real_photo',
            callback: function(url) {
                const wrap = document.getElementById('itemFormRealImgPreviewWrap');
                if (wrap) wrap.classList.remove('d-none');
            }
        });
    }

    function browseColoringOutline() {
        const q = (document.querySelector('input[name="name_en"]')?.value || '').trim() 
               || (document.querySelector('input[name="name_vi"]')?.value || '').trim();
        openImageBrowser({
            inputId: 'itemFormOutlineInput',
            previewId: 'itemFormOutlinePreview',
            query: (q ? q + ' ' : '') + 'coloring page line art outline for kids',
            callback: function(url) {
                const wrap = document.getElementById('itemFormOutlinePreviewWrap');
                if (wrap) wrap.classList.remove('d-none');
            }
        });
    }
</script>
