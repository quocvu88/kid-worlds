import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:youtube_player_flutter/youtube_player_flutter.dart';

import '../../core/app_info.dart';
import '../../core/services/audio_service.dart';
import '../../core/services/screen_time_service.dart';
import '../../core/theme/app_theme.dart';
import '../../models/topic_item_model.dart';
import '../../widgets/animated_playful_background.dart';
import '../../widgets/screen_time_badge.dart';

class YoutubePlayerScreen extends StatefulWidget {
  final TopicItem item;

  const YoutubePlayerScreen({super.key, required this.item});

  @override
  State<YoutubePlayerScreen> createState() => _YoutubePlayerScreenState();
}

class _YoutubePlayerScreenState extends State<YoutubePlayerScreen> {
  late final YoutubePlayerController _controller;
  StreamSubscription<YoutubePlayerValue>? _playerSub;
  bool _isFullScreen = false;
  OverlayEntry? _exitFullscreenOverlay;

  bool _wasLocked = false;

  /// Dừng video ngay khi hết giờ chơi (màn khoá phủ lên trên nhưng video vẫn có thể phát tiếng).
  void _onScreenTimeChanged() {
    final locked = ScreenTimeService.instance.isLockedOut;
    if (locked && !_wasLocked) {
      if (_isFullScreen) _controller.exitFullScreen();
      _controller.pauseVideo();
    }
    _wasLocked = locked;
  }

  @override
  void initState() {
    super.initState();
    ScreenTimeService.instance.addListener(_onScreenTimeChanged);
    // Stop any ongoing audio/TTS before video plays
    AudioService.instance.stop();

    final videoId = widget.item.cleanYoutubeId ?? '4mNZK2H9v-o';

    _controller = YoutubePlayerController.fromVideoId(
      videoId: videoId,
      autoPlay: false,
      params: const YoutubePlayerParams(
        showControls: true,
        showFullscreenButton: true,
        mute: false,
        enableCaption: false,
        strictRelatedVideos: true,
      ),
    );

    _playerSub = _controller.stream.listen((value) {
      final fs = value.fullScreenOption.enabled;
      if (_isFullScreen != fs) {
        if (mounted) {
          setState(() {
            _isFullScreen = fs;
          });
          _handleFullscreenChange(fs);
        }
      }
    });
  }

  void _handleFullscreenChange(bool isFs) {
    if (isFs) {
      _showExitFullscreenOverlay();
    } else {
      _removeExitFullscreenOverlay();
      SystemChrome.setPreferredOrientations(kAppOrientations);
    }
  }

  void _showExitFullscreenOverlay() {
    _removeExitFullscreenOverlay();
    _exitFullscreenOverlay = OverlayEntry(
      builder: (ctx) => Positioned(
        top: MediaQuery.paddingOf(ctx).top + 10,
        left: MediaQuery.paddingOf(ctx).left + 14,
        child: Material(
          color: Colors.transparent,
          child: InkWell(
            onTap: () {
              HapticFeedback.lightImpact();
              _controller.exitFullScreen();
            },
            borderRadius: BorderRadius.circular(24),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              decoration: BoxDecoration(
                color: Colors.black.withValues(alpha: 0.75),
                borderRadius: BorderRadius.circular(24),
                border: Border.all(color: Colors.white.withValues(alpha: 0.35), width: 1.5),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.5),
                    blurRadius: 8,
                    offset: const Offset(0, 3),
                  ),
                ],
              ),
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.fullscreen_exit_rounded, color: Colors.white, size: 22),
                  SizedBox(width: 6),
                  Text(
                    'Thu nhỏ',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 13,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );

    if (mounted) {
      Overlay.of(context).insert(_exitFullscreenOverlay!);
    }
  }

  void _removeExitFullscreenOverlay() {
    if (_exitFullscreenOverlay != null) {
      try {
        _exitFullscreenOverlay?.remove();
      } catch (_) {}
      _exitFullscreenOverlay = null;
    }
  }

  @override
  void dispose() {
    ScreenTimeService.instance.removeListener(_onScreenTimeChanged);
    _playerSub?.cancel();
    _removeExitFullscreenOverlay();
    _controller.close();
    // Trả về chế độ chỉ màn hình ngang khi thoát (player có thể đã đổi hướng lúc fullscreen)
    SystemChrome.setPreferredOrientations(kAppOrientations);
    super.dispose();
  }

  void _toggleFullScreen() {
    HapticFeedback.lightImpact();
    if (_isFullScreen) {
      _controller.exitFullScreen();
    } else {
      _controller.toggleFullScreen();
    }
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: !_isFullScreen,
      onPopInvokedWithResult: (didPop, result) {
        if (didPop) return;
        if (_isFullScreen) {
          _controller.exitFullScreen();
        } else {
          _removeExitFullscreenOverlay();
          Navigator.of(context).pop();
        }
      },
      child: AnimatedPlayfulBackground(
      child: Scaffold(
        backgroundColor: Colors.transparent,
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          surfaceTintColor: Colors.transparent,
          elevation: 0,
          scrolledUnderElevation: 0,
          leading: IconButton(
            icon: Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.9),
                shape: BoxShape.circle,
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.08),
                    blurRadius: 6,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: const Icon(Icons.arrow_back_rounded, color: AppColors.textDark, size: 20),
            ),
            onPressed: () {
              _removeExitFullscreenOverlay();
              if (_isFullScreen) {
                _controller.exitFullScreen();
              } else {
                Navigator.of(context).pop();
              }
            },
          ),
          title: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(widget.item.emoji, style: const TextStyle(fontSize: 22)),
              const SizedBox(width: 8),
              Flexible(
                child: Text(
                  '${widget.item.nameEn} • ${widget.item.nameVi}',
                  style: AppTextStyles.kidTitle.copyWith(fontSize: 17),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          actions: const [
            ScreenTimeBadge(),
            SizedBox(width: 14),
          ],
        ),
        body: OrientationBuilder(
          builder: (context, orientation) {
            final isLandscape = orientation == Orientation.landscape;

            if (isLandscape) {
              return Row(
                children: [
                  // Left side: Big Video Player
                  Expanded(
                    flex: 6,
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(16, 8, 8, 16),
                      child: _buildPlayerContainer(isLandscape: true),
                    ),
                  ),
                  // Right side: Controls & Info
                  Expanded(
                    flex: 4,
                    child: SingleChildScrollView(
                      padding: const EdgeInsets.fromLTRB(8, 8, 16, 16),
                      child: _buildInfoCard(isLandscape: true),
                    ),
                  ),
                ],
              );
            }

            // Portrait layout
            return SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _buildPlayerContainer(isLandscape: false),
                  const SizedBox(height: 16),
                  _buildInfoCard(isLandscape: false),
                ],
              ),
            );
          },
        ),
      ),
    ),
  );
}

  Widget _buildPlayerContainer({required bool isLandscape}) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.black,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.15),
            blurRadius: 14,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: YoutubePlayer(
        controller: _controller,
        aspectRatio: 16 / 9,
        autoFullScreen: false,
        enableFullScreenOnVerticalDrag: true,
      ),
    );
  }

  Widget _buildInfoCard({required bool isLandscape}) {
    return Container(
      padding: EdgeInsets.all(isLandscape ? 14 : 18),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.95),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.border, width: 1.2),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Zoom Fullscreen Button
          ElevatedButton.icon(
            onPressed: _toggleFullScreen,
            icon: Icon(_isFullScreen ? Icons.fullscreen_exit_rounded : Icons.fullscreen_rounded, size: 24),
            label: Text(
              _isFullScreen ? 'Thu nhỏ video' : 'Phóng to toàn màn hình',
              style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
            ),
            style: ElevatedButton.styleFrom(
              backgroundColor: _isFullScreen ? const Color(0xFF475569) : const Color(0xFFEF4444),
              foregroundColor: Colors.white,
              elevation: 2,
              padding: const EdgeInsets.symmetric(vertical: 12),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            ),
          ),
          const SizedBox(height: 10),

          // Quick tip for kids
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(
              color: const Color(0xFFFEF3C7),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Row(
              children: [
                const Text('💡', style: TextStyle(fontSize: 16)),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    'Bé có thể xoay ngang máy hoặc vuốt lên để xem toàn màn hình nhé!',
                    style: AppTextStyles.configCaption.copyWith(
                      color: const Color(0xFFB45309),
                      fontSize: 11.5,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),

          // Card info summary
          Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.1),
                  shape: BoxShape.circle,
                ),
                alignment: Alignment.center,
                child: Text(widget.item.emoji, style: const TextStyle(fontSize: 24)),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      widget.item.nameEn,
                      style: AppTextStyles.kidTitle.copyWith(fontSize: 17, color: AppColors.secondary),
                    ),
                    Text(
                      widget.item.nameVi,
                      style: AppTextStyles.kidSubTitle.copyWith(fontSize: 14, color: AppColors.accent),
                    ),
                  ],
                ),
              ),
            ],
          ),
          if (widget.item.descriptionVi != null && widget.item.descriptionVi!.isNotEmpty) ...[
            const SizedBox(height: 10),
            Text(
              widget.item.descriptionVi!,
              style: AppTextStyles.configValue.copyWith(fontSize: 13, color: AppColors.textDark),
            ),
          ],
        ],
      ),
    );
  }
}
