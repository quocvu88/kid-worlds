<?php

namespace KidsWorld\Backend;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dbPath = __DIR__ . '/../data/database.sqlite';
            $isNew = !file_exists($dbPath);

            try {
                self::$instance = new PDO('sqlite:' . $dbPath);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                self::$instance->exec('PRAGMA foreign_keys = ON;');
                self::$instance->exec('PRAGMA journal_mode = WAL;');

                if ($isNew || filesize($dbPath) === 0) {
                    self::initTables();
                    self::seedInitialData();
                } else {
                    self::ensureColumnsExist();
                }
            } catch (PDOException $e) {
                die('Database Connection Error: ' . $e->getMessage());
            }
        }
        return self::$instance;
    }

    private static function ensureColumnsExist(): void {
        $db = self::$instance;
        $packCols = ['background_url TEXT', 'theme_color TEXT'];
        foreach ($packCols as $col) {
            try {
                $db->exec("ALTER TABLE topic_packs ADD COLUMN $col;");
            } catch (\Exception $e) {}
        }

        $itemCols = [
            'phonics_en TEXT',
            'real_image_url TEXT',
            'sfx_sound TEXT',
            'fun_fact_vi TEXT',
            'fun_fact_en TEXT',
            'prompt_question_vi TEXT',
            'prompt_question_en TEXT',
            'action_hint_vi TEXT'
        ];
        foreach ($itemCols as $col) {
            try {
                $db->exec("ALTER TABLE topic_items ADD COLUMN $col;");
            } catch (\Exception $e) {}
        }
    }

    private static function initTables(): void {
        $db = self::$instance;

        // 1. Topic Packs Table
        $db->exec("
            CREATE TABLE IF NOT EXISTS topic_packs (
                id TEXT PRIMARY KEY,
                title_vi TEXT NOT NULL,
                title_en TEXT NOT NULL,
                category TEXT NOT NULL,
                target_age_min INTEGER DEFAULT 3,
                target_age_max INTEGER DEFAULT 10,
                target_gender TEXT DEFAULT 'all',
                thumbnail_url TEXT,
                background_url TEXT,
                theme_color TEXT,
                description_vi TEXT,
                description_en TEXT,
                version INTEGER DEFAULT 1,
                size_mb REAL DEFAULT 1.5,
                is_active INTEGER DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // 2. Topic Items Table
        $db->exec("
            CREATE TABLE IF NOT EXISTS topic_items (
                id TEXT PRIMARY KEY,
                pack_id TEXT NOT NULL,
                name_vi TEXT NOT NULL,
                name_en TEXT NOT NULL,
                description_vi TEXT,
                description_en TEXT,
                pronounce_vi_url TEXT,
                pronounce_en_url TEXT,
                images_json TEXT NOT NULL DEFAULT '[]',
                youtube_video_id TEXT,
                map_x REAL DEFAULT 100,
                map_y REAL DEFAULT 100,
                phonics_en TEXT,
                real_image_url TEXT,
                sfx_sound TEXT,
                fun_fact_vi TEXT,
                fun_fact_en TEXT,
                prompt_question_vi TEXT,
                prompt_question_en TEXT,
                action_hint_vi TEXT,
                sort_order INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (pack_id) REFERENCES topic_packs(id) ON DELETE CASCADE
            );
        ");
    }

    private static function seedInitialData(): void {
        $db = self::$instance;

        // Seed Pack 1: Động Vật
        $stmt = $db->prepare("
            INSERT OR IGNORE INTO topic_packs (id, title_vi, title_en, category, target_age_min, target_age_max, target_gender, thumbnail_url, background_url, theme_color, description_vi, description_en, version, size_mb)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'topic_animals',
            'Thế Giới Động Vật',
            'Wild Animals',
            'animals',
            3,
            8,
            'all',
            'https://images.unsplash.com/photo-1546182990-dffeafbe841d?w=600&auto=format&fit=crop&q=80',
            'assets/backgrounds/bg_green_meadow.jpg',
            '#4CAF50',
            'Khám phá thế giới hoang dã với tiếng kêu và hình ảnh các loài động vật ngộ nghĩnh.',
            'Explore the wildlife with animal sounds and cheerful pictures.',
            1,
            2.4
        ]);

        // Seed Pack 2: Phương Tiện Giao Thông
        $stmt->execute([
            'topic_vehicles',
            'Phương Tiện Giao Thông',
            'Vehicles & Transport',
            'vehicles',
            3,
            7,
            'all',
            'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=600&auto=format&fit=crop&q=80',
            'assets/backgrounds/bg_balloon_party.jpg',
            '#2196F3',
            'Tìm hiểu về các loại xe cộ trên đường phố, tàu hỏa, máy bay và tàu thủy.',
            'Learn about road vehicles, trains, airplanes and ships.',
            1,
            1.8
        ]);

        // Seed Pack 3: Trái Cây Thơm Ngon
        $stmt->execute([
            'topic_fruits',
            'Trái Cây Thơm Ngon',
            'Delicious Fruits',
            'fruits',
            2,
            6,
            'all',
            'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=600&auto=format&fit=crop&q=80',
            'assets/backgrounds/bg_candy_land.jpg',
            '#FF9800',
            'Khám phá màu sắc rực rỡ và tên gọi các loại quả ngọt lành mà bé ăn hàng ngày.',
            'Discover vibrant colors and names of sweet healthy fruits.',
            1,
            1.6
        ]);

        // Seed Pack 4: Vũ Trụ Kỳ Thú
        $stmt->execute([
            'topic_space',
            'Vũ Trụ Kỳ Thú',
            'Space & Planets',
            'space',
            4,
            10,
            'all',
            'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=600&auto=format&fit=crop&q=80',
            'assets/backgrounds/bg_pastel_galaxy.jpg',
            '#9C27B0',
            'Chuyến bay thám hiểm hệ Mặt Trời, Mặt Trăng và các vì sao lấp lánh.',
            'An exciting journey exploring the Solar System, Moon and shining stars.',
            1,
            2.1
        ]);

        // Seed Items
        $itemStmt = $db->prepare("
            INSERT OR IGNORE INTO topic_items (
                id, pack_id, name_vi, name_en, description_vi, description_en,
                pronounce_vi_url, pronounce_en_url, images_json, youtube_video_id,
                map_x, map_y, phonics_en, real_image_url, sfx_sound,
                fun_fact_vi, prompt_question_vi, action_hint_vi, sort_order
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $animalItems = [
            ['item_lion', 'topic_animals', 'Sư Tử', 'Lion', 'Sư tử dũng mãnh là vua của thảo nguyên xanh.', 'The mighty lion is the king of the jungle.', '', '', json_encode(['https://images.unsplash.com/photo-1614027164847-1b28caa1440c?w=600&auto=format&fit=crop&q=80']), 'f9qIBCNxXXM', 120.0, 220.0, 'L - /l/ - Lion', 'https://images.unsplash.com/photo-1546182990-dffeafbe841d?w=800&auto=format&fit=crop&q=80', 'Gừừừ... Roaaar! Sư tử dũng mãnh!', 'Sư tử đực có chiếc bờm xù rất oai phong để bảo vệ cổ và làm đẹp với các bạn sư tử cái!', 'Đố con biết chú sư tử ngủ bao nhiêu tiếng một ngày? (Tận 20 tiếng đấy!)', 'Mẹ và bé hãy cùng xòe móng vuốt và bắt chước tiếng sư tử gầm thật oai vệ nào!', 1],
            ['item_elephant', 'topic_animals', 'Con Voi', 'Elephant', 'Chú voi hiền lành có chiếc vòi dài khéo léo và đôi tai to như quạt.', 'The gentle elephant has a long trunk and big ears.', '', '', json_encode(['https://images.unsplash.com/photo-1557050543-4d5f4e07ef46?w=600&auto=format&fit=crop&q=80']), 'z4FbTIldHys', 320.0, 180.0, 'E - /e/ - Elephant', 'https://images.unsplash.com/photo-1557050543-4d5f4e07ef46?w=800&auto=format&fit=crop&q=80', 'Pawoo! Éc éc! Chú voi con gọi mẹ!', 'Chiếc vòi của voi không hề có xương mà được tạo bởi hơn 40.000 bó cơ bắp khéo léo!', 'Đố bé vòi của chú voi dùng để làm gì nào? (Uống nước, gắp thức ăn và tắm mát)', 'Bé hãy dùng một cánh tay vươn dài làm chiếc vòi voi đung đưa hút nước uống nhé!', 2],
            ['item_monkey', 'topic_animals', 'Con Khỉ', 'Monkey', 'Chú khỉ tinh nghịch rất thích chuyền cành và ăn chuối chín.', 'The playful monkey loves swinging across branches and eating bananas.', '', '', json_encode(['https://images.unsplash.com/photo-1540573133985-87b6da6d54a9?w=600&auto=format&fit=crop&q=80']), 'fN1Cyr0ZK9M', 540.0, 140.0, 'M - /m/ - Monkey', 'https://images.unsplash.com/photo-1540573133985-87b6da6d54a9?w=800&auto=format&fit=crop&q=80', 'Khẹc khẹc... Oó oó! Chú khỉ nhảy nhót!', 'Các bạn khỉ có chiếc đuôi dài khéo léo như bàn tay thứ năm giúp bám chặt vào cành cây!', 'Đố bé món ăn thơm ngọt khoái khẩu nhất của chú khỉ là quả gì nào?', 'Bé cùng mẹ gãi đầu và giả làm những chú khỉ tinh nghịch chuyền cành nào!', 3],
            ['item_giraffe', 'topic_animals', 'Hươu Cao Cổ', 'Giraffe', 'Hươu cao cổ có chiếc cổ dài chạm tới những vòm lá xanh cao nhất.', 'The tall giraffe easily reaches the sweetest leaves high on trees.', '', '', json_encode(['https://images.unsplash.com/photo-1547721064-da6cfb341d50?w=600&auto=format&fit=crop&q=80']), 'pWepfJ-8XU0', 750.0, 190.0, 'G - /dʒ/ - Giraffe', 'https://images.unsplash.com/photo-1547721064-da6cfb341d50?w=800&auto=format&fit=crop&q=80', 'Ụm bò... Hươu cao cổ dạo bước êm đềm!', 'Hươu cao cổ có chiếc lưỡi màu xanh đen dài tới 45cm để hái lá trên ngọn cây gai!', 'Đố con hươu cao cổ ngủ đứng hay ngủ nằm? (Bạn ấy chỉ ngủ đứng khoảng 30 phút mỗi ngày thôi!)', 'Hai mẹ con cùng kiễng chân vươn cổ thật cao chạm trần nhà nào!', 4],
            ['item_tiger', 'topic_animals', 'Con Hổ', 'Tiger', 'Chú hổ oai phong có bộ lông vàng cam với những sọc đen nổi bật.', 'The powerful tiger has a distinctive orange coat with bold black stripes.', '', '', json_encode(['https://images.unsplash.com/photo-1534188753412-3e26d0d618d6?w=600&auto=format&fit=crop&q=80']), 'p_IEUJqThZE', 980.0, 240.0, 'T - /t/ - Tiger', 'https://images.unsplash.com/photo-1534188753412-3e26d0d618d6?w=800&auto=format&fit=crop&q=80', 'Gừừừ... Roaaar! Hổ dũng mãnh sơn lâm!', 'Không có hai chú hổ nào có sọc giống hệt nhau, giống như dấu vân tay của con người vậy!', 'Hổ bơi rất giỏi và thích tắm mát, bé có thích đi bơi giống chú hổ không?', 'Bé hãy bước đi rón rén thật êm như những bước chân đệm thịt của chú hổ!', 5],
            ['item_zebra', 'topic_animals', 'Ngựa Vằn', 'Zebra', 'Ngựa vằn đáng yêu khoác chiếc áo sọc trắng đen tuyệt đẹp trên thảo nguyên.', 'The lovely zebra sports classic black and white stripes across grasslands.', '', '', json_encode(['https://images.unsplash.com/photo-1501705388883-4ed8a543392c?w=600&auto=format&fit=crop&q=80']), 'j4lDDQTKN8s', 1200.0, 210.0, 'Z - /z/ - Zebra', 'https://images.unsplash.com/photo-1501705388883-4ed8a543392c?w=800&auto=format&fit=crop&q=80', 'Hí hí hí... Lộp cộp lộp cộp trên thảo nguyên!', 'Sọc đen trắng của ngựa vằn giúp xua đuổi ruồi bọ và làm hoa mắt kẻ săn mồi!', 'Ngựa vằn có lông màu đen sọc trắng hay màu trắng sọc đen nhỉ?', 'Bé cùng phi ngựa nhịp nhàng lộp cộp lộp cộp quanh phòng khách nhé!', 6],
        ];

        foreach ($animalItems as $item) {
            $itemStmt->execute($item);
        }
    }
}
