<?php

namespace KidsWorld\Backend\Controllers;

use KidsWorld\Backend\Models\TopicPack;
use KidsWorld\Backend\Models\TopicItem;

class ApiController {
    private function jsonResponse(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    private function getBaseUrl(): string {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
        return $scheme . '://' . $host;
    }

    private function resolveUrl(?string $url): string {
        if (empty($url)) return '';
        $clean = trim($url);

        // Fix deprecated wikimedia thumb domain
        if (str_contains($clean, 'thumb.wikimedia.org')) {
            $clean = str_replace('thumb.wikimedia.org', 'upload.wikimedia.org', $clean);
        }
        if (str_contains($clean, 'wikimedia.org') && str_contains($clean, '?')) {
            $clean = explode('?', $clean)[0];
        }

        if (str_starts_with($clean, 'assets/')) {
            return $clean;
        }

        // Dynamically replace localhost / 127.0.0.1 with incoming request host
        if (preg_match('#^https?://(?:localhost|127\.0\.0\.1)(?::\d+)?(/.*)?$#i', $clean, $m)) {
            $path = $m[1] ?? '';
            return rtrim($this->getBaseUrl(), '/') . $path;
        }

        if (str_starts_with($clean, 'http://') || str_starts_with($clean, 'https://')) {
            return $clean;
        }

        return rtrim($this->getBaseUrl(), '/') . '/' . ltrim($clean, '/');
    }

    public function info(): void {
        $stats = TopicPack::stats();
        $this->jsonResponse([
            'status' => 'ok',
            'server_name' => 'Kids World Content Server',
            'version' => '1.0.0',
            'api_version' => 'v1',
            'base_url' => $this->getBaseUrl(),
            'stats' => $stats,
            'timestamp' => date('c'),
        ]);
    }

    public function listPacks(): void {
        $packs = TopicPack::all(true);
        $baseUrl = $this->getBaseUrl();

        $formatted = array_map(function($pack) {
            return [
                'id' => $pack['id'],
                'title_vi' => $pack['title_vi'],
                'title_en' => $pack['title_en'],
                'category' => $pack['category'],
                'target_age_min' => (int)$pack['target_age_min'],
                'target_age_max' => (int)$pack['target_age_max'],
                'target_gender' => $pack['target_gender'],
                'thumbnail_url' => $this->resolveUrl($pack['thumbnail_url']),
                'background_url' => $this->resolveUrl($pack['background_url'] ?? ''),
                'theme_color' => $pack['theme_color'] ?? '#FF6584',
                'description_vi' => $pack['description_vi'] ?? '',
                'description_en' => $pack['description_en'] ?? '',
                'version' => (int)$pack['version'],
                'size_mb' => (float)$pack['size_mb'],
                'item_count' => (int)$pack['item_count'],
                'selected_games' => json_decode($pack['selected_games_json'] ?? '["coloring","memory_match"]', true) ?: ['coloring', 'memory_match'],
                'game_config' => json_decode($pack['game_config_json'] ?? '{}', true) ?: (object)[],
                'updated_at' => $pack['updated_at'],
                'download_url' => $this->getBaseUrl() . '/api/packs/' . urlencode($pack['id']) . '/download',
            ];
        }, $packs);

        $this->jsonResponse([
            'status' => 'success',
            'total' => count($formatted),
            'data' => $formatted
        ]);
    }

    public function getPack(string $id): void {
        $pack = TopicPack::find($id);
        if (!$pack) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Topic pack not found'], 404);
        }

        $items = TopicItem::byPack($id);
        $formattedItems = array_map(function($item) {
            $rawImages = json_decode($item['images_json'] ?? '[]', true) ?: [];
            $resolvedImages = array_map(fn($img) => $this->resolveUrl($img), $rawImages);

            return [
                'id' => $item['id'],
                'topic_id' => $item['pack_id'],
                'name_vi' => $item['name_vi'],
                'name_en' => $item['name_en'],
                'description_vi' => $item['description_vi'] ?? '',
                'description_en' => $item['description_en'] ?? '',
                'pronounce_vi_url' => $this->resolveUrl($item['pronounce_vi_url']),
                'pronounce_en_url' => $this->resolveUrl($item['pronounce_en_url']),
                'images' => $resolvedImages,
                'images_json' => json_encode($resolvedImages, JSON_UNESCAPED_SLASHES),
                'youtube_video_id' => $item['youtube_video_id'] ?? '',
                'map_coordinates' => [
                    'x' => (float)$item['map_x'],
                    'y' => (float)$item['map_y']
                ],
                'map_coordinates_json' => json_encode(['x' => (float)$item['map_x'], 'y' => (float)$item['map_y']]),
                'phonics_en' => $item['phonics_en'] ?? '',
                'real_image_url' => $this->resolveUrl($item['real_image_url'] ?? ''),
                'sfx_sound' => $item['sfx_sound'] ?? '',
                'fun_fact_vi' => $item['fun_fact_vi'] ?? '',
                'fun_fact_en' => $item['fun_fact_en'] ?? '',
                'prompt_question_vi' => $item['prompt_question_vi'] ?? '',
                'prompt_question_en' => $item['prompt_question_en'] ?? '',
                'action_hint_vi' => $item['action_hint_vi'] ?? '',
                'coloring_outline_url' => $this->resolveUrl($item['coloring_outline_url'] ?? '')
            ];
        }, $items);

        $pack['thumbnail_url'] = $this->resolveUrl($pack['thumbnail_url']);
        $pack['background_url'] = $this->resolveUrl($pack['background_url'] ?? '');
        $pack['theme_color'] = $pack['theme_color'] ?? '#FF6584';
        $pack['selected_games'] = json_decode($pack['selected_games_json'] ?? '["coloring","memory_match"]', true) ?: ['coloring', 'memory_match'];
        $pack['game_config'] = json_decode($pack['game_config_json'] ?? '{}', true) ?: (object)[];
        $pack['items'] = $formattedItems;
        $pack['item_count'] = count($formattedItems);

        $this->jsonResponse([
            'status' => 'success',
            'data' => $pack
        ]);
    }

    public function downloadPack(string $id): void {
        $pack = TopicPack::find($id);
        if (!$pack) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Topic pack not found'], 404);
        }

        $items = TopicItem::byPack($id);
        $formattedItems = array_map(function($item) {
            $rawImages = json_decode($item['images_json'] ?? '[]', true) ?: [];
            $resolvedImages = array_map(fn($img) => $this->resolveUrl($img), $rawImages);

            return [
                'id' => $item['id'],
                'topic_id' => $item['pack_id'],
                'name_vi' => $item['name_vi'],
                'name_en' => $item['name_en'],
                'description_vi' => $item['description_vi'] ?? '',
                'description_en' => $item['description_en'] ?? '',
                'pronounce_vi_url' => $this->resolveUrl($item['pronounce_vi_url']),
                'pronounce_en_url' => $this->resolveUrl($item['pronounce_en_url']),
                'images_json' => json_encode($resolvedImages, JSON_UNESCAPED_SLASHES),
                'youtube_video_id' => $item['youtube_video_id'] ?? '',
                'map_coordinates_json' => json_encode(['x' => (float)$item['map_x'], 'y' => (float)$item['map_y']]),
                'phonics_en' => $item['phonics_en'] ?? '',
                'real_image_url' => $this->resolveUrl($item['real_image_url'] ?? ''),
                'sfx_sound' => $item['sfx_sound'] ?? '',
                'fun_fact_vi' => $item['fun_fact_vi'] ?? '',
                'fun_fact_en' => $item['fun_fact_en'] ?? '',
                'prompt_question_vi' => $item['prompt_question_vi'] ?? '',
                'prompt_question_en' => $item['prompt_question_en'] ?? '',
                'action_hint_vi' => $item['action_hint_vi'] ?? '',
                'coloring_outline_url' => $this->resolveUrl($item['coloring_outline_url'] ?? '')
            ];
        }, $items);

        // Package bundle structured for direct SQLite insertion by the Flutter App
        $bundle = [
            'pack' => [
                'id' => $pack['id'],
                'title_vi' => $pack['title_vi'],
                'title_en' => $pack['title_en'],
                'category' => $pack['category'],
                'target_age_min' => (int)$pack['target_age_min'],
                'target_age_max' => (int)$pack['target_age_max'],
                'target_gender' => $pack['target_gender'],
                'thumbnail_path' => $this->resolveUrl($pack['thumbnail_url']),
                'background_path' => $this->resolveUrl($pack['background_url'] ?? ''),
                'theme_color' => $pack['theme_color'] ?? '#FF6584',
                'version' => (int)$pack['version'],
                'selected_games_json' => $pack['selected_games_json'] ?? '["coloring","memory_match"]',
                'game_config_json' => $pack['game_config_json'] ?? '{}',
                'is_downloaded' => 1
            ],
            'items' => $formattedItems,
            'exported_at' => date('c'),
            'total_items' => count($formattedItems),
            'server_version' => $pack['version']
        ];

        $this->jsonResponse([
            'status' => 'success',
            'data' => $bundle
        ]);
    }
}
