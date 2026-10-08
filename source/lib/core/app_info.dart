import 'package:flutter/services.dart';

/// Phiên bản hiển thị trong app. Nhớ cập nhật cùng lúc với `version:` trong pubspec.yaml.
const String kAppVersion = '1.0.2 (build 3)';

/// App chỉ chạy ở màn hình NGANG (cả hai chiều xoay ngang).
const List<DeviceOrientation> kAppOrientations = [
  DeviceOrientation.landscapeLeft,
  DeviceOrientation.landscapeRight,
];
