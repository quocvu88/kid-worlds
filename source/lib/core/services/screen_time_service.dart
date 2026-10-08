import 'dart:async';

import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../models/child_model.dart';
import '../../models/screen_time_model.dart';
import '../database/database_helper.dart';

/// Quản lý thời gian sử dụng theo ngày cho từng bé.
///
/// Lưu trữ (SharedPreferences, theo ngày địa phương):
///  - `screen_time_<childId>_<yyyy-MM-dd>`       : số giây ĐÃ DÙNG trong ngày (đếm lên)
///  - `screen_time_bonus_<childId>_<yyyy-MM-dd>` : số giây phụ huynh GIA HẠN thêm trong ngày
///
/// Thời gian còn lại = giới hạn hôm nay + gia hạn - đã dùng.
class ScreenTimeService extends ChangeNotifier with WidgetsBindingObserver {
  static final ScreenTimeService instance = ScreenTimeService._init();

  Timer? _timer;
  Child? _currentChild;
  ScreenTimeSettings? _settings;

  String _currentDay = _todayKey();
  int _usedSeconds = 0;
  int _bonusSeconds = 0;
  int _unsavedTicks = 0;

  bool _isLockedOut = false;
  bool _isPaused = false;

  ScreenTimeService._init() {
    WidgetsBinding.instance.addObserver(this);
  }

  static String _todayKey() {
    final now = DateTime.now();
    final m = now.month.toString().padLeft(2, '0');
    final d = now.day.toString().padLeft(2, '0');
    return '${now.year}-$m-$d';
  }

  static String usedKey(String childId, String day) => 'screen_time_${childId}_$day';
  static String bonusKey(String childId, String day) => 'screen_time_bonus_${childId}_$day';

  Child? get currentChild => _currentChild;
  ScreenTimeSettings? get settings => _settings;
  bool get isLockedOut => _isLockedOut;
  bool get isPaused => _isPaused;

  /// Số giây đã dùng hôm nay của bé đang hoạt động.
  int get usedSecondsToday => _usedSeconds;

  int get _limitSeconds => (_settings?.currentLimitMinutes ?? 30) * 60 + _bonusSeconds;

  int get remainingSeconds {
    final remaining = _limitSeconds - _usedSeconds;
    return remaining > 0 ? remaining : 0;
  }

  String get remainingFormatted {
    final minutes = remainingSeconds ~/ 60;
    final seconds = remainingSeconds % 60;
    return '${minutes.toString().padLeft(2, '0')}:${seconds.toString().padLeft(2, '0')}';
  }

  double get progress {
    if (_settings == null) return 1.0;
    final total = _limitSeconds;
    if (total <= 0) return 0.0;
    return (remainingSeconds / total).clamp(0.0, 1.0);
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _isPaused = false;
      _rolloverIfNewDay();
      _startTimer();
    } else if (state == AppLifecycleState.paused ||
        state == AppLifecycleState.inactive ||
        state == AppLifecycleState.hidden ||
        state == AppLifecycleState.detached) {
      _isPaused = true;
      _timer?.cancel();
      _saveToPrefs();
    }
  }

  Future<void> setActiveChild(Child child) async {
    // Lưu phần thời gian chưa ghi của bé trước đó
    await _saveToPrefs();

    _currentChild = child;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('active_child_id', child.id);

    _settings = await DatabaseHelper.instance.getScreenTimeSettings(child.id);
    await _loadDay(prefs, _todayKey());

    notifyListeners();
    _startTimer();
  }

  /// Bỏ chọn bé hiện tại (ví dụ phụ huynh muốn chọn hồ sơ khác từ màn khoá).
  Future<void> clearActiveChild() async {
    await _saveToPrefs();
    _timer?.cancel();
    _currentChild = null;
    _settings = null;
    _usedSeconds = 0;
    _bonusSeconds = 0;
    _isLockedOut = false;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('active_child_id');
    notifyListeners();
  }

  Future<void> _loadDay(SharedPreferences prefs, String day) async {
    _currentDay = day;
    final childId = _currentChild?.id;
    if (childId == null) return;
    _usedSeconds = prefs.getInt(usedKey(childId, day)) ?? 0;
    _bonusSeconds = prefs.getInt(bonusKey(childId, day)) ?? 0;
    _unsavedTicks = 0;
    _isLockedOut = remainingSeconds <= 0;
  }

  /// Khi qua nửa đêm: lưu ngày cũ và bắt đầu ngày mới từ 0.
  void _rolloverIfNewDay() {
    final today = _todayKey();
    if (today == _currentDay || _currentChild == null) return;
    _saveToPrefs(day: _currentDay);
    _currentDay = today;
    _usedSeconds = 0;
    _bonusSeconds = 0;
    _unsavedTicks = 0;
    _isLockedOut = remainingSeconds <= 0;
    notifyListeners();
  }

  void _startTimer() {
    _timer?.cancel();
    if (_isLockedOut || _currentChild == null || _isPaused) return;

    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      _rolloverIfNewDay();
      if (_isLockedOut) {
        timer.cancel();
        return;
      }

      _usedSeconds++;
      _unsavedTicks++;

      if (remainingSeconds <= 0) {
        _isLockedOut = true;
        timer.cancel();
        _saveToPrefs();
      } else if (_unsavedTicks >= 5) {
        _saveToPrefs();
      }
      notifyListeners();
    });
  }

  Future<void> _saveToPrefs({String? day}) async {
    final childId = _currentChild?.id;
    if (childId == null) return;
    final targetDay = day ?? _currentDay;
    final used = _usedSeconds;
    final bonus = _bonusSeconds;
    _unsavedTicks = 0;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt(usedKey(childId, targetDay), used);
    await prefs.setInt(bonusKey(childId, targetDay), bonus);
  }

  /// Phụ huynh gia hạn thêm phút cho hôm nay (được lưu lại, không mất khi mở lại app).
  void addExtensionMinutes(int minutes) {
    if (_currentChild == null) return;
    // Nếu đã dùng quá giới hạn (ví dụ do đổi cài đặt), tính phần gia hạn từ thời điểm hiện tại
    final overflow = _usedSeconds - _limitSeconds;
    if (overflow > 0) _bonusSeconds += overflow;
    _bonusSeconds += minutes * 60;
    _isLockedOut = remainingSeconds <= 0;
    _saveToPrefs();
    notifyListeners();
    _startTimer();
  }

  /// Phụ huynh đặt lại thời gian hôm nay về đủ giới hạn (được lưu lại).
  void resetDailyTime() {
    if (_currentChild == null) return;
    _usedSeconds = 0;
    _bonusSeconds = 0;
    _isLockedOut = remainingSeconds <= 0;
    _saveToPrefs();
    notifyListeners();
    _startTimer();
  }

  Future<void> refreshSettings() async {
    if (_currentChild != null) {
      await setActiveChild(_currentChild!);
    }
  }

  void stopTimer() {
    _timer?.cancel();
    _saveToPrefs();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _timer?.cancel();
    super.dispose();
  }
}
