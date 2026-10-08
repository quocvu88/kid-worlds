import 'package:audioplayers/audioplayers.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_tts/flutter_tts.dart';

import '../utils/image_helper.dart';

class AudioService {
  static final AudioService instance = AudioService._init();
  final AudioPlayer _player = AudioPlayer();
  final FlutterTts _tts = FlutterTts();
  bool _ttsInitialized = false;

  // Giọng mặc định. Giữ pitch = 1.0: đổi cao độ khiến engine TTS phải xử lý lại âm thanh,
  // trên nhiều máy (đặc biệt giọng tiếng Việt) nghe bị rè/méo. Chỉ thay đổi tốc độ đọc.
  static const double _defaultRate = 0.45; // Android: 0.5 = tốc độ bình thường
  static const double _defaultPitch = 1.0;

  /// Giọng tốt nhất đã chọn cho từng ngôn ngữ (cache sau lần tìm đầu tiên).
  final Map<String, Map<String, String>?> _bestVoices = {};

  AudioService._init() {
    _player.setReleaseMode(ReleaseMode.stop);
    _initTts();
  }

  Future<void> _initTts() async {
    try {
      await _tts.setVolume(1.0);
      await _tts.awaitSpeakCompletion(false);
      _ttsInitialized = true;
    } catch (e) {
      debugPrint('TTS init error: $e');
    }
  }

  /// Chọn giọng offline có chất lượng cao nhất cho ngôn ngữ (vd 'vi-VN').
  /// Giọng mặc định đôi khi là giọng "network" chất lượng thấp → bị rè, giật.
  Future<Map<String, String>?> _findBestVoice(String language) async {
    if (_bestVoices.containsKey(language)) return _bestVoices[language];
    Map<String, String>? best;
    try {
      final voices = await _tts.getVoices;
      if (voices is List) {
        final wanted = language.toLowerCase().replaceAll('_', '-');
        int bestScore = -1;
        for (final v in voices) {
          if (v is! Map) continue;
          final name = v['name']?.toString() ?? '';
          final locale = (v['locale']?.toString() ?? '').toLowerCase().replaceAll('_', '-');
          if (name.isEmpty || locale != wanted) continue;

          final quality = (v['quality']?.toString() ?? '').toLowerCase();
          final network = v['network_required']?.toString() == '1' ||
              name.toLowerCase().contains('network');
          int score = 0;
          if (quality.contains('very high') || quality == '500') {
            score += 40;
          } else if (quality.contains('high') || quality == '400') {
            score += 30;
          } else if (quality.contains('normal') || quality == '300') {
            score += 20;
          }
          if (!network) score += 50; // ưu tiên giọng offline: không giật khi mạng chậm
          if (score > bestScore) {
            bestScore = score;
            best = {'name': name, 'locale': v['locale'].toString()};
          }
        }
      }
    } catch (e) {
      debugPrint('TTS getVoices error: $e');
    }
    _bestVoices[language] = best;
    return best;
  }

  /// Mỗi yêu cầu phát mới (hoặc stop) tăng số này. Yêu cầu cũ còn đang chờ (await) sẽ tự huỷ
  /// thay vì chen vào giữa câu mới → tránh tình trạng 2 câu nói chồng/cắt nhau liên tục gây rè.
  int _requestId = 0;

  bool _isStale(int id) => id != _requestId;

  void _log(String msg) {
    if (kDebugMode) debugPrint('[Audio ${DateTime.now().toIso8601String().substring(11, 23)}] $msg');
  }

  Future<void> _stopPlayers() async {
    try {
      await _player.stop();
      await _tts.stop();
    } catch (e) {
      debugPrint('AudioService error stopping: $e');
    }
  }

  /// Dừng âm thanh hiện tại và trả về mã của yêu cầu mới.
  Future<int> _beginRequest() async {
    final id = ++_requestId;
    await _stopPlayers();
    return id;
  }

  Future<void> _speak(int id, String text, {required String language, double rate = _defaultRate, double pitch = _defaultPitch}) async {
    if (text.trim().isEmpty || _isStale(id)) return;
    if (!_ttsInitialized) await _initTts();
    await _tts.setLanguage(language);
    final voice = await _findBestVoice(language);
    if (_isStale(id)) return;
    if (voice != null) {
      await _tts.setVoice(voice);
    }
    await _tts.setSpeechRate(rate);
    await _tts.setPitch(pitch);
    if (_isStale(id)) return;
    _log('TTS #$id $language voice=${voice?['name'] ?? 'default'} rate=$rate: "$text"');
    await _tts.speak(text);
  }

  Future<void> speakEnglish(String text) async {
    try {
      final id = await _beginRequest();
      await _speak(id, text, language: 'en-US');
    } catch (e) {
      debugPrint('TTS speakEnglish error: $e');
    }
  }

  Future<void> speakVietnamese(String text) async {
    try {
      final id = await _beginRequest();
      await _speak(id, text, language: 'vi-VN');
    } catch (e) {
      debugPrint('TTS speakVietnamese error: $e');
    }
  }

  /// Phát file phát âm (asset hoặc URL từ CMS); nếu không có/không phát được thì đọc bằng TTS.
  Future<void> playPronunciation(String? audioUrl, {required String fallbackText, required String language}) async {
    final id = await _beginRequest();
    final raw = audioUrl?.trim() ?? '';
    if (raw.isNotEmpty) {
      final resolved = ImageHelper.resolveUrl(raw);
      try {
        if (resolved.startsWith('assets/')) {
          if (_isStale(id)) return;
          _log('FILE #$id $resolved');
          await _player.play(AssetSource(resolved.substring('assets/'.length)));
          return;
        } else if (resolved.startsWith('http://') || resolved.startsWith('https://')) {
          if (_isStale(id)) return;
          _log('URL #$id $resolved');
          await _player.play(UrlSource(resolved));
          return;
        }
      } catch (e) {
        debugPrint('AudioService pronunciation error ($resolved), fallback TTS: $e');
      }
    }
    try {
      await _speak(id, fallbackText, language: language);
    } catch (e) {
      debugPrint('TTS fallback error: $e');
    }
  }

  Future<void> _playAssetFor(int id, String assetPath) async {
    if (_isStale(id)) return;
    final cleanPath = assetPath.startsWith('assets/') ? assetPath.substring('assets/'.length) : assetPath;
    _log('FILE #$id $cleanPath');
    await _player.play(AssetSource(cleanPath));
  }

  Future<void> _playUrlFor(int id, String url) async {
    if (_isStale(id)) return;
    final resolved = ImageHelper.resolveUrl(url);
    _log('URL #$id $resolved');
    await _player.play(UrlSource(resolved));
  }

  Future<void> playAsset(String assetPath) async {
    try {
      final id = await _beginRequest();
      await _playAssetFor(id, assetPath);
    } catch (e) {
      debugPrint('AudioService error playing asset $assetPath: $e');
    }
  }

  Future<void> playUrl(String url) async {
    try {
      final id = await _beginRequest();
      await _playUrlFor(id, url);
    } catch (e) {
      debugPrint('AudioService error playing url $url: $e');
    }
  }

  Future<void> playSfx(String sfx) async {
    try {
      final id = await _beginRequest();
      final clean = sfx.trim();
      if (clean.startsWith('http://') || clean.startsWith('https://') || clean.startsWith('/uploads/')) {
        await _playUrlFor(id, clean);
      } else if (clean.startsWith('assets/')) {
        await _playAssetFor(id, clean);
      } else {
        // Expressive playful onomatopoeia vocalization
        await _speak(id, clean, language: 'vi-VN', rate: 0.5);
      }
    } catch (e) {
      debugPrint('AudioService playSfx error: $e');
    }
  }

  Future<void> speakPhonics(String phonics) async {
    try {
      final id = await _beginRequest();
      await _speak(id, phonics, language: 'en-US', rate: 0.4);
    } catch (e) {
      debugPrint('AudioService speakPhonics error: $e');
    }
  }

  /// Dừng mọi âm thanh và huỷ các yêu cầu phát đang chờ.
  Future<void> stop() async {
    _requestId++;
    await _stopPlayers();
  }

  /// Nói sau khi hiệu ứng chuyển màn hình kết thúc. Lúc mở màn hình mới máy đang dựng giao diện
  /// rất nặng (ảnh, animation, confetti); phát tiếng ngay lúc đó dễ bị giật/rè, nhất là trên emulator.
  Future<void> speakVietnameseAfterTransition(
    String text, {
    bool Function()? isActive,
    Duration delay = const Duration(milliseconds: 600),
  }) async {
    await Future.delayed(delay);
    // Màn hình đã đóng trong lúc chờ thì thôi không nói
    if (isActive != null && !isActive()) return;
    await speakVietnamese(text);
  }

  void dispose() {
    _player.dispose();
    _tts.stop();
  }
}
