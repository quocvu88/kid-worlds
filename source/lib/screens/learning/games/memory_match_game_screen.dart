import 'dart:async';
import 'dart:ui';
import 'package:confetti/confetti.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/services/audio_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/image_helper.dart';
import '../../../models/topic_item_model.dart';
import '../../../models/topic_model.dart';

class _CardModel {
  final int id;
  final TopicItem item;
  bool isFlipped = false;
  bool isMatched = false;

  _CardModel({
    required this.id,
    required this.item,
  });
}

class MemoryMatchGameScreen extends StatefulWidget {
  final Topic topic;
  final List<TopicItem> items;

  const MemoryMatchGameScreen({
    super.key,
    required this.topic,
    required this.items,
  });

  @override
  State<MemoryMatchGameScreen> createState() => _MemoryMatchGameScreenState();
}

class _MemoryMatchGameScreenState extends State<MemoryMatchGameScreen> {
  late ConfettiController _confettiController;
  List<_CardModel> _cards = [];
  
  _CardModel? _firstSelectedCard;
  _CardModel? _secondSelectedCard;
  bool _isProcessing = false;

  Timer? _gameTimer;
  int _secondsElapsed = 0;
  int _turnsCount = 0;
  int _matchesFound = 0;
  int _totalPairs = 6; // 6 pairs = 12 cards (3x4), or 12 pairs = 24 cards (4x6)
  String _difficulty = '3x4'; // '3x4' or '4x6'

  @override
  void initState() {
    super.initState();
    _confettiController = ConfettiController(duration: const Duration(seconds: 4));
    _initGame();
  }

  @override
  void dispose() {
    _gameTimer?.cancel();
    _confettiController.dispose();
    super.dispose();
  }

  void _initGame() {
    _gameTimer?.cancel();
    _secondsElapsed = 0;
    _turnsCount = 0;
    _matchesFound = 0;
    _firstSelectedCard = null;
    _secondSelectedCard = null;
    _isProcessing = false;

    _totalPairs = _difficulty == '4x6' ? 12 : 6;
    final availableItems = List<TopicItem>.from(widget.items);
    if (availableItems.isEmpty) return;

    // Pick pairs
    availableItems.shuffle();
    final selectedItems = <TopicItem>[];
    for (int i = 0; i < _totalPairs; i++) {
      selectedItems.add(availableItems[i % availableItems.length]);
    }

    // Duplicate to make pairs
    final cardList = <_CardModel>[];
    int cardId = 0;
    for (final it in selectedItems) {
      cardList.add(_CardModel(id: cardId++, item: it));
      cardList.add(_CardModel(id: cardId++, item: it));
    }
    cardList.shuffle();

    setState(() {
      _cards = cardList;
    });

    _startTimer();
    AudioService.instance.speakVietnamese('Bé hãy tìm các cặp hình giống nhau nhé!');
  }

  void _startTimer() {
    _gameTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) return;
      setState(() => _secondsElapsed++);
    });
  }

  void _onCardTap(_CardModel card) {
    if (_isProcessing || card.isFlipped || card.isMatched) return;

    HapticFeedback.lightImpact();
    setState(() {
      card.isFlipped = true;
    });

    // Speak bilingual name on flip
    AudioService.instance.speakEnglish(card.item.nameEn);

    if (_firstSelectedCard == null) {
      _firstSelectedCard = card;
    } else {
      _secondSelectedCard = card;
      _turnsCount++;
      _checkMatch();
    }
  }

  void _checkMatch() {
    _isProcessing = true;
    final card1 = _firstSelectedCard!;
    final card2 = _secondSelectedCard!;

    if (card1.item.id == card2.item.id) {
      // Match found!
      HapticFeedback.mediumImpact();
      Future.delayed(const Duration(milliseconds: 400), () {
        if (!mounted) return;
        setState(() {
          card1.isMatched = true;
          card2.isMatched = true;
          _matchesFound++;
          _firstSelectedCard = null;
          _secondSelectedCard = null;
          _isProcessing = false;
        });

        AudioService.instance.speakVietnamese('Chính xác! ${card1.item.nameVi}');

        if (_matchesFound >= _totalPairs) {
          _onGameVictory();
        }
      });
    } else {
      // Not matched, flip back
      Future.delayed(const Duration(milliseconds: 900), () {
        if (!mounted) return;
        setState(() {
          card1.isFlipped = false;
          card2.isFlipped = false;
          _firstSelectedCard = null;
          _secondSelectedCard = null;
          _isProcessing = false;
        });
      });
    }
  }

  void _onGameVictory() {
    _gameTimer?.cancel();
    _confettiController.play();
    HapticFeedback.heavyImpact();
    AudioService.instance.speakVietnamese('Bé thật tuyệt vời! Đã tìm thấy tất cả các cặp hình!');

    showDialog(
      context: context,
      barrierColor: Colors.black.withValues(alpha: 0.70),
      barrierDismissible: false,
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
                      Text('🏆', style: TextStyle(fontSize: isLandscape ? 42 : 52)),
                      const SizedBox(height: 6),
                      Text(
                        'Chiến Thắng Xuất Sắc!',
                        textAlign: TextAlign.center,
                        style: AppTextStyles.kidTitle.copyWith(
                          fontSize: isLandscape ? 20 : 22,
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
                      const SizedBox(height: 12),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          _buildStatBadge('⏱️ Thời gian', '$_secondsElapsed giây'),
                          const SizedBox(width: 12),
                          _buildStatBadge('🎯 Số lượt', '$_turnsCount lượt'),
                        ],
                      ),
                      SizedBox(height: isLandscape ? 8 : 12),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text('⭐', style: TextStyle(fontSize: isLandscape ? 28 : 34)),
                          const SizedBox(width: 4),
                          Text('⭐', style: TextStyle(fontSize: isLandscape ? 36 : 42)),
                          const SizedBox(width: 4),
                          Text('⭐', style: TextStyle(fontSize: isLandscape ? 28 : 34)),
                        ],
                      ),
                      SizedBox(height: isLandscape ? 14 : 18),
                      // Buttons fitted safely inside the card frame
                      Row(
                        children: [
                          Expanded(
                            child: ElevatedButton.icon(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: Colors.white.withValues(alpha: 0.16),
                                foregroundColor: Colors.white,
                                elevation: 0,
                                side: BorderSide(
                                  color: Colors.white.withValues(alpha: 0.35),
                                  width: 1.5,
                                ),
                                padding: const EdgeInsets.symmetric(vertical: 12),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(16),
                                ),
                              ),
                              icon: const Icon(Icons.refresh_rounded, size: 20),
                              label: const Text(
                                'Chơi Lại',
                                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5),
                              ),
                              onPressed: () {
                                Navigator.of(ctx).pop();
                                _initGame();
                              },
                            ),
                          ),
                          const SizedBox(width: 12),
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
                                'Hoàn Thành',
                                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5),
                              ),
                              onPressed: () {
                                Navigator.of(ctx).pop();
                                Navigator.of(context).pop();
                              },
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

  Widget _buildStatBadge(String label, String value) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Colors.white.withValues(alpha: 0.2)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            label,
            style: const TextStyle(fontSize: 11, color: Colors.white70, fontWeight: FontWeight.w500),
          ),
          const SizedBox(height: 2),
          Text(
            value,
            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF38BDF8)),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final orientation = MediaQuery.of(context).orientation;
    final isLandscape = orientation == Orientation.landscape;

    int crossAxisCount;
    if (_difficulty == '4x6') {
      crossAxisCount = isLandscape ? 6 : 4;
    } else {
      crossAxisCount = isLandscape ? 4 : 3;
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF1F5F9),
      body: Stack(
        children: [
          SafeArea(
            child: Column(
              children: [
                // Top Game Header
                _buildHeader(),

                // Cards Grid Area
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                    child: GridView.builder(
                      physics: const BouncingScrollPhysics(),
                      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: crossAxisCount,
                        crossAxisSpacing: 10,
                        mainAxisSpacing: 10,
                        childAspectRatio: isLandscape ? 1.15 : 0.88,
                      ),
                      itemCount: _cards.length,
                      itemBuilder: (context, index) {
                        return _buildFlipCard(_cards[index]);
                      },
                    ),
                  ),
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
              maxBlastForce: 25,
              minBlastForce: 10,
              emissionFrequency: 0.05,
              numberOfParticles: 35,
              gravity: 0.2,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeader() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 8, offset: const Offset(0, 2)),
        ],
      ),
      child: Row(
        children: [
          IconButton(
            icon: const Icon(Icons.arrow_back_rounded, color: AppColors.textDark),
            onPressed: () => Navigator.of(context).pop(),
          ),
          const SizedBox(width: 4),
          const Text('🃏', style: TextStyle(fontSize: 22)),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  'Tìm Cặp Hình: ${widget.topic.titleVi}',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AppColors.textDark),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                Text(
                  'Đã tìm: $_matchesFound / $_totalPairs cặp',
                  style: const TextStyle(fontSize: 12, color: AppColors.textLight),
                ),
              ],
            ),
          ),

          // Timer & Turns
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
              color: const Color(0xFFFEF3C7),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Row(
              children: [
                const Icon(Icons.timer_outlined, size: 16, color: Color(0xFFD97706)),
                const SizedBox(width: 4),
                Text(
                  '${_secondsElapsed}s',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFFB45309)),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),

          // Difficulty toggle (3x4 or 4x6)
          InkWell(
            onTap: () {
              HapticFeedback.selectionClick();
              setState(() {
                _difficulty = _difficulty == '3x4' ? '4x6' : '3x4';
              });
              _initGame();
            },
            borderRadius: BorderRadius.circular(14),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: AppColors.primary.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppColors.primary.withValues(alpha: 0.3)),
              ),
              child: Text(
                _difficulty == '3x4' ? '12 Thẻ' : '24 Thẻ',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: AppColors.primary),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFlipCard(_CardModel card) {
    return GestureDetector(
      onTap: () => _onCardTap(card),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 250),
        decoration: BoxDecoration(
          color: card.isMatched
              ? const Color(0xFFDCFCE7)
              : card.isFlipped
                  ? Colors.white
                  : AppColors.primary,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(
            color: card.isMatched
                ? const Color(0xFF22C55E)
                : card.isFlipped
                    ? AppColors.primary.withValues(alpha: 0.4)
                    : Colors.white.withValues(alpha: 0.8),
            width: card.isMatched ? 2.5 : 2,
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.08),
              blurRadius: 6,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(16),
          child: card.isFlipped || card.isMatched
              ? _buildCardFront(card)
              : _buildCardBack(),
        ),
      ),
    );
  }

  Widget _buildCardFront(_CardModel card) {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Expanded(
          child: Padding(
            padding: const EdgeInsets.all(6),
            child: ImageHelper.buildSafeImage(
              card.item.primaryImage,
              fit: BoxFit.contain,
              fallback: Center(child: Text(card.item.emoji, style: const TextStyle(fontSize: 36))),
            ),
          ),
        ),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(vertical: 3),
          color: card.isMatched ? const Color(0xFF22C55E) : AppColors.primary,
          child: Text(
            card.item.nameVi,
            textAlign: TextAlign.center,
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.bold,
              fontSize: 11,
            ),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }

  Widget _buildCardBack() {
    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            AppColors.primary,
            AppColors.primary.withValues(alpha: 0.85),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: const Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text('🌟', style: TextStyle(fontSize: 28)),
            SizedBox(height: 2),
            Text(
              'Kids World',
              style: TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w900,
                fontSize: 10,
                letterSpacing: 0.5,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
