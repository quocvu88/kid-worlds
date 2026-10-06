import 'dart:convert';

class MapCoordinates {
  final double x;
  final double y;

  MapCoordinates({required this.x, required this.y});

  factory MapCoordinates.fromJson(String? jsonStr) {
    if (jsonStr == null || jsonStr.isEmpty) {
      return MapCoordinates(x: 100, y: 100);
    }
    try {
      final map = jsonDecode(jsonStr);
      return MapCoordinates(x: (map['x'] as num?)?.toDouble() ?? 100.0, y: (map['y'] as num?)?.toDouble() ?? 100.0);
    } catch (_) {
      return MapCoordinates(x: 100, y: 100);
    }
  }

  String toJson() => jsonEncode({'x': x, 'y': y});
}

class TopicItem {
  final String id;
  final String topicId;
  final String nameVi;
  final String nameEn;
  final String? descriptionVi;
  final String? descriptionEn;
  final String? pronounceEnUrl;
  final String? pronounceViUrl;
  final String imagesJson;
  final String? youtubeVideoId;
  final String? mapCoordinatesJson;

  // Multi-sensory & Parent Guide Extensions
  final String? phonicsEn;
  final String? realImageUrl;
  final String? sfxSound;
  final String? funFactVi;
  final String? funFactEn;
  final String? promptQuestionVi;
  final String? promptQuestionEn;
  final String? actionHintVi;

  TopicItem({
    required this.id,
    required this.topicId,
    required this.nameVi,
    required this.nameEn,
    this.descriptionVi,
    this.descriptionEn,
    this.pronounceEnUrl,
    this.pronounceViUrl,
    required this.imagesJson,
    this.youtubeVideoId,
    this.mapCoordinatesJson,
    this.phonicsEn,
    this.realImageUrl,
    this.sfxSound,
    this.funFactVi,
    this.funFactEn,
    this.promptQuestionVi,
    this.promptQuestionEn,
    this.actionHintVi,
  });

  List<String> get images {
    try {
      final list = jsonDecode(imagesJson);
      if (list is List) {
        return list.map((e) => e.toString()).toList();
      }
    } catch (_) {}
    return [];
  }

  String get primaryImage {
    final imgs = images;
    return imgs.isNotEmpty ? imgs.first : '';
  }

  /// Check if a real-life photograph is available
  bool get hasRealImage {
    if (realImageUrl != null && realImageUrl!.trim().isNotEmpty) return true;
    final imgs = images;
    return imgs.length > 1 && imgs[1].trim().isNotEmpty;
  }

  /// Get the real-life photograph URL or path
  String? get effectiveRealImage {
    if (realImageUrl != null && realImageUrl!.trim().isNotEmpty) {
      return realImageUrl!.trim();
    }
    final imgs = images;
    if (imgs.length > 1 && imgs[1].trim().isNotEmpty) {
      return imgs[1].trim();
    }
    return null;
  }

  /// Check if sound effect (SFX) exists
  bool get hasSfx => sfxSound != null && sfxSound!.trim().isNotEmpty;

  /// Check if Phonics breakdown exists
  bool get hasPhonics => phonicsEn != null && phonicsEn!.trim().isNotEmpty;

  /// Check if any Parent Guide coaching information exists
  bool get hasParentGuide =>
      (funFactVi != null && funFactVi!.trim().isNotEmpty) ||
      (promptQuestionVi != null && promptQuestionVi!.trim().isNotEmpty) ||
      (actionHintVi != null && actionHintVi!.trim().isNotEmpty);

  String? get cleanYoutubeId {
    if (youtubeVideoId == null || youtubeVideoId!.trim().isEmpty) return null;
    final raw = youtubeVideoId!.trim();
    final regExp = RegExp(
      r'(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})',
      caseSensitive: false,
    );
    final match = regExp.firstMatch(raw);
    if (match != null && match.groupCount >= 1) {
      return match.group(1);
    }
    return raw;
  }

  bool get hasYoutubeVideo => cleanYoutubeId != null && cleanYoutubeId!.isNotEmpty;

  String get emoji {
    switch (id) {
      case 'item_lion':
        return '🦁';
      case 'item_elephant':
        return '🐘';
      case 'item_monkey':
        return '🐒';
      case 'item_giraffe':
        return '🦒';
      case 'item_tiger':
        return '🐯';
      case 'item_zebra':
        return '🦓';
      case 'item_car':
        return '🚗';
      case 'item_bus':
        return '🚌';
      case 'item_train':
        return '🚂';
      case 'item_airplane':
        return '✈️';
      case 'item_boat':
        return '⛵';
      case 'item_apple':
        return '🍎';
      case 'item_banana':
        return '🍌';
      case 'item_orange':
        return '🍊';
      case 'item_strawberry':
        return '🍓';
      case 'item_watermelon':
        return '🍉';
      case 'item_sun':
        return '☀️';
      case 'item_earth':
        return '🌍';
      case 'item_moon':
        return '🌙';
      case 'item_rocket':
        return '🚀';
      default:
        return '⭐';
    }
  }

  MapCoordinates get coordinates => MapCoordinates.fromJson(mapCoordinatesJson);

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'topic_id': topicId,
      'name_vi': nameVi,
      'name_en': nameEn,
      'description_vi': descriptionVi,
      'description_en': descriptionEn,
      'pronounce_en_url': pronounceEnUrl,
      'pronounce_vi_url': pronounceViUrl,
      'images_json': imagesJson,
      'youtube_video_id': youtubeVideoId,
      'map_coordinates_json': mapCoordinatesJson,
      'phonics_en': phonicsEn,
      'real_image_url': realImageUrl,
      'sfx_sound': sfxSound,
      'fun_fact_vi': funFactVi,
      'fun_fact_en': funFactEn,
      'prompt_question_vi': promptQuestionVi,
      'prompt_question_en': promptQuestionEn,
      'action_hint_vi': actionHintVi,
    };
  }

  factory TopicItem.fromMap(Map<String, dynamic> map) {
    return TopicItem(
      id: map['id'] as String,
      topicId: map['topic_id'] as String,
      nameVi: map['name_vi'] as String,
      nameEn: map['name_en'] as String,
      descriptionVi: map['description_vi'] as String?,
      descriptionEn: map['description_en'] as String?,
      pronounceEnUrl: map['pronounce_en_url'] as String?,
      pronounceViUrl: map['pronounce_vi_url'] as String?,
      imagesJson: map['images_json'] as String? ?? '[]',
      youtubeVideoId: map['youtube_video_id'] as String?,
      mapCoordinatesJson: map['map_coordinates_json'] as String?,
      phonicsEn: map['phonics_en'] as String?,
      realImageUrl: map['real_image_url'] as String?,
      sfxSound: map['sfx_sound'] as String?,
      funFactVi: map['fun_fact_vi'] as String?,
      funFactEn: map['fun_fact_en'] as String?,
      promptQuestionVi: map['prompt_question_vi'] as String?,
      promptQuestionEn: map['prompt_question_en'] as String?,
      actionHintVi: map['action_hint_vi'] as String?,
    );
  }
}
