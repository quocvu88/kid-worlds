<?php

namespace KidsWorld\Backend\Controllers;

use KidsWorld\Backend\Models\TopicPack;
use KidsWorld\Backend\Models\TopicItem;
use KidsWorld\Backend\Services\AiService;
use KidsWorld\Backend\Services\Auth;
use KidsWorld\Backend\Services\ClaudeImageService;
use KidsWorld\Backend\Services\RealPhotoService;
use KidsWorld\Backend\Services\AiRewriteService;
use KidsWorld\Backend\Services\Settings;

class AdminController {
    private function render(string $view, array $data = []): void {
        extract($data);
        $contentView = __DIR__ . '/../Views/' . $view . '.php';
        require __DIR__ . '/../Views/layout/main.php';
        exit;
    }

    private function redirect(string $url): void {
        header("Location: $url");
        exit;
    }

    /**
     * Sinh ID duy nhất từ tiền tố + tên (chỉ a-z0-9_). Nếu trùng thì thêm hậu tố _2, _3...
     * Tránh lỗi UNIQUE constraint (HTTP 500) khi tạo gói/thẻ trùng tên.
     */
    private function makeUniqueId(string $prefix, string $requested, string $name, callable $exists): string {
        $base = strtolower(trim($requested));
        if ($base === '') {
            $slug = preg_replace('/[^a-z0-9_]+/', '_', strtolower(trim($name)));
            $slug = trim(preg_replace('/_+/', '_', $slug), '_');
            $base = $prefix . ($slug !== '' ? $slug : substr(md5(uniqid('', true)), 0, 8));
        } else {
            $base = preg_replace('/[^a-z0-9_\-]/', '_', $base);
        }

        $id = $base;
        $n = 2;
        while ($exists($id)) {
            $id = $base . '_' . $n++;
        }
        return $id;
    }

    private function handleFileUpload(string $fieldName, string $folder): ?string {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES[$fieldName];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = [
            'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
            'audio' => ['mp3', 'wav', 'ogg', 'm4a', 'aac']
        ];

        $type = ($folder === 'audio') ? 'audio' : 'images';
        if (!in_array($ext, $allowedExts[$type])) {
            return null;
        }

        $uploadDir = __DIR__ . '/../../public/uploads/' . $folder . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = uniqid('kw_' . $folder . '_') . '.' . $ext;
        $target = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            return '/uploads/' . $folder . '/' . $filename;
        }
        return null;
    }

    public function dashboard(): void {
        $stats = TopicPack::stats();
        $recentPacks = TopicPack::all();

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
        $protocol = $isHttps ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
        $serverUrl = $protocol . $host;

        $this->render('dashboard', [
            'title' => 'Bảng Điều Khiển Quản Trị - Kids World',
            'stats' => $stats,
            'gaps' => TopicPack::contentGaps(),
            'packs' => $recentPacks,
            'localIp' => '192.168.2.6',
            'serverUrl' => $serverUrl,
            'currentHost' => $host,
        ]);
    }

    public function packs(): void {
        $packs = TopicPack::all();
        $this->render('packs/index', [
            'title' => 'Danh Mục Gói Chủ Đề',
            'packs' => $packs,
        ]);
    }

    public function createPack(): void {
        $this->render('packs/form', [
            'title' => 'Thêm Gói Chủ Đề Mới',
            'pack' => null,
            'items' => [],
            'isEdit' => false
        ]);
    }

    public function storePack(): void {
        $id = $this->makeUniqueId('topic_', $_POST['id'] ?? '', $_POST['title_en'] ?? '', fn($x) => TopicPack::find($x) !== null);

        $uploadedThumbnail = $this->handleFileUpload('thumbnail_file', 'images');
        $thumbnailUrl = $uploadedThumbnail ?: trim($_POST['thumbnail_url'] ?? '');

        $rawGames = $_POST['selected_games'] ?? ['coloring', 'memory_match'];
        if (!is_array($rawGames) || empty($rawGames)) {
            $rawGames = ['coloring', 'memory_match'];
        }
        $rawGames = array_slice($rawGames, 0, 3);
        $selectedGamesJson = json_encode(array_values($rawGames), JSON_UNESCAPED_UNICODE);

        $gameConfig = $_POST['game_config'] ?? [];
        $gameConfigJson = is_array($gameConfig) ? json_encode($gameConfig, JSON_UNESCAPED_UNICODE) : '{}';

        $data = [
            'id' => $id,
            'title_vi' => trim($_POST['title_vi'] ?? ''),
            'title_en' => trim($_POST['title_en'] ?? ''),
            'category' => trim($_POST['category'] ?? 'general'),
            'target_age_min' => (int)($_POST['target_age_min'] ?? 3),
            'target_age_max' => (int)($_POST['target_age_max'] ?? 10),
            'target_gender' => trim($_POST['target_gender'] ?? 'all'),
            'thumbnail_url' => $thumbnailUrl,
            'background_url' => trim($_POST['background_url'] ?? ''),
            'theme_color' => trim($_POST['theme_color'] ?? '#FF6584'),
            'description_vi' => trim($_POST['description_vi'] ?? ''),
            'description_en' => trim($_POST['description_en'] ?? ''),
            'version' => 1,
            'size_mb' => (float)($_POST['size_mb'] ?? 1.5),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'selected_games_json' => $selectedGamesJson,
            'game_config_json' => $gameConfigJson
        ];

        TopicPack::create($data);
        $this->redirect('/packs?created=1');
    }

    public function editPack(string $id): void {
        $pack = TopicPack::find($id);
        if (!$pack) {
            $this->redirect('/packs');
        }

        $items = TopicItem::byPack($id);

        $this->render('packs/form', [
            'title' => 'Chỉnh Sửa Gói: ' . $pack['title_vi'],
            'pack' => $pack,
            'items' => $items,
            'isEdit' => true
        ]);
    }

    public function updatePack(string $id): void {
        $pack = TopicPack::find($id);
        if (!$pack) {
            $this->redirect('/packs');
        }

        $uploadedThumbnail = $this->handleFileUpload('thumbnail_file', 'images');
        $thumbnailUrl = $uploadedThumbnail ?: (trim($_POST['thumbnail_url'] ?? '') ?: $pack['thumbnail_url']);

        $rawGames = $_POST['selected_games'] ?? ['coloring', 'memory_match'];
        if (!is_array($rawGames) || empty($rawGames)) {
            $rawGames = ['coloring', 'memory_match'];
        }
        $rawGames = array_slice($rawGames, 0, 3);
        $selectedGamesJson = json_encode(array_values($rawGames), JSON_UNESCAPED_UNICODE);

        $gameConfig = $_POST['game_config'] ?? [];
        $gameConfigJson = is_array($gameConfig) ? json_encode($gameConfig, JSON_UNESCAPED_UNICODE) : '{}';

        $data = [
            'title_vi' => trim($_POST['title_vi'] ?? ''),
            'title_en' => trim($_POST['title_en'] ?? ''),
            'category' => trim($_POST['category'] ?? 'general'),
            'target_age_min' => (int)($_POST['target_age_min'] ?? 3),
            'target_age_max' => (int)($_POST['target_age_max'] ?? 10),
            'target_gender' => trim($_POST['target_gender'] ?? 'all'),
            'thumbnail_url' => $thumbnailUrl,
            'background_url' => trim($_POST['background_url'] ?? '') ?: ($pack['background_url'] ?? ''),
            'theme_color' => trim($_POST['theme_color'] ?? '') ?: ($pack['theme_color'] ?? '#FF6584'),
            'description_vi' => trim($_POST['description_vi'] ?? ''),
            'description_en' => trim($_POST['description_en'] ?? ''),
            'size_mb' => (float)($_POST['size_mb'] ?? $pack['size_mb']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'selected_games_json' => $selectedGamesJson,
            'game_config_json' => $gameConfigJson
        ];

        TopicPack::update($id, $data);

        // Cập nhật tranh nét vẽ tô màu cho từng thẻ trong gói nếu có submit từ Tab 3
        if (isset($_POST['coloring_outline']) && is_array($_POST['coloring_outline'])) {
            foreach ($_POST['coloring_outline'] as $itemId => $outlineUrl) {
                $cleanUrl = trim($outlineUrl);
                $uploadFileKey = 'coloring_outline_file_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $itemId);
                $uploadedOutline = $this->handleFileUpload($uploadFileKey, 'images');
                if ($uploadedOutline) {
                    $cleanUrl = $uploadedOutline;
                }
                TopicItem::updateColoringOutline($itemId, $cleanUrl);
            }
        }

        $this->redirect('/packs?updated=1');
    }

    public function deletePack(string $id): void {
        TopicPack::delete($id);
        $this->redirect('/packs?deleted=1');
    }

    public function items(string $packId): void {
        $pack = TopicPack::find($packId);
        if (!$pack) {
            $this->redirect('/packs');
        }

        $items = TopicItem::byPack($packId);
        $this->render('items/index', [
            'title' => 'Thẻ Từ Vựng: ' . $pack['title_vi'],
            'pack' => $pack,
            'items' => $items,
        ]);
    }

    public function createItem(string $packId): void {
        $pack = TopicPack::find($packId);
        if (!$pack) {
            $this->redirect('/packs');
        }

        $this->render('items/form', [
            'title' => 'Thêm Thẻ Học Cho ' . $pack['title_vi'],
            'pack' => $pack,
            'item' => null,
            'isEdit' => false
        ]);
    }

    public function storeItem(string $packId): void {
        $pack = TopicPack::find($packId);
        if (!$pack) {
            $this->redirect('/packs');
        }

        $id = $this->makeUniqueId('item_', $_POST['id'] ?? '', $_POST['name_en'] ?? '', fn($x) => TopicItem::find($x) !== null);

        $uploadedImage = $this->handleFileUpload('image_file', 'images');
        $imageUrl = $uploadedImage ?: trim($_POST['image_url'] ?? '');
        $images = !empty($imageUrl) ? [$imageUrl] : [];

        $uploadedAudioVi = $this->handleFileUpload('audio_vi_file', 'audio');
        $pronounceVi = $uploadedAudioVi ?: trim($_POST['pronounce_vi_url'] ?? '');

        $uploadedAudioEn = $this->handleFileUpload('audio_en_file', 'audio');
        $pronounceEn = $uploadedAudioEn ?: trim($_POST['pronounce_en_url'] ?? '');

        $uploadedOutline = $this->handleFileUpload('coloring_outline_file', 'images');
        $coloringOutline = $uploadedOutline ?: trim($_POST['coloring_outline_url'] ?? '');

        $data = [
            'id' => $id,
            'pack_id' => $packId,
            'name_vi' => trim($_POST['name_vi'] ?? ''),
            'name_en' => trim($_POST['name_en'] ?? ''),
            'description_vi' => trim($_POST['description_vi'] ?? ''),
            'description_en' => trim($_POST['description_en'] ?? ''),
            'pronounce_vi_url' => $pronounceVi,
            'pronounce_en_url' => $pronounceEn,
            'images' => $images,
            'youtube_video_id' => trim($_POST['youtube_video_id'] ?? ''),
            'map_x' => (float)($_POST['map_x'] ?? 100),
            'map_y' => (float)($_POST['map_y'] ?? 100),
            'phonics_en' => trim($_POST['phonics_en'] ?? ''),
            'real_image_url' => trim($_POST['real_image_url'] ?? ''),
            'sfx_sound' => trim($_POST['sfx_sound'] ?? ''),
            'fun_fact_vi' => trim($_POST['fun_fact_vi'] ?? ''),
            'fun_fact_en' => trim($_POST['fun_fact_en'] ?? ''),
            'prompt_question_vi' => trim($_POST['prompt_question_vi'] ?? ''),
            'prompt_question_en' => trim($_POST['prompt_question_en'] ?? ''),
            'action_hint_vi' => trim($_POST['action_hint_vi'] ?? ''),
            'coloring_outline_url' => $coloringOutline,
            'sort_order' => (int)($_POST['sort_order'] ?? 0)
        ];

        TopicItem::create($data);
        $this->redirect('/packs/' . urlencode($packId) . '/items?created=1');
    }

    public function editItem(string $id): void {
        $item = TopicItem::find($id);
        if (!$item) {
            $this->redirect('/packs');
        }
        $pack = TopicPack::find($item['pack_id']);

        $this->render('items/form', [
            'title' => 'Sửa Thẻ: ' . $item['name_vi'],
            'pack' => $pack,
            'item' => $item,
            'siblings' => TopicItem::byPack($item['pack_id']),
            'isEdit' => true
        ]);
    }

    public function updateItem(string $id): void {
        $item = TopicItem::find($id);
        if (!$item) {
            $this->redirect('/packs');
        }

        $uploadedImage = $this->handleFileUpload('image_file', 'images');
        $rawImages = json_decode($item['images_json'] ?? '[]', true) ?: [];
        if ($uploadedImage) {
            $rawImages = [$uploadedImage];
        } elseif (!empty($_POST['image_url'])) {
            $rawImages = [trim($_POST['image_url'])];
        }

        $uploadedAudioVi = $this->handleFileUpload('audio_vi_file', 'audio');
        $pronounceVi = $uploadedAudioVi ?: (trim($_POST['pronounce_vi_url'] ?? '') ?: $item['pronounce_vi_url']);

        $uploadedAudioEn = $this->handleFileUpload('audio_en_file', 'audio');
        $pronounceEn = $uploadedAudioEn ?: (trim($_POST['pronounce_en_url'] ?? '') ?: $item['pronounce_en_url']);

        $uploadedOutline = $this->handleFileUpload('coloring_outline_file', 'images');
        $coloringOutline = $uploadedOutline ?: (trim($_POST['coloring_outline_url'] ?? '') ?: ($item['coloring_outline_url'] ?? ''));

        $data = [
            'name_vi' => trim($_POST['name_vi'] ?? ''),
            'name_en' => trim($_POST['name_en'] ?? ''),
            'description_vi' => trim($_POST['description_vi'] ?? ''),
            'description_en' => trim($_POST['description_en'] ?? ''),
            'pronounce_vi_url' => $pronounceVi,
            'pronounce_en_url' => $pronounceEn,
            'images' => $rawImages,
            'youtube_video_id' => trim($_POST['youtube_video_id'] ?? ''),
            'map_x' => (float)($_POST['map_x'] ?? $item['map_x']),
            'map_y' => (float)($_POST['map_y'] ?? $item['map_y']),
            'phonics_en' => trim($_POST['phonics_en'] ?? '') ?: ($item['phonics_en'] ?? ''),
            'real_image_url' => trim($_POST['real_image_url'] ?? '') ?: ($item['real_image_url'] ?? ''),
            'sfx_sound' => trim($_POST['sfx_sound'] ?? '') ?: ($item['sfx_sound'] ?? ''),
            'fun_fact_vi' => trim($_POST['fun_fact_vi'] ?? '') ?: ($item['fun_fact_vi'] ?? ''),
            'fun_fact_en' => trim($_POST['fun_fact_en'] ?? '') ?: ($item['fun_fact_en'] ?? ''),
            'prompt_question_vi' => trim($_POST['prompt_question_vi'] ?? '') ?: ($item['prompt_question_vi'] ?? ''),
            'prompt_question_en' => trim($_POST['prompt_question_en'] ?? '') ?: ($item['prompt_question_en'] ?? ''),
            'action_hint_vi' => trim($_POST['action_hint_vi'] ?? '') ?: ($item['action_hint_vi'] ?? ''),
            'coloring_outline_url' => $coloringOutline,
            'sort_order' => (int)($_POST['sort_order'] ?? $item['sort_order']),
            'pack_id' => $item['pack_id']
        ];

        TopicItem::update($id, $data);
        $this->redirect('/packs/' . urlencode($item['pack_id']) . '/items?updated=1');
    }

    public function deleteItem(string $id): void {
        $item = TopicItem::find($id);
        $packId = $item['pack_id'] ?? '';
        TopicItem::delete($id);
        $this->redirect('/packs/' . urlencode($packId) . '/items?deleted=1');
    }

    public function domainGuide(): void {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
        $baseUrl = $scheme . '://' . $host;

        $this->render('guide', [
            'title' => 'Hướng Dẫn Cấu Hình Domain Lưu Trữ Cho App',
            'currentUrl' => $baseUrl,
            'localIp' => '192.168.2.6',
            'emulatorUrl' => 'http://10.0.2.2:8000',
        ]);
    }

    public function docsSpecification(): void {
        $this->render('docs/specification', [
            'title' => 'Tiêu Chuẩn Thiết Kế Bộ Học Phần - Kids World Docs'
        ]);
    }

    public function aiGenerator(): void {
        $this->render('ai/generator', [
            'title' => 'Khởi Tạo Bộ Nội Dung Tự Động Bằng AI - Kids World CMS',
            'aiProviders' => AiService::availableProviders(),
        ]);
    }

    public function settings(?string $error = null): void {
        $fileKey = trim((string)Settings::get('gemini_api_key', ''));
        $envKey = trim((string)(getenv('GEMINI_API_KEY') ?: ''));
        $this->render('settings', [
            'title' => 'Cài đặt',
            'error' => $error,
            'csrf' => Auth::csrfToken(),
            'currentUser' => Auth::user(),
            'keySource' => $fileKey !== '' ? 'file' : ($envKey !== '' ? 'env' : 'none'),
            'maskedKey' => Settings::maskKey($fileKey !== '' ? $fileKey : $envKey),
            'model' => Settings::geminiModel(),
            'claudeKeySource' => trim((string)Settings::get('anthropic_api_key', '')) !== '' ? 'file' : (trim((string)(getenv('ANTHROPIC_API_KEY') ?: '')) !== '' ? 'env' : 'none'),
            'claudeMaskedKey' => Settings::maskKey(Settings::anthropicApiKey()),
            'claudeModel' => Settings::claudeModel(),
        ]);
    }

    public function saveSettings(): void {
        if (!Auth::checkCsrf()) {
            $this->settings('Phiên làm việc đã hết hạn, vui lòng thử lại.');
        }
        $action = $_POST['action'] ?? '';

        if ($action === 'save_ai') {
            $key = trim((string)($_POST['gemini_api_key'] ?? ''));
            $model = trim((string)($_POST['gemini_model'] ?? ''));
            if ($key !== '' && !str_starts_with($key, 'AIza')) {
                $this->settings('API key Gemini phải bắt đầu bằng AIza.');
            }
            if ($model !== '' && !preg_match('/^[a-zA-Z0-9._-]+$/', $model)) {
                $this->settings('Tên model chỉ gồm chữ, số, dấu chấm, gạch ngang.');
            }
            $values = ['gemini_model' => $model !== '' ? $model : null];
            if ($key !== '') {
                $values['gemini_api_key'] = $key; // để trống thì giữ key đang lưu
            }
            Settings::save($values);
            $this->redirect('/settings?saved=ai');
        }

        if ($action === 'save_claude') {
            $key = trim((string)($_POST['anthropic_api_key'] ?? ''));
            $model = trim((string)($_POST['claude_model'] ?? ''));
            if ($key !== '' && !str_starts_with($key, 'sk-ant-')) {
                $this->settings('Claude API key phải bắt đầu bằng sk-ant-.');
            }
            if ($model !== '' && !preg_match('/^[a-z0-9.-]+$/', $model)) {
                $this->settings('Tên model Claude chỉ gồm chữ thường, số, dấu chấm, gạch ngang.');
            }
            $values = ['claude_model' => $model !== '' ? $model : null];
            if ($key !== '') {
                $values['anthropic_api_key'] = $key; // để trống thì giữ key đang lưu
            }
            Settings::save($values);
            $this->redirect('/settings?saved=claude#claude');
        }

        if ($action === 'clear_claude_key') {
            Settings::save(['anthropic_api_key' => null]);
            $this->redirect('/settings?saved=claude_cleared#claude');
        }

        if ($action === 'clear_key') {
            Settings::save(['gemini_api_key' => null]);
            $this->redirect('/settings?saved=key_cleared');
        }

        if ($action === 'change_password') {
            $username = Auth::user();
            $current = (string)($_POST['current_password'] ?? '');
            $new = (string)($_POST['new_password'] ?? '');
            $confirm = (string)($_POST['new_password_confirm'] ?? '');
            $hash = Auth::users()[$username]['password_hash'] ?? '';
            if (!password_verify($current, $hash)) {
                $this->settings('Mật khẩu hiện tại không đúng.');
            }
            $error = Auth::validateCredentials($username, $new) ?? ($new !== $confirm ? 'Hai lần nhập mật khẩu mới không khớp.' : null);
            if ($error) {
                $this->settings($error);
            }
            Auth::setPassword($username, $new);
            $this->redirect('/settings?saved=password');
        }

        $this->redirect('/settings');
    }

    public function apiGenerateAi(): void {
        header('Content-Type: application/json');
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;

        $prompt = trim($input['prompt'] ?? '');
        $count = (int)($input['count'] ?? 6);
        $targetAge = trim($input['target_age'] ?? '3-8');
        try {
            // Key chỉ lấy ở máy chủ (trang Cài đặt hoặc GEMINI_API_KEY), không nhận từ trình duyệt
            set_time_limit(200);
            $data = AiService::generate($prompt, $count, $targetAge, trim((string)($input['provider'] ?? '')) ?: null);
            echo json_encode(['status' => 'success', 'data' => $data]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function apiSaveAiPack(): void {
        header('Content-Type: application/json');
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (empty($data['pack']) || empty($data['items'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Dữ liệu gói hoặc thẻ không hợp lệ']);
            exit;
        }

        $packData = $data['pack'];
        $itemsData = $data['items'];

        // Ensure unique ID if already exists
        $packData['id'] = $this->makeUniqueId('topic_', $packData['id'] ?? '', $packData['title_en'] ?? '', fn($x) => TopicPack::find($x) !== null);

        $packData['background_url'] = trim($packData['background_url'] ?? '');
        $packData['theme_color'] = trim($packData['theme_color'] ?? '#10b981');

        $packCreated = TopicPack::create($packData);
        if (!$packCreated) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Không thể tạo gói chủ đề trong CSDL']);
            exit;
        }

        $savedItems = 0;
        foreach ($itemsData as $it) {
            if (isset($it['is_selected']) && !$it['is_selected']) {
                continue;
            }

            $images = is_array($it['images'] ?? null) ? $it['images'] : [($it['image'] ?? '')];
            $itemId = $this->makeUniqueId('item_', $it['id'] ?? '', $it['name_en'] ?? '', fn($x) => TopicItem::find($x) !== null);

            $itemPayload = [
                'id' => $itemId,
                'pack_id' => $packData['id'],
                'name_vi' => $it['name_vi'] ?? '',
                'name_en' => $it['name_en'] ?? '',
                'description_vi' => $it['description_vi'] ?? '',
                'description_en' => $it['description_en'] ?? '',
                'pronounce_vi_url' => $it['pronounce_vi_url'] ?? '',
                'pronounce_en_url' => $it['pronounce_en_url'] ?? '',
                'images' => $images,
                'youtube_video_id' => $it['youtube_video_id'] ?? '',
                'map_x' => (float)($it['map_x'] ?? 100),
                'map_y' => (float)($it['map_y'] ?? 100),
                'phonics_en' => trim($it['phonics_en'] ?? ''),
                'real_image_url' => trim($it['real_image_url'] ?? ''),
                'sfx_sound' => trim($it['sfx_sound'] ?? ''),
                'fun_fact_vi' => trim($it['fun_fact_vi'] ?? ''),
                'fun_fact_en' => trim($it['fun_fact_en'] ?? ''),
                'prompt_question_vi' => trim($it['prompt_question_vi'] ?? ''),
                'prompt_question_en' => trim($it['prompt_question_en'] ?? ''),
                'action_hint_vi' => trim($it['action_hint_vi'] ?? ''),
                'coloring_outline_url' => trim($it['coloring_outline_url'] ?? ''),
                'sort_order' => (int)($it['sort_order'] ?? ($savedItems + 1)),
            ];

            if (TopicItem::create($itemPayload)) {
                $savedItems++;
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => "Đã tạo thành công gói '{$packData['title_vi']}' với {$savedItems} thẻ học tập!",
            'pack_id' => $packData['id'],
            'redirect' => '/packs/' . urlencode($packData['id']) . '/items?created=1'
        ]);
        exit;
    }

    public function apiPromptTemplate(): void {
        header('Content-Type: application/json');
        $topic = trim($_GET['topic'] ?? $_POST['topic'] ?? '');
        $count = (int)($_GET['count'] ?? $_POST['count'] ?? 6);
        $targetAge = trim($_GET['target_age'] ?? $_POST['target_age'] ?? '3-8');

        $prompt = AiService::buildPromptTemplate($topic, $count, $targetAge);
        echo json_encode(['status' => 'success', 'prompt' => $prompt]);
        exit;
    }

    public function apiSearchImages(): void {
        header('Content-Type: application/json');
        $query = trim($_GET['q'] ?? '');
        if (empty($query)) {
            echo json_encode(['status' => 'success', 'images' => []]);
            exit;
        }

        // Clean query: strip non-alphanumeric/spaces
        $cleanQ = preg_replace('/[^\p{L}\p{N}\s_-]/u', ' ', $query);
        $cleanQ = preg_replace('/\s+/', ' ', trim($cleanQ));

        $url = 'https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrnamespace=6&gsrsearch=filetype:bitmap|drawing+' . urlencode($cleanQ) . '&gsrlimit=20&prop=imageinfo&iiprop=url|thumburl|dimensions&iiurlwidth=600&format=json';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'KidsWorldCMS/1.0 (Educational Content Management)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);
        curl_close($ch);

        $images = [];
        if ($res) {
            $data = json_decode($res, true);
            $pages = $data['query']['pages'] ?? [];
            foreach ($pages as $p) {
                $info = $p['imageinfo'][0] ?? null;
                if ($info && !empty($info['thumburl'])) {
                    $title = str_replace(['File:', '.jpg', '.jpeg', '.png', '.webp', '.svg'], '', $p['title'] ?? '');
                    $images[] = [
                        'title' => trim($title),
                        'thumb' => $info['thumburl'],
                        'url' => $info['url'] ?? $info['thumburl'],
                        'width' => (int)($info['width'] ?? 0),
                        'height' => (int)($info['height'] ?? 0),
                    ];
                }
            }
        }

        echo json_encode([
            'status' => 'success',
            'query' => $query,
            'count' => count($images),
            'images' => $images
        ]);
        exit;
    }

    public function apiUploadImage(): void {
        header('Content-Type: application/json');

        $fileKey = isset($_FILES['image']) ? 'image' : (isset($_FILES['file']) ? 'file' : null);
        if (!$fileKey || !isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Không tìm thấy file ảnh hoặc quá trình tải file bị lỗi.'
            ]);
            exit;
        }

        $file = $_FILES[$fileKey];
        $originalName = $file['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];

        if (!in_array($ext, $allowed)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Định dạng file không hợp lệ. Chỉ chấp nhận các định dạng: ' . implode(', ', $allowed)
            ]);
            exit;
        }

        // Giới hạn 15MB
        if ($file['size'] > 15 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Dung lượng file vượt quá giới hạn cho phép (15MB).'
            ]);
            exit;
        }

        $uploadDir = __DIR__ . '/../../public/uploads/images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = 'kw_img_' . date('Ymd_His') . '_' . substr(md5(uniqid()), 0, 6) . '.' . $ext;
        $target = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            $webUrl = '/uploads/images/' . $filename;
            $sizeKb = round($file['size'] / 1024, 1);
            $sizeStr = $sizeKb > 1024 ? round($sizeKb / 1024, 2) . ' MB' : $sizeKb . ' KB';

            echo json_encode([
                'status' => 'success',
                'message' => 'Tải ảnh lên thành công!',
                'url' => $webUrl,
                'thumb' => $webUrl,
                'filename' => $filename,
                'original_name' => $originalName,
                'size_formatted' => $sizeStr,
                'created_at' => date('d/m/Y H:i')
            ]);
            exit;
        }

        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Không thể lưu file vào thư mục máy chủ. Vui lòng kiểm tra quyền ghi.'
        ]);
        exit;
    }

    public function apiListUploadedImages(): void {
        header('Content-Type: application/json');
        $uploadDir = __DIR__ . '/../../public/uploads/images/';
        if (!is_dir($uploadDir)) {
            echo json_encode(['status' => 'success', 'images' => []]);
            exit;
        }

        $files = scandir($uploadDir);
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        $images = [];

        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $filePath = $uploadDir . $f;
                $size = @filesize($filePath) ?: 0;
                $mtime = @filemtime($filePath) ?: 0;
                $sizeKb = round($size / 1024, 1);
                $sizeStr = $sizeKb > 1024 ? round($sizeKb / 1024, 2) . ' MB' : $sizeKb . ' KB';

                $images[] = [
                    'filename' => $f,
                    'url' => '/uploads/images/' . $f,
                    'thumb' => '/uploads/images/' . $f,
                    'size_formatted' => $sizeStr,
                    'timestamp' => $mtime,
                    'created_at' => date('d/m/Y H:i', $mtime)
                ];
            }
        }

        // Sort newest first
        usort($images, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        echo json_encode([
            'status' => 'success',
            'count' => count($images),
            'images' => $images
        ]);
        exit;
    }

    /** POST /api/ai/claude-image {name_en, name_vi, style}: Claude vẽ SVG cho một thẻ. */
    public function apiClaudeImage(): void {
        header('Content-Type: application/json');
        set_time_limit(200);
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        try {
            $result = ClaudeImageService::drawSvg(
                (string)($body['name_en'] ?? ''),
                (string)($body['name_vi'] ?? ''),
                (string)($body['style'] ?? 'illustration'),
                is_array($body['members'] ?? null) ? $body['members'] : []
            );
            echo json_encode(['status' => 'success', 'data' => $result]);
        } catch (\Throwable $e) {
            http_response_code($e instanceof \InvalidArgumentException ? 400 : 502);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    /** POST /api/items/{id}/image {field: image|coloring_outline, url}: gán ảnh vừa tải lên cho thẻ. */
    public function apiSetItemImage(string $id): void {
        header('Content-Type: application/json');
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $field = (string)($body['field'] ?? '');
        $url = trim((string)($body['url'] ?? ''));
        if (!TopicItem::find($id) || !str_starts_with($url, '/uploads/images/') || !in_array($field, ['image', 'coloring_outline'], true)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Thẻ, loại ảnh hoặc đường dẫn ảnh không hợp lệ.']);
            exit;
        }
        $ok = $field === 'image' ? TopicItem::updatePrimaryImage($id, $url) : TopicItem::updateColoringOutline($id, $url);
        echo json_encode(['status' => $ok ? 'success' : 'error']);
        exit;
    }

    /** POST /api/items/{id}/real-photo: tìm ảnh chụp thật trên Wikimedia Commons, tải về và gán cho thẻ. */
    public function apiItemRealPhoto(string $id): void {
        header('Content-Type: application/json');
        set_time_limit(90);
        $item = TopicItem::find($id);
        if (!$item) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy thẻ.']);
            exit;
        }
        try {
            $photo = RealPhotoService::findAndSave((string)$item['name_en'], (string)$item['name_vi']);
            TopicItem::updateRealImage($id, $photo['url']);
            echo json_encode(['status' => 'success', 'data' => $photo]);
        } catch (\Throwable $e) {
            http_response_code($e instanceof \InvalidArgumentException ? 400 : 502);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    /** POST /api/packs/{id}/thumbnail {url}: gán ảnh đại diện vừa tải lên cho gói. */
    public function apiSetPackThumbnail(string $id): void {
        header('Content-Type: application/json');
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $url = trim((string)($body['url'] ?? ''));
        if (!TopicPack::find($id) || !str_starts_with($url, '/uploads/images/')) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Gói hoặc đường dẫn ảnh không hợp lệ.']);
            exit;
        }
        echo json_encode(['status' => TopicPack::updateThumbnail($id, $url) ? 'success' : 'error']);
        exit;
    }

    /** POST /api/packs/{id}/ai-rewrite {provider, instruction, pack: bool, items: bool}: AI đề xuất bản viết lại, chưa lưu. */
    public function apiAiRewrite(string $id): void {
        header('Content-Type: application/json');
        set_time_limit(200);
        $pack = TopicPack::find($id);
        if (!$pack) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy gói.']);
            exit;
        }
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        try {
            $result = AiRewriteService::rewrite(
                $pack,
                TopicItem::byPack($id),
                !empty($body['pack']),
                !empty($body['items']),
                mb_substr(trim((string)($body['instruction'] ?? '')), 0, 1000),
                trim((string)($body['provider'] ?? '')) ?: null
            );
            echo json_encode(['status' => 'success', 'data' => $result]);
        } catch (\Throwable $e) {
            http_response_code($e instanceof \InvalidArgumentException ? 400 : 502);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    /** POST /api/packs/{id}/ai-rewrite/apply {pack: {field: value}, items: [{id, field: value}]}: lưu các thay đổi đã chọn. */
    public function apiAiRewriteApply(string $id): void {
        header('Content-Type: application/json');
        if (!TopicPack::find($id)) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy gói.']);
            exit;
        }
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $changed = 0;

        $packFields = array_intersect_key(is_array($body['pack'] ?? null) ? $body['pack'] : [], AiRewriteService::PACK_FIELDS);
        $packFields = array_map(fn($v) => trim((string)$v), $packFields);
        if (isset($packFields['title_vi']) && $packFields['title_vi'] === '') unset($packFields['title_vi']);
        if (isset($packFields['title_en']) && $packFields['title_en'] === '') unset($packFields['title_en']);
        if ($packFields) {
            TopicPack::updateTextFields($id, $packFields);
            $changed += count($packFields);
        }

        $packItemIds = array_column(TopicItem::byPack($id), 'id');
        foreach (is_array($body['items'] ?? null) ? $body['items'] : [] as $row) {
            if (!is_array($row) || !in_array($row['id'] ?? null, $packItemIds, true)) continue;
            $fields = array_map(fn($v) => trim((string)$v), array_intersect_key($row, AiRewriteService::ITEM_FIELDS));
            if ($fields) {
                TopicItem::updateTextFields($row['id'], $fields);
                $changed += count($fields);
            }
        }

        if ($changed > 0) {
            TopicPack::bumpVersion($id); // để app nhận nội dung mới
        }
        echo json_encode(['status' => 'success', 'changed' => $changed, 'version' => (int)TopicPack::find($id)['version']]);
        exit;
    }

    /** POST /api/packs/{id}/bump-version: tăng phiên bản để app nhận ảnh mới. */
    public function apiBumpPackVersion(string $id): void {
        header('Content-Type: application/json');
        if (!TopicPack::find($id)) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy gói.']);
            exit;
        }
        TopicPack::bumpVersion($id);
        echo json_encode(['status' => 'success', 'version' => (int)TopicPack::find($id)['version']]);
        exit;
    }

    public function apiGenerateImagePrompt(): void {
        header('Content-Type: application/json');
        $body = json_decode(file_get_contents('php://input'), true);
        $keyword = trim($body['keyword'] ?? $_POST['keyword'] ?? $_GET['keyword'] ?? 'Hà Mã');
        $context = trim($body['context'] ?? $_POST['context'] ?? $_GET['context'] ?? '');
        $aspectRatio = trim($body['aspect_ratio'] ?? $_POST['aspect_ratio'] ?? $_GET['aspect_ratio'] ?? '16:9');
        $resolution = trim($body['resolution'] ?? $_POST['resolution'] ?? $_GET['resolution'] ?? '1920x1080');

        $result = AiService::generateImagePrompts($keyword, $context, $aspectRatio, $resolution);
        echo json_encode([
            'status' => 'success',
            'data' => $result
        ]);
        exit;
    }
}
