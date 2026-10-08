import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../services/content_server_config_service.dart';

/// Danh sách asset thực sự được đóng gói trong app (đọc từ AssetManifest lúc khởi động).
/// Dùng để bỏ qua các đường dẫn `assets/...` trong dữ liệu mẫu nhưng chưa có file thật.
class BundledAssets {
  static Set<String>? _assets;

  static Future<void> init() async {
    if (_assets != null) return;
    try {
      final manifest = await AssetManifest.loadFromAssetBundle(rootBundle);
      _assets = manifest.listAssets().toSet();
    } catch (e) {
      debugPrint('BundledAssets init error: $e');
    }
  }

  /// true nếu không phải asset, hoặc asset có trong gói. Nếu chưa init thì coi như có.
  static bool exists(String path) {
    final p = path.trim();
    if (!p.startsWith('assets/')) return true;
    return _assets == null || _assets!.contains(p);
  }
}

class ImageHelper {
  /// Resolve any image path or URL into a reachable, valid URL or asset path
  static String resolveUrl(String? rawUrl) {
    if (rawUrl == null || rawUrl.trim().isEmpty) return '';
    String url = rawUrl.trim();

    // 1. Local assets (bỏ qua asset không có trong gói để hiện ảnh dự phòng)
    if (url.startsWith('assets/')) {
      return BundledAssets.exists(url) ? url : '';
    }

    // 2. Fix legacy or deprecated Wikimedia thumb domain
    if (url.contains('thumb.wikimedia.org')) {
      url = url.replaceAll('thumb.wikimedia.org', 'upload.wikimedia.org');
    }

    // Strip problematic query params on Wikimedia that cause 400 Bad Request
    if (url.contains('wikimedia.org') && url.contains('?')) {
      url = url.split('?').first;
    }

    // 3. Resolve localhost / 127.0.0.1 to current server URL (for Android emulator or LAN device)
    final serverBase = ContentServerConfigService.instance.currentServerUrl;
    final localhostPattern = RegExp(r'^https?://(?:localhost|127\.0\.0\.1)(?::\d+)?(/.*)?$');
    final match = localhostPattern.firstMatch(url);
    if (match != null) {
      final path = match.group(1) ?? '';
      return '${serverBase.replaceAll(RegExp(r'/+$'), '')}$path';
    }

    // 4. Resolve relative paths like /uploads/... or uploads/...
    if (url.startsWith('/')) {
      return '${serverBase.replaceAll(RegExp(r'/+$'), '')}$url';
    } else if (url.startsWith('uploads/')) {
      return '${serverBase.replaceAll(RegExp(r'/+$'), '')}/$url';
    }

    return url;
  }

  /// Build a safe image widget that handles assets, remote URLs, headers, and fallbacks
  static Widget buildSafeImage(
    String? rawUrl, {
    double? width,
    double? height,
    BoxFit fit = BoxFit.cover,
    Widget? placeholder,
    Widget? fallback,
    BorderRadius? borderRadius,
  }) {
    final cleanUrl = resolveUrl(rawUrl);

    Widget imageWidget;
    if (cleanUrl.isEmpty) {
      imageWidget = fallback ??
          Container(
            width: width,
            height: height,
            color: const Color(0xFFF1F5F9),
            child: const Icon(Icons.image_not_supported_rounded, color: Color(0xFF94A3B8), size: 28),
          );
    } else if (cleanUrl.startsWith('assets/')) {
      imageWidget = Image.asset(
        cleanUrl,
        width: width,
        height: height,
        fit: fit,
        errorBuilder: (context, error, stackTrace) =>
            fallback ??
            Container(
              width: width,
              height: height,
              color: const Color(0xFFF1F5F9),
              child: const Icon(Icons.broken_image_rounded, color: Color(0xFF94A3B8), size: 28),
            ),
      );
    } else {
      imageWidget = Image.network(
        cleanUrl,
        width: width,
        height: height,
        fit: fit,
        headers: const {
          'User-Agent': 'KidsWorldApp/1.0 (Mobile Educational App; https://kidsworld.app)',
          'Accept': 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
        },
        loadingBuilder: (context, child, progress) {
          if (progress == null) return child;
          return placeholder ??
              Container(
                width: width,
                height: height,
                color: const Color(0xFFF8FAFC),
                child: const Center(
                  child: SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  ),
                ),
              );
        },
        errorBuilder: (context, error, stackTrace) =>
            fallback ??
            Container(
              width: width,
              height: height,
              color: const Color(0xFFF1F5F9),
              child: const Center(
                child: Icon(Icons.broken_image_outlined, color: Color(0xFF94A3B8), size: 28),
              ),
            ),
      );
    }

    if (borderRadius != null) {
      return ClipRRect(borderRadius: borderRadius, child: imageWidget);
    }
    return imageWidget;
  }
}
