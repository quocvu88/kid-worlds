<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-custom p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div class="d-flex align-items-center gap-2.5">
                    <span class="d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px; background: var(--kw-primary-light); border: 1px solid var(--kw-primary-border); border-radius: var(--kw-radius-sm); color: var(--kw-primary);">
                        <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-plus-circle' ?> fs-5"></i>
                    </span>
                    <div>
                        <h5 class="fw-semibold mb-0 text-dark"><?= htmlspecialchars($title) ?></h5>
                        <small class="text-muted">Cập nhật thông tin nhận diện và thông số kỹ thuật của gói</small>
                    </div>
                </div>
                <a href="/packs" class="btn btn-kw-outline btn-sm">
                    <i class="bi bi-arrow-left"></i> Quay Lại
                </a>
            </div>

            <?php if (!$isEdit): ?>
                <div class="banner-light-blue p-3 mb-3.5 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-stars text-primary fs-5"></i>
                        <span class="small text-dark fw-medium">Muốn tạo nhanh trọn bộ gói chủ đề kèm các thẻ flashcard học tập?</span>
                    </div>
                    <a href="/ai-generator" class="btn btn-kw-primary btn-sm py-1 px-3">
                        <i class="bi bi-stars me-1"></i> Tạo Tự Động Bằng AI
                    </a>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= $isEdit ? '/packs/' . urlencode($pack['id']) . '/update' : '/packs/store' ?>" enctype="multipart/form-data">
                <div class="row g-3">
                    <?php if (!$isEdit): ?>
                        <div class="col-md-6">
                            <label class="form-label">Mã Gói (ID) <span class="text-danger">*</span></label>
                            <input type="text" name="id" class="form-control" placeholder="topic_animals, topic_fruits..." required>
                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Định danh duy nhất, không dấu cách (vd: topic_animals)</small>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-<?= $isEdit ? '12' : '6' ?>">
                        <label class="form-label">Danh Mục <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <?php
                            $cats = ['animals' => 'Động vật', 'vehicles' => 'Xe cộ', 'fruits' => 'Trái cây', 'space' => 'Vũ trụ', 'general' => 'Tổng hợp', 'nature' => 'Thiên nhiên'];
                            $currentCat = $pack['category'] ?? 'animals';
                            foreach ($cats as $key => $name): ?>
                                <option value="<?= $key ?>" <?= $currentCat === $key ? 'selected' : '' ?>><?= $name ?> (<?= $key ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tên Tiếng Việt <span class="text-danger">*</span></label>
                        <input type="text" name="title_vi" class="form-control" value="<?= htmlspecialchars($pack['title_vi'] ?? '') ?>" placeholder="Thế Giới Động Vật" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tên Tiếng Anh <span class="text-danger">*</span></label>
                        <input type="text" name="title_en" class="form-control" value="<?= htmlspecialchars($pack['title_en'] ?? '') ?>" placeholder="Wild Animals" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Độ Tuổi Tối Thiểu</label>
                        <input type="number" name="target_age_min" class="form-control" min="1" max="15" value="<?= $pack['target_age_min'] ?? 3 ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Độ Tuổi Tối Đa</label>
                        <input type="number" name="target_age_max" class="form-control" min="1" max="15" value="<?= $pack['target_age_max'] ?? 10 ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Dung Lượng Ước Tính (MB)</label>
                        <input type="number" step="0.1" name="size_mb" class="form-control" value="<?= $pack['size_mb'] ?? 1.5 ?>">
                    </div>

                    <div class="col-12">
                        <div class="p-3 bg-light rounded-3 border" style="border-color: var(--kw-border) !important;">
                            <label class="form-label fw-semibold text-dark mb-1">Ảnh Đại Diện (Thumbnail)</label>
                            <div class="input-group mb-2">
                                <input type="file" name="thumbnail_file" class="form-control" accept="image/*">
                            </div>
                            <div class="input-group">
                                <input type="text" id="packFormThumbInput" name="thumbnail_url" class="form-control" value="<?= htmlspecialchars($pack['thumbnail_url'] ?? '') ?>" placeholder="Hoặc dán URL ảnh trực tiếp (https://...)">
                                <button type="button" class="btn btn-kw-subtle" onclick="browsePackFormThumb()">
                                    <i class="bi bi-images me-1"></i> Duyệt Kho Ảnh
                                </button>
                            </div>
                            <?php if (!empty($pack['thumbnail_url'])): ?>
                                <div class="mt-2.5 d-flex align-items-center gap-2">
                                    <small class="text-muted">Ảnh hiện tại:</small>
                                    <img id="packFormThumbPreview" src="<?= htmlspecialchars($pack['thumbnail_url']) ?>" style="height: 48px; border-radius: var(--kw-radius-sm); border: 1px solid var(--kw-border);" alt="Current thumbnail">
                                </div>
                            <?php else: ?>
                                <div class="mt-2 d-none align-items-center gap-2" id="packFormThumbPreviewWrap">
                                    <small class="text-muted">Ảnh đã chọn:</small>
                                    <img id="packFormThumbPreview" src="" style="height: 48px; border-radius: var(--kw-radius-sm); border: 1px solid var(--kw-border);" alt="Preview">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">
                            <i class="bi bi-image text-primary me-1"></i> Hình Nền Bộ Chủ Đề (Background Theme)
                        </label>
                        <input type="text" name="background_url" class="form-control" value="<?= htmlspecialchars($pack['background_url'] ?? '') ?>" placeholder="assets/backgrounds/bg_green_meadow.jpg hoặc URL ảnh nền">
                        <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Ví dụ: <code>assets/backgrounds/bg_green_meadow.jpg</code> hoặc dán URL Unsplash</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="bi bi-palette text-danger me-1"></i> Màu Sắc Chủ Đạo (Theme Color)
                        </label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars($pack['theme_color'] ?? '#FF6584') ?>" onchange="document.getElementById('themeColorText').value = this.value">
                            <input type="text" id="themeColorText" name="theme_color" class="form-control" value="<?= htmlspecialchars($pack['theme_color'] ?? '#FF6584') ?>" placeholder="#FF6584">
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Mô Tả Tiếng Việt</label>
                        <textarea name="description_vi" class="form-control" rows="2" placeholder="Giới thiệu nội dung của gói chủ đề..."><?= htmlspecialchars($pack['description_vi'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Mô Tả Tiếng Anh</label>
                        <textarea name="description_en" class="form-control" rows="2" placeholder="English description..."><?= htmlspecialchars($pack['description_en'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" value="1" <?= (!isset($pack['is_active']) || $pack['is_active'] == 1) ? 'checked' : '' ?>>
                            <label class="form-check-label text-dark" for="isActiveSwitch" style="font-size: 0.875rem;">
                                Kích hoạt gói (Cho phép ứng dụng nhìn thấy và tải về)
                            </label>
                        </div>
                    </div>

                    <div class="col-12 mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="/packs" class="btn btn-kw-outline">Hủy Bỏ</a>
                        <button type="submit" class="btn btn-kw-primary px-4">
                            <i class="bi bi-check2"></i> <?= $isEdit ? 'Lưu Cập Nhật' : 'Tạo Gói Chủ Đề' ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function browsePackFormThumb() {
        const q = (document.querySelector('input[name="title_en"]')?.value || '').trim() 
               || (document.querySelector('input[name="title_vi"]')?.value || '').trim();
        openImageBrowser({
            inputId: 'packFormThumbInput',
            previewId: 'packFormThumbPreview',
            query: q,
            callback: function(url) {
                const wrap = document.getElementById('packFormThumbPreviewWrap');
                if (wrap) wrap.classList.remove('d-none');
            }
        });
    }
</script>
