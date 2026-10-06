import 'dart:convert';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:kids_world/core/utils/image_helper.dart';
import 'package:kids_world/models/topic_model.dart';
import 'package:kids_world/models/topic_item_model.dart';

import 'dart:io';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  HttpOverrides.global = null;
  SharedPreferences.setMockInitialValues({});
  test('Test dinosaur pack download and parsing', () async {
    final res = await http.get(Uri.parse('http://localhost:8000/api/packs/topic_dinosaurs/download'));
    expect(res.statusCode, 200);

    final jsonBody = jsonDecode(utf8.decode(res.bodyBytes));
    final bundle = jsonBody['data'] as Map<String, dynamic>;
    final packData = bundle['pack'] as Map<String, dynamic>;
    final itemsData = bundle['items'] as List<dynamic>;

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
    );

    // ignore: avoid_print
    print('=== TOPIC MODEL ===');
    // ignore: avoid_print
    print('ID: ${topic.id}');
    // ignore: avoid_print
    print('Title: ${topic.titleVi}');
    // ignore: avoid_print
    print('thumbnailPath: ${topic.thumbnailPath}');
    // ignore: avoid_print
    print('Resolved Thumbnail: ${ImageHelper.resolveUrl(topic.thumbnailPath)}');
    // ignore: avoid_print
    print('backgroundPath: ${topic.backgroundPath}');
    // ignore: avoid_print
    print('effectiveBackground: ${topic.effectiveBackground}');

    for (var it in itemsData) {
      final item = TopicItem(
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

      // ignore: avoid_print
      print('Item: ${item.nameVi} (${item.nameEn})');
      // ignore: avoid_print
      print('  primaryImage: ${item.primaryImage}');
      // ignore: avoid_print
      print('  Resolved Primary: ${ImageHelper.resolveUrl(item.primaryImage)}');
      // ignore: avoid_print
      print('  hasRealImage: ${item.hasRealImage}');
      // ignore: avoid_print
      print('  effectiveRealImage: ${item.effectiveRealImage}');
    }
  });
}
