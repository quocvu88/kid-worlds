import 'package:flutter/material.dart';

import '../core/services/screen_time_service.dart';
import '../core/theme/app_theme.dart';

import 'package:flutter/services.dart';

import 'parent_gate_dialog.dart';

class ScreenTimeBadge extends StatelessWidget {
  const ScreenTimeBadge({super.key});

  void _onTapBadge(BuildContext context) async {
    HapticFeedback.lightImpact();
    final passed = await ParentGateDialog.verify(context, title: 'Cộng Thêm Thời Gian');
    if (!passed || !context.mounted) return;

    final added = await showDialog<int>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: [
            const Icon(Icons.more_time_rounded, color: AppColors.secondary, size: 20),
            const SizedBox(width: 8),
            Text('Thêm Thời Gian Học', style: AppTextStyles.configTitle),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Chọn thời gian cộng thêm cho bé hôm nay:', style: AppTextStyles.configCaption),
            const SizedBox(height: 14),
            Row(
              children: [
                _buildTimeOption(ctx, 15, '+15 phút', AppColors.secondary),
                const SizedBox(width: 8),
                _buildTimeOption(ctx, 30, '+30 phút', AppColors.primary),
                const SizedBox(width: 8),
                _buildTimeOption(ctx, 60, '+60 phút', AppColors.purple),
              ],
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(null),
            child: Text('Đóng', style: AppTextStyles.configLabel.copyWith(color: AppColors.textLight)),
          ),
        ],
      ),
    );

    if (added != null && context.mounted) {
      ScreenTimeService.instance.addExtensionMinutes(added);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Đã cộng thêm $added phút học cho bé!'), behavior: SnackBarBehavior.floating),
      );
    }
  }

  Widget _buildTimeOption(BuildContext context, int minutes, String label, Color color) {
    return Expanded(
      child: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: () {
          HapticFeedback.lightImpact();
          Navigator.of(context).pop(minutes);
        },
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 10),
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: color.withValues(alpha: 0.3)),
          ),
          child: Column(
            children: [Text(label, style: AppTextStyles.configButton.copyWith(fontSize: 12, color: color))],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: ScreenTimeService.instance,
      builder: (context, _) {
        final service = ScreenTimeService.instance;
        final remainingSeconds = service.remainingSeconds;
        final minutes = remainingSeconds ~/ 60;

        Color badgeColor = AppColors.success;
        if (minutes < 5) {
          badgeColor = AppColors.primary;
        } else if (minutes < 10) {
          badgeColor = AppColors.accent;
        }

        return InkWell(
          onTap: () => _onTapBadge(context),
          borderRadius: BorderRadius.circular(8),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
            decoration: BoxDecoration(
              color: badgeColor.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: badgeColor.withValues(alpha: 0.4), width: 1),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.hourglass_top_rounded, size: 14, color: badgeColor),
                const SizedBox(width: 4),
                Text(
                  service.remainingFormatted,
                  style: AppTextStyles.configValue.copyWith(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: badgeColor == AppColors.accent ? Colors.amber.shade900 : badgeColor,
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
