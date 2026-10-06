class ScreenTimeSettings {
  final String childId;
  final int dailyLimitMinutes;
  final int weekendLimitMinutes;
  final String pinCode;

  ScreenTimeSettings({
    required this.childId,
    this.dailyLimitMinutes = 30,
    this.weekendLimitMinutes = 45,
    this.pinCode = '1234',
  });

  bool get isWeekend {
    final weekday = DateTime.now().weekday;
    return weekday == DateTime.saturday || weekday == DateTime.sunday;
  }

  int get currentLimitMinutes => isWeekend ? weekendLimitMinutes : dailyLimitMinutes;

  Map<String, dynamic> toMap() {
    return {
      'child_id': childId,
      'daily_limit_minutes': dailyLimitMinutes,
      'weekend_limit_minutes': weekendLimitMinutes,
      'pin_code': pinCode,
    };
  }

  factory ScreenTimeSettings.fromMap(Map<String, dynamic> map) {
    return ScreenTimeSettings(
      childId: map['child_id'] as String,
      dailyLimitMinutes: map['daily_limit_minutes'] as int? ?? 30,
      weekendLimitMinutes: map['weekend_limit_minutes'] as int? ?? 45,
      pinCode: map['pin_code'] as String? ?? '1234',
    );
  }

  ScreenTimeSettings copyWith({String? childId, int? dailyLimitMinutes, int? weekendLimitMinutes, String? pinCode}) {
    return ScreenTimeSettings(
      childId: childId ?? this.childId,
      dailyLimitMinutes: dailyLimitMinutes ?? this.dailyLimitMinutes,
      weekendLimitMinutes: weekendLimitMinutes ?? this.weekendLimitMinutes,
      pinCode: pinCode ?? this.pinCode,
    );
  }
}
