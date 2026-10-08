import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'core/app_info.dart';
import 'core/navigation.dart';
import 'core/services/screen_time_service.dart';
import 'core/theme/app_theme.dart';
import 'screens/lockout/lockout_screen.dart';
import 'screens/splash/splash_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Chỉ hiển thị màn hình ngang
  await SystemChrome.setPreferredOrientations(kAppOrientations);

  runApp(const KidsWorldApp());
}

class KidsWorldApp extends StatelessWidget {
  const KidsWorldApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Kids World - Học Tiếng Anh',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      navigatorKey: appNavigatorKey,
      home: const SplashScreen(),
      // Màn khoá giờ chơi được phủ lên TOÀN BỘ ứng dụng (kể cả game, YouTube, dialog)
      // thay vì chỉ kiểm tra trong từng màn hình.
      builder: (context, navigator) => _ScreenTimeLockOverlay(child: navigator ?? const SizedBox.shrink()),
    );
  }
}

class _ScreenTimeLockOverlay extends StatelessWidget {
  final Widget child;

  const _ScreenTimeLockOverlay({required this.child});

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: ScreenTimeService.instance,
      child: child,
      builder: (context, navigator) {
        final locked = ScreenTimeService.instance.isLockedOut;
        return Stack(
          fit: StackFit.expand,
          children: [
            // Khi bị khoá: tắt animation & chặn tương tác với nội dung bên dưới
            TickerMode(
              enabled: !locked,
              child: IgnorePointer(
                ignoring: locked,
                child: ExcludeFocus(excluding: locked, child: navigator!),
              ),
            ),
            if (locked)
              Positioned.fill(
                child: Navigator(
                  onGenerateRoute: (_) => PageRouteBuilder(
                    pageBuilder: (_, __, ___) => const LockoutScreen(),
                    transitionDuration: Duration.zero,
                  ),
                ),
              ),
          ],
        );
      },
    );
  }
}
