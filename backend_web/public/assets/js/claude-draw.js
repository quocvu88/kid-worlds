// Vẽ ảnh thẻ bằng Claude: máy chủ trả SVG, trình duyệt đổi sang PNG 1920×1080 rồi tải lên như ảnh thường.
// App Flutter chỉ đọc PNG/JPG nên không lưu SVG.
(function () {
    const WIDTH = 1920;
    const HEIGHT = 1080;

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });
        let data = null;
        try { data = await res.json(); } catch (e) { /* trả về không phải JSON */ }
        if (!res.ok || !data || data.status !== 'success') {
            if (res.status === 401) throw new Error('Phiên đăng nhập đã hết, tải lại trang để đăng nhập.');
            if (res.status === 504) throw new Error('Máy chủ chờ Claude quá lâu. Thử lại.');
            throw new Error((data && data.message) || ('Lỗi máy chủ (' + res.status + ')'));
        }
        return data;
    }

    async function svgToPngBlob(svg) {
        const svgUrl = URL.createObjectURL(new Blob([svg], { type: 'image/svg+xml' }));
        try {
            const img = new Image();
            img.src = svgUrl;
            await img.decode();
            const canvas = document.createElement('canvas');
            canvas.width = WIDTH;
            canvas.height = HEIGHT;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ffffff'; // nền trắng cho chỗ SVG để trong suốt
            ctx.fillRect(0, 0, WIDTH, HEIGHT);
            ctx.drawImage(img, 0, 0, WIDTH, HEIGHT);
            return await new Promise((resolve, reject) =>
                canvas.toBlob(blob => blob ? resolve(blob) : reject(new Error('Không đổi được SVG sang PNG.')), 'image/png'));
        } finally {
            URL.revokeObjectURL(svgUrl);
        }
    }

    async function uploadPng(blob, baseName) {
        const form = new FormData();
        form.append('image', blob, (baseName || 'claude') + '.png');
        const res = await fetch('/api/upload-image', { method: 'POST', body: form });
        const data = await res.json().catch(() => null);
        if (!res.ok || !data || data.status !== 'success') {
            throw new Error((data && data.message) || 'Tải ảnh lên máy chủ thất bại.');
        }
        return data.url;
    }

    /**
     * @param {{nameEn: string, nameVi: string, style: 'illustration'|'coloring'|'cover', members?: string[]}} card
     *        style 'cover' = ảnh đại diện của gói; nameEn/nameVi là tên gói, members là tên các thẻ trong gói.
     * @returns {Promise<{url: string, inputTokens: number, outputTokens: number, model: string}>}
     */
    window.kwClaudeDraw = async function (card) {
        const { data } = await postJson('/api/ai/claude-image', {
            name_en: card.nameEn || '',
            name_vi: card.nameVi || '',
            style: card.style,
            members: card.members || [],
        });
        const png = await svgToPngBlob(data.svg);
        const slug = (card.nameEn || card.nameVi || 'the').toLowerCase().replace(/[^a-z0-9]+/g, '_').slice(0, 40);
        const url = await uploadPng(png, 'claude_' + card.style + '_' + slug);
        return { url, inputTokens: data.input_tokens, outputTokens: data.output_tokens, model: data.model };
    };

    /** Tìm ảnh chụp thật trên Wikimedia Commons cho thẻ; máy chủ tải về và gán luôn vào thẻ. */
    window.kwFetchRealPhoto = async function (itemId) {
        const { data } = await postJson('/api/items/' + encodeURIComponent(itemId) + '/real-photo', {});
        return data; // {url, source, title, artist, license}
    };

    window.kwSavePackThumbnail = function (packId, url) {
        return postJson('/api/packs/' + encodeURIComponent(packId) + '/thumbnail', { url });
    };

    /** Tăng phiên bản gói để app trên máy bé thấy bản cập nhật và tải ảnh mới. */
    window.kwBumpPackVersion = function (packId) {
        return postJson('/api/packs/' + encodeURIComponent(packId) + '/bump-version', {});
    };

    /** Gán ảnh đã tải lên cho thẻ: field 'image' (ảnh minh hoạ chính) hoặc 'coloring_outline'. */
    window.kwSaveItemImage = function (itemId, field, url) {
        return postJson('/api/items/' + encodeURIComponent(itemId) + '/image', { field, url });
    };
})();
