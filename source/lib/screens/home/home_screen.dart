import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../core/database/database_helper.dart';
import '../../core/services/audio_service.dart';
import '../../core/services/screen_time_service.dart';
import '../../core/utils/image_helper.dart';
import '../../core/theme/app_theme.dart';
import '../../models/child_model.dart';
import '../../models/topic_model.dart';
import '../../widgets/parent_gate_dialog.dart';
import '../../widgets/screen_time_badge.dart';
import '../../widgets/child_profile_popup.dart';
import '../../widgets/animated_playful_background.dart';
import '../learning/flashcard_learning_screen.dart';
import '../topic_store/topic_store_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  List<Topic> _topics = [];
  bool _isLoading = true;
  String _selectedCategory = 'all';

  @override
  void initState() {
    super.initState();
    _loadTopics();
  }

  Future<void> _loadTopics() async {
    setState(() => _isLoading = true);
    final topics = await DatabaseHelper.instance.getTopics();
    setState(() {
      _topics = topics;
      _isLoading = false;
    });
  }

  List<Topic> _getFilteredTopics(Child? activeChild) {
    return _topics.where((t) {
      if (_selectedCategory != 'all' && t.category != _selectedCategory) {
        return false;
      }
      if (activeChild != null) {
        final age = activeChild.age;
        return age >= t.targetAgeMin && age <= t.targetAgeMax;
      }
      return true;
    }).toList();
  }

  void _onAvatarTapped() {
    HapticFeedback.lightImpact();
    final child = ScreenTimeService.instance.currentChild;
    if (child != null) {
      ChildProfilePopup.show(
        context,
        child: child,
        onAvatarChanged: () {
          setState(() {});
        },
      );
    }
  }

  void _onOpenStatistics() async {
    HapticFeedback.lightImpact();
    final child = ScreenTimeService.instance.currentChild;
    if (child != null) {
      final stats = await DatabaseHelper.instance.getChildStats(child.id);
      if (!mounted) return;

      showDialog(context: context, builder: (context) => _buildStatisticsDialog(context, child, stats));
    }
  }

  Widget _buildStatisticsDialog(BuildContext context, Child child, Map<String, dynamic> stats) {
    final isLandscape = MediaQuery.of(context).orientation == Orientation.landscape;

    return Dialog(
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.transparent,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
      insetPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: ConstrainedBox(
        constraints: BoxConstraints(maxWidth: isLandscape ? 560 : 360, maxHeight: isLandscape ? 280 : 380),
        child: Padding(
          padding: const EdgeInsets.all(18.0),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(5),
                        decoration: BoxDecoration(
                          color: AppColors.secondary.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Icon(Icons.analytics_outlined, size: 16, color: AppColors.secondary),
                      ),
                      const SizedBox(width: 8),
                      Text('Thống Kê Học Tập • Bé ${child.name}', style: AppTextStyles.configTitle),
                    ],
                  ),
                  IconButton(
                    visualDensity: VisualDensity.compact,
                    padding: EdgeInsets.zero,
                    constraints: const BoxConstraints(),
                    icon: const Icon(Icons.close_rounded, size: 20, color: AppColors.textLight),
                    onPressed: () => Navigator.of(context).pop(),
                  ),
                ],
              ),
              const SizedBox(height: 12),

              // Screen Time Row
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                decoration: BoxDecoration(
                  color: AppColors.surfaceMuted,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.border),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.hourglass_bottom_rounded, size: 20, color: AppColors.secondary),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Thời gian còn lại hôm nay', style: AppTextStyles.configCaption),
                          Text(
                            ScreenTimeService.instance.remainingFormatted,
                            style: AppTextStyles.configValue.copyWith(fontSize: 16),
                          ),
                        ],
                      ),
                    ),
                    InkWell(
                      onTap: () async {
                        HapticFeedback.lightImpact();
                        final passed = await ParentGateDialog.verify(context, title: 'Cộng Thêm Thời Gian');
                        if (passed && context.mounted) {
                          ScreenTimeService.instance.addExtensionMinutes(15);
                          Navigator.of(context).pop();
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(
                              content: Text('Đã cộng thêm 15 phút học cho bé!'),
                              behavior: SnackBarBehavior.floating,
                            ),
                          );
                        }
                      },
                      borderRadius: BorderRadius.circular(8),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                        decoration: BoxDecoration(color: AppColors.secondary, borderRadius: BorderRadius.circular(8)),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.add, size: 14, color: Colors.white),
                            const SizedBox(width: 3),
                            Text('15m', style: AppTextStyles.configButton.copyWith(color: Colors.white, fontSize: 12)),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),

              // Bento Metrics: 3 Tiles
              Row(
                children: [
                  Expanded(
                    child: _buildCompactMetricTile(
                      icon: Icons.record_voice_over_outlined,
                      color: AppColors.success,
                      label: 'Từ vựng',
                      value: '${stats['total_words_learned'] ?? 0}',
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: _buildCompactMetricTile(
                      icon: Icons.play_circle_outline,
                      color: AppColors.primary,
                      label: 'Video',
                      value: '${stats['total_videos_watched'] ?? 0}',
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: _buildCompactMetricTile(
                      icon: Icons.cloud_done_outlined,
                      color: AppColors.purple,
                      label: 'Gói Offline',
                      value: '${_topics.length}',
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCompactMetricTile({
    required IconData icon,
    required Color color,
    required String label,
    required String value,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: color.withValues(alpha: 0.2)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(label, style: AppTextStyles.configCaption.copyWith(color: AppColors.textBody)),
              Icon(icon, size: 15, color: color),
            ],
          ),
          const SizedBox(height: 4),
          Text(value, style: AppTextStyles.configValue.copyWith(fontSize: 18, color: color)),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    // Màn khoá được phủ toàn cục trong main.dart; không cần rebuild cả màn hình mỗi giây.
    return Builder(
      builder: (context) {
        final activeChild = ScreenTimeService.instance.currentChild;

        return AnimatedPlayfulBackground(
          child: Scaffold(
            backgroundColor: Colors.transparent,
            appBar: AppBar(
              backgroundColor: Colors.transparent,
              surfaceTintColor: Colors.transparent,
              elevation: 0,
              scrolledUnderElevation: 0,
              leading: Padding(
                padding: const EdgeInsets.only(left: 12.0),
                child: GestureDetector(
                  onTap: _onAvatarTapped,
                  child: CircleAvatar(
                    backgroundColor: AppColors.primary.withValues(alpha: 0.15),
                    radius: 16,
                    child: Text(
                      (activeChild?.avatarUrl != null &&
                              activeChild!.avatarUrl!.isNotEmpty &&
                              !activeChild.avatarUrl!.startsWith('assets/'))
                          ? activeChild.avatarUrl!
                          : (activeChild?.gender == 'female' ? '👧' : '👦'),
                      style: const TextStyle(fontSize: 17),
                    ),
                  ),
                ),
              ),
              title: Text(
                activeChild != null ? 'Chào ${activeChild.name}! 🌟' : 'Kids World',
                style: AppTextStyles.kidTitle,
              ),
              actions: [
                const ScreenTimeBadge(),
                const SizedBox(width: 4),
                IconButton(
                  icon: const Icon(Icons.cloud_download_outlined, color: AppColors.secondary, size: 22),
                  tooltip: 'Kho Gói Chủ Đề',
                  onPressed: () async {
                    HapticFeedback.lightImpact();
                    final passed = await ParentGateDialog.verify(context, title: 'Mở Kho Gói Chủ Đề');
                    if (passed && context.mounted) {
                      await Navigator.of(context).push(
                        MaterialPageRoute(builder: (_) => const TopicStoreScreen()),
                      );
                      _loadTopics();
                    }
                  },
                ),
                IconButton(
                  icon: const Icon(Icons.analytics_outlined, color: AppColors.primary, size: 22),
                  tooltip: 'Thống kê học tập',
                  onPressed: _onOpenStatistics,
                ),
                const SizedBox(width: 8),
              ],
            ),
            body: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _buildTopicsBody(activeChild),
          ),
        );
      },
    );
  }

  Color _getCategoryColor(String category) {
    switch (category) {
      case 'animals': return const Color(0xFFFECA57);
      case 'vehicles': return const Color(0xFF38BDF8);
      case 'fruits': return const Color(0xFFFF6B81);
      case 'space': return const Color(0xFFA855F7);
      default: return const Color(0xFF10B981);
    }
  }

  String _getCategoryEmoji(String category) {
    switch (category) {
      case 'animals': return '🦁';
      case 'vehicles': return '🚗';
      case 'fruits': return '🍎';
      case 'space': return '🚀';
      default: return '⭐';
    }
  }

  // ---------------------------------------------------------------------------
  // Giao diện chọn chủ đề cho bé: ít chữ, hình to, chạm vào đâu trên thẻ cũng vào học.
  // ---------------------------------------------------------------------------

  static const List<(String key, String label, String emoji)> _categories = [
    ('all', 'Tất cả', '🌈'),
    ('animals', 'Động vật', '🦁'),
    ('vehicles', 'Xe cộ', '🚗'),
    ('fruits', 'Trái cây', '🍎'),
    ('space', 'Vũ trụ', '🚀'),
  ];

  Widget _buildTopicsBody(Child? activeChild) {
    final filteredTopics = _getFilteredTopics(activeChild);

    return SafeArea(
      top: false,
      child: Column(
        children: [
          // Hàng chủ đề: nút tròn có hình (bé chưa biết đọc vẫn chọn được)
          SizedBox(
            height: 64,
            child: Center(
              child: ListView.separated(
                shrinkWrap: true,
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 16),
                itemCount: _categories.length,
                separatorBuilder: (_, __) => const SizedBox(width: 14),
                itemBuilder: (context, i) {
                  final c = _categories[i];
                  return _buildCategoryBubble(c.$1, c.$2, c.$3);
                },
              ),
            ),
          ),

          // Thẻ chủ đề cỡ lớn, vuốt ngang
          Expanded(
            child: filteredTopics.isEmpty
                ? _buildEmptyState(activeChild)
                : LayoutBuilder(
                    builder: (context, constraints) {
                      final cardHeight = constraints.maxHeight - 20;
                      final cardWidth = (cardHeight * 0.82).clamp(160.0, 300.0);
                      return ListView.separated(
                        scrollDirection: Axis.horizontal,
                        physics: const BouncingScrollPhysics(),
                        padding: const EdgeInsets.fromLTRB(20, 4, 20, 16),
                        itemCount: filteredTopics.length,
                        separatorBuilder: (_, __) => const SizedBox(width: 18),
                        itemBuilder: (context, index) => SizedBox(
                          width: cardWidth,
                          child: _BigTopicCard(
                            topic: filteredTopics[index],
                            color: _getCategoryColor(filteredTopics[index].category),
                            emoji: _getCategoryEmoji(filteredTopics[index].category),
                            onTap: () => _openTopic(filteredTopics[index]),
                          ),
                        ),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }

  Future<void> _openTopic(Topic topic) async {
    HapticFeedback.mediumImpact();
    await Navigator.of(context).push(MaterialPageRoute(builder: (_) => FlashcardLearningScreen(topic: topic)));
    if (mounted) _loadTopics();
  }

  Widget _buildEmptyState(Child? activeChild) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Text('🧸', style: TextStyle(fontSize: 64)),
          const SizedBox(height: 8),
          Text(
            'Chưa có bài học cho độ tuổi này (${activeChild?.age ?? 0} tuổi).',
            style: AppTextStyles.configCaption,
          ),
        ],
      ),
    );
  }

  Widget _buildCategoryBubble(String key, String label, String emoji) {
    final isSelected = _selectedCategory == key;
    final color = key == 'all' ? AppColors.primary : _getCategoryColor(key);

    return GestureDetector(
      onTap: () {
        HapticFeedback.lightImpact();
        setState(() => _selectedCategory = key);
        AudioService.instance.speakVietnamese(label); // đọc tên chủ đề cho bé chưa biết chữ
      },
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        curve: Curves.easeOutBack,
        padding: EdgeInsets.only(left: 6, right: isSelected ? 16 : 6),
        height: isSelected ? 54 : 48,
        decoration: BoxDecoration(
          color: isSelected ? color : Colors.white,
          borderRadius: BorderRadius.circular(30),
          border: Border.all(color: isSelected ? color : AppColors.border, width: 2),
          boxShadow: isSelected
              ? [BoxShadow(color: color.withValues(alpha: 0.35), blurRadius: 10, offset: const Offset(0, 4))]
              : null,
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 40,
              height: 40,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: isSelected ? Colors.white : color.withValues(alpha: 0.15),
                shape: BoxShape.circle,
              ),
              child: Text(emoji, style: const TextStyle(fontSize: 22)),
            ),
            // Chỉ hiện chữ ở chủ đề đang chọn để gọn, hình là chính
            if (isSelected) ...[
              const SizedBox(width: 8),
              Text(
                label,
                style: AppTextStyles.kidTitle.copyWith(fontSize: 15, color: Colors.white),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

/// Thẻ chủ đề cỡ lớn: ảnh tràn thẻ, tên chủ đề to, nút ▶ lớn. Chạm bất kỳ đâu trên thẻ để vào học.
class _BigTopicCard extends StatefulWidget {
  final Topic topic;
  final Color color;
  final String emoji;
  final VoidCallback onTap;

  const _BigTopicCard({required this.topic, required this.color, required this.emoji, required this.onTap});

  @override
  State<_BigTopicCard> createState() => _BigTopicCardState();
}

class _BigTopicCardState extends State<_BigTopicCard> {
  bool _pressed = false;

  @override
  Widget build(BuildContext context) {
    final topic = widget.topic;
    final fallback = Container(
      color: widget.color.withValues(alpha: 0.25),
      alignment: Alignment.center,
      child: Text(widget.emoji, style: const TextStyle(fontSize: 72)),
    );

    return GestureDetector(
      onTapDown: (_) => setState(() => _pressed = true),
      onTapCancel: () => setState(() => _pressed = false),
      onTapUp: (_) => setState(() => _pressed = false),
      onTap: widget.onTap,
      child: AnimatedScale(
        scale: _pressed ? 0.95 : 1.0,
        duration: const Duration(milliseconds: 120),
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(28),
            border: Border.all(color: Colors.white, width: 4),
            boxShadow: [
              BoxShadow(color: widget.color.withValues(alpha: 0.45), blurRadius: 14, offset: const Offset(0, 6)),
            ],
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(24),
            child: Stack(
              fit: StackFit.expand,
              children: [
                (topic.thumbnailPath != null && topic.thumbnailPath!.trim().isNotEmpty)
                    ? ImageHelper.buildSafeImage(topic.thumbnailPath, fit: BoxFit.cover, fallback: fallback)
                    : fallback,

                // Lớp tối dần ở dưới để chữ trắng dễ đọc
                const DecoratedBox(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topCenter,
                      end: Alignment.bottomCenter,
                      stops: [0.45, 1.0],
                      colors: [Colors.transparent, Color(0xCC000000)],
                    ),
                  ),
                ),

                // Huy hiệu chủ đề
                Positioned(
                  top: 10,
                  left: 10,
                  child: Container(
                    width: 44,
                    height: 44,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.92),
                      shape: BoxShape.circle,
                    ),
                    child: Text(widget.emoji, style: const TextStyle(fontSize: 24)),
                  ),
                ),

                // Tên chủ đề + nút chơi
                Positioned(
                  left: 14,
                  right: 12,
                  bottom: 12,
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Expanded(
                        child: Text(
                          topic.titleVi,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: AppTextStyles.kidTitle.copyWith(
                            fontSize: 20,
                            height: 1.15,
                            color: Colors.white,
                            shadows: const [Shadow(color: Colors.black54, blurRadius: 6)],
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Container(
                        width: 56,
                        height: 56,
                        decoration: BoxDecoration(
                          color: widget.color,
                          shape: BoxShape.circle,
                          border: Border.all(color: Colors.white, width: 3),
                        ),
                        child: const Icon(Icons.play_arrow_rounded, color: Colors.white, size: 36),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
