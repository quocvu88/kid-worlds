<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card card-custom p-4 mb-4">
            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                <div class="d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: var(--kw-primary-light); border: 1px solid var(--kw-primary-border); border-radius: var(--kw-radius-sm); color: var(--kw-primary);">
                    <i class="bi bi-hdd-network fs-4"></i>
                </div>
                <div>
                    <h5 class="fw-semibold mb-0 text-dark">Hướng Dẫn Cấu Hình Domain Máy Chủ Cho App Kids World</h5>
                    <small class="text-muted">Cách kết nối và đồng bộ các gói chủ đề từ Web PHP về điện thoại/máy tính bảng của bé</small>
                </div>
            </div>

            <!-- Standard Specification Banner Callout -->
            <div class="p-3 mb-4 rounded-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3" style="background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%); border: 1px solid #7dd3fc;">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; background: #0284c7; color: #ffffff; border-radius: 10px;">
                        <i class="bi bi-journal-check fs-5"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.95rem;">Tài Liệu Chuẩn Thiết Kế Bộ Học Phần (Multi-sensory Topic Standards)</h6>
                            <span class="badge" style="background: #0284c7; color: #ffffff; font-size: 0.68rem; padding: 2px 7px;">Mới</span>
                        </div>
                        <p class="text-secondary mb-0 small mt-0.5">
                            Quy chuẩn 4 trụ cột cơ bản (Background, Song ngữ, Hướng dẫn phụ huynh, Video YouTube) &amp; 6 tính năng đa giác quan mở rộng (SFX, Ảnh thực tế, Micro-interactions, Phonics, Bento Hints, Mini-game củng cố) kèm JSON Schema.
                        </p>
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <a href="/docs" class="btn btn-sm text-white fw-medium d-inline-flex align-items-center gap-1 shadow-sm px-3 py-2" style="background: #0284c7; border-radius: 8px; font-size: 0.825rem;">
                        <i class="bi bi-book-half"></i> Xem Tiêu Chuẩn <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- Architecture Diagram -->
            <div class="card-p-standard rounded-3 border mb-4" style="background: #fbfdff; border-color: var(--kw-border) !important;">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-diagram-3 text-primary"></i>
                    <span class="fw-semibold text-dark" style="font-size: 0.875rem;">Mô Hình Kết Nối & Đồng Bộ Nội Dung</span>
                </div>
                <div class="row text-center g-3 align-items-center">
                    <div class="col-md-3">
                        <div class="bg-white p-3 rounded-3 shadow-xs border" style="border-color: var(--kw-border);">
                            <i class="bi bi-laptop fs-2 mb-2 d-block" style="color: var(--kw-primary);"></i>
                            <span class="d-block fw-semibold text-dark" style="font-size: 0.85rem;">PHP Admin CMS</span>
                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Tạo gói, upload ảnh, audio phát âm và video</small>
                        </div>
                    </div>
                    <div class="col-md-1 d-none d-md-block text-muted">
                        <i class="bi bi-arrow-left-right fs-4 text-primary opacity-60"></i>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-white p-3 rounded-3 shadow-xs border" style="border-color: var(--kw-primary-border); background: var(--kw-primary-light);">
                            <i class="bi bi-broadcast fs-2 mb-2 d-block text-success"></i>
                            <span class="d-block fw-semibold text-dark" style="font-size: 0.85rem;">REST API Domain</span>
                            <code class="d-block text-primary mt-1" style="font-size: 0.75rem;">/api/packs</code>
                            <code class="d-block text-muted" style="font-size: 0.725rem;">/api/packs/{id}/download</code>
                        </div>
                    </div>
                    <div class="col-md-1 d-none d-md-block text-muted">
                        <i class="bi bi-arrow-left-right fs-4 text-primary opacity-60"></i>
                    </div>
                    <div class="col-md-3">
                        <div class="bg-white p-3 rounded-3 shadow-xs border" style="border-color: var(--kw-border);">
                            <i class="bi bi-phone fs-2 text-warning mb-2 d-block"></i>
                            <span class="d-block fw-semibold text-dark" style="font-size: 0.85rem;">Kids World App</span>
                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Tải gói về SQLite & học offline trên Flutter</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 1: Domains Selection -->
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge-kw-blue fw-semibold">Bước 1</span>
                <span class="fw-semibold text-dark">Chọn Địa Chỉ Domain Phù Hợp Với Môi Trường Của Bạn</span>
            </div>
            
            <div class="row g-3 mb-4">
                <!-- Android Emulator -->
                <div class="col-md-4">
                    <div class="card h-100 p-3 card-custom" style="background: var(--kw-primary-light); border-color: var(--kw-primary-border);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold text-primary" style="font-size: 0.85rem;"><i class="bi bi-android2 me-1"></i> Android Emulator</span>
                            <span class="badge-kw-blue" style="font-size: 0.68rem;">Phổ biến nhất</span>
                        </div>
                        <p class="text-muted mb-2" style="font-size: 0.775rem;">Khi test trên máy ảo Android Studio:</p>
                        <div class="bg-white p-2 rounded border font-monospace text-center fw-medium text-primary mb-2 select-all shadow-xs" style="font-size: 0.85rem; border-color: var(--kw-border) !important;">
                            http://10.0.2.2:8000
                        </div>
                        <small class="text-muted d-block" style="font-size: 0.725rem;">
                            (Android Emulator dùng <code>10.0.2.2</code> để kết nối về máy chủ <code>localhost</code> của PC).
                        </small>
                    </div>
                </div>

                <!-- Wi-Fi Real Device -->
                <div class="col-md-4">
                    <div class="card h-100 p-3 card-custom" style="background: #f0fdf4; border-color: #bbf7d0;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold text-success" style="font-size: 0.85rem;"><i class="bi bi-phone me-1"></i> Thiết Bị Thật (Wi-Fi)</span>
                            <span class="badge-kw-green" style="font-size: 0.68rem;">Mạng nội bộ</span>
                        </div>
                        <p class="text-muted mb-2" style="font-size: 0.775rem;">Khi cài app lên điện thoại thật cùng Wi-Fi nhà:</p>
                        <div class="bg-white p-2 rounded border font-monospace text-center fw-medium text-success mb-2 select-all shadow-xs" style="font-size: 0.85rem; border-color: var(--kw-border) !important;">
                            http://<?= htmlspecialchars($localIp) ?>:8000
                        </div>
                        <small class="text-muted d-block" style="font-size: 0.725rem;">
                            (Điện thoại và máy tính cắm cùng modem Wi-Fi nhà bạn).
                        </small>
                    </div>
                </div>

                <!-- Custom Production Domain -->
                <div class="col-md-4">
                    <div class="card h-100 p-3 card-custom">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold text-dark" style="font-size: 0.85rem;"><i class="bi bi-globe2 me-1 text-primary"></i> Tên Miền Tự Đặt</span>
                            <span class="badge-kw-slate" style="font-size: 0.68rem;">Production</span>
                        </div>
                        <p class="text-muted mb-2" style="font-size: 0.775rem;">Khi triển khai lên Hosting/VPS hoặc Synology NAS:</p>
                        <div class="bg-white p-2 rounded border font-monospace text-center fw-medium text-dark mb-2 select-all shadow-xs" style="font-size: 0.85rem; border-color: var(--kw-border) !important;">
                            https://kids.yourdomain.com
                        </div>
                        <small class="text-muted d-block" style="font-size: 0.725rem;">
                            (Trỏ bản ghi DNS <code>A</code> hoặc <code>CNAME</code> về IP server và cấu hình SSL).
                        </small>
                    </div>
                </div>
            </div>

            <!-- Step 2: How to configure in the App -->
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge-kw-blue fw-semibold">Bước 2</span>
                <span class="fw-semibold text-dark">Nhập Domain Vào App Kids World</span>
            </div>
            
            <div class="list-group mb-4 shadow-xs" style="border-radius: var(--kw-radius); overflow: hidden; border: 1px solid var(--kw-border);">
                <div class="list-group-item p-3 border-0 border-bottom" style="border-color: var(--kw-border) !important;">
                    <div class="d-flex align-items-start gap-3">
                        <span class="d-inline-flex align-items-center justify-content-center fw-semibold text-primary" style="width: 26px; height: 26px; background: var(--kw-primary-light); border: 1px solid var(--kw-primary-border); border-radius: 50%; font-size: 0.75rem;">1</span>
                        <div>
                            <span class="fw-semibold text-dark" style="font-size: 0.875rem;">Mở Khu Vực Phụ Huynh:</span>
                            <p class="mb-0 text-muted" style="font-size: 0.825rem;">Tại màn hình Chọn Bé hoặc Màn hình Chính, nhấn biểu tượng <strong>Cài đặt</strong> (bánh răng) và giải phép toán/nhập mã PIN để mở khóa.</p>
                        </div>
                    </div>
                </div>
                <div class="list-group-item p-3 border-0 border-bottom" style="border-color: var(--kw-border) !important;">
                    <div class="d-flex align-items-start gap-3">
                        <span class="d-inline-flex align-items-center justify-content-center fw-semibold text-primary" style="width: 26px; height: 26px; background: var(--kw-primary-light); border: 1px solid var(--kw-primary-border); border-radius: 50%; font-size: 0.75rem;">2</span>
                        <div>
                            <span class="fw-semibold text-dark" style="font-size: 0.875rem;">Chọn Mục "Máy Chủ Nội Dung":</span>
                            <p class="mb-0 text-muted" style="font-size: 0.825rem;">Nhấn vào ô cấu hình domain, nhập URL máy chủ tương ứng (Ví dụ: <code>http://10.0.2.2:8000</code> hoặc domain của bạn).</p>
                        </div>
                    </div>
                </div>
                <div class="list-group-item p-3 border-0">
                    <div class="d-flex align-items-start gap-3">
                        <span class="d-inline-flex align-items-center justify-content-center fw-semibold text-primary" style="width: 26px; height: 26px; background: var(--kw-primary-light); border: 1px solid var(--kw-primary-border); border-radius: 50%; font-size: 0.75rem;">3</span>
                        <div>
                            <span class="fw-semibold text-dark" style="font-size: 0.875rem;">Kiểm Tra Kết Nối & Tải Gói:</span>
                            <p class="mb-0 text-muted" style="font-size: 0.825rem;">Nhấn <strong>"Kiểm Tra Kết Nối"</strong>. Khi biểu tượng chuyển sang màu xanh lá báo thành công, mở <strong>"Kho Gói Chủ Đề"</strong> và nhấn nút <strong>"Tải về"</strong> các chủ đề bạn muốn cho bé học!</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Testing Endpoints -->
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge-kw-blue fw-semibold">Bước 3</span>
                <span class="fw-semibold text-dark">Kiểm Tra Hoạt Động Của REST API</span>
            </div>
            
            <p class="text-muted" style="font-size: 0.825rem;">Bạn có thể bấm trực tiếp các đường link dưới đây để kiểm tra dữ liệu JSON trả về:</p>

            <div class="table-custom-wrapper">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>API Endpoint</th>
                            <th>Mục Đích</th>
                            <th class="text-end" style="width: 140px;">Thử Nghiệm</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code class="fw-medium text-primary">GET /api/info</code></td>
                            <td>Kiểm tra tình trạng server (Health check & ping)</td>
                            <td class="text-end"><a href="/api/info" target="_blank" class="btn btn-kw-outline btn-sm py-0.5 px-2.5">Mở JSON</a></td>
                        </tr>
                        <tr>
                            <td><code class="fw-medium text-primary">GET /api/packs</code></td>
                            <td>Lấy danh mục tất cả các gói chủ đề có sẵn</td>
                            <td class="text-end"><a href="/api/packs" target="_blank" class="btn btn-kw-outline btn-sm py-0.5 px-2.5">Mở JSON</a></td>
                        </tr>
                        <tr>
                            <td><code class="fw-medium text-primary">GET /api/packs/topic_fruits</code></td>
                            <td>Lấy chi tiết gói Trái Cây kèm danh sách thẻ</td>
                            <td class="text-end"><a href="/api/packs/topic_fruits" target="_blank" class="btn btn-kw-outline btn-sm py-0.5 px-2.5">Mở JSON</a></td>
                        </tr>
                        <tr>
                            <td><code class="fw-medium text-primary">GET /api/packs/topic_space/download</code></td>
                            <td>Gói dữ liệu hoàn chỉnh để App lưu vào SQLite</td>
                            <td class="text-end"><a href="/api/packs/topic_space/download" target="_blank" class="btn btn-kw-outline btn-sm py-0.5 px-2.5">Mở JSON</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
