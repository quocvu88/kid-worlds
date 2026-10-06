import 'dart:math' as math;
import 'dart:ui';

import 'package:confetti/confetti.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:uuid/uuid.dart';

import '../../core/database/database_helper.dart';
import '../../core/services/audio_service.dart';
import '../../core/services/screen_time_service.dart';
import '../../core/services/topic_store_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/image_helper.dart';
import '../../models/activity_log_model.dart';
import '../../models/topic_item_model.dart';
import '../../models/topic_model.dart';
import '../lockout/lockout_screen.dart';
import 'games/topic_games_hub_dialog.dart';
import 'youtube_player_screen.dart';

class FlashcardLearningScreen extends StatefulWidget {
  final Topic topic;

  const FlashcardLearningScreen({super.key, required this.topic});

  @override
  State<FlashcardLearningScreen> createState() => _FlashcardLearningScreenState();
}

class _FlashcardLearningScreenState extends State<FlashcardLearningScreen> {
  late PageController _pageController;
  late ConfettiController _confettiController;

  Topic? _currentTopic;
  Topic get _topic => _currentTopic ?? widget.topic;

  List<TopicItem> _items = [];
  bool _isLoading = true;
  int _currentIndex = 0;
  bool _showRealImage = false;
  bool _hasShownCompletionModal = false;
  bool _isSyncing = false;

  @override
  void initState() {
    super.initState();
    _currentTopic = widget.topic;
    _pageController = PageController();
    _confettiController = ConfettiController(duration: const Duration(seconds: 3));
    _loadItems();
  }

  @override
  void dispose() {
    _pageController.dispose();
    _confettiController.dispose();
    AudioService.instance.stop();
    super.dispose();
  }

  Future<void> _loadItems() async {
    final freshTopic = await DatabaseHelper.instance.getTopic(widget.topic.id);
    final items = await DatabaseHelper.instance.getTopicItems(widget.topic.id);
    if (!mounted) return;

    setState(() {
      if (freshTopic != null) _currentTopic = freshTopic;
      _items = items;
      _isLoading = false;
    });

    if (items.isEmpty) {
      // Auto attempt to fetch from CMS if local items are empty
      _syncFromCms(silent: true);
    } else {
      _logItemAction(items.first, 'view_card');
      // Speak English by default when card opens
      AudioService.instance.speakEnglish(items.first.nameEn);
    }
  }

  Future<void> _syncFromCms({bool silent = false}) async {
    if (_isSyncing) return;
    setState(() => _isSyncing = true);
    if (!silent) {
      HapticFeedback.lightImpact();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Đang tải nội dung và hình ảnh mới nhất từ CMS...'),
          duration: Duration(seconds: 1),
        ),
      );
    }

    final success = await TopicStoreService.instance.downloadAndInstallPack(widget.topic.id);
    if (!mounted) return;

    if (success) {
      final freshTopic = await DatabaseHelper.instance.getTopic(widget.topic.id);
      final freshItems = await DatabaseHelper.instance.getTopicItems(widget.topic.id);
      if (mounted) {
        setState(() {
          if (freshTopic != null) _currentTopic = freshTopic;
          _items = freshItems;
          _isLoading = false;
        });
        if (!silent) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Đã cập nhật bộ nội dung & hình ảnh mới nhất từ CMS!'),
              backgroundColor: Colors.green,
              duration: Duration(seconds: 2),
            ),
          );
        }
      }
    } else if (!silent) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Không thể đồng bộ từ CMS. Hãy kiểm tra kết nối mạng/máy chủ!'),
          backgroundColor: Colors.red,
          duration: Duration(seconds: 2),
        ),
      );
    }
    if (mounted) {
      setState(() => _isSyncing = false);
    }
  }

  void _logItemAction(TopicItem item, String actionType) {
    final currentChild = ScreenTimeService.instance.currentChild;
    if (currentChild == null) return;

    final log = ActivityLog(
      id: const Uuid().v4(),
      childId: currentChild.id,
      itemId: item.id,
      topicId: widget.topic.id,
      actionType: actionType,
      durationSeconds: 5,
      timestamp: DateTime.now().toIso8601String(),
    );
    DatabaseHelper.instance.insertActivityLog(log);
  }

  void _onPageChanged(int index) {
    HapticFeedback.selectionClick();
    setState(() {
      _currentIndex = index;
      _showRealImage = false;
    });

    if (index < _items.length) {
      final currentItem = _items[index];
      _logItemAction(currentItem, 'view_card');
      AudioService.instance.speakEnglish(currentItem.nameEn);
    }

    // Trigger celebration when reaching last card
    if (index == _items.length - 1 && _items.length > 1 && !_hasShownCompletionModal) {
      _hasShownCompletionModal = true;
      _confettiController.play();
      Future.delayed(const Duration(milliseconds: 600), () {
        if (mounted) _showMiniGameChallenge();
      });
    }
  }

  void _speakEnglish(TopicItem item) {
    HapticFeedback.lightImpact();
    _logItemAction(item, 'listen_pronounce');
    AudioService.instance.speakEnglish(item.nameEn);
  }

  void _speakVietnamese(TopicItem item) {
    HapticFeedback.lightImpact();
    _logItemAction(item, 'listen_pronounce');
    AudioService.instance.speakVietnamese(item.nameVi);
  }

  void _playSfx(TopicItem item) {
    HapticFeedback.mediumImpact();
    _logItemAction(item, 'play_sfx');
    if (item.hasSfx) {
      AudioService.instance.playSfx(item.sfxSound!);
    } else {
      AudioService.instance.speakVietnamese(item.nameVi);
    }
  }

  void _speakPhonics(TopicItem item) {
    HapticFeedback.lightImpact();
    _logItemAction(item, 'listen_phonics');
    if (item.hasPhonics) {
      AudioService.instance.speakPhonics(item.phonicsEn!);
    } else {
      AudioService.instance.speakEnglish(item.nameEn);
    }
  }

  void _speakDescriptionEn(TopicItem item) {
    HapticFeedback.lightImpact();
    if (item.descriptionEn != null && item.descriptionEn!.isNotEmpty) {
      AudioService.instance.speakEnglish(item.descriptionEn!);
    }
  }

  void _speakDescriptionVi(TopicItem item) {
    HapticFeedback.lightImpact();
    if (item.descriptionVi != null && item.descriptionVi!.isNotEmpty) {
      AudioService.instance.speakVietnamese(item.descriptionVi!);
    }
  }

  void _openYoutubePlayer(TopicItem item) {
    HapticFeedback.lightImpact();
    AudioService.instance.stop();
    _logItemAction(item, 'watch_youtube');
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (context) => YoutubePlayerScreen(item: item),
      ),
    );
  }

  void _showMiniGameChallenge() {
    if (_items.isEmpty) return;
    _confettiController.play();
    HapticFeedback.mediumImpact();
    AudioService.instance.speakVietnamese('Bé thật xuất sắc! Cùng chơi đố vui nhận Huy hiệu nhé!');

    final targetItem = _items[math.Random().nextInt(_items.length)];
    final candidateList = List<TopicItem>.from(_items)..shuffle();
    final options = <TopicItem>[targetItem];
    for (final it in candidateList) {
      if (options.length >= 3) break;
      if (it.id != targetItem.id) {
        options.add(it);
      }
    }
    options.shuffle();

    showDialog(
      context: context,
      barrierColor: Colors.black.withValues(alpha: 0.70),
      barrierDismissible: true,
      builder: (dialogCtx) {
        bool? isCorrect;
        final isLandscape = MediaQuery.of(dialogCtx).orientation == Orientation.landscape;
        return StatefulBuilder(
          builder: (ctx, setDialogState) {
            return BackdropFilter(
              filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
              child: Dialog(
                backgroundColor: Colors.transparent,
                insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                child: ConstrainedBox(
                  constraints: BoxConstraints(
                    maxWidth: isLandscape ? 440 : 380,
                    maxHeight: MediaQuery.of(ctx).size.height * 0.88,
                  ),
                  child: Container(
                    clipBehavior: Clip.antiAlias,
                    decoration: BoxDecoration(
                      color: const Color(0xFF1E293B).withValues(alpha: 0.70),
                      borderRadius: BorderRadius.circular(28),
                      border: Border.all(
                        color: const Color(0xFFFFD700).withValues(alpha: 0.65),
                        width: 2,
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.5),
                          blurRadius: 24,
                          offset: const Offset(0, 8),
                        ),
                        BoxShadow(
                          color: const Color(0xFFFFD700).withValues(alpha: 0.2),
                          blurRadius: 16,
                        ),
                      ],
                    ),
                    child: SingleChildScrollView(
                      physics: const BouncingScrollPhysics(),
                      padding: EdgeInsets.symmetric(
                        horizontal: 20,
                        vertical: isLandscape ? 16 : 22,
                      ),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Row(
                                children: [
                                  const Text('🏆', style: TextStyle(fontSize: 28)),
                                  const SizedBox(width: 8),
                                  Text(
                                    'Thử Thách Thám Hiểm',
                                    style: AppTextStyles.kidTitle.copyWith(
                                      fontSize: 18,
                                      color: const Color(0xFFFBBF24),
                                    ),
                                  ),
                                ],
                              ),
                              IconButton(
                                icon: const Icon(Icons.close_rounded, color: Colors.white70),
                                onPressed: () => Navigator.of(ctx).pop(),
                              ),
                            ],
                          ),
                          const SizedBox(height: 10),
                          const Text(
                            'Đố bé đâu là:',
                            style: TextStyle(
                              fontSize: 14.5,
                              color: Colors.white70,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                          const SizedBox(height: 6),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 7),
                            decoration: BoxDecoration(
                              color: AppColors.accent.withValues(alpha: 0.25),
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: AppColors.accent.withValues(alpha: 0.4)),
                            ),
                            child: Text(
                              '${targetItem.nameVi} • ${targetItem.nameEn}',
                              style: AppTextStyles.kidTitle.copyWith(
                                fontSize: 19,
                                color: const Color(0xFFFDE047),
                              ),
                            ),
                          ),
                          const SizedBox(height: 16),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                            children: options.map((opt) {
                              final isThisCorrect = isCorrect == true && opt.id == targetItem.id;
                              final isThisWrong = isCorrect == false && opt.id != targetItem.id;
                              return InkWell(
                                onTap: () {
                                  HapticFeedback.heavyImpact();
                                  if (opt.id == targetItem.id) {
                                    setDialogState(() => isCorrect = true);
                                    _confettiController.play();
                                    AudioService.instance.speakVietnamese('Chính xác rồi! Bé thật thông minh!');
                                  } else {
                                    setDialogState(() => isCorrect = false);
                                    HapticFeedback.vibrate();
                                    AudioService.instance.speakVietnamese('Bé thử lại lần nữa nhé!');
                                  }
                                },
                                borderRadius: BorderRadius.circular(20),
                                child: AnimatedContainer(
                                  duration: const Duration(milliseconds: 250),
                                  width: 82,
                                  height: 82,
                                  decoration: BoxDecoration(
                                    color: isThisCorrect
                                        ? Colors.green.withValues(alpha: 0.25)
                                        : Colors.white.withValues(alpha: 0.12),
                                    borderRadius: BorderRadius.circular(20),
                                    border: Border.all(
                                      color: isThisCorrect
                                          ? const Color(0xFF22C55E)
                                          : isThisWrong
                                              ? Colors.redAccent.withValues(alpha: 0.6)
                                              : Colors.white.withValues(alpha: 0.25),
                                      width: isThisCorrect ? 3 : 1.5,
                                    ),
                                  ),
                                  alignment: Alignment.center,
                                  child: Text(opt.emoji, style: const TextStyle(fontSize: 40)),
                                ),
                              );
                            }).toList(),
                          ),
                          const SizedBox(height: 16),
                          if (isCorrect == true) ...[
                            Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: Colors.green.withValues(alpha: 0.2),
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(color: Colors.green.withValues(alpha: 0.4)),
                              ),
                              child: Row(
                                children: [
                                  const Text('🌟', style: TextStyle(fontSize: 22)),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      'Chúc mừng bé nhận Huy Hiệu Thám Hiểm ${widget.topic.titleVi}!',
                                      style: const TextStyle(
                                        fontWeight: FontWeight.bold,
                                        color: Color(0xFF4ADE80),
                                        fontSize: 13,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(height: 14),
                            Row(
                              children: [
                                Expanded(
                                  child: ElevatedButton(
                                    onPressed: () => Navigator.of(ctx).pop(),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: const Color(0xFF10B981),
                                      foregroundColor: Colors.white,
                                      elevation: 3,
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(16),
                                      ),
                                      padding: const EdgeInsets.symmetric(vertical: 12),
                                    ),
                                    child: const Text(
                                      'Nhận Huy Hiệu ⭐',
                                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ] else ...[
                            const Text(
                              'Chạm vào đúng hình để mở khóa Huy hiệu nhé!',
                              textAlign: TextAlign.center,
                              style: TextStyle(fontSize: 12.5, color: Colors.white70),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildThemedBackground() {
    final bg = _topic.effectiveBackground;
    final hasCustomBg = _topic.backgroundPath != null && _topic.backgroundPath!.trim().isNotEmpty;

    return Stack(
      fit: StackFit.expand,
      children: [
        ImageHelper.buildSafeImage(
          bg,
          fit: BoxFit.cover,
          fallback: Container(color: AppColors.background),
        ),
        Container(
          color: Colors.white.withValues(alpha: hasCustomBg ? 0.22 : 0.35),
        ),
      ],
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

        return Stack(
          fit: StackFit.expand,
          children: [
            // Themed Background for this Topic Pack
            _buildThemedBackground(),

            Scaffold(
              extendBodyBehindAppBar: true,
              backgroundColor: Colors.transparent,
              appBar: PreferredSize(
                preferredSize: const Size.fromHeight(40),
                child: SafeArea(
                  bottom: false,
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 2),
                    child: Row(
                      children: [
                        IconButton(
                          padding: EdgeInsets.zero,
                          constraints: const BoxConstraints(),
                          icon: Container(
                            padding: const EdgeInsets.all(7),
                            decoration: BoxDecoration(
                              color: Colors.black.withValues(alpha: 0.35),
                              shape: BoxShape.circle,
                              border: Border.all(color: Colors.white.withValues(alpha: 0.2)),
                            ),
                            child: const Icon(Icons.arrow_back_rounded, color: Colors.white, size: 18),
                          ),
                          onPressed: () => Navigator.of(context).pop(),
                        ),
                        Expanded(
                          child: Center(
                            child: Text(
                              _topic.titleVi,
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                color: Colors.white,
                                shadows: [
                                  Shadow(color: Colors.black87, blurRadius: 10, offset: Offset(0, 1)),
                                ],
                              ),
                            ),
                          ),
                        ),
                        if (_items.isNotEmpty)
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: Colors.black.withValues(alpha: 0.35),
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(color: Colors.white.withValues(alpha: 0.2)),
                            ),
                            child: Text(
                              '${_currentIndex + 1} / ${_items.length}',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                      ],
                    ),
                  ),
                ),
              ),
              body: Stack(
                fit: StackFit.expand,
                children: [
                  _isLoading
                      ? const Center(child: CircularProgressIndicator(color: Colors.white))
                      : _items.isEmpty
                          ? Center(
                              child: Text(
                                'Không tìm thấy thẻ học nào.',
                                style: AppTextStyles.configCaption.copyWith(color: Colors.white),
                              ),
                            )
                          : OrientationBuilder(
                              builder: (context, orientation) {
                                final isLandscape = orientation == Orientation.landscape;
                                return PageView.builder(
                                  controller: _pageController,
                                  physics: const BouncingScrollPhysics(),
                                  onPageChanged: _onPageChanged,
                                  itemCount: _items.length,
                                  itemBuilder: (context, index) {
                                    final item = _items[index];
                                    return _buildImmersiveItemPage(item, isLandscape);
                                  },
                                );
                              },
                            ),

                  // Confetti overlay on topic completion
                  Align(
                    alignment: Alignment.topCenter,
                    child: ConfettiWidget(
                      confettiController: _confettiController,
                      blastDirectionality: BlastDirectionality.explosive,
                      maxBlastForce: 20,
                      minBlastForce: 8,
                      emissionFrequency: 0.05,
                      numberOfParticles: 25,
                      gravity: 0.2,
                      colors: const [
                        Color(0xFFFF6584),
                        Color(0xFF38BDF8),
                        Color(0xFFF59E0B),
                        Color(0xFF10B981),
                        Color(0xFF8B5CF6),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _buildCircleActionButton({
    String? emoji,
    IconData? icon,
    Color iconColor = Colors.white,
    required String tooltip,
    required Color bgColor,
    required Color borderColor,
    required VoidCallback onTap,
    double size = 48,
  }) {
    return Tooltip(
      message: tooltip,
      child: InkWell(
        onTap: () {
          HapticFeedback.lightImpact();
          onTap();
        },
        borderRadius: BorderRadius.circular(size / 2),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 150),
          width: size,
          height: size,
          decoration: BoxDecoration(
            color: bgColor,
            shape: BoxShape.circle,
            border: Border.all(color: borderColor, width: 2),
            boxShadow: [
              BoxShadow(
                color: borderColor.withValues(alpha: 0.3),
                blurRadius: 8,
                offset: const Offset(0, 3),
              ),
            ],
          ),
          child: Center(
            child: emoji != null
                ? Text(emoji, style: TextStyle(fontSize: size * 0.46))
                : Icon(icon, color: iconColor, size: size * 0.5),
          ),
        ),
      ),
    );
  }

  // =========================================================================
  // BỐ CỤC FULL-SCREEN MỚI THEO YÊU CẦU:
  // - Hình ảnh Full màn hình (Edge-to-Edge)
  // - Tiêu đề Anh / Việt thu nhỏ còn 80%
  // - 3 icon phát âm Anh, Việt, âm thanh đặt ở góc dưới bên trái, dưới text
  // - Các icon còn lại canh ngay giữa màn hình theo chiều dọc, nằm về bên phải
  // =========================================================================

  Widget _buildImmersiveItemPage(TopicItem item, bool isLandscape) {
    final hasReal = item.hasRealImage;
    final realImg = item.effectiveRealImage;
    final primaryImg = item.primaryImage;
    final displayImg = (_showRealImage && realImg != null && realImg.isNotEmpty) ? realImg : primaryImg;

    return Stack(
      fit: StackFit.expand,
      children: [
        // -------------------------------------------------------------
        // 1. HÌNH ẢNH FULL MÀN HÌNH TRÀN VIỀN (EDGE-TO-EDGE)
        // -------------------------------------------------------------
        GestureDetector(
          onTap: () {
            HapticFeedback.lightImpact();
            if (hasReal) {
              setState(() => _showRealImage = !_showRealImage);
            } else {
              _playSfx(item);
            }
          },
          child: Stack(
            fit: StackFit.expand,
            children: [
              // Ảnh nền tràn viền toàn bộ màn hình
              if (displayImg.isNotEmpty)
                ImageHelper.buildSafeImage(
                  displayImg,
                  width: double.infinity,
                  height: double.infinity,
                  fit: BoxFit.cover,
                  fallback: Container(color: const Color(0xFF0F172A)),
                )
              else
                Container(
                  color: const Color(0xFF0F172A),
                  child: Center(child: Text(item.emoji, style: const TextStyle(fontSize: 96))),
                ),

              // Gradient bóng mờ trên cùng (giúp thanh AppBar sát cạnh trên luôn rõ)
              Positioned(
                top: 0,
                left: 0,
                right: 0,
                height: 80,
                child: Container(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topCenter,
                      end: Alignment.bottomCenter,
                      colors: [
                        Colors.black.withValues(alpha: 0.6),
                        Colors.transparent,
                      ],
                    ),
                  ),
                ),
              ),

              // Gradient bóng mờ góc dưới trái (giúp text và 3 icon âm thanh luôn nổi bật)
              Positioned(
                bottom: 0,
                left: 0,
                right: isLandscape ? 120 : 70,
                height: 220,
                child: Container(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.bottomLeft,
                      end: Alignment.topRight,
                      colors: [
                        Colors.black.withValues(alpha: 0.8),
                        Colors.black.withValues(alpha: 0.4),
                        Colors.transparent,
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),

        // -------------------------------------------------------------
        // 2. GÓC DƯỚI BÊN TRÁI: TIÊU ĐỀ (80%) & 3 ICON ÂM THANH NGAY DƯỚI
        // -------------------------------------------------------------
        Positioned(
          left: isLandscape ? 24 : 16,
          bottom: isLandscape ? 18 : 16,
          child: SafeArea(
            top: false,
            child: _buildBottomLeftContent(item, isLandscape),
          ),
        ),

        // -------------------------------------------------------------
        // 3. CÁC ICON CÒN LẠI: CANH NGAY GIỮA DỌC VÀ NẰM VỀ BÊN PHẢI
        // -------------------------------------------------------------
        Positioned(
          right: isLandscape ? 18 : 12,
          top: 0,
          bottom: 0,
          child: SafeArea(
            child: Center(
              child: _buildRightSideActionPanel(item, isLandscape),
            ),
          ),
        ),
      ],
    );
  }

  // Góc dưới bên trái: Tiêu đề thu nhỏ 80% + 3 icon phát âm Anh Việt, âm thanh ngay dưới text
  Widget _buildBottomLeftContent(TopicItem item, bool isLandscape) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        // Tiêu đề tiếng Anh (Thu nhỏ 80%: 25 / 21)
        Text(
          item.nameEn,
          style: TextStyle(
            fontSize: isLandscape ? 25 : 21,
            fontWeight: FontWeight.w900,
            color: Colors.white,
            letterSpacing: 0.4,
            shadows: const [
              Shadow(color: Colors.black87, blurRadius: 10, offset: Offset(0, 2)),
              Shadow(color: Colors.black54, blurRadius: 4, offset: Offset(0, 1)),
            ],
          ),
          textAlign: TextAlign.left,
        ),

        // Phonics (nếu có, thu nhỏ 80%: 10.5)
        if (item.hasPhonics) ...[
          const SizedBox(height: 4),
          InkWell(
            onTap: () => _speakPhonics(item),
            borderRadius: BorderRadius.circular(12),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: Colors.black.withValues(alpha: 0.45),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFC4B5FD).withValues(alpha: 0.6)),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.spellcheck_rounded, color: Color(0xFFDDD6FE), size: 12),
                  const SizedBox(width: 4),
                  Text(
                    item.phonicsEn!,
                    style: const TextStyle(
                      fontSize: 10.5,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFFDDD6FE),
                    ),
                  ),
                  const SizedBox(width: 4),
                  const Icon(Icons.volume_up_rounded, color: Color(0xFFDDD6FE), size: 12),
                ],
              ),
            ),
          ),
        ],

        const SizedBox(height: 3),

        // Tiêu đề tiếng Việt (Thu nhỏ 80%: 17.5 / 15)
        Text(
          item.nameVi,
          style: TextStyle(
            fontSize: isLandscape ? 17.5 : 15,
            fontWeight: FontWeight.bold,
            color: const Color(0xFFFDE047), // Màu vàng ấm áp
            shadows: const [
              Shadow(color: Colors.black87, blurRadius: 8, offset: Offset(0, 1)),
            ],
          ),
          textAlign: TextAlign.left,
        ),

        const SizedBox(height: 10),

        // 3 icon phát âm Anh Việt, âm thanh đặt ở góc dưới bên trái, ngay dưới text
        Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            // 🇺🇸 Phát âm tiếng Anh
            _buildCircleActionButton(
              emoji: '🇺🇸',
              tooltip: 'Phát âm tiếng Anh',
              bgColor: const Color(0xFFE0F2FE),
              borderColor: const Color(0xFF38BDF8),
              size: 42,
              onTap: () => _speakEnglish(item),
            ),
            const SizedBox(width: 10),

            // 🇻🇳 Phát âm tiếng Việt
            _buildCircleActionButton(
              emoji: '🇻🇳',
              tooltip: 'Phát âm tiếng Việt',
              bgColor: const Color(0xFFFEF3C7),
              borderColor: const Color(0xFFF59E0B),
              size: 42,
              onTap: () => _speakVietnamese(item),
            ),
            const SizedBox(width: 10),

            // 🔊 Âm thanh đặc trưng (SFX)
            _buildCircleActionButton(
              icon: Icons.volume_up_rounded,
              iconColor: Colors.white,
              tooltip: 'Âm thanh đặc trưng',
              bgColor: const Color(0xFF10B981),
              borderColor: const Color(0xFF059669),
              size: 42,
              onTap: () => _playSfx(item),
            ),
          ],
        ),
      ],
    );
  }

  // Các icon còn lại: Canh ngay giữa màn hình theo chiều dọc, nằm về bên phải của màn hình
  Widget _buildRightSideActionPanel(TopicItem item, bool isLandscape) {
    final hasReal = item.hasRealImage;
    final btnSize = isLandscape ? 46.0 : 42.0;
    const spacing = 10.0;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.black.withValues(alpha: 0.32),
        borderRadius: BorderRadius.circular(28),
        border: Border.all(color: Colors.white.withValues(alpha: 0.18)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // 🎮 Nút Game Hub của Bộ Sưu Tập (Mở các game của topic này)
          _buildCircleActionButton(
            emoji: '🎮',
            tooltip: 'Khu Vui Chơi Mini-Games',
            bgColor: const Color(0xFFF3E8FF),
            borderColor: const Color(0xFFA855F7),
            size: btnSize,
            onTap: () {
              TopicGamesHubDialog.show(
                context,
                topic: _topic,
                items: _items,
                currentItem: item,
              );
            },
          ),

          // ▶️ Nút YouTube (nếu có video)
          if (item.hasYoutubeVideo) ...[
            const SizedBox(height: spacing),
            _buildCircleActionButton(
              icon: Icons.play_arrow_rounded,
              iconColor: Colors.white,
              tooltip: 'Xem Video YouTube',
              bgColor: const Color(0xFFEE1D23),
              borderColor: const Color(0xFFCC181E),
              size: btnSize,
              onTap: () => _openYoutubePlayer(item),
            ),
          ],

          // 📷 / 🎨 Nút đổi ảnh thật / minh họa (nếu có ảnh thật)
          if (hasReal) ...[
            const SizedBox(height: spacing),
            _buildCircleActionButton(
              icon: _showRealImage ? Icons.brush_rounded : Icons.photo_camera_rounded,
              iconColor: Colors.white,
              tooltip: _showRealImage ? 'Xem hình vẽ 3D' : 'Xem ảnh đời thực',
              bgColor: _showRealImage ? const Color(0xFFF59E0B) : const Color(0xFF0284C7),
              borderColor: _showRealImage ? const Color(0xFFD97706) : const Color(0xFF0369A1),
              size: btnSize,
              onTap: () {
                setState(() => _showRealImage = !_showRealImage);
              },
            ),
          ],

          const SizedBox(height: spacing),
          // 📖 Nút Chi tiết (Mở Bottom Sheet cẩm nang hướng dẫn cho Cha Mẹ)
          _buildCircleActionButton(
            icon: Icons.menu_book_rounded,
            iconColor: Colors.white,
            tooltip: 'Chi tiết & Gợi ý cho Cha Mẹ',
            bgColor: const Color(0xFF8B5CF6),
            borderColor: const Color(0xFF7C3AED),
            size: btnSize,
            onTap: () => _showDetailBottomSheet(item),
          ),
        ],
      ),
    );
  }

  // Popup / Bottom Sheet hiển thị chi tiết phụ huynh & bé khi bấm nút Chi tiết
  // Tự động thích ứng: Landscape hiển thị Dialog 2 cột cân xứng trong khung hình, Portrait hiển thị Bottom Sheet
  void _showDetailBottomSheet(TopicItem item) {
    HapticFeedback.lightImpact();
    final isLandscape = MediaQuery.of(context).orientation == Orientation.landscape;

    if (isLandscape) {
      showDialog(
        context: context,
        barrierDismissible: true,
        builder: (ctx) => Dialog(
          backgroundColor: Colors.transparent,
          insetPadding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
          child: Container(
            constraints: BoxConstraints(
              maxWidth: 760,
              maxHeight: MediaQuery.of(ctx).size.height - 24,
            ),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(24),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.25),
                  blurRadius: 20,
                  offset: const Offset(0, 6),
                ),
              ],
            ),
            child: SafeArea(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  // Header
                  Padding(
                    padding: const EdgeInsets.fromLTRB(20, 10, 12, 8),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.menu_book_rounded, color: Color(0xFF7C3AED), size: 22),
                            const SizedBox(width: 8),
                            Text(
                              '${item.nameVi} • ${item.nameEn}',
                              style: const TextStyle(
                                fontSize: 16.5,
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF4C1D95),
                              ),
                            ),
                          ],
                        ),
                        IconButton(
                          icon: const Icon(Icons.close_rounded, color: AppColors.textDark),
                          onPressed: () => Navigator.of(ctx).pop(),
                        ),
                      ],
                    ),
                  ),
                  const Divider(height: 1),

                  // Nội dung 2 cột cân đối và cuộn mượt mà
                  Flexible(
                    child: SingleChildScrollView(
                      physics: const BouncingScrollPhysics(),
                      padding: const EdgeInsets.fromLTRB(20, 12, 20, 16),
                      child: _buildDetailContentResponsive(item, isLandscape: true),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      );
    } else {
      showModalBottomSheet(
        context: context,
        isScrollControlled: true,
        backgroundColor: Colors.transparent,
        builder: (ctx) => Container(
          constraints: BoxConstraints(
            maxHeight: MediaQuery.of(ctx).size.height * 0.82,
          ),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
            boxShadow: [
              BoxShadow(
                color: Colors.black26,
                blurRadius: 20,
                offset: Offset(0, -4),
              ),
            ],
          ),
          child: SafeArea(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Thanh kéo drag handle
                Center(
                  child: Container(
                    margin: const EdgeInsets.only(top: 10, bottom: 6),
                    width: 44,
                    height: 5,
                    decoration: BoxDecoration(
                      color: Colors.grey.shade300,
                      borderRadius: BorderRadius.circular(3),
                    ),
                  ),
                ),

                // Header
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.menu_book_rounded, color: Color(0xFF7C3AED), size: 22),
                          const SizedBox(width: 8),
                          Text(
                            '${item.nameVi} • ${item.nameEn}',
                            style: const TextStyle(
                              fontSize: 17,
                              fontWeight: FontWeight.bold,
                              color: Color(0xFF4C1D95),
                            ),
                          ),
                        ],
                      ),
                      IconButton(
                        icon: const Icon(Icons.close_rounded, color: AppColors.textDark),
                        onPressed: () => Navigator.of(ctx).pop(),
                      ),
                    ],
                  ),
                ),
                const Divider(height: 1),

                // Nội dung chi tiết cuộn mượt mà
                Flexible(
                  child: SingleChildScrollView(
                    physics: const BouncingScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(20, 14, 20, 24),
                    child: _buildDetailContentResponsive(item, isLandscape: false),
                  ),
                ),
              ],
            ),
          ),
        ),
      );
    }
  }

  // Nội dung responsive cho popup Chi Tiết: Chia 2 cột khi ở Landscape, cuộn dọc khi ở Portrait
  Widget _buildDetailContentResponsive(TopicItem item, {required bool isLandscape}) {
    final hasDescVi = item.descriptionVi != null && item.descriptionVi!.trim().isNotEmpty;
    final hasDescEn = item.descriptionEn != null && item.descriptionEn!.trim().isNotEmpty;
    final hasDesc = hasDescVi || hasDescEn;
    final hasFunFact = item.funFactVi != null && item.funFactVi!.isNotEmpty;
    final hasQuestion = item.promptQuestionVi != null && item.promptQuestionVi!.isNotEmpty;
    final hasAction = item.actionHintVi != null && item.actionHintVi!.isNotEmpty;

    if (!hasDesc && !hasFunFact && !hasQuestion && !hasAction) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 24),
        child: Center(
          child: Text(
            'Chưa có nội dung cẩm nang cho mục này.',
            style: TextStyle(color: AppColors.textLight, fontSize: 13),
          ),
        ),
      );
    }

    if (isLandscape) {
      return Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Cột trái: Mô tả chi tiết (nếu có) + 💡 Sự thật kỳ thú
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (hasDesc) ...[
                  _buildDetailedDescriptionCard(item),
                  if (hasFunFact) const SizedBox(height: 8),
                ],
                if (hasFunFact) _buildFunFactCard(item),
                if (!hasDesc && !hasFunFact && hasQuestion) _buildPromptQuestionCard(item),
              ],
            ),
          ),
          const SizedBox(width: 12),
          // Cột phải: ❓ Cha mẹ đố bé + 🤸 Cùng chơi tương tác
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (hasDesc || hasFunFact) ...[
                  if (hasQuestion) ...[
                    _buildPromptQuestionCard(item),
                    if (hasAction) const SizedBox(height: 8),
                  ],
                  if (hasAction) _buildActionHintCard(item),
                ] else ...[
                  if (hasAction) _buildActionHintCard(item),
                ],
              ],
            ),
          ),
        ],
      );
    }

    // Portrait: Dọc tuần tự
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (hasDesc) ...[
          _buildDetailedDescriptionCard(item),
          const SizedBox(height: 10),
        ],
        if (hasFunFact) ...[
          _buildFunFactCard(item),
          const SizedBox(height: 10),
        ],
        if (hasQuestion) ...[
          _buildPromptQuestionCard(item),
          const SizedBox(height: 10),
        ],
        if (hasAction) _buildActionHintCard(item),
      ],
    );
  }

  // Thẻ Mô tả chi tiết song ngữ giúp cha mẹ dạy bé
  Widget _buildDetailedDescriptionCard(TopicItem item) {
    final hasDescVi = item.descriptionVi != null && item.descriptionVi!.trim().isNotEmpty;
    final hasDescEn = item.descriptionEn != null && item.descriptionEn!.trim().isNotEmpty;
    if (!hasDescVi && !hasDescEn) return const SizedBox.shrink();

    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: const [
              Icon(Icons.auto_stories_rounded, size: 15, color: AppColors.primary),
              SizedBox(width: 6),
              Text(
                'Mô tả chi tiết',
                style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: AppColors.primary),
              ),
            ],
          ),
          const SizedBox(height: 6),
          if (hasDescEn)
            InkWell(
              onTap: () => _speakDescriptionEn(item),
              borderRadius: BorderRadius.circular(8),
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 2),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('🇺🇸', style: TextStyle(fontSize: 13)),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        item.descriptionEn!,
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF0284C7), height: 1.3),
                      ),
                    ),
                    const SizedBox(width: 4),
                    const Icon(Icons.volume_up_rounded, size: 15, color: Color(0xFF0284C7)),
                  ],
                ),
              ),
            ),
          if (hasDescEn && hasDescVi) const Divider(height: 8, color: Color(0xFFE2E8F0)),
          if (hasDescVi)
            InkWell(
              onTap: () => _speakDescriptionVi(item),
              borderRadius: BorderRadius.circular(8),
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 2),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('🇻🇳', style: TextStyle(fontSize: 13)),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        item.descriptionVi!,
                        style: const TextStyle(fontSize: 12, color: Color(0xFF334155), height: 1.35),
                      ),
                    ),
                    const SizedBox(width: 4),
                    const Icon(Icons.volume_up_rounded, size: 15, color: Color(0xFFB45309)),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }

  // 💡 Thẻ Sự thật kỳ thú
  Widget _buildFunFactCard(TopicItem item) {
    if (item.funFactVi == null || item.funFactVi!.isEmpty) return const SizedBox.shrink();
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: const Color(0xFFFEF3C7),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFFDE68A)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('💡', style: TextStyle(fontSize: 16)),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Sự thật kỳ thú',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11.5, color: Color(0xFF92400E)),
                ),
                const SizedBox(height: 2),
                Text(
                  item.funFactVi!,
                  style: const TextStyle(fontSize: 11.5, color: Color(0xFF78350F), height: 1.32),
                ),
              ],
            ),
          ),
          IconButton(
            visualDensity: VisualDensity.compact,
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(),
            icon: const Icon(Icons.volume_up_rounded, color: Color(0xFF92400E), size: 17),
            onPressed: () {
              HapticFeedback.lightImpact();
              AudioService.instance.speakVietnamese(item.funFactVi!);
            },
          ),
        ],
      ),
    );
  }

  // ❓ Thẻ Cha mẹ đố bé
  Widget _buildPromptQuestionCard(TopicItem item) {
    if (item.promptQuestionVi == null || item.promptQuestionVi!.isEmpty) return const SizedBox.shrink();
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: const Color(0xFFE0F2FE),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFBAE6FD)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('❓', style: TextStyle(fontSize: 16)),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Cha mẹ đố bé',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11.5, color: Color(0xFF0369A1)),
                ),
                const SizedBox(height: 2),
                Text(
                  item.promptQuestionVi!,
                  style: const TextStyle(fontSize: 11.5, color: Color(0xFF075985), height: 1.32),
                ),
              ],
            ),
          ),
          IconButton(
            visualDensity: VisualDensity.compact,
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(),
            icon: const Icon(Icons.volume_up_rounded, color: Color(0xFF0369A1), size: 17),
            onPressed: () {
              HapticFeedback.lightImpact();
              AudioService.instance.speakVietnamese(item.promptQuestionVi!);
            },
          ),
        ],
      ),
    );
  }

  // 🤸 Thẻ Cùng chơi tương tác
  Widget _buildActionHintCard(TopicItem item) {
    if (item.actionHintVi == null || item.actionHintVi!.isEmpty) return const SizedBox.shrink();
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: const Color(0xFFD1FAE5),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFA7F3D0)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('🤸', style: TextStyle(fontSize: 16)),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Cùng chơi tương tác',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11.5, color: Color(0xFF065F46)),
                ),
                const SizedBox(height: 2),
                Text(
                  item.actionHintVi!,
                  style: const TextStyle(fontSize: 11.5, color: Color(0xFF064E3B), height: 1.32),
                ),
              ],
            ),
          ),
          IconButton(
            visualDensity: VisualDensity.compact,
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(),
            icon: const Icon(Icons.volume_up_rounded, color: Color(0xFF065F46), size: 17),
            onPressed: () {
              HapticFeedback.lightImpact();
              AudioService.instance.speakVietnamese(item.actionHintVi!);
            },
          ),
        ],
      ),
    );
  }
}
