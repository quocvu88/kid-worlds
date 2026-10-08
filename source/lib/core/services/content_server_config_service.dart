import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ServerInfo {
  final bool isOnline;
  final String serverName;
  final String version;
  final int totalPacks;
  final String? errorMessage;
  final int latencyMs;

  ServerInfo({
    required this.isOnline,
    this.serverName = '',
    this.version = '',
    this.totalPacks = 0,
    this.errorMessage,
    this.latencyMs = 0,
  });
}

class ContentServerConfigService extends ChangeNotifier {
  static final ContentServerConfigService instance = ContentServerConfigService._init();

  static const String _keyServerUrl = 'content_server_base_url';
  static const String defaultEmulatorUrl = 'http://10.0.2.2:8000';
  static const String defaultLocalIpUrl = 'http://192.168.2.6:8000';

  String _currentServerUrl = defaultEmulatorUrl;
  ServerInfo? _lastServerInfo;
  bool _isChecking = false;

  late final Future<void> _ready;

  ContentServerConfigService._init() {
    _ready = _loadFromPrefs();
  }

  /// Hoàn tất khi URL máy chủ đã được đọc từ bộ nhớ. Luôn `await` trước khi gọi mạng
  /// để tránh dùng nhầm URL mặc định ở lần gọi đầu tiên.
  Future<void> get ready => _ready;

  String get currentServerUrl => _currentServerUrl;
  ServerInfo? get lastServerInfo => _lastServerInfo;
  bool get isChecking => _isChecking;

  Future<void> _loadFromPrefs() async {
    String? saved;
    try {
      final prefs = await SharedPreferences.getInstance();
      saved = prefs.getString(_keyServerUrl);
    } catch (e) {
      debugPrint('ContentServerConfigService load error: $e');
    }
    if (saved != null && saved.trim().isNotEmpty) {
      _currentServerUrl = _sanitizeUrl(saved);
    } else {
      _currentServerUrl = defaultEmulatorUrl;
    }
    notifyListeners();
  }

  String _sanitizeUrl(String url) {
    String trimmed = url.trim();
    if (!trimmed.startsWith('http://') && !trimmed.startsWith('https://')) {
      trimmed = 'http://$trimmed';
    }
    // Remove trailing slash
    if (trimmed.endsWith('/')) {
      trimmed = trimmed.substring(0, trimmed.length - 1);
    }
    return trimmed;
  }

  Future<void> setServerUrl(String url) async {
    await _ready;
    final clean = _sanitizeUrl(url);
    _currentServerUrl = clean;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_keyServerUrl, clean);
    notifyListeners();
  }

  Future<ServerInfo> testConnection([String? customUrl]) async {
    final targetUrl = _sanitizeUrl(customUrl ?? _currentServerUrl);
    _isChecking = true;
    notifyListeners();

    final stopwatch = Stopwatch()..start();
    try {
      final endpoint = Uri.parse('$targetUrl/api/info');
      final response = await http.get(endpoint).timeout(const Duration(seconds: 4));
      stopwatch.stop();

      if (response.statusCode == 200) {
        final data = jsonDecode(utf8.decode(response.bodyBytes));
        final info = ServerInfo(
          isOnline: true,
          serverName: data['server_name'] as String? ?? 'Kids World Server',
          version: data['version'] as String? ?? '1.0',
          totalPacks: (data['stats']?['total_packs'] as num?)?.toInt() ?? 0,
          latencyMs: stopwatch.elapsedMilliseconds,
        );
        _lastServerInfo = info;
        _isChecking = false;
        notifyListeners();
        return info;
      } else {
        final info = ServerInfo(
          isOnline: false,
          errorMessage: 'Server trả về lỗi HTTP ${response.statusCode}',
          latencyMs: stopwatch.elapsedMilliseconds,
        );
        _lastServerInfo = info;
        _isChecking = false;
        notifyListeners();
        return info;
      }
    } catch (e) {
      stopwatch.stop();
      final info = ServerInfo(
        isOnline: false,
        errorMessage: 'Không thể kết nối tới máy chủ ($e)',
        latencyMs: stopwatch.elapsedMilliseconds,
      );
      _lastServerInfo = info;
      _isChecking = false;
      notifyListeners();
      return info;
    }
  }
}
