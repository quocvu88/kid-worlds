<?php
// Hộp thoại "Viết lại bằng AI" cho trang Sửa gói. Cần $pack, $items.
$rewriteProviders = \KidsWorld\Backend\Services\AiService::availableProviders();
$providerNames = ['gemini' => 'Gemini', 'claude' => 'Claude'];
$packFields = \KidsWorld\Backend\Services\AiRewriteService::PACK_FIELDS;
$itemFields = \KidsWorld\Backend\Services\AiRewriteService::ITEM_FIELDS;
$rewriteCurrent = [
    'pack' => array_map(fn($f) => (string)($pack[$f] ?? ''), array_combine(array_keys($packFields), array_keys($packFields))),
    'items' => array_map(function ($it) use ($itemFields) {
        $row = ['id' => $it['id'], 'name' => $it['name_vi'] ?: $it['name_en']];
        foreach (array_keys($itemFields) as $f) $row[$f] = (string)($it[$f] ?? '');
        return $row;
    }, $items),
];
?>
<div class="modal fade" id="aiRewriteModal" tabindex="-1" aria-labelledby="aiRewriteTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="padding:16px 20px">
                <h2 class="k-base" id="aiRewriteTitle" style="font-weight:600;margin:0">Viết lại bằng AI</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng" data-rw-close></button>
            </div>

            <!-- Bước 1: chọn phần cần viết lại -->
            <div class="modal-body" id="rwStepOptions" style="padding:20px;display:grid;gap:16px">
                <?php if (!$rewriteProviders): ?>
                    <div class="k-flash k-flash-warning"><i class="bi bi-exclamation-triangle-fill"></i><span>Chưa có API key Gemini hay Claude. <a href="/settings" style="color:var(--foreground);font-weight:500;text-decoration:underline">Thêm key ở Cài đặt</a></span></div>
                <?php endif; ?>
                <div class="k-grid-2" style="gap:8px">
                    <label class="k-choice"><input type="checkbox" id="rwPack" checked>
                        <span><span class="k-row-title">Thông tin gói</span><span class="k-xs k-muted" style="display:block">Tên và mô tả tiếng Việt, tiếng Anh</span></span></label>
                    <label class="k-choice"><input type="checkbox" id="rwItems" <?= $items ? 'checked' : 'disabled' ?>>
                        <span><span class="k-row-title">Nội dung <?= count($items) ?> thẻ</span><span class="k-xs k-muted" style="display:block">Mô tả, sự thật thú vị, câu đố, vận động, phiên âm, tiếng kêu. Tên thẻ giữ nguyên</span></span></label>
                </div>
                <div class="k-field">
                    <label class="k-label" for="rwInstruction">Yêu cầu thêm <span class="k-muted" style="font-weight:400">(không bắt buộc)</span></label>
                    <textarea class="k-textarea" id="rwInstruction" maxlength="1000" placeholder="Ví dụ: viết ngắn hơn cho bé 3 tuổi; thêm câu hỏi đếm số; giọng kể chuyện vui"></textarea>
                </div>
                <?php if (count($rewriteProviders) > 1): ?>
                    <fieldset class="k-field" style="border:0;margin:0;padding:0">
                        <legend class="k-label" style="float:none;margin-bottom:6px;font-size:14px">AI viết lại</legend>
                        <div class="k-grid-2">
                            <?php foreach ($rewriteProviders as $key => $model): ?>
                                <label class="k-choice"><input type="radio" name="rwProvider" value="<?= $key ?>" <?= $key === array_key_first($rewriteProviders) ? 'checked' : '' ?>>
                                    <span><span class="k-row-title"><?= $providerNames[$key] ?></span><span class="k-xs k-muted k-mono k-truncate" style="display:block"><?= kw_e($model) ?></span></span></label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php elseif ($rewriteProviders): $only = array_key_first($rewriteProviders); ?>
                    <input type="hidden" name="rwProvider" value="<?= $only ?>">
                    <p class="k-muted" style="margin:0">Dùng <?= $providerNames[$only] ?> <span class="k-mono"><?= kw_e($rewriteProviders[$only]) ?></span>.</p>
                <?php endif; ?>
                <div class="k-banner d-none" id="rwError" role="alert"><i class="bi bi-exclamation-circle"></i><span></span></div>
            </div>

            <!-- Bước 2: đang viết -->
            <div class="modal-body d-none" id="rwStepLoading" style="padding:40px 20px;text-align:center" aria-live="polite">
                <span class="spinner-border text-primary" role="status"></span>
                <p style="margin:12px 0 0;font-weight:500" id="rwLoadingTitle">AI đang viết lại</p>
                <p class="k-muted" style="margin:4px 0 0" id="rwLoadingSub"></p>
            </div>

            <!-- Bước 3: xem trước và chọn thay đổi -->
            <div class="modal-body d-none" id="rwStepPreview" style="padding:20px;display:grid;gap:12px">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <span class="k-muted" id="rwPreviewSummary" style="flex:1"></span>
                    <button type="button" class="k-btn k-btn-ghost k-btn-sm" id="rwSelectAll">Chọn tất cả</button>
                    <button type="button" class="k-btn k-btn-ghost k-btn-sm" id="rwSelectNone">Bỏ chọn</button>
                </div>
                <div id="rwPreviewList" style="display:grid;gap:12px"></div>
            </div>

            <div class="modal-footer" style="padding:12px 20px;gap:8px">
                <button type="button" class="k-btn k-btn-outline" data-bs-dismiss="modal" data-rw-close id="rwCancel">Huỷ</button>
                <button type="button" class="k-btn k-btn-outline d-none" id="rwBack">Viết lại lần nữa</button>
                <button type="button" class="k-btn k-btn-primary" id="rwRun" <?= $rewriteProviders ? '' : 'disabled' ?>><i class="bi bi-magic"></i>Viết lại</button>
                <button type="button" class="k-btn k-btn-primary d-none" id="rwApply">Áp dụng</button>
            </div>
        </div>
    </div>
</div>

<style>
    .k-rw-group { border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
    .k-rw-group-head { padding: 10px 16px; background: var(--surface-hover); font-weight: 600; border-bottom: 1px solid var(--border); }
    .k-rw-row { display: flex; gap: 12px; align-items: flex-start; padding: 12px 16px; border-bottom: 1px solid var(--border); cursor: pointer; margin: 0; }
    .k-rw-row:last-child { border-bottom: 0; }
    .k-rw-row input { margin-top: 3px; flex-shrink: 0; }
    .k-rw-old { color: var(--muted); text-decoration: line-through; text-decoration-color: color-mix(in srgb, var(--muted) 50%, transparent); }
    .k-rw-new { color: var(--foreground); }
    .k-rw-row:has(input:not(:checked)) .k-rw-new { color: var(--muted); }
</style>

<script>
(function () {
    const current = <?= json_encode($rewriteCurrent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const PACK_LABELS = <?= json_encode($packFields, JSON_UNESCAPED_UNICODE) ?>;
    const ITEM_LABELS = <?= json_encode($itemFields, JSON_UNESCAPED_UNICODE) ?>;
    const packId = <?= json_encode($pack['id']) ?>;
    const el = id => document.getElementById(id);
    let changes = [];
    let busy = false;

    function escapeText(text) {
        const span = document.createElement('span');
        span.textContent = text == null ? '' : String(text);
        return span.innerHTML;
    }

    function step(name) {
        ['Options', 'Loading', 'Preview'].forEach(s => el('rwStep' + s).classList.toggle('d-none', s !== name));
        el('rwRun').classList.toggle('d-none', name !== 'Options');
        el('rwBack').classList.toggle('d-none', name !== 'Preview');
        el('rwApply').classList.toggle('d-none', name !== 'Preview');
        el('rwCancel').classList.toggle('d-none', name === 'Loading');
        document.querySelectorAll('[data-rw-close]').forEach(b => b.disabled = name === 'Loading' || busy);
    }

    function showError(message) {
        const box = el('rwError');
        box.querySelector('span').textContent = message;
        box.classList.remove('d-none');
    }

    function selectedCount() {
        return document.querySelectorAll('#rwPreviewList input[type=checkbox]:checked').length;
    }

    function updateApplyLabel() {
        const n = selectedCount();
        el('rwApply').textContent = n ? 'Áp dụng ' + n + ' thay đổi' : 'Chọn thay đổi để áp dụng';
        el('rwApply').disabled = n === 0 || busy;
        el('rwPreviewSummary').textContent = changes.length + ' chỗ AI đề xuất sửa, đã chọn ' + n + '.';
    }

    function renderPreview(data) {
        changes = [];
        const groups = [];
        if (data.pack && Object.keys(data.pack).length) {
            const rows = [];
            Object.entries(data.pack).forEach(([field, value]) => {
                if (value === (current.pack[field] || '')) return;
                const index = changes.push({ scope: 'pack', field, value }) - 1;
                rows.push({ index, label: PACK_LABELS[field], oldValue: current.pack[field], value });
            });
            if (rows.length) groups.push({ title: 'Thông tin gói', rows });
        }
        (data.items || []).forEach(item => {
            const before = current.items.find(i => i.id === item.id);
            if (!before) return;
            const rows = [];
            Object.entries(item).forEach(([field, value]) => {
                if (field === 'id' || !(field in ITEM_LABELS) || value === (before[field] || '')) return;
                const index = changes.push({ scope: 'item', id: item.id, field, value }) - 1;
                rows.push({ index, label: ITEM_LABELS[field], oldValue: before[field], value });
            });
            if (rows.length) groups.push({ title: before.name, rows });
        });

        el('rwPreviewList').innerHTML = groups.length ? groups.map(g =>
            '<section class="k-rw-group"><div class="k-rw-group-head">' + escapeText(g.title) + '</div>'
            + g.rows.map(r => '<label class="k-rw-row"><input class="form-check-input" type="checkbox" checked data-change="' + r.index + '">'
                + '<div style="flex:1;min-width:0;display:grid;gap:4px"><div class="k-xs k-muted" style="font-weight:500">' + escapeText(r.label) + '</div>'
                + (r.oldValue ? '<div class="k-rw-old">' + escapeText(r.oldValue) + '</div>' : '<div class="k-xs k-muted">(đang trống)</div>')
                + '<div class="k-rw-new">' + escapeText(r.value) + '</div></div></label>').join('')
            + '</section>').join('')
            : '<p class="k-empty" style="margin:0">AI giữ nguyên mọi nội dung, không có gì để thay đổi.</p>';
        document.querySelectorAll('#rwPreviewList input[type=checkbox]').forEach(cb => cb.addEventListener('change', updateApplyLabel));
        updateApplyLabel();
    }

    el('rwRun').addEventListener('click', async () => {
        const includePack = el('rwPack').checked;
        const includeItems = el('rwItems').checked && !el('rwItems').disabled;
        el('rwError').classList.add('d-none');
        if (!includePack && !includeItems) {
            showError('Chọn ít nhất một phần để viết lại.');
            return;
        }
        const providerInput = document.querySelector('input[name="rwProvider"]:checked') || document.querySelector('input[name="rwProvider"]');
        const provider = providerInput ? providerInput.value : '';
        const name = provider === 'claude' ? 'Claude' : 'Gemini';
        el('rwLoadingTitle').textContent = name + ' đang viết lại' + (includeItems ? ' ' + current.items.length + ' thẻ' : ' thông tin gói');
        el('rwLoadingSub').textContent = provider === 'claude' ? 'Thường mất 30–90 giây.' : 'Thường mất khoảng 20–40 giây.';
        busy = true;
        step('Loading');
        try {
            const res = await fetch('/api/packs/' + encodeURIComponent(packId) + '/ai-rewrite', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ provider, instruction: el('rwInstruction').value, pack: includePack, items: includeItems }),
            });
            const body = await res.json().catch(() => null);
            if (!res.ok || !body || body.status !== 'success') throw new Error((body && body.message) || 'Lỗi máy chủ (' + res.status + ')');
            busy = false;
            renderPreview(body.data);
            step('Preview');
        } catch (err) {
            busy = false;
            step('Options');
            showError(err.message);
        }
    });

    el('rwBack').addEventListener('click', () => step('Options'));
    el('rwSelectAll').addEventListener('click', () => { document.querySelectorAll('#rwPreviewList input[type=checkbox]').forEach(cb => cb.checked = true); updateApplyLabel(); });
    el('rwSelectNone').addEventListener('click', () => { document.querySelectorAll('#rwPreviewList input[type=checkbox]').forEach(cb => cb.checked = false); updateApplyLabel(); });

    el('rwApply').addEventListener('click', async () => {
        const payload = { pack: {}, items: [] };
        const byItem = {};
        document.querySelectorAll('#rwPreviewList input[type=checkbox]:checked').forEach(cb => {
            const change = changes[Number(cb.dataset.change)];
            if (change.scope === 'pack') payload.pack[change.field] = change.value;
            else (byItem[change.id] = byItem[change.id] || { id: change.id })[change.field] = change.value;
        });
        payload.items = Object.values(byItem);
        busy = true;
        updateApplyLabel();
        el('rwApply').textContent = 'Đang lưu…';
        try {
            const res = await fetch('/api/packs/' + encodeURIComponent(packId) + '/ai-rewrite/apply', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const body = await res.json().catch(() => null);
            if (!res.ok || !body || body.status !== 'success') throw new Error((body && body.message) || 'Lỗi máy chủ (' + res.status + ')');
            const tab = new URLSearchParams(location.search).get('tab') || 'items';
            location.href = location.pathname + '?tab=' + encodeURIComponent(tab) + '&updated=1';
        } catch (err) {
            busy = false;
            updateApplyLabel();
            el('rwPreviewSummary').textContent = 'Lưu thất bại: ' + err.message;
        }
    });

    el('aiRewriteModal').addEventListener('show.bs.modal', () => { if (!busy) step('Options'); });
})();
</script>
