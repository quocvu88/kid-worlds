import 'dart:convert';
import 'dart:math' as math;
import 'dart:ui';
import 'dart:ui' as ui;
import 'package:confetti/confetti.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/services/artwork_service.dart';
import '../../../core/services/audio_service.dart';
import '../../../core/services/screen_time_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/image_helper.dart';
import '../../../models/artwork_model.dart';
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

  // Lưu gọn: điểm làm tròn 4 chữ số và trải phẳng [x0, y0, x1, y1, ...]
  Map<String, dynamic> toJson() => {
        'c': color.toARGB32(),
        'w': width,
        'e': isEraser ? 1 : 0,
        'p': [
          for (final pt in points) ...[
            double.parse(pt.dx.toStringAsFixed(4)),
            double.parse(pt.dy.toStringAsFixed(4)),
          ],
        ],
      };

  static _Stroke fromJson(Map<String, dynamic> json) {
    final stroke = _Stroke(
      color: Color(json['c'] as int),
      width: (json['w'] as num).toDouble(),
      isEraser: json['e'] == 1,
    );
    final flat = (json['p'] as List).cast<num>();
    for (var i = 0; i + 1 < flat.length; i += 2) {
      stroke.points.add(Offset(flat[i].toDouble(), flat[i + 1].toDouble()));
    }
    return stroke;
  }
}

class ColoringGameScreen extends StatefulWidget {
  final Topic topic;
  final List<TopicItem> items;
  final TopicItem? currentItem;

  /// Mở lại tranh đã lưu để tô tiếp (Góc Sáng Tạo).
  final Artwork? resumeArtwork;

  const ColoringGameScreen({
    super.key,
    required this.topic,
    required this.items,
    this.currentItem,
    this.resumeArtwork,
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

  /// Tranh đã lưu đang được tô tiếp → bấm Lưu sẽ ghi đè thay vì tạo bản mới.
  String? _savedArtworkId;
  bool _isSaving = false;

  /// Kích thước khung vẽ gần nhất (để xuất ảnh đúng tỉ lệ như trên màn hình).
  Size _canvasSize = Size.zero;

  // Zoom/kéo tranh bằng 2 ngón: 1 ngón = tô, 2 ngón = phóng to/thu nhỏ + kéo.
  // Toạ độ màn hình → toạ độ tranh: (p - _pan) / _zoom.
  static const double _maxZoom = 5.0;
  double _zoom = 1.0;
  Offset _pan = Offset.zero;
  final ValueNotifier<int> _view = ValueNotifier<int>(0);
  final Map<int, Offset> _pointers = {};
  bool _isZooming = false; // giữ chế độ zoom tới khi nhấc hết ngón
  DateTime _strokeStartedAt = DateTime.fromMillisecondsSinceEpoch(0);
  double _zoomStartScale = 1.0;
  Offset _zoomStartPan = Offset.zero;
  double _zoomStartDistance = 0;
  Offset _zoomStartFocal = Offset.zero;

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
    final resume = widget.resumeArtwork;
    if (resume != null) {
      _activeItem = widget.items.firstWhere((it) => it.id == resume.itemId, orElse: () => _activeItem);
      _savedArtworkId = resume.id;
      try {
        final list = jsonDecode(resume.strokesJson) as List;
        _strokes.addAll(list.map((e) => _Stroke.fromJson(Map<String, dynamic>.from(e as Map))));
      } catch (e) {
        debugPrint('Không đọc được nét vẽ của tranh ${resume.id}: $e');
      }
    }
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
      _savedArtworkId = null; // tranh khác → lần lưu sau là tranh mới
    });
    _resetZoom();
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
    _view.dispose();
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

  /// Xuất tranh thành PNG (nền trắng + nét tô + tranh nét) đúng tỉ lệ khung vẽ trên màn hình.
  Future<Uint8List?> _exportPng() async {
    if (_canvasSize.isEmpty) return null;
    // Gấp đôi kích thước màn hình cho nét, giới hạn chiều rộng 1600px để file không quá nặng
    final scale = math.min(2.0, 1600 / _canvasSize.width);
    final size = _canvasSize * scale;
    final recorder = ui.PictureRecorder();
    final canvas = Canvas(recorder, Offset.zero & size);
    canvas.drawRect(Offset.zero & size, Paint()..color = Colors.white);
    _ColoringPainter(strokes: _strokes, outline: _outlineImage, repaint: _repaint).paint(canvas, size);
    final image = await recorder.endRecording().toImage(size.width.round(), size.height.round());
    try {
      final data = await image.toByteData(format: ui.ImageByteFormat.png);
      return data?.buffer.asUint8List();
    } finally {
      image.dispose();
    }
  }

  Future<void> _saveArtwork() async {
    if (_isSaving) return;
    final child = ScreenTimeService.instance.currentChild;
    void tell(String msg) {
      AudioService.instance.speakVietnamese(msg);
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(msg), behavior: SnackBarBehavior.floating));
    }

    if (_strokes.isEmpty) {
      HapticFeedback.lightImpact();
      tell('Bé hãy tô vài nét rồi mới lưu tranh nhé!');
      return;
    }
    if (child == null || !ArtworkService.instance.isSupported) {
      tell('Chưa lưu được tranh trên thiết bị này.');
      return;
    }

    _isSaving = true;
    try {
      final png = await _exportPng();
      if (png == null) return;
      final saved = await ArtworkService.instance.save(
        existingId: _savedArtworkId,
        childId: child.id,
        topicId: _activeItem.topicId,
        itemId: _activeItem.id == 'item_default' ? null : _activeItem.id,
        title: _activeItem.nameVi,
        pngBytes: png,
        strokesJson: jsonEncode(_strokes.map((s) => s.toJson()).toList()),
      );
      _savedArtworkId = saved.id;
    } catch (e) {
      debugPrint('Lưu tranh thất bại: $e');
      if (mounted) tell('Ôi, chưa lưu được tranh. Bé thử lại nhé!');
      return;
    } finally {
      _isSaving = false;
    }
    if (!mounted) return;
    _showSavedDialog();
  }

  void _showSavedDialog() {
    HapticFeedback.heavyImpact();
    _confettiController.play();
    AudioService.instance.speakVietnamese('Bé tô tranh thật xuất sắc! Bức tranh đã được lưu vào Góc Sáng Tạo của bé!');

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

  // ---------------------------------------------------------------------------
  // Cử chỉ trên khung vẽ (dùng Listener thô để tự phân biệt 1 ngón / 2 ngón,
  // tránh GestureDetector pan "giành" mất cử chỉ zoom).
  // ---------------------------------------------------------------------------

  void _resetZoom() {
    _zoom = 1.0;
    _pan = Offset.zero;
    _view.value++;
  }

  Offset _normalize(Offset screen, Size size) {
    final p = (screen - _pan) / _zoom;
    return Offset((p.dx / size.width).clamp(0.0, 1.0), (p.dy / size.height).clamp(0.0, 1.0));
  }

  void _beginZoom() {
    final pts = _pointers.values.take(2).toList();
    _zoomStartDistance = (pts[0] - pts[1]).distance;
    _zoomStartFocal = (pts[0] + pts[1]) / 2;
    _zoomStartScale = _zoom;
    _zoomStartPan = _pan;
  }

  void _onPointerDown(PointerDownEvent e, Size size) {
    _pointers[e.pointer] = e.localPosition;
    if (size.width <= 0) return;

    if (_pointers.length == 1 && !_isZooming) {
      final stroke = _Stroke(
        color: _selectedColor,
        // Chia cho zoom để cỡ cọ trên màn hình giữ nguyên → phóng to thì tô được chi tiết nhỏ
        width: _strokeWidth / _zoom / size.width,
        isEraser: _isEraser,
      )..points.add(_normalize(e.localPosition, size));
      _currentStroke = stroke;
      _strokeStartedAt = DateTime.now();
      _strokes.add(stroke);
      _repaint.value++;
    } else if (_pointers.length >= 2) {
      // Ngón thứ 2 chạm xuống ngay sau ngón đầu → đó là zoom, bỏ chấm/nét vừa lỡ vẽ
      final stroke = _currentStroke;
      if (stroke != null && DateTime.now().difference(_strokeStartedAt).inMilliseconds < 300) {
        _strokes.remove(stroke);
        _repaint.value++;
      }
      _currentStroke = null;
      _isZooming = true;
      _beginZoom();
    }
  }

  void _onPointerMove(PointerMoveEvent e, Size size) {
    if (!_pointers.containsKey(e.pointer)) return;
    _pointers[e.pointer] = e.localPosition;

    if (_isZooming) {
      if (_pointers.length < 2 || _zoomStartDistance <= 0) return;
      final pts = _pointers.values.take(2).toList();
      final focal = (pts[0] + pts[1]) / 2;
      final zoom = (_zoomStartScale * (pts[0] - pts[1]).distance / _zoomStartDistance).clamp(1.0, _maxZoom);
      // Giữ điểm tranh nằm dưới tâm 2 ngón lúc bắt đầu luôn nằm dưới tâm 2 ngón hiện tại
      final contentFocal = (_zoomStartFocal - _zoomStartPan) / _zoomStartScale;
      final pan = focal - contentFocal * zoom;
      _zoom = zoom;
      _pan = Offset(
        pan.dx.clamp(size.width * (1 - zoom), 0.0),
        pan.dy.clamp(size.height * (1 - zoom), 0.0),
      );
      _view.value++;
      return;
    }

    final stroke = _currentStroke;
    if (stroke == null) return;
    stroke.points.add(_normalize(e.localPosition, size));
    _repaint.value++;
  }

  void _onPointerEnd(PointerEvent e) {
    _pointers.remove(e.pointer);
    if (_pointers.isEmpty) {
      _isZooming = false;
      _currentStroke = null;
    } else if (_isZooming && _pointers.length >= 2) {
      _beginZoom(); // còn ≥2 ngón: lấy mốc mới để tranh không bị giật
    }
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
                _canvasSize = canvasSize;

                final content = Stack(
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
                    RepaintBoundary(
                      child: CustomPaint(
                        painter: _ColoringPainter(
                          strokes: _strokes,
                          outline: outlineReady ? _outlineImage : null,
                          repaint: _repaint,
                        ),
                        size: Size.infinite,
                      ),
                    ),
                  ],
                );

                return Stack(
                  fit: StackFit.expand,
                  children: [
                    Listener(
                      behavior: HitTestBehavior.opaque,
                      onPointerDown: (e) => _onPointerDown(e, canvasSize),
                      onPointerMove: (e) => _onPointerMove(e, canvasSize),
                      onPointerUp: _onPointerEnd,
                      onPointerCancel: _onPointerEnd,
                      child: ValueListenableBuilder<int>(
                        valueListenable: _view,
                        builder: (context, _, child) => Transform(
                          transform: Matrix4.diagonal3Values(_zoom, _zoom, 1)
                            ..setTranslationRaw(_pan.dx, _pan.dy, 0),
                          child: child,
                        ),
                        child: content,
                      ),
                    ),

                    // Nút đưa tranh về kích thước ban đầu (chỉ hiện khi đang phóng to)
                    Positioned(
                      right: 12,
                      bottom: 12,
                      child: ValueListenableBuilder<int>(
                        valueListenable: _view,
                        builder: (context, _, _) => _zoom > 1.01
                            ? Material(
                                color: Colors.white,
                                shape: const CircleBorder(),
                                elevation: 4,
                                child: InkWell(
                                  customBorder: const CircleBorder(),
                                  onTap: () {
                                    HapticFeedback.lightImpact();
                                    _resetZoom();
                                  },
                                  child: const Padding(
                                    padding: EdgeInsets.all(10),
                                    child: Icon(Icons.zoom_out_map_rounded, size: 26, color: AppColors.primary),
                                  ),
                                ),
                              )
                            : const SizedBox.shrink(),
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
