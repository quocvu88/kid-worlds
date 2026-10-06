<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 0.8rem;">
                <li class="breadcrumb-item"><a href="/packs" class="text-decoration-none text-muted">Gói Chủ Đề</a></li>
                <li class="breadcrumb-item active text-primary" aria-current="page"><?= htmlspecialchars($pack['title_vi']) ?></li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h5 class="fw-semibold mb-0 text-dark">
                Thẻ Từ Vựng: <span style="color: #0284c7;"><?= htmlspecialchars($pack['title_vi']) ?></span>
            </h5>
            <span class="badge-kw-blue"><?= count($items) ?> thẻ</span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="/packs" class="btn btn-kw-outline">
            <i class="bi bi-arrow-left"></i> Danh Sách Gói
        </a>
        <a href="/packs/<?= urlencode($pack['id']) ?>/items/create" class="btn btn-kw-primary">
            <i class="bi bi-plus-lg"></i> Thêm Thẻ Mới
        </a>
    </div>
</div>

<div class="card card-custom overflow-hidden mb-4">
    <div class="card-custom-header py-3 px-4">
        <div>
            <h6 class="fw-semibold mb-0 text-dark" style="font-size: 0.95rem;">
                Danh Sách Thẻ Từ Vựng Trong Gói
                <span class="badge-kw-blue ms-1.5"><?= count($items) ?> thẻ</span>
            </h6>
            <small class="text-muted">Các thẻ hiển thị trong Flashcard 3D, Bento Parent Guide và Mini-games của bé</small>
        </div>
        <div>
            <a href="/packs/<?= urlencode($pack['id']) ?>/items/create" class="btn btn-kw-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Thêm Thẻ Mới
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($items)): ?>
            <div class="text-center py-5 text-muted">
                <div class="py-4">
                    <i class="bi bi-card-heading fs-1 text-muted opacity-40 mb-2 d-block"></i>
                    <h6 class="fw-semibold text-dark">Chưa có thẻ từ vựng nào trong gói này!</h6>
                    <p class="small text-muted mb-3">Hãy thêm thẻ từ vựng đầu tiên kèm hình ảnh minh họa, phát âm chuẩn và video khám phá.</p>
                    <a href="/packs/<?= urlencode($pack['id']) ?>/items/create" class="btn btn-kw-primary btn-sm">
                        <i class="bi bi-plus-lg"></i> Thêm Thẻ Ngay
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th style="width: 50px;">STT</th>
                            <th style="width: 70px;">Hình Ảnh</th>
                            <th>Tên Tiếng Việt</th>
                            <th>Tên Tiếng Anh</th>
                            <th>Phát Âm (Audio)</th>
                            <th>Video YouTube</th>
                            <th>Tọa Độ Bản Đồ</th>
                            <th class="text-end" style="width: 140px;">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $idx => $it): 
                            $images = json_decode($it['images_json'] ?? '[]', true) ?: [];
                            $primaryImg = !empty($images) ? $images[0] : '';
                        ?>
                            <tr>
                                <td class="text-muted font-monospace" style="font-size: 0.775rem;"><?= $idx + 1 ?></td>
                                <td>
                                    <?php if (!empty($primaryImg)): ?>
                                        <img src="<?= htmlspecialchars($primaryImg) ?>" class="topic-thumb-sm" alt="Card Image">
                                    <?php else: ?>
                                        <div class="topic-thumb-sm bg-light d-flex align-items-center justify-content-center text-muted">
                                            <i class="bi bi-image fs-6 opacity-50"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($it['name_vi']) ?></div>
                                    <small class="text-muted text-truncate d-inline-block" style="max-width: 220px; font-size: 0.775rem;">
                                        <?= htmlspecialchars($it['description_vi'] ?? '') ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="fw-semibold" style="color: #0284c7;"><?= htmlspecialchars($it['name_en']) ?></div>
                                    <small class="text-muted text-truncate d-inline-block" style="max-width: 220px; font-size: 0.775rem;">
                                        <?= htmlspecialchars($it['description_en'] ?? '') ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="d-flex gap-1.5">
                                        <?php if (!empty($it['pronounce_vi_url'])): ?>
                                            <button type="button" class="btn btn-kw-outline btn-sm py-0.5 px-2" onclick="playAudio('<?= htmlspecialchars($it['pronounce_vi_url']) ?>')" title="Nghe phát âm VN" style="font-size: 0.75rem;">
                                                🇻🇳 Loa
                                            </button>
                                        <?php else: ?>
                                            <span class="badge-kw-slate" style="font-size: 0.7rem;">VN (TTS)</span>
                                        <?php endif; ?>

                                        <?php if (!empty($it['pronounce_en_url'])): ?>
                                            <button type="button" class="btn btn-kw-outline btn-sm py-0.5 px-2" onclick="playAudio('<?= htmlspecialchars($it['pronounce_en_url']) ?>')" title="Nghe phát âm EN" style="font-size: 0.75rem;">
                                                🇺🇸 Loa
                                            </button>
                                        <?php else: ?>
                                            <span class="badge-kw-slate" style="font-size: 0.7rem;">US (TTS)</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($it['youtube_video_id'])): ?>
                                        <a href="https://www.youtube.com/watch?v=<?= htmlspecialchars($it['youtube_video_id']) ?>" target="_blank" class="btn btn-kw-outline btn-sm py-0.5 px-2 text-danger" style="font-size: 0.75rem;">
                                            <i class="bi bi-youtube me-1"></i> <?= htmlspecialchars($it['youtube_video_id']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small" style="font-size: 0.75rem;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-kw-slate font-monospace" style="font-size: 0.725rem;">X: <?= (int)$it['map_x'] ?>, Y: <?= (int)$it['map_y'] ?></span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="/items/<?= urlencode($it['id']) ?>/edit" class="btn btn-kw-outline btn-sm py-1 px-2.5" title="Sửa thẻ">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="/items/<?= urlencode($it['id']) ?>/delete" class="btn btn-kw-outline btn-sm py-1 px-2 text-danger" onclick="return confirm('Bạn có chắc muốn xóa thẻ <?= htmlspecialchars($it['name_vi']) ?>?');" title="Xóa thẻ">
                                            <i class="bi bi-trash3"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-custom-footer px-4 py-2.5 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted"><i class="bi bi-card-text text-primary me-1"></i>Tổng cộng <?= count($items) ?> thẻ từ vựng</small>
                <small class="text-muted"><i class="bi bi-check-circle text-success me-1"></i>Đồng bộ trực tiếp</small>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function playAudio(url) {
        if (!url) return;
        const audio = new Audio(url);
        audio.play().catch(e => alert('Không thể phát file âm thanh: ' + e));
    }
</script>
