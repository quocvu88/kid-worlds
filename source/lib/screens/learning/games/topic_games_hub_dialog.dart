import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/services/audio_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../models/topic_item_model.dart';
import '../../../models/topic_model.dart';
import 'coloring_game_screen.dart';
import 'memory_match_game_screen.dart';

class TopicGamesHubDialog extends StatelessWidget {
  final Topic topic;
  final List<TopicItem> items;
  final TopicItem? currentItem;

  const TopicGamesHubDialog({
    super.key,
    required this.topic,
    required this.items,
    this.currentItem,
  });

  static void show(BuildContext context, {
    required Topic topic,
    required List<TopicItem> items,
    TopicItem? currentItem,
  }) {
    HapticFeedback.mediumImpact();
    AudioService.instance.speakVietnameseAfterTransition(
      'Chào mừng bé đến với Khu Vui Chơi ${topic.titleVi}!',
      delay: const Duration(milliseconds: 350),
    );

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => TopicGamesHubDialog(
        topic: topic,
        items: items,
        currentItem: currentItem,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final orientation = MediaQuery.of(context).orientation;
    final isLandscape = orientation == Orientation.landscape;

    final selectedGameKeys = topic.selectedGames.isNotEmpty
        ? topic.selectedGames
        : const ['coloring', 'memory_match'];

    return Container(
      constraints: BoxConstraints(
        maxHeight: isLandscape
            ? MediaQuery.of(context).size.height * 0.92
            : MediaQuery.of(context).size.height * 0.75,
      ),
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(32),
        boxShadow: const [
          BoxShadow(
            color: Colors.black26,
            blurRadius: 25,
            offset: Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Drag handle
          Center(
            child: Container(
              width: 48,
              height: 5,
              decoration: BoxDecoration(
                color: Colors.grey.withValues(alpha: 0.3),
                borderRadius: BorderRadius.circular(10),
              ),
            ),
          ),
          const SizedBox(height: 12),

          // Header
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: AppColors.primary.withValues(alpha: 0.12),
                      shape: BoxShape.circle,
                    ),
                    child: const Text('🎮', style: TextStyle(fontSize: 26)),
                  ),
                  const SizedBox(width: 12),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Khu Vui Chơi Bộ Sưu Tập',
                        style: AppTextStyles.kidTitle.copyWith(fontSize: 18, color: AppColors.primary),
                      ),
                      Text(
                        topic.titleVi,
                        style: const TextStyle(fontSize: 13, color: AppColors.textLight, fontWeight: FontWeight.w600),
                      ),
                    ],
                  ),
                ],
              ),
              IconButton(
                icon: const Icon(Icons.close_rounded, color: AppColors.textLight, size: 24),
                onPressed: () => Navigator.of(context).pop(),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Games Grid / Row (Max 2-3 games)
          Expanded(
            child: isLandscape
                ? Row(
                    children: selectedGameKeys.map((gameKey) {
                      return Expanded(
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 6),
                          child: _buildGameCard(context, gameKey, isLandscape),
                        ),
                      );
                    }).toList(),
                  )
                : ListView(
                    physics: const BouncingScrollPhysics(),
                    children: selectedGameKeys.map((gameKey) {
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: _buildGameCard(context, gameKey, isLandscape),
                      );
                    }).toList(),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildGameCard(BuildContext context, String gameKey, bool isLandscape) {
    String emoji;
    String title;
    String subtitle;
    List<Color> gradientColors;
    VoidCallback onLaunch;

    switch (gameKey) {
      case 'coloring':
        emoji = '🎨';
        title = 'Tô Màu Sáng Tạo';
        subtitle = 'Vẽ cọ sáp, chọn màu rực rỡ và lưu tranh vào hồ sơ bé';
        gradientColors = [const Color(0xFFFF6584), const Color(0xFFFF8E53)];
        onLaunch = () {
          Navigator.of(context).pop();
          Navigator.of(context).push(
            MaterialPageRoute(
              builder: (ctx) => ColoringGameScreen(
                topic: topic,
                items: items,
                currentItem: currentItem,
              ),
            ),
          );
        };
        break;

      case 'memory_match':
        emoji = '🃏';
        title = 'Tìm Cặp Hình Giống Nhau';
        subtitle = 'Lật mở thẻ 3D, nhớ hình ảnh & rèn luyện trí nhớ nhanh';
        gradientColors = [const Color(0xFF38BDF8), const Color(0xFF3B82F6)];
        onLaunch = () {
          Navigator.of(context).pop();
          Navigator.of(context).push(
            MaterialPageRoute(
              builder: (ctx) => MemoryMatchGameScreen(
                topic: topic,
                items: items,
              ),
            ),
          );
        };
        break;

      case 'shadow_match':
        emoji = '🧩';
        title = 'Ghép Bóng Nhận Diện';
        subtitle = 'Kéo thả hình ảnh khớp đúng với bóng đen Silhouette';
        gradientColors = [const Color(0xFF10B981), const Color(0xFF059669)];
        onLaunch = () {
          Navigator.of(context).pop();
          _showComingSoon(context, 'Ghép Bóng Nhận Diện');
        };
        break;

      case 'bubble_pop':
        emoji = '🫧';
        title = 'Đập Bóng Từ Vựng';
        subtitle = 'Nghe phát âm và chạm nhanh làm nổ tung bong bóng';
        gradientColors = [const Color(0xFFA855F7), const Color(0xFF7C3AED)];
        onLaunch = () {
          Navigator.of(context).pop();
          _showComingSoon(context, 'Đập Bóng Từ Vựng');
        };
        break;

      case 'jigsaw':
        emoji = '🖼️';
        title = 'Xếp Hình Khéo Léo';
        subtitle = 'Ghép tranh 4, 9, 16 mảnh ghép thông minh';
        gradientColors = [const Color(0xFFF59E0B), const Color(0xFFD97706)];
        onLaunch = () {
          Navigator.of(context).pop();
          _showComingSoon(context, 'Xếp Hình Khéo Léo');
        };
        break;

      case 'sound_linker':
      default:
        emoji = '🔔';
        title = 'Nối Âm Thanh & Hình';
        subtitle = 'Lắng nghe tiếng kêu thực tế và chọn đúng hình';
        gradientColors = [const Color(0xFFEC4899), const Color(0xFFBE185D)];
        onLaunch = () {
          Navigator.of(context).pop();
          _showComingSoon(context, 'Nối Âm Thanh & Hình');
        };
        break;
    }

    return InkWell(
      onTap: () {
        HapticFeedback.lightImpact();
        onLaunch();
      },
      borderRadius: BorderRadius.circular(24),
      child: Container(
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: gradientColors,
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(24),
          boxShadow: [
            BoxShadow(
              color: gradientColors.first.withValues(alpha: 0.35),
              blurRadius: 12,
              offset: const Offset(0, 6),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: isLandscape ? MainAxisAlignment.spaceBetween : MainAxisAlignment.center,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.25),
                    shape: BoxShape.circle,
                  ),
                  child: Text(emoji, style: const TextStyle(fontSize: 32)),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.9),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: const Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.play_arrow_rounded, color: AppColors.textDark, size: 16),
                      SizedBox(width: 2),
                      Text('Chơi', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: AppColors.textDark)),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Text(
              title,
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w900,
                fontSize: 17,
                letterSpacing: 0.3,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              subtitle,
              style: TextStyle(
                color: Colors.white.withValues(alpha: 0.9),
                fontSize: 12,
                height: 1.3,
              ),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }

  void _showComingSoon(BuildContext context, String gameName) {
    AudioService.instance.speakVietnamese('Trò chơi $gameName đang được chuẩn bị thêm tài nguyên!');
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Trò chơi $gameName sẽ sớm được mở thêm!'),
        duration: const Duration(seconds: 2),
      ),
    );
  }
}
