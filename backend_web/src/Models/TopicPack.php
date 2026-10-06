<?php

namespace KidsWorld\Backend\Models;

use KidsWorld\Backend\Database;
use PDO;

class TopicPack {
    public static function all(bool $activeOnly = false): array {
        $db = Database::getConnection();
        $sql = "
            SELECT p.*, COUNT(i.id) as item_count 
            FROM topic_packs p
            LEFT JOIN topic_items i ON p.id = i.pack_id
        ";
        if ($activeOnly) {
            $sql .= " WHERE p.is_active = 1 ";
        }
        $sql .= " GROUP BY p.id ORDER BY p.created_at DESC";

        return $db->query($sql)->fetchAll();
    }

    public static function find(string $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT p.*, COUNT(i.id) as item_count 
            FROM topic_packs p
            LEFT JOIN topic_items i ON p.id = i.pack_id
            WHERE p.id = ?
            GROUP BY p.id
        ");
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function create(array $data): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO topic_packs (
                id, title_vi, title_en, category, target_age_min, target_age_max,
                target_gender, thumbnail_url, background_url, theme_color,
                description_vi, description_en, version, size_mb, is_active,
                selected_games_json, game_config_json, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP
            )
        ");

        return $stmt->execute([
            $data['id'],
            $data['title_vi'],
            $data['title_en'],
            $data['category'],
            (int)($data['target_age_min'] ?? 3),
            (int)($data['target_age_max'] ?? 10),
            $data['target_gender'] ?? 'all',
            $data['thumbnail_url'] ?? '',
            $data['background_url'] ?? '',
            $data['theme_color'] ?? '#FF6584',
            $data['description_vi'] ?? '',
            $data['description_en'] ?? '',
            (int)($data['version'] ?? 1),
            (float)($data['size_mb'] ?? 1.5),
            isset($data['is_active']) ? (int)$data['is_active'] : 1,
            $data['selected_games_json'] ?? '["coloring","memory_match"]',
            $data['game_config_json'] ?? '{}'
        ]);
    }

    public static function update(string $id, array $data): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE topic_packs SET
                title_vi = ?,
                title_en = ?,
                category = ?,
                target_age_min = ?,
                target_age_max = ?,
                target_gender = ?,
                thumbnail_url = ?,
                background_url = ?,
                theme_color = ?,
                description_vi = ?,
                description_en = ?,
                version = version + 1,
                size_mb = ?,
                is_active = ?,
                selected_games_json = ?,
                game_config_json = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['title_vi'],
            $data['title_en'],
            $data['category'],
            (int)($data['target_age_min'] ?? 3),
            (int)($data['target_age_max'] ?? 10),
            $data['target_gender'] ?? 'all',
            $data['thumbnail_url'] ?? '',
            $data['background_url'] ?? '',
            $data['theme_color'] ?? '#FF6584',
            $data['description_vi'] ?? '',
            $data['description_en'] ?? '',
            (float)($data['size_mb'] ?? 1.5),
            isset($data['is_active']) ? (int)$data['is_active'] : 1,
            $data['selected_games_json'] ?? '["coloring","memory_match"]',
            $data['game_config_json'] ?? '{}',
            $id
        ]);
    }

    public static function delete(string $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM topic_packs WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function stats(): array {
        $db = Database::getConnection();
        $packCount = $db->query("SELECT COUNT(*) FROM topic_packs")->fetchColumn();
        $itemCount = $db->query("SELECT COUNT(*) FROM topic_items")->fetchColumn();
        $totalSize = $db->query("SELECT SUM(size_mb) FROM topic_packs")->fetchColumn() ?: 0;

        return [
            'total_packs' => (int)$packCount,
            'total_items' => (int)$itemCount,
            'total_size_mb' => round((float)$totalSize, 1),
        ];
    }
}
