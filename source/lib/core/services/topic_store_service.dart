import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;

import '../../models/topic_model.dart';
import '../../models/topic_item_model.dart';
import '../database/database_helper.dart';
import 'content_server_config_service.dart';

class ServerTopicPack {
  final String id;
  final String titleVi;
  final String titleEn;
  final String category;
  final int targetAgeMin;
  final int targetAgeMax;
  final String targetGender;
  final String thumbnailUrl;
  final String descriptionVi;
  final String descriptionEn;
  final int version;
  final double sizeMb;
  final int itemCount;
  final String downloadUrl;

  // Local state
  final bool isInstalled;
  final int? localVersion;

  bool get hasUpdate => isInstalled && localVersion != null && version > localVersion!;

  ServerTopicPack({
    required this.id,
    required this.titleVi,
    required this.titleEn,
    required this.category,
    required this.targetAgeMin,
    required this.targetAgeMax,
    required this.targetGender,
    required this.thumbnailUrl,
    required this.descriptionVi,
    required this.descriptionEn,
    required this.version,
    required this.sizeMb,
    required this.itemCount,
    required this.downloadUrl,
    this.isInstalled = false,
    this.localVersion,
  });

  factory ServerTopicPack.fromJson(Map<String, dynamic> json, {Topic? localTopic}) {
    final isInstalled = localTopic != null;
    final localVer = localTopic?.version;

    return ServerTopicPack(
      id: json['id'] as String,
      titleVi: json['title_vi'] as String? ?? '',
      titleEn: json['title_en'] as String? ?? '',
      category: json['category'] as String? ?? 'general',
      targetAgeMin: (json['target_age_min'] as num?)?.toInt() ?? 3,
      targetAgeMax: (json['target_age_max'] as num?)?.toInt() ?? 10,
      targetGender: json['target_gender'] as String? ?? 'all',
      thumbnailUrl: json['thumbnail_url'] as String? ?? '',
      descriptionVi: json['description_vi'] as String? ?? '',
      descriptionEn: json['description_en'] as String? ?? '',
      version: (json['version'] as num?)?.toInt() ?? 1,
      sizeMb: (json['size_mb'] as num?)?.toDouble() ?? 1.5,
      itemCount: (json['item_count'] as num?)?.toInt() ?? 0,
      downloadUrl: json['download_url'] as String? ?? '',
      isInstalled: isInstalled,
      localVersion: localVer,
    );
  }
}

class TopicStoreService extends ChangeNotifier {
  static final TopicStoreService instance = TopicStoreService._init();

  List<ServerTopicPack> _serverPacks = [];
  bool _isLoading = false;
  String? _error;
  final Map<String, double> _downloadingProgress = {};

  TopicStoreService._init();

  List<ServerTopicPack> get serverPacks => _serverPacks;
  bool get isLoading => _isLoading;
  String? get error => _error;
  Map<String, double> get downloadingProgress => _downloadingProgress;

  bool isDownloading(String packId) => _downloadingProgress.containsKey(packId);

  Future<void> fetchPacks() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    final serverUrl = ContentServerConfigService.instance.currentServerUrl;
    try {
      final endpoint = Uri.parse('$serverUrl/api/packs');
      final response = await http.get(endpoint).timeout(const Duration(seconds: 6));

      if (response.statusCode == 200) {
        final data = jsonDecode(utf8.decode(response.bodyBytes));
        final rawList = data['data'] as List? ?? [];

        // Check local database to mark installed topics
        final localTopics = await DatabaseHelper.instance.getTopics();
        final localMap = {for (final t in localTopics) t.id: t};

        _serverPacks = rawList.map((item) {
          final id = item['id'] as String;
          return ServerTopicPack.fromJson(item, localTopic: localMap[id]);
        }).toList();

        _error = null;
      } else {
        _error = 'Lỗi máy chủ: HTTP ${response.statusCode}';
      }
    } catch (e) {
      _error = 'Không thể kết nối đến máy chủ nội dung ($serverUrl). Hãy kiểm tra lại Domain!';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> downloadAndInstallPack(String packId) async {
    final serverUrl = ContentServerConfigService.instance.currentServerUrl;
    _downloadingProgress[packId] = 0.1;
    notifyListeners();

    try {
      final endpoint = Uri.parse('$serverUrl/api/packs/$packId/download');
      _downloadingProgress[packId] = 0.4;
      notifyListeners();

      final response = await http.get(endpoint).timeout(const Duration(seconds: 15));

      if (response.statusCode != 200) {
        _downloadingProgress.remove(packId);
        notifyListeners();
        return false;
      }

      _downloadingProgress[packId] = 0.7;
      notifyListeners();

      final jsonBody = jsonDecode(utf8.decode(response.bodyBytes));
      final bundle = jsonBody['data'] as Map<String, dynamic>;
      final packData = bundle['pack'] as Map<String, dynamic>;
      final itemsData = bundle['items'] as List<dynamic>? ?? [];

      // 1. Create Topic in SQLite with resolved media paths
      final topic = Topic(
        id: packData['id'] as String,
        titleVi: packData['title_vi'] as String,
        titleEn: packData['title_en'] as String,
        category: packData['category'] as String,
        targetAgeMin: (packData['target_age_min'] as num?)?.toInt() ?? 3,
        targetAgeMax: (packData['target_age_max'] as num?)?.toInt() ?? 10,
        targetGender: packData['target_gender'] as String? ?? 'all',
        thumbnailPath: packData['thumbnail_path'] as String?,
        backgroundPath: packData['background_path'] as String?,
        themeColor: packData['theme_color'] as String?,
        isDownloaded: true,
        version: (packData['version'] as num?)?.toInt() ?? 1,
        selectedGames: Topic.fromMap(packData).selectedGames,
      );

      // 2. Parse items with all multi-sensory attributes
      final items = itemsData.map((it) {
        return TopicItem(
          id: it['id'] as String,
          topicId: it['topic_id'] as String,
          nameVi: it['name_vi'] as String,
          nameEn: it['name_en'] as String,
          descriptionVi: it['description_vi'] as String?,
          descriptionEn: it['description_en'] as String?,
          pronounceEnUrl: it['pronounce_en_url'] as String?,
          pronounceViUrl: it['pronounce_vi_url'] as String?,
          imagesJson: it['images_json'] as String? ?? '[]',
          youtubeVideoId: it['youtube_video_id'] as String?,
          mapCoordinatesJson: it['map_coordinates_json'] as String?,
          phonicsEn: it['phonics_en'] as String?,
          realImageUrl: it['real_image_url'] as String?,
          sfxSound: it['sfx_sound'] as String?,
          funFactVi: it['fun_fact_vi'] as String?,
          funFactEn: it['fun_fact_en'] as String?,
          promptQuestionVi: it['prompt_question_vi'] as String?,
          promptQuestionEn: it['prompt_question_en'] as String?,
          actionHintVi: it['action_hint_vi'] as String?,
        );
      }).toList();

      // 3. Atomically replace topic and clean old items to avoid ghost entries
      await DatabaseHelper.instance.replaceTopicWithItems(topic, items);

      _downloadingProgress[packId] = 1.0;
      notifyListeners();

      // Refresh list status
      await fetchPacks();
      return true;
    } catch (e) {
      debugPrint('Error downloading pack: $e');
      return false;
    } finally {
      _downloadingProgress.remove(packId);
      notifyListeners();
    }
  }

  Future<void> deletePack(String packId) async {
    await DatabaseHelper.instance.deleteTopicAndItems(packId);
    await fetchPacks();
  }
}
