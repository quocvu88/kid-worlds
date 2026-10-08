import 'dart:math' as math;
import 'dart:ui';
import 'dart:ui' as ui;
import 'package:confetti/confetti.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/services/audio_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/image_helper.dart';
import '../../../models/topic_item_model.dart';
import '../../../models/topic_model.dart';

/// Một nét vẽ. Toạ độ điểm và độ dày được CHUẨN HOÁ theo cạnh khung vẽ (0..1)
/// để nét không bị lệch khi xoay màn hình / đổi kích thước.
class _Stroke {
  final Color color;
  final double width; // tỉ lệ so với chiều rộng khung vẽ
  final bool isEraser;
  final List<Offset> points = [];

  _Stroke({required this.color, required this.width, required this.isEraser});
}

class ColoringGameScreen extends StatefulWidget {
  final Topic topic;
  final List<TopicItem> items;
  final TopicItem? currentItem;

  const ColoringGameScreen({
    super.key,
    required this.topic,
    required this.items,
    this.currentItem,
  });

  @override
  State<ColoringGameScreen> createState() => _ColoringGameScreenState();
}

class _ColoringGameScreenState extends State<ColoringGameScreen> {
  late ConfettiController _confettiController;
  late TopicItem _activeItem;
  
  final List<_Stroke> _strokes = [];
  _Stroke? _currentStroke;

  /// Tăng giá trị để vẽ lại canvas mà không rebuild cả màn hình.
  final ValueNotifier<int> _repaint = ValueNotifier<int>(0);

  // Tranh nét (outline) được nạp thành ui.Image để vẽ chồng lên nét tô bằng BlendMode.multiply
  ui.Image? _outlineImage;
  ImageStream? _outlineStream;
  ImageStreamListener? _outlineListener;
  String _outlineKey = '';
  
  Color _selectedColor = const Color(0xFFFF3B30);
  double _strokeWidth = 14.0;
  bool _isEraser = false;

  final List<Color> _palette = const [
    Color(0xFFFF3B30), // Đỏ
    Color(0xFFFF9500), // Cam
    Color(0xFFFFCC00), // Vàng
    Color(0xFF34C759), // Xanh lá
    Color(0xFF00C7BE), // Xanh ngọc
    Color(0xFF007AFF), // Xanh dương
    Color(0xFF5856D6), // Tím chàm
    Color(0xFFAF52DE), // Tím
    Color(0xFFFF2D55), // Hồng cánh sen
    Color(0xFF8B5A2B), // Nâu
    Color(0xFF1F2937), // Đen xám
    Color(0xFFFFFFFF), // Trắng
  ];

  @override
  void initState() {
    super.initState();
    _confettiController = ConfettiController(duration: const Duration(seconds: 3));
    // Ẩn status bar + thanh điều hướng để dành tối đa chỗ cho khung vẽ (vuốt từ mép để hiện lại)
    SystemChrome.setEnabledSystemUIMode(SystemUiMode.immersiveSticky);
    _activeItem = widget.currentItem ?? (widget.items.isNotEmpty ? widget.items.first : _dummyItem());
    AudioService.instance.speakVietnameseAfterTransition('Bé hãy chọn màu yêu thích và tô tranh nhé!', isActive: () => mounted);
    _loadOutline();
  }

  bool get _hasOutline =>
      _activeItem.coloringOutlineUrl != null && _activeItem.coloringOutlineUrl!.trim().isNotEmpty;

  void _loadOutline() {
    final url = _hasOutline ? ImageHelper.resolveUrl(_activeItem.coloringOutlineUrl) : '';
    if (url == _outlineKey) return;
    _disposeOutlineStream();
    _outlineKey = url;
    _outlineImage = null;
    if (url.isEmpty) return;

    final ImageProvider provider = url.startsWith('assets/') ? AssetImage(url) : NetworkImage(url);
    final stream = provider.resolve(ImageConfiguration.empty);
    final listener = ImageStreamListener(
      (info, _) {
        if (!mounted || _outlineKey != url) return;
        setState(() => _outlineImage = info.image);
      },
      onError: (error, _) => debugPrint('Không tải được tranh nét $url: $error'),
    );
    stream.addListener(listener);
    _outlineStream = stream;
    _outlineListener = listener;
  }

  void _disposeOutlineStream() {
    if (_outlineStream != null && _outlineListener != null) {
      _outlineStream!.removeListener(_outlineListener!);
    }
    _outlineStream = null;
    _outlineListener = null;
  }

  void _selectItem(TopicItem it) {
    setState(() {
      _activeItem = it;
      _strokes.clear();
      _currentStroke = null;
    });
    _loadOutline();
    _repaint.value++;
  }

  TopicItem _dummyItem() {
    return TopicItem(
      id: 'item_default',
      topicId: widget.topic.id,
      nameVi: widget.topic.titleVi,
      nameEn: widget.topic.titleEn,
      imagesJson: '[]',
    );
  }

  @override
  void dispose() {
    _disposeOutlineStream();
    _repaint.dispose();
    _confettiController.dispose();
    AudioService.instance.stop(); // thoát game thì ngừng đọc
    SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
    super.dispose();
  }

  void _undo() {
    HapticFeedback.lightImpact();
    if (_strokes.isNotEmpty) {
      _strokes.removeLast();
      _repaint.value++;
    }
  }

  void _clearCanvas() {
    HapticFeedback.mediumImpact();
    _strokes.clear();
    _repaint.value++;
  }

  void _saveArtwork() {
    HapticFeedback.heavyImpact();
    _confettiController.play();
    AudioService.instance.speakVietnamese('Bé tô tranh thật xuất sắc! Bức tranh đã được lưu vào hồ sơ của bé!');
    
    showDialog(
      context: context,
      barrierColor: Colors.black.withValues(alpha: 0.70),
      builder: (ctx) {
        final isLandscape = MediaQuery.of(ctx).orientation == Orientation.landscape;
        return BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
          child: Dialog(
            backgroundColor: Colors.transparent,
            insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
            child: ConstrainedBox(
              constraints: BoxConstraints(
                maxWidth: isLandscape ? 440 : 360,
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
                      Text('🎉', style: TextStyle(fontSize: isLandscape ? 40 : 48)),
                      const SizedBox(height: 8),
                      Text(
                        'Bức Tranh Tuyệt Đẹp!',
                        textAlign: TextAlign.center,
                        style: AppTextStyles.kidTitle.copyWith(
                          fontSize: isLandscape ? 19 : 21,
                          color: const Color(0xFFFBBF24),
                          shadows: [
                            Shadow(
                              color: Colors.black.withValues(alpha: 0.6),
                              offset: const Offset(0, 2),
                              blurRadius: 4,
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'Tác phẩm của bé đã được lưu thành công vào Góc Sáng Tạo!',
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          fontSize: 13.5,
                          color: Colors.white70,
                          height: 1.4,
                        ),
                      ),
                      SizedBox(height: isLandscape ? 14 : 20),
                      Row(
                        children: [
                          Expanded(
                            child: ElevatedButton.icon(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF10B981),
                                foregroundColor: Colors.white,
                                elevation: 3,
                                shadowColor: const Color(0xFF10B981).withValues(alpha: 0.4),
                                padding: const EdgeInsets.symmetric(vertical: 12),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(16),
                                ),
                              ),
                              icon: const Icon(Icons.check_circle_rounded, size: 20),
                              label: const Text(
                                'Bé Rất Vui',
                                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                              ),
                              onPressed: () => Navigator.of(ctx).pop(),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: Stack(
        children: [
          SafeArea(
            child: Column(
              children: [
                // Top Header bar
                _buildTopBar(),

                // Main Workspace
                Expanded(
                  child: _buildLandscapeLayout(),
                ),
              ],
            ),
          ),

          // Confetti celebratory overlay
          Align(
            alignment: Alignment.topCenter,
            child: ConfettiWidget(
              confettiController: _confettiController,
              blastDirectionality: BlastDirectionality.explosive,
              maxBlastForce: 20,
              minBlastForce: 8,
              emissionFrequency: 0.05,
              numberOfParticles: 30,
              gravity: 0.2,
              colors: const [
                Color(0xFFFF3B30),
                Color(0xFFFFCC00),
                Color(0xFF34C759),
                Color(0xFF007AFF),
                Color(0xFFAF52DE),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // Thanh trên (44dp): quay lại | bảng màu (giữa) | đổi tranh + lưu (phải)
  Widget _buildTopBar() {
    return SizedBox(
      height: 44,
      child: Stack(
        alignment: Alignment.center,
        children: [
          Align(
            alignment: Alignment.centerLeft,
            child: IconButton(
              icon: const Icon(Icons.arrow_back_rounded, color: AppColors.textDark, size: 26),
              tooltip: 'Quay lại',
              onPressed: () => Navigator.of(context).pop(),
            ),
          ),

          // Nút bảng màu ở giữa: chấm giữa là màu đang chọn
          _PaletteButton(
            selectedColor: _selectedColor,
            isEraser: _isEraser,
            palette: _palette,
            onTap: _openColorPicker,
          ),

          Align(
            alignment: Alignment.centerRight,
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                if (widget.items.length > 1)
                  IconButton(
                    icon: const Icon(Icons.swap_horiz_rounded, color: AppColors.secondary, size: 28),
                    tooltip: 'Đổi tranh',
                    onPressed: _openItemPicker,
                  ),
                IconButton(
                  icon: const Icon(Icons.favorite_rounded, color: AppColors.primary, size: 26),
                  tooltip: 'Lưu tranh',
                  onPressed: _saveArtwork,
                ),
                const SizedBox(width: 4),
              ],
            ),
          ),
        ],
      ),
    );
  }

  /// Bảng chọn màu: các ô màu lớn (52dp) dễ bấm cho bé.
  void _openColorPicker() {
    HapticFeedback.lightImpact();
    showDialog(
      context: context,
      barrierColor: Colors.black.withValues(alpha: 0.35),
      builder: (ctx) => Dialog(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        insetPadding: const EdgeInsets.all(16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 440),
            child: Wrap(
              alignment: WrapAlignment.center,
              spacing: 14,
              runSpacing: 14,
              children: _palette.map((color) {
                final isSelected = !_isEraser && _selectedColor == color;
                return GestureDetector(
                  onTap: () {
                    HapticFeedback.selectionClick();
                    setState(() {
                      _selectedColor = color;
                      _isEraser = false;
                    });
                    Navigator.of(ctx).pop();
                  },
                  child: Container(
                    width: 52,
                    height: 52,
                    decoration: BoxDecoration(
                      color: color,
                      shape: BoxShape.circle,
                      border: Border.all(
                        color: isSelected ? AppColors.textDark : Colors.grey.withValues(alpha: 0.35),
                        width: isSelected ? 4 : 1.5,
                      ),
                    ),
                    child: isSelected
                        ? Icon(Icons.check_rounded,
                            color: color.computeLuminance() > 0.6 ? AppColors.textDark : Colors.white, size: 28)
                        : null,
                  ),
                );
              }).toList(),
            ),
          ),
        ),
      ),
    );
  }

  /// Danh sách tranh: dùng ảnh thu nhỏ (ảnh thật) của từng thẻ.
  void _openItemPicker() {
    HapticFeedback.lightImpact();
    showDialog(
      context: context,
      barrierColor: Colors.black.withValues(alpha: 0.35),
      builder: (ctx) {
        final size = MediaQuery.sizeOf(ctx);
        return Dialog(
          backgroundColor: Colors.white,
          surfaceTintColor: Colors.transparent,
          insetPadding: const EdgeInsets.all(16),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
          child: ConstrainedBox(
            constraints: BoxConstraints(maxWidth: 640, maxHeight: size.height * 0.85),
            child: GridView.builder(
              shrinkWrap: true,
              padding: const EdgeInsets.all(16),
              gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
                maxCrossAxisExtent: 130,
                mainAxisSpacing: 12,
                crossAxisSpacing: 12,
                childAspectRatio: 0.85,
              ),
              itemCount: widget.items.length,
              itemBuilder: (context, idx) {
                final it = widget.items[idx];
                final isSelected = it.id == _activeItem.id;
                final thumb = it.effectiveRealImage ?? it.primaryImage;
                return GestureDetector(
                  onTap: () {
                    HapticFeedback.selectionClick();
                    Navigator.of(ctx).pop();
                    if (!isSelected) _selectItem(it);
                  },
                  child: Container(
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(
                        color: isSelected ? AppColors.primary : Colors.grey.withValues(alpha: 0.25),
                        width: isSelected ? 3 : 1.5,
                      ),
                    ),
                    clipBehavior: Clip.antiAlias,
                    child: Column(
                      children: [
                        Expanded(
                          child: ImageHelper.buildSafeImage(
                            thumb,
                            width: double.infinity,
                            fit: BoxFit.cover,
                            fallback: Center(child: Text(it.emoji, style: const TextStyle(fontSize: 40))),
                          ),
                        ),
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 4),
                          child: Text(
                            it.nameVi,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
                              color: isSelected ? AppColors.primary : AppColors.textDark,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
        );
      },
    );
  }

  // Bố cục ngang: công cụ bên trái, khung vẽ ở giữa (cao hết cỡ), cỡ nét bên phải
  Widget _buildLandscapeLayout() {
    return Row(
      children: [
        _buildLeftToolBar(),
        Expanded(child: _buildDrawingArea()),
        _buildRightSizeBar(),
      ],
    );
  }

  // Cột công cụ bên trái (Landscape)
  Widget _buildLeftToolBar() {
    return Container(
      width: 54,
      padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 2),
      child: Center(
        child: SingleChildScrollView(
          physics: const BouncingScrollPhysics(),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // Nút Cọ vẽ
              _buildIconToolButton(
                icon: Icons.brush_rounded,
                tooltip: 'Cọ vẽ',
                isSelected: !_isEraser,
                onTap: () => setState(() => _isEraser = false),
              ),
              const SizedBox(height: 6),

              // Nút Cục Tẩy (Icon cục tẩy thực tế)
              _buildEraserToolButton(
                tooltip: 'Cục tẩy',
                isSelected: _isEraser,
                onTap: () => setState(() => _isEraser = true),
              ),
              const SizedBox(height: 8),

              Container(
                width: 24,
                height: 1.5,
                color: Colors.grey.withValues(alpha: 0.2),
              ),
              const SizedBox(height: 8),

              // Nút Hoàn tác (Undo)
              _buildIconToolButton(
                icon: Icons.undo_rounded,
                tooltip: 'Hoàn tác',
                isSelected: false,
                color: AppColors.textDark,
                onTap: _undo,
              ),
              const SizedBox(height: 6),

              // Nút Xóa hết tranh vẽ (Clear)
              _buildIconToolButton(
                icon: Icons.delete_outline_rounded,
                tooltip: 'Xóa vẽ lại',
                isSelected: false,
                color: Colors.redAccent,
                onTap: _clearCanvas,
              ),
            ],
          ),
        ),
      ),
    );
  }

  // Cột chọn kích thước nét vẽ bên phải dạng ô tròn trực quan (Landscape)
  Widget _buildRightSizeBar() {
    final sizes = const [6.0, 12.0, 20.0, 28.0];

    return Container(
      width: 54,
      padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 2),
      child: Center(
        child: SingleChildScrollView(
          physics: const BouncingScrollPhysics(),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            mainAxisAlignment: MainAxisAlignment.center,
            children: sizes.map((size) {
              final isSelected = _strokeWidth == size;
              return Padding(
                padding: const EdgeInsets.symmetric(vertical: 4),
                child: _buildActualSizeCircleButton(size, isSelected),
              );
            }).toList(),
          ),
        ),
      ),
    );
  }

  Widget _buildDrawingArea() {
    final hasOutline = _hasOutline;
    final outlineReady = hasOutline && _outlineImage != null;
    // Khi chưa có tranh nét: dùng ảnh minh hoạ mờ làm nền gợi ý
    final backgroundImage = hasOutline ? _activeItem.coloringOutlineUrl!.trim() : _activeItem.primaryImage;

    // Không viền/khung/bóng: tranh chiếm hết chiều cao vùng giữa
    return ColoredBox(
      color: Colors.white,
      child: ClipRect(
        // Khung vẽ lấp đầy toàn bộ vùng giữa. App chỉ chạy màn ngang + toàn màn hình nên kích thước
        // khung ổn định; toạ độ nét vẽ vẫn được chuẩn hoá theo khung.
        child: LayoutBuilder(
              builder: (context, constraints) {
                final canvasSize = Size(constraints.maxWidth, constraints.maxHeight);

                Offset normalize(Offset p) => Offset(
                      (p.dx / canvasSize.width).clamp(0.0, 1.0),
                      (p.dy / canvasSize.height).clamp(0.0, 1.0),
                    );

                return Stack(
                  fit: StackFit.expand,
                  children: [
                    // Nền gợi ý (ảnh mờ) hoặc tranh nét trong lúc đang tải
                    if (!outlineReady && backgroundImage.isNotEmpty)
                      Opacity(
                        opacity: hasOutline ? 0.90 : 0.35,
                        child: ImageHelper.buildSafeImage(
                          backgroundImage,
                          fit: BoxFit.contain,
                          fallback: Center(
                            child: Text(_activeItem.emoji, style: const TextStyle(fontSize: 100)),
                          ),
                        ),
                      ),

                    // Lớp vẽ: nét tô nằm DƯỚI tranh nét (multiply) nên không che mất đường viền
                    GestureDetector(
                      behavior: HitTestBehavior.opaque,
                      onPanStart: (details) {
                        if (canvasSize.width <= 0) return;
                        final stroke = _Stroke(
                          color: _selectedColor,
                          width: _strokeWidth / canvasSize.width,
                          isEraser: _isEraser,
                        )..points.add(normalize(details.localPosition));
                        _currentStroke = stroke;
                        _strokes.add(stroke);
                        _repaint.value++;
                      },
                      onPanUpdate: (details) {
                        final stroke = _currentStroke;
                        if (stroke == null) return;
                        stroke.points.add(normalize(details.localPosition));
                        _repaint.value++;
                      },
                      onPanEnd: (_) => _currentStroke = null,
                      child: RepaintBoundary(
                        child: CustomPaint(
                          painter: _ColoringPainter(
                            strokes: _strokes,
                            outline: outlineReady ? _outlineImage : null,
                            repaint: _repaint,
                          ),
                          size: Size.infinite,
                        ),
                      ),
                    ),
                  ],
                );
              },
            ),
      ),
    );
  }

  // Nút công cụ dạng icon bo tròn không text
  Widget _buildIconToolButton({
    required IconData icon,
    required String tooltip,
    required bool isSelected,
    Color? color,
    required VoidCallback onTap,
  }) {
    final activeColor = color ?? AppColors.primary;
    return Tooltip(
      message: tooltip,
      child: InkWell(
        onTap: () {
          HapticFeedback.selectionClick();
          onTap();
        },
        borderRadius: BorderRadius.circular(19),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 150),
          width: 38,
          height: 38,
          decoration: BoxDecoration(
            color: isSelected ? activeColor.withValues(alpha: 0.15) : Colors.transparent,
            shape: BoxShape.circle,
            border: Border.all(
              color: isSelected ? activeColor : Colors.grey.withValues(alpha: 0.25),
              width: isSelected ? 2.0 : 1.2,
            ),
            boxShadow: isSelected
                ? [
                    BoxShadow(
                      color: activeColor.withValues(alpha: 0.2),
                      blurRadius: 6,
                      offset: const Offset(0, 2),
                    ),
                  ]
                : null,
          ),
          child: Center(
            child: Icon(
              icon,
              color: isSelected ? activeColor : (color ?? AppColors.textLight),
              size: 20,
            ),
          ),
        ),
      ),
    );
  }

  // Nút Cục Tẩy riêng biệt với Icon cục tẩy trực quan không text
  Widget _buildEraserToolButton({
    required String tooltip,
    required bool isSelected,
    required VoidCallback onTap,
  }) {
    const activeColor = AppColors.primary;
    return Tooltip(
      message: tooltip,
      child: InkWell(
        onTap: () {
          HapticFeedback.selectionClick();
          onTap();
        },
        borderRadius: BorderRadius.circular(19),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 150),
          width: 38,
          height: 38,
          decoration: BoxDecoration(
            color: isSelected ? activeColor.withValues(alpha: 0.15) : Colors.transparent,
            shape: BoxShape.circle,
            border: Border.all(
              color: isSelected ? activeColor : Colors.grey.withValues(alpha: 0.25),
              width: isSelected ? 2.0 : 1.2,
            ),
            boxShadow: isSelected
                ? [
                    BoxShadow(
                      color: activeColor.withValues(alpha: 0.2),
                      blurRadius: 6,
                      offset: const Offset(0, 2),
                    ),
                  ]
                : null,
          ),
          child: Center(
            child: EraserIcon(
              size: 20,
              color: isSelected ? activeColor : AppColors.textLight,
            ),
          ),
        ),
      ),
    );
  }

  // Ô tròn thể hiện đúng kích thước thực tế của nét vẽ (không text)
  Widget _buildActualSizeCircleButton(double size, bool isSelected) {
    // Giới hạn đường kính hiển thị tối đa trong ô 38px
    final dotDiameter = math.min(size, 24.0);

    return Tooltip(
      message: 'Nét ${size.toInt()}px',
      child: InkWell(
        onTap: () {
          HapticFeedback.selectionClick();
          setState(() => _strokeWidth = size);
        },
        borderRadius: BorderRadius.circular(19),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 150),
          width: 38,
          height: 38,
          decoration: BoxDecoration(
            color: isSelected ? AppColors.primary.withValues(alpha: 0.12) : Colors.grey.withValues(alpha: 0.06),
            shape: BoxShape.circle,
            border: Border.all(
              color: isSelected ? AppColors.primary : Colors.grey.withValues(alpha: 0.25),
              width: isSelected ? 2.2 : 1.2,
            ),
            boxShadow: isSelected
                ? [
                    BoxShadow(
                      color: AppColors.primary.withValues(alpha: 0.22),
                      blurRadius: 6,
                      offset: const Offset(0, 2),
                    ),
                  ]
                : null,
          ),
          child: Center(
            // Ô tròn thể hiện đúng kích thước nét vẽ
            child: Container(
              width: dotDiameter,
              height: dotDiameter,
              decoration: BoxDecoration(
                color: _isEraser ? const Color(0xFF64748B) : _selectedColor,
                shape: BoxShape.circle,
              ),
            ),
          ),
        ),
      ),
    );
  }
}

/// Nút bảng màu: vòng các đốm màu, chấm giữa là màu đang chọn (hoặc biểu tượng tẩy).
class _PaletteButton extends StatelessWidget {
  final Color selectedColor;
  final bool isEraser;
  final List<Color> palette;
  final VoidCallback onTap;

  const _PaletteButton({
    required this.selectedColor,
    required this.isEraser,
    required this.palette,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Tooltip(
      message: 'Chọn màu',
      child: InkResponse(
        onTap: onTap,
        radius: 26,
        child: SizedBox(
          width: 44,
          height: 44,
          child: CustomPaint(
            painter: _PaletteDotsPainter(
              dots: palette.where((c) => c != const Color(0xFFFFFFFF)).take(8).toList(),
              center: isEraser ? Colors.white : selectedColor,
            ),
            child: isEraser
                ? const Center(child: Icon(Icons.cleaning_services_rounded, size: 14, color: AppColors.textLight))
                : null,
          ),
        ),
      ),
    );
  }
}

class _PaletteDotsPainter extends CustomPainter {
  final List<Color> dots;
  final Color center;

  _PaletteDotsPainter({required this.dots, required this.center});

  @override
  void paint(Canvas canvas, Size size) {
    final c = size.center(Offset.zero);
    final r = size.shortestSide / 2;
    canvas.drawCircle(c, r - 1, Paint()..color = Colors.white);
    canvas.drawCircle(
      c,
      r - 1,
      Paint()
        ..style = PaintingStyle.stroke
        ..strokeWidth = 1.5
        ..color = Colors.grey.withValues(alpha: 0.35),
    );
    final ringR = r * 0.62;
    final dotR = r * 0.18;
    for (var i = 0; i < dots.length; i++) {
      final angle = -math.pi / 2 + 2 * math.pi * i / dots.length;
      canvas.drawCircle(c + Offset(math.cos(angle), math.sin(angle)) * ringR, dotR, Paint()..color = dots[i]);
    }
    canvas.drawCircle(c, r * 0.3, Paint()..color = center);
    canvas.drawCircle(
      c,
      r * 0.3,
      Paint()
        ..style = PaintingStyle.stroke
        ..strokeWidth = 1.5
        ..color = AppColors.textDark.withValues(alpha: 0.6),
    );
  }

  @override
  bool shouldRepaint(covariant _PaletteDotsPainter old) => old.center != center || old.dots != dots;
}

class _ColoringPainter extends CustomPainter {
  final List<_Stroke> strokes;
  final ui.Image? outline;

  _ColoringPainter({required this.strokes, required this.outline, required Listenable repaint})
      : super(repaint: repaint);

  @override
  void paint(Canvas canvas, Size size) {
    final rect = Offset.zero & size;
    canvas.saveLayer(rect, Paint());

    for (final stroke in strokes) {
      if (stroke.points.isEmpty) continue;
      final strokeWidth = stroke.width * size.width;
      final paint = Paint()
        ..color = stroke.isEraser ? Colors.transparent : stroke.color
        ..blendMode = stroke.isEraser ? BlendMode.clear : BlendMode.srcOver
        ..strokeWidth = strokeWidth
        ..strokeCap = StrokeCap.round
        ..strokeJoin = StrokeJoin.round
        ..isAntiAlias = true;

      final first = Offset(stroke.points.first.dx * size.width, stroke.points.first.dy * size.height);
      if (stroke.points.length == 1) {
        // Chạm 1 lần cũng để lại một chấm màu
        canvas.drawCircle(first, strokeWidth / 2, paint..style = PaintingStyle.fill);
        continue;
      }
      final path = Path()..moveTo(first.dx, first.dy);
      for (var i = 1; i < stroke.points.length; i++) {
        final p = stroke.points[i];
        path.lineTo(p.dx * size.width, p.dy * size.height);
      }
      canvas.drawPath(path, paint..style = PaintingStyle.stroke);
    }

    // Tranh nét vẽ chồng lên bằng multiply: nền trắng của tranh "trong suốt", nét đen luôn hiện rõ
    final img = outline;
    if (img != null) {
      paintImage(
        canvas: canvas,
        rect: rect,
        image: img,
        fit: BoxFit.contain,
        blendMode: BlendMode.multiply,
        filterQuality: FilterQuality.medium,
      );
    }

    canvas.restore();
  }

  @override
  bool shouldRepaint(covariant _ColoringPainter oldDelegate) =>
      oldDelegate.outline != outline || oldDelegate.strokes != strokes;
}

// Icon Cục Tẩy vẽ vector sắc nét hình khối thực tế
class EraserIcon extends StatelessWidget {
  final double size;
  final Color color;

  const EraserIcon({super.key, this.size = 24, required this.color});

  @override
  Widget build(BuildContext context) {
    return CustomPaint(
      size: Size(size, size),
      painter: _EraserPainter(color: color),
    );
  }
}

class _EraserPainter extends CustomPainter {
  final Color color;

  _EraserPainter({required this.color});

  @override
  void paint(Canvas canvas, Size size) {
    final strokePaint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = 2.0 * (size.width / 24.0)
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round;

    final fillPaint = Paint()
      ..color = color.withValues(alpha: 0.25)
      ..style = PaintingStyle.fill;

    final scale = size.width / 24.0;
    canvas.save();
    canvas.scale(scale);

    // Thân cục tẩy nghiêng 45 độ
    final path = Path();
    path.moveTo(15.5, 3.2);
    path.lineTo(20.8, 8.5);
    path.arcToPoint(const Offset(20.8, 11.5), radius: const Radius.circular(2.5));
    path.lineTo(12.0, 20.3);
    path.arcToPoint(const Offset(6.5, 20.3), radius: const Radius.circular(4.0));
    path.lineTo(2.8, 16.6);
    path.arcToPoint(const Offset(2.8, 11.2), radius: const Radius.circular(4.0));
    path.lineTo(13.2, 0.8);
    path.arcToPoint(const Offset(15.5, 3.2), radius: const Radius.circular(2.5));
    path.close();

    canvas.drawPath(path, fillPaint);
    canvas.drawPath(path, strokePaint);

    // Vạch đai bọc của cục tẩy
    final bandPath = Path();
    bandPath.moveTo(7.5, 14.5);
    bandPath.lineTo(14.5, 7.5);
    canvas.drawPath(bandPath, strokePaint);

    // Đường mặt bàn tẩy
    final baseLine = Path();
    baseLine.moveTo(17.5, 21.0);
    baseLine.lineTo(22.0, 21.0);
    canvas.drawPath(baseLine, strokePaint);

    canvas.restore();
  }

  @override
  bool shouldRepaint(covariant _EraserPainter oldDelegate) => oldDelegate.color != color;
}
