import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../core/database/database_helper.dart';
import '../../core/theme/app_theme.dart';
import '../../models/child_model.dart';
import '../../models/screen_time_model.dart';
import '../../core/services/screen_time_service.dart';
import '../../core/services/content_server_config_service.dart';
import '../../widgets/change_pin_dialog.dart';
import '../../widgets/content_server_config_dialog.dart';
import '../topic_store/topic_store_screen.dart';

class ParentSettingsDialog extends StatefulWidget {
  final VoidCallback? onSettingsChanged;

  const ParentSettingsDialog({super.key, this.onSettingsChanged});

  static Future<void> show(BuildContext context, {VoidCallback? onSettingsChanged}) async {
    await showDialog(
      context: context,
      builder: (context) => ParentSettingsDialog(onSettingsChanged: onSettingsChanged),
    );
  }

  @override
  State<ParentSettingsDialog> createState() => _ParentSettingsDialogState();
}

class _ParentSettingsDialogState extends State<ParentSettingsDialog> {
  List<Child> _children = [];
  Child? _selectedChild;
  bool _isLoading = true;

  int _dailyMinutes = 30;
  int _weekendMinutes = 45;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    final children = await DatabaseHelper.instance.getChildren();
    if (!mounted) return;

    setState(() {
      _children = children;
      if (children.isNotEmpty) {
        _selectedChild = children.first;
      }
    });

    if (_selectedChild != null) {
      await _loadSettingsForChild(_selectedChild!);
    } else {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _loadSettingsForChild(Child child) async {
    final settings = await DatabaseHelper.instance.getScreenTimeSettings(child.id);
    if (!mounted) return;
    setState(() {
      _selectedChild = child;
      _dailyMinutes = settings.dailyLimitMinutes;
      _weekendMinutes = settings.weekendLimitMinutes;
      _isLoading = false;
    });
  }

  Future<void> _saveScreenTimeSettings() async {
    if (_selectedChild == null) return;
    HapticFeedback.lightImpact();

    final pin = await DatabaseHelper.instance.getMasterPin();
    final updated = ScreenTimeSettings(
      childId: _selectedChild!.id,
      dailyLimitMinutes: _dailyMinutes,
      weekendLimitMinutes: _weekendMinutes,
      pinCode: pin,
    );

    await DatabaseHelper.instance.updateScreenTimeSettings(updated);
    await ScreenTimeService.instance.refreshSettings();
    if (!mounted) return;

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Đã lưu thời gian học cho ${_selectedChild!.name}!'), behavior: SnackBarBehavior.floating),
    );

    widget.onSettingsChanged?.call();
  }

  Future<void> _confirmDeleteChild(Child child) async {
    if (_children.length <= 1) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Cần giữ lại ít nhất 1 hồ sơ bé trong hệ thống!'),
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Text('Xóa hồ sơ bé ${child.name}?', style: AppTextStyles.configTitle),
        content: Text(
          'Tất cả tiến độ học và lịch sử học tập của bé sẽ bị xóa vĩnh viễn.',
          style: AppTextStyles.configCaption.copyWith(fontSize: 13),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: Text('Hủy', style: AppTextStyles.configLabel.copyWith(color: AppColors.textLight)),
          ),
          ElevatedButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red.shade500,
              foregroundColor: Colors.white,
              elevation: 0,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            child: const Text('Xác nhận xóa'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      await DatabaseHelper.instance.deleteChild(child.id);
      widget.onSettingsChanged?.call();
      await _loadData();
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
        constraints: BoxConstraints(maxWidth: isLandscape ? 660 : 400, maxHeight: isLandscape ? 340 : 580),
        child: Padding(
          padding: const EdgeInsets.fromLTRB(18, 14, 18, 14),
          child: _isLoading
              ? const Center(child: CircularProgressIndicator())
              : isLandscape
              ? _buildLandscapeLayout()
              : _buildPortraitLayout(),
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Row(
          children: [
            Container(
              padding: const EdgeInsets.all(5),
              decoration: BoxDecoration(
                color: AppColors.primary.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(Icons.tune_rounded, size: 18, color: AppColors.primary),
            ),
            const SizedBox(width: 8),
            Text('Cài Đặt Phụ Huynh (Settings)', style: AppTextStyles.configTitle),
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
    );
  }

  Widget _buildChildSelector() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('CHỌN BÉ ĐỂ CẤU HÌNH THỜI GIAN', style: AppTextStyles.configSection),
        const SizedBox(height: 6),
        SizedBox(
          height: 38,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: _children.length,
            separatorBuilder: (_, _) => const SizedBox(width: 8),
            itemBuilder: (context, index) {
              final child = _children[index];
              final isSelected = _selectedChild?.id == child.id;

              return InkWell(
                onTap: () => _loadSettingsForChild(child),
                borderRadius: BorderRadius.circular(8),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 180),
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    color: isSelected ? AppColors.primary.withValues(alpha: 0.12) : AppColors.surfaceMuted,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(
                      color: isSelected ? AppColors.primary : AppColors.border,
                      width: isSelected ? 1.2 : 1,
                    ),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        (child.avatarUrl != null &&
                                child.avatarUrl!.isNotEmpty &&
                                !child.avatarUrl!.startsWith('assets/'))
                            ? child.avatarUrl!
                            : (child.gender == 'female' ? '👧' : '👦'),
                        style: const TextStyle(fontSize: 15),
                      ),
                      const SizedBox(width: 6),
                      Text(
                        child.name,
                        style: AppTextStyles.configLabel.copyWith(
                          fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
                          color: isSelected ? AppColors.primary : AppColors.textDark,
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        ),
      ],
    );
  }

  Widget _buildScreenTimeSection() {
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: AppColors.surfaceMuted,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  const Icon(Icons.timer_outlined, size: 14, color: AppColors.secondary),
                  const SizedBox(width: 6),
                  Text('GIỚI HẠN THỜI GIAN HỌC', style: AppTextStyles.configSection),
                ],
              ),
              InkWell(
                onTap: _saveScreenTimeSettings,
                borderRadius: BorderRadius.circular(6),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(color: AppColors.primary, borderRadius: BorderRadius.circular(6)),
                  child: Text('Lưu', style: AppTextStyles.configButton.copyWith(color: Colors.white, fontSize: 11)),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          _buildCompactStepper(
            label: 'Ngày thường (T2 - T6)',
            minutes: _dailyMinutes,
            color: AppColors.secondary,
            onDecrease: _dailyMinutes > 15 ? () => setState(() => _dailyMinutes -= 15) : null,
            onIncrease: _dailyMinutes < 120 ? () => setState(() => _dailyMinutes += 15) : null,
          ),
          const SizedBox(height: 6),
          _buildCompactStepper(
            label: 'Cuối tuần (T7 - CN)',
            minutes: _weekendMinutes,
            color: AppColors.accent,
            onDecrease: _weekendMinutes > 15 ? () => setState(() => _weekendMinutes -= 15) : null,
            onIncrease: _weekendMinutes < 180 ? () => setState(() => _weekendMinutes += 15) : null,
          ),
        ],
      ),
    );
  }

  Widget _buildCompactStepper({
    required String label,
    required int minutes,
    required Color color,
    required VoidCallback? onDecrease,
    required VoidCallback? onIncrease,
  }) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: AppTextStyles.configLabel.copyWith(fontSize: 11.5)),
        Row(
          children: [
            _buildMiniButton(Icons.remove, onDecrease),
            SizedBox(
              width: 54,
              child: Text(
                '$minutes p',
                textAlign: TextAlign.center,
                style: AppTextStyles.configValue.copyWith(fontSize: 12),
              ),
            ),
            _buildMiniButton(Icons.add, onIncrease),
          ],
        ),
      ],
    );
  }

  Widget _buildMiniButton(IconData icon, VoidCallback? onPressed) {
    return InkWell(
      onTap: onPressed,
      borderRadius: BorderRadius.circular(5),
      child: Container(
        width: 24,
        height: 24,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(5),
          border: Border.all(color: AppColors.border),
        ),
        child: Icon(icon, size: 13, color: onPressed != null ? AppColors.textDark : AppColors.textMuted),
      ),
    );
  }

  Widget _buildSecuritySection() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: AppColors.surfaceMuted,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              const Icon(Icons.lock_reset_rounded, size: 16, color: AppColors.purple),
              const SizedBox(width: 8),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('MÃ PIN PHỤ HUYNH', style: AppTextStyles.configSection),
                  Text('Bảo vệ cài đặt & thêm bé', style: AppTextStyles.configCaption.copyWith(fontSize: 11)),
                ],
              ),
            ],
          ),
          OutlinedButton(
            onPressed: () => ChangePinDialog.show(context),
            style: OutlinedButton.styleFrom(
              foregroundColor: AppColors.purple,
              side: BorderSide(color: AppColors.purple.withValues(alpha: 0.4)),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              minimumSize: const Size(60, 28),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
            ),
            child: Text('Đổi PIN', style: AppTextStyles.configButton.copyWith(fontSize: 11, color: AppColors.purple)),
          ),
        ],
      ),
    );
  }

  Widget _buildContentPackagesSection() {
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: AppColors.surfaceMuted,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  const Icon(Icons.cloud_download_outlined, size: 16, color: AppColors.success),
                  const SizedBox(width: 8),
                  Text('KHO GÓI CHỦ ĐỀ HỌC TẬP', style: AppTextStyles.configSection),
                ],
              ),
              ElevatedButton.icon(
                onPressed: () {
                  HapticFeedback.lightImpact();
                  Navigator.of(context).push(
                    MaterialPageRoute(builder: (context) => const TopicStoreScreen()),
                  ).then((_) => widget.onSettingsChanged?.call());
                },
                icon: const Icon(Icons.shopping_bag_outlined, size: 14, color: Colors.white),
                label: Text('Mở Kho Gói', style: AppTextStyles.configButton.copyWith(fontSize: 11, color: Colors.white)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.success,
                  elevation: 0,
                  minimumSize: const Size(60, 28),
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          // Server Domain Config Row
          ListenableBuilder(
            listenable: ContentServerConfigService.instance,
            builder: (context, _) {
              final serverUrl = ContentServerConfigService.instance.currentServerUrl;
              return Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: AppColors.border),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.dns_rounded, size: 14, color: AppColors.primary),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Domain máy chủ lưu trữ:', style: AppTextStyles.configCaption.copyWith(fontSize: 10)),
                          Text(
                            serverUrl,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: AppTextStyles.configCaption.copyWith(
                              fontFamily: 'monospace',
                              fontWeight: FontWeight.w600,
                              color: AppColors.primary,
                              fontSize: 11,
                            ),
                          ),
                        ],
                      ),
                    ),
                    InkWell(
                      onTap: () {
                        HapticFeedback.lightImpact();
                        ContentServerConfigDialog.show(context);
                      },
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        child: Text(
                          'Cấu hình',
                          style: AppTextStyles.configCaption.copyWith(
                            color: AppColors.secondary,
                            fontWeight: FontWeight.w700,
                            fontSize: 11,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  Widget _buildManageChildrenSection() {
    if (_children.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('HỒ SƠ BÉ HIỆN TẠI', style: AppTextStyles.configSection),
        const SizedBox(height: 6),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
          decoration: BoxDecoration(
            color: AppColors.surfaceMuted,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: AppColors.border),
          ),
          child: Column(
            children: _children.map((c) {
              return Padding(
                padding: const EdgeInsets.symmetric(vertical: 3.0),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Text(
                          (c.avatarUrl != null && c.avatarUrl!.isNotEmpty && !c.avatarUrl!.startsWith('assets/'))
                              ? c.avatarUrl!
                              : (c.gender == 'female' ? '👧' : '👦'),
                          style: const TextStyle(fontSize: 16),
                        ),
                        const SizedBox(width: 8),
                        Text('${c.name} (${c.age} tuổi)', style: AppTextStyles.configLabel.copyWith(fontSize: 12)),
                      ],
                    ),
                    IconButton(
                      visualDensity: VisualDensity.compact,
                      padding: EdgeInsets.zero,
                      constraints: const BoxConstraints(),
                      icon: const Icon(Icons.delete_outline_rounded, size: 16, color: Colors.redAccent),
                      tooltip: 'Xóa hồ sơ bé',
                      onPressed: () => _confirmDeleteChild(c),
                    ),
                  ],
                ),
              );
            }).toList(),
          ),
        ),
      ],
    );
  }

  Widget _buildPortraitLayout() {
    return SingleChildScrollView(
      physics: const ClampingScrollPhysics(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          _buildHeader(),
          const SizedBox(height: 10),
          _buildChildSelector(),
          const SizedBox(height: 8),
          _buildScreenTimeSection(),
          const SizedBox(height: 8),
          _buildSecuritySection(),
          const SizedBox(height: 8),
          _buildContentPackagesSection(),
          const SizedBox(height: 8),
          _buildManageChildrenSection(),
        ],
      ),
    );
  }

  Widget _buildLandscapeLayout() {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        _buildHeader(),
        const SizedBox(height: 8),
        Expanded(
          child: SingleChildScrollView(
            physics: const ClampingScrollPhysics(),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Cột trái: Chọn bé & Cấu hình thời gian
                Expanded(
                  flex: 5,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [_buildChildSelector(), const SizedBox(height: 8), _buildScreenTimeSection()],
                  ),
                ),
                const SizedBox(width: 14),
                // Cột phải: Đổi PIN & Gói nội dung & Quản lý bé
                Expanded(
                  flex: 5,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _buildSecuritySection(),
                      const SizedBox(height: 8),
                      _buildContentPackagesSection(),
                      const SizedBox(height: 8),
                      _buildManageChildrenSection(),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}
