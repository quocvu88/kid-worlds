import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../core/database/database_helper.dart';
import '../../core/theme/app_theme.dart';
import '../profile/profile_selection_screen.dart';
import '../../widgets/animated_playful_background.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    try {
      // 1. Initialize SQLite
      await DatabaseHelper.instance.database;

      // 2. Nạp trước hình nền để chuyển màn hình mượt mà
      if (mounted) await AppBackgrounds.precacheAll(context);

      // Wait 1.2s for splash animation
      await Future.delayed(const Duration(milliseconds: 1200));

      // Luôn vào màn hình chọn hồ sơ để các bé chọn avatar của mình vào học
      if (!mounted) return;
      Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => const ProfileSelectionScreen()));
    } catch (e, stack) {
      debugPrint('Bootstrap error: $e\n$stack');
      if (!mounted) return;
      Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => const ProfileSelectionScreen()));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AnimatedPlayfulBackground(
        child: SizedBox(
          width: double.infinity,
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                width: 120,
                height: 120,
                decoration: BoxDecoration(
                  color: Colors.white,
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(color: Colors.black.withValues(alpha: 0.15), blurRadius: 20, offset: const Offset(0, 8)),
                  ],
                ),
                child: const Center(child: Icon(Icons.auto_stories, size: 64, color: AppColors.primary)),
              ).animate().scale(duration: 800.ms, curve: Curves.elasticOut).then().shake(duration: 600.ms),
              const SizedBox(height: 24),
              const Text(
                'Kids World',
                style: TextStyle(
                  fontSize: 36,
                  fontWeight: FontWeight.w900,
                  color: AppColors.primary,
                  letterSpacing: 1.5,
                ),
              ).animate().fadeIn(delay: 300.ms).slideY(begin: 0.3, end: 0),
              const SizedBox(height: 8),
              const Text(
                'Vừa Học Tiếng Anh • Vừa Vui Chơi',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: AppColors.textBody),
              ).animate().fadeIn(delay: 500.ms),
              const SizedBox(height: 48),
              const CircularProgressIndicator(valueColor: AlwaysStoppedAnimation<Color>(AppColors.primary)),
            ],
          ),
        ),
      ),
    );
  }
}

// Helper MaterialPageRoute replacement
class MaterialUrlPageRoute<T> extends MaterialPageRoute<T> {
  MaterialUrlPageRoute({required super.builder});
}
