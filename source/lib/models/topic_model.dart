import 'dart:convert';

class Topic {
  final String id;
  final String titleVi;
  final String titleEn;
  final String category; // 'animals', 'vehicles', 'space', 'fruits', etc.
  final int targetAgeMin;
  final int targetAgeMax;
  final String targetGender; // 'all', 'male', 'female'
  final String? thumbnailPath;
  final String? backgroundPath;
  final String? themeColor;
  final bool isDownloaded;
  final int version;
  final List<String> selectedGames;

  Topic({
    required this.id,
    required this.titleVi,
    required this.titleEn,
    required this.category,
    this.targetAgeMin = 3,
    this.targetAgeMax = 10,
    this.targetGender = 'all',
    this.thumbnailPath,
    this.backgroundPath,
    this.themeColor,
    this.isDownloaded = false,
    this.version = 1,
    this.selectedGames = const ['coloring', 'memory_match'],
  });

  /// Get appropriate themed background for this topic pack
  String get effectiveBackground {
    if (backgroundPath != null && backgroundPath!.trim().isNotEmpty) {
      return backgroundPath!.trim();
    }
    switch (category.toLowerCase()) {
      case 'animals':
        return 'assets/backgrounds/bg_green_meadow.jpg';
      case 'vehicles':
        return 'assets/backgrounds/bg_balloon_party.jpg';
      case 'fruits':
        return 'assets/backgrounds/bg_candy_land.jpg';
      case 'space':
        return 'assets/backgrounds/bg_pastel_galaxy.jpg';
      default:
        return 'assets/backgrounds/bg_candy_sky.jpg';
    }
  }

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'title_vi': titleVi,
      'title_en': titleEn,
      'category': category,
      'target_age_min': targetAgeMin,
      'target_age_max': targetAgeMax,
      'target_gender': targetGender,
      'thumbnail_path': thumbnailPath,
      'background_path': backgroundPath,
      'theme_color': themeColor,
      'is_downloaded': isDownloaded ? 1 : 0,
      'version': version,
      'selected_games_json': jsonEncode(selectedGames),
    };
  }

  factory Topic.fromMap(Map<String, dynamic> map) {
    List<String> parsedGames = const ['coloring', 'memory_match'];
    final rawGames = map['selected_games_json'];
    if (rawGames is String && rawGames.isNotEmpty) {
      try {
        final decoded = jsonDecode(rawGames);
        if (decoded is List) {
          parsedGames = decoded.map((e) => e.toString()).toList();
        }
      } catch (_) {}
    } else if (map['selected_games'] is List) {
      parsedGames = (map['selected_games'] as List).map((e) => e.toString()).toList();
    }

    return Topic(
      id: map['id'] as String,
      titleVi: map['title_vi'] as String,
      titleEn: map['title_en'] as String,
      category: map['category'] as String,
      targetAgeMin: map['target_age_min'] as int? ?? 3,
      targetAgeMax: map['target_age_max'] as int? ?? 10,
      targetGender: map['target_gender'] as String? ?? 'all',
      thumbnailPath: map['thumbnail_path'] as String?,
      backgroundPath: map['background_path'] as String?,
      themeColor: map['theme_color'] as String?,
      isDownloaded: (map['is_downloaded'] as int? ?? 0) == 1,
      version: map['version'] as int? ?? 1,
      selectedGames: parsedGames,
    );
  }

  Topic copyWith({
    String? id,
    String? titleVi,
    String? titleEn,
    String? category,
    int? targetAgeMin,
    int? targetAgeMax,
    String? targetGender,
    String? thumbnailPath,
    String? backgroundPath,
    String? themeColor,
    bool? isDownloaded,
    int? version,
    List<String>? selectedGames,
  }) {
    return Topic(
      id: id ?? this.id,
      titleVi: titleVi ?? this.titleVi,
      titleEn: titleEn ?? this.titleEn,
      category: category ?? this.category,
      targetAgeMin: targetAgeMin ?? this.targetAgeMin,
      targetAgeMax: targetAgeMax ?? this.targetAgeMax,
      targetGender: targetGender ?? this.targetGender,
      thumbnailPath: thumbnailPath ?? this.thumbnailPath,
      backgroundPath: backgroundPath ?? this.backgroundPath,
      themeColor: themeColor ?? this.themeColor,
      isDownloaded: isDownloaded ?? this.isDownloaded,
      version: version ?? this.version,
      selectedGames: selectedGames ?? this.selectedGames,
    );
  }
}
