<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1" style="font-size: 0.8rem;">
            <li class="breadcrumb-item"><a href="/" class="text-decoration-none text-muted">Bảng Điều Khiển</a></li>
            <li class="breadcrumb-item active text-primary" aria-current="page">AI Content Studio & Prompt Generator</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <span class="d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: var(--kw-primary-light); border: 1px solid var(--kw-primary-border); border-radius: var(--kw-radius-sm); color: var(--kw-primary);">
                    <i class="bi bi-stars fs-5"></i>
                </span>
                Studio Thiết Kế Nội Dung & Tạo Prompt AI
            </h5>
            <p class="text-muted mb-0" style="font-size: 0.875rem;">
                Chuẩn hóa cấu trúc bộ học phần đa giác quan (Background, Phonics, SFX, Real Photo, Bento Parent Guide: Fun Fact, Câu hỏi đố bé, Vận động) để làm prompt input cho AI và nhập nhanh JSON vào hệ thống.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/packs" class="btn btn-kw-outline">
                <i class="bi bi-collection"></i> Danh Sách Gói
            </a>
            <button type="button" class="btn btn-kw-primary" onclick="switchTab('promptTab')">
                <i class="bi bi-pencil-square"></i> Tạo Prompt AI
            </button>
        </div>
    </div>
</div>

<!-- Navigation Tabs -->
<ul class="nav nav-pills mb-4 gap-2" id="studioTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active d-flex align-items-center gap-2" id="prompt-tab-btn" data-bs-toggle="pill" data-bs-target="#promptTab" type="button" role="tab">
            <i class="bi bi-card-text"></i>
            <span>1. Trình Tạo Prompt AI (Master Prompt)</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link d-flex align-items-center gap-2" id="import-tab-btn" data-bs-toggle="pill" data-bs-target="#importTab" type="button" role="tab">
            <i class="bi bi-box-arrow-in-down"></i>
            <span>2. Nhập Dữ Liệu JSON Từ AI</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link d-flex align-items-center gap-2" id="builtin-tab-btn" data-bs-toggle="pill" data-bs-target="#builtinTab" type="button" role="tab">
            <i class="bi bi-lightning-charge"></i>
            <span>3. Khởi Tạo Tự Động Trực Tiếp (Built-in)</span>
        </button>
    </li>
</ul>

<div class="tab-content" id="studioTabsContent">
    <!-- TAB 1: PROMPT GENERATOR & MASTER SPECIFICATION -->
    <div class="tab-pane fade show active" id="promptTab" role="tabpanel">
        <div class="card card-custom p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom flex-wrap gap-2">
                <div>
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-input-cursor-text text-primary"></i>
                        Cấu Hình Input Cho Prompt Tạo Nội Dung
                    </h6>
                    <small class="text-muted">Tùy chỉnh thông số chủ đề, hệ thống sẽ tự động ghép vào chuẩn kiến trúc đa giác quan hoàn chỉnh</small>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-kw-outline btn-sm" onclick="copyMasterPrompt()">
                        <i class="bi bi-clipboard" id="copyIcon"></i> <span id="copyBtnText">Sao Chép Toàn Bộ Prompt</span>
                    </button>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold text-dark">Chủ Đề Hoặc Ý Tưởng Cần Tạo</label>
                    <input type="text" id="builderTopicInput" class="form-control" value="Thế giới động vật hoang dã kỳ thú trên thảo nguyên" placeholder="Ví dụ: Các loại xe chuyên dụng cứu hộ, Thế giới khủng long..." oninput="updatePromptDisplay()">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-semibold text-dark">Số Lượng Thẻ Học</label>
                    <select id="builderCountSelect" class="form-select" onchange="updatePromptDisplay()">
                        <option value="4">4 thẻ (Gọn nhẹ)</option>
                        <option value="6" selected>6 thẻ (Chuẩn khuyến nghị)</option>
                        <option value="8">8 thẻ (Đầy đủ)</option>
                        <option value="10">10 thẻ (Phong phú)</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-semibold text-dark">Độ Tuổi Mục Tiêu</label>
                    <select id="builderAgeSelect" class="form-select" onchange="updatePromptDisplay()">
                        <option value="2-5">2 – 5 tuổi (Mầm non / Làm quen)</option>
                        <option value="3-8" selected>3 – 8 tuổi (Mẫu giáo & Lớp 1)</option>
                        <option value="6-10">6 – 10 tuổi (Tiểu học / Mở rộng)</option>
                    </select>
                </div>
            </div>

            <!-- Quick Suggestions -->
            <div class="mb-3">
                <small class="text-muted d-block mb-1.5" style="font-size: 0.75rem; font-weight: 500;">Gợi ý chủ đề nhanh:</small>
                <div class="d-flex flex-wrap gap-1.5">
                    <button type="button" class="btn btn-kw-outline btn-sm py-1 px-2.5" onclick="setBuilderTopic('Thế giới động vật hoang dã dũng mãnh và đáng yêu trên thảo nguyên', 6, '3-8')">
                        🦁 Động Vật Thảo Nguyên
                    </button>
                    <button type="button" class="btn btn-kw-outline btn-sm py-1 px-2.5" onclick="setBuilderTopic('Các phương tiện giao thông cứu hộ khẩn cấp: xe cứu hỏa, cứu thương, cảnh sát, trực thăng', 6, '3-6')">
                        🚒 Xe Cứu Hộ Khẩn Cấp
                    </button>
                    <button type="button" class="btn btn-kw-outline btn-sm py-1 px-2.5" onclick="setBuilderTopic('Thế giới khủng long tiền sử kỳ vĩ: T-Rex, Triceratops, Brachiosaurus', 6, '4-8')">
                        🦖 Khủng Long Tiền Sử
                    </button>
                    <button type="button" class="btn btn-kw-outline btn-sm py-1 px-2.5" onclick="setBuilderTopic('Khám phá hệ mặt trời, các phi hành gia và tàu vũ trụ', 6, '5-10')">
                        🚀 Vũ Trụ & Phi Hành Gia
                    </button>
                    <button type="button" class="btn btn-kw-outline btn-sm py-1 px-2.5" onclick="setBuilderTopic('Các sinh vật đại dương kỳ thú: cá voi xanh, cá heo thông minh, rùa biển', 6, '3-8')">
                        🌊 Đại Dương Bao La
                    </button>
                    <button type="button" class="btn btn-kw-outline btn-sm py-1 px-2.5" onclick="setBuilderTopic('Các loại trái cây nhiệt đới thơm ngon và giàu vitamin', 6, '2-5')">
                        🍎 Trái Cây Nhiệt Đới
                    </button>
                </div>
            </div>

            <!-- Master Prompt Display Box -->
            <div class="position-relative">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label fw-semibold text-dark mb-0">
                        Nội Dung Prompt Hoàn Chỉnh (Sẵn sàng copy & dán vào AI):
                    </label>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge-kw-blue font-monospace" style="font-size: 0.7rem;">Chuẩn Kids World Spec v2</span>
                    </div>
                </div>
                <textarea id="masterPromptBox" class="form-control font-monospace mb-3" rows="13" style="font-size: 0.8rem; background: #0f172a; color: #38bdf8; border-color: #334155; line-height: 1.5;"></textarea>
                
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top">
                    <div class="d-flex gap-2 align-items-center">
                        <span class="text-muted small">Mở nhanh công cụ AI:</span>
                        <a href="https://chatgpt.com" target="_blank" class="btn btn-kw-outline btn-sm py-1 px-2.5 text-decoration-none">
                            <i class="bi bi-box-arrow-up-right me-1"></i> ChatGPT
                        </a>
                        <a href="https://gemini.google.com" target="_blank" class="btn btn-kw-outline btn-sm py-1 px-2.5 text-decoration-none">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Gemini
                        </a>
                        <a href="https://claude.ai" target="_blank" class="btn btn-kw-outline btn-sm py-1 px-2.5 text-decoration-none">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Claude
                        </a>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-kw-outline btn-sm py-1.5 px-3" onclick="copyMasterPrompt()">
                            <i class="bi bi-clipboard"></i> Sao Chép Prompt
                        </button>
                        <button type="button" class="btn btn-kw-primary btn-sm py-1.5 px-3" onclick="goToImportTab()">
                            <i class="bi bi-arrow-right-circle me-1"></i> Đã Có Kết Quả → Đi Tới Tab Nhập JSON
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Architecture Breakdown Cards -->
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card card-custom card-p-standard h-100">
                    <div class="d-flex align-items-center gap-2 mb-2 text-primary fw-semibold" style="font-size: 0.9rem;">
                        <i class="bi bi-palette-fill fs-5"></i>
                        <span>1. Background & Theme Color</span>
                    </div>
                    <p class="text-muted small mb-0">
                        Quy định màu chủ đạo và ảnh nền theo chủ đề (Thảo nguyên, Kẹo ngọt, Vũ trụ, Tiệc bóng bay...) tạo không gian đắm chìm cho trẻ.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom card-p-standard h-100">
                    <div class="d-flex align-items-center gap-2 mb-2 text-success fw-semibold" style="font-size: 0.9rem;">
                        <i class="bi bi-soundwave fs-5"></i>
                        <span>2. Song Ngữ, Phonics & SFX</span>
                    </div>
                    <p class="text-muted small mb-0">
                        Tích hợp đánh vần ngữ âm Phonics <code>/l-aɪ-ə-n/</code>, âm thanh thực tế SFX (tiếng gầm, còi xe) và loa phát âm song ngữ Anh - Việt.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom card-p-standard h-100">
                    <div class="d-flex align-items-center gap-2 mb-2 text-warning fw-semibold" style="font-size: 0.9rem;">
                        <i class="bi bi-card-checklist fs-5"></i>
                        <span>3. Bento Parent Guide 3 Khối</span>
                    </div>
                    <p class="text-muted small mb-0">
                        Cẩm nang mặt sau thẻ hỗ trợ cha mẹ: 💡 <b>Sự thật kỳ thú</b>, ❓ <b>Cha mẹ đố bé</b> kích thích tư duy, và 🤸 <b>Cùng bé vận động</b> thể chất.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: IMPORT JSON FROM AI -->
    <div class="tab-pane fade" id="importTab" role="tabpanel">
        <div class="card card-custom p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom flex-wrap gap-2">
                <div>
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-box-arrow-in-down text-primary"></i>
                        Dán Kết Quả JSON Từ AI (ChatGPT / Gemini / Claude)
                    </h6>
                    <small class="text-muted">Nhập chuỗi JSON do AI sinh ra, hệ thống sẽ phân tích cú pháp và hiển thị đầy đủ thẻ học đa giác quan</small>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-kw-outline btn-sm" onclick="loadSampleJson()">
                        <i class="bi bi-file-earmark-code"></i> Nạp Dữ Liệu Mẫu
                    </button>
                    <button type="button" class="btn btn-kw-outline btn-sm" onclick="clearJsonInput()">
                        <i class="bi bi-x-circle"></i> Xóa Trắng
                    </button>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-dark">Chuỗi JSON Kết Quả</label>
                <textarea id="jsonImportInput" class="form-control font-monospace" rows="9" placeholder='Dán mã JSON {"pack": {...}, "items": [...]} vào đây...' style="font-size: 0.825rem; resize: vertical;"></textarea>
                <div id="jsonErrorAlert" class="alert alert-danger py-2 px-3 mt-2 d-none" role="alert" style="font-size: 0.825rem;"></div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top">
                <span class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i> Trình phân tích tự động chuẩn hóa dữ liệu, URL ảnh, tọa độ và gán fallback thông minh.
                </span>
                <button type="button" class="btn btn-kw-primary px-4 py-2" onclick="handleImportJson()">
                    <i class="bi bi-magic me-1"></i> Phân Tích & Xem Trước Dữ Liệu
                </button>
            </div>
        </div>
    </div>

    <!-- TAB 3: BUILT-IN AUTOMATIC GENERATOR -->
    <div class="tab-pane fade" id="builtinTab" role="tabpanel">
        <div class="card card-custom p-4 mb-4">
            <form id="aiPromptForm" onsubmit="handleGenerate(event)">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark d-flex justify-content-between align-items-center">
                        <span>Mô Tả Chủ Đề Cần Tạo Tự Động <span class="text-danger">*</span></span>
                        <span class="text-muted fw-normal" style="font-size: 0.75rem;">Hỗ trợ tiếng Việt tự nhiên</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted" style="border-color: var(--kw-border);">
                            <i class="bi bi-chat-left-quote"></i>
                        </span>
                        <textarea id="promptInput" class="form-control" rows="2" placeholder="Ví dụ: Bộ thẻ học về các loài khủng long tiền sử cho bé 4-8 tuổi..." required style="resize: none;">Thế giới khủng long tiền sử kỳ thú cho bé 4-8 tuổi</textarea>
                    </div>
                </div>

                <!-- Configuration Options Row -->
                <div class="row g-3 align-items-end pt-2 border-top">
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">Số Lượng Thẻ Học</label>
                        <select id="countSelect" class="form-select">
                            <option value="4">4 thẻ (Gọn nhẹ)</option>
                            <option value="6" selected>6 thẻ (Chuẩn khuyến nghị)</option>
                            <option value="8">8 thẻ (Đầy đủ)</option>
                            <option value="10">10 thẻ (Phong phú)</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">Độ Tuổi Mục Tiêu</label>
                        <select id="targetAgeSelect" class="form-select">
                            <option value="2-5">2 – 5 tuổi (Mầm non / Làm quen)</option>
                            <option value="3-8" selected>3 – 8 tuổi (Khám phá / Mẫu giáo & Lớp 1)</option>
                            <option value="6-10">6 – 10 tuổi (Tiểu học / Nâng cao)</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label d-flex justify-content-between">
                            <span>Gemini API Key</span>
                            <small class="text-muted fw-normal" style="font-size: 0.7rem;">(Tùy chọn)</small>
                        </label>
                        <input type="password" id="apiKeyInput" class="form-control" placeholder="Để trống = Dùng Built-in AI">
                    </div>
                    <div class="col-md-3 col-sm-6 text-md-end">
                        <button type="submit" id="btnGenerate" class="btn btn-kw-primary w-100 py-2 justify-content-center">
                            <i class="bi bi-stars"></i> Khởi Tạo Trực Tiếp
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Loading State Card -->
<div id="loadingBox" class="card card-custom p-5 text-center mb-4 d-none">
    <div class="spinner-border text-primary mb-3" style="width: 2.8rem; height: 2.8rem; border-width: 0.25em;" role="status"></div>
    <h6 class="fw-semibold text-dark mb-1" id="loadingTitle">AI đang khởi tạo bộ nội dung đa giác quan...</h6>
    <p class="text-muted small mb-0" id="loadingSubtitle">Đang thiết lập thông tin song ngữ, Phonics, SFX thực tế, ảnh đời thực và Bento Parent Guide.</p>
</div>

<!-- Result Preview Container -->
<div id="resultContainer" class="d-none">
    <!-- Pack Overview Card -->
    <div class="card card-custom p-4 mb-4" style="background: #fcfdfe; border-color: var(--kw-primary-border);">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge-kw-blue fw-semibold">Thông Tin Gói Chủ Đề (Pack)</span>
                <span class="text-muted" style="font-size: 0.8rem;">(Xem trước và điều chỉnh trước khi lưu vào CSDL)</span>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-kw-subtle btn-sm" onclick="openPackPromptSheet()">
                    <i class="bi bi-stars me-1 text-primary"></i> Bộ Prompt Tạo Ảnh Cả Gói
                </button>
                <button type="button" class="btn btn-kw-outline btn-sm" onclick="resetForm()">
                    <i class="bi bi-arrow-repeat"></i> Soạn Lại
                </button>
                <button type="button" class="btn btn-kw-primary btn-sm px-3" onclick="saveGeneratedPack()">
                    <i class="bi bi-cloud-arrow-up-fill"></i> Lưu Gói & Thẻ Vào Hệ Thống
                </button>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-3 text-center">
                <div class="border rounded-3 overflow-hidden bg-light position-relative shadow-xs mb-2" style="height: 140px; border-color: var(--kw-border) !important; cursor: pointer;" onclick="browsePackImage()" title="Nhấp để đổi ảnh thumbnail">
                    <img id="previewPackThumb" src="" style="width: 100%; height: 100%; object-fit: cover;" alt="Pack Thumbnail">
                    <div class="position-absolute bottom-0 start-0 end-0 py-1 bg-dark bg-opacity-60 text-white text-center" style="font-size: 0.725rem;">
                        <i class="bi bi-camera me-1"></i> Bấm để duyệt & đổi ảnh
                    </div>
                </div>
                <div class="input-group input-group-sm">
                    <input type="text" id="packThumbInput" class="form-control text-truncate" placeholder="URL ảnh Thumbnail" onchange="updatePackThumb(this.value)">
                    <button type="button" class="btn btn-kw-subtle" onclick="browsePackImage()" title="Duyệt kho ảnh">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-9">
                <div class="row g-2.5">
                    <div class="col-md-4">
                        <label class="form-label">Mã Gói (ID)</label>
                        <input type="text" id="packIdInput" class="form-control form-control-sm font-monospace" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tên Tiếng Việt</label>
                        <input type="text" id="packTitleViInput" class="form-control form-control-sm fw-semibold" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tên Tiếng Anh</label>
                        <input type="text" id="packTitleEnInput" class="form-control form-control-sm" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Danh Mục</label>
                        <select id="packCategorySelect" class="form-select form-select-sm">
                            <option value="animals">Động vật (animals)</option>
                            <option value="dinosaurs">Khủng long (dinosaurs)</option>
                            <option value="space">Vũ trụ (space)</option>
                            <option value="vehicles">Xe cộ (vehicles)</option>
                            <option value="fruits">Trái cây (fruits)</option>
                            <option value="nature">Thiên nhiên (nature)</option>
                            <option value="general">Tổng hợp (general)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Màu Chủ Đạo</label>
                        <div class="input-group input-group-sm">
                            <input type="color" id="packThemeColorPicker" class="form-control form-control-color" style="width: 40px; padding: 2px;" value="#10b981" onchange="document.getElementById('packThemeColorInput').value = this.value">
                            <input type="text" id="packThemeColorInput" class="form-control font-monospace" value="#10b981" onchange="document.getElementById('packThemeColorPicker').value = this.value">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Lứa Tuổi</label>
                        <div class="input-group input-group-sm">
                            <input type="number" id="packAgeMinInput" class="form-control" min="1" max="15">
                            <span class="input-group-text bg-white text-muted">đến</span>
                            <input type="number" id="packAgeMaxInput" class="form-control" min="1" max="15">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Dung Lượng Dự Kiến</label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.1" id="packSizeInput" class="form-control">
                            <span class="input-group-text bg-white text-muted">MB</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">URL Hình Nền (Background URL)</label>
                        <input type="text" id="packBgUrlInput" class="form-control form-control-sm" placeholder="https://... hoặc để trống để dùng nền chuẩn">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mô Tả Tiếng Việt Cho Bé</label>
                        <input type="text" id="packDescViInput" class="form-control form-control-sm">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Grid Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h6 class="fw-semibold mb-0 text-dark">
                Danh Sách Thẻ Từ Vựng Đa Giác Quan (<span id="selectedCountText">0</span>/<span id="totalCountText">0</span> thẻ đã chọn)
            </h6>
            <small class="text-muted">Đầy đủ Phonics, SFX thực tế, hình ảnh minh họa & đời thực, cùng Bento cẩm nang tương tác phụ huynh</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-kw-outline btn-sm py-1" onclick="toggleSelectAll(true)">
                <i class="bi bi-check-all"></i> Chọn Tất Cả
            </button>
            <button type="button" class="btn btn-kw-outline btn-sm py-1" onclick="toggleSelectAll(false)">
                <i class="bi bi-dash"></i> Bỏ Chọn
            </button>
            <button type="button" class="btn btn-kw-primary btn-sm py-1 px-3" onclick="saveGeneratedPack()">
                <i class="bi bi-check-circle me-1"></i> Lưu Vào Hệ Thống
            </button>
        </div>
    </div>

    <!-- Items Grid Container -->
    <div class="row g-3" id="itemsGridContainer">
        <!-- Rendered dynamically by JavaScript -->
    </div>
</div>

<script>
    let generatedData = null;
    let itemImageModes = {}; // tracks whether showing 'illustration' or 'real' per item index

    // Switch between tabs cleanly
    function switchTab(tabId) {
        const triggerEl = document.querySelector(`[data-bs-target="#${tabId}"]`);
        if (triggerEl) {
            bootstrap.Tab.getOrCreateInstance(triggerEl).show();
        }
    }

    function goToImportTab() {
        switchTab('importTab');
        document.getElementById('jsonImportInput').focus();
    }

    // Set topic in builder
    function setBuilderTopic(topic, count, age) {
        document.getElementById('builderTopicInput').value = topic;
        document.getElementById('builderCountSelect').value = count;
        document.getElementById('builderAgeSelect').value = age;
        updatePromptDisplay();
    }

    // Fetch and update prompt text dynamically
    function updatePromptDisplay() {
        const topic = document.getElementById('builderTopicInput').value.trim();
        const count = document.getElementById('builderCountSelect').value;
        const age = document.getElementById('builderAgeSelect').value;

        fetch(`/api/ai/prompt-template?topic=${encodeURIComponent(topic)}&count=${count}&target_age=${encodeURIComponent(age)}`)
            .then(res => res.json())
            .then(data => {
                if (data.prompt) {
                    document.getElementById('masterPromptBox').value = data.prompt;
                }
            })
            .catch(() => {
                // Fallback manual template if offline
                document.getElementById('masterPromptBox').value = generateLocalPrompt(topic, count, age);
            });
    }

    function generateLocalPrompt(topic, count, age) {
        return `Bạn là chuyên gia thiết kế chương trình giáo dục mầm non & tiểu học cho ứng dụng trẻ em "Kids World".
Hãy tạo 01 bộ học phần Flashcard đa giác quan hoàn chỉnh theo chủ đề: "${topic}" dành cho bé lứa tuổi ${age}, gồm chính xác ${count} thẻ học tập.

Yêu cầu xuất DUY NHẤT mã JSON theo cấu trúc chuẩn:
{
  "pack": {
    "id": "topic_slug",
    "title_vi": "Tên tiếng Việt",
    "title_en": "English Name",
    "category": "animals|vehicles|dinosaurs|space|fruits|nature|general",
    "theme_color": "#10b981",
    "background_url": "https://images.unsplash.com/...",
    "target_age_min": 3,
    "target_age_max": 8,
    "target_gender": "all",
    "thumbnail_url": "https://images.unsplash.com/...",
    "description_vi": "Mô tả cho bé",
    "description_en": "Description in English",
    "version": 1,
    "size_mb": 1.8,
    "is_active": 1
  },
  "items": [
    {
      "id": "item_slug",
      "name_vi": "Tên thẻ tiếng Việt",
      "name_en": "English name",
      "phonics_en": "/p-h-o-n-i-c-s/",
      "description_vi": "Mô tả ngắn gọn 1-2 câu",
      "description_en": "Short description",
      "images": ["https://images.unsplash.com/minh_hoa"],
      "real_image_url": "https://images.unsplash.com/doi_thuc",
      "sfx_sound": "Tiếng kêu thực tế (ví dụ: Roar! Roar!)",
      "youtube_video_id": "VbZ07n242-g",
      "fun_fact_vi": "💡 Sự thật kỳ thú cho bé",
      "fun_fact_en": "💡 Fun fact",
      "prompt_question_vi": "❓ Câu hỏi đố bé",
      "prompt_question_en": "❓ Prompt question",
      "action_hint_vi": "🤸 Hành động mô phỏng vui nhộn",
      "map_x": 200,
      "map_y": 250,
      "sort_order": 1,
      "is_selected": true
    }
  ]
}`;
    }

    // Copy Master Prompt to clipboard
    function copyMasterPrompt() {
        const text = document.getElementById('masterPromptBox').value;
        if (!text) return;

        navigator.clipboard.writeText(text).then(() => {
            const btnText = document.getElementById('copyBtnText');
            const icon = document.getElementById('copyIcon');
            const origText = btnText.innerText;

            btnText.innerText = 'Đã Sao Chép!';
            icon.className = 'bi bi-check-lg text-success';

            setTimeout(() => {
                btnText.innerText = origText;
                icon.className = 'bi bi-clipboard';
            }, 2500);
        }).catch(err => {
            alert('Không thể sao chép tự động: ' + err);
        });
    }

    // Load Sample JSON for quick testing
    function loadSampleJson() {
        const sample = {
            pack: {
                id: "topic_savanna_safari",
                title_vi: "Khám Phá Muôn Loài Thảo Nguyên",
                title_en: "Amazing Savanna Safari",
                category: "animals",
                theme_color: "#10b981",
                background_url: "https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=1200&q=80",
                target_age_min: 3,
                target_age_max: 8,
                target_gender: "all",
                thumbnail_url: "https://images.unsplash.com/photo-1534188753412-3e26d0d618d6?auto=format&fit=crop&w=800&q=80",
                description_vi: "Cùng bé gặp gỡ những người bạn động vật dũng mãnh và đáng yêu trên thảo nguyên xanh bao la.",
                description_en: "Meet adorable and magnificent wild animal friends on the vast green savanna.",
                version: 1,
                size_mb: 1.8,
                is_active: 1
            },
            items: [
                {
                    id: "item_lion",
                    name_vi: "Sư Tử Dũng Mãnh",
                    name_en: "Brave Lion",
                    phonics_en: "/ˈl-aɪ-ə-n/",
                    description_vi: "Sư tử có chiếc bờm vàng óng ả, được mệnh danh là chúa tể sơn lâm của thảo nguyên.",
                    description_en: "The lion with its golden mane is known as the majestic king of the savanna.",
                    images: ["https://images.unsplash.com/photo-1534188753412-3e26d0d618d6?auto=format&fit=crop&w=800&q=80"],
                    real_image_url: "https://images.unsplash.com/photo-1547721064-da6cfb341d50?auto=format&fit=crop&w=800&q=80",
                    sfx_sound: "Gầm gừ... Roarrrr!",
                    youtube_video_id: "VbZ07n242-g",
                    fun_fact_vi: "Tiếng gầm uy lực của sư tử có thể vang xa tới 8 cây số xuyên qua màn đêm!",
                    fun_fact_en: "A lion's majestic roar can be heard up to 5 miles away!",
                    prompt_question_vi: "Bé có biết chiếc bờm dày màu vàng chỉ có ở bạn sư tử đực hay sư tử cái không?",
                    prompt_question_en: "Does only the male lion grow such a magnificent fluffy mane?",
                    action_hint_vi: "Bé xòe rộng 5 ngón tay làm móng vuốt, bước đi oai vệ và gầm vang 'Roarrr!' dũng mãnh nhé!",
                    map_x: 200,
                    map_y: 250,
                    sort_order: 1,
                    is_selected: true
                },
                {
                    id: "item_giraffe",
                    name_vi: "Hươu Cao Cổ",
                    name_en: "Tall Giraffe",
                    phonics_en: "/dʒ-ɪ-ˈr-ɑː-f/",
                    description_vi: "Hươu cao cổ có chiếc cổ cao kỷ lục và bộ lông đốm hoa văn rất xinh đẹp.",
                    description_en: "The giraffe has an astonishingly tall neck and graceful patterned coat.",
                    images: ["https://images.unsplash.com/photo-1547721064-da6cfb341d50?auto=format&fit=crop&w=800&q=80"],
                    real_image_url: "https://images.unsplash.com/photo-1534188753412-3e26d0d618d6?auto=format&fit=crop&w=800&q=80",
                    sfx_sound: "Nhai nhóp nhép... Munch-crunch!",
                    youtube_video_id: "VbZ07n242-g",
                    fun_fact_vi: "Chiếc lưỡi của bạn hươu dài tới 50cm và có màu tím đen để không bị cháy nắng khi ăn lá trên cao!",
                    fun_fact_en: "Giraffes have 20-inch-long bluish tongues protected from sun exposure!",
                    prompt_question_vi: "Nhờ có chiếc cổ siêu cao, bạn hươu có thể với tới những chiếc lá non ở đâu?",
                    prompt_question_en: "Where can tall giraffes reach to munch on sweet fresh leaves?",
                    action_hint_vi: "Bé kiễng chân thật cao, duỗi 2 tay lên trần nhà như chiếc cổ dài vươn hái lá non nào!",
                    map_x: 450,
                    map_y: 200,
                    sort_order: 2,
                    is_selected: true
                },
                {
                    id: "item_elephant",
                    name_vi: "Voi Con Thông Thái",
                    name_en: "Gentle Elephant",
                    phonics_en: "/ˈɛ-l-ɪ-f-ə-n-t/",
                    description_vi: "Bạn voi có đôi tai to như chiếc quạt và chiếc vòi khéo léo để uống nước và chào bé.",
                    description_en: "The wise elephant has large ears like fans and a strong versatile trunk to drink water.",
                    images: ["https://images.unsplash.com/photo-1557050543-4d5f4e07ef46?auto=format&fit=crop&w=800&q=80"],
                    real_image_url: "https://images.unsplash.com/photo-1526095179574-86e545346ae6?auto=format&fit=crop&w=800&q=80",
                    sfx_sound: "Tu húuuu... Trumpet-toot!",
                    youtube_video_id: "VbZ07n242-g",
                    fun_fact_vi: "Chiếc vòi voi có hơn 40.000 bó cơ khác nhau nên vừa có thể nâng cây gỗ lớn vừa nhặt được hạt lạc tí hon!",
                    fun_fact_en: "An elephant's trunk has over 40,000 muscles and can pick up a tiny peanut!",
                    prompt_question_vi: "Bạn voi dùng đôi tai to để làm gì mỗi khi trời nóng nực bé có biết không?",
                    prompt_question_en: "Why does the elephant flap its big ears on a hot sunny day?",
                    action_hint_vi: "Bé lấy một cánh tay làm chiếc vòi dài vung vẩy trước mũi và kêu 'Pa-ooom!'.",
                    map_x: 320,
                    map_y: 480,
                    sort_order: 3,
                    is_selected: true
                }
            ]
        };

        document.getElementById('jsonImportInput').value = JSON.stringify(sample, null, 2);
        document.getElementById('jsonErrorAlert').classList.add('d-none');
    }

    function clearJsonInput() {
        document.getElementById('jsonImportInput').value = '';
        document.getElementById('jsonErrorAlert').classList.add('d-none');
    }

    // Handle JSON Import
    function handleImportJson() {
        const raw = document.getElementById('jsonImportInput').value.trim();
        const errAlert = document.getElementById('jsonErrorAlert');
        errAlert.classList.add('d-none');

        if (!raw) {
            errAlert.innerText = 'Vui lòng dán chuỗi JSON vào ô nhập liệu.';
            errAlert.classList.remove('d-none');
            return;
        }

        let parsed = null;
        try {
            // Strip code fences if user accidentally copied ```json ... ```
            let clean = raw;
            if (clean.startsWith('```')) {
                clean = clean.replace(/^```[a-zA-Z]*\n?/, '').replace(/```\s*$/, '').trim();
            }
            parsed = JSON.parse(clean);
        } catch (e) {
            errAlert.innerText = 'Cú pháp JSON không hợp lệ: ' + e.message;
            errAlert.classList.remove('d-none');
            return;
        }

        if (!parsed || !parsed.pack || !Array.isArray(parsed.items)) {
            errAlert.innerText = 'JSON thiếu cấu trúc "pack" hoặc mảng "items". Vui lòng kiểm tra lại prompt.';
            errAlert.classList.remove('d-none');
            return;
        }

        generatedData = parsed;
        renderGeneratedResult(generatedData);
    }

    // Direct Built-in / Gemini generation
    function handleGenerate(event) {
        event.preventDefault();
        const prompt = document.getElementById('promptInput').value.trim();
        const count = parseInt(document.getElementById('countSelect').value) || 6;
        const targetAge = document.getElementById('targetAgeSelect').value;
        const apiKey = document.getElementById('apiKeyInput').value.trim();

        if (!prompt) return;

        document.getElementById('loadingBox').classList.remove('d-none');
        document.getElementById('resultContainer').classList.add('d-none');
        document.getElementById('btnGenerate').disabled = true;

        fetch('/api/ai/generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                prompt: prompt,
                count: count,
                target_age: targetAge,
                api_key: apiKey
            })
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('loadingBox').classList.add('d-none');
            document.getElementById('btnGenerate').disabled = false;

            if (data.status === 'success' && data.data) {
                generatedData = data.data;
                renderGeneratedResult(generatedData);
            } else {
                alert('Có lỗi xảy ra: ' + (data.message || 'Không thể sinh nội dung'));
            }
        })
        .catch(err => {
            document.getElementById('loadingBox').classList.add('d-none');
            document.getElementById('btnGenerate').disabled = false;
            alert('Lỗi kết nối tới máy chủ AI: ' + err);
        });
    }

    // Render Preview
    function renderGeneratedResult(data) {
        const pack = data.pack;
        const items = data.items || [];

        // Fill pack fields
        document.getElementById('packIdInput').value = pack.id || '';
        document.getElementById('packTitleViInput').value = pack.title_vi || '';
        document.getElementById('packTitleEnInput').value = pack.title_en || '';
        document.getElementById('packCategorySelect').value = pack.category || 'general';
        document.getElementById('packThemeColorPicker').value = pack.theme_color || '#10b981';
        document.getElementById('packThemeColorInput').value = pack.theme_color || '#10b981';
        document.getElementById('packBgUrlInput').value = pack.background_url || '';
        document.getElementById('packAgeMinInput').value = pack.target_age_min || 3;
        document.getElementById('packAgeMaxInput').value = pack.target_age_max || 8;
        document.getElementById('packSizeInput').value = pack.size_mb || 1.8;
        document.getElementById('packDescViInput').value = pack.description_vi || '';
        document.getElementById('previewPackThumb').src = pack.thumbnail_url || 'https://images.unsplash.com/photo-1534188753412-3e26d0d618d6?auto=format&fit=crop&w=800&q=80';
        document.getElementById('packThumbInput').value = pack.thumbnail_url || '';

        // Render Items Grid
        const grid = document.getElementById('itemsGridContainer');
        grid.innerHTML = '';
        itemImageModes = {};

        items.forEach((it, idx) => {
            const illustImg = (it.images && it.images[0]) || it.image || '';
            const realImg = it.real_image_url || it.real_image || illustImg;
            itemImageModes[idx] = 'illustration';

            const cardCol = document.createElement('div');
            cardCol.className = 'col-lg-6 col-12';
            cardCol.id = 'itemCard_' + idx;

            cardCol.innerHTML = `
                <div class="card card-custom h-100 overflow-hidden d-flex flex-column" style="border-color: var(--kw-border);">
                    <div class="row g-0 flex-grow-1">
                        <!-- Left Side: Image Preview & Mode Switcher -->
                        <div class="col-sm-5 d-flex flex-column bg-light border-end" style="border-color: var(--kw-border) !important;">
                            <div style="height: 180px; position: relative; overflow: hidden; cursor: pointer;" onclick="browseItemImage(${idx})" title="Nhấp để duyệt & chọn ảnh mới">
                                <img src="${escapeHtml(illustImg)}" id="itemImg_${idx}" style="width: 100%; height: 100%; object-fit: cover;" alt="${escapeHtml(it.name_vi)}">
                                
                                <div class="position-absolute top-0 start-0 m-2">
                                    <span class="badge bg-white text-dark shadow-xs" style="font-size: 0.75rem;">#${idx + 1}</span>
                                </div>
                                <div class="position-absolute top-0 end-0 m-2" onclick="event.stopPropagation()">
                                    <div class="form-check m-0 bg-white px-2 py-0.5 rounded shadow-xs">
                                        <input class="form-check-input item-select-checkbox" type="checkbox" id="checkItem_${idx}" ${it.is_selected !== false ? 'checked' : ''} onchange="updateSelectedCount()">
                                        <label class="form-check-label small fw-medium" for="checkItem_${idx}">Chọn</label>
                                    </div>
                                </div>
                                <div class="position-absolute bottom-0 start-0 end-0 py-1 bg-dark bg-opacity-60 text-white text-center" style="font-size: 0.7rem;">
                                    <i class="bi bi-images me-1"></i> Đổi ảnh minh họa
                                </div>
                            </div>

                            <!-- Toggle Illustration vs Real Photo Mode -->
                            <div class="p-2 border-top bg-white d-flex gap-1" style="border-color: var(--kw-border) !important;">
                                <button type="button" class="btn btn-sm btn-kw-primary flex-fill py-0.5 active" id="btnModeIllust_${idx}" onclick="toggleCardImageMode(${idx}, 'illustration')" style="font-size: 0.725rem;">
                                    🎨 Minh họa
                                </button>
                                <button type="button" class="btn btn-sm btn-kw-outline flex-fill py-0.5" id="btnModeReal_${idx}" onclick="toggleCardImageMode(${idx}, 'real')" style="font-size: 0.725rem;">
                                    📷 Đời thực
                                </button>
                            </div>

                            <!-- Image URL Inputs -->
                            <div class="p-2 bg-light border-top flex-grow-1" style="font-size: 0.75rem; border-color: var(--kw-border) !important;">
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-0.5">
                                        <label class="form-label mb-0 fw-medium" style="font-size: 0.7rem;">Ảnh 3D Minh Họa:</label>
                                        <button type="button" class="btn btn-kw-outline btn-sm py-0 px-1.5" onclick="browseItemImage(${idx}, 'illustration')" style="font-size: 0.68rem;" title="Upload ảnh hoặc sinh prompt 3D Pixar">
                                            <i class="bi bi-stars text-primary"></i> Đổi / Prompt AI
                                        </button>
                                    </div>
                                    <input type="text" class="form-control form-control-sm text-truncate" style="font-size: 0.725rem;" id="imgInput_${idx}" value="${escapeHtml(illustImg)}" placeholder="URL ảnh 3D hoặc file tải lên">
                                </div>
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-0.5">
                                        <label class="form-label mb-0 fw-medium" style="font-size: 0.7rem;">Ảnh Đời Thực (Real):</label>
                                        <button type="button" class="btn btn-kw-outline btn-sm py-0 px-1.5" onclick="browseItemImage(${idx}, 'real')" style="font-size: 0.68rem;" title="Upload ảnh thật hoặc sinh prompt National Geographic">
                                            <i class="bi bi-camera text-info"></i> Đổi / Prompt Thật
                                        </button>
                                    </div>
                                    <input type="text" class="form-control form-control-sm text-truncate" style="font-size: 0.725rem;" id="realImgInput_${idx}" value="${escapeHtml(realImg)}" placeholder="URL ảnh đời thực">
                                </div>
                            </div>
                        </div>

                        <!-- Right Side: Multi-Sensory & Bento Parent Guide -->
                        <div class="col-sm-7 p-3 d-flex flex-column">
                            <!-- Bilingual Names -->
                            <div class="row g-1.5 mb-2">
                                <div class="col-6">
                                    <label class="form-label mb-0" style="font-size: 0.7rem;">Tên Tiếng Việt:</label>
                                    <input type="text" class="form-control form-control-sm fw-semibold text-dark" id="nameVi_${idx}" value="${escapeHtml(it.name_vi || '')}" placeholder="Tên tiếng Việt">
                                </div>
                                <div class="col-6">
                                    <label class="form-label mb-0" style="font-size: 0.7rem;">English Name:</label>
                                    <input type="text" class="form-control form-control-sm" style="color: #0284c7;" id="nameEn_${idx}" value="${escapeHtml(it.name_en || '')}" placeholder="English name">
                                </div>
                            </div>

                            <!-- Phonics & SFX Row -->
                            <div class="row g-1.5 mb-2">
                                <div class="col-6">
                                    <label class="form-label mb-0 text-primary" style="font-size: 0.7rem;">Phonics / Ngữ âm:</label>
                                    <input type="text" class="form-control form-control-sm font-monospace" style="color: #8b5cf6;" id="phonics_${idx}" value="${escapeHtml(it.phonics_en || '')}" placeholder="/p-h-o-n-i-c-s/">
                                </div>
                                <div class="col-6">
                                    <label class="form-label mb-0 text-warning" style="font-size: 0.7rem;">SFX / Tiếng kêu:</label>
                                    <input type="text" class="form-control form-control-sm" style="color: #d97706;" id="sfx_${idx}" value="${escapeHtml(it.sfx_sound || '')}" placeholder="Gừ gừ... Roarrrr!">
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="mb-2">
                                <label class="form-label mb-0" style="font-size: 0.7rem;">Mô Tả Cho Bé:</label>
                                <textarea class="form-control form-control-sm text-muted" rows="2" style="font-size: 0.775rem; resize: none;" id="descVi_${idx}" placeholder="Mô tả ngắn gọn, dễ thương cho bé">${escapeHtml(it.description_vi || '')}</textarea>
                            </div>

                            <!-- Bento Parent Guide Box -->
                            <div class="p-2.5 rounded-3 bg-light border mb-2 flex-grow-1" style="border-color: #e2e8f0 !important; font-size: 0.75rem;">
                                <div class="fw-semibold text-secondary mb-1.5 d-flex align-items-center gap-1" style="font-size: 0.725rem;">
                                    <i class="bi bi-person-heart text-danger"></i> Bento Card Cẩm Nang Phụ Huynh:
                                </div>
                                <div class="mb-1">
                                    <span class="badge bg-warning bg-opacity-20 text-dark me-1" style="font-size: 0.65rem;">💡 Sự thật kỳ thú</span>
                                    <input type="text" class="form-control form-control-sm mt-0.5" style="font-size: 0.725rem;" id="funFactVi_${idx}" value="${escapeHtml(it.fun_fact_vi || '')}" placeholder="Kiến thức khoa học kỳ thú gây tò mò cho bé">
                                </div>
                                <div class="mb-1">
                                    <span class="badge bg-info bg-opacity-20 text-dark me-1" style="font-size: 0.65rem;">❓ Cha mẹ đố bé</span>
                                    <input type="text" class="form-control form-control-sm mt-0.5" style="font-size: 0.725rem;" id="promptVi_${idx}" value="${escapeHtml(it.prompt_question_vi || '')}" placeholder="Câu hỏi gợi mở kích thích quan sát">
                                </div>
                                <div>
                                    <span class="badge bg-success bg-opacity-20 text-dark me-1" style="font-size: 0.65rem;">🤸 Cùng bé vận động</span>
                                    <input type="text" class="form-control form-control-sm mt-0.5" style="font-size: 0.725rem;" id="actionVi_${idx}" value="${escapeHtml(it.action_hint_vi || '')}" placeholder="Trò chơi thể chất hoặc mô phỏng tương tác">
                                </div>
                            </div>

                            <!-- Actions Row -->
                            <div class="d-flex justify-content-between align-items-center pt-1 border-top" style="border-color: var(--kw-border) !important;">
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-kw-outline btn-sm py-0.5 px-2" style="font-size: 0.7rem;" onclick="playAudio('${escapeHtml(it.pronounce_vi_url || '')}', 'vi', document.getElementById('nameVi_${idx}').value)">
                                        🇻🇳 Loa VN
                                    </button>
                                    <button type="button" class="btn btn-kw-outline btn-sm py-0.5 px-2" style="font-size: 0.7rem;" onclick="playAudio('${escapeHtml(it.pronounce_en_url || '')}', 'en', document.getElementById('nameEn_${idx}').value)">
                                        🇺🇸 Loa EN
                                    </button>
                                </div>
                                <div class="d-flex align-items-center gap-1.5">
                                    <span class="text-muted" style="font-size: 0.7rem;">YouTube:</span>
                                    <input type="text" class="form-control form-control-sm font-monospace text-center py-0" style="width: 100px; font-size: 0.7rem;" id="youtube_${idx}" value="${escapeHtml(it.youtube_video_id || 'VbZ07n242-g')}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            grid.appendChild(cardCol);
        });

        document.getElementById('resultContainer').classList.remove('d-none');
        updateSelectedCount();

        // Scroll smoothly to preview
        document.getElementById('resultContainer').scrollIntoView({ behavior: 'smooth' });
    }

    function toggleCardImageMode(idx, mode) {
        itemImageModes[idx] = mode;
        const btnIllust = document.getElementById('btnModeIllust_' + idx);
        const btnReal = document.getElementById('btnModeReal_' + idx);
        const img = document.getElementById('itemImg_' + idx);

        if (mode === 'real') {
            btnReal.classList.add('active', 'btn-kw-primary');
            btnReal.classList.remove('btn-kw-outline');
            btnIllust.classList.remove('active', 'btn-kw-primary');
            btnIllust.classList.add('btn-kw-outline');

            const realUrl = document.getElementById('realImgInput_' + idx).value;
            if (realUrl) img.src = realUrl;
        } else {
            btnIllust.classList.add('active', 'btn-kw-primary');
            btnIllust.classList.remove('btn-kw-outline');
            btnReal.classList.remove('active', 'btn-kw-primary');
            btnReal.classList.add('btn-kw-outline');

            const illustUrl = document.getElementById('imgInput_' + idx).value;
            if (illustUrl) img.src = illustUrl;
        }
    }

    function updatePackThumb(val) {
        if (val) {
            document.getElementById('previewPackThumb').src = val;
        }
    }

    function browsePackImage() {
        const query = (document.getElementById('packTitleEnInput')?.value || '').trim() 
                   || (document.getElementById('packTitleViInput')?.value || '').trim() 
                   || (document.getElementById('builderTopicInput')?.value || '').trim();
        openImageBrowser({
            inputId: 'packThumbInput',
            previewId: 'previewPackThumb',
            query: query
        });
    }

    function browseItemImage(idx, mode = 'illustration') {
        const nameVi = (document.getElementById('nameVi_' + idx)?.value || '').trim();
        const nameEn = (document.getElementById('nameEn_' + idx)?.value || '').trim();
        const query = nameVi || nameEn || 'Hà Mã';

        const inputId = (mode === 'real') ? ('realImgInput_' + idx) : ('imgInput_' + idx);
        const preferredStyle = (mode === 'real') ? 'real_photo' : 'pixar_3d';

        openImageBrowser({
            inputId: inputId,
            previewId: 'itemImg_' + idx,
            query: query,
            defaultTab: 'ai_prompt',
            preferredStyle: preferredStyle,
            callback: function(url) {
                if (mode === 'real') {
                    const btnReal = document.getElementById('btnModeReal_' + idx);
                    if (btnReal) btnReal.click();
                } else {
                    const btnIllust = document.getElementById('btnModeIllust_' + idx);
                    if (btnIllust) btnIllust.click();
                }
            }
        });
    }

    function toggleSelectAll(checked) {
        document.querySelectorAll('.item-select-checkbox').forEach(cb => {
            cb.checked = checked;
        });
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const checkboxes = document.querySelectorAll('.item-select-checkbox');
        let selected = 0;
        checkboxes.forEach(cb => {
            if (cb.checked) selected++;
        });
        document.getElementById('selectedCountText').innerText = selected;
        document.getElementById('totalCountText').innerText = checkboxes.length;
    }

    function playAudio(url, lang, text) {
        let soundUrl = url;
        if (!soundUrl && text) {
            soundUrl = `https://translate.google.com/translate_tts?ie=UTF-8&tl=${lang}&client=tw-ob&q=${encodeURIComponent(text)}`;
        }
        if (soundUrl) {
            const audio = new Audio(soundUrl);
            audio.play().catch(e => {
                alert('Không thể phát âm thanh: ' + e);
            });
        }
    }

    function saveGeneratedPack() {
        if (!generatedData) return;

        const pack = {
            id: document.getElementById('packIdInput').value.trim(),
            title_vi: document.getElementById('packTitleViInput').value.trim(),
            title_en: document.getElementById('packTitleEnInput').value.trim(),
            category: document.getElementById('packCategorySelect').value,
            theme_color: document.getElementById('packThemeColorInput').value.trim(),
            background_url: document.getElementById('packBgUrlInput').value.trim(),
            target_age_min: parseInt(document.getElementById('packAgeMinInput').value) || 3,
            target_age_max: parseInt(document.getElementById('packAgeMaxInput').value) || 8,
            target_gender: 'all',
            thumbnail_url: document.getElementById('packThumbInput').value.trim(),
            description_vi: document.getElementById('packDescViInput').value.trim(),
            description_en: generatedData.pack.description_en || '',
            version: 1,
            size_mb: parseFloat(document.getElementById('packSizeInput').value) || 1.8,
            is_active: 1
        };

        if (!pack.id || !pack.title_vi) {
            alert('Vui lòng điền đầy đủ Mã gói và Tên tiếng Việt.');
            return;
        }

        const items = [];
        (generatedData.items || []).forEach((orig, idx) => {
            const isSelected = document.getElementById('checkItem_' + idx).checked;
            const nameVi = document.getElementById('nameVi_' + idx).value.trim();
            const nameEn = document.getElementById('nameEn_' + idx).value.trim();
            const descVi = document.getElementById('descVi_' + idx).value.trim();
            const img = document.getElementById('imgInput_' + idx).value.trim();
            const realImg = document.getElementById('realImgInput_' + idx).value.trim();
            const phonics = document.getElementById('phonics_' + idx).value.trim();
            const sfx = document.getElementById('sfx_' + idx).value.trim();
            const funFact = document.getElementById('funFactVi_' + idx).value.trim();
            const promptQ = document.getElementById('promptVi_' + idx).value.trim();
            const actionH = document.getElementById('actionVi_' + idx).value.trim();
            const youtubeId = document.getElementById('youtube_' + idx).value.trim();

            items.push({
                id: orig.id,
                name_vi: nameVi || orig.name_vi,
                name_en: nameEn || orig.name_en,
                description_vi: descVi || orig.description_vi,
                description_en: orig.description_en || '',
                images: [img || ''],
                real_image_url: realImg || '',
                phonics_en: phonics || '',
                sfx_sound: sfx || '',
                fun_fact_vi: funFact || '',
                fun_fact_en: orig.fun_fact_en || '',
                prompt_question_vi: promptQ || '',
                prompt_question_en: orig.prompt_question_en || '',
                action_hint_vi: actionH || '',
                pronounce_vi_url: orig.pronounce_vi_url || '',
                pronounce_en_url: orig.pronounce_en_url || '',
                youtube_video_id: youtubeId || orig.youtube_video_id || 'VbZ07n242-g',
                map_x: orig.map_x || 100,
                map_y: orig.map_y || 100,
                sort_order: idx + 1,
                is_selected: isSelected
            });
        });

        if (items.filter(i => i.is_selected).length === 0) {
            alert('Vui lòng tích chọn ít nhất 1 thẻ từ vựng để lưu vào gói.');
            return;
        }

        if (!confirm(`Xác nhận lưu gói "${pack.title_vi}" với ${items.filter(i => i.is_selected).length} thẻ học tập vào hệ thống CMS?`)) {
            return;
        }

        // Send to backend
        fetch('/api/ai/save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pack: pack, items: items })
        })
        .then(res => res.json())
        .then(resData => {
            if (resData.status === 'success') {
                window.location.href = resData.redirect || '/packs?created=1';
            } else {
                alert('Lỗi lưu dữ liệu: ' + (resData.message || 'Không thể lưu'));
            }
        })
        .catch(err => {
            alert('Lỗi gửi dữ liệu lưu: ' + err);
        });
    }

    function resetForm() {
        document.getElementById('resultContainer').classList.add('d-none');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ==========================================
    // BATCH PACK PROMPT SHEET LOGIC
    // ==========================================
    let packPromptsCache = [];

    function openPackPromptSheet() {
        if (!generatedData || !generatedData.items || generatedData.items.length === 0) {
            alert('Vui lòng tạo hoặc tải dữ liệu gói trước.');
            return;
        }

        const items = generatedData.items;
        const tbody = document.getElementById('promptSheetBody');
        tbody.innerHTML = '';
        packPromptsCache = [];

        document.getElementById('promptSheetSummary').innerText = `Tổng cộng ${items.length} thẻ từ vựng trong gói: "${generatedData.pack.title_vi}"`;

        items.forEach((it, idx) => {
            const nameVi = document.getElementById('nameVi_' + idx)?.value || it.name_vi || 'Sự vật';
            const nameEn = document.getElementById('nameEn_' + idx)?.value || it.name_en || nameVi;

            // Generate high-standard prompt for this item
            const prompt3D = `Adorable cute baby ${nameEn}, friendly smiling face, big expressive sparkling eyes, smooth 3D Pixar Disney animation style, soft warm studio lighting, vibrant cheerful pastel colors, clean solid light pastel background, 3D claymation render, Octane render, 8k, volumetric lighting, children educational book illustration, centered, full body shot, high quality --ar 1:1 --v 6.0`;
            const promptReal = `Authentic high resolution wildlife photograph of a ${nameEn} in natural environment, National Geographic documentary style, sharp crisp focus on natural skin texture and gentle eyes, shot on 85mm lens f/2.8, photorealistic, beautiful natural daylight, detailed wildlife photography, clean uncluttered composition --ar 1:1 --v 6.0`;

            packPromptsCache.push({
                idx: idx,
                name_vi: nameVi,
                name_en: nameEn,
                prompt_3d: prompt3D,
                prompt_real: promptReal
            });

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="font-monospace text-muted small">${idx + 1}</td>
                <td>
                    <div class="fw-semibold text-dark">${escapeHtml(nameVi)}</div>
                    <small class="text-primary font-monospace">${escapeHtml(nameEn)}</small>
                </td>
                <td>
                    <div class="p-2 rounded bg-light border font-monospace text-dark mb-1.5" style="font-size: 0.7rem; line-height: 1.4; max-height: 75px; overflow-y: auto;">${escapeHtml(prompt3D)}</div>
                    <button type="button" class="btn btn-kw-outline btn-sm py-0.5 px-2" style="font-size: 0.725rem;" onclick="copySingleSheetPrompt(this, '${escapeHtml(prompt3D)}')">
                        <i class="bi bi-copy me-1"></i> Sao chép 3D
                    </button>
                </td>
                <td>
                    <div class="p-2 rounded bg-light border font-monospace text-dark mb-1.5" style="font-size: 0.7rem; line-height: 1.4; max-height: 75px; overflow-y: auto;">${escapeHtml(promptReal)}</div>
                    <button type="button" class="btn btn-kw-outline btn-sm py-0.5 px-2" style="font-size: 0.725rem;" onclick="copySingleSheetPrompt(this, '${escapeHtml(promptReal)}')">
                        <i class="bi bi-copy me-1"></i> Sao chép Thật
                    </button>
                </td>
                <td class="text-end">
                    <button type="button" class="btn btn-kw-primary btn-sm py-1 px-2 text-nowrap" style="font-size: 0.75rem;" onclick="uploadFromSheet(${idx})">
                        <i class="bi bi-cloud-arrow-up"></i> Upload
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        const modalEl = document.getElementById('packPromptSheetModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    function copySingleSheetPrompt(btn, text) {
        navigator.clipboard.writeText(text).then(() => {
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check2 text-success"></i> Đã chép!';
            setTimeout(() => { btn.innerHTML = original; }, 1500);
        }).catch(() => {
            prompt('Sao chép prompt:', text);
        });
    }

    function copyAllPackPrompts(type) {
        if (!packPromptsCache || packPromptsCache.length === 0) return;

        let fullText = '';
        packPromptsCache.forEach(p => {
            fullText += `### Thẻ: ${p.name_vi} (${p.name_en})\n`;
            if (type === '3d') {
                fullText += `${p.prompt_3d}\n\n`;
            } else {
                fullText += `${p.prompt_real}\n\n`;
            }
        });

        navigator.clipboard.writeText(fullText).then(() => {
            alert(`Đã sao chép toàn bộ ${packPromptsCache.length} prompt ${type === '3d' ? 'Hoạt Họa 3D Pixar' : 'Ảnh Chụp Thật'} vào bộ nhớ tạm! Bạn có thể dán hàng loạt vào Discord/ChatGPT.`);
        }).catch(() => {
            prompt('Sao chép toàn bộ prompt:', fullText);
        });
    }

    function uploadFromSheet(idx) {
        const modalEl = document.getElementById('packPromptSheetModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        browseItemImage(idx, 'illustration');
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Initialize prompt display on load
    document.addEventListener('DOMContentLoaded', () => {
        updatePromptDisplay();
    });
</script>

<!-- Modal: Pack Image Prompt Sheet -->
<div class="modal fade" id="packPromptSheetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: var(--kw-radius); border: 1px solid var(--kw-border); box-shadow: 0 16px 40px rgba(0,0,0,0.12);">
            <div class="modal-header py-3 px-4 bg-white border-bottom">
                <div class="d-flex align-items-center gap-2.5">
                    <span class="d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: #fef3c7; border: 1px solid #fde68a; border-radius: var(--kw-radius-sm); color: #d97706;">
                        <i class="bi bi-magic fs-5"></i>
                    </span>
                    <div>
                        <h6 class="modal-title fw-bold text-dark mb-0">Bộ Prompt Sinh Ảnh AI Cho Toàn Bộ Thẻ Trong Gói</h6>
                        <small class="text-muted" style="font-size: 0.775rem;">Chuẩn hóa tham số cho Midjourney, ChatGPT DALL-E 3 & Bing Image Creator</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <span class="text-muted small fw-medium" id="promptSheetSummary">Tổng cộng 6 thẻ</span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-kw-subtle btn-sm" onclick="copyAllPackPrompts('3d')">
                            <i class="bi bi-copy me-1"></i> Sao Chép Toàn Bộ Prompt 3D
                        </button>
                        <button type="button" class="btn btn-kw-outline btn-sm" onclick="copyAllPackPrompts('real')">
                            <i class="bi bi-copy me-1"></i> Sao Chép Toàn Bộ Prompt Ảnh Thật
                        </button>
                    </div>
                </div>
                <div class="table-responsive bg-white rounded border" style="border-color: var(--kw-border) !important;">
                    <table class="table table-custom mb-0" id="promptSheetTable">
                        <thead>
                            <tr>
                                <th style="width: 45px;">STT</th>
                                <th style="width: 150px;">Thẻ Học</th>
                                <th>🎨 Prompt 3D Pixar Cute</th>
                                <th>📸 Prompt Ảnh Chụp Thật</th>
                                <th style="width: 100px;" class="text-end">Upload</th>
                            </tr>
                        </thead>
                        <tbody id="promptSheetBody">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2.5 px-4 bg-white border-top">
                <button type="button" class="btn btn-kw-outline btn-sm" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
