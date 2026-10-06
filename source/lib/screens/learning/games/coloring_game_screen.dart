import 'dart:math' as math;
import 'dart:ui';
import 'package:confetti/confetti.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/services/audio_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/image_helper.dart';
import '../../../models/topic_item_model.dart';
import '../../../models/topic_model.dart';

class DrawingPoint {
  final Offset point;
  final Paint paint;

  DrawingPoint({required this.point, required this.paint});
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
  
  final List<List<DrawingPoint?>> _strokes = [];
  List<DrawingPoint?> _currentStroke = [];
  
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
    _activeItem = widget.currentItem ?? (widget.items.isNotEmpty ? widget.items.first : _dummyItem());
    AudioService.instance.speakVietnamese('Bé hãy chọn màu yêu thích và tô tranh nhé!');
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
    _confettiController.dispose();
    super.dispose();
  }

  void _undo() {
    HapticFeedback.lightImpact();
    if (_strokes.isNotEmpty) {
      setState(() => _strokes.removeLast());
    }
  }

  void _clearCanvas() {
    HapticFeedback.mediumImpact();
    setState(() => _strokes.clear());
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
    final orientation = MediaQuery.of(context).orientation;
    final isLandscape = orientation == Orientation.landscape;

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      body: Stack(
        children: [
          SafeArea(
            child: Column(
              children: [
                // Top Header bar
                _buildTopBar(isLandscape),

                // Main Workspace
                Expanded(
                  child: isLandscape ? _buildLandscapeLayout() : _buildPortraitLayout(),
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

  Widget _buildTopBar(bool isLandscape) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(bottom: BorderSide(color: Colors.grey.withValues(alpha: 0.2))),
      ),
      child: Row(
        children: [
          IconButton(
            icon: const Icon(Icons.arrow_back_rounded, color: AppColors.textDark, size: 24),
            onPressed: () => Navigator.of(context).pop(),
          ),
          const SizedBox(width: 6),
          const Text('🎨', style: TextStyle(fontSize: 22)),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  'Tô Màu: ${_activeItem.nameVi}',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppColors.textDark),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                Text(
                  widget.topic.titleVi,
                  style: const TextStyle(fontSize: 12, color: AppColors.textLight),
                ),
              ],
            ),
          ),

          // Items Carousel Selector mini
          if (widget.items.length > 1) ...[
            SizedBox(
              height: 36,
              child: ListView.builder(
                scrollDirection: Axis.horizontal,
                shrinkWrap: true,
                itemCount: math.min(widget.items.length, 6),
                itemBuilder: (context, idx) {
                  final it = widget.items[idx];
                  final isSelected = it.id == _activeItem.id;
                  return Padding(
                    padding: const EdgeInsets.only(right: 6),
                    child: InkWell(
                      onTap: () {
                        HapticFeedback.lightImpact();
                        setState(() {
                          _activeItem = it;
                          _strokes.clear();
                        });
                      },
                      borderRadius: BorderRadius.circular(18),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: isSelected ? AppColors.primary : Colors.grey.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(18),
                        ),
                        child: Center(
                          child: Text(
                            it.nameVi,
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                              color: isSelected ? Colors.white : AppColors.textDark,
                            ),
                          ),
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),
            const SizedBox(width: 8),
          ],

          // Save button
          ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF10B981),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            ),
            icon: const Icon(Icons.favorite_rounded, size: 16),
            label: const Text('Lưu Tranh', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
            onPressed: _saveArtwork,
          ),
        ],
      ),
    );
  }

  // Màn hình ngang (Landscape): Cụm công cụ bên trái, nét vẽ ô tròn bên phải, bảng màu ở cạnh dưới
  Widget _buildLandscapeLayout() {
    return Column(
      children: [
        Expanded(
          child: Row(
            children: [
              // Cột bên trái: Công cụ vẽ (Bút, Tẩy, Hoàn tác, Xóa) - Không text
              _buildLeftToolBar(),

              // Vùng trung tâm: Canvas vẽ tranh
              Expanded(child: _buildDrawingArea()),

              // Cột bên phải: Chọn kích thước nét vẽ dạng ô tròn trực quan
              _buildRightSizeBar(),
            ],
          ),
        ),

        // Cạnh dưới màn hình: Bảng màu trải ngang
        _buildBottomPaletteBar(),
      ],
    );
  }

  // Cột công cụ bên trái (Landscape)
  Widget _buildLeftToolBar() {
    return Container(
      width: 62,
      padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 4),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(
          right: BorderSide(color: Colors.grey.withValues(alpha: 0.15)),
        ),
      ),
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
      width: 62,
      padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 4),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(
          left: BorderSide(color: Colors.grey.withValues(alpha: 0.15)),
        ),
      ),
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

  // Thanh bảng màu nằm ở cạnh dưới màn hình
  Widget _buildBottomPaletteBar() {
    return Container(
      height: 52,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(
          top: BorderSide(color: Colors.grey.withValues(alpha: 0.15)),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 6,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: Center(
        child: SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          physics: const BouncingScrollPhysics(),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: _palette.map((color) {
              final isSelected = !_isEraser && _selectedColor == color;
              return Padding(
                padding: const EdgeInsets.symmetric(horizontal: 5),
                child: _buildColorCircle(color, isSelected),
              );
            }).toList(),
          ),
        ),
      ),
    );
  }

  // Màn hình dọc (Portrait)
  Widget _buildPortraitLayout() {
    final sizes = const [6.0, 12.0, 20.0, 30.0];

    return Column(
      children: [
        Expanded(child: _buildDrawingArea()),

        // Thanh điều khiển dưới đáy
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            border: Border(top: BorderSide(color: Colors.grey.withValues(alpha: 0.15))),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Hàng màu
              _buildBottomPaletteBar(),

              // Hàng công cụ + Kích thước nét vẽ ô tròn
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                  children: [
                    _buildIconToolButton(
                      icon: Icons.brush_rounded,
                      tooltip: 'Cọ vẽ',
                      isSelected: !_isEraser,
                      onTap: () => setState(() => _isEraser = false),
                    ),
                    _buildEraserToolButton(
                      tooltip: 'Cục tẩy',
                      isSelected: _isEraser,
                      onTap: () => setState(() => _isEraser = true),
                    ),
                    // Kích thước nét dạng ô tròn thực tế
                    ...sizes.map((size) {
                      final isSelected = _strokeWidth == size;
                      return _buildActualSizeCircleButton(size, isSelected);
                    }),
                    _buildIconToolButton(
                      icon: Icons.undo_rounded,
                      tooltip: 'Hoàn tác',
                      isSelected: false,
                      color: AppColors.textDark,
                      onTap: _undo,
                    ),
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
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildDrawingArea() {
    final hasOutline = _activeItem.coloringOutlineUrl != null && _activeItem.coloringOutlineUrl!.trim().isNotEmpty;
    final imageToOutline = hasOutline ? _activeItem.coloringOutlineUrl!.trim() : _activeItem.primaryImage;

    return Container(
      margin: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(24),
        child: Stack(
          fit: StackFit.expand,
          children: [
            // Background Image / Outline
            if (imageToOutline.isNotEmpty)
              Opacity(
                opacity: hasOutline ? 0.90 : 0.35,
                child: ImageHelper.buildSafeImage(
                  imageToOutline,
                  fit: BoxFit.contain,
                  fallback: Center(
                    child: Text(_activeItem.emoji, style: const TextStyle(fontSize: 100)),
                  ),
                ),
              ),

            // Drawing Canvas Layer
            GestureDetector(
              onPanStart: (details) {
                final point = details.localPosition;
                final paint = Paint()
                  ..color = _isEraser ? Colors.white : _selectedColor
                  ..strokeWidth = _strokeWidth
                  ..strokeCap = StrokeCap.round
                  ..strokeJoin = StrokeJoin.round
                  ..style = PaintingStyle.stroke;

                setState(() {
                  _currentStroke = [DrawingPoint(point: point, paint: paint)];
                  _strokes.add(_currentStroke);
                });
              },
              onPanUpdate: (details) {
                final point = details.localPosition;
                final paint = Paint()
                  ..color = _isEraser ? Colors.white : _selectedColor
                  ..strokeWidth = _strokeWidth
                  ..strokeCap = StrokeCap.round
                  ..strokeJoin = StrokeJoin.round
                  ..style = PaintingStyle.stroke;

                setState(() {
                  _currentStroke.add(DrawingPoint(point: point, paint: paint));
                });
              },
              onPanEnd: (details) {
                setState(() {
                  _currentStroke = [];
                });
              },
              child: CustomPaint(
                painter: _ColoringPainter(strokes: _strokes),
                size: Size.infinite,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildColorCircle(Color color, bool isSelected) {
    return GestureDetector(
      onTap: () {
        HapticFeedback.selectionClick();
        setState(() {
          _selectedColor = color;
          _isEraser = false;
        });
      },
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        width: isSelected ? 38 : 30,
        height: isSelected ? 38 : 30,
        decoration: BoxDecoration(
          color: color,
          shape: BoxShape.circle,
          border: Border.all(
            color: isSelected ? AppColors.textDark : Colors.grey.withValues(alpha: 0.35),
            width: isSelected ? 3 : 1.5,
          ),
          boxShadow: [
            if (isSelected)
              BoxShadow(
                color: color.withValues(alpha: 0.5),
                blurRadius: 8,
                offset: const Offset(0, 2),
              ),
          ],
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

class _ColoringPainter extends CustomPainter {
  final List<List<DrawingPoint?>> strokes;

  _ColoringPainter({required this.strokes});

  @override
  void paint(Canvas canvas, Size size) {
    for (final stroke in strokes) {
      for (int i = 0; i < stroke.length - 1; i++) {
        final p1 = stroke[i];
        final p2 = stroke[i + 1];
        if (p1 != null && p2 != null) {
          canvas.drawLine(p1.point, p2.point, p1.paint);
        }
      }
    }
  }

  @override
  bool shouldRepaint(covariant _ColoringPainter oldDelegate) => true;
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
