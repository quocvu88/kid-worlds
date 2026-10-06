import 'dart:convert';
import 'package:flutter_test/flutter_test.dart';
import 'package:kids_world/models/topic_model.dart';
import 'package:kids_world/models/topic_item_model.dart';

void main() {
  group('Topic & TopicItem Multi-sensory Standards', () {
    test('Topic defaults and effectiveBackground fallback', () {
      final topic = Topic(
        id: 'topic_animals',
        titleVi: 'Động vật',
        titleEn: 'Animals',
        category: 'animals',
      );

      expect(topic.effectiveBackground, 'assets/backgrounds/bg_green_meadow.jpg');

      final customTopic = topic.copyWith(backgroundPath: 'assets/backgrounds/custom_bg.jpg');
      expect(customTopic.effectiveBackground, 'assets/backgrounds/custom_bg.jpg');
    });

    test('TopicItem multi-sensory and parent guide properties', () {
      final item = TopicItem(
        id: 'item_lion',
        topicId: 'topic_animals',
        nameVi: 'Sư Tử',
        nameEn: 'Lion',
        imagesJson: jsonEncode(['assets/images/lion.png']),
        phonicsEn: 'L - /l/ - Lion',
        realImageUrl: 'https://images.unsplash.com/lion.jpg',
        sfxSound: 'Gừừừ... Roaaar!',
        funFactVi: 'Sư tử có bờm oai phong!',
        promptQuestionVi: 'Đố con biết chú sư tử ngủ mấy tiếng?',
        actionHintVi: 'Cùng bắt chước tiếng sư tử gầm nào!',
      );

      expect(item.hasPhonics, isTrue);
      expect(item.hasRealImage, isTrue);
      expect(item.effectiveRealImage, 'https://images.unsplash.com/lion.jpg');
      expect(item.hasSfx, isTrue);
      expect(item.hasParentGuide, isTrue);

      final map = item.toMap();
      final revived = TopicItem.fromMap(map);
      expect(revived.nameVi, 'Sư Tử');
      expect(revived.phonicsEn, 'L - /l/ - Lion');
      expect(revived.sfxSound, 'Gừừừ... Roaaar!');
      expect(revived.funFactVi, 'Sư tử có bờm oai phong!');
    });
  });
}
