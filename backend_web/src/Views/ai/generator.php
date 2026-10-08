<?php
$pageTitle = 'Tạo bằng AI';
ob_start(); ?>
<div class="k-savebar d-none" id="resultSavebar">
    <span class="k-muted" style="white-space:nowrap">Giữ <span id="selectedCountText">0</span> / <span id="totalCountText">0</span> thẻ</span>
    <button type="button" class="k-btn k-btn-ghost k-btn-sm k-hide-sm" onclick="toggleSelectAll(true)">Chọn tất cả</button>
    <button type="button" class="k-btn k-btn-ghost k-btn-sm k-hide-sm" onclick="toggleSelectAll(false)">Bỏ chọn</button>
    <div style="margin-left:auto;display:flex;gap:8px">
        <button type="button" class="k-btn k-btn-outline k-sm-icon" onclick="openPackPromptSheet()" aria-label="Bộ prompt ảnh cả gói"><i class="bi bi-magic"></i><span class="k-btn-label">Prompt ảnh cả gói</span></button>
        <button type="button" class="k-btn k-btn-outline" onclick="resetForm()">Soạn lại</button>
        <button type="button" class="k-btn k-btn-primary" onclick="saveGeneratedPack()">Lưu thành gói</button>
    </div>
</div>
<?php $kwAfterMain = ob_get_clean(); ?>

<div class="k-split-left">
    <!-- Cột trái: ba cách đưa nội dung vào -->
    <div class="k-sticky">
        <section class="k-card">
            <div class="k-tabs" role="tablist" style="padding:0 20px;gap:20px" id="studioTabs">
                <button class="k-tab" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#builtinTab" aria-selected="true">Tạo trực tiếp</button>
                <button class="k-tab" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#promptTab" aria-selected="false">Prompt cho AI ngoài</button>
                <button class="k-tab" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#importTab" aria-selected="false">Dán JSON</button>
            </div>
            <div class="tab-content">
                <!-- Tạo trực tiếp -->
                <div class="tab-pane fade show active" id="builtinTab" role="tabpanel">
                    <form id="aiPromptForm" onsubmit="handleGenerate(event)" class="k-card-body" style="display:grid;gap:16px">
                        <div class="k-field"><label class="k-label" for="promptInput">Chủ đề <span class="k-req">*</span></label>
                            <textarea id="promptInput" class="k-textarea" placeholder="Ví dụ: Bộ thẻ về các loài khủng long cho bé 4–8 tuổi" required>Thế giới khủng long tiền sử kỳ thú cho bé 4-8 tuổi</textarea></div>
                        <div class="k-grid-2">
                            <div class="k-field"><label class="k-label" for="countSelect">Số thẻ</label>
                                <select id="countSelect" class="k-select"><option value="4">4 thẻ</option><option value="6" selected>6 thẻ</option><option value="8">8 thẻ</option><option value="10">10 thẻ</option></select></div>
                            <div class="k-field"><label class="k-label" for="targetAgeSelect">Độ tuổi</label>
                                <select id="targetAgeSelect" class="k-select"><option value="2-5">2–5 tuổi</option><option value="3-8" selected>3–8 tuổi</option><option value="6-10">6–10 tuổi</option></select></div>
                        </div>
                        <?php if (!empty($hasAiKey)): ?>
                            <p class="k-muted" style="margin:0"><i class="bi bi-check-circle-fill" style="color:var(--success)"></i> Dùng Gemini <span class="k-mono"><?= kw_e($aiModel ?? '') ?></span>. <a href="/settings#ai" style="color:var(--foreground);font-weight:500">Đổi ở Cài đặt</a></p>
                        <?php else: ?>
                            <div class="k-flash k-flash-warning"><i class="bi bi-exclamation-triangle-fill"></i><span>Chưa có API key nên kết quả là bộ thẻ mẫu, không theo chủ đề. <a href="/settings#ai" style="color:var(--foreground);font-weight:500;text-decoration:underline">Thêm key ở Cài đặt</a></span></div>
                        <?php endif; ?>
                        <button type="submit" id="btnGenerate" class="k-btn k-btn-primary"><i class="bi bi-stars"></i>Tạo nội dung</button>
                    </form>
                </div>

                <!-- Prompt cho AI ngoài -->
                <div class="tab-pane fade" id="promptTab" role="tabpanel">
                    <div class="k-card-body" style="display:grid;gap:16px">
                        <div class="k-field"><label class="k-label" for="builderTopicInput">Chủ đề</label>
                            <input type="text" id="builderTopicInput" class="k-input" value="Thế giới động vật hoang dã kỳ thú trên thảo nguyên" placeholder="Ví dụ: Các loại xe cứu hộ" oninput="updatePromptDisplay()"></div>
                        <div class="k-grid-2">
                            <div class="k-field"><label class="k-label" for="builderCountSelect">Số thẻ</label>
                                <select id="builderCountSelect" class="k-select" onchange="updatePromptDisplay()"><option value="4">4 thẻ</option><option value="6" selected>6 thẻ</option><option value="8">8 thẻ</option><option value="10">10 thẻ</option></select></div>
                            <div class="k-field"><label class="k-label" for="builderAgeSelect">Độ tuổi</label>
                                <select id="builderAgeSelect" class="k-select" onchange="updatePromptDisplay()"><option value="2-5">2–5 tuổi</option><option value="3-8" selected>3–8 tuổi</option><option value="6-10">6–10 tuổi</option></select></div>
                        </div>
                        <div style="display:flex;flex-wrap:wrap;gap:6px" aria-label="Chủ đề gợi ý">
                            <button type="button" class="k-chip" onclick="setBuilderTopic('Thế giới động vật hoang dã dũng mãnh và đáng yêu trên thảo nguyên', 6, '3-8')">Động vật thảo nguyên</button>
                            <button type="button" class="k-chip" onclick="setBuilderTopic('Các phương tiện giao thông cứu hộ khẩn cấp: xe cứu hỏa, cứu thương, cảnh sát, trực thăng', 6, '3-6')">Xe cứu hộ</button>
                            <button type="button" class="k-chip" onclick="setBuilderTopic('Thế giới khủng long tiền sử kỳ vĩ: T-Rex, Triceratops, Brachiosaurus', 6, '4-8')">Khủng long</button>
                            <button type="button" class="k-chip" onclick="setBuilderTopic('Khám phá hệ mặt trời, các phi hành gia và tàu vũ trụ', 6, '5-10')">Vũ trụ</button>
                            <button type="button" class="k-chip" onclick="setBuilderTopic('Các sinh vật đại dương kỳ thú: cá voi xanh, cá heo thông minh, rùa biển', 6, '3-8')">Đại dương</button>
                            <button type="button" class="k-chip" onclick="setBuilderTopic('Các loại trái cây nhiệt đới thơm ngon và giàu vitamin', 6, '2-5')">Trái cây</button>
                        </div>
                        <div class="k-field"><label class="k-label" for="masterPromptBox">Prompt hoàn chỉnh</label>
                            <textarea id="masterPromptBox" class="k-prompt-box"></textarea></div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                            <button type="button" class="k-btn k-btn-outline" onclick="copyMasterPrompt()"><i class="bi bi-clipboard" id="copyIcon"></i><span id="copyBtnText">Sao chép prompt</span></button>
                            <button type="button" class="k-btn k-btn-primary" onclick="goToImportTab()">Đã có kết quả, dán JSON</button>
                        </div>
                        <div class="k-xs k-muted" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">Mở nhanh:
                            <a class="k-link-more" style="margin:0" href="https://chatgpt.com" target="_blank" rel="noopener">ChatGPT</a>
                            <a class="k-link-more" style="margin:0" href="https://gemini.google.com" target="_blank" rel="noopener">Gemini</a>
                            <a class="k-link-more" style="margin:0" href="https://claude.ai" target="_blank" rel="noopener">Claude</a>
                        </div>
                    </div>
                </div>

                <!-- Dán JSON -->
                <div class="tab-pane fade" id="importTab" role="tabpanel">
                    <div class="k-card-body" style="display:grid;gap:16px">
                        <div class="k-field"><label class="k-label" for="jsonImportInput">JSON từ AI</label>
                            <textarea id="jsonImportInput" class="k-prompt-box" placeholder='Dán {"pack": {...}, "items": [...]} vào đây'></textarea>
                            <div id="jsonErrorAlert" class="k-banner d-none" role="alert"></div></div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                            <button type="button" class="k-btn k-btn-primary" onclick="handleImportJson()">Xem trước</button>
                            <button type="button" class="k-btn k-btn-outline" onclick="loadSampleJson()">Dữ liệu mẫu</button>
                            <button type="button" class="k-btn k-btn-ghost" onclick="clearJsonInput()">Xoá trắng</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Cột phải: kết quả để duyệt -->
    <div class="k-stack">
        <div id="emptyResultBox" class="k-card"><p class="k-empty" style="margin:0">Nhập chủ đề rồi bấm Tạo nội dung, hoặc dán JSON từ AI ngoài. Thẻ AI soạn sẽ hiện ở đây để bạn duyệt trước khi lưu.</p></div>

        <div id="loadingBox" class="k-stack d-none" aria-live="polite">
            <div class="k-muted" style="display:flex;gap:8px;align-items:center"><span class="spinner-border spinner-border-sm text-primary" role="status"></span><span><span id="loadingTitle">AI đang soạn thẻ</span>. <span id="loadingSubtitle">Thường mất khoảng 20 giây.</span></span></div>
            <?php for ($i = 0; $i < 4; $i++): ?>
                <div class="k-card" style="padding:16px;display:flex;gap:12px"><span class="k-skel" style="width:56px;height:56px"></span><div style="flex:1;display:grid;gap:8px"><span class="k-skel" style="width:40%;height:14px"></span><span class="k-skel" style="width:30%;height:10px"></span></div></div>
            <?php endfor; ?>
        </div>

        <div id="resultContainer" class="k-stack d-none">
            <div id="resultWarning" class="k-flash k-flash-warning d-none" role="alert"><i class="bi bi-exclamation-triangle-fill"></i><span></span></div>
            <section class="k-card">
                <div class="k-card-head"><h2>Thông tin gói</h2></div>
                <div class="k-card-body" style="display:grid;grid-template-columns:120px minmax(0,1fr);gap:16px" id="packMetaGrid">
                    <div style="display:grid;gap:8px;align-content:start">
                        <div class="k-image-box" style="aspect-ratio:1;cursor:pointer" onclick="browsePackImage()" title="Đổi ảnh đại diện"><img id="previewPackThumb" src="" alt="Ảnh đại diện gói"></div>
                        <input type="hidden" id="packThumbInput" onchange="updatePackThumb(this.value)">
                        <button type="button" class="k-btn k-btn-outline k-btn-sm" onclick="browsePackImage()">Đổi ảnh</button>
                    </div>
                    <div style="display:grid;gap:12px">
                        <div class="k-grid-2">
                            <div class="k-field"><label class="k-label" for="packTitleViInput">Tên tiếng Việt</label><input type="text" id="packTitleViInput" class="k-input" required></div>
                            <div class="k-field"><label class="k-label" for="packTitleEnInput">Tên tiếng Anh</label><input type="text" id="packTitleEnInput" class="k-input" required></div>
                        </div>
                        <div class="k-grid-3">
                            <div class="k-field"><label class="k-label" for="packIdInput">Mã gói</label><input type="text" id="packIdInput" class="k-input k-mono" required></div>
                            <div class="k-field"><label class="k-label" for="packCategorySelect">Danh mục</label>
                                <select id="packCategorySelect" class="k-select">
                                    <option value="animals">Động vật</option><option value="dinosaurs">Khủng long</option><option value="space">Vũ trụ</option><option value="vehicles">Xe cộ</option><option value="fruits">Trái cây</option><option value="nature">Thiên nhiên</option><option value="general">Tổng hợp</option>
                                </select></div>
                            <div class="k-field"><label class="k-label" for="packThemeColorInput">Màu chủ đạo</label>
                                <div style="display:flex;gap:8px"><input type="color" id="packThemeColorPicker" class="form-control form-control-color" value="#10b981" onchange="document.getElementById('packThemeColorInput').value = this.value" aria-label="Chọn màu"><input type="text" id="packThemeColorInput" class="k-input k-mono" value="#10b981" onchange="document.getElementById('packThemeColorPicker').value = this.value"></div></div>
                        </div>
                        <div class="k-grid-3">
                            <div class="k-field"><span class="k-label">Độ tuổi</span><div style="display:flex;gap:6px;align-items:center"><input type="number" id="packAgeMinInput" class="k-input k-num" min="1" max="15" aria-label="Tuổi từ"><span class="k-muted">–</span><input type="number" id="packAgeMaxInput" class="k-input k-num" min="1" max="15" aria-label="Tuổi đến"></div></div>
                            <div class="k-field"><label class="k-label" for="packSizeInput">Dung lượng (MB)</label><input type="number" step="0.1" id="packSizeInput" class="k-input k-num"></div>
                            <div class="k-field"><label class="k-label" for="packBgUrlInput">Hình nền</label><input type="text" id="packBgUrlInput" class="k-input" placeholder="Để trống dùng nền chuẩn"></div>
                        </div>
                        <div class="k-field"><label class="k-label" for="packDescViInput">Mô tả cho bé</label><input type="text" id="packDescViInput" class="k-input"></div>
                    </div>
                </div>
            </section>

            <div id="itemsGridContainer" class="k-stack"></div>
        </div>
    </div>
</div>
<style>@media (max-width:767px){#packMetaGrid{grid-template-columns:minmax(0,1fr)!important}}</style>

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

            btnText.innerText = 'Đã sao chép';
            icon.className = 'bi bi-check-lg';

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
        document.getElementById('resultWarning').classList.add('d-none');
        renderGeneratedResult(generatedData);
    }

    // Direct Built-in / Gemini generation
    function handleGenerate(event) {
        event.preventDefault();
        const prompt = document.getElementById('promptInput').value.trim();
        const count = parseInt(document.getElementById('countSelect').value) || 6;
        const targetAge = document.getElementById('targetAgeSelect').value;

        if (!prompt) return;

        document.getElementById('loadingBox').classList.remove('d-none');
        document.getElementById('resultContainer').classList.add('d-none');
        document.getElementById('emptyResultBox').classList.add('d-none');
        document.getElementById('resultSavebar').classList.add('d-none');
        document.getElementById('resultWarning').classList.add('d-none');
        document.getElementById('btnGenerate').disabled = true;

        fetch('/api/ai/generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                prompt: prompt,
                count: count,
                target_age: targetAge
            })
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('loadingBox').classList.add('d-none');
            document.getElementById('btnGenerate').disabled = false;

            if (data.status === 'success' && data.data) {
                generatedData = data.data;
                renderGeneratedResult(generatedData);
                if (data.data.warning) {
                    const warn = document.getElementById('resultWarning');
                    warn.querySelector('span').textContent = data.data.warning;
                    warn.classList.remove('d-none');
                }
            } else {
                if (!generatedData) document.getElementById('emptyResultBox').classList.remove('d-none');
                alert('Có lỗi xảy ra: ' + (data.message || 'Không thể sinh nội dung'));
            }
        })
        .catch(err => {
            document.getElementById('loadingBox').classList.add('d-none');
            document.getElementById('btnGenerate').disabled = false;
            if (!generatedData) document.getElementById('emptyResultBox').classList.remove('d-none');
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
            cardCol.className = 'k-card';
            cardCol.id = 'itemCard_' + idx;

            cardCol.innerHTML = `
                <div class="k-ai-card">
                    <div style="display:grid;gap:8px;align-content:start">
                        <div class="k-image-box ${illustImg ? '' : 'is-empty'}" onclick="browseItemImage(${idx})" title="Đổi ảnh minh hoạ">
                            <img src="${escapeHtml(illustImg)}" id="itemImg_${idx}" alt="${escapeHtml(it.name_vi)}" ${illustImg ? '' : 'hidden'} onload="this.hidden=false;this.parentElement.classList.remove('is-empty');this.nextElementSibling?.remove()">
                            ${illustImg ? '' : '<span class="k-xs">Chưa có ảnh</span>'}
                        </div>
                        <div class="k-segment" role="group" aria-label="Ảnh đang xem">
                            <button type="button" class="is-active" id="btnModeIllust_${idx}" onclick="toggleCardImageMode(${idx}, 'illustration')">Minh hoạ</button>
                            <button type="button" id="btnModeReal_${idx}" onclick="toggleCardImageMode(${idx}, 'real')">Đời thực</button>
                        </div>
                    </div>
                    <div style="display:grid;gap:8px;min-width:0;align-content:start">
                        <div style="display:flex;gap:8px;align-items:flex-start">
                            <label class="k-hit" style="margin:-6px -4px -6px -8px" title="Giữ thẻ này khi lưu"><input class="form-check-input item-select-checkbox m-0" type="checkbox" id="checkItem_${idx}" ${it.is_selected !== false ? 'checked' : ''} onchange="updateSelectedCount()" aria-label="Giữ thẻ ${escapeHtml(it.name_vi)}"></label>
                            <div style="flex:1;min-width:0">
                                <div class="k-row-title" style="overflow-wrap:anywhere">${escapeHtml(it.name_vi || '')}</div>
                                <div class="k-muted" style="overflow-wrap:anywhere">${escapeHtml(it.name_en || '')}${it.phonics_en ? ' · ' + escapeHtml(it.phonics_en) : ''}</div>
                                ${it.sfx_sound ? `<div class="k-xs k-muted">${escapeHtml(it.sfx_sound)}</div>` : ''}
                            </div>
                            <button type="button" class="k-btn k-btn-ghost k-btn-sm" aria-expanded="false" aria-controls="itemEdit_${idx}" onclick="toggleItemEditor(this, ${idx})">Sửa</button>
                        </div>
                    </div>
                        <div id="itemEdit_${idx}" class="k-ai-edit" hidden>
                            <div class="k-grid-2">
                                <div class="k-field"><label class="k-mini-label" for="nameVi_${idx}">Tên tiếng Việt</label><input type="text" class="k-input" id="nameVi_${idx}" value="${escapeHtml(it.name_vi || '')}"></div>
                                <div class="k-field"><label class="k-mini-label" for="nameEn_${idx}">Tên tiếng Anh</label><input type="text" class="k-input" id="nameEn_${idx}" value="${escapeHtml(it.name_en || '')}"></div>
                            </div>
                            <div class="k-grid-2">
                                <div class="k-field"><label class="k-mini-label" for="phonics_${idx}">Phiên âm</label><input type="text" class="k-input" id="phonics_${idx}" value="${escapeHtml(it.phonics_en || '')}" placeholder="/ˈlaɪən/"></div>
                                <div class="k-field"><label class="k-mini-label" for="sfx_${idx}">Tiếng kêu</label><input type="text" class="k-input" id="sfx_${idx}" value="${escapeHtml(it.sfx_sound || '')}"></div>
                            </div>
                            <div class="k-field"><label class="k-mini-label" for="descVi_${idx}">Mô tả cho bé</label><textarea class="k-textarea" id="descVi_${idx}">${escapeHtml(it.description_vi || '')}</textarea></div>
                            <div class="k-field"><label class="k-mini-label" for="funFactVi_${idx}">Sự thật thú vị</label><input type="text" class="k-input" id="funFactVi_${idx}" value="${escapeHtml(it.fun_fact_vi || '')}"></div>
                            <div class="k-field"><label class="k-mini-label" for="promptVi_${idx}">Câu hỏi cha mẹ đố bé</label><input type="text" class="k-input" id="promptVi_${idx}" value="${escapeHtml(it.prompt_question_vi || '')}"></div>
                            <div class="k-field"><label class="k-mini-label" for="actionVi_${idx}">Gợi ý vận động</label><input type="text" class="k-input" id="actionVi_${idx}" value="${escapeHtml(it.action_hint_vi || '')}"></div>
                            <div class="k-grid-2">
                                <div class="k-field"><span class="k-mini-label">Ảnh 3D minh hoạ</span><div style="display:flex;gap:6px"><input type="text" class="k-input" id="imgInput_${idx}" value="${escapeHtml(illustImg)}" placeholder="URL ảnh" aria-label="URL ảnh 3D"><button type="button" class="k-btn k-btn-outline k-btn-icon" onclick="browseItemImage(${idx}, 'illustration')" aria-label="Chọn ảnh 3D" title="Chọn ảnh 3D"><i class="bi bi-image"></i></button></div></div>
                                <div class="k-field"><span class="k-mini-label">Ảnh đời thực</span><div style="display:flex;gap:6px"><input type="text" class="k-input" id="realImgInput_${idx}" value="${escapeHtml(realImg)}" placeholder="URL ảnh" aria-label="URL ảnh đời thực"><button type="button" class="k-btn k-btn-outline k-btn-icon" onclick="browseItemImage(${idx}, 'real')" aria-label="Chọn ảnh đời thực" title="Chọn ảnh đời thực"><i class="bi bi-camera"></i></button></div></div>
                            </div>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                                <button type="button" class="k-btn k-btn-outline k-btn-sm" onclick="playAudio('${escapeHtml(it.pronounce_vi_url || '')}', 'vi', document.getElementById('nameVi_${idx}').value)"><i class="bi bi-volume-up"></i>Nghe tiếng Việt</button>
                                <button type="button" class="k-btn k-btn-outline k-btn-sm" onclick="playAudio('${escapeHtml(it.pronounce_en_url || '')}', 'en', document.getElementById('nameEn_${idx}').value)"><i class="bi bi-volume-up"></i>Nghe tiếng Anh</button>
                                <label class="k-mini-label" for="youtube_${idx}" style="margin-left:auto">YouTube</label>
                                <input type="text" class="k-input k-mono" style="width:130px;min-height:32px;border-radius:8px" id="youtube_${idx}" value="${escapeHtml(it.youtube_video_id || 'VbZ07n242-g')}">
                            </div>
                        </div>
                </div>
            `;
            grid.appendChild(cardCol);
        });

        document.getElementById('resultContainer').classList.remove('d-none');
        document.getElementById('emptyResultBox').classList.add('d-none');
        document.getElementById('resultSavebar').classList.remove('d-none');
        updateSelectedCount();

        // Scroll smoothly to preview
        document.getElementById('resultContainer').scrollIntoView({ behavior: 'smooth' });
    }

    function toggleCardImageMode(idx, mode) {
        itemImageModes[idx] = mode;
        const img = document.getElementById('itemImg_' + idx);
        document.getElementById('btnModeIllust_' + idx).classList.toggle('is-active', mode !== 'real');
        document.getElementById('btnModeReal_' + idx).classList.toggle('is-active', mode === 'real');
        const url = document.getElementById(mode === 'real' ? 'realImgInput_' + idx : 'imgInput_' + idx).value;
        if (url) img.src = url;
    }

    // Mở / đóng phần sửa chi tiết của một thẻ
    function toggleItemEditor(button, idx) {
        const panel = document.getElementById('itemEdit_' + idx);
        panel.hidden = !panel.hidden;
        button.setAttribute('aria-expanded', String(!panel.hidden));
        button.textContent = panel.hidden ? 'Sửa' : 'Thu gọn';
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
        const query = nameEn || nameVi;

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
        document.getElementById('resultSavebar').classList.add('d-none');
        document.getElementById('emptyResultBox').classList.remove('d-none');
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
            const prompt3D = `Adorable cute baby ${nameEn}, friendly smiling face, big expressive sparkling eyes, smooth 3D Pixar Disney animation style, soft warm studio lighting, vibrant cheerful pastel colors, clean solid light pastel background, 3D claymation render, Octane render, 8k, volumetric lighting, children educational book illustration, centered, full body shot, landscape 1920x1080 pixels --ar 16:9 --v 6.0`;
            const promptReal = `Authentic high resolution wildlife photograph of a ${nameEn} in natural environment, National Geographic documentary style, sharp crisp focus on natural skin texture and gentle eyes, shot on 85mm lens f/2.8, photorealistic, beautiful natural daylight, detailed wildlife photography, clean uncluttered composition, landscape 1920x1080 pixels --ar 16:9 --v 6.0`;

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

    // Tab gạch dưới: đổi aria-selected theo tab đang mở
    document.querySelectorAll('#studioTabs [data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', () => {
            document.querySelectorAll('#studioTabs .k-tab').forEach(t => t.setAttribute('aria-selected', String(t === tab)));
        });
    });

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
