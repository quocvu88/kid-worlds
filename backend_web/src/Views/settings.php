<?php
$pageTitle = 'Cài đặt';
$messages = [
    'ai' => ['success', 'bi-check-circle-fill', 'Đã lưu cài đặt AI.'],
    'key_cleared' => ['warning', 'bi-trash3-fill', 'Đã xoá API key. Trang Tạo bằng AI sẽ dùng bộ thẻ mẫu cho tới khi có key mới.'],
    'password' => ['success', 'bi-check-circle-fill', 'Đã đổi mật khẩu.'],
    'claude' => ['success', 'bi-check-circle-fill', 'Đã lưu cài đặt Claude.'],
    'claude_cleared' => ['warning', 'bi-trash3-fill', 'Đã xoá Claude API key. Soạn thẻ và vẽ ảnh bằng Claude sẽ tắt cho tới khi có key mới.'],
];
$msg = $messages[$_GET['saved'] ?? ''] ?? null;
?>
<div class="k-stack" style="max-width:720px">
    <?php if ($msg): ?>
        <div class="k-flash k-flash-<?= $msg[0] ?>" role="status"><i class="bi <?= $msg[1] ?>"></i><span><?= $msg[2] ?></span></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="k-banner" role="alert"><i class="bi bi-exclamation-circle"></i><span><?= kw_e($error) ?></span></div>
    <?php endif; ?>

    <section class="k-card" id="ai">
        <div class="k-card-head"><h2>AI tạo nội dung</h2></div>
        <form class="k-card-body" method="POST" action="/settings" style="display:grid;gap:16px">
            <input type="hidden" name="_csrf" value="<?= kw_e($csrf) ?>">
            <input type="hidden" name="action" value="save_ai">

            <?php if ($keySource === 'file'): ?>
                <div class="k-flash k-flash-success"><i class="bi bi-check-circle-fill"></i><span>Đang dùng key <span class="k-mono"><?= kw_e($maskedKey) ?></span> lưu trong cài đặt.</span></div>
            <?php elseif ($keySource === 'env'): ?>
                <div class="k-flash k-flash-info"><i class="bi bi-info-circle-fill"></i><span>Đang dùng key <span class="k-mono"><?= kw_e($maskedKey) ?></span> từ biến môi trường GEMINI_API_KEY. Key lưu ở đây sẽ được ưu tiên.</span></div>
            <?php else: ?>
                <div class="k-flash k-flash-warning"><i class="bi bi-exclamation-triangle-fill"></i><span>Chưa có API key. Trang Tạo bằng AI đang trả về bộ thẻ mẫu, không phải nội dung AI tạo theo chủ đề.</span></div>
            <?php endif; ?>

            <div class="k-field">
                <label class="k-label" for="geminiKey">Gemini API key</label>
                <input class="k-input k-mono" id="geminiKey" name="gemini_api_key" type="password" autocomplete="off" spellcheck="false"
                       placeholder="<?= $keySource === 'file' ? 'Để trống thì giữ key đang lưu' : 'AIza…' ?>">
                <span class="k-hint">Tạo key ở Google AI Studio, key bắt đầu bằng AIza. Key chỉ lưu trên máy chủ, trong data/secrets.json, không hiện lại sau khi lưu.</span>
            </div>
            <div class="k-field">
                <label class="k-label" for="geminiModel">Model</label>
                <input class="k-input k-mono" id="geminiModel" name="gemini_model" type="text" value="<?= kw_e($model) ?>" placeholder="gemini-2.5-flash">
                <span class="k-hint">Tên model Gemini dùng để soạn thẻ. Để trống thì dùng gemini-2.5-flash.</span>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <button class="k-btn k-btn-primary" type="submit">Lưu cài đặt AI</button>
                <?php if ($keySource === 'file'): ?>
                    <button class="k-btn k-btn-danger" type="submit" name="action" value="clear_key" onclick="return confirm('Xoá API key đang lưu? Trang Tạo bằng AI sẽ dùng bộ thẻ mẫu cho tới khi có key mới.');"><i class="bi bi-trash3"></i>Xoá key</button>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="k-card" id="claude">
        <div class="k-card-head"><h2>Claude soạn thẻ và vẽ ảnh</h2></div>
        <form class="k-card-body" method="POST" action="/settings" style="display:grid;gap:16px">
            <input type="hidden" name="_csrf" value="<?= kw_e($csrf) ?>">
            <input type="hidden" name="action" value="save_claude">

            <?php if ($claudeKeySource === 'file'): ?>
                <div class="k-flash k-flash-success"><i class="bi bi-check-circle-fill"></i><span>Đang dùng key <span class="k-mono"><?= kw_e($claudeMaskedKey) ?></span> lưu trong cài đặt.</span></div>
            <?php elseif ($claudeKeySource === 'env'): ?>
                <div class="k-flash k-flash-info"><i class="bi bi-info-circle-fill"></i><span>Đang dùng key <span class="k-mono"><?= kw_e($claudeMaskedKey) ?></span> từ biến môi trường ANTHROPIC_API_KEY. Key lưu ở đây sẽ được ưu tiên.</span></div>
            <?php else: ?>
                <div class="k-flash k-flash-warning"><i class="bi bi-exclamation-triangle-fill"></i><span>Chưa có Claude API key nên chưa dùng Claude để soạn thẻ hay vẽ ảnh được.</span></div>
            <?php endif; ?>

            <p class="k-muted" style="margin:0;max-width:65ch">Claude soạn bộ thẻ ở trang Tạo bằng AI (có cả key Gemini thì chọn được dùng AI nào khi tạo), và vẽ tranh minh hoạ vector phẳng cùng tranh nét tô màu khổ ngang 1920×1080. CMS tự đổi ảnh sang PNG trước khi lưu, app không cần cập nhật. Claude không vẽ ảnh thật, ô Ảnh thật vẫn lấy từ kho ảnh.</p>

            <div class="k-field">
                <label class="k-label" for="anthropicKey">Claude API key</label>
                <input class="k-input k-mono" id="anthropicKey" name="anthropic_api_key" type="password" autocomplete="off" spellcheck="false"
                       placeholder="<?= $claudeKeySource === 'file' ? 'Để trống thì giữ key đang lưu' : 'sk-ant-…' ?>">
                <span class="k-hint">Tạo key ở Claude Console (console.anthropic.com), key bắt đầu bằng sk-ant-. Lưu trong data/secrets.json, không hiện lại sau khi lưu.</span>
            </div>
            <div class="k-field">
                <label class="k-label" for="claudeModel">Model</label>
                <input class="k-input k-mono" id="claudeModel" name="claude_model" type="text" value="<?= kw_e($claudeModel) ?>" placeholder="claude-opus-5-5">
                <span class="k-hint">Để trống thì dùng claude-opus-5-5. Muốn rẻ hơn thì thử claude-sonnet-5-5, nét vẽ có thể đơn giản hơn.</span>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <button class="k-btn k-btn-primary" type="submit">Lưu cài đặt Claude</button>
                <?php if ($claudeKeySource === 'file'): ?>
                    <button class="k-btn k-btn-danger" type="submit" name="action" value="clear_claude_key" onclick="return confirm('Xoá Claude API key đang lưu?');"><i class="bi bi-trash3"></i>Xoá key</button>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="k-card" id="account">
        <div class="k-card-head"><h2>Tài khoản</h2><span class="k-muted" style="margin-left:auto">Đang đăng nhập: <b style="color:var(--foreground)"><?= kw_e($currentUser) ?></b></span></div>
        <form class="k-card-body" method="POST" action="/settings" style="display:grid;gap:16px">
            <input type="hidden" name="_csrf" value="<?= kw_e($csrf) ?>">
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="username" value="<?= kw_e($currentUser) ?>" autocomplete="username">
            <div class="k-field"><label class="k-label" for="pwCurrent">Mật khẩu hiện tại</label><input class="k-input" id="pwCurrent" name="current_password" type="password" autocomplete="current-password" required></div>
            <div class="k-grid-2">
                <div class="k-field"><label class="k-label" for="pwNew">Mật khẩu mới</label><input class="k-input" id="pwNew" name="new_password" type="password" autocomplete="new-password" minlength="8" required><span class="k-hint">Ít nhất 8 ký tự</span></div>
                <div class="k-field"><label class="k-label" for="pwConfirm">Nhập lại mật khẩu mới</label><input class="k-input" id="pwConfirm" name="new_password_confirm" type="password" autocomplete="new-password" minlength="8" required></div>
            </div>
            <div><button class="k-btn k-btn-outline" type="submit">Đổi mật khẩu</button></div>
        </form>
    </section>
</div>
