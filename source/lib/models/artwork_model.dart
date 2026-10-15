/// Một bức tranh bé đã tô và lưu lại (Góc Sáng Tạo).
///
/// Ảnh PNG nằm trong bộ nhớ riêng của app (`imagePath`); các nét vẽ được lưu
/// dạng JSON (`strokesJson`, toạ độ chuẩn hoá 0..1) để mở lại và tô tiếp.
class Artwork {
  final String id;
  final String childId;
  final String? topicId;
  final String? itemId;
  final String title;
  final String imagePath;
  final String strokesJson;
  final String createdAt;
  final String updatedAt;

  Artwork({
    required this.id,
    required this.childId,
    this.topicId,
    this.itemId,
    required this.title,
    required this.imagePath,
    required this.strokesJson,
    String? createdAt,
    String? updatedAt,
  })  : createdAt = createdAt ?? DateTime.now().toIso8601String(),
        updatedAt = updatedAt ?? createdAt ?? DateTime.now().toIso8601String();

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'child_id': childId,
      'topic_id': topicId,
      'item_id': itemId,
      'title': title,
      'image_path': imagePath,
      'strokes_json': strokesJson,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }

  factory Artwork.fromMap(Map<String, dynamic> map) {
    return Artwork(
      id: map['id'] as String,
      childId: map['child_id'] as String,
      topicId: map['topic_id'] as String?,
      itemId: map['item_id'] as String?,
      title: map['title'] as String? ?? '',
      imagePath: map['image_path'] as String? ?? '',
      strokesJson: map['strokes_json'] as String? ?? '[]',
      createdAt: map['created_at'] as String?,
      updatedAt: map['updated_at'] as String?,
    );
  }
}
