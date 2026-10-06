import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../core/database/database_helper.dart';
import '../core/theme/app_theme.dart';

class ChangePinDialog extends StatefulWidget {
  const ChangePinDialog({super.key});

  static Future<void> show(BuildContext context) async {
    await showDialog(context: context, builder: (context) => const ChangePinDialog());
  }

  @override
  State<ChangePinDialog> createState() => _ChangePinDialogState();
}

class _ChangePinDialogState extends State<ChangePinDialog> {
  final _oldPinController = TextEditingController();
  final _newPinController = TextEditingController();
  final _confirmPinController = TextEditingController();

  String _errorMessage = '';
  bool _isLoading = false;

  @override
  void dispose() {
    _oldPinController.dispose();
    _newPinController.dispose();
    _confirmPinController.dispose();
    super.dispose();
  }

  Future<void> _submitChangePin() async {
    HapticFeedback.lightImpact();
    final oldPin = _oldPinController.text.trim();
    final newPin = _newPinController.text.trim();
    final confirmPin = _confirmPinController.text.trim();

    final currentMasterPin = await DatabaseHelper.instance.getMasterPin();

    if (oldPin != currentMasterPin) {
      setState(() => _errorMessage = 'Mã PIN hiện tại không chính xác!');
      return;
    }

    if (newPin.length != 4 || int.tryParse(newPin) == null) {
      setState(() => _errorMessage = 'Mã PIN mới phải gồm đúng 4 chữ số!');
      return;
    }

    if (newPin != confirmPin) {
      setState(() => _errorMessage = 'Xác nhận mã PIN mới chưa khớp!');
      return;
    }

    setState(() => _isLoading = true);
    await DatabaseHelper.instance.updateMasterPin(newPin);
    if (!mounted) return;

    Navigator.of(context).pop();
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Đã cập nhật mã PIN phụ huynh mới thành công!'),
        behavior: SnackBarBehavior.floating,
        backgroundColor: AppColors.success,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.transparent,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
      insetPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 340),
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
                          color: AppColors.primary.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Icon(Icons.password_rounded, size: 16, color: AppColors.primary),
                      ),
                      const SizedBox(width: 8),
                      Text('Đổi Mã PIN Phụ Huynh', style: AppTextStyles.configTitle.copyWith(fontSize: 15)),
                    ],
                  ),
                  IconButton(
                    visualDensity: VisualDensity.compact,
                    padding: EdgeInsets.zero,
                    constraints: const BoxConstraints(),
                    icon: const Icon(Icons.close_rounded, size: 18, color: AppColors.textLight),
                    onPressed: () => Navigator.of(context).pop(),
                  ),
                ],
              ),
              const SizedBox(height: 14),

              // Old PIN field
              _buildPinField(label: 'MÃ PIN HIỆN TẠI', controller: _oldPinController, hintText: 'Nhập mã PIN cũ'),
              const SizedBox(height: 10),

              // New PIN field
              _buildPinField(label: 'MÃ PIN MỚI (4 SỐ)', controller: _newPinController, hintText: 'Nhập 4 số mới'),
              const SizedBox(height: 10),

              // Confirm PIN field
              _buildPinField(
                label: 'XÁC NHẬN MÃ PIN MỚI',
                controller: _confirmPinController,
                hintText: 'Nhập lại 4 số mới',
              ),

              if (_errorMessage.isNotEmpty) ...[
                const SizedBox(height: 8),
                Text(
                  _errorMessage,
                  style: AppTextStyles.configCaption.copyWith(color: Colors.red.shade600, fontSize: 11.5),
                ),
              ],
              const SizedBox(height: 14),

              // Save button
              SizedBox(
                width: double.infinity,
                height: 38,
                child: ElevatedButton(
                  onPressed: _isLoading ? null : _submitChangePin,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    foregroundColor: Colors.white,
                    elevation: 0,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  child: _isLoading
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : Text('Lưu Mã PIN Mới', style: AppTextStyles.configButton),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildPinField({required String label, required TextEditingController controller, required String hintText}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: AppTextStyles.configSection),
        const SizedBox(height: 4),
        SizedBox(
          height: 38,
          child: TextField(
            controller: controller,
            keyboardType: TextInputType.number,
            obscureText: true,
            maxLength: 4,
            style: AppTextStyles.configValue.copyWith(fontSize: 14),
            decoration: InputDecoration(
              counterText: '',
              hintText: hintText,
              hintStyle: AppTextStyles.configCaption.copyWith(fontSize: 12),
              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              filled: true,
              fillColor: AppColors.surfaceMuted,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(8),
                borderSide: const BorderSide(color: AppColors.primary, width: 1.2),
              ),
            ),
          ),
        ),
      ],
    );
  }
}
