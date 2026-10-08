import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../core/navigation.dart';
import '../../core/services/audio_service.dart';
import '../../core/services/screen_time_service.dart';
import '../../core/theme/app_theme.dart';
import '../../widgets/parent_gate_dialog.dart';
import '../../widgets/animated_playful_background.dart';
import '../profile/profile_selection_screen.dart';

class LockoutScreen extends StatelessWidget {
  const LockoutScreen({super.key});

  @override
  Widget build(BuildContext context) {
    // Immediately stop any active audio when lockout starts
    AudioService.instance.stop();

    return PopScope(
      canPop: false, // Prevent kid from using Android back button to escape
      child: AnimatedPlayfulBackground(
        overlayColor: AppColors.nightSky,
        overlayOpacity: 0.86,
        child: Scaffold(
          backgroundColor: Colors.transparent,
          body: SafeArea(
            child: OrientationBuilder(
              builder: (context, orientation) {
                final isLandscape = orientation == Orientation.landscape;

                if (isLandscape) {
                  return _buildLandscapeLayout(context);
                }
                return _buildPortraitLayout(context);
              },
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildPortraitLayout(BuildContext context) {
    return Center(
      child: SingleChildScrollView(
        physics: const BouncingScrollPhysics(),
        child: SizedBox(
          width: double.infinity,
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 24.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.center,
              mainAxisSize: MainAxisSize.min,
              children: [
                _buildSleepingMascot(size: 116),
                const SizedBox(height: 24),
                _buildMessageSection(),
                const SizedBox(height: 36),
                _buildActionButtons(context),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildLandscapeLayout(BuildContext context) {
    return Center(
      child: SingleChildScrollView(
        physics: const BouncingScrollPhysics(),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 32.0, vertical: 16.0),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              // Left: Animated Sleeping Mascot
              Expanded(
                flex: 4,
                child: Center(child: _buildSleepingMascot(size: 90)),
              ),
              const SizedBox(width: 24),
              // Right: Message & Parent Unlock Button
              Expanded(
                flex: 6,
                child: Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      _buildMessageSection(),
                      const SizedBox(height: 18),
                      _buildActionButtons(context),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSleepingMascot({required double size}) {
    return Container(
          width: size,
          height: size,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: AppColors.moonYellow.withValues(alpha: 0.15),
            boxShadow: [
              BoxShadow(color: AppColors.moonYellow.withValues(alpha: 0.12), blurRadius: 24, spreadRadius: 6),
            ],
          ),
          child: Center(
            child: Icon(Icons.nightlight_round, size: size * 0.6, color: AppColors.moonYellow),
          ),
        )
        .animate(onPlay: (controller) => controller.repeat(reverse: true))
        .scale(
          duration: 2000.ms,
          begin: const Offset(0.94, 0.94),
          end: const Offset(1.06, 1.06),
          curve: Curves.easeInOut,
        );
  }

  Widget _buildMessageSection() {
    return Column(
      children: [
        Text(
          'Đã Đến Giờ Nghỉ Ngơi! 🌙',
          textAlign: TextAlign.center,
          style: AppTextStyles.kidHeadline.copyWith(fontSize: 20, color: Colors.white, letterSpacing: -0.2),
        ).animate().fadeIn(duration: 500.ms).slideY(begin: 0.15, end: 0),
        const SizedBox(height: 8),
        Text(
          'Bé hãy để mắt nghỉ ngơi, uống nước\nvà vận động nhẹ nhàng nhé!',
          textAlign: TextAlign.center,
          style: AppTextStyles.configLabel.copyWith(fontSize: 13, color: Colors.white70, height: 1.4),
        ).animate().fadeIn(delay: 200.ms),
      ],
    );
  }

  Widget _buildActionButtons(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        SizedBox(
          width: 280,
          height: 38,
          child: ElevatedButton.icon(
            onPressed: () async {
              HapticFeedback.lightImpact();
              final passed = await ParentGateDialog.verify(context, title: 'Mở Khóa Gia Hạn Thêm Giờ');
              if (passed) {
                ScreenTimeService.instance.addExtensionMinutes(15);
                if (context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Đã gia hạn thêm 15 phút học tập cho bé!'),
                      behavior: SnackBarBehavior.floating,
                    ),
                  );
                }
              }
            },
            icon: const Icon(Icons.lock_open_rounded, size: 16, color: Colors.white),
            label: Text(
              'Bố mẹ mở khóa thêm 15 phút',
              style: AppTextStyles.configButton.copyWith(fontSize: 12.5, color: Colors.white),
            ),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              elevation: 0,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
          ),
        ),
        const SizedBox(height: 4),
        TextButton(
          onPressed: () async {
            HapticFeedback.lightImpact();
            final passed = await ParentGateDialog.verify(context, title: 'Đổi Hồ Sơ Bé');
            if (passed) {
              // Bỏ chọn bé hiện tại (KHÔNG cộng lại giờ) và quay về màn chọn hồ sơ
              await ScreenTimeService.instance.clearActiveChild();
              appNavigatorKey.currentState?.pushAndRemoveUntil(
                MaterialPageRoute(builder: (_) => const ProfileSelectionScreen()),
                (route) => false,
              );
            }
          },
          child: Text(
            'Chọn bé khác (Dành cho phụ huynh)',
            style: AppTextStyles.configCaption.copyWith(color: Colors.white54),
          ),
        ),
      ],
    );
  }
}
