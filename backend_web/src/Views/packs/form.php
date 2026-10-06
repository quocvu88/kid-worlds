<div class="row justify-content-center">
    <div class="col-lg-11 col-xl-10">
        <div class="card card-custom p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2.5">
                    <span class="d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: var(--kw-primary-light); border: 1px solid var(--kw-primary-border); border-radius: var(--kw-radius-sm); color: var(--kw-primary);">
                        <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-plus-circle' ?> fs-5"></i>
                    </span>
                    <div>
                        <h5 class="fw-semibold mb-0 text-dark"><?= htmlspecialchars($title) ?></h5>
                        <small class="text-muted">Quản lý toàn diện: Thông tin chung, Thẻ nội dung học tập và Mini-Games của bộ sưu tập</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="/packs" class="btn btn-kw-outline btn-sm">
                        <i class="bi bi-arrow-left"></i> Quay Lại Danh Sách
                    </a>
                </div>
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

            <!-- Navigation Tabs -->
            <ul class="nav nav-pills mb-4 p-1.5 bg-light rounded-4 border d-inline-flex gap-1" id="packTab" role="tablist" style="border-color: var(--kw-border) !important;">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active px-3.5 py-2 rounded-3 fw-semibold" id="general-tab" data-bs-toggle="pill" data-bs-target="#tab-general" type="button" role="tab">
                        <i class="bi bi-info-circle me-1.5"></i> 1. Thông Tin Chung
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-3.5 py-2 rounded-3 fw-semibold position-relative" id="items-tab" data-bs-toggle="pill" data-bs-target="#tab-items" type="button" role="tab">
                        <i class="bi bi-card-checklist me-1.5"></i> 2. Các Thẻ Nội Dung
                        <span class="badge bg-secondary ms-1.5 rounded-pill" style="font-size: 0.72rem;"><?= count($items ?? []) ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-3.5 py-2 rounded-3 fw-semibold" id="games-tab" data-bs-toggle="pill" data-bs-target="#tab-games" type="button" role="tab">
                        <i class="bi bi-controller me-1.5"></i> 3. Mini-Games
                    </button>
                </li>
            </ul>

            <form method="POST" action="<?= $isEdit ? '/packs/' . urlencode($pack['id']) . '/update' : '/packs/store' ?>" enctype="multipart/form-data">
                <div class="tab-content" id="packTabContent">
                    
                    <!-- ======================================================== -->
                    <!-- TAB 1: THÔNG TIN CHUNG, HÌNH NỀN, MÔ TẢ                  -->
                    <!-- ======================================================== -->
                    <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                        <div class="row g-3.5">
                            <?php if (!$isEdit): ?>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium text-dark">Mã Gói (ID) <span class="text-danger">*</span></label>
                                    <input type="text" name="id" class="form-control" placeholder="topic_animals, topic_fruits..." required>
                                    <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Định danh duy nhất, không dấu cách (vd: topic_animals)</small>
                                </div>
                            <?php endif; ?>

                            <div class="col-md-<?= $isEdit ? '12' : '6' ?>">
                                <label class="form-label fw-medium text-dark">Phân Loại / Danh Mục (Category) <span class="text-danger">*</span></label>
                                <select name="category" class="form-select" required>
                                    <?php
                                    $cats = [
                                        'simple' => 'Simple (Gói thẻ học tiêu chuẩn)',
                                        'map_2d' => 'Map 2D (Bản đồ khám phá 2D - Đang phát triển)',
                                        'journey' => 'Chuỗi Hành Trình (Học theo tiến trình - Đang phát triển)',
                                        'animals' => 'Động vật (animals)',
                                        'vehicles' => 'Xe cộ (vehicles)',
                                        'fruits' => 'Trái cây (fruits)',
                                        'space' => 'Vũ trụ (space)',
                                        'general' => 'Tổng hợp (general)',
                                    ];
                                    $currentCat = $pack['category'] ?? 'simple';
                                    foreach ($cats as $key => $name): ?>
                                        <option value="<?= $key ?>" <?= $currentCat === $key ? 'selected' : '' ?>><?= $name ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Các gói hiện tại thuộc phân loại <strong>Simple</strong>. Các phân loại khác sẽ bổ sung sau.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium text-dark">Tên Tiếng Việt <span class="text-danger">*</span></label>
                                <input type="text" name="title_vi" class="form-control" value="<?= htmlspecialchars($pack['title_vi'] ?? '') ?>" placeholder="Thế Giới Khủng Long Tiền Sử" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium text-dark">Tên Tiếng Anh <span class="text-danger">*</span></label>
                                <input type="text" name="title_en" class="form-control" value="<?= htmlspecialchars($pack['title_en'] ?? '') ?>" placeholder="Prehistoric Dinosaurs" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-medium text-dark">Độ Tuổi Tối Thiểu</label>
                                <input type="number" name="target_age_min" class="form-control" min="1" max="15" value="<?= $pack['target_age_min'] ?? 3 ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-medium text-dark">Độ Tuổi Tối Đa</label>
                                <input type="number" name="target_age_max" class="form-control" min="1" max="15" value="<?= $pack['target_age_max'] ?? 10 ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-medium text-dark">Dung Lượng Ước Tính (MB)</label>
                                <input type="number" step="0.1" name="size_mb" class="form-control" value="<?= $pack['size_mb'] ?? 1.5 ?>">
                            </div>

                            <!-- Thumbnail Upload / URL -->
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

                            <!-- Background Theme & Theme Color -->
                            <div class="col-md-8">
                                <label class="form-label fw-medium text-dark">
                                    <i class="bi bi-image text-primary me-1"></i> Hình Nền Bộ Chủ Đề (Background Theme)
                                </label>
                                <input type="text" name="background_url" class="form-control" value="<?= htmlspecialchars($pack['background_url'] ?? '') ?>" placeholder="assets/backgrounds/bg_green_meadow.jpg hoặc URL ảnh nền">
                                <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Ví dụ: <code>assets/backgrounds/bg_green_meadow.jpg</code> hoặc dán URL ảnh</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-medium text-dark">
                                    <i class="bi bi-palette text-danger me-1"></i> Màu Sắc Chủ Đạo (Theme Color)
                                </label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars($pack['theme_color'] ?? '#FF6584') ?>" onchange="document.getElementById('themeColorText').value = this.value">
                                    <input type="text" id="themeColorText" name="theme_color" class="form-control" value="<?= htmlspecialchars($pack['theme_color'] ?? '#FF6584') ?>" placeholder="#FF6584">
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-medium text-dark">Mô Tả Tiếng Việt</label>
                                <textarea name="description_vi" class="form-control" rows="2" placeholder="Giới thiệu nội dung của gói chủ đề..."><?= htmlspecialchars($pack['description_vi'] ?? '') ?></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-medium text-dark">Mô Tả Tiếng Anh</label>
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
                        </div>
                    </div>

                    <!-- ======================================================== -->
                    <!-- TAB 2: CÁC THẺ NỘI DUNG (FLASHCARD ITEMS)                -->
                    <!-- ======================================================== -->
                    <div class="tab-pane fade" id="tab-items" role="tabpanel">
                        <?php if (!$isEdit): ?>
                            <div class="text-center py-5 text-muted bg-light rounded-4 border">
                                <i class="bi bi-card-checklist fs-1 text-muted opacity-40 mb-2 d-block"></i>
                                <h6 class="fw-semibold text-dark">Vui lòng tạo gói trước khi quản lý thẻ</h6>
                                <p class="small text-muted mb-3">Sau khi lưu gói chủ đề mới, bạn sẽ có thể thêm các thẻ từ vựng vào đây.</p>
                            </div>
                        <?php else: ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Danh Sách Thẻ Từ Vựng Trong Gói</h6>
                                    <small class="text-muted">Tổng cộng <?= count($items) ?> thẻ học tập Flashcard cho bé</small>
                                </div>
                                <a href="/packs/<?= urlencode($pack['id']) ?>/items/create" class="btn btn-kw-primary btn-sm">
                                    <i class="bi bi-plus-lg me-1"></i> Thêm Thẻ Mới
                                </a>
                            </div>

                            <?php if (empty($items)): ?>
                                <div class="text-center py-5 text-muted bg-light rounded-4 border">
                                    <i class="bi bi-card-heading fs-1 text-muted opacity-40 mb-2 d-block"></i>
                                    <h6 class="fw-semibold text-dark">Chưa có thẻ từ vựng nào trong gói này!</h6>
                                    <p class="small text-muted mb-3">Hãy thêm thẻ từ vựng đầu tiên kèm hình ảnh minh họa, phát âm và âm thanh.</p>
                                    <a href="/packs/<?= urlencode($pack['id']) ?>/items/create" class="btn btn-kw-primary btn-sm">
                                        <i class="bi bi-plus-lg me-1"></i> Thêm Thẻ Đầu Tiên
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive rounded-3 border">
                                    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 45px;">STT</th>
                                                <th style="width: 65px;">Hình</th>
                                                <th>Tên Tiếng Việt</th>
                                                <th>Tên Tiếng Anh</th>
                                                <th>Âm Thanh</th>
                                                <th>YouTube</th>
                                                <th class="text-end" style="width: 120px;">Thao Tác</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($items as $idx => $it): 
                                                $images = json_decode($it['images_json'] ?? '[]', true) ?: [];
                                                $primaryImg = !empty($images) ? $images[0] : '';
                                            ?>
                                                <tr>
                                                    <td class="text-muted font-monospace"><?= $idx + 1 ?></td>
                                                    <td>
                                                        <?php if (!empty($primaryImg)): ?>
                                                            <img src="<?= htmlspecialchars($primaryImg) ?>" style="width: 44px; height: 44px; object-fit: cover; border-radius: 8px; border: 1px solid var(--kw-border);" alt="Card Image">
                                                        <?php else: ?>
                                                            <div style="width: 44px; height: 44px; border-radius: 8px; background: #e2e8f0;" class="d-flex align-items-center justify-content-center text-muted">
                                                                <i class="bi bi-image"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($it['name_vi']) ?></div>
                                                        <?php if (!empty($it['phonics_en'])): ?>
                                                            <small class="text-muted font-monospace"><?= htmlspecialchars($it['phonics_en']) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold text-primary"><?= htmlspecialchars($it['name_en']) ?></div>
                                                        <small class="text-muted d-block text-truncate" style="max-width: 180px;"><?= htmlspecialchars($it['description_en'] ?? '') ?></small>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex gap-1">
                                                            <?php if (!empty($it['pronounce_en_url'])): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1.5" onclick="playAudio('<?= htmlspecialchars($it['pronounce_en_url']) ?>')" title="Nghe tiếng Anh">
                                                                    🇺🇸
                                                                </button>
                                                            <?php endif; ?>
                                                            <?php if (!empty($it['pronounce_vi_url'])): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-warning py-0 px-1.5" onclick="playAudio('<?= htmlspecialchars($it['pronounce_vi_url']) ?>')" title="Nghe tiếng Việt">
                                                                    🇻🇳
                                                                </button>
                                                            <?php endif; ?>
                                                            <?php if (!empty($it['sfx_sound'])): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-success py-0 px-1.5" onclick="playAudio('<?= htmlspecialchars($it['sfx_sound']) ?>')" title="Âm thanh SFX">
                                                                    🔊
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($it['youtube_video_id'])): ?>
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                                <i class="bi bi-youtube me-0.5"></i> Có
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="/items/<?= urlencode($it['id']) ?>/edit" class="btn btn-sm btn-kw-outline py-0.5 px-2" title="Sửa thẻ">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <a href="/items/<?= urlencode($it['id']) ?>/delete" class="btn btn-sm btn-kw-outline py-0.5 px-2 text-danger" onclick="return confirm('Bạn có chắc muốn xóa thẻ này?');" title="Xóa thẻ">
                                                            <i class="bi bi-trash3"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- ======================================================== -->
                    <!-- TAB 3: CẤU HÌNH MINI-GAMES                               -->
                    <!-- ======================================================== -->
                    <div class="tab-pane fade" id="tab-games" role="tabpanel">
                        <?php
                        $currentGames = json_decode($pack['selected_games_json'] ?? '["coloring","memory_match"]', true);
                        if (!is_array($currentGames) || empty($currentGames)) {
                            $currentGames = ['coloring', 'memory_match'];
                        }
                        ?>
                        <div class="card p-4 border-0 rounded-4" style="background: #FDFBF7; border: 1px solid #EBE5DB !important;">
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2" style="font-size: 1.05rem;">
                                        <i class="bi bi-controller text-primary fs-4"></i> Mini-Games Kèm Theo Bộ Sưu Tập
                                    </h6>
                                    <p class="text-muted mb-0" style="font-size: 0.85rem;">
                                        Mỗi bộ sưu tập nên chọn từ <strong>1 đến tối đa 3 games</strong> để app luôn nhẹ, tải nhanh và giữ giao diện cho bé trong trẻo.
                                    </p>
                                </div>
                                <span id="gameSelectionBadge" class="badge rounded-pill bg-primary px-3.5 py-2" style="font-size: 0.875rem;">
                                    Đã chọn: <span id="selectedGameCount"><?= count($currentGames) ?></span> / 3 game
                                </span>
                            </div>

                            <div class="row g-3 mt-1" id="gamesSelectionRow">
                                <!-- 1. Tô Màu Sáng Tạo -->
                                <div class="col-md-4 col-sm-6">
                                    <label class="game-select-card d-flex align-items-start gap-3 p-3 rounded-4 border bg-white cursor-pointer h-100 shadow-sm" style="border-color: #E2E8F0 !important; cursor: pointer; transition: all 0.2s ease;">
                                        <input type="checkbox" name="selected_games[]" value="coloring" class="form-check-input game-checkbox mt-1" <?= in_array('coloring', $currentGames) ? 'checked' : '' ?>>
                                        <span class="fs-2">🎨</span>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">Tô Màu Sáng Tạo</div>
                                            <p class="text-muted mb-0 mt-1" style="font-size: 0.775rem; line-height: 1.35;">
                                                Tranh nét AI không bóng, cọ vẽ sáp, bảng màu kẹo ngọt và lưu vào góc tranh của bé.
                                            </p>
                                        </div>
                                    </label>
                                </div>

                                <!-- 2. Lật Hình Nhớ Cặp -->
                                <div class="col-md-4 col-sm-6">
                                    <label class="game-select-card d-flex align-items-start gap-3 p-3 rounded-4 border bg-white cursor-pointer h-100 shadow-sm" style="border-color: #E2E8F0 !important; cursor: pointer; transition: all 0.2s ease;">
                                        <input type="checkbox" name="selected_games[]" value="memory_match" class="form-check-input game-checkbox mt-1" <?= in_array('memory_match', $currentGames) ? 'checked' : '' ?>>
                                        <span class="fs-2">🃏</span>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">Lật Hình Nhớ Cặp</div>
                                            <p class="text-muted mb-0 mt-1" style="font-size: 0.775rem; line-height: 1.35;">
                                                Lật mở thẻ 3D ma trận 3x4 / 4x6 / 6x8 theo tuổi, đọc từ vựng, tính giờ & vinh danh sao.
                                            </p>
                                        </div>
                                    </label>
                                </div>

                                <!-- 3. Ghép Bóng Nhận Diện -->
                                <div class="col-md-4 col-sm-6">
                                    <label class="game-select-card d-flex align-items-start gap-3 p-3 rounded-4 border bg-white cursor-pointer h-100 shadow-sm" style="border-color: #E2E8F0 !important; cursor: pointer; transition: all 0.2s ease;">
                                        <input type="checkbox" name="selected_games[]" value="shadow_match" class="form-check-input game-checkbox mt-1" <?= in_array('shadow_match', $currentGames) ? 'checked' : '' ?>>
                                        <span class="fs-2">🧩</span>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">Ghép Bóng Nhận Diện</div>
                                            <p class="text-muted mb-0 mt-1" style="font-size: 0.775rem; line-height: 1.35;">
                                                Kéo thả hình màu khớp đúng với bóng đen Silhouette, rèn thị giác không gian.
                                            </p>
                                        </div>
                                    </label>
                                </div>

                                <!-- 4. Đập Bóng Từ Vựng -->
                                <div class="col-md-4 col-sm-6">
                                    <label class="game-select-card d-flex align-items-start gap-3 p-3 rounded-4 border bg-white cursor-pointer h-100 shadow-sm" style="border-color: #E2E8F0 !important; cursor: pointer; transition: all 0.2s ease;">
                                        <input type="checkbox" name="selected_games[]" value="bubble_pop" class="form-check-input game-checkbox mt-1" <?= in_array('bubble_pop', $currentGames) ? 'checked' : '' ?>>
                                        <span class="fs-2">🫧</span>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">Đập Bóng Từ Vựng</div>
                                            <p class="text-muted mb-0 mt-1" style="font-size: 0.775rem; line-height: 1.35;">
                                                Lắng nghe phát âm và chạm nhanh làm nổ tung bong bóng mang hình ảnh đúng.
                                            </p>
                                        </div>
                                    </label>
                                </div>

                                <!-- 5. Xếp Hình Khéo Léo -->
                                <div class="col-md-4 col-sm-6">
                                    <label class="game-select-card d-flex align-items-start gap-3 p-3 rounded-4 border bg-white cursor-pointer h-100 shadow-sm" style="border-color: #E2E8F0 !important; cursor: pointer; transition: all 0.2s ease;">
                                        <input type="checkbox" name="selected_games[]" value="jigsaw" class="form-check-input game-checkbox mt-1" <?= in_array('jigsaw', $currentGames) ? 'checked' : '' ?>>
                                        <span class="fs-2">🖼️</span>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">Xếp Hình Khéo Léo</div>
                                            <p class="text-muted mb-0 mt-1" style="font-size: 0.775rem; line-height: 1.35;">
                                                Ghép tranh chủ đề 4, 9, 16 mảnh ghép thông minh kèm tính năng hút nam châm.
                                            </p>
                                        </div>
                                    </label>
                                </div>

                                <!-- 6. Nối Âm Thanh & Hình -->
                                <div class="col-md-4 col-sm-6">
                                    <label class="game-select-card d-flex align-items-start gap-3 p-3 rounded-4 border bg-white cursor-pointer h-100 shadow-sm" style="border-color: #E2E8F0 !important; cursor: pointer; transition: all 0.2s ease;">
                                        <input type="checkbox" name="selected_games[]" value="sound_linker" class="form-check-input game-checkbox mt-1" <?= in_array('sound_linker', $currentGames) ? 'checked' : '' ?>>
                                        <span class="fs-2">🔔</span>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">Nối Âm Thanh & Hình</div>
                                            <p class="text-muted mb-0 mt-1" style="font-size: 0.775rem; line-height: 1.35;">
                                                Chiếc loa thần kỳ phát tiếng kêu SFX thực tế, bé đoán và chạm đúng hình ảnh.
                                            </p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div id="gameMaxWarning" class="alert alert-warning py-2 px-3 mt-3 mb-0 small <?= count($currentGames) >= 3 ? '' : 'd-none' ?>">
                                <i class="bi bi-info-circle me-1"></i> Đã chọn tối đa 3 games cho bộ sưu tập này. Bỏ bớt 1 game nếu muốn chọn game khác.
                            </div>
                        </div>

                        <?php
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
                        ?>

                        <!-- Khu Vực Cấu Hình Nội Dung Chi Tiết Cho Từng Game -->
                        <div class="mt-4 pt-2">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2" style="font-size: 1.05rem;">
                                        <i class="bi bi-sliders2 text-primary"></i> Cấu Hình Nội Dung Chi Tiết Cho Từng Game Đã Chọn
                                    </h6>
                                    <small class="text-muted">Tùy biến hình ảnh vẽ nét, độ khó, ma trận và trải nghiệm chơi phù hợp với độ tuổi của bé</small>
                                </div>
                            </div>

                            <!-- 1. Cấu Hình Game Tô Màu Sáng Tạo -->
                            <div id="game-config-coloring" class="card border rounded-4 p-3 mb-3 bg-white shadow-sm" style="border-color: #E2E8F0 !important;">
                                <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fs-4">🎨</span>
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark">Game Tô Màu Sáng Tạo - Tranh Nét Cho Thẻ</h6>
                                            <small class="text-muted">Cung cấp tranh vẽ nét đen trắng (Line Art) cho từng thẻ từ vựng để bé tô màu ngón tay</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1">Coloring Studio</span>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-dark">Bảng Màu Cọ Vẽ Cho Bé</label>
                                        <select name="game_config[coloring_palette]" class="form-select form-select-sm">
                                            <option value="candy" <?= $coloringPalette === 'candy' ? 'selected' : '' ?>>🍭 Bảng Màu Kẹo Ngọt (Candy Pastel - Dịu mắt)</option>
                                            <option value="rainbow" <?= $coloringPalette === 'rainbow' ? 'selected' : '' ?>>🌈 Cầu Vồng Rực Rỡ (Vivid Primary Colors)</option>
                                            <option value="nature" <?= $coloringPalette === 'nature' ? 'selected' : '' ?>>🍃 Thảo Nguyên Tự Nhiên (Earth & Green)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-dark">Lưu Tranh Vào Góc Sáng Tạo Của Bé</label>
                                        <select name="game_config[coloring_export]" class="form-select form-select-sm">
                                            <option value="1" <?= $coloringExport == 1 ? 'selected' : '' ?>>Bật - Cho phép bé bấm lưu tác phẩm và pháo giấy chúc mừng</option>
                                            <option value="0" <?= $coloringExport == 0 ? 'selected' : '' ?>>Tắt</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mt-2">
                                    <label class="form-label small fw-bold text-dark d-flex align-items-center justify-content-between">
                                        <span><i class="bi bi-images text-primary me-1"></i> Danh Sách Tranh Nét Tô Màu Theo Thẻ</span>
                                        <span class="text-muted fw-normal" style="font-size: 0.775rem;">(Nếu chưa có tranh nét, game sẽ tự động dùng ảnh mờ làm gợi ý)</span>
                                    </label>

                                    <?php if (empty($items)): ?>
                                        <div class="p-3 text-center bg-light rounded-3 text-muted small border">
                                            <i class="bi bi-info-circle me-1"></i> Gói này chưa có thẻ từ vựng nào. Hãy lưu gói và thêm thẻ tại <strong>Tab 2 (Các Thẻ Nội Dung)</strong>, sau đó quay lại đây để gán tranh nét vẽ.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive rounded-3 border">
                                            <table class="table table-hover align-middle mb-0" style="font-size: 0.825rem;">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width: 40px;">STT</th>
                                                        <th style="width: 170px;">Thẻ Học</th>
                                                        <th>Đường Dẫn Tranh Nét (Line Art Outline URL) / Tải Lên</th>
                                                        <th class="text-center" style="width: 100px;">Xem Tranh Nét</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($items as $idx => $it): 
                                                        $images = json_decode($it['images_json'] ?? '[]', true) ?: [];
                                                        $primaryImg = !empty($images) ? $images[0] : '';
                                                        $outline = $it['coloring_outline_url'] ?? '';
                                                        $cleanItemId = preg_replace('/[^a-zA-Z0-9_]/', '_', $it['id']);
                                                    ?>
                                                        <tr>
                                                            <td class="text-muted font-monospace"><?= $idx + 1 ?></td>
                                                            <td>
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <?php if (!empty($primaryImg)): ?>
                                                                        <img src="<?= htmlspecialchars($primaryImg) ?>" style="width: 36px; height: 36px; object-fit: cover; border-radius: 6px; border: 1px solid var(--kw-border);" alt="Thumb">
                                                                    <?php else: ?>
                                                                        <div style="width: 36px; height: 36px; border-radius: 6px; background: #e2e8f0;" class="d-flex align-items-center justify-content-center text-muted">
                                                                            <i class="bi bi-image"></i>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div>
                                                                        <div class="fw-semibold text-dark text-truncate" style="max-width: 120px;"><?= htmlspecialchars($it['name_vi']) ?></div>
                                                                        <div class="text-primary font-monospace small" style="font-size: 0.725rem;"><?= htmlspecialchars($it['name_en']) ?></div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex flex-column gap-1.5">
                                                                    <div class="input-group input-group-sm">
                                                                        <input type="text" 
                                                                               id="outline_input_<?= $cleanItemId ?>" 
                                                                               name="coloring_outline[<?= htmlspecialchars($it['id']) ?>]" 
                                                                               class="form-control text-truncate font-monospace" 
                                                                               value="<?= htmlspecialchars($outline) ?>" 
                                                                               placeholder="https://... hoặc /uploads/images/... hoặc để trống">
                                                                        <button type="button" 
                                                                                class="btn btn-kw-subtle" 
                                                                                onclick="browseOutlineImage('<?= $cleanItemId ?>', '<?= htmlspecialchars(addslashes($it['name_en'])) ?>')">
                                                                            <i class="bi bi-search me-1"></i> Kho Ảnh / AI Outline
                                                                        </button>
                                                                    </div>
                                                                    <div class="d-flex align-items-center gap-2">
                                                                        <small class="text-muted" style="font-size: 0.725rem;">Hoặc tải file từ máy:</small>
                                                                        <input type="file" 
                                                                               name="coloring_outline_file_<?= $cleanItemId ?>" 
                                                                               class="form-control form-control-sm py-0.5" 
                                                                               accept="image/*" 
                                                                               style="max-width: 250px; font-size: 0.725rem;"
                                                                               onchange="previewOutlineLocal(this, 'outline_preview_<?= $cleanItemId ?>', 'outline_preview_wrap_<?= $cleanItemId ?>')">
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="text-center">
                                                                <div id="outline_preview_wrap_<?= $cleanItemId ?>" class="<?= empty($outline) ? 'd-none' : '' ?>">
                                                                    <img id="outline_preview_<?= $cleanItemId ?>" 
                                                                         src="<?= htmlspecialchars($outline) ?>" 
                                                                         style="width: 48px; height: 48px; object-fit: contain; border-radius: 8px; border: 1px solid var(--kw-border); background: #ffffff; padding: 2px;" 
                                                                         alt="Outline Preview">
                                                                </div>
                                                                <span id="outline_placeholder_<?= $cleanItemId ?>" class="text-muted small <?= !empty($outline) ? 'd-none' : '' ?>" style="font-size: 0.75rem;">
                                                                    (Dùng ảnh gốc)
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- 2. Cấu Hình Game Lật Hình Nhớ Cặp -->
                            <div id="game-config-memory_match" class="card border rounded-4 p-3 mb-3 bg-white shadow-sm" style="border-color: #E2E8F0 !important;">
                                <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fs-4">🃏</span>
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark">Game Lật Hình Nhớ Cặp (Memory Match)</h6>
                                            <small class="text-muted">Thiết lập ma trận thẻ lật, chế độ phát âm từ vựng và tính giờ</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1">Brain Memory</span>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-dark">Ma Trận Mặc Định (Độ Khó)</label>
                                        <select name="game_config[memory_matrix]" class="form-select form-select-sm">
                                            <option value="3x4" <?= $memoryMatrix === '3x4' ? 'selected' : '' ?>>3x4 (12 thẻ / 6 cặp) - Phù hợp bé 3 - 4 tuổi</option>
                                            <option value="4x6" <?= $memoryMatrix === '4x6' ? 'selected' : '' ?>>4x6 (24 thẻ / 12 cặp) - Tiêu chuẩn (5 - 7 tuổi)</option>
                                            <option value="6x8" <?= $memoryMatrix === '6x8' ? 'selected' : '' ?>>6x8 (48 thẻ / 24 cặp) - Thử thách trí nhớ (8+ tuổi)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-dark">Phát Âm Khi Ghép Đúng Cặp</label>
                                        <select name="game_config[memory_tts]" class="form-select form-select-sm">
                                            <option value="1" <?= $memoryTts == 1 ? 'selected' : '' ?>>Bật - Đọc to tên tiếng Anh & tiếng Việt</option>
                                            <option value="0" <?= $memoryTts == 0 ? 'selected' : '' ?>>Tắt - Chỉ phát âm thanh ting ting SFX</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-dark">Đếm Giờ & Giới Hạn Thời Gian</label>
                                        <select name="game_config[memory_timer]" class="form-select form-select-sm">
                                            <option value="unlimited" <?= $memoryTimer === 'unlimited' ? 'selected' : '' ?>>Bấm giờ tăng dần (Không giới hạn, bé thoải mái tìm)</option>
                                            <option value="60s" <?= $memoryTimer === '60s' ? 'selected' : '' ?>>Đếm ngược 60 giây</option>
                                            <option value="90s" <?= $memoryTimer === '90s' ? 'selected' : '' ?>>Đếm ngược 90 giây</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Cấu Hình Game Ghép Bóng Nhận Diện -->
                            <div id="game-config-shadow_match" class="card border rounded-4 p-3 mb-3 bg-white shadow-sm" style="border-color: #E2E8F0 !important;">
                                <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fs-4">🧩</span>
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark">Game Ghép Bóng Nhận Diện (Shadow Match)</h6>
                                            <small class="text-muted">Kéo thả hình màu khớp đúng với bóng đen Silhouette để rèn luyện tư duy không gian</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2.5 py-1">Silhouette Matching</span>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-dark">Số Cặp Ghép Mỗi Lượt Chơi</label>
                                        <select name="game_config[shadow_count]" class="form-select form-select-sm">
                                            <option value="3" <?= $shadowCount == 3 ? 'selected' : '' ?>>3 cặp (Dễ cho bé nhỏ tuổi)</option>
                                            <option value="4" <?= $shadowCount == 4 ? 'selected' : '' ?>>4 cặp (Tiêu chuẩn)</option>
                                            <option value="5" <?= $shadowCount == 5 ? 'selected' : '' ?>>5 cặp (Nâng cao)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-dark">Cách Tạo Bóng Silhouette</label>
                                        <select name="game_config[shadow_generator]" class="form-select form-select-sm">
                                            <option value="auto">Tự động trích xuất bóng đen từ hình ảnh thẻ trên app</option>
                                            <option value="outline">Dùng đường viền tranh nét nếu có</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Cấu Hình Game Đập Bóng Từ Vựng -->
                            <div id="game-config-bubble_pop" class="card border rounded-4 p-3 mb-3 bg-white shadow-sm" style="border-color: #E2E8F0 !important;">
                                <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fs-4">🫧</span>
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark">Game Đập Bóng Từ Vựng (Bubble Pop)</h6>
                                            <small class="text-muted">Lắng nghe giọng đọc và đập vỡ quả bóng mang hình ảnh đúng</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1">Bubble Action</span>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-dark">Tốc Độ Bóng Bay Lơ Lửng</label>
                                        <select name="game_config[bubble_speed]" class="form-select form-select-sm">
                                            <option value="slow" <?= $bubbleSpeed === 'slow' ? 'selected' : '' ?>>Chậm (Thong thả cho bé ngắm nghía)</option>
                                            <option value="normal" <?= $bubbleSpeed === 'normal' ? 'selected' : '' ?>>Bình thường (Vui nhộn)</option>
                                            <option value="fast" <?= $bubbleSpeed === 'fast' ? 'selected' : '' ?>>Nhanh (Thử thách phản xạ)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-dark">Chế Độ Giọng Đọc Đố Bé</label>
                                        <select name="game_config[bubble_voice]" class="form-select form-select-sm">
                                            <option value="bilingual" <?= $bubbleVoice === 'bilingual' ? 'selected' : '' ?>>Song ngữ Anh - Việt kết hợp</option>
                                            <option value="en_only" <?= $bubbleVoice === 'en_only' ? 'selected' : '' ?>>Chỉ phát âm Tiếng Anh chuẩn</option>
                                            <option value="sfx_sound" <?= $bubbleVoice === 'sfx_sound' ? 'selected' : '' ?>>Phát âm thanh tiếng kêu SFX thực tế</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- 5. Cấu Hình Game Xếp Hình Khéo Léo -->
                            <div id="game-config-jigsaw" class="card border rounded-4 p-3 mb-3 bg-white shadow-sm" style="border-color: #E2E8F0 !important;">
                                <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fs-4">🖼️</span>
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark">Game Xếp Hình Khéo Léo (Jigsaw Puzzle)</h6>
                                            <small class="text-muted">Ghép các mảnh tranh ảnh chủ đề khớp nối khéo léo kèm nam châm hút chuẩn xác</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1">Jigsaw Master</span>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-dark">Số Mảnh Ghép Mặc Định</label>
                                        <select name="game_config[jigsaw_pieces]" class="form-select form-select-sm">
                                            <option value="4" <?= $jigsawPieces == 4 ? 'selected' : '' ?>>4 mảnh (2x2 - Bé 3 tuổi tự tin ráp được)</option>
                                            <option value="9" <?= $jigsawPieces == 9 ? 'selected' : '' ?>>9 mảnh (3x3 - Vừa sức)</option>
                                            <option value="16" <?= $jigsawPieces == 16 ? 'selected' : '' ?>>16 mảnh (4x4 - Khéo tay)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-dark">Hình Mờ Gợi Ý Nền (Ghost Guide)</label>
                                        <select name="game_config[jigsaw_ghost]" class="form-select form-select-sm">
                                            <option value="1" <?= $jigsawGhost == 1 ? 'selected' : '' ?>>Bật - Hiển thị hình mờ 20% bên dưới giúp bé dễ quan sát khớp nối</option>
                                            <option value="0" <?= $jigsawGhost == 0 ? 'selected' : '' ?>>Tắt - Nền trống</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- 6. Cấu Hình Game Nối Âm Thanh & Hình -->
                            <div id="game-config-sound_linker" class="card border rounded-4 p-3 mb-3 bg-white shadow-sm" style="border-color: #E2E8F0 !important;">
                                <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fs-4">🔔</span>
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark">Game Nối Âm Thanh & Hình (Sound & Match)</h6>
                                            <small class="text-muted">Chiếc loa phát tiếng kêu, bé đoán và chạm đúng hình ảnh đại diện</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1">Audio Quiz</span>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-dark">Nguồn Âm Thanh Thử Thách</label>
                                        <select name="game_config[sound_source]" class="form-select form-select-sm">
                                            <option value="sfx" <?= $soundSource === 'sfx' ? 'selected' : '' ?>>Âm thanh thực tế SFX (Tiếng rống, tiếng còi xe...)</option>
                                            <option value="phonics" <?= $soundSource === 'phonics' ? 'selected' : '' ?>>Ngữ âm Phonics & Đánh vần chữ cái đầu</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-dark">Số Lựa Chọn Hình Ảnh Mỗi Câu Đố</label>
                                        <select name="game_config[sound_choices]" class="form-select form-select-sm">
                                            <option value="3">3 thẻ lựa chọn</option>
                                            <option value="4" selected>4 thẻ lựa chọn (Chuẩn)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                <!-- Footer Submit Actions -->
                <div class="col-12 mt-4 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <a href="/packs" class="btn btn-kw-outline">Hủy Bỏ</a>
                    <button type="submit" class="btn btn-kw-primary px-4 py-2">
                        <i class="bi bi-check2"></i> <?= $isEdit ? 'Lưu Cập Nhật Gói' : 'Tạo Gói Chủ Đề' ?>
                    </button>
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

    // Modal chọn / upload tranh nét cho từng thẻ trong game Tô Màu
    function browseOutlineImage(cleanItemId, nameEn) {
        openImageBrowser({
            inputId: 'outline_input_' + cleanItemId,
            previewId: 'outline_preview_' + cleanItemId,
            query: nameEn || '',
            defaultTab: 'ai_prompt',
            preferredStyle: 'coloring_outline',
            callback: function(url) {
                const wrap = document.getElementById('outline_preview_wrap_' + cleanItemId);
                const placeholder = document.getElementById('outline_placeholder_' + cleanItemId);
                if (wrap) wrap.classList.remove('d-none');
                if (placeholder) placeholder.classList.add('d-none');
            }
        });
    }

    // Xem trước file tranh nét khi tải trực tiếp từ máy
    function previewOutlineLocal(input, imgId, wrapId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
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

    // Quản lý giới hạn tối đa 3 games cho mỗi bộ sưu tập và ẩn/hiện cấu hình chi tiết tương ứng
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.game-checkbox');
        const countSpan = document.getElementById('selectedGameCount');
        const badge = document.getElementById('gameSelectionBadge');
        const warning = document.getElementById('gameMaxWarning');
        const MAX_GAMES = 3;

        const allGameKeys = ['coloring', 'memory_match', 'shadow_match', 'bubble_pop', 'jigsaw', 'sound_linker'];

        function updateGameLimitAndConfigVisibility() {
            const checkedBoxes = document.querySelectorAll('.game-checkbox:checked');
            const count = checkedBoxes.length;
            if (countSpan) countSpan.textContent = count;

            // 1. Quản lý trạng thái disabled theo limit
            if (count >= MAX_GAMES) {
                checkboxes.forEach(cb => {
                    const card = cb.closest('.game-select-card');
                    if (!cb.checked) {
                        cb.disabled = true;
                        if (card) {
                            card.style.opacity = '0.5';
                            card.style.cursor = 'not-allowed';
                        }
                    } else {
                        cb.disabled = false;
                        if (card) {
                            card.style.opacity = '1';
                            card.style.cursor = 'pointer';
                        }
                    }
                });
                if (warning) warning.classList.remove('d-none');
                if (badge) {
                    badge.classList.remove('bg-primary');
                    badge.classList.add('bg-success');
                }
            } else {
                checkboxes.forEach(cb => {
                    cb.disabled = false;
                    const card = cb.closest('.game-select-card');
                    if (card) {
                        card.style.opacity = '1';
                        card.style.cursor = 'pointer';
                    }
                });
                if (warning) warning.classList.add('d-none');
                if (badge) {
                    badge.classList.remove('bg-success');
                    badge.classList.add('bg-primary');
                }
            }

            // 2. Ẩn / Hiện section cấu hình tương ứng với từng game
            allGameKeys.forEach(gameKey => {
                const configSection = document.getElementById('game-config-' + gameKey);
                const isSelected = Array.from(checkedBoxes).some(cb => cb.value === gameKey);
                if (configSection) {
                    if (isSelected) {
                        configSection.style.display = 'block';
                    } else {
                        configSection.style.display = 'none';
                    }
                }
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateGameLimitAndConfigVisibility);
        });

        // Chạy lần đầu khi load trang
        updateGameLimitAndConfigVisibility();
    });
</script>
