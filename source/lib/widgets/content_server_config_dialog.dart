import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../core/services/content_server_config_service.dart';
import '../core/theme/app_theme.dart';

class ContentServerConfigDialog extends StatefulWidget {
  final VoidCallback? onSaved;

  const ContentServerConfigDialog({super.key, this.onSaved});

  static Future<void> show(BuildContext context, {VoidCallback? onSaved}) async {
    await showDialog(
      context: context,
      builder: (context) => ContentServerConfigDialog(onSaved: onSaved),
    );
  }

  @override
  State<ContentServerConfigDialog> createState() => _ContentServerConfigDialogState();
}

class _ContentServerConfigDialogState extends State<ContentServerConfigDialog> {
  late TextEditingController _urlController;
  ServerInfo? _testResult;
  bool _isTesting = false;

  @override
  void initState() {
    super.initState();
    _urlController = TextEditingController(text: ContentServerConfigService.instance.currentServerUrl);
  }

  @override
  void dispose() {
    _urlController.dispose();
    super.dispose();
  }

  Future<void> _runConnectionTest() async {
    HapticFeedback.lightImpact();
    setState(() {
      _isTesting = true;
      _testResult = null;
    });

    final result = await ContentServerConfigService.instance.testConnection(_urlController.text);
    if (!mounted) return;

    setState(() {
      _isTesting = false;
      _testResult = result;
    });
  }

  Future<void> _saveAndClose() async {
    HapticFeedback.mediumImpact();
    final url = _urlController.text.trim();
    if (url.isEmpty) return;

    await ContentServerConfigService.instance.setServerUrl(url);
    if (!mounted) return;

    Navigator.of(context).pop();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Đã cập nhật máy chủ nội dung: $url'),
        behavior: SnackBarBehavior.floating,
      ),
    );
    widget.onSaved?.call();
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
        constraints: BoxConstraints(
          maxWidth: isLandscape ? 580 : 380,
          maxHeight: isLandscape ? 320 : 540,
        ),
        child: Padding(
          padding: const EdgeInsets.all(18.0),
          child: SingleChildScrollView(
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
                          padding: const EdgeInsets.all(6),
                          decoration: BoxDecoration(
                            color: AppColors.primary.withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: const Icon(Icons.dns_rounded, size: 18, color: AppColors.primary),
                        ),
                        const SizedBox(width: 8),
                        Text('Cấu Hình Domain Máy Chủ', style: AppTextStyles.configTitle),
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

                Text(
                  'Nhập địa chỉ Domain máy chủ lưu trữ (PHP Server) để App tải các gói chủ đề mới:',
                  style: AppTextStyles.configCaption.copyWith(fontSize: 11.5, color: AppColors.textBody),
                ),
                const SizedBox(height: 10),

                // Text Input
                TextField(
                  controller: _urlController,
                  style: AppTextStyles.configLabel.copyWith(fontSize: 13, fontFamily: 'monospace'),
                  decoration: InputDecoration(
                    prefixIcon: const Icon(Icons.link_rounded, size: 18, color: AppColors.primary),
                    hintText: 'http://10.0.2.2:8000',
                    hintStyle: AppTextStyles.configCaption.copyWith(fontFamily: 'monospace'),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(10),
                      borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
                    ),
                  ),
                ),
                const SizedBox(height: 10),

                // Quick presets
                Text('CHỌN NHANH ĐỊA CHỈ PHÙ HỢP:', style: AppTextStyles.configSection.copyWith(fontSize: 10)),
                const SizedBox(height: 6),
                Wrap(
                  spacing: 6,
                  runSpacing: 6,
                  children: [
                    _buildPresetChip(
                      label: 'Android Emulator',
                      url: ContentServerConfigService.defaultEmulatorUrl,
                      icon: Icons.android_rounded,
                    ),
                    _buildPresetChip(
                      label: 'Wi-Fi PC (192.168.2.6)',
                      url: ContentServerConfigService.defaultLocalIpUrl,
                      icon: Icons.wifi_rounded,
                    ),
                    _buildPresetChip(
                      label: 'Localhost (PC)',
                      url: 'http://localhost:8000',
                      icon: Icons.laptop_rounded,
                    ),
                  ],
                ),
                const SizedBox(height: 14),

                // Test Connection Button & Result Box
                Row(
                  children: [
                    OutlinedButton.icon(
                      onPressed: _isTesting ? null : _runConnectionTest,
                      icon: _isTesting
                          ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2))
                          : const Icon(Icons.bolt_rounded, size: 16),
                      label: Text(_isTesting ? 'Đang thử...' : 'Kiểm Tra Kết Nối', style: const TextStyle(fontSize: 11.5)),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppColors.primary,
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                    ),
                  ],
                ),

                if (_testResult != null) ...[
                  const SizedBox(height: 10),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: _testResult!.isOnline
                          ? AppColors.success.withValues(alpha: 0.1)
                          : AppColors.coralRed.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(
                        color: _testResult!.isOnline
                            ? AppColors.success.withValues(alpha: 0.3)
                            : AppColors.coralRed.withValues(alpha: 0.3),
                      ),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(
                          _testResult!.isOnline ? Icons.check_circle_rounded : Icons.error_rounded,
                          size: 18,
                          color: _testResult!.isOnline ? AppColors.success : AppColors.coralRed,
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                _testResult!.isOnline
                                    ? 'Kết nối thành công! (${_testResult!.latencyMs}ms)'
                                    : 'Kết nối thất bại!',
                                style: AppTextStyles.configLabel.copyWith(
                                  fontWeight: FontWeight.w700,
                                  color: _testResult!.isOnline ? AppColors.success : AppColors.coralRed,
                                  fontSize: 12,
                                ),
                              ),
                              if (_testResult!.isOnline)
                                Text(
                                  '${_testResult!.serverName} (v${_testResult!.version}) • Sẵn sàng ${_testResult!.totalPacks} gói chủ đề',
                                  style: AppTextStyles.configCaption.copyWith(fontSize: 11),
                                )
                              else if (_testResult!.errorMessage != null)
                                Text(
                                  _testResult!.errorMessage!,
                                  style: AppTextStyles.configCaption.copyWith(fontSize: 10.5, color: AppColors.coralRed),
                                ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ],

                const SizedBox(height: 16),

                // Save button
                SizedBox(
                  width: double.infinity,
                  height: 38,
                  child: ElevatedButton(
                    onPressed: _saveAndClose,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      elevation: 0,
                    ),
                    child: Text('Lưu Cấu Hình Domain', style: AppTextStyles.configButton.copyWith(color: Colors.white)),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildPresetChip({required String label, required String url, required IconData icon}) {
    final isSelected = _urlController.text.trim() == url;
    return InkWell(
      onTap: () {
        HapticFeedback.lightImpact();
        setState(() {
          _urlController.text = url;
          _testResult = null;
        });
      },
      borderRadius: BorderRadius.circular(8),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.primary.withValues(alpha: 0.12) : AppColors.surfaceMuted,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: isSelected ? AppColors.primary : AppColors.border),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 12, color: isSelected ? AppColors.primary : AppColors.textLight),
            const SizedBox(width: 4),
            Text(
              label,
              style: AppTextStyles.configCaption.copyWith(
                fontSize: 10.5,
                fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
                color: isSelected ? AppColors.primary : AppColors.textBody,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
