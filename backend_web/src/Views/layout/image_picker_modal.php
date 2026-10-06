<!-- Image Picker, Media Uploader & AI Prompt Assistant Modal (evondevKit standard) -->
<div class="modal fade" id="kwImageBrowserModal" tabindex="-1" aria-labelledby="kwImageBrowserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: var(--kw-radius); border: 1px solid var(--kw-border); box-shadow: 0 16px 40px rgba(0,0,0,0.12); overflow: hidden;">
            <!-- Modal Header -->
            <div class="modal-header py-2.5 px-4" style="background: #ffffff; border-bottom: 1px solid var(--kw-border-light);">
                <div class="d-flex align-items-center gap-2.5">
                    <span class="d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px; background: var(--kw-primary-light); border: 1px solid var(--kw-primary-border); border-radius: var(--kw-radius-sm); color: var(--kw-primary);">
                        <i class="bi bi-images fs-5"></i>
                    </span>
                    <h6 class="modal-title fw-bold text-dark mb-0" id="kwImageBrowserModalLabel">Kho Ảnh & Trợ Lý Tạo Prompt AI</h6>
                </div>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Tab Navigation Header -->
            <div class="px-4 pt-2.5 pb-0 bg-white border-bottom" style="border-color: var(--kw-border-subtle) !important;">
                <ul class="nav nav-tabs border-bottom-0 gap-2" id="imgModalNavTabs" role="list">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active d-flex align-items-center gap-2 py-2 px-3 fw-medium" id="tab-upload-btn" data-bs-toggle="tab" data-bs-target="#tab-media-upload" type="button" role="tab">
                            <i class="bi bi-cloud-arrow-up text-primary fs-6"></i>
                            <span>1. Tải Lên & Kho Của Tôi</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link d-flex align-items-center gap-2 py-2 px-3 fw-medium" id="tab-prompt-btn" data-bs-toggle="tab" data-bs-target="#tab-ai-prompt" type="button" role="tab">
                            <i class="bi bi-stars text-warning fs-6"></i>
                            <span>2. Trợ Lý Tạo Prompt AI</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link d-flex align-items-center gap-2 py-2 px-3 fw-medium" id="tab-search-btn" data-bs-toggle="tab" data-bs-target="#tab-online-search" type="button" role="tab">
                            <i class="bi bi-globe2 text-info fs-6"></i>
                            <span>3. Tìm Kiếm Online & Dán URL</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-3.5 bg-light" style="min-height: 480px;">
                <div class="tab-content" id="imgModalTabsContent">
                    
                    <!-- ==========================================
                         TAB 1: TẢI LÊN & KHO ẢNH CỦA TÔI
                         ========================================== -->
                    <div class="tab-pane fade show active" id="tab-media-upload" role="tabpanel">
                        <!-- Dropzone Area -->
                        <div class="card card-custom p-4 text-center mb-4" id="imgDropzone" style="border: 2px dashed #93c5fd !important; background: #f0f9ff; cursor: pointer; transition: all 0.2s ease;">
                            <input type="file" id="imgFileInput" class="d-none" accept="image/png, image/jpeg, image/jpg, image/webp, image/gif, image/svg+xml">
                            <div class="py-3">
                                <div class="mb-2">
                                    <span class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle shadow-xs" style="width: 54px; height: 54px; border: 1px solid #bae6fd; color: #0284c7;">
                                        <i class="bi bi-cloud-arrow-up fs-2"></i>
                                    </span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">Kéo thả hình ảnh vào đây hoặc bấm để chọn từ máy tính</h6>
                                <p class="text-muted small mb-3">Hỗ trợ định dạng PNG, JPG, JPEG, WEBP, GIF, SVG (Tối đa 15MB/ảnh)</p>
                                <div>
                                    <button type="button" class="btn btn-kw-primary" onclick="document.getElementById('imgFileInput').click()">
                                        <i class="bi bi-folder2-open me-1"></i> Chọn Ảnh Từ Máy Tính
                                    </button>
                                </div>
                            </div>
                            <!-- Uploading Status -->
                            <div id="imgUploadProgress" class="d-none mt-3">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                <span class="text-primary fw-medium small" id="imgUploadStatusText">Đang tải ảnh lên máy chủ...</span>
                            </div>
                        </div>

                        <!-- Uploaded Library List -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.95rem;">
                                    Kho Ảnh Đã Tải Lên Trên Máy Chủ
                                </h6>
                                <span class="badge-kw-blue" id="uploadedCountBadge">0 ảnh</span>
                            </div>
                            <button type="button" class="btn btn-kw-outline btn-sm py-1 px-2.5" onclick="fetchUploadedImages()">
                                <i class="bi bi-arrow-clockwise me-1"></i> Làm mới kho ảnh
                            </button>
                        </div>

                        <!-- Loading State -->
                        <div id="uploadedLoading" class="text-center py-4 d-none">
                            <div class="spinner-border text-primary spinner-border-sm mb-2" role="status"></div>
                            <div class="text-muted small">Đang tải danh sách ảnh từ máy chủ...</div>
                        </div>

                        <!-- Empty State -->
                        <div id="uploadedEmpty" class="text-center py-5 text-muted d-none card card-custom p-4 bg-white">
                            <i class="bi bi-folder2-open fs-1 opacity-40 d-block mb-2"></i>
                            <span class="d-block fw-medium text-dark">Chưa có ảnh nào được tải lên</span>
                            <small class="text-muted">Kéo thả ảnh ở khu vực trên để lưu trữ và sử dụng cho thẻ học của bé.</small>
                        </div>

                        <!-- Uploaded Grid -->
                        <div class="row g-2.5" id="uploadedGrid">
                            <!-- Populated dynamically -->
                        </div>
                    </div>

                    <!-- ==========================================
                         TAB 2: TRỢ LÝ TẠO PROMPT AI
                         ========================================== -->
                    <div class="tab-pane fade" id="tab-ai-prompt" role="tabpanel">
                        <div class="card card-custom p-3.5 mb-3 bg-white">
                            <div class="row g-2.5 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label small fw-semibold text-dark mb-1">
                                        <i class="bi bi-lightbulb text-warning me-1"></i> Đối tượng / Chủ đề cần tạo:
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="aiPromptKeyword" class="form-control" placeholder="Ví dụ: Hà Mã, Sư tử, Xe buýt, T-Rex..." onkeydown="if(event.key==='Enter'){ generateAiImagePrompts(); }">
                                        <button type="button" class="btn btn-kw-primary" onclick="generateAiImagePrompts()">
                                            <i class="bi bi-magic me-1"></i> Tạo Prompt AI
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold text-dark mb-1">
                                        <i class="bi bi-aspect-ratio text-info me-1"></i> Kích Thước & Tỉ Lệ:
                                    </label>
                                    <select id="aiPromptSizeSelect" class="form-select form-select-sm" onchange="generateAiImagePrompts()">
                                        <option value="1:1|1024x1024" selected>Vuông 1:1 (1024x1024) - Chuẩn App</option>
                                        <option value="4:3|1024x768">Ngang 4:3 (1024x768)</option>
                                        <option value="3:4|768x1024">Dọc 3:4 (768x1024)</option>
                                        <option value="16:9|1280x720">Rộng 16:9 (1280x720)</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <div class="d-flex flex-wrap gap-1" id="aiPromptQuickTags">
                                        <button type="button" class="btn btn-kw-outline btn-sm py-0.5 px-2" style="font-size: 0.75rem;" onclick="setPromptKeyword('Hà Mã')">🦛 Hà Mã</button>
                                        <button type="button" class="btn btn-kw-outline btn-sm py-0.5 px-2" style="font-size: 0.75rem;" onclick="setPromptKeyword('Sư tử')">🦁 Sư tử</button>
                                        <button type="button" class="btn btn-kw-outline btn-sm py-0.5 px-2" style="font-size: 0.75rem;" onclick="setPromptKeyword('Cá heo')">🐬 Cá heo</button>
                                        <button type="button" class="btn btn-kw-outline btn-sm py-0.5 px-2" style="font-size: 0.75rem;" onclick="setPromptKeyword('Máy bay')">✈️ Máy bay</button>
                                        <button type="button" class="btn btn-kw-outline btn-sm py-0.5 px-2" style="font-size: 0.75rem;" onclick="setPromptKeyword('T-Rex')">🦖 T-Rex</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Prompt Result Cards Container: 3 Cột Cân Đối -->
                        <div id="aiPromptResults">
                            <div class="row g-3">
                                <!-- Option 1: 3D Pixar Animation -->
                                <div class="col-lg-4">
                                    <div class="card card-custom h-100 p-3 bg-white d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="d-flex align-items-center gap-1.5">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.7rem;">Flashcard 3D</span>
                                                <h6 class="fw-bold text-dark mb-0" style="font-size: 0.85rem;">
                                                    🎨 Hoạt Họa 3D Pixar Cute
                                                </h6>
                                            </div>
                                            <button type="button" class="btn btn-kw-primary btn-sm py-0.5 px-2" style="font-size: 0.75rem;" onclick="copyPromptText('promptPixarText', this)">
                                                <i class="bi bi-copy me-1"></i> Sao Chép
                                            </button>
                                        </div>
                                        <div class="p-2.5 rounded bg-light border font-monospace text-dark flex-grow-1 mb-2.5" style="font-size: 0.75rem; line-height: 1.45; white-space: pre-wrap; word-break: break-word;" id="promptPixarText">Adorable cute baby hippopotamus, chubby body, friendly smiling expression, big sparkling eyes, smooth 3D Pixar Disney animation style, soft warm studio lighting, vibrant cheerful pastel colors, clean solid light pastel background, 3D claymation render, Octane render, 8k, volumetric lighting, children educational book illustration, centered, full body shot, high resolution 1024x1024 pixels --ar 1:1 --v 6.0</div>
                                        <div class="d-flex align-items-center justify-content-end pt-1.5 border-top">
                                            <a href="https://www.bing.com/images/create" target="_blank" class="btn btn-kw-outline btn-sm py-0.5 px-2" style="font-size: 0.725rem;">
                                                <i class="bi bi-box-arrow-up-right me-1"></i> Mở Bing Creator
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Option 2: Real Life Photography -->
                                <div class="col-lg-4">
                                    <div class="card card-custom h-100 p-3 bg-white d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="d-flex align-items-center gap-1.5">
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size: 0.7rem;">Ảnh Đời Thực</span>
                                                <h6 class="fw-bold text-dark mb-0" style="font-size: 0.85rem;">
                                                    📸 Ảnh Chụp Thật (NatGeo)
                                                </h6>
                                            </div>
                                            <button type="button" class="btn btn-kw-primary btn-sm py-0.5 px-2" style="font-size: 0.75rem;" onclick="copyPromptText('promptRealText', this)">
                                                <i class="bi bi-copy me-1"></i> Sao Chép
                                            </button>
                                        </div>
                                        <div class="p-2.5 rounded bg-light border font-monospace text-dark flex-grow-1 mb-2.5" style="font-size: 0.75rem; line-height: 1.45; white-space: pre-wrap; word-break: break-word;" id="promptRealText">Authentic high resolution wildlife photograph of a hippopotamus resting peacefully near a clean tropical African riverbank, National Geographic documentary style, sharp crisp focus on natural skin texture and gentle eyes, shot on 85mm lens f/2.8, photorealistic, beautiful natural daylight, detailed wildlife photography, clean uncluttered composition, high resolution 1024x1024 pixels --ar 1:1 --v 6.0</div>
                                        <div class="d-flex align-items-center justify-content-end pt-1.5 border-top">
                                            <a href="https://chatgpt.com" target="_blank" class="btn btn-kw-outline btn-sm py-0.5 px-2" style="font-size: 0.725rem;">
                                                <i class="bi bi-box-arrow-up-right me-1"></i> Mở ChatGPT (DALL-E 3)
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Option 3: Tranh Nét Chia Mảng Để Tô (Coloring Page Line Art) -->
                                <div class="col-lg-4">
                                    <div class="card card-custom h-100 p-3 bg-white d-flex flex-column" style="border-color: #FED7AA !important; background: #FFFDF9 !important;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="d-flex align-items-center gap-1.5">
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 0.7rem;">Game Tô Màu</span>
                                                <h6 class="fw-bold text-dark mb-0" style="font-size: 0.85rem;">
                                                    🖍️ Tranh Nét Chia Mảng Để Tô
                                                </h6>
                                            </div>
                                            <button type="button" class="btn btn-kw-primary btn-sm py-0.5 px-2" style="font-size: 0.75rem;" onclick="copyPromptText('promptColoringText', this)">
                                                <i class="bi bi-copy me-1"></i> Sao Chép
                                            </button>
                                        </div>
                                        <div class="p-2.5 rounded bg-white border font-monospace text-dark flex-grow-1 mb-2.5" style="font-size: 0.75rem; line-height: 1.45; white-space: pre-wrap; word-break: break-word;" id="promptColoringText">Clean bold black line art, coloring book page for toddlers and kids, cute friendly hippopotamus, segmented into distinct clear individual sections for easy coloring, thick closed outlines, well-defined large coloring areas, no shading, no gradients, no grey tones, pure solid white background, high contrast, minimalist cartoon illustration, printable coloring sheet, crisp vector lines, square format 1024x1024 pixels --ar 1:1 --v 6.0</div>
                                        <div class="d-flex align-items-center justify-content-between pt-1.5 border-top">
                                            <small class="text-success fw-medium" style="font-size: 0.7rem;">Viền khép kín, chia mảng</small>
                                            <a href="https://www.bing.com/images/create" target="_blank" class="btn btn-kw-outline btn-sm py-0.5 px-2" style="font-size: 0.725rem;">
                                                <i class="bi bi-box-arrow-up-right me-1"></i> Mở Bing Creator
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dropzone right under prompt for fast workflow -->
                        <div class="mt-3 p-2.5 bg-white rounded border d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-color: #bae6fd !important;">
                            <span class="fw-semibold text-dark small">
                                <i class="bi bi-cloud-arrow-up text-primary me-1"></i> Đã tạo xong ảnh từ AI?
                            </span>
                            <button type="button" class="btn btn-kw-subtle btn-sm py-1 px-3" onclick="document.getElementById('tab-upload-btn').click(); document.getElementById('imgFileInput').click();">
                                Tải Lên Máy Chủ Ngay
                            </button>
                        </div>
                    </div>

                    <!-- ==========================================
                         TAB 3: TÌM KIẾM ONLINE & DÁN URL
                         ========================================== -->
                    <div class="tab-pane fade" id="tab-online-search" role="tabpanel">
                        <!-- Search Box -->
                        <div class="card card-custom p-3.5 mb-3 bg-white">
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-white text-muted" style="border-color: var(--kw-border);">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" id="imgModalSearchInput" class="form-control" placeholder="Nhập từ khóa tìm kiếm trực tuyến (ví dụ: Hippopotamus, Dolphin, Lion...)" onkeydown="if(event.key==='Enter'){ searchModalImages(); }">
                                <button type="button" class="btn btn-kw-primary px-3" onclick="searchModalImages()">
                                    <i class="bi bi-search me-1"></i> Tìm Ảnh Online
                                </button>
                            </div>
                            <!-- Quick Suggestions -->
                            <div class="d-flex align-items-center gap-1.5 flex-wrap" id="imgModalSuggestions">
                                <!-- Dynamic chips -->
                            </div>
                        </div>

                        <!-- Custom Direct URL Paste Bar -->
                        <div class="p-2.5 rounded bg-white border mb-3 d-flex align-items-center gap-2" style="border-color: var(--kw-border) !important;">
                            <small class="text-muted text-nowrap" style="font-size: 0.75rem;">Hoặc dán URL trực tiếp:</small>
                            <input type="text" id="imgModalDirectUrl" class="form-control form-control-sm" placeholder="https://images.unsplash.com/photo-... hoặc URL ảnh bất kỳ">
                            <button type="button" class="btn btn-kw-subtle btn-sm text-nowrap py-1 px-2.5" onclick="selectDirectUrl()">
                                Áp dụng URL
                            </button>
                        </div>

                        <!-- Loading State -->
                        <div id="imgModalLoading" class="text-center py-5 d-none">
                            <div class="spinner-border text-primary mb-2" style="width: 2.2rem; height: 2.2rem; border-width: 0.2em;" role="status"></div>
                            <div class="text-muted small">Đang tìm kiếm kho ảnh bách khoa an toàn cho trẻ em...</div>
                        </div>

                        <!-- Empty State -->
                        <div id="imgModalEmpty" class="text-center py-5 text-muted d-none card card-custom p-4 bg-white">
                            <i class="bi bi-image fs-1 opacity-40 d-block mb-2"></i>
                            <span class="d-block fw-medium text-dark">Chưa tìm thấy hình ảnh phù hợp</span>
                            <small class="text-muted">Hãy thử tìm bằng từ khóa tiếng Anh ngắn gọn hơn (vd: <code>hippo</code>, <code>whale</code>, <code>airplane</code>)</small>
                        </div>

                        <!-- Image Gallery Grid -->
                        <div class="row g-2.5" id="imgModalGrid">
                            <!-- Populated dynamically -->
                        </div>
                    </div>

                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer py-2 px-4 justify-content-end" style="background: #ffffff; border-top: 1px solid var(--kw-border-light);">
                <button type="button" class="btn btn-kw-outline btn-sm px-3" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<style>
    .img-picker-card {
        border: 1px solid var(--kw-border);
        border-radius: var(--kw-radius-sm);
        overflow: hidden;
        background: #ffffff;
        cursor: pointer;
        position: relative;
        transition: all 0.15s ease;
    }

    .img-picker-card:hover {
        border-color: var(--kw-primary);
        box-shadow: 0 4px 14px rgba(14, 165, 233, 0.2);
        transform: translateY(-2px);
    }

    .img-picker-card:hover .img-picker-hover-btn {
        opacity: 1;
    }

    .img-picker-hover-btn {
        position: absolute;
        bottom: 8px;
        left: 50%;
        transform: translateX(-50%);
        opacity: 0;
        transition: opacity 0.15s ease;
        background: rgba(14, 165, 233, 0.95);
        color: #ffffff;
        font-size: 0.725rem;
        font-weight: 500;
        padding: 3px 8px;
        border-radius: 6px;
        white-space: nowrap;
        pointer-events: none;
    }

    #imgDropzone.dragover {
        border-color: var(--kw-primary) !important;
        background-color: #e0f2fe !important;
        transform: scale(1.01);
    }
</style>

<script>
    let currentImageTarget = {
        inputId: null,
        previewId: null,
        callback: null,
        preferredStyle: null
    };

    function openImageBrowser(options) {
        currentImageTarget.inputId = options.inputId || null;
        currentImageTarget.previewId = options.previewId || null;
        currentImageTarget.callback = options.callback || null;
        currentImageTarget.preferredStyle = options.preferredStyle || null;

        const modalEl = document.getElementById('kwImageBrowserModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        const rawQuery = (options.query || '').trim();
        const cleanQuery = rawQuery.replace(/coloring\s+page|line\s+art|outline\s+for\s+kids/gi, '').trim();
        document.getElementById('imgModalSearchInput').value = cleanQuery || rawQuery;
        document.getElementById('aiPromptKeyword').value = cleanQuery || rawQuery || 'Hà Mã';
        document.getElementById('imgModalDirectUrl').value = '';

        // Switch tab based on preference
        if (options.defaultTab === 'ai_prompt' || options.preferredStyle === 'coloring_outline') {
            const promptTabBtn = document.getElementById('tab-prompt-btn');
            if (promptTabBtn) bootstrap.Tab.getOrCreateInstance(promptTabBtn).show();
            generateAiImagePrompts();
        } else if (options.defaultTab === 'search') {
            const searchTabBtn = document.getElementById('tab-search-btn');
            if (searchTabBtn) bootstrap.Tab.getOrCreateInstance(searchTabBtn).show();
            renderModalSuggestions(cleanQuery || rawQuery);
            if (cleanQuery || rawQuery) searchModalImages();
        } else {
            // Default to Upload tab
            const uploadTabBtn = document.getElementById('tab-upload-btn');
            if (uploadTabBtn) bootstrap.Tab.getOrCreateInstance(uploadTabBtn).show();
            fetchUploadedImages();
            if (cleanQuery || rawQuery) generateAiImagePrompts();
        }

        modal.show();
    }

    // ==========================================
    // UPLOAD LOGIC & DRAG AND DROP
    // ==========================================
    document.addEventListener('DOMContentLoaded', function() {
        const dropzone = document.getElementById('imgDropzone');
        const fileInput = document.getElementById('imgFileInput');

        if (dropzone && fileInput) {
            ['dragenter', 'dragover'].forEach(evt => {
                dropzone.addEventListener(evt, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('dragover');
                });
            });

            ['dragleave', 'drop'].forEach(evt => {
                dropzone.addEventListener(evt, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('dragover');
                });
            });

            dropzone.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                const files = dt.files;
                if (files && files.length > 0) {
                    uploadImageFile(files[0]);
                }
            });

            fileInput.addEventListener('change', function() {
                if (this.files && this.files.length > 0) {
                    uploadImageFile(this.files[0]);
                }
            });
        }
    });

    function uploadImageFile(file) {
        if (!file) return;

        const progress = document.getElementById('imgUploadProgress');
        const statusText = document.getElementById('imgUploadStatusText');

        progress.classList.remove('d-none');
        statusText.innerText = 'Đang tải lên ' + file.name + '...';

        const formData = new FormData();
        formData.append('image', file);

        fetch('/api/upload-image', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            progress.classList.add('d-none');
            if (data.status === 'success') {
                // Refresh list and auto-select
                fetchUploadedImages(data.url);
                selectPickedImage(data.url);
            } else {
                alert('Lỗi tải ảnh: ' + (data.message || 'Không thể upload ảnh'));
            }
        })
        .catch(err => {
            progress.classList.add('d-none');
            alert('Lỗi đường truyền khi tải ảnh: ' + err);
        });
    }

    function fetchUploadedImages(highlightUrl = null) {
        const loading = document.getElementById('uploadedLoading');
        const empty = document.getElementById('uploadedEmpty');
        const grid = document.getElementById('uploadedGrid');
        const badge = document.getElementById('uploadedCountBadge');

        loading.classList.remove('d-none');
        empty.classList.add('d-none');
        grid.innerHTML = '';

        fetch('/api/media/list')
        .then(res => res.json())
        .then(data => {
            loading.classList.add('d-none');
            if (data.status === 'success' && data.images && data.images.length > 0) {
                badge.innerText = data.count + ' ảnh';
                renderUploadedGallery(data.images, highlightUrl);
            } else {
                badge.innerText = '0 ảnh';
                empty.classList.remove('d-none');
            }
        })
        .catch(err => {
            loading.classList.add('d-none');
            console.error('Lỗi lấy danh sách ảnh đã tải lên:', err);
        });
    }

    function renderUploadedGallery(images, highlightUrl = null) {
        const grid = document.getElementById('uploadedGrid');
        grid.innerHTML = '';

        images.forEach(img => {
            const isHighlight = highlightUrl && img.url === highlightUrl;
            const col = document.createElement('div');
            col.className = 'col-lg-2 col-md-3 col-sm-4 col-6';

            col.innerHTML = `
                <div class="img-picker-card ${isHighlight ? 'border-primary ring-2' : ''}" onclick="selectPickedImage('${escapeJsString(img.url)}')">
                    <div style="height: 120px; background: #ffffff; overflow: hidden; position: relative;" class="d-flex align-items-center justify-content-center p-1">
                        <img src="${escapeJsString(img.thumb)}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;" loading="lazy" alt="Uploaded Image">
                        <div class="img-picker-hover-btn shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Chọn ảnh này
                        </div>
                    </div>
                    <div class="p-2 bg-white border-top" style="border-color: #f1f5f9 !important;">
                        <div class="text-truncate fw-medium text-dark small" style="font-size: 0.725rem;" title="${escapeJsString(img.filename)}">
                            ${escapeJsString(img.filename)}
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 0.675rem;">
                            <span>${escapeJsString(img.size_formatted || '')}</span>
                            <span>${escapeJsString(img.created_at || '')}</span>
                        </div>
                    </div>
                </div>
            `;
            grid.appendChild(col);
        });
    }

    // ==========================================
    // AI PROMPT GENERATOR LOGIC
    // ==========================================
    function setPromptKeyword(kw) {
        document.getElementById('aiPromptKeyword').value = kw;
        generateAiImagePrompts();
    }

    function generateAiImagePrompts() {
        const rawKw = document.getElementById('aiPromptKeyword').value.trim() || 'Hà Mã';
        const cleanKw = rawKw.replace(/coloring\s+page|line\s+art|outline\s+for\s+kids/gi, '').trim() || rawKw;

        const sizeVal = document.getElementById('aiPromptSizeSelect')?.value || '1:1|1024x1024';
        const parts = sizeVal.split('|');
        const ar = parts[0] || '1:1';
        const res = parts[1] || '1024x1024';

        fetch('/api/ai/image-prompt', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                keyword: cleanKw,
                aspect_ratio: ar,
                resolution: res
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.data && data.data.prompts) {
                const prompts = data.data.prompts;
                if (prompts.pixar_3d && document.getElementById('promptPixarText')) {
                    document.getElementById('promptPixarText').innerText = prompts.pixar_3d.prompt;
                }
                if (prompts.real_photo && document.getElementById('promptRealText')) {
                    document.getElementById('promptRealText').innerText = prompts.real_photo.prompt;
                }
                if (prompts.coloring_outline && document.getElementById('promptColoringText')) {
                    document.getElementById('promptColoringText').innerText = prompts.coloring_outline.prompt;
                }
            }
        })
        .catch(err => {
            console.error('Lỗi sinh prompt AI:', err);
        });
    }

    function copyPromptText(elementId, btnElement) {
        const text = document.getElementById(elementId).innerText;
        navigator.clipboard.writeText(text).then(function() {
            const originalHtml = btnElement.innerHTML;
            btnElement.innerHTML = '<i class="bi bi-check2 text-white me-1"></i> Đã sao chép!';
            btnElement.classList.remove('btn-kw-primary');
            btnElement.classList.add('btn-success');
            setTimeout(function() {
                btnElement.innerHTML = originalHtml;
                btnElement.classList.remove('btn-success');
                btnElement.classList.add('btn-kw-primary');
            }, 2000);
        }).catch(function() {
            prompt('Sao chép prompt bên dưới:', text);
        });
    }

    // ==========================================
    // ONLINE SEARCH & SELECTION LOGIC
    // ==========================================
    function renderModalSuggestions(query) {
        const container = document.getElementById('imgModalSuggestions');
        container.innerHTML = '';
        if (!query) return;

        const words = query.split(/[\s,–-]+/).filter(w => w.length > 2);
        const set = new Set([query, ...words]);

        set.forEach(text => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-kw-outline btn-sm py-0.5 px-2';
            btn.style.fontSize = '0.725rem';
            btn.innerText = text;
            btn.onclick = function() {
                document.getElementById('imgModalSearchInput').value = text;
                searchModalImages();
            };
            container.appendChild(btn);
        });
    }

    function searchModalImages() {
        const query = document.getElementById('imgModalSearchInput').value.trim();
        if (!query) return;

        const loading = document.getElementById('imgModalLoading');
        const empty = document.getElementById('imgModalEmpty');
        const grid = document.getElementById('imgModalGrid');

        loading.classList.remove('d-none');
        empty.classList.add('d-none');
        grid.innerHTML = '';

        fetch('/api/images/search?q=' + encodeURIComponent(query))
        .then(res => res.json())
        .then(data => {
            loading.classList.add('d-none');
            if (data.status === 'success' && data.images && data.images.length > 0) {
                renderModalGallery(data.images);
            } else {
                empty.classList.remove('d-none');
            }
        })
        .catch(err => {
            loading.classList.add('d-none');
            empty.classList.remove('d-none');
            console.error('Lỗi tìm ảnh:', err);
        });
    }

    function renderModalGallery(images) {
        const grid = document.getElementById('imgModalGrid');
        grid.innerHTML = '';

        images.forEach(img => {
            const col = document.createElement('div');
            col.className = 'col-lg-3 col-md-4 col-sm-6';

            col.innerHTML = `
                <div class="img-picker-card" onclick="selectPickedImage('${escapeJsString(img.thumb || img.url)}')">
                    <div style="height: 130px; background: #f1f5f9; overflow: hidden; position: relative;">
                        <img src="${escapeJsString(img.thumb || img.url)}" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy" alt="${escapeJsString(img.title || '')}">
                        <div class="img-picker-hover-btn shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Chọn ảnh này
                        </div>
                    </div>
                    <div class="p-1.5 text-truncate text-muted small px-2" style="font-size: 0.7rem;" title="${escapeJsString(img.title || '')}">
                        ${escapeJsString(img.title || 'Ảnh minh họa')}
                    </div>
                </div>
            `;
            grid.appendChild(col);
        });
    }

    function selectPickedImage(url) {
        if (!url) return;

        if (currentImageTarget.inputId) {
            const inputEl = document.getElementById(currentImageTarget.inputId);
            if (inputEl) {
                inputEl.value = url;
                inputEl.dispatchEvent(new Event('change'));
            }
        }

        if (currentImageTarget.previewId) {
            const previewEl = document.getElementById(currentImageTarget.previewId);
            if (previewEl) {
                previewEl.src = url;
                const wrap = previewEl.closest('.d-none') || document.getElementById('itemFormImgPreviewWrap');
                if (wrap) wrap.classList.remove('d-none');
            }
        }

        if (typeof currentImageTarget.callback === 'function') {
            currentImageTarget.callback(url);
        }

        // Close modal
        const modalEl = document.getElementById('kwImageBrowserModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
    }

    function selectDirectUrl() {
        const directUrl = document.getElementById('imgModalDirectUrl').value.trim();
        if (!directUrl) {
            alert('Vui lòng dán link URL ảnh hợp lệ.');
            return;
        }
        selectPickedImage(directUrl);
    }

    function escapeJsString(str) {
        if (!str) return '';
        return String(str)
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'")
            .replace(/"/g, '&quot;');
    }
</script>
