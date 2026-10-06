import 'package:audioplayers/audioplayers.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_tts/flutter_tts.dart';

class AudioService {
  static final AudioService instance = AudioService._init();
  final AudioPlayer _player = AudioPlayer();
  final FlutterTts _tts = FlutterTts();
  bool _ttsInitialized = false;

  AudioService._init() {
    _player.setReleaseMode(ReleaseMode.stop);
    _initTts();
  }

  Future<void> _initTts() async {
    try {
      await _tts.setSpeechRate(0.42);
      await _tts.setPitch(1.08);
      await _tts.setVolume(1.0);
      _ttsInitialized = true;
    } catch (e) {
      debugPrint('TTS init error: $e');
    }
  }

  Future<void> speakEnglish(String text) async {
    try {
      await stop();
      if (!_ttsInitialized) await _initTts();
      await _tts.setLanguage('en-US');
      await _tts.speak(text);
    } catch (e) {
      debugPrint('TTS speakEnglish error: $e');
    }
  }

  Future<void> speakVietnamese(String text) async {
    try {
      await stop();
      if (!_ttsInitialized) await _initTts();
      await _tts.setLanguage('vi-VN');
      await _tts.speak(text);
    } catch (e) {
      debugPrint('TTS speakVietnamese error: $e');
    }
  }

  Future<void> playAsset(String assetPath) async {
    try {
      await stop();
      final cleanPath = assetPath.startsWith('assets/') ? assetPath.substring('assets/'.length) : assetPath;
      await _player.play(AssetSource(cleanPath));
    } catch (e) {
      debugPrint('AudioService error playing asset $assetPath: $e');
    }
  }

  Future<void> playUrl(String url) async {
    try {
      await stop();
      await _player.play(UrlSource(url));
    } catch (e) {
      debugPrint('AudioService error playing url $url: $e');
    }
  }

  Future<void> playSfx(String sfx) async {
    try {
      await stop();
      final clean = sfx.trim();
      if (clean.startsWith('http://') || clean.startsWith('https://')) {
        await playUrl(clean);
      } else if (clean.startsWith('assets/')) {
        await playAsset(clean);
      } else {
        // Expressive playful onomatopoeia vocalization
        if (!_ttsInitialized) await _initTts();
        await _tts.setPitch(1.22);
        await _tts.setSpeechRate(0.46);
        await _tts.setLanguage('vi-VN');
        await _tts.speak(clean);
      }
    } catch (e) {
      debugPrint('AudioService playSfx error: $e');
    }
  }

  Future<void> speakPhonics(String phonics) async {
    try {
      await stop();
      if (!_ttsInitialized) await _initTts();
      await _tts.setPitch(1.1);
      await _tts.setSpeechRate(0.38);
      await _tts.setLanguage('en-US');
      await _tts.speak(phonics);
    } catch (e) {
      debugPrint('AudioService speakPhonics error: $e');
    }
  }

  Future<void> stop() async {
    try {
      await _player.stop();
      await _tts.stop();
    } catch (e) {
      debugPrint('AudioService error stopping: $e');
    }
  }

  void dispose() {
    _player.dispose();
    _tts.stop();
  }
}
