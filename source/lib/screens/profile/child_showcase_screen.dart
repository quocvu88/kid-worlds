import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../core/database/database_helper.dart';
import '../../core/services/artwork_service.dart';
import '../../core/services/audio_service.dart';
import '../../core/theme/app_theme.dart';
import '../../models/artwork_model.dart';
import '../../models/child_model.dart';
import '../../widgets/parent_gate_dialog.dart';
import '../learning/games/coloring_game_screen.dart';

/// Hồ sơ mở rộng của bé: tab "Góc Sáng Tạo" (tranh đã tô) và "Thành Tích" (số liệu + huy hiệu).
class ChildShowcaseScreen extends StatefulWidget {
  final Child child;
  final int initialTab; // 0 = Góc Sáng Tạo, 1 = Thành Tích

  const ChildShowcaseScreen({super.key, required this.child, this.initialTab = 0});

  @override
  State<ChildShowcaseScreen> createState() => _ChildShowcaseScreenState();
}

class _ChildShowcaseScreenState extends State<ChildShowcaseScreen> {
  List<Artwork> _artworks = [];
  Map<String, int> _counts = {};
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final artworks = await DatabaseHelper.instance.getArtworks(widget.child.id);
    final counts = await DatabaseHelper.instance.getAchievementCounts(widget.child.id);
    if (!mounted) return;
    setState(() {
      _artworks = artworks;
      _counts = counts;
      _loading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 2,
      initialIndex: widget.initialTab,
      child: Scaffold(
        backgroundColor: AppColors.background,
        body: SafeArea(
          child: Column(
            children: [
              _buildHeader(),
              Expanded(
                child: _loading
                    ? const Center(child: CircularProgressIndicator())
                    : TabBarView(
                        children: [
                          _ArtworkGallery(artworks: _artworks, onChanged: _load),
                          _AchievementsView(counts: _counts),
                        ],
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHeader() {
    final child = widget.child;
    final avatar = (child.avatarUrl != null && child.avatarUrl!.isNotEmpty && !child.avatarUrl!.startsWith('assets/'))
        ? child.avatarUrl!
        : (child.gender == 'female' ? '👧' : '👦');

    return Padding(
      padding: const EdgeInsets.fromLTRB(8, 6, 16, 4),
      child: Row(
        children: [
          IconButton(
            icon: const Icon(Icons.arrow_back_rounded, size: 28, color: AppColors.textDark),
            tooltip: 'Quay lại',
            onPressed: () => Navigator.of(context).pop(),
          ),
          Text(avatar, style: const TextStyle(fontSize: 30)),
          const SizedBox(width: 8),
          Text(child.name, style: AppTextStyles.kidTitle.copyWith(fontSize: 20)),
          const SizedBox(width: 24),
          Expanded(
            child: Container(
              height: 48,
              padding: const EdgeInsets.all(4),
              decoration: BoxDecoration(
                color: AppColors.surfaceMuted,
                borderRadius: BorderRadius.circular(24),
              ),
              child: TabBar(
                onTap: (_) => HapticFeedback.selectionClick(),
                indicator: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: [
                    BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 6, offset: const Offset(0, 2)),
                  ],
                ),
                indicatorSize: TabBarIndicatorSize.tab,
                dividerColor: Colors.transparent,
                labelColor: AppColors.textDark,
                unselectedLabelColor: AppColors.textLight,
                labelStyle: AppTextStyles.kidTitle.copyWith(fontSize: 15),
                unselectedLabelStyle: AppTextStyles.kidTitle.copyWith(fontSize: 15),
                tabs: [
                  Tab(text: '🎨  Góc Sáng Tạo (${_artworks.length})'),
                  const Tab(text: '🏆  Thành Tích'),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

String _formatDate(String iso) {
  final d = DateTime.tryParse(iso);
  if (d == null) return '';
  String two(int v) => v.toString().padLeft(2, '0');
  return '${two(d.day)}/${two(d.month)}/${d.year}';
}

// =============================================================================
// GÓC SÁNG TẠO
// =============================================================================

class _ArtworkGallery extends StatelessWidget {
  final List<Artwork> artworks;
  final Future<void> Function() onChanged;

  const _ArtworkGallery({required this.artworks, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    if (artworks.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text('🖍️', style: TextStyle(fontSize: 64)),
            const SizedBox(height: 8),
            Text('Bé chưa lưu bức tranh nào', style: AppTextStyles.kidTitle.copyWith(fontSize: 18)),
            const SizedBox(height: 4),
            Text(
              'Vào trò Tô Màu, tô xong bấm ♥ để lưu tranh vào đây nhé!',
              style: AppTextStyles.configCaption,
            ),
          ],
        ),
      );
    }

    return GridView.builder(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 20),
      gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
        maxCrossAxisExtent: 240,
        mainAxisSpacing: 16,
        crossAxisSpacing: 16,
        childAspectRatio: 1.25,
      ),
      itemCount: artworks.length,
      itemBuilder: (context, i) {
        final art = artworks[i];
        return _ArtworkTile(artwork: art, onTap: () => _openViewer(context, art))
            .animate()
            .fadeIn(duration: 250.ms, delay: (40 * i.clamp(0, 10)).ms)
            .scale(begin: const Offset(0.92, 0.92), curve: Curves.easeOut);
      },
    );
  }

  Future<void> _openViewer(BuildContext context, Artwork art) async {
    HapticFeedback.lightImpact();
    final action = await showDialog<String>(
      context: context,
      builder: (ctx) => _ArtworkViewer(artwork: art),
    );
    if (!context.mounted || action == null) return;

    if (action == 'continue') {
      await _continueColoring(context, art);
    } else if (action == 'delete') {
      final ok = await ParentGateDialog.verify(context, title: 'Xoá tranh của bé');
      if (!ok) return;
      await ArtworkService.instance.delete(art);
    }
    await onChanged();
  }

  Future<void> _continueColoring(BuildContext context, Artwork art) async {
    final topicId = art.topicId;
    final topic = topicId == null ? null : await DatabaseHelper.instance.getTopic(topicId);
    if (!context.mounted) return;
    if (topic == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Gói chủ đề của tranh này đã bị gỡ nên chưa tô tiếp được.'),
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }
    final items = await DatabaseHelper.instance.getTopicItems(topic.id);
    if (!context.mounted) return;
    await Navigator.of(context).push(MaterialPageRoute(
      builder: (_) => ColoringGameScreen(topic: topic, items: items, resumeArtwork: art),
    ));
  }
}

class _ArtworkTile extends StatelessWidget {
  final Artwork artwork;
  final VoidCallback onTap;

  const _ArtworkTile({required this.artwork, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(20),
      clipBehavior: Clip.antiAlias,
      elevation: 2,
      shadowColor: Colors.black.withValues(alpha: 0.15),
      child: InkWell(
        onTap: onTap,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Expanded(
              child: Image.file(
                File(artwork.imagePath),
                fit: BoxFit.cover,
                cacheWidth: 480, // ảnh thu nhỏ cho lưới, đỡ tốn bộ nhớ
                errorBuilder: (_, _, _) => const ColoredBox(
                  color: AppColors.surfaceMuted,
                  child: Center(child: Text('🖼️', style: TextStyle(fontSize: 40))),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 6, 12, 8),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      artwork.title,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: AppTextStyles.kidTitle.copyWith(fontSize: 14),
                    ),
                  ),
                  Text(_formatDate(artwork.updatedAt), style: AppTextStyles.configCaption.copyWith(fontSize: 11)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Xem tranh cỡ lớn. Trả về 'continue' (tô tiếp) hoặc 'delete' (xoá).
class _ArtworkViewer extends StatelessWidget {
  final Artwork artwork;

  const _ArtworkViewer({required this.artwork});

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;
    return Dialog(
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.transparent,
      insetPadding: const EdgeInsets.all(16),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      child: ConstrainedBox(
        constraints: BoxConstraints(maxWidth: size.width * 0.9, maxHeight: size.height * 0.92),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Flexible(
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(16),
                  child: InteractiveViewer(
                    maxScale: 4,
                    child: Image.file(
                      File(artwork.imagePath),
                      fit: BoxFit.contain,
                      errorBuilder: (_, _, _) => const SizedBox(
                        width: 240,
                        height: 160,
                        child: Center(child: Text('🖼️', style: TextStyle(fontSize: 48))),
                      ),
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              SizedBox(
                width: 150,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Text(artwork.title, style: AppTextStyles.kidTitle.copyWith(fontSize: 18), maxLines: 2),
                    const SizedBox(height: 2),
                    Text(_formatDate(artwork.updatedAt), style: AppTextStyles.configCaption),
                    const SizedBox(height: 16),
                    ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        minimumSize: const Size.fromHeight(52),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                      ),
                      icon: const Icon(Icons.brush_rounded),
                      label: const Text('Tô tiếp', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                      onPressed: () => Navigator.of(context).pop('continue'),
                    ),
                    const SizedBox(height: 10),
                    OutlinedButton.icon(
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppColors.coralRed,
                        side: BorderSide(color: AppColors.coralRed.withValues(alpha: 0.5)),
                        minimumSize: const Size.fromHeight(48),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                      ),
                      icon: const Icon(Icons.delete_outline_rounded, size: 20),
                      label: const Text('Xoá'),
                      onPressed: () => Navigator.of(context).pop('delete'),
                    ),
                    const SizedBox(height: 10),
                    TextButton(
                      onPressed: () => Navigator.of(context).pop(),
                      child: const Text('Đóng'),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// =============================================================================
// THÀNH TÍCH
// =============================================================================

class _Badge {
  final String emoji;
  final String title;
  final String description;
  final String metric;
  final int target;
  final Color color;

  const _Badge(this.emoji, this.title, this.description, this.metric, this.target, this.color);
}

const _badges = <_Badge>[
  _Badge('👀', 'Bé Tò Mò', 'Học từ mới đầu tiên', 'words', 1, Color(0xFF38BDF8)),
  _Badge('📚', 'Nhà Thám Hiểm', 'Học 10 từ mới', 'words', 10, Color(0xFF38BDF8)),
  _Badge('🧠', 'Siêu Trí Nhớ', 'Học 50 từ mới', 'words', 50, Color(0xFF6366F1)),
  _Badge('🎓', 'Bậc Thầy Từ Vựng', 'Học 100 từ mới', 'words', 100, Color(0xFF8B5CF6)),
  _Badge('👂', 'Đôi Tai Thính', 'Nghe phát âm 30 lần', 'listens', 30, Color(0xFF14B8A6)),
  _Badge('🎬', 'Mọt Phim Nhí', 'Xem 5 video', 'videos', 5, Color(0xFFEF4444)),
  _Badge('🗺️', 'Chinh Phục Chủ Đề', 'Xem hết thẻ của 1 chủ đề', 'topics_done', 1, Color(0xFF10B981)),
  _Badge('🌍', 'Nhà Du Hành', 'Xem hết thẻ của 5 chủ đề', 'topics_done', 5, Color(0xFF059669)),
  _Badge('🎨', 'Hoạ Sĩ Nhí', 'Lưu bức tranh đầu tiên', 'artworks', 1, Color(0xFFFF5E7E)),
  _Badge('🖼️', 'Phòng Tranh Mini', 'Lưu 10 bức tranh', 'artworks', 10, Color(0xFFE04364)),
  _Badge('🃏', 'Lật Thẻ Tài Ba', 'Thắng trò Tìm Cặp Hình', 'memory_wins', 1, Color(0xFFF59E0B)),
  _Badge('🏅', 'Vua Lật Thẻ', 'Thắng Tìm Cặp Hình 10 lần', 'memory_wins', 10, Color(0xFFD97706)),
  _Badge('🔥', 'Chăm Chỉ', 'Học 3 ngày liên tiếp', 'best_streak', 3, Color(0xFFF97316)),
  _Badge('⭐', 'Siêu Sao Tuần', 'Học 7 ngày liên tiếp', 'best_streak', 7, Color(0xFFFBBF24)),
  _Badge('📅', 'Bền Bỉ', 'Học tổng cộng 30 ngày', 'active_days', 30, Color(0xFF0EA5E9)),
];

class _AchievementsView extends StatelessWidget {
  final Map<String, int> counts;

  const _AchievementsView({required this.counts});

  int _value(String key) => counts[key] ?? 0;

  @override
  Widget build(BuildContext context) {
    final earned = _badges.where((b) => _value(b.metric) >= b.target).length;

    final stats = <(String, String, int, Color)>[
      ('📖', 'Từ đã học', _value('words'), const Color(0xFF38BDF8)),
      ('👂', 'Lượt nghe', _value('listens'), const Color(0xFF14B8A6)),
      ('🎬', 'Video đã xem', _value('videos'), const Color(0xFFEF4444)),
      ('🗺️', 'Chủ đề xong', _value('topics_done'), const Color(0xFF10B981)),
      ('🎨', 'Tranh đã lưu', _value('artworks'), const Color(0xFFFF5E7E)),
      ('🃏', 'Thắng lật thẻ', _value('memory_wins'), const Color(0xFFF59E0B)),
      ('📅', 'Ngày đã học', _value('active_days'), const Color(0xFF0EA5E9)),
      ('🔥', 'Chuỗi hiện tại', _value('streak'), const Color(0xFFF97316)),
    ];

    return SingleChildScrollView(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          LayoutBuilder(
            builder: (context, c) {
              // 4 ô/hàng trên màn ngang
              final w = (c.maxWidth - 3 * 12) / 4;
              return Wrap(
                spacing: 12,
                runSpacing: 12,
                children: [for (final s in stats) SizedBox(width: w, child: _StatTile(s.$1, s.$2, s.$3, s.$4))],
              );
            },
          ),
          const SizedBox(height: 20),
          Row(
            children: [
              Text('Huy hiệu', style: AppTextStyles.kidTitle.copyWith(fontSize: 18)),
              const SizedBox(width: 10),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                decoration: BoxDecoration(
                  color: AppColors.accent.withValues(alpha: 0.18),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  '$earned/${_badges.length}',
                  style: AppTextStyles.configValue.copyWith(color: const Color(0xFFB45309), fontSize: 13),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
              maxCrossAxisExtent: 170,
              mainAxisSpacing: 12,
              crossAxisSpacing: 12,
              mainAxisExtent: 150,
            ),
            itemCount: _badges.length,
            itemBuilder: (context, i) {
              final b = _badges[i];
              return _BadgeTile(badge: b, value: _value(b.metric));
            },
          ),
        ],
      ),
    );
  }
}

class _StatTile extends StatelessWidget {
  final String emoji;
  final String label;
  final int value;
  final Color color;

  const _StatTile(this.emoji, this.label, this.value, this.color);

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withValues(alpha: 0.25)),
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            alignment: Alignment.center,
            decoration: BoxDecoration(color: color.withValues(alpha: 0.14), shape: BoxShape.circle),
            child: Text(emoji, style: const TextStyle(fontSize: 20)),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('$value', style: AppTextStyles.kidTitle.copyWith(fontSize: 20, color: AppColors.textDark)),
                Text(
                  label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: AppTextStyles.configCaption.copyWith(fontSize: 11.5),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _BadgeTile extends StatelessWidget {
  final _Badge badge;
  final int value;

  const _BadgeTile({required this.badge, required this.value});

  @override
  Widget build(BuildContext context) {
    final unlocked = value >= badge.target;
    final progress = (value / badge.target).clamp(0.0, 1.0);

    return Material(
      color: unlocked ? badge.color.withValues(alpha: 0.12) : Colors.white,
      borderRadius: BorderRadius.circular(22),
      child: InkWell(
        borderRadius: BorderRadius.circular(22),
        onTap: () {
          HapticFeedback.lightImpact();
          AudioService.instance.speakVietnamese(
            unlocked ? 'Huy hiệu ${badge.title}! ${badge.description}.' : '${badge.description} để nhận huy hiệu ${badge.title} nhé!',
          );
        },
        child: Container(
          padding: const EdgeInsets.fromLTRB(10, 12, 10, 10),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(22),
            border: Border.all(
              color: unlocked ? badge.color.withValues(alpha: 0.6) : AppColors.border,
              width: unlocked ? 2 : 1,
            ),
          ),
          child: Column(
            children: [
              Opacity(
                opacity: unlocked ? 1 : 0.3,
                child: Text(badge.emoji, style: const TextStyle(fontSize: 36)),
              ),
              const SizedBox(height: 4),
              Text(
                badge.title,
                textAlign: TextAlign.center,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: AppTextStyles.kidTitle.copyWith(
                  fontSize: 13.5,
                  color: unlocked ? AppColors.textDark : AppColors.textLight,
                ),
              ),
              Text(
                badge.description,
                textAlign: TextAlign.center,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: AppTextStyles.configCaption.copyWith(fontSize: 10.5),
              ),
              const Spacer(),
              if (unlocked)
                Text('Đã đạt ✓', style: AppTextStyles.configValue.copyWith(fontSize: 11.5, color: badge.color))
              else ...[
                ClipRRect(
                  borderRadius: BorderRadius.circular(4),
                  child: LinearProgressIndicator(
                    value: progress,
                    minHeight: 6,
                    backgroundColor: AppColors.surfaceMuted,
                    valueColor: AlwaysStoppedAnimation(badge.color),
                  ),
                ),
                const SizedBox(height: 2),
                Text('${value.clamp(0, badge.target)}/${badge.target}', style: AppTextStyles.configCaption.copyWith(fontSize: 10.5)),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
