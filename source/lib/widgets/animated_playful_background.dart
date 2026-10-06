import 'dart:math';

import 'package:flutter/material.dart';

/// Danh sách hình nền hoạt họa pastel dùng chung cho toàn bộ app.
/// Mỗi khi một màn hình được mở, hệ thống sẽ chọn ngẫu nhiên 1 hình
/// (không trùng với hình vừa dùng trước đó).
class AppBackgrounds {
  AppBackgrounds._();

  static const List<String> assets = [
    'assets/backgrounds/bg_candy_sky.jpg',
    'assets/backgrounds/bg_green_meadow.jpg',
    'assets/backgrounds/bg_ocean_bubbles.jpg',
    'assets/backgrounds/bg_pastel_galaxy.jpg',
    'assets/backgrounds/bg_rainbow_peach.jpg',
    'assets/backgrounds/bg_candy_land.jpg',
    'assets/backgrounds/bg_autumn_forest.jpg',
    'assets/backgrounds/bg_balloon_party.jpg',
    'assets/backgrounds/bg_snowy_winter.jpg',
    'assets/backgrounds/bg_doodle_pattern.jpg',
  ];

  static final Random _random = Random();
  static int _lastIndex = -1;

  /// Chọn ngẫu nhiên 1 hình nền, tránh lặp lại hình vừa hiển thị.
  static String pickRandom() {
    int index;
    do {
      index = _random.nextInt(assets.length);
    } while (index == _lastIndex && assets.length > 1);
    _lastIndex = index;
    return assets[index];
  }

  /// Nạp trước toàn bộ hình nền vào bộ nhớ đệm để chuyển màn hình mượt mà.
  static Future<void> precacheAll(BuildContext context) async {
    for (final path in assets) {
      await precacheImage(AssetImage(path), context);
    }
  }
}

/// Nền hoạt họa cho mọi màn hình: hình nền ngẫu nhiên + hiệu ứng zoom/trôi nhẹ.
/// Hỗ trợ cả màn hình dọc và ngang (ảnh luôn phủ kín, ưu tiên giữ phần họa tiết
/// phía dưới khi ở chế độ dọc).
class AnimatedPlayfulBackground extends StatefulWidget {
  final Widget child;

  /// Lớp phủ trắng mờ giúp chữ/thẻ phía trên dễ đọc hơn (0.0 – 1.0).
  final double overlayOpacity;

  /// Màu lớp phủ (mặc định trắng). Ví dụ màn hình đi ngủ dùng màu trời đêm.
  final Color overlayColor;

  const AnimatedPlayfulBackground({
    super.key,
    required this.child,
    this.overlayOpacity = 0.12,
    this.overlayColor = Colors.white,
  });

  @override
  State<AnimatedPlayfulBackground> createState() => _AnimatedPlayfulBackgroundState();
}

class _AnimatedPlayfulBackgroundState extends State<AnimatedPlayfulBackground> with SingleTickerProviderStateMixin {
  late final AnimationController _animController;
  late final String _asset;

  @override
  void initState() {
    super.initState();
    _asset = AppBackgrounds.pickRandom();
    _animController = AnimationController(vsync: this, duration: const Duration(seconds: 18))..repeat(reverse: true);
  }

  @override
  void dispose() {
    _animController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isLandscape = MediaQuery.of(context).orientation == Orientation.landscape;

    return Stack(
      fit: StackFit.expand,
      children: [
        // Hình nền với hiệu ứng "thở" nhẹ (zoom + trôi ngang chậm)
        AnimatedBuilder(
          animation: _animController,
          builder: (context, child) {
            final t = Curves.easeInOut.transform(_animController.value);
            return Transform.translate(
              offset: Offset((t - 0.5) * 10, 0),
              child: Transform.scale(scale: 1.04 + t * 0.04, child: child),
            );
          },
          child: Image.asset(
            _asset,
            fit: BoxFit.cover,
            alignment: isLandscape ? Alignment.center : Alignment.bottomCenter,
            filterQuality: FilterQuality.medium,
          ),
        ),
        if (widget.overlayOpacity > 0) ColoredBox(color: widget.overlayColor.withValues(alpha: widget.overlayOpacity)),
        widget.child,
      ],
    );
  }
}
