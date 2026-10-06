import 'dart:math';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../core/database/database_helper.dart';
import '../core/theme/app_theme.dart';

class ParentGateDialog extends StatefulWidget {
  final String correctPin;
  final String title;

  const ParentGateDialog({super.key, this.correctPin = '1234', this.title = 'Khu Vực Dành Cho Bố Mẹ'});

  static Future<bool> verify(
    BuildContext context, {
    String? correctPin,
    String title = 'Khu Vực Dành Cho Bố Mẹ',
  }) async {
    final pin = correctPin ?? await DatabaseHelper.instance.getMasterPin();
    if (!context.mounted) return false;
    final result = await showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (context) => ParentGateDialog(correctPin: pin, title: title),
    );
    return result ?? false;
  }

  @override
  State<ParentGateDialog> createState() => _ParentGateDialogState();
}

class _ParentGateDialogState extends State<ParentGateDialog> {
  bool _useMathChallenge = true;
  late int _num1;
  late int _num2;
  late int _mathAnswer;
  final TextEditingController _mathController = TextEditingController();
  final FocusNode _mathFocusNode = FocusNode();
  String _pinEntered = '';
  String _errorMessage = '';

  @override
  void initState() {
    super.initState();
    _generateMathProblem();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _mathFocusNode.requestFocus();
    });
  }

  @override
  void dispose() {
    _mathController.dispose();
    _mathFocusNode.dispose();
    super.dispose();
  }

  void _generateMathProblem() {
    final random = Random();
    final isMultiply = random.nextBool(); // 50% + or 50% *
    if (isMultiply) {
      // Strictly single-digit multiplication only (2 to 9)
      _num1 = random.nextInt(8) + 2;
      _num2 = random.nextInt(8) + 2;
      _mathAnswer = _num1 * _num2;
    } else {
      // 2-digit addition suitable for adults
      _num1 = random.nextInt(35) + 15;
      _num2 = random.nextInt(30) + 10;
      _mathAnswer = _num1 + _num2;
    }
    _mathController.clear();
  }

  void _verifyMath() {
    HapticFeedback.lightImpact();
    final entered = int.tryParse(_mathController.text.trim());
    if (entered == _mathAnswer) {
      Navigator.of(context).pop(true);
    } else {
      setState(() {
        _errorMessage = 'Kết quả chưa đúng, hãy thử lại!';
        _generateMathProblem();
      });
      _mathController.clear();
      _mathFocusNode.requestFocus();
    }
  }

  void _verifyPin(String digit) {
    HapticFeedback.lightImpact();
    if (_pinEntered.length < 4) {
      setState(() {
        _pinEntered += digit;
        _errorMessage = '';
      });
      if (_pinEntered.length == 4) {
        if (_pinEntered == widget.correctPin) {
          Navigator.of(context).pop(true);
        } else {
          setState(() {
            _errorMessage = 'Mã PIN chưa chính xác!';
            _pinEntered = '';
          });
        }
      }
    }
  }

  void _backspacePin() {
    HapticFeedback.lightImpact();
    if (_pinEntered.isNotEmpty) {
      setState(() {
        _pinEntered = _pinEntered.substring(0, _pinEntered.length - 1);
        _errorMessage = '';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final isLandscape = MediaQuery.of(context).orientation == Orientation.landscape;

    return Dialog(
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.transparent,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
      insetPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: ConstrainedBox(
        constraints: BoxConstraints(maxWidth: isLandscape ? 560 : 330, maxHeight: isLandscape ? 280 : 440),
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: isLandscape ? _buildLandscapeLayout() : _buildPortraitLayout(),
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(5),
          decoration: BoxDecoration(
            color: AppColors.primary.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: const Icon(Icons.lock_outline_rounded, size: 16, color: AppColors.primary),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            widget.title,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: AppTextStyles.configTitle.copyWith(fontSize: 14.5),
          ),
        ),
        IconButton(
          visualDensity: VisualDensity.compact,
          padding: EdgeInsets.zero,
          constraints: const BoxConstraints(),
          icon: const Icon(Icons.close_rounded, size: 18, color: AppColors.textLight),
          onPressed: () => Navigator.of(context).pop(false),
        ),
      ],
    );
  }

  Widget _buildModeToggle() {
    return Container(
      height: 32,
      padding: const EdgeInsets.all(2),
      decoration: BoxDecoration(color: AppColors.surfaceMuted, borderRadius: BorderRadius.circular(8)),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          _buildToggleOption(
            title: 'Giải toán',
            isSelected: _useMathChallenge,
            onTap: () => setState(() {
              _useMathChallenge = true;
              _errorMessage = '';
            }),
          ),
          _buildToggleOption(
            title: 'Mã PIN',
            isSelected: !_useMathChallenge,
            onTap: () => setState(() {
              _useMathChallenge = false;
              _errorMessage = '';
            }),
          ),
        ],
      ),
    );
  }

  Widget _buildToggleOption({required String title, required bool isSelected, required VoidCallback onTap}) {
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        padding: const EdgeInsets.symmetric(horizontal: 14),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: isSelected ? Colors.white : Colors.transparent,
          borderRadius: BorderRadius.circular(6),
          boxShadow: isSelected
              ? [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 4, offset: const Offset(0, 1))]
              : null,
        ),
        child: Text(
          title,
          style: AppTextStyles.configLabel.copyWith(
            fontSize: 12,
            fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
            color: isSelected ? AppColors.primary : AppColors.textLight,
          ),
        ),
      ),
    );
  }

  Widget _buildMathChallenge() {
    final isMultiply = _mathAnswer == _num1 * _num2;

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
          decoration: BoxDecoration(
            color: AppColors.surfaceMuted,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: AppColors.border),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                '$_num1 ${isMultiply ? '×' : '+'} $_num2 = ',
                style: AppTextStyles.configValue.copyWith(fontSize: 18),
              ),
              SizedBox(
                width: 70,
                height: 36,
                child: TextField(
                  controller: _mathController,
                  focusNode: _mathFocusNode,
                  keyboardType: TextInputType.number,
                  autofocus: true,
                  textAlign: TextAlign.center,
                  style: AppTextStyles.configValue.copyWith(fontSize: 17),
                  decoration: InputDecoration(
                    hintText: '?',
                    hintStyle: AppTextStyles.configCaption.copyWith(fontSize: 16),
                    contentPadding: EdgeInsets.zero,
                    filled: true,
                    fillColor: Colors.white,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                      borderSide: const BorderSide(color: AppColors.border),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                      borderSide: const BorderSide(color: AppColors.primary, width: 1.2),
                    ),
                  ),
                  onSubmitted: (_) => _verifyMath(),
                ),
              ),
            ],
          ),
        ),
        if (_errorMessage.isNotEmpty) ...[
          const SizedBox(height: 6),
          Text(_errorMessage, style: AppTextStyles.configCaption.copyWith(color: Colors.red.shade600)),
        ],
        const SizedBox(height: 12),
        SizedBox(
          width: double.infinity,
          height: 38,
          child: ElevatedButton(
            onPressed: _verifyMath,
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
              elevation: 0,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            child: Text('Xác Nhận', style: AppTextStyles.configButton),
          ),
        ),
      ],
    );
  }

  Widget _buildPinChallenge({required bool isLandscape}) {
    final btnSize = isLandscape ? 34.0 : 40.0;
    final fontSize = isLandscape ? 14.0 : 16.0;

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        // 4 dots
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: List.generate(4, (index) {
            final isFilled = index < _pinEntered.length;
            return Container(
              margin: const EdgeInsets.symmetric(horizontal: 5),
              width: 10,
              height: 10,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: isFilled ? AppColors.primary : AppColors.surfaceMuted,
                border: Border.all(color: isFilled ? AppColors.primary : AppColors.border, width: 1.2),
              ),
            );
          }),
        ),
        if (_errorMessage.isNotEmpty) ...[
          const SizedBox(height: 4),
          Text(_errorMessage, style: AppTextStyles.configCaption.copyWith(color: Colors.red.shade600, fontSize: 11)),
        ],
        const SizedBox(height: 10),
        // Keypad
        _buildCompactKeypad(btnSize, fontSize),
      ],
    );
  }

  Widget _buildCompactKeypad(double btnSize, double fontSize) {
    Widget buildBtn(String val, {IconData? icon, VoidCallback? onTap}) {
      return SizedBox(
        width: btnSize,
        height: btnSize,
        child: InkWell(
          onTap: onTap ?? () => _verifyPin(val),
          borderRadius: BorderRadius.circular(8),
          child: Container(
            decoration: BoxDecoration(
              color: AppColors.surfaceMuted,
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: AppColors.border, width: 0.8),
            ),
            alignment: Alignment.center,
            child: icon != null
                ? Icon(icon, size: 16, color: AppColors.textLight)
                : Text(val, style: AppTextStyles.configValue.copyWith(fontSize: fontSize)),
          ),
        ),
      );
    }

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [buildBtn('1'), const SizedBox(width: 8), buildBtn('2'), const SizedBox(width: 8), buildBtn('3')],
        ),
        const SizedBox(height: 6),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [buildBtn('4'), const SizedBox(width: 8), buildBtn('5'), const SizedBox(width: 8), buildBtn('6')],
        ),
        const SizedBox(height: 6),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [buildBtn('7'), const SizedBox(width: 8), buildBtn('8'), const SizedBox(width: 8), buildBtn('9')],
        ),
        const SizedBox(height: 6),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            SizedBox(width: btnSize, height: btnSize),
            const SizedBox(width: 8),
            buildBtn('0'),
            const SizedBox(width: 8),
            buildBtn('', icon: Icons.backspace_outlined, onTap: _backspacePin),
          ],
        ),
      ],
    );
  }

  Widget _buildPortraitLayout() {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        _buildHeader(),
        const SizedBox(height: 10),
        _buildModeToggle(),
        const SizedBox(height: 14),
        if (_useMathChallenge) _buildMathChallenge() else _buildPinChallenge(isLandscape: false),
      ],
    );
  }

  Widget _buildLandscapeLayout() {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        _buildHeader(),
        const SizedBox(height: 12),
        Row(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            // Left Column: Instructions + Toggle
            Expanded(
              flex: 5,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Bố mẹ giải phép tính hoặc nhập mã PIN phụ huynh để tiếp tục:',
                    style: AppTextStyles.configCaption.copyWith(fontSize: 11.5, color: AppColors.textBody),
                  ),
                  const SizedBox(height: 12),
                  _buildModeToggle(),
                ],
              ),
            ),
            const SizedBox(width: 16),
            Container(width: 1, height: 140, color: AppColors.border),
            const SizedBox(width: 16),
            // Right Column: Challenge
            Expanded(
              flex: 5,
              child: Center(child: _useMathChallenge ? _buildMathChallenge() : _buildPinChallenge(isLandscape: true)),
            ),
          ],
        ),
      ],
    );
  }
}
