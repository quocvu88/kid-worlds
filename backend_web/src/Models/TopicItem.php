<?php

namespace KidsWorld\Backend\Models;

use KidsWorld\Backend\Database;
use PDO;

class TopicItem {
    public static function byPack(string $packId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM topic_items 
            WHERE pack_id = ? 
            ORDER BY sort_order ASC, created_at ASC
        ");
        $stmt->execute([$packId]);
        return $stmt->fetchAll();
    }

    public static function find(string $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM topic_items WHERE id = ?");
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function create(array $data): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO topic_items (
                id, pack_id, name_vi, name_en, description_vi, description_en,
                pronounce_vi_url, pronounce_en_url, images_json, youtube_video_id,
                map_x, map_y, phonics_en, real_image_url, sfx_sound,
                fun_fact_vi, fun_fact_en, prompt_question_vi, prompt_question_en, action_hint_vi,
                coloring_outline_url, sort_order
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");

        $imagesJson = is_array($data['images'] ?? null) 
            ? json_encode($data['images']) 
            : ($data['images_json'] ?? '[]');

        $success = $stmt->execute([
            $data['id'],
            $data['pack_id'],
            $data['name_vi'],
            $data['name_en'],
            $data['description_vi'] ?? '',
            $data['description_en'] ?? '',
            $data['pronounce_vi_url'] ?? '',
            $data['pronounce_en_url'] ?? '',
            $imagesJson,
            $data['youtube_video_id'] ?? '',
            (float)($data['map_x'] ?? 100),
            (float)($data['map_y'] ?? 100),
            $data['phonics_en'] ?? '',
            $data['real_image_url'] ?? '',
            $data['sfx_sound'] ?? '',
            $data['fun_fact_vi'] ?? '',
            $data['fun_fact_en'] ?? '',
            $data['prompt_question_vi'] ?? '',
            $data['prompt_question_en'] ?? '',
            $data['action_hint_vi'] ?? '',
            $data['coloring_outline_url'] ?? '',
            (int)($data['sort_order'] ?? 0)
        ]);

        if ($success) {
            self::touchPack($data['pack_id']);
        }
        return $success;
    }

    public static function update(string $id, array $data): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE topic_items SET
                name_vi = ?,
                name_en = ?,
                description_vi = ?,
                description_en = ?,
                pronounce_vi_url = ?,
                pronounce_en_url = ?,
                images_json = ?,
                youtube_video_id = ?,
                map_x = ?,
                map_y = ?,
                phonics_en = ?,
                real_image_url = ?,
                sfx_sound = ?,
                fun_fact_vi = ?,
                fun_fact_en = ?,
                prompt_question_vi = ?,
                prompt_question_en = ?,
                action_hint_vi = ?,
                coloring_outline_url = ?,
                sort_order = ?
            WHERE id = ?
        ");

        $imagesJson = is_array($data['images'] ?? null) 
            ? json_encode($data['images']) 
            : ($data['images_json'] ?? '[]');

        $success = $stmt->execute([
            $data['name_vi'],
            $data['name_en'],
            $data['description_vi'] ?? '',
            $data['description_en'] ?? '',
            $data['pronounce_vi_url'] ?? '',
            $data['pronounce_en_url'] ?? '',
            $imagesJson,
            $data['youtube_video_id'] ?? '',
            (float)($data['map_x'] ?? 100),
            (float)($data['map_y'] ?? 100),
            $data['phonics_en'] ?? '',
            $data['real_image_url'] ?? '',
            $data['sfx_sound'] ?? '',
            $data['fun_fact_vi'] ?? '',
            $data['fun_fact_en'] ?? '',
            $data['prompt_question_vi'] ?? '',
            $data['prompt_question_en'] ?? '',
            $data['action_hint_vi'] ?? '',
            $data['coloring_outline_url'] ?? '',
            (int)($data['sort_order'] ?? 0),
            $id
        ]);

        if ($success && isset($data['pack_id'])) {
            self::touchPack($data['pack_id']);
        }
        return $success;
    }

    public static function updateColoringOutline(string $id, string $url): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE topic_items SET coloring_outline_url = ? WHERE id = ?");
        return $stmt->execute([$url, $id]);
    }

    public static function delete(string $id): bool {
        $item = self::find($id);
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM topic_items WHERE id = ?");
        $success = $stmt->execute([$id]);

        if ($success && $item) {
            self::touchPack($item['pack_id']);
        }
        return $success;
    }

    private static function touchPack(string $packId): void {
        $db = Database::getConnection();
        $db->prepare("
            UPDATE topic_packs 
            SET version = version + 1, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ")->execute([$packId]);
    }
}
