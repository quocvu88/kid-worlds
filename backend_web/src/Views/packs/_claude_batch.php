<?php
// Hộp thoại tạo bộ ảnh cho gói: Claude vẽ ảnh đại diện, ảnh minh hoạ, tranh tô màu; ảnh thật lấy từ Wikimedia Commons.
// Cần $pack, $items.
$claudeReady = \KidsWorld\Backend\Services\ClaudeImageService::isConfigured();
$claudeCards = array_map(fn($it) => [
    'id' => $it['id'],
    'nameEn' => $it['name_en'] ?? '',
    'nameVi' => $it['name_vi'] ?? '',
    'hasImage' => kw_valid_image_url(kw_first_image($it['images_json'] ?? '')),
    'hasLine' => kw_valid_image_url($it['coloring_outline_url'] ?? ''),
    'hasReal' => kw_valid_image_url($it['real_image_url'] ?? ''),
], $items);
$claudePack = [
    'id' => $pack['id'],
    'nameEn' => $pack['title_en'] ?? '',
    'nameVi' => $pack['title_vi'] ?? '',
    'hasThumb' => kw_valid_image_url($pack['thumbnail_url'] ?? ''),
];
$kindOptions = [
    ['cbCover', 'Ảnh đại diện của bộ', 'Claude vẽ', $claudeReady],
    ['cbIllustration', 'Ảnh minh hoạ', 'Claude vẽ', $claudeReady],
    ['cbColoring', 'Tranh tô màu', 'Claude vẽ', $claudeReady],
    ['cbReal', 'Ảnh thật', 'Wikimedia Commons', true],
];
?>
<div class="modal fade" id="claudeBatchModal" tabindex="-1" aria-labelledby="claudeBatchTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="padding:16px 20px">
                <h2 class="k-base" id="claudeBatchTitle" style="font-weight:600;margin:0">Tạo bộ ảnh</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng" data-claude-close></button>
            </div>
            <div class="modal-body" style="padding:20px;display:grid;gap:16px">
                <div class="k-grid-2" style="gap:8px">
                    <?php foreach ($kindOptions as [$id, $label, $source, $enabled]): ?>
                        <label class="k-choice" style="min-height:48px<?= $enabled ? '' : ';opacity:.55;cursor:not-allowed' ?>">
                            <input type="checkbox" id="<?= $id ?>" <?= $enabled ? 'checked' : 'disabled data-unavailable' ?>>
                            <span><span class="k-row-title"><?= $label ?></span><span class="k-xs k-muted" style="display:block"><?= $source ?></span></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if (!$claudeReady): ?>
                    <p class="k-muted k-xs" style="margin:0">Muốn Claude vẽ ảnh đại diện, ảnh minh hoạ và tranh tô màu thì <a href="/settings#claude" style="color:var(--foreground);font-weight:500;text-decoration:underline">thêm Claude API key ở Cài đặt</a>.</p>
                <?php endif; ?>
                <label class="form-check" style="margin:0"><input class="form-check-input" type="checkbox" id="cbRedraw"> <span class="form-check-label">Làm lại cả ảnh đã có</span></label>
                <p class="k-muted" style="margin:0" id="claudeBatchSummary"></p>
                <div class="k-list" style="border:1px solid var(--border);border-radius:12px" id="claudeBatchList"></div>
            </div>
            <div class="modal-footer" style="padding:12px 20px;gap:8px">
                <span class="k-muted" style="margin-right:auto" id="claudeBatchProgress"></span>
                <button type="button" class="k-btn k-btn-outline" data-bs-dismiss="modal" data-claude-close>Đóng</button>
                <button type="button" class="k-btn k-btn-outline d-none" id="claudeBatchStop">Dừng</button>
                <button type="button" class="k-btn k-btn-primary" id="claudeBatchStart">Bắt đầu</button>
                <button type="button" class="k-btn k-btn-primary d-none" id="claudeBatchReload" onclick="location.reload()">Tải lại để xem</button>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/claude-draw.js"></script>
<script>
(function () {
    const cards = <?= json_encode($claudeCards, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const pack = <?= json_encode($claudePack, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const KIND = {
        cover: { label: 'Ảnh đại diện của bộ', icon: 'bi-image-alt', queue: 'claude' },
        illustration: { label: 'Ảnh minh hoạ', icon: 'bi-image', queue: 'claude' },
        coloring: { label: 'Tranh tô màu', icon: 'bi-palette', queue: 'claude' },
        real: { label: 'Ảnh thật', icon: 'bi-camera', queue: 'photo' },
    };
    // Claude vẽ 2 ảnh cùng lúc; Wikimedia lấy lần lượt từng ảnh để không bị giới hạn tốc độ
    const WORKERS = { claude: 2, photo: 1 };
    const el = id => document.getElementById(id);
    let jobs = [];
    let running = false;
    let stopRequested = false;
    let versionNote = '';

    function escapeText(text) {
        const span = document.createElement('span');
        span.textContent = text == null ? '' : String(text);
        return span.innerHTML;
    }

    function checked(id) {
        const box = el(id);
        return box && !box.hasAttribute('data-unavailable') && box.checked;
    }

    function buildJobs() {
        const redraw = el('cbRedraw').checked;
        jobs = [];
        if (checked('cbCover') && (redraw || !pack.hasThumb)) jobs.push({ kind: 'cover', title: pack.nameVi || pack.nameEn, state: 'wait' });
        cards.forEach(card => {
            const title = card.nameVi || card.nameEn;
            if (checked('cbIllustration') && (redraw || !card.hasImage)) jobs.push({ kind: 'illustration', card, title, state: 'wait' });
            if (checked('cbColoring') && (redraw || !card.hasLine)) jobs.push({ kind: 'coloring', card, title, state: 'wait' });
            if (checked('cbReal') && (redraw || !card.hasReal)) jobs.push({ kind: 'real', card, title, state: 'wait' });
        });
        versionNote = '';
        render();
    }

    function badge(job) {
        switch (job.state) {
            case 'run': return '<span class="k-badge k-badge-primary"><span class="spinner-border spinner-border-sm" style="width:10px;height:10px;border-width:2px"></span>' + (KIND[job.kind].queue === 'photo' ? 'Đang tìm' : 'Đang vẽ') + '</span>';
            case 'done': return '<span class="k-badge k-badge-success"><i class="bi bi-check2"></i>Đã lưu</span>';
            case 'error': return '<span class="k-badge k-badge-danger">Lỗi</span>';
            case 'skip': return '<span class="k-badge k-badge-neutral">Đã dừng</span>';
            default: return '<span class="k-badge k-badge-neutral">Chờ</span>';
        }
    }

    function detail(job) {
        if (job.error) return ' · <span style="color:var(--error-text)">' + escapeText(job.error) + '</span>';
        if (job.credit) {
            const who = job.credit.artist ? escapeText(job.credit.artist.slice(0, 40)) + ', ' : '';
            return ' · <a href="' + escapeText(job.credit.source) + '" target="_blank" rel="noopener" style="color:inherit;text-decoration:underline">' + who + escapeText(job.credit.license || 'Wikimedia Commons') + '</a>';
        }
        return '';
    }

    function render() {
        const list = el('claudeBatchList');
        if (!jobs.length) {
            list.innerHTML = '<p class="k-empty" style="margin:0">Không còn ảnh nào thiếu ở các loại đã chọn. Chọn "Làm lại cả ảnh đã có" nếu muốn làm lại.</p>';
        } else {
            list.innerHTML = jobs.map((job, i) => {
                const fit = job.kind === 'real' ? 'object-fit:cover' : 'object-fit:contain;background:#fff;border:1px solid var(--border)';
                const preview = job.url
                    ? '<img class="k-thumb" src="' + escapeText(job.url) + '" alt="" style="' + fit + '">'
                    : '<span class="k-thumb"><i class="bi ' + KIND[job.kind].icon + '"></i></span>';
                return '<div class="k-list-row" data-job="' + i + '">' + preview
                    + '<div class="k-truncate" style="flex:1"><div class="k-row-title k-truncate">' + escapeText(job.title) + '</div>'
                    + '<div class="k-xs k-muted k-truncate">' + KIND[job.kind].label + detail(job) + '</div></div>'
                    + badge(job) + '</div>';
            }).join('');
        }
        const claudeCount = jobs.filter(j => KIND[j.kind].queue === 'claude').length;
        const photoCount = jobs.length - claudeCount;
        const parts = [];
        if (claudeCount) parts.push(claudeCount + ' ảnh Claude vẽ (30–90 giây mỗi ảnh, 2 ảnh cùng lúc)');
        if (photoCount) parts.push(photoCount + ' ảnh thật từ Wikimedia (vài giây mỗi ảnh)');
        el('claudeBatchSummary').textContent = jobs.length
            ? 'Sẽ tạo ' + parts.join(' và ') + '. Ảnh xong được lưu ngay vào gói, khổ ngang.'
            : '';

        const done = jobs.filter(j => j.state === 'done').length;
        const failed = jobs.filter(j => j.state === 'error').length;
        const remaining = jobs.some(j => j.state === 'wait' || j.state === 'error' || j.state === 'skip');
        el('claudeBatchProgress').textContent = (running || done || failed)
            ? 'Xong ' + done + '/' + jobs.length + (failed ? ', lỗi ' + failed : '') + (versionNote ? '. ' + versionNote : '')
            : '';
        el('claudeBatchStart').disabled = !remaining;
        el('claudeBatchStart').textContent = jobs.some(j => j.state === 'error' || j.state === 'skip') && !running ? 'Làm tiếp phần còn lại' : 'Bắt đầu';
        el('claudeBatchStart').classList.toggle('d-none', running || (done > 0 && !remaining));
        el('claudeBatchStop').classList.toggle('d-none', !running);
        el('claudeBatchReload').classList.toggle('d-none', running || !done);
        ['cbCover', 'cbIllustration', 'cbColoring', 'cbReal', 'cbRedraw'].forEach(id => {
            const box = el(id);
            if (box) box.disabled = box.hasAttribute('data-unavailable') || running || done > 0;
        });
        document.querySelectorAll('[data-claude-close]').forEach(b => b.disabled = running);
    }

    async function runJob(job) {
        if (job.kind === 'real') {
            const photo = await kwFetchRealPhoto(job.card.id); // máy chủ tải ảnh và gán luôn vào thẻ
            job.url = photo.url;
            job.credit = photo;
            return;
        }
        if (job.kind === 'cover') {
            const result = await kwClaudeDraw({ nameEn: pack.nameEn, nameVi: pack.nameVi, style: 'cover', members: cards.map(c => c.nameEn || c.nameVi) });
            await kwSavePackThumbnail(pack.id, result.url);
            job.url = result.url;
            return;
        }
        const result = await kwClaudeDraw({ nameEn: job.card.nameEn, nameVi: job.card.nameVi, style: job.kind });
        await kwSaveItemImage(job.card.id, job.kind === 'illustration' ? 'image' : 'coloring_outline', result.url);
        job.url = result.url;
    }

    async function worker(queue) {
        while (!stopRequested) {
            const job = jobs.find(j => j.state === 'wait' && KIND[j.kind].queue === queue);
            if (!job) return;
            job.state = 'run';
            job.error = '';
            render();
            try {
                await runJob(job);
                job.state = 'done';
            } catch (err) {
                job.state = 'error';
                job.error = err.message;
            }
            render();
        }
    }

    el('claudeBatchStart').addEventListener('click', async () => {
        jobs.forEach(j => { if (j.state === 'error' || j.state === 'skip') j.state = 'wait'; });
        running = true;
        stopRequested = false;
        el('claudeBatchStop').disabled = false;
        render();
        const workers = [];
        Object.entries(WORKERS).forEach(([queue, count]) => { for (let i = 0; i < count; i++) workers.push(worker(queue)); });
        await Promise.all(workers);
        jobs.forEach(j => { if (j.state === 'wait') j.state = 'skip'; });
        if (jobs.some(j => j.state === 'done')) {
            try {
                const res = await kwBumpPackVersion(pack.id);
                versionNote = 'Gói lên v' + res.version + ', app sẽ thấy bản cập nhật';
            } catch (err) {
                versionNote = 'Chưa tăng được phiên bản gói: ' + err.message;
            }
        }
        running = false;
        render();
    });
    el('claudeBatchStop').addEventListener('click', () => {
        stopRequested = true;
        el('claudeBatchStop').disabled = true;
        el('claudeBatchProgress').textContent = 'Đang chờ các ảnh đang làm xong rồi dừng…';
    });
    ['cbCover', 'cbIllustration', 'cbColoring', 'cbReal', 'cbRedraw'].forEach(id => el(id)?.addEventListener('change', buildJobs));
    el('claudeBatchModal').addEventListener('show.bs.modal', () => { if (!running && !jobs.some(j => j.state === 'done')) buildJobs(); });
    window.addEventListener('beforeunload', e => { if (running) { e.preventDefault(); e.returnValue = ''; } });
})();
</script>
