import 'dart:async';

import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../models/child_model.dart';
import '../../models/screen_time_model.dart';
import '../database/database_helper.dart';

class ScreenTimeService extends ChangeNotifier with WidgetsBindingObserver {
  static final ScreenTimeService instance = ScreenTimeService._init();

  Timer? _timer;
  Child? _currentChild;
  ScreenTimeSettings? _settings;

  int _remainingSeconds = 30 * 60;
  bool _isLockedOut = false;
  bool _isPaused = false;

  ScreenTimeService._init() {
    WidgetsBinding.instance.addObserver(this);
  }

  Child? get currentChild => _currentChild;
  ScreenTimeSettings? get settings => _settings;
  int get remainingSeconds => _remainingSeconds;
  bool get isLockedOut => _isLockedOut;
  bool get isPaused => _isPaused;

  String get remainingFormatted {
    final minutes = _remainingSeconds ~/ 60;
    final seconds = _remainingSeconds % 60;
    return '${minutes.toString().padLeft(2, '0')}:${seconds.toString().padLeft(2, '0')}';
  }

  double get progress {
    if (_settings == null) return 1.0;
    final totalSeconds = _settings!.currentLimitMinutes * 60;
    if (totalSeconds == 0) return 0.0;
    return (_remainingSeconds / totalSeconds).clamp(0.0, 1.0);
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _isPaused = false;
      _startTimer();
    } else if (state == AppLifecycleState.paused || state == AppLifecycleState.inactive) {
      _isPaused = true;
      _timer?.cancel();
    }
  }

  Future<void> setActiveChild(Child child) async {
    _currentChild = child;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('active_child_id', child.id);

    _settings = await DatabaseHelper.instance.getScreenTimeSettings(child.id);

    // Calculate today's used seconds from SharedPreferences
    final today = DateTime.now().toIso8601String().substring(0, 10);
    final key = 'screen_time_${child.id}_$today';
    final usedSeconds = prefs.getInt(key) ?? 0;

    final limitMinutes = _settings!.currentLimitMinutes;
    final limitSeconds = limitMinutes * 60;

    _remainingSeconds = (limitSeconds - usedSeconds);
    if (_remainingSeconds <= 0) {
      _remainingSeconds = 0;
      _isLockedOut = true;
    } else {
      _isLockedOut = false;
    }

    notifyListeners();
    _startTimer();
  }

  void _startTimer() {
    _timer?.cancel();
    if (_isLockedOut || _currentChild == null) return;

    _timer = Timer.periodic(const Duration(seconds: 1), (timer) async {
      if (_remainingSeconds > 0) {
        _remainingSeconds--;

        // Periodically persist to SharedPreferences every 5 seconds or when hitting 0
        if (_remainingSeconds % 5 == 0 || _remainingSeconds == 0) {
          _saveUsedTimeToPrefs();
        }

        if (_remainingSeconds <= 0) {
          _remainingSeconds = 0;
          _isLockedOut = true;
          _timer?.cancel();
          notifyListeners();
        } else {
          notifyListeners();
        }
      }
    });
  }

  Future<void> _saveUsedTimeToPrefs() async {
    if (_currentChild == null || _settings == null) return;
    final prefs = await SharedPreferences.getInstance();
    final today = DateTime.now().toIso8601String().substring(0, 10);
    final key = 'screen_time_${_currentChild!.id}_$today';

    final totalLimitSeconds = _settings!.currentLimitMinutes * 60;
    final usedSeconds = totalLimitSeconds - _remainingSeconds;
    await prefs.setInt(key, usedSeconds > 0 ? usedSeconds : 0);
  }

  void addExtensionMinutes(int minutes) {
    _remainingSeconds += minutes * 60;
    _isLockedOut = false;
    notifyListeners();
    _startTimer();
  }

  void resetDailyTime() {
    if (_settings != null) {
      _remainingSeconds = _settings!.currentLimitMinutes * 60;
      _isLockedOut = false;
      notifyListeners();
      _startTimer();
    }
  }

  Future<void> refreshSettings() async {
    if (_currentChild != null) {
      await setActiveChild(_currentChild!);
    }
  }

  void stopTimer() {
    _timer?.cancel();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _timer?.cancel();
    super.dispose();
  }
}
