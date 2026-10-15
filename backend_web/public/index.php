<?php

// Autoloader for KidsWorld\Backend namespace
spl_autoload_register(function ($class) {
    $prefix = 'KidsWorld\\Backend\\';
    $baseDir = __DIR__ . '/../src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Thư viện cài bằng Composer (SDK Anthropic cho tính năng vẽ ảnh bằng Claude)
if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

use KidsWorld\Backend\Controllers\AdminController;
use KidsWorld\Backend\Controllers\ApiController;
use KidsWorld\Backend\Controllers\AuthController;
use KidsWorld\Backend\Services\Auth;

// Handle static files if using PHP built-in web server
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// CORS Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$admin = new AdminController();
$api = new ApiController();
$auth = new AuthController();

// 0. Đăng nhập. App của bé chỉ đọc /api/info và /api/packs..., các đường đó để công khai;
//    mọi trang CMS và API quản trị (AI, tải ảnh, lưu gói) phải đăng nhập.
Auth::startSession();
$isPublicApi = $uri === '/api/info' || $uri === '/api/packs' || preg_match('#^/api/packs/[^/]+(/download)?$#', $uri);
if ($uri === '/login') {
    $method === 'POST' ? $auth->login() : $auth->showLogin();
} elseif ($uri === '/logout' && $method === 'POST') {
    $auth->logout();
} elseif (!$isPublicApi && !Auth::user()) {
    if (str_starts_with($uri, '/api/')) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Cần đăng nhập CMS']);
        exit;
    }
    $next = $_SERVER['REQUEST_URI'] ?? '/';
    header('Location: /login' . ($next !== '/' ? '?next=' . urlencode($next) : ''));
    exit;
}

// 1. API Endpoints
if (str_starts_with($uri, '/api/')) {
    if ($uri === '/api/info') {
        $api->info();
    } elseif ($uri === '/api/packs') {
        $api->listPacks();
    } elseif ($uri === '/api/ai/generate' && $method === 'POST') {
        $admin->apiGenerateAi();
    } elseif ($uri === '/api/ai/prompt-template') {
        $admin->apiPromptTemplate();
    } elseif ($uri === '/api/ai/save' && $method === 'POST') {
        $admin->apiSaveAiPack();
    } elseif ($uri === '/api/images/search') {
        $admin->apiSearchImages();
    } elseif ($uri === '/api/upload-image' && $method === 'POST') {
        $admin->apiUploadImage();
    } elseif ($uri === '/api/media/list') {
        $admin->apiListUploadedImages();
    } elseif ($uri === '/api/ai/claude-image' && $method === 'POST') {
        $admin->apiClaudeImage();
    } elseif (preg_match('#^/api/items/([^/]+)/image$#', $uri, $matches) && $method === 'POST') {
        $admin->apiSetItemImage(urldecode($matches[1]));
    } elseif (preg_match('#^/api/items/([^/]+)/real-photo$#', $uri, $matches) && $method === 'POST') {
        $admin->apiItemRealPhoto(urldecode($matches[1]));
    } elseif (preg_match('#^/api/packs/([^/]+)/thumbnail$#', $uri, $matches) && $method === 'POST') {
        $admin->apiSetPackThumbnail(urldecode($matches[1]));
    } elseif (preg_match('#^/api/packs/([^/]+)/ai-rewrite$#', $uri, $matches) && $method === 'POST') {
        $admin->apiAiRewrite(urldecode($matches[1]));
    } elseif (preg_match('#^/api/packs/([^/]+)/ai-rewrite/apply$#', $uri, $matches) && $method === 'POST') {
        $admin->apiAiRewriteApply(urldecode($matches[1]));
    } elseif (preg_match('#^/api/packs/([^/]+)/bump-version$#', $uri, $matches) && $method === 'POST') {
        $admin->apiBumpPackVersion(urldecode($matches[1]));
    } elseif ($uri === '/api/ai/image-prompt') {
        $admin->apiGenerateImagePrompt();
    } elseif (preg_match('#^/api/packs/([^/]+)/download$#', $uri, $matches)) {
        $api->downloadPack(urldecode($matches[1]));
    } elseif (preg_match('#^/api/packs/([^/]+)$#', $uri, $matches)) {
        $api->getPack(urldecode($matches[1]));
    } else {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'API endpoint not found']);
        exit;
    }
}

// 2. Admin Web UI Routes
if ($uri === '/' || $uri === '/dashboard') {
    $admin->dashboard();
} elseif ($uri === '/ai-generator') {
    $admin->aiGenerator();
} elseif ($uri === '/packs') {
    $admin->packs();
} elseif ($uri === '/packs/create') {
    $admin->createPack();
} elseif ($uri === '/packs/store' && $method === 'POST') {
    $admin->storePack();
} elseif (preg_match('#^/packs/([^/]+)/edit$#', $uri, $matches)) {
    $admin->editPack(urldecode($matches[1]));
} elseif (preg_match('#^/packs/([^/]+)/update$#', $uri, $matches) && $method === 'POST') {
    $admin->updatePack(urldecode($matches[1]));
} elseif (preg_match('#^/packs/([^/]+)/delete$#', $uri, $matches)) {
    $admin->deletePack(urldecode($matches[1]));
} elseif (preg_match('#^/packs/([^/]+)/items/create$#', $uri, $matches)) {
    $admin->createItem(urldecode($matches[1]));
} elseif (preg_match('#^/packs/([^/]+)/items/store$#', $uri, $matches) && $method === 'POST') {
    $admin->storeItem(urldecode($matches[1]));
} elseif (preg_match('#^/packs/([^/]+)/items$#', $uri, $matches)) {
    $admin->items(urldecode($matches[1]));
} elseif (preg_match('#^/items/([^/]+)/edit$#', $uri, $matches)) {
    $admin->editItem(urldecode($matches[1]));
} elseif (preg_match('#^/items/([^/]+)/update$#', $uri, $matches) && $method === 'POST') {
    $admin->updateItem(urldecode($matches[1]));
} elseif (preg_match('#^/items/([^/]+)/delete$#', $uri, $matches)) {
    $admin->deleteItem(urldecode($matches[1]));
} elseif ($uri === '/settings') {
    $method === 'POST' ? $admin->saveSettings() : $admin->settings();
} elseif ($uri === '/guide') {
    $admin->domainGuide();
} elseif ($uri === '/docs' || $uri === '/docs/specification') {
    $admin->docsSpecification();
} else {
    header("Location: /");
    exit;
}
