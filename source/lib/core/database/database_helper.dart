import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:path/path.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:sqflite/sqflite.dart';
import 'package:sqflite_common_ffi_web/sqflite_ffi_web.dart';

import '../../models/child_model.dart';
import '../../models/screen_time_model.dart';
import '../../models/topic_model.dart';
import '../../models/topic_item_model.dart';
import '../../models/activity_log_model.dart';

class DatabaseHelper {
  static final DatabaseHelper instance = DatabaseHelper._init();
  static Database? _database;

  DatabaseHelper._init();

  /// v3: bật foreign keys, chuyển các bản vá dữ liệu mẫu sang migration (chạy 1 lần), dọn dữ liệu mồ côi.
  static const int _dbVersion = 3;

  Future<void> _configureDB(Database db) async {
    // SQLite mặc định TẮT foreign keys → ON DELETE CASCADE không chạy nếu thiếu dòng này
    await db.execute('PRAGMA foreign_keys = ON');
  }

  Future<Database> get database async {
    if (_database != null) return _database!;
    _database = await _initDB('kids_world.db');
    return _database!;
  }

  Future<Database> _initDB(String filePath) async {
    Database db;
    if (kIsWeb) {
      final factory = databaseFactoryFfiWeb;
      db = await factory.openDatabase(
        'kids_world_web.db',
        options: OpenDatabaseOptions(
          version: _dbVersion,
          onConfigure: _configureDB,
          onCreate: _createDB,
          onUpgrade: _upgradeDB,
        ),
      );
    } else {
      final dbPath = await getDatabasesPath();
      final path = join(dbPath, filePath);
      db = await openDatabase(
        path,
        version: _dbVersion,
        onConfigure: _configureDB,
        onCreate: _createDB,
        onUpgrade: _upgradeDB,
      );
    }
    await _ensureColumnsExist(db);
    return db;
  }

  Future<void> _upgradeDB(Database db, int oldVersion, int newVersion) async {
    if (oldVersion < 2) {
      await _ensureColumnsExist(db);
    }
    if (oldVersion < 3) {
      await _ensureColumnsExist(db);
      await _patchSeedContent(db);
      await _cleanupOrphans(db);
    }
  }

  /// Xoá dữ liệu mồ côi tạo ra trước khi bật foreign keys (ví dụ khi đã xoá bé/chủ đề).
  Future<void> _cleanupOrphans(DatabaseExecutor db) async {
    await db.execute('DELETE FROM screen_time_settings WHERE child_id NOT IN (SELECT id FROM children)');
    await db.execute('DELETE FROM activity_logs WHERE child_id NOT IN (SELECT id FROM children)');
    await db.execute('DELETE FROM topic_items WHERE topic_id NOT IN (SELECT id FROM topics)');
  }

  Future<void> _ensureColumnsExist(Database db) async {
    // Chỉ ALTER những cột thực sự thiếu (trước đây thử ALTER mọi cột mỗi lần mở app rồi nuốt lỗi)
    Future<void> addMissing(String table, List<String> columnDefs) async {
      final info = await db.rawQuery('PRAGMA table_info($table)');
      final existing = info.map((r) => (r['name'] as String).toLowerCase()).toSet();
      for (final def in columnDefs) {
        final name = def.split(' ').first.toLowerCase();
        if (!existing.contains(name)) {
          await db.execute('ALTER TABLE $table ADD COLUMN $def');
        }
      }
    }

    await addMissing('topics', [
      'background_path TEXT',
      'theme_color TEXT',
      'selected_games_json TEXT',
    ]);

    await addMissing('topic_items', [
      'phonics_en TEXT',
      'real_image_url TEXT',
      'sfx_sound TEXT',
      'fun_fact_vi TEXT',
      'fun_fact_en TEXT',
      'prompt_question_vi TEXT',
      'prompt_question_en TEXT',
      'action_hint_vi TEXT',
      'coloring_outline_url TEXT',
    ]);
  }

  Future<void> _createDB(Database db, int version) async {
    // 1. Children table
    await db.execute('''
      CREATE TABLE children (
        id TEXT PRIMARY KEY,
        name TEXT NOT NULL,
        gender TEXT CHECK(gender IN ('male', 'female', 'other')),
        birth_year INTEGER NOT NULL,
        avatar_url TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    // 2. Screen time settings table
    await db.execute('''
      CREATE TABLE screen_time_settings (
        child_id TEXT PRIMARY KEY,
        daily_limit_minutes INTEGER DEFAULT 30,
        weekend_limit_minutes INTEGER DEFAULT 45,
        pin_code TEXT NOT NULL DEFAULT '1234',
        FOREIGN KEY(child_id) REFERENCES children(id) ON DELETE CASCADE
      )
    ''');

    // 3. Topics table
    await db.execute('''
      CREATE TABLE topics (
        id TEXT PRIMARY KEY,
        title_vi TEXT NOT NULL,
        title_en TEXT NOT NULL,
        category TEXT NOT NULL,
        target_age_min INTEGER DEFAULT 3,
        target_age_max INTEGER DEFAULT 10,
        target_gender TEXT DEFAULT 'all',
        thumbnail_path TEXT,
        background_path TEXT,
        theme_color TEXT,
        is_downloaded BOOLEAN DEFAULT 1,
        version INTEGER DEFAULT 1,
        selected_games_json TEXT DEFAULT '["coloring","memory_match"]'
      )
    ''');

    // 4. Topic items table
    await db.execute('''
      CREATE TABLE topic_items (
        id TEXT PRIMARY KEY,
        topic_id TEXT NOT NULL,
        name_vi TEXT NOT NULL,
        name_en TEXT NOT NULL,
        description_vi TEXT,
        description_en TEXT,
        pronounce_en_url TEXT,
        pronounce_vi_url TEXT,
        images_json TEXT NOT NULL,
        youtube_video_id TEXT,
        map_coordinates_json TEXT,
        phonics_en TEXT,
        real_image_url TEXT,
        sfx_sound TEXT,
        fun_fact_vi TEXT,
        fun_fact_en TEXT,
        prompt_question_vi TEXT,
        prompt_question_en TEXT,
        action_hint_vi TEXT,
        coloring_outline_url TEXT,
        FOREIGN KEY(topic_id) REFERENCES topics(id) ON DELETE CASCADE
      )
    ''');

    // 5. Activity logs table
    await db.execute('''
      CREATE TABLE activity_logs (
        id TEXT PRIMARY KEY,
        child_id TEXT NOT NULL,
        item_id TEXT,
        topic_id TEXT,
        action_type TEXT,
        duration_seconds INTEGER DEFAULT 0,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(child_id) REFERENCES children(id) ON DELETE CASCADE
      )
    ''');

    // Seed Initial Sample Data
    await _seedInitialData(db);
    await _patchSeedContent(db);
  }

  Future<void> _seedInitialData(Database db) async {
    // Default Profiles
    await db.insert('children', {
      'id': 'child_1',
      'name': 'Bé Bắp',
      'gender': 'male',
      'birth_year': DateTime.now().year - 4, // 4 years old
      'avatar_url': 'assets/images/avatar_boy.png',
      'created_at': DateTime.now().toIso8601String(),
    });

    await db.insert('screen_time_settings', {
      'child_id': 'child_1',
      'daily_limit_minutes': 30,
      'weekend_limit_minutes': 45,
      'pin_code': '1234',
    });

    await db.insert('children', {
      'id': 'child_2',
      'name': 'Bé Miu',
      'gender': 'female',
      'birth_year': DateTime.now().year - 6, // 6 years old
      'avatar_url': 'assets/images/avatar_girl.png',
      'created_at': DateTime.now().toIso8601String(),
    });

    await db.insert('screen_time_settings', {
      'child_id': 'child_2',
      'daily_limit_minutes': 40,
      'weekend_limit_minutes': 60,
      'pin_code': '1234',
    });

    // Sample Topic 1: Animals
    await db.insert('topics', {
      'id': 'topic_animals',
      'title_vi': 'Thế Giới Động Vật',
      'title_en': 'Wild Animals',
      'category': 'animals',
      'target_age_min': 3,
      'target_age_max': 8,
      'target_gender': 'all',
      'thumbnail_path': 'assets/images/animals_thumb.png',
      'background_path': 'assets/backgrounds/bg_green_meadow.jpg',
      'theme_color': '#4CAF50',
      'is_downloaded': 1,
      'version': 2,
    });

    // Animals Items
    final animals = [
      {
        'id': 'item_lion',
        'topic_id': 'topic_animals',
        'name_vi': 'Sư Tử',
        'name_en': 'Lion',
        'description_vi': 'Sư tử dũng mãnh là vua của thảo nguyên xanh.',
        'description_en': 'The mighty lion is known as the king of the jungle.',
        'pronounce_en_url': 'assets/audio/lion_en.mp3',
        'pronounce_vi_url': 'assets/audio/lion_vi.mp3',
        'images_json': jsonEncode(['assets/images/lion.png', 'https://images.unsplash.com/photo-1546182990-dffeafbe841d?w=800&auto=format&fit=crop&q=80']),
        'youtube_video_id': 'f9qIBCNxXXM',
        'map_coordinates_json': jsonEncode({'x': 120.0, 'y': 220.0}),
        'phonics_en': 'L - /l/ - Lion',
        'real_image_url': 'https://images.unsplash.com/photo-1546182990-dffeafbe841d?w=800&auto=format&fit=crop&q=80',
        'sfx_sound': 'Gừừừ... Roaaar! Sư tử dũng mãnh!',
        'fun_fact_vi': 'Sư tử đực có chiếc bờm xù rất oai phong để bảo vệ cổ và làm đẹp với các bạn sư tử cái!',
        'prompt_question_vi': 'Đố con biết chú sư tử ngủ bao nhiêu tiếng một ngày? (Tận 20 tiếng đấy!)',
        'action_hint_vi': 'Mẹ và bé hãy cùng xòe móng vuốt và bắt chước tiếng sư tử gầm thật oai vệ nào!',
      },
      {
        'id': 'item_elephant',
        'topic_id': 'topic_animals',
        'name_vi': 'Con Voi',
        'name_en': 'Elephant',
        'description_vi': 'Chú voi hiền lành có chiếc vòi dài uống nước.',
        'description_en': 'The gentle elephant has a long trunk to drink water.',
        'pronounce_en_url': 'assets/audio/elephant_en.mp3',
        'pronounce_vi_url': 'assets/audio/elephant_vi.mp3',
        'images_json': jsonEncode(['assets/images/elephant.png', 'https://images.unsplash.com/photo-1557050543-4d5f4e07ef46?w=800&auto=format&fit=crop&q=80']),
        'youtube_video_id': 'z4FbTIldHys',
        'map_coordinates_json': jsonEncode({'x': 320.0, 'y': 180.0}),
        'phonics_en': 'E - /e/ - Elephant',
        'real_image_url': 'https://images.unsplash.com/photo-1557050543-4d5f4e07ef46?w=800&auto=format&fit=crop&q=80',
        'sfx_sound': 'Pawoo! Éc éc! Tiếng chú voi con gọi mẹ!',
        'fun_fact_vi': 'Chiếc vòi của voi không hề có xương mà được tạo bởi hơn 40.000 bó cơ bắp khéo léo!',
        'prompt_question_vi': 'Đố bé vòi của chú voi dùng để làm gì nào? (Uống nước, gắp thức ăn và tắm mát)',
        'action_hint_vi': 'Bé hãy dùng một cánh tay vươn dài làm chiếc vòi voi đung đưa hút nước uống nhé!',
      },
      {
        'id': 'item_monkey',
        'topic_id': 'topic_animals',
        'name_vi': 'Con Khỉ',
        'name_en': 'Monkey',
        'description_vi': 'Chú khỉ tinh nghịch rất thích chuyền cành và ăn chuối.',
        'description_en': 'The playful monkey loves swinging on branches and eating bananas.',
        'pronounce_en_url': 'assets/audio/monkey_en.mp3',
        'pronounce_vi_url': 'assets/audio/monkey_vi.mp3',
        'images_json': jsonEncode(['assets/images/monkey.png', 'https://images.unsplash.com/photo-1540573133985-87b6da6d54a9?w=800&auto=format&fit=crop&q=80']),
        'youtube_video_id': 'fN1Cyr0ZK9M',
        'map_coordinates_json': jsonEncode({'x': 540.0, 'y': 140.0}),
        'phonics_en': 'M - /m/ - Monkey',
        'real_image_url': 'https://images.unsplash.com/photo-1540573133985-87b6da6d54a9?w=800&auto=format&fit=crop&q=80',
        'sfx_sound': 'Khẹc khẹc... Oó oó! Chú khỉ nhảy nhót!',
        'fun_fact_vi': 'Các bạn khỉ có chiếc đuôi dài khéo léo như bàn tay thứ năm giúp bám chặt vào cành cây!',
        'prompt_question_vi': 'Đố bé món ăn thơm ngọt khoái khẩu nhất của chú khỉ là quả gì nào?',
        'action_hint_vi': 'Bé cùng mẹ gãi đầu và giả làm những chú khỉ tinh nghịch chuyền cành nào!',
      },
      {
        'id': 'item_giraffe',
        'topic_id': 'topic_animals',
        'name_vi': 'Hươu Cao Cổ',
        'name_en': 'Giraffe',
        'description_vi': 'Hươu cao cổ có chiếc cổ rất dài để ăn lá trên ngọn cây.',
        'description_en': 'The tall giraffe reaches the highest leaves with its long neck.',
        'pronounce_en_url': 'assets/audio/giraffe_en.mp3',
        'pronounce_vi_url': 'assets/audio/giraffe_vi.mp3',
        'images_json': jsonEncode(['assets/images/giraffe.png', 'https://images.unsplash.com/photo-1547721064-da6cfb341d50?w=800&auto=format&fit=crop&q=80']),
        'youtube_video_id': 'pWepfJ-8XU0',
        'map_coordinates_json': jsonEncode({'x': 750.0, 'y': 190.0}),
        'phonics_en': 'G - /dʒ/ - Giraffe',
        'real_image_url': 'https://images.unsplash.com/photo-1547721064-da6cfb341d50?w=800&auto=format&fit=crop&q=80',
        'sfx_sound': 'Ụm bò... Hươu cao cổ dạo bước êm đềm!',
        'fun_fact_vi': 'Hươu cao cổ có chiếc lưỡi màu xanh đen dài tới 45cm để hái lá trên ngọn cây gai!',
        'prompt_question_vi': 'Đố con hươu cao cổ ngủ đứng hay ngủ nằm? (Bạn ấy chỉ ngủ đứng khoảng 30 phút mỗi ngày thôi!)',
        'action_hint_vi': 'Hai mẹ con cùng kiễng chân vươn cổ thật cao chạm trần nhà nào!',
      },
      {
        'id': 'item_tiger',
        'topic_id': 'topic_animals',
        'name_vi': 'Con Hổ',
        'name_en': 'Tiger',
        'description_vi': 'Chú hổ dũng mãnh có bộ lông vằn cam đen nổi bật.',
        'description_en': 'The powerful tiger has beautiful orange and black stripes.',
        'pronounce_en_url': 'assets/audio/tiger_en.mp3',
        'pronounce_vi_url': 'assets/audio/tiger_vi.mp3',
        'images_json': jsonEncode(['assets/images/tiger.png', 'https://images.unsplash.com/photo-1534188753412-3e26d0d618d6?w=800&auto=format&fit=crop&q=80']),
        'youtube_video_id': 'p_IEUJqThZE',
        'map_coordinates_json': jsonEncode({'x': 980.0, 'y': 240.0}),
        'phonics_en': 'T - /t/ - Tiger',
        'real_image_url': 'https://images.unsplash.com/photo-1534188753412-3e26d0d618d6?w=800&auto=format&fit=crop&q=80',
        'sfx_sound': 'Gừừừ... Roaaar! Hổ dũng mãnh sơn lâm!',
        'fun_fact_vi': 'Không có hai chú hổ nào có sọc giống hệt nhau, giống như dấu vân tay của con người vậy!',
        'prompt_question_vi': 'Hổ bơi rất giỏi và thích tắm mát, bé có thích đi bơi giống chú hổ không?',
        'action_hint_vi': 'Bé hãy bước đi rón rén thật êm như những bước chân đệm thịt của chú hổ!',
      },
      {
        'id': 'item_zebra',
        'topic_id': 'topic_animals',
        'name_vi': 'Ngựa Vằn',
        'name_en': 'Zebra',
        'description_vi': 'Ngựa vằn khoác trên mình chiếc áo sọc trắng đen tuyệt đẹp.',
        'description_en': 'The zebra wears an iconic black and white striped coat.',
        'pronounce_en_url': 'assets/audio/zebra_en.mp3',
        'pronounce_vi_url': 'assets/audio/zebra_vi.mp3',
        'images_json': jsonEncode(['assets/images/zebra.png', 'https://images.unsplash.com/photo-1501705388883-4ed8a543392c?w=800&auto=format&fit=crop&q=80']),
        'youtube_video_id': 'j4lDDQTKN8s',
        'map_coordinates_json': jsonEncode({'x': 1200.0, 'y': 210.0}),
        'phonics_en': 'Z - /z/ - Zebra',
        'real_image_url': 'https://images.unsplash.com/photo-1501705388883-4ed8a543392c?w=800&auto=format&fit=crop&q=80',
        'sfx_sound': 'Hí hí hí... Lộp cộp lộp cộp trên thảo nguyên!',
        'fun_fact_vi': 'Sọc đen trắng của ngựa vằn giúp xua đuổi ruồi bọ và làm hoa mắt kẻ săn mồi!',
        'prompt_question_vi': 'Ngựa vằn có lông màu đen sọc trắng hay màu trắng sọc đen nhỉ?',
        'action_hint_vi': 'Bé cùng phi ngựa nhịp nhàng lộp cộp lộp cộp quanh phòng khách nhé!',
      },
    ];

    for (final item in animals) {
      await db.insert('topic_items', item);
    }

    // Sample Topic 2: Vehicles
    await db.insert('topics', {
      'id': 'topic_vehicles',
      'title_vi': 'Phương Tiện Giao Thông',
      'title_en': 'Vehicles & Transport',
      'category': 'vehicles',
      'target_age_min': 3,
      'target_age_max': 9,
      'target_gender': 'all',
      'thumbnail_path': 'assets/images/vehicles_thumb.png',
      'background_path': 'assets/backgrounds/bg_balloon_party.jpg',
      'theme_color': '#2196F3',
      'is_downloaded': 1,
      'version': 2,
    });

    final vehicles = [
      {
        'id': 'item_car',
        'topic_id': 'topic_vehicles',
        'name_vi': 'Xe Ô Tô',
        'name_en': 'Car',
        'description_vi': 'Chiếc xe hơi bon bon chạy trên đường phố.',
        'description_en': 'The family car travels smoothly on the city roads.',
        'pronounce_en_url': 'assets/audio/car_en.mp3',
        'pronounce_vi_url': 'assets/audio/car_vi.mp3',
        'images_json': jsonEncode(['assets/images/car.png', 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=800&auto=format&fit=crop&q=80']),
        'youtube_video_id': 'yWwY9U1A8mE',
        'map_coordinates_json': jsonEncode({'x': 150.0, 'y': 250.0}),
        'phonics_en': 'C - /k/ - Car',
        'real_image_url': 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=800&auto=format&fit=crop&q=80',
        'sfx_sound': 'Bíp bíp! Honk honk! Xe hơi bon bon lăn bánh!',
        'fun_fact_vi': 'Một chiếc xe ô tô hiện đại được lắp ghép từ hơn 30.000 linh kiện khác nhau!',
        'prompt_question_vi': 'Khi ngồi trên xe ô tô, việc đầu tiên bé và cha mẹ cần làm là gì? (Cài dây an toàn nhé!)',
        'action_hint_vi': 'Bé hãy cầm vô lăng vô hình, bấm còi Bíp bíp và cùng lái xe quanh nhà nào!',
      },
      {
        'id': 'item_bus',
        'topic_id': 'topic_vehicles',
        'name_vi': 'Xe Buýt',
        'name_en': 'Bus',
        'description_vi': 'Xe buýt màu vàng chở các bạn nhỏ tới trường.',
        'description_en': 'The yellow school bus takes kids happily to school.',
        'pronounce_en_url': 'assets/audio/bus_en.mp3',
        'pronounce_vi_url': 'assets/audio/bus_vi.mp3',
        'images_json': jsonEncode(['assets/images/bus.png', 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=800&auto=format&fit=crop&q=80']),
        'youtube_video_id': 'e_04ZrNroTo',
        'map_coordinates_json': jsonEncode({'x': 400.0, 'y': 240.0}),
        'phonics_en': 'B - /b/ - Bus',
        'real_image_url': 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=800&auto=format&fit=crop&q=80',
        'sfx_sound': 'Bíp bíp! Xe buýt trường học đón các bạn nhỏ!',
        'fun_fact_vi': 'Xe buýt trường học ở Mỹ thường có màu vàng đặc biệt để các xe khác dễ nhìn thấy từ xa!',
        'prompt_question_vi': 'Đố bé xe buýt chở được nhiều người hơn hay xe ô tô 4 chỗ chở được nhiều hơn?',
        'action_hint_vi': 'Mẹ và bé hãy xếp hàng nối đuôi nhau làm đoàn xe buýt chở các bạn gấu bông nhé!',
      },
      {
        'id': 'item_train',
        'topic_id': 'topic_vehicles',
        'name_vi': 'Tàu Hỏa',
        'name_en': 'Train',
        'description_vi': 'Đoàn tàu hỏa xình xịch chạy trên đường ray dài.',
        'description_en': 'The chugging train rides along the long railway track.',
        'pronounce_en_url': 'assets/audio/train_en.mp3',
        'pronounce_vi_url': 'assets/audio/train_vi.mp3',
        'images_json': jsonEncode(['assets/images/train.png', 'https://images.unsplash.com/photo-1474487548417-781cb71495f3?w=800&auto=format&fit=crop&q=80']),
        'youtube_video_id': 'OTfKMw0v_40',
        'map_coordinates_json': jsonEncode({'x': 680.0, 'y': 270.0}),
        'phonics_en': 'T - /t/ - Train',
        'real_image_url': 'https://images.unsplash.com/photo-1474487548417-781cb71495f3?w=800&auto=format&fit=crop&q=80',
        'sfx_sound': 'Tu tu xình xịch! Choo choo! Tàu hỏa về ga!',
        'fun_fact_vi': 'Tàu hỏa chạy trên hai thanh đường ray bằng thép thẳng tắp và bánh xe cũng làm bằng thép!',
        'prompt_question_vi': 'Đố con đoàn tàu hỏa chạy phát ra tiếng kêu gì vui tai nào?',
        'action_hint_vi': 'Hai tay bé xoay tròn như bánh xe lăn và miệng kêu xình xịch... xình xịch... tu tu!',
      },
      {
        'id': 'item_airplane',
        'topic_id': 'topic_vehicles',
        'name_vi': 'Máy Bay',
        'name_en': 'Airplane',
        'description_vi': 'Máy bay lượn trên bầu trời xanh đưa mọi người bay thật xa.',
        'description_en': 'The airplane flies high in the blue sky above the clouds.',
        'pronounce_en_url': 'assets/audio/airplane_en.mp3',
        'pronounce_vi_url': 'assets/audio/airplane_vi.mp3',
        'images_json': jsonEncode(['assets/images/airplane.png', 'https://images.unsplash.com/photo-1520437358207-323b43b50729?w=800&auto=format&fit=crop&q=80']),
        'youtube_video_id': '3xWkG2d7_gU',
        'map_coordinates_json': jsonEncode({'x': 950.0, 'y': 100.0}),
        'phonics_en': 'A - /eə/ - Airplane',
        'real_image_url': 'https://images.unsplash.com/photo-1520437358207-323b43b50729?w=800&auto=format&fit=crop&q=80',
        'sfx_sound': 'Vù vù vút... Whooosh! Máy bay lượn trên mây!',
        'fun_fact_vi': 'Máy bay bay ở độ cao hơn 10.000 mét, nơi nhiệt độ bên ngoài lạnh tới âm 50 độ C!',
        'prompt_question_vi': 'Người lái chiếc máy bay chở mọi người bay trên trời gọi là gì nhỉ? (Phi công)',
        'action_hint_vi': 'Bé dang hai cánh tay thật rộng làm đôi cánh máy bay bay lượn qua các đám mây!',
      },
      {
        'id': 'item_boat',
        'topic_id': 'topic_vehicles',
        'name_vi': 'Thuyền Thủy Thủ',
        'name_en': 'Boat',
        'description_vi': 'Thuyền buồm lướt êm đềm trên mặt biển biếc.',
        'description_en': 'The sailboat glides smoothly across the gentle waves.',
        'pronounce_en_url': 'assets/audio/boat_en.mp3',
        'pronounce_vi_url': 'assets/audio/boat_vi.mp3',
        'images_json': jsonEncode(['assets/images/boat.png', 'https://images.unsplash.com/photo-1505705694340-019e1e335916?w=800&auto=format&fit=crop&q=80']),
        'youtube_video_id': '1GDFa-nEzlg',
        'map_coordinates_json': jsonEncode({'x': 1220.0, 'y': 310.0}),
        'phonics_en': 'B - /b/ - Boat',
        'real_image_url': 'https://images.unsplash.com/photo-1505705694340-019e1e335916?w=800&auto=format&fit=crop&q=80',
        'sfx_sound': 'Tò tí te... Tiếng còi tàu rẽ sóng ra khơi!',
        'fun_fact_vi': 'Thuyền buồm không cần động cơ xăng mà chạy bằng sức gió thổi căng cánh buồm!',
        'prompt_question_vi': 'Thuyền buồm di chuyển trên mặt nước hay trên bầu trời vậy con?',
        'action_hint_vi': 'Hai mẹ con ngồi đối diện nhau, nắm tay nhau đu đưa nhịp nhàng chèo thuyền nhé!',
      },
    ];

    for (final item in vehicles) {
      await db.insert('topic_items', item);
    }
  }

  // Children CRUD
  Future<List<Child>> getChildren() async {
    final db = await database;
    final maps = await db.query('children', orderBy: 'created_at ASC');
    return maps.map((e) => Child.fromMap(e)).toList();
  }

  Future<Child?> getChildById(String id) async {
    final db = await database;
    final maps = await db.query('children', where: 'id = ?', whereArgs: [id]);
    if (maps.isNotEmpty) {
      return Child.fromMap(maps.first);
    }
    return null;
  }

  Future<void> insertChild(Child child, ScreenTimeSettings settings) async {
    final db = await database;
    await db.transaction((txn) async {
      await txn.insert('children', child.toMap(), conflictAlgorithm: ConflictAlgorithm.replace);
      await txn.insert('screen_time_settings', settings.toMap(), conflictAlgorithm: ConflictAlgorithm.replace);
    });
  }

  Future<void> updateChild(Child child) async {
    final db = await database;
    await db.update('children', child.toMap(), where: 'id = ?', whereArgs: [child.id]);
  }

  Future<void> deleteChild(String id) async {
    final db = await database;
    await db.transaction((txn) async {
      // Xoá tường minh (phòng khi foreign keys không khả dụng trên nền tảng nào đó)
      await txn.delete('activity_logs', where: 'child_id = ?', whereArgs: [id]);
      await txn.delete('screen_time_settings', where: 'child_id = ?', whereArgs: [id]);
      await txn.delete('children', where: 'id = ?', whereArgs: [id]);
    });
  }

  // Screen Time Settings
  Future<ScreenTimeSettings> getScreenTimeSettings(String childId) async {
    final db = await database;
    final maps = await db.query('screen_time_settings', where: 'child_id = ?', whereArgs: [childId]);
    if (maps.isNotEmpty) {
      return ScreenTimeSettings.fromMap(maps.first);
    }
    return ScreenTimeSettings(childId: childId);
  }

  Future<void> updateScreenTimeSettings(ScreenTimeSettings settings) async {
    final db = await database;
    await db.insert('screen_time_settings', settings.toMap(), conflictAlgorithm: ConflictAlgorithm.replace);
  }

  // Master Parent PIN
  Future<String> getMasterPin() async {
    final prefs = await SharedPreferences.getInstance();
    final savedPin = prefs.getString('parent_master_pin');
    if (savedPin != null && savedPin.isNotEmpty) {
      return savedPin;
    }
    return '1234';
  }

  Future<void> updateMasterPin(String newPin) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('parent_master_pin', newPin);
    final db = await database;
    await db.rawUpdate('UPDATE screen_time_settings SET pin_code = ?', [newPin]);
  }

  // Topics & Items
  Future<List<Topic>> getTopics() async {
    final db = await database;
    final maps = await db.query('topics');
    return maps.map((e) => Topic.fromMap(e)).toList();
  }

  Future<Topic?> getTopic(String topicId) async {
    final db = await database;
    final maps = await db.query('topics', where: 'id = ?', whereArgs: [topicId]);
    if (maps.isNotEmpty) {
      return Topic.fromMap(maps.first);
    }
    return null;
  }

  Future<void> upsertTopic(Topic topic) async {
    final db = await database;
    await db.insert('topics', topic.toMap(), conflictAlgorithm: ConflictAlgorithm.replace);
  }

  Future<void> insertOrReplaceTopicItems(List<TopicItem> items) async {
    final db = await database;
    await db.transaction((txn) async {
      for (final item in items) {
        await txn.insert('topic_items', item.toMap(), conflictAlgorithm: ConflictAlgorithm.replace);
      }
    });
  }

  /// Atomically replace a topic and all its items with newly downloaded data from CMS
  Future<void> replaceTopicWithItems(Topic topic, List<TopicItem> items) async {
    final db = await database;
    await db.transaction((txn) async {
      await txn.insert('topics', topic.toMap(), conflictAlgorithm: ConflictAlgorithm.replace);
      // Clean existing items for this topic to avoid stale/ghost items from previous versions
      await txn.delete('topic_items', where: 'topic_id = ?', whereArgs: [topic.id]);
      for (final item in items) {
        await txn.insert('topic_items', item.toMap(), conflictAlgorithm: ConflictAlgorithm.replace);
      }
    });
  }

  Future<void> deleteTopicAndItems(String topicId) async {
    final db = await database;
    await db.transaction((txn) async {
      await txn.delete('topic_items', where: 'topic_id = ?', whereArgs: [topicId]);
      await txn.delete('topics', where: 'id = ?', whereArgs: [topicId]);
    });
  }

  /// Bản vá dữ liệu mẫu (trước đây chạy ~17 lệnh UPDATE mỗi lần mở chủ đề).
  /// Nay chỉ chạy 1 lần khi tạo/nâng cấp DB.
  Future<void> _patchSeedContent(DatabaseExecutor db) async {
    // Auto-update to verified active YouTube videos
    await db.rawUpdate("UPDATE topic_items SET youtube_video_id = 'f9qIBCNxXXM' WHERE id = 'item_lion' AND (youtube_video_id IS NULL OR youtube_video_id = '4mNZK2H9v-o')");
    await db.rawUpdate("UPDATE topic_items SET youtube_video_id = 'z4FbTIldHys' WHERE id = 'item_elephant' AND (youtube_video_id IS NULL OR youtube_video_id = '4k3uLgT8D2o')");
    await db.rawUpdate("UPDATE topic_items SET youtube_video_id = 'p_IEUJqThZE' WHERE id = 'item_tiger' AND (youtube_video_id IS NULL OR youtube_video_id = '020gEDjM9Q8')");
    await db.rawUpdate("UPDATE topic_items SET youtube_video_id = 'j4lDDQTKN8s' WHERE id = 'item_zebra' AND (youtube_video_id IS NULL OR youtube_video_id = '020gEDjM9Q8')");
    await db.rawUpdate("UPDATE topic_items SET youtube_video_id = 'e_04ZrNroTo' WHERE id = 'item_car' AND (youtube_video_id IS NULL OR youtube_video_id = 'yWwY9U1A8mE')");
    await db.rawUpdate("UPDATE topic_items SET youtube_video_id = '1GDFa-nEzlg' WHERE id = 'item_boat' AND (youtube_video_id IS NULL OR youtube_video_id = '1GDFa-nEzlg')");

    // Auto-update topics background
    await db.rawUpdate("UPDATE topics SET background_path = 'assets/backgrounds/bg_green_meadow.jpg', theme_color = '#4CAF50' WHERE id = 'topic_animals' AND (background_path IS NULL OR background_path = '')");
    await db.rawUpdate("UPDATE topics SET background_path = 'assets/backgrounds/bg_balloon_party.jpg', theme_color = '#2196F3' WHERE id = 'topic_vehicles' AND (background_path IS NULL OR background_path = '')");

    // Auto-populate multi-sensory and parent guide fields if missing
    await db.rawUpdate('''
      UPDATE topic_items SET 
        phonics_en = 'L - /l/ - Lion',
        real_image_url = 'https://images.unsplash.com/photo-1546182990-dffeafbe841d?w=800&auto=format&fit=crop&q=80',
        sfx_sound = 'Gừừừ... Roaaar! Sư tử dũng mãnh!',
        fun_fact_vi = 'Sư tử đực có chiếc bờm xù rất oai phong để bảo vệ cổ và làm đẹp với các bạn sư tử cái!',
        prompt_question_vi = 'Đố con biết chú sư tử ngủ bao nhiêu tiếng một ngày? (Tận 20 tiếng đấy!)',
        action_hint_vi = 'Mẹ và bé hãy cùng xòe móng vuốt và bắt chước tiếng sư tử gầm thật oai vệ nào!'
      WHERE id = 'item_lion' AND (phonics_en IS NULL OR phonics_en = '');
    ''');
    await db.rawUpdate('''
      UPDATE topic_items SET 
        phonics_en = 'E - /e/ - Elephant',
        real_image_url = 'https://images.unsplash.com/photo-1557050543-4d5f4e07ef46?w=800&auto=format&fit=crop&q=80',
        sfx_sound = 'Pawoo! Éc éc! Tiếng chú voi con gọi mẹ!',
        fun_fact_vi = 'Chiếc vòi của voi không hề có xương mà được tạo bởi hơn 40.000 bó cơ bắp khéo léo!',
        prompt_question_vi = 'Đố bé vòi của chú voi dùng để làm gì nào? (Uống nước, gắp thức ăn và tắm mát)',
        action_hint_vi = 'Bé hãy dùng một cánh tay vươn dài làm chiếc vòi voi đung đưa hút nước uống nhé!'
      WHERE id = 'item_elephant' AND (phonics_en IS NULL OR phonics_en = '');
    ''');
    await db.rawUpdate('''
      UPDATE topic_items SET 
        phonics_en = 'M - /m/ - Monkey',
        real_image_url = 'https://images.unsplash.com/photo-1540573133985-87b6da6d54a9?w=800&auto=format&fit=crop&q=80',
        sfx_sound = 'Khẹc khẹc... Oó oó! Chú khỉ nhảy nhót!',
        fun_fact_vi = 'Các bạn khỉ có chiếc đuôi dài khéo léo như bàn tay thứ năm giúp bám chặt vào cành cây!',
        prompt_question_vi = 'Đố bé món ăn thơm ngọt khoái khẩu nhất của chú khỉ là quả gì nào?',
        action_hint_vi = 'Bé cùng mẹ gãi đầu và giả làm những chú khỉ tinh nghịch chuyền cành nào!'
      WHERE id = 'item_monkey' AND (phonics_en IS NULL OR phonics_en = '');
    ''');
    await db.rawUpdate('''
      UPDATE topic_items SET 
        phonics_en = 'G - /dʒ/ - Giraffe',
        real_image_url = 'https://images.unsplash.com/photo-1547721064-da6cfb341d50?w=800&auto=format&fit=crop&q=80',
        sfx_sound = 'Ụm bò... Hươu cao cổ dạo bước êm đềm!',
        fun_fact_vi = 'Hươu cao cổ có chiếc lưỡi màu xanh đen dài tới 45cm để hái lá trên ngọn cây gai!',
        prompt_question_vi = 'Đố con hươu cao cổ ngủ đứng hay ngủ nằm? (Bạn ấy chỉ ngủ đứng khoảng 30 phút mỗi ngày thôi!)',
        action_hint_vi = 'Hai mẹ con cùng kiễng chân vươn cổ thật cao chạm trần nhà nào!'
      WHERE id = 'item_giraffe' AND (phonics_en IS NULL OR phonics_en = '');
    ''');
    await db.rawUpdate('''
      UPDATE topic_items SET 
        phonics_en = 'T - /t/ - Tiger',
        real_image_url = 'https://images.unsplash.com/photo-1534188753412-3e26d0d618d6?w=800&auto=format&fit=crop&q=80',
        sfx_sound = 'Gừừừ... Roaaar! Hổ dũng mãnh sơn lâm!',
        fun_fact_vi = 'Không có hai chú hổ nào có sọc giống hệt nhau, giống như dấu vân tay của con người vậy!',
        prompt_question_vi = 'Hổ bơi rất giỏi và thích tắm mát, bé có thích đi bơi giống chú hổ không?',
        action_hint_vi = 'Bé hãy bước đi rón rén thật êm như những bước chân đệm thịt của chú hổ!'
      WHERE id = 'item_tiger' AND (phonics_en IS NULL OR phonics_en = '');
    ''');
    await db.rawUpdate('''
      UPDATE topic_items SET 
        phonics_en = 'Z - /z/ - Zebra',
        real_image_url = 'https://images.unsplash.com/photo-1501705388883-4ed8a543392c?w=800&auto=format&fit=crop&q=80',
        sfx_sound = 'Hí hí hí... Lộp cộp lộp cộp trên thảo nguyên!',
        fun_fact_vi = 'Sọc đen trắng của ngựa vằn giúp xua đuổi ruồi bọ và làm hoa mắt kẻ săn mồi!',
        prompt_question_vi = 'Ngựa vằn có lông màu đen sọc trắng hay màu trắng sọc đen nhỉ?',
        action_hint_vi = 'Bé cùng phi ngựa nhịp nhàng lộp cộp lộp cộp quanh phòng khách nhé!'
      WHERE id = 'item_zebra' AND (phonics_en IS NULL OR phonics_en = '');
    ''');
    await db.rawUpdate('''
      UPDATE topic_items SET 
        phonics_en = 'C - /k/ - Car',
        real_image_url = 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=800&auto=format&fit=crop&q=80',
        sfx_sound = 'Bíp bíp! Honk honk! Xe hơi bon bon lăn bánh!',
        fun_fact_vi = 'Một chiếc xe ô tô hiện đại được lắp ghép từ hơn 30.000 linh kiện khác nhau!',
        prompt_question_vi = 'Khi ngồi trên xe ô tô, việc đầu tiên bé và cha mẹ cần làm là gì? (Cài dây an toàn nhé!)',
        action_hint_vi = 'Bé hãy cầm vô lăng vô hình, bấm còi Bíp bíp và cùng lái xe quanh nhà nào!'
      WHERE id = 'item_car' AND (phonics_en IS NULL OR phonics_en = '');
    ''');
    await db.rawUpdate('''
      UPDATE topic_items SET 
        phonics_en = 'B - /b/ - Bus',
        real_image_url = 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=800&auto=format&fit=crop&q=80',
        sfx_sound = 'Bíp bíp! Xe buýt trường học đón các bạn nhỏ!',
        fun_fact_vi = 'Xe buýt trường học ở Mỹ thường có màu vàng đặc biệt để các xe khác dễ nhìn thấy từ xa!',
        prompt_question_vi = 'Đố bé xe buýt chở được nhiều người hơn hay xe ô tô 4 chỗ chở được nhiều hơn?',
        action_hint_vi = 'Mẹ và bé hãy xếp hàng nối đuôi nhau làm đoàn xe buýt chở các bạn gấu bông nhé!'
      WHERE id = 'item_bus' AND (phonics_en IS NULL OR phonics_en = '');
    ''');
    await db.rawUpdate('''
      UPDATE topic_items SET 
        phonics_en = 'T - /t/ - Train',
        real_image_url = 'https://images.unsplash.com/photo-1474487548417-781cb71495f3?w=800&auto=format&fit=crop&q=80',
        sfx_sound = 'Tu tu xình xịch! Choo choo! Tàu hỏa về ga!',
        fun_fact_vi = 'Tàu hỏa chạy trên hai thanh đường ray bằng thép thẳng tắp và bánh xe cũng làm bằng thép!',
        prompt_question_vi = 'Đố con đoàn tàu hỏa chạy phát ra tiếng kêu gì vui tai nào?',
        action_hint_vi = 'Hai tay bé xoay tròn như bánh xe lăn và miệng kêu xình xịch... xình xịch... tu tu!'
      WHERE id = 'item_train' AND (phonics_en IS NULL OR phonics_en = '');
    ''');
    await db.rawUpdate('''
      UPDATE topic_items SET 
        phonics_en = 'A - /eə/ - Airplane',
        real_image_url = 'https://images.unsplash.com/photo-1520437358207-323b43b50729?w=800&auto=format&fit=crop&q=80',
        sfx_sound = 'Vù vù vút... Whooosh! Máy bay lượn trên mây!',
        fun_fact_vi = 'Máy bay bay ở độ cao hơn 10.000 mét, nơi nhiệt độ bên ngoài lạnh tới âm 50 độ C!',
        prompt_question_vi = 'Người lái chiếc máy bay chở mọi người bay trên trời gọi là gì nhỉ? (Phi công)',
        action_hint_vi = 'Bé dang hai cánh tay thật rộng làm đôi cánh máy bay bay lượn qua các đám mây!'
      WHERE id = 'item_airplane' AND (phonics_en IS NULL OR phonics_en = '');
    ''');
    await db.rawUpdate('''
      UPDATE topic_items SET 
        phonics_en = 'B - /b/ - Boat',
        real_image_url = 'https://images.unsplash.com/photo-1505705694340-019e1e335916?w=800&auto=format&fit=crop&q=80',
        sfx_sound = 'Tò tí te... Tiếng còi tàu rẽ sóng ra khơi!',
        fun_fact_vi = 'Thuyền buồm không cần động cơ xăng mà chạy bằng sức gió thổi căng cánh buồm!',
        prompt_question_vi = 'Thuyền buồm di chuyển trên mặt nước hay trên bầu trời vậy con?',
        action_hint_vi = 'Hai mẹ con ngồi đối diện nhau, nắm tay nhau đu đưa nhịp nhàng chèo thuyền nhé!'
      WHERE id = 'item_boat' AND (phonics_en IS NULL OR phonics_en = '');
    ''');

  }

  Future<List<TopicItem>> getTopicItems(String topicId) async {
    final db = await database;
    final maps = await db.query('topic_items', where: 'topic_id = ?', whereArgs: [topicId]);
    return maps.map((e) => TopicItem.fromMap(e)).toList();
  }

  // Activity Logs
  Future<void> insertActivityLog(ActivityLog log) async {
    final db = await database;
    await db.insert('activity_logs', log.toMap());
  }

  Future<List<ActivityLog>> getActivityLogs(String childId) async {
    final db = await database;
    final maps = await db.query(
      'activity_logs',
      where: 'child_id = ?',
      whereArgs: [childId],
      orderBy: 'timestamp DESC',
    );
    return maps.map((e) => ActivityLog.fromMap(e)).toList();
  }

  Future<Map<String, dynamic>> getChildStats(String childId) async {
    final db = await database;

    // Thời gian dùng hôm nay: lấy từ bộ đếm thời gian thực của ScreenTimeService
    // (trước đây cộng cứng 5 giây/hành động nên luôn sai).
    final now = DateTime.now();
    final today =
        '${now.year}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}';
    final prefs = await SharedPreferences.getInstance();
    final usedSecondsToday = prefs.getInt('screen_time_${childId}_$today') ?? 0;

    // Distinct words heard/practiced
    final wordsCount = await db.rawQuery(
      '''
      SELECT COUNT(DISTINCT item_id) as total_words
      FROM activity_logs
      WHERE child_id = ? AND action_type IN ('listen_pronounce', 'view_card', 'listen_phonics')
    ''',
      [childId],
    );

    // Videos watched (app ghi 'watch_youtube'; giữ 'watch_video' cho dữ liệu cũ)
    final videoLogs = await db.rawQuery(
      '''
      SELECT COUNT(*) as total_videos
      FROM activity_logs
      WHERE child_id = ? AND action_type IN ('watch_youtube', 'watch_video')
    ''',
      [childId],
    );

    return {
      'total_minutes_today': (usedSecondsToday / 60).round(),
      'total_words_learned': wordsCount.first['total_words'] as int? ?? 0,
      'total_videos_watched': videoLogs.first['total_videos'] as int? ?? 0,
    };
  }
}
