import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:flutter/painting.dart';
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';
import 'package:uuid/uuid.dart';

import '../../models/activity_log_model.dart';
import '../../models/artwork_model.dart';
import '../database/database_helper.dart';

/// Lưu / xoá tranh tô màu của bé (Góc Sáng Tạo).
///
/// Ảnh PNG nằm ở `<thư mục tài liệu app>/artworks/<childId>/<artworkId>.png`,
/// thông tin + nét vẽ nằm trong bảng `artworks`.
class ArtworkService {
  ArtworkService._();
  static final ArtworkService instance = ArtworkService._();

  /// Web không có hệ thống file → không hỗ trợ lưu tranh.
  bool get isSupported => !kIsWeb;

  Future<Directory> _childDir(String childId) async {
    final docs = await getApplicationDocumentsDirectory();
    final dir = Directory(p.join(docs.path, 'artworks', childId));
    if (!await dir.exists()) await dir.create(recursive: true);
    return dir;
  }

  /// Lưu mới (khi [existingId] null) hoặc ghi đè tranh đang tô tiếp.
  Future<Artwork> save({
    String? existingId,
    required String childId,
    String? topicId,
    String? itemId,
    required String title,
    required Uint8List pngBytes,
    required String strokesJson,
  }) async {
    final existing = existingId == null ? null : await DatabaseHelper.instance.getArtwork(existingId);
    final id = existing?.id ?? const Uuid().v4();
    final dir = await _childDir(childId);
    final file = File(p.join(dir.path, '$id.png'));
    await file.writeAsBytes(pngBytes, flush: true);
    // Ảnh ghi đè cùng đường dẫn → xoá cache để Image.file hiện bản mới
    await FileImage(file).evict();

    final artwork = Artwork(
      id: id,
      childId: childId,
      topicId: topicId,
      itemId: itemId,
      title: title,
      imagePath: file.path,
      strokesJson: strokesJson,
      createdAt: existing?.createdAt,
      updatedAt: DateTime.now().toIso8601String(),
    );
    await DatabaseHelper.instance.upsertArtwork(artwork);
    await DatabaseHelper.instance.insertActivityLog(ActivityLog(
      id: const Uuid().v4(),
      childId: childId,
      itemId: itemId,
      topicId: topicId,
      actionType: 'save_artwork',
    ));
    return artwork;
  }

  Future<void> delete(Artwork artwork) async {
    await DatabaseHelper.instance.deleteArtwork(artwork.id);
    try {
      final file = File(artwork.imagePath);
      if (await file.exists()) await file.delete();
    } catch (e) {
      debugPrint('Không xoá được file tranh ${artwork.imagePath}: $e');
    }
  }

  /// Gọi khi xoá hồ sơ bé: dọn cả thư mục ảnh.
  Future<void> deleteAllForChild(String childId) async {
    if (!isSupported) return;
    try {
      final docs = await getApplicationDocumentsDirectory();
      final dir = Directory(p.join(docs.path, 'artworks', childId));
      if (await dir.exists()) await dir.delete(recursive: true);
    } catch (e) {
      debugPrint('Không xoá được thư mục tranh của bé $childId: $e');
    }
  }
}
