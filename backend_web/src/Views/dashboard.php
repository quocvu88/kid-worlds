<!-- 4 Metric Stat Cards -->
<div class="row g-3 mb-4">
    <!-- Stat 1: Total Packs -->
    <div class="col-md-3 col-sm-6">
        <div class="card card-custom card-p-standard h-100">
            <div class="card-stat-header">
                <div>
                    <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.725rem; letter-spacing: 0.05em;">Tổng Gói Chủ Đề</span>
                    <h3 class="fw-bold mb-0 mt-1" style="color: #0284c7; font-size: 1.75rem; letter-spacing: -0.02em;"><?= $stats['total_packs'] ?></h3>
                </div>
                <div class="card-stat-icon" style="background: var(--kw-primary-light); border-color: var(--kw-primary-border); color: var(--kw-primary);">
                    <i class="bi bi-collection-play fs-5"></i>
                </div>
            </div>
            <div class="card-stat-footer">
                <i class="bi bi-check2-circle text-success me-1.5"></i> Sẵn sàng cho App tải về
            </div>
        </div>
    </div>

    <!-- Stat 2: Total Items -->
    <div class="col-md-3 col-sm-6">
        <div class="card card-custom card-p-standard h-100">
            <div class="card-stat-header">
                <div>
                    <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.725rem; letter-spacing: 0.05em;">Thẻ Flashcard Từ Vựng</span>
                    <h3 class="fw-bold mb-0 mt-1 text-dark" style="font-size: 1.75rem; letter-spacing: -0.02em;"><?= $stats['total_items'] ?></h3>
                </div>
                <div class="card-stat-icon" style="background: #ecfdf5; border-color: #a7f3d0; color: #059669;">
                    <i class="bi bi-card-text fs-5"></i>
                </div>
            </div>
            <div class="card-stat-footer">
                <i class="bi bi-translate text-primary me-1.5"></i> Song ngữ Anh - Việt
            </div>
        </div>
    </div>

    <!-- Stat 3: Total Size -->
    <div class="col-md-3 col-sm-6">
        <div class="card card-custom card-p-standard h-100">
            <div class="card-stat-header">
                <div>
                    <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.725rem; letter-spacing: 0.05em;">Dung Lượng Toàn Kho</span>
                    <h3 class="fw-bold mb-0 mt-1" style="color: #0369a1; font-size: 1.75rem; letter-spacing: -0.02em;"><?= $stats['total_size_mb'] ?> <span class="fw-normal text-muted fs-6">MB</span></h3>
                </div>
                <div class="card-stat-icon" style="background: #f0fdf4; border-color: #bbf7d0; color: #16a34a;">
                    <i class="bi bi-hdd-network fs-5"></i>
                </div>
            </div>
            <div class="card-stat-footer">
                <i class="bi bi-lightning-charge text-warning me-1.5"></i> Tối ưu nén tải nhanh
            </div>
        </div>
    </div>

    <!-- Stat 4: Server Status -->
    <div class="col-md-3 col-sm-6">
        <div class="card card-custom card-p-standard h-100">
            <div class="card-stat-header">
                <div>
                    <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.725rem; letter-spacing: 0.05em;">Trạng Thái REST API</span>
                    <div class="mt-2">
                        <span class="server-status-pill">
                            <span class="pulse-dot"></span> Đang Hoạt Động
                        </span>
                    </div>
                </div>
                <div class="card-stat-icon" style="background: var(--kw-primary-light); border-color: var(--kw-primary-border); color: var(--kw-primary);">
                    <i class="bi bi-broadcast fs-5"></i>
                </div>
            </div>
            <div class="card-stat-footer">
                <a href="/api/info" target="_blank" class="text-decoration-none text-muted d-inline-flex align-items-center gap-1">
                    <i class="bi bi-box-arrow-up-right text-primary" style="font-size: 0.7rem;"></i> Kiểm tra /api/info
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Option A: Sync Station & Device Connectivity Banner -->
<div class="banner-light-blue mb-4">
    <div class="row align-items-center gy-3">
        <div class="col-lg-8">
            <div class="d-flex align-items-center gap-2 mb-1.5">
                <span class="d-inline-flex align-items-center justify-content-center bg-white text-primary border rounded-circle shadow-xs" style="width: 32px; height: 32px; border-color: var(--kw-primary-border) !important;">
                    <i class="bi bi-router fs-5"></i>
                </span>
                <h6 class="fw-semibold text-dark mb-0" style="font-size: 0.95rem;">Cổng Kết Nối & Đồng Bộ Cho Thiết Bị Của Bé</h6>
                <span class="badge-kw-green ms-1" style="font-size: 0.7rem;">Sẵn Sàng</span>
            </div>
            <p class="text-muted mb-2.5" style="font-size: 0.825rem;">
                Sao chép địa chỉ IP máy chủ để nhập vào mục <strong>Cài đặt phụ huynh &gt; Máy chủ nội dung</strong> trên App Kids World:
            </p>
            <div class="d-flex flex-wrap gap-2">
                <!-- Chip Android Emulator -->
                <div class="bg-white px-3 py-1.5 d-flex align-items-center gap-2" style="border-radius: var(--kw-radius-sm); border: 1px solid var(--kw-border);">
                    <div>
                        <small class="text-muted d-block" style="font-size: 0.68rem;">Máy ảo Android Studio:</small>
                        <code class="fw-medium text-primary" id="ipEmulator" style="font-size: 0.85rem;">http://10.0.2.2:8000</code>
                    </div>
                    <button type="button" class="btn btn-kw-outline btn-sm py-0.5 px-2 ms-1" onclick="copyDomain('http://10.0.2.2:8000')" title="Sao chép URL">
                        <i class="bi bi-copy" style="font-size: 0.75rem;"></i>
                    </button>
                </div>

                <!-- Chip Real Device Wi-Fi -->
                <div class="bg-white px-3 py-1.5 d-flex align-items-center gap-2" style="border-radius: var(--kw-radius-sm); border: 1px solid var(--kw-border);">
                    <div>
                        <small class="text-muted d-block" style="font-size: 0.68rem;">Thiết bị thật (Cùng Wi-Fi):</small>
                        <code class="fw-medium text-success" id="ipWifi" style="font-size: 0.85rem;">http://<?= htmlspecialchars($localIp ?? '192.168.2.6') ?>:8000</code>
                    </div>
                    <button type="button" class="btn btn-kw-outline btn-sm py-0.5 px-2 ms-1" onclick="copyDomain('http://<?= htmlspecialchars($localIp ?? '192.168.2.6') ?>:8000')" title="Sao chép URL">
                        <i class="bi bi-copy" style="font-size: 0.75rem;"></i>
                    </button>
                </div>

                <!-- Chip REST API Endpoint -->
                <div class="bg-white px-3 py-1.5 d-flex align-items-center gap-2" style="border-radius: var(--kw-radius-sm); border: 1px solid var(--kw-border);">
                    <div>
                        <small class="text-muted d-block" style="font-size: 0.68rem;">API Endpoint JSON:</small>
                        <code class="fw-medium" style="color: #0284c7; font-size: 0.85rem;">/api/packs</code>
                    </div>
                    <a href="/api/packs" target="_blank" class="btn btn-kw-outline btn-sm py-0.5 px-2 ms-1" title="Kiểm tra JSON">
                        <i class="bi bi-box-arrow-up-right" style="font-size: 0.75rem;"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 text-lg-end">
            <div class="d-inline-flex flex-column flex-sm-row gap-2">
                <a href="/guide" class="btn btn-kw-outline">
                    <i class="bi bi-book text-primary"></i> Hướng Dẫn
                </a>
                <a href="/ai-generator" class="btn btn-kw-subtle">
                    <i class="bi bi-stars"></i> Tạo Bằng AI
                </a>
                <a href="/packs/create" class="btn btn-kw-primary">
                    <i class="bi bi-plus-lg"></i> Thêm Gói Mới
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Card: Topic Packs Table & Quick Action Panel -->
<div class="card card-custom overflow-hidden mb-4">
    <!-- Header with Filters & Search (Chuẩn padding px-4 py-3 theo Quy chuẩn Card) -->
    <div class="card-custom-header py-3 px-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 w-100">
            <div>
                <h6 class="fw-semibold mb-0 text-dark" style="font-size: 0.95rem;">
                    Danh Sách Gói Nội Dung Học Tập
                    <span class="badge-kw-blue ms-1.5"><?= count($packs) ?> gói</span>
                </h6>
                <small class="text-muted">Quản lý thẻ flashcard, thông số lứa tuổi và phiên bản dữ liệu tải về</small>
            </div>

            <!-- Search and Filter Bar -->
            <div class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm" style="width: 220px;">
                    <span class="input-group-text bg-white border-end-0 text-muted" style="border-color: var(--kw-border); border-top-left-radius: var(--kw-radius-sm); border-bottom-left-radius: var(--kw-radius-sm);">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="packSearchInput" class="form-control border-start-0 ps-0" placeholder="Tìm kiếm gói..." style="border-top-right-radius: var(--kw-radius-sm); border-bottom-right-radius: var(--kw-radius-sm);" onkeyup="filterPacks()">
                </div>
                <select id="categoryFilter" class="form-select form-select-sm" style="width: 140px; border-radius: var(--kw-radius-sm);" onchange="filterPacks()">
                    <option value="">Tất cả danh mục</option>
                    <option value="animals">Động vật</option>
                    <option value="fruits">Trái cây</option>
                    <option value="vehicles">Xe cộ</option>
                    <option value="space">Vũ trụ</option>
                    <option value="nature">Thiên nhiên</option>
                    <option value="general">Tổng hợp</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table Body -->
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom" id="packsTable">
                <thead>
                    <tr>
                        <th style="width: 60px;">Ảnh</th>
                        <th>Tên Gói Chủ Đề</th>
                        <th>Danh Mục</th>
                        <th>Độ Tuổi</th>
                        <th>Số Thẻ</th>
                        <th>Dung Lượng</th>
                        <th>Phiên Bản</th>
                        <th>Trạng Thái</th>
                        <th class="text-end" style="width: 190px;">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($packs)): ?>
                        <tr id="emptyRow">
                            <td colspan="9" class="text-center py-5 text-muted">
                                <div class="py-3">
                                    <i class="bi bi-folder2-open fs-2 text-muted opacity-40 d-block mb-2"></i>
                                    <span class="d-block mb-2 font-medium">Chưa có gói chủ đề nào trong hệ thống.</span>
                                    <a href="/packs/create" class="btn btn-kw-primary btn-sm">
                                        <i class="bi bi-plus-lg"></i> Tạo Gói Đầu Tiên
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($packs as $p): ?>
                            <tr class="pack-row" data-category="<?= htmlspecialchars($p['category']) ?>" data-search="<?= strtolower(htmlspecialchars($p['title_vi'] . ' ' . $p['title_en'] . ' ' . $p['id'])) ?>">
                                <td>
                                    <?php if (!empty($p['thumbnail_url'])): ?>
                                        <img src="<?= htmlspecialchars($p['thumbnail_url']) ?>" class="topic-thumb-sm" alt="Thumbnail">
                                    <?php else: ?>
                                        <div class="topic-thumb-sm bg-light d-flex align-items-center justify-content-center text-muted">
                                            <i class="bi bi-image fs-6 opacity-40"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($p['title_vi']) ?></div>
                                    <small class="text-muted font-monospace" style="font-size: 0.75rem;"><?= htmlspecialchars($p['title_en']) ?> · <code><?= htmlspecialchars($p['id']) ?></code></small>
                                </td>
                                <td>
                                    <span class="badge-kw-blue"><?= htmlspecialchars($p['category']) ?></span>
                                </td>
                                <td>
                                    <span class="badge-kw-slate"><?= $p['target_age_min'] ?>–<?= $p['target_age_max'] ?> tuổi</span>
                                </td>
                                <td>
                                    <span class="fw-medium text-dark"><i class="bi bi-card-text text-primary me-1"></i><?= $p['item_count'] ?> thẻ</span>
                                </td>
                                <td>
                                    <span class="text-muted font-monospace" style="font-size: 0.775rem;"><?= $p['size_mb'] ?> MB</span>
                                </td>
                                <td>
                                    <span class="badge-kw-slate font-monospace" style="font-size: 0.725rem;">v<?= $p['version'] ?></span>
                                </td>
                                <td>
                                    <?php if (!isset($p['is_active']) || $p['is_active'] == 1): ?>
                                        <span class="badge-kw-green">Hoạt động</span>
                                    <?php else: ?>
                                        <span class="badge-kw-slate text-muted">Tạm ẩn</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="/packs/<?= urlencode($p['id']) ?>/items" class="btn btn-kw-subtle btn-sm py-1 px-2.5" title="Quản lý thẻ học">
                                            <i class="bi bi-card-checklist"></i> Thẻ
                                        </a>
                                        <a href="/packs/<?= urlencode($p['id']) ?>/edit" class="btn btn-kw-outline btn-sm py-1 px-2" title="Chỉnh sửa gói">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="/api/packs/<?= urlencode($p['id']) ?>/download" target="_blank" class="btn btn-kw-outline btn-sm py-1 px-2 text-primary" title="Tải JSON Payload">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <a href="/packs/<?= urlencode($p['id']) ?>/delete" class="btn btn-kw-outline btn-sm py-1 px-2 text-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa gói <?= htmlspecialchars($p['title_vi']) ?> kèm toàn bộ thẻ bên trong?');" title="Xóa gói">
                                            <i class="bi bi-trash3"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Card Footer Quy chuẩn -->
    <div class="card-custom-footer px-4 py-2.5 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="text-muted" style="font-size: 0.8rem;">
            <i class="bi bi-collection text-primary me-1"></i>
            Hiển thị <span id="visiblePacksCount" class="fw-semibold text-dark"><?= count($packs) ?></span> / <?= count($packs) ?> gói chủ đề
        </div>
        <div class="text-muted d-flex align-items-center gap-3" style="font-size: 0.775rem;">
            <span><i class="bi bi-shield-check text-success me-1"></i>Đồng bộ tự động</span>
            <span><i class="bi bi-arrow-repeat text-primary me-1"></i>Phiên bản thời gian thực</span>
        </div>
    </div>
</div>

<!-- Interactive Toast Feedback for Copy -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
    <div id="copyToast" class="toast align-items-center text-white border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" style="background: #0284c7; border-radius: var(--kw-radius-sm);">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2 py-2 px-3" style="font-size: 0.85rem;">
                <i class="bi bi-check2-circle fs-5"></i>
                <span id="toastMsg">Đã sao chép địa chỉ domain vào bộ nhớ tạm!</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
    function copyDomain(text) {
        navigator.clipboard.writeText(text).then(function() {
            const toastEl = document.getElementById('copyToast');
            document.getElementById('toastMsg').innerText = 'Đã sao chép: ' + text;
            const toast = new bootstrap.Toast(toastEl, { delay: 2500 });
            toast.show();
        }).catch(function(err) {
            prompt('Sao chép địa chỉ này:', text);
        });
    }

    function filterPacks() {
        const query = (document.getElementById('packSearchInput').value || '').trim().toLowerCase();
        const cat = (document.getElementById('categoryFilter').value || '').trim().toLowerCase();
        const rows = document.querySelectorAll('.pack-row');
        let visibleCount = 0;

        rows.forEach(function(row) {
            const rowCat = (row.getAttribute('data-category') || '').toLowerCase();
            const rowSearch = (row.getAttribute('data-search') || '').toLowerCase();

            const matchCat = !cat || rowCat === cat;
            const matchQuery = !query || rowSearch.includes(query);

            if (matchCat && matchQuery) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const countEl = document.getElementById('visiblePacksCount');
        if (countEl) {
            countEl.innerText = visibleCount;
        }
    }
</script>
