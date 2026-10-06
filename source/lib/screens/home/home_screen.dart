import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../core/database/database_helper.dart';
import '../../core/services/screen_time_service.dart';
import '../../core/utils/image_helper.dart';
import '../../core/theme/app_theme.dart';
import '../../models/child_model.dart';
import '../../models/topic_model.dart';
import '../../widgets/parent_gate_dialog.dart';
import '../../widgets/screen_time_badge.dart';
import '../lockout/lockout_screen.dart';
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
    return ListenableBuilder(
      listenable: ScreenTimeService.instance,
      builder: (context, _) {
        if (ScreenTimeService.instance.isLockedOut) {
          return const LockoutScreen();
        }

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
                : OrientationBuilder(
                    builder: (context, orientation) {
                      final isLandscape = orientation == Orientation.landscape;
                      final filteredTopics = _getFilteredTopics(activeChild);

                      return SafeArea(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const SizedBox(height: 8),
                            // Category Tabs (Horizontally scrollable for all packages)
                            Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 16.0),
                              child: SingleChildScrollView(
                                scrollDirection: Axis.horizontal,
                                physics: const BouncingScrollPhysics(),
                                child: Row(
                                  children: [
                                    _buildCategoryTab('Tất cả', 'all', Icons.grid_view_rounded),
                                    const SizedBox(width: 8),
                                    _buildCategoryTab('Động vật', 'animals', Icons.pets_rounded),
                                    const SizedBox(width: 8),
                                    _buildCategoryTab('Xe cộ', 'vehicles', Icons.directions_car_rounded),
                                    const SizedBox(width: 8),
                                    _buildCategoryTab('Trái cây', 'fruits', Icons.apple_rounded),
                                    const SizedBox(width: 8),
                                    _buildCategoryTab('Vũ trụ', 'space', Icons.rocket_launch_rounded),
                                  ],
                                ),
                              ),
                            ),
                            const SizedBox(height: 8),

                            // Responsive Topics List / Grid
                            Expanded(
                              child: RefreshIndicator(
                                onRefresh: _loadTopics,
                                color: AppColors.primary,
                                child: filteredTopics.isEmpty
                                    ? ListView(
                                        physics: const AlwaysScrollableScrollPhysics(parent: BouncingScrollPhysics()),
                                        children: [
                                          SizedBox(height: MediaQuery.of(context).size.height * 0.2),
                                          Center(
                                            child: Text(
                                              'Chưa có bài học cho độ tuổi này (${activeChild?.age ?? 0} tuổi).',
                                              style: AppTextStyles.configCaption,
                                            ),
                                          ),
                                        ],
                                      )
                                    : isLandscape
                                    ? GridView.builder(
                                        physics: const AlwaysScrollableScrollPhysics(parent: BouncingScrollPhysics()),
                                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                                        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                                          crossAxisCount: 2,
                                          crossAxisSpacing: 14,
                                          mainAxisSpacing: 10,
                                          childAspectRatio: 2.5,
                                        ),
                                        itemCount: filteredTopics.length,
                                        itemBuilder: (context, index) {
                                          return _buildLandscapeTopicCard(filteredTopics[index]);
                                        },
                                      )
                                    : ListView.builder(
                                        physics: const AlwaysScrollableScrollPhysics(parent: BouncingScrollPhysics()),
                                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                                        itemCount: filteredTopics.length,
                                        itemBuilder: (context, index) {
                                          return _buildTopicCard(filteredTopics[index]);
                                        },
                                      ),
                              ),
                            ),
                          ],
                        ),
                      );
                    },
                  ),
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

  Widget _buildCategoryTab(String title, String categoryKey, IconData icon) {
    final isSelected = _selectedCategory == categoryKey;
    return InkWell(
      onTap: () {
        HapticFeedback.lightImpact();
        setState(() => _selectedCategory = categoryKey);
      },
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.primary : Colors.white,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: isSelected ? AppColors.primary : AppColors.border),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 15, color: isSelected ? Colors.white : AppColors.textLight),
            const SizedBox(width: 6),
            Text(
              title,
              style: AppTextStyles.configLabel.copyWith(
                fontSize: 12,
                fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
                color: isSelected ? Colors.white : AppColors.textBody,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTopicCard(Topic topic) {
    final cardColor = _getCategoryColor(topic.category);
    final iconEmoji = _getCategoryEmoji(topic.category);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: const BorderRadius.horizontal(left: Radius.circular(13)),
            child: SizedBox(
              width: 86,
              height: 86,
              child: (topic.thumbnailPath != null && topic.thumbnailPath!.trim().isNotEmpty)
                  ? ImageHelper.buildSafeImage(
                      topic.thumbnailPath,
                      width: 86,
                      height: 86,
                      fit: BoxFit.cover,
                      fallback: Container(
                        color: cardColor.withValues(alpha: 0.2),
                        child: Center(child: Text(iconEmoji, style: const TextStyle(fontSize: 42))),
                      ),
                    )
                  : Container(
                      color: cardColor.withValues(alpha: 0.2),
                      child: Center(child: Text(iconEmoji, style: const TextStyle(fontSize: 42))),
                    ),
            ),
          ),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 14.0, vertical: 10.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(topic.titleVi, style: AppTextStyles.kidTitle.copyWith(fontSize: 15)),
                  Text(topic.titleEn, style: AppTextStyles.configCaption.copyWith(fontSize: 12)),
                  const SizedBox(height: 6),
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: AppColors.success.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          'Offline',
                          style: AppTextStyles.configCaption.copyWith(
                            fontSize: 10.5,
                            fontWeight: FontWeight.w600,
                            color: AppColors.success,
                          ),
                        ),
                      ),
                      const SizedBox(width: 6),
                      Text(
                        '${topic.targetAgeMin}-${topic.targetAgeMax} tuổi',
                        style: AppTextStyles.configCaption.copyWith(fontSize: 11),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.only(right: 14.0),
            child: ElevatedButton(
              onPressed: () async {
                HapticFeedback.lightImpact();
                await Navigator.of(context).push(MaterialPageRoute(builder: (_) => FlashcardLearningScreen(topic: topic)));
                if (mounted) _loadTopics();
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                elevation: 0,
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                minimumSize: const Size(60, 32),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
              child: Text('Vào Học', style: AppTextStyles.configButton.copyWith(fontSize: 12)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLandscapeTopicCard(Topic topic) {
    final cardColor = _getCategoryColor(topic.category);
    final iconEmoji = _getCategoryEmoji(topic.category);

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: const BorderRadius.horizontal(left: Radius.circular(13)),
            child: SizedBox(
              width: 80,
              height: double.infinity,
              child: (topic.thumbnailPath != null && topic.thumbnailPath!.trim().isNotEmpty)
                  ? ImageHelper.buildSafeImage(
                      topic.thumbnailPath,
                      width: 80,
                      fit: BoxFit.cover,
                      fallback: Container(
                        color: cardColor.withValues(alpha: 0.2),
                        child: Center(child: Text(iconEmoji, style: const TextStyle(fontSize: 38))),
                      ),
                    )
                  : Container(
                      color: cardColor.withValues(alpha: 0.2),
                      child: Center(child: Text(iconEmoji, style: const TextStyle(fontSize: 38))),
                    ),
            ),
          ),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12.0, vertical: 8.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    topic.titleVi,
                    style: AppTextStyles.kidTitle.copyWith(fontSize: 14),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  Text(
                    topic.titleEn,
                    style: AppTextStyles.configCaption.copyWith(fontSize: 11.5),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                        decoration: BoxDecoration(
                          color: AppColors.success.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text(
                          'Offline',
                          style: AppTextStyles.configCaption.copyWith(
                            fontSize: 10,
                            fontWeight: FontWeight.w600,
                            color: AppColors.success,
                          ),
                        ),
                      ),
                      const SizedBox(width: 6),
                      Text(
                        '${topic.targetAgeMin}-${topic.targetAgeMax} tuổi',
                        style: AppTextStyles.configCaption.copyWith(fontSize: 10.5),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.only(right: 12.0),
            child: ElevatedButton(
              onPressed: () async {
                HapticFeedback.lightImpact();
                await Navigator.of(context).push(MaterialPageRoute(builder: (_) => FlashcardLearningScreen(topic: topic)));
                if (mounted) _loadTopics();
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                elevation: 0,
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                minimumSize: const Size(60, 30),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
              child: Text('Vào Học', style: AppTextStyles.configButton.copyWith(fontSize: 11.5)),
            ),
          ),
        ],
      ),
    );
  }
}
