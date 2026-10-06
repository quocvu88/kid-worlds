<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-1 text-dark">Quản Lý Gói Chủ Đề</h5>
        <p class="text-muted mb-0" style="font-size: 0.875rem;">Tạo mới, chỉnh sửa thông tin và xuất dữ liệu gói chủ đề cho App Kids World</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="/ai-generator" class="btn btn-kw-subtle">
            <i class="bi bi-stars"></i> Tạo Bằng AI
        </a>
        <a href="/packs/create" class="btn btn-kw-primary">
            <i class="bi bi-plus-lg"></i> Tạo Gói Thủ Công
        </a>
    </div>
</div>

<div class="row g-3">
    <?php if (empty($packs)): ?>
        <div class="col-12">
            <div class="card card-custom p-5 text-center text-muted">
                <i class="bi bi-folder2-open fs-1 mb-2 text-muted opacity-50"></i>
                <h6 class="fw-semibold text-dark">Chưa có gói chủ đề nào</h6>
                <p class="small text-muted mb-3">Hãy tạo gói chủ đề đầu tiên để bắt đầu thêm nội dung học tập cho bé.</p>
                <div>
                    <a href="/packs/create" class="btn btn-kw-primary btn-sm">
                        <i class="bi bi-plus-lg"></i> Tạo Gói Ngay
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($packs as $p): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card card-custom h-100 overflow-hidden d-flex flex-column">
                    <!-- Thumbnail preview (Bấm vào để vào Edit với 3 tab) -->
                    <a href="/packs/<?= urlencode($p['id']) ?>/edit" class="d-block text-decoration-none position-relative" style="height: 150px; background: #f8fafc; overflow: hidden; border-bottom: 1px solid var(--kw-border);" title="Bấm để chỉnh sửa gói và quản lý thẻ">
                        <?php if (!empty($p['thumbnail_url'])): ?>
                            <img src="<?= htmlspecialchars($p['thumbnail_url']) ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="Thumbnail">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                <i class="bi bi-image fs-2 opacity-40"></i>
                            </div>
                        <?php endif; ?>
                        <div class="position-absolute top-0 start-0 m-2.5">
                            <span class="badge-kw-blue shadow-xs" style="background: rgba(255,255,255,0.92); backdrop-filter: blur(4px); text-transform: capitalize;">
                                <?= htmlspecialchars($p['category']) ?>
                            </span>
                        </div>
                        <div class="position-absolute top-0 end-0 m-2.5">
                            <span class="badge-kw-slate shadow-xs" style="background: rgba(255,255,255,0.92); backdrop-filter: blur(4px);">v<?= $p['version'] ?></span>
                        </div>
                    </a>

                    <div class="card-body card-p-standard d-flex flex-column flex-grow-1">
                        <div class="mb-2">
                            <a href="/packs/<?= urlencode($p['id']) ?>/edit" class="text-decoration-none" title="Chỉnh sửa gói chủ đề">
                                <h6 class="fw-semibold mb-0 text-dark hover-primary"><?= htmlspecialchars($p['title_vi']) ?></h6>
                            </a>
                            <div class="text-muted font-monospace" style="font-size: 0.775rem;"><?= htmlspecialchars($p['title_en']) ?> · <code><?= htmlspecialchars($p['id']) ?></code></div>
                        </div>

                        <p class="text-muted mb-3 flex-grow-1" style="font-size: 0.825rem; line-height: 1.45;">
                            <?= htmlspecialchars($p['description_vi'] ?: 'Chưa có mô tả cho gói chủ đề này.') ?>
                        </p>

                        <div class="d-flex justify-content-between align-items-center py-2 border-top border-bottom mb-3 text-muted" style="font-size: 0.775rem;">
                            <span><i class="bi bi-person me-1 text-primary"></i> <?= $p['target_age_min'] ?>–<?= $p['target_age_max'] ?> tuổi</span>
                            <span class="fw-medium text-dark"><i class="bi bi-card-text me-1 text-primary"></i> <?= $p['item_count'] ?> thẻ</span>
                            <span class="font-monospace"><i class="bi bi-hdd me-1"></i> <?= $p['size_mb'] ?> MB</span>
                        </div>

                        <div class="d-flex gap-2 mt-auto">
                            <a href="/packs/<?= urlencode($p['id']) ?>/edit" class="btn btn-kw-primary btn-sm flex-fill justify-content-center py-1.5">
                                <i class="bi bi-pencil-square me-1"></i> Chỉnh Sửa
                            </a>
                            <a href="/api/packs/<?= urlencode($p['id']) ?>/download" target="_blank" class="btn btn-kw-outline btn-sm justify-content-center py-1.5 text-primary" title="Xem JSON Payload">
                                <i class="bi bi-download"></i>
                            </a>
                            <a href="/packs/<?= urlencode($p['id']) ?>/delete" class="btn btn-kw-outline btn-sm py-1.5 text-danger px-2.5" onclick="return confirm('Bạn có chắc chắn muốn xóa gói này?');" title="Xóa gói">
                                <i class="bi bi-trash3"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
