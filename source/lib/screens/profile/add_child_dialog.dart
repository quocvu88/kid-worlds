import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:uuid/uuid.dart';

import '../../core/database/database_helper.dart';
import '../../core/theme/app_theme.dart';
import '../../models/child_model.dart';
import '../../models/screen_time_model.dart';

class AddChildDialog extends StatefulWidget {
  final VoidCallback onChildAdded;

  const AddChildDialog({super.key, required this.onChildAdded});

  static Future<void> show(BuildContext context, {required VoidCallback onChildAdded}) async {
    await showDialog(
      context: context,
      builder: (context) => AddChildDialog(onChildAdded: onChildAdded),
    );
  }

  static const List<Map<String, String>> boyAvatars = [
    {'emoji': '👦', 'label': 'Bé Trai'},
    {'emoji': '🦁', 'label': 'Sư Tử'},
    {'emoji': '🚀', 'label': 'Tàu Bay'},
    {'emoji': '🦖', 'label': 'Khủng Long'},
    {'emoji': '🚗', 'label': 'Xe Đua'},
    {'emoji': '🐻', 'label': 'Gấu Con'},
    {'emoji': '⚽', 'label': 'Bóng Đá'},
    {'emoji': '🦊', 'label': 'Cáo Vàng'},
    {'emoji': '🦸‍♂️', 'label': 'Siêu Nhân'},
    {'emoji': '🐼', 'label': 'Gấu Trúc'},
  ];

  static const List<Map<String, String>> girlAvatars = [
    {'emoji': '👧', 'label': 'Bé Gái'},
    {'emoji': '🦄', 'label': 'Kỳ Lân'},
    {'emoji': '🌸', 'label': 'Hoa Nhỏ'},
    {'emoji': '🐱', 'label': 'Mèo Con'},
    {'emoji': '🐰', 'label': 'Thỏ Trắng'},
    {'emoji': '👑', 'label': 'Công Chúa'},
    {'emoji': '🍓', 'label': 'Dâu Tây'},
    {'emoji': '🦋', 'label': 'Bướm Xinh'},
    {'emoji': '🧚‍♀️', 'label': 'Tiên Nhí'},
    {'emoji': '🐬', 'label': 'Cá Heo'},
  ];

  @override
  State<AddChildDialog> createState() => _AddChildDialogState();
}

class _AddChildDialogState extends State<AddChildDialog> {
  final _nameController = TextEditingController();
  String _gender = 'male';
  String _selectedAvatar = '👦';
  int _age = 4;
  int _dailyLimitMinutes = 30;
  int _weekendLimitMinutes = 45;

  @override
  void dispose() {
    _nameController.dispose();
    super.dispose();
  }

  void _onGenderChanged(String newGender) {
    if (_gender == newGender) return;
    setState(() {
      _gender = newGender;
      _selectedAvatar = newGender == 'female' ? '👧' : '👦';
    });
  }

  Future<void> _saveChild() async {
    HapticFeedback.lightImpact();
    final name = _nameController.text.trim();
    if (name.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Vui lòng nhập tên hoặc biệt danh của bé'), behavior: SnackBarBehavior.floating),
      );
      return;
    }

    final currentYear = DateTime.now().year;
    final birthYear = currentYear - _age;

    final newChild = Child(
      id: const Uuid().v4(),
      name: name,
      gender: _gender,
      birthYear: birthYear,
      avatarUrl: _selectedAvatar,
    );

    final navigator = Navigator.of(context);

    // Lấy master PIN từ SharedPreferences/Database
    final masterPin = await DatabaseHelper.instance.getMasterPin();

    final newSettings = ScreenTimeSettings(
      childId: newChild.id,
      dailyLimitMinutes: _dailyLimitMinutes,
      weekendLimitMinutes: _weekendLimitMinutes,
      pinCode: masterPin,
    );

    await DatabaseHelper.instance.insertChild(newChild, newSettings);
    if (!mounted) return;
    navigator.pop();
    widget.onChildAdded();
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
        constraints: BoxConstraints(maxWidth: isLandscape ? 620 : 380, maxHeight: isLandscape ? 335 : 550),
        child: Padding(
          padding: const EdgeInsets.fromLTRB(18, 14, 18, 14),
          child: isLandscape ? _buildLandscapeLayout() : _buildPortraitLayout(),
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
                color: AppColors.primary.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(Icons.person_add_alt_1_rounded, size: 16, color: AppColors.primary),
            ),
            const SizedBox(width: 8),
            Text('Thêm Hồ Sơ Bé', style: AppTextStyles.configTitle),
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

  Widget _buildNameField() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('TÊN HOẶC BIỆT DANH', style: AppTextStyles.configSection),
        const SizedBox(height: 5),
        SizedBox(
          height: 38,
          child: TextField(
            controller: _nameController,
            style: AppTextStyles.configValue.copyWith(fontSize: 13.5),
            decoration: InputDecoration(
              hintText: 'Ví dụ: Bé Bắp, Bé Bon...',
              hintStyle: AppTextStyles.configCaption.copyWith(fontSize: 12.5),
              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              prefixIcon: const Icon(Icons.badge_outlined, size: 16, color: AppColors.textLight),
              filled: true,
              fillColor: AppColors.surfaceMuted,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(9), borderSide: BorderSide.none),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(9),
                borderSide: const BorderSide(color: AppColors.primary, width: 1.2),
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildGenderSelector() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('GIỚI TÍNH', style: AppTextStyles.configSection),
        const SizedBox(height: 5),
        Row(
          children: [
            Expanded(
              child: _buildGenderOption(
                label: 'Bé Trai 👦',
                isSelected: _gender == 'male',
                activeColor: AppColors.secondary,
                onTap: () => _onGenderChanged('male'),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: _buildGenderOption(
                label: 'Bé Gái 👧',
                isSelected: _gender == 'female',
                activeColor: AppColors.primary,
                onTap: () => _onGenderChanged('female'),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildGenderOption({
    required String label,
    required bool isSelected,
    required Color activeColor,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        height: 34,
        decoration: BoxDecoration(
          color: isSelected ? activeColor.withValues(alpha: 0.12) : AppColors.surfaceMuted,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: isSelected ? activeColor : Colors.transparent, width: 1.2),
        ),
        alignment: Alignment.center,
        child: Text(
          label,
          style: AppTextStyles.configLabel.copyWith(
            fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
            color: isSelected ? activeColor : AppColors.textBody,
            fontSize: 12,
          ),
        ),
      ),
    );
  }

  Widget _buildAvatarSelector() {
    final avatarList = _gender == 'female' ? AddChildDialog.girlAvatars : AddChildDialog.boyAvatars;
    final activeColor = _gender == 'female' ? AppColors.primary : AppColors.secondary;

    final currentAvatarLabel = avatarList.firstWhere(
      (item) => item['emoji'] == _selectedAvatar,
      orElse: () => {'emoji': _selectedAvatar, 'label': 'Đã chọn'},
    )['label']!;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text('CHỌN AVATAR CHO BÉ', style: AppTextStyles.configSection),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
              decoration: BoxDecoration(
                color: activeColor.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(6),
              ),
              child: Text(
                '$_selectedAvatar $currentAvatarLabel',
                style: AppTextStyles.configCaption.copyWith(
                  color: activeColor,
                  fontWeight: FontWeight.w600,
                  fontSize: 11,
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 6),
        SizedBox(
          height: 42,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: avatarList.length,
            separatorBuilder: (_, _) => const SizedBox(width: 7),
            itemBuilder: (context, index) {
              final item = avatarList[index];
              final emoji = item['emoji']!;
              final isSelected = emoji == _selectedAvatar;

              return InkWell(
                onTap: () {
                  HapticFeedback.selectionClick();
                  setState(() => _selectedAvatar = emoji);
                },
                borderRadius: BorderRadius.circular(21),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 180),
                  width: 38,
                  height: 38,
                  decoration: BoxDecoration(
                    color: isSelected ? activeColor.withValues(alpha: 0.16) : AppColors.surfaceMuted,
                    shape: BoxShape.circle,
                    border: Border.all(color: isSelected ? activeColor : Colors.transparent, width: 1.8),
                  ),
                  alignment: Alignment.center,
                  child: Text(emoji, style: TextStyle(fontSize: isSelected ? 21 : 18)),
                ),
              );
            },
          ),
        ),
      ],
    );
  }

  Widget _buildAgeSelector() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text('ĐỘ TUỔI HIỆN TẠI', style: AppTextStyles.configSection),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
              decoration: BoxDecoration(
                color: AppColors.purple.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(6),
              ),
              child: Text(
                '$_age tuổi',
                style: AppTextStyles.configValue.copyWith(fontSize: 11.5, color: AppColors.purple),
              ),
            ),
          ],
        ),
        const SizedBox(height: 2),
        SliderTheme(
          data: SliderTheme.of(context).copyWith(
            trackHeight: 3,
            thumbShape: const RoundSliderThumbShape(enabledThumbRadius: 6),
            overlayShape: const RoundSliderOverlayShape(overlayRadius: 12),
            activeTrackColor: AppColors.purple,
            thumbColor: AppColors.purple,
            inactiveTrackColor: AppColors.border,
          ),
          child: Slider(
            value: _age.toDouble(),
            min: 2,
            max: 10,
            divisions: 8,
            onChanged: (val) => setState(() => _age = val.toInt()),
          ),
        ),
      ],
    );
  }

  Widget _buildTimeLimitsSection() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: AppColors.surfaceMuted,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.timer_outlined, size: 13, color: AppColors.textLight),
              const SizedBox(width: 5),
              Text('ĐỊNH MỨC THỜI GIAN SỬ DỤNG', style: AppTextStyles.configSection.copyWith(fontSize: 10.5)),
            ],
          ),
          const SizedBox(height: 6),
          _buildCompactLimitRow(
            label: 'Ngày thường (T2 - T6)',
            minutes: _dailyLimitMinutes,
            color: AppColors.secondary,
            onDecrease: _dailyLimitMinutes > 15 ? () => setState(() => _dailyLimitMinutes -= 15) : null,
            onIncrease: _dailyLimitMinutes < 90 ? () => setState(() => _dailyLimitMinutes += 15) : null,
          ),
          const SizedBox(height: 4),
          _buildCompactLimitRow(
            label: 'Cuối tuần (T7 - CN)',
            minutes: _weekendLimitMinutes,
            color: AppColors.accent,
            onDecrease: _weekendLimitMinutes > 15 ? () => setState(() => _weekendLimitMinutes -= 15) : null,
            onIncrease: _weekendLimitMinutes < 120 ? () => setState(() => _weekendLimitMinutes += 15) : null,
          ),
        ],
      ),
    );
  }

  Widget _buildCompactLimitRow({
    required String label,
    required int minutes,
    required Color color,
    required VoidCallback? onDecrease,
    required VoidCallback? onIncrease,
  }) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: AppTextStyles.configLabel.copyWith(fontSize: 11)),
        Row(
          children: [
            _buildMiniStepperButton(Icons.remove, onDecrease),
            SizedBox(
              width: 52,
              child: Text(
                '$minutes p',
                textAlign: TextAlign.center,
                style: AppTextStyles.configValue.copyWith(fontSize: 11.5),
              ),
            ),
            _buildMiniStepperButton(Icons.add, onIncrease),
          ],
        ),
      ],
    );
  }

  Widget _buildMiniStepperButton(IconData icon, VoidCallback? onPressed) {
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

  Widget _buildSaveButton() {
    return SizedBox(
      width: double.infinity,
      height: 38,
      child: ElevatedButton(
        onPressed: _saveChild,
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          foregroundColor: Colors.white,
          elevation: 0,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(9)),
        ),
        child: Text('Lưu Hồ Sơ Bé', style: AppTextStyles.configButton.copyWith(fontSize: 13)),
      ),
    );
  }

  Widget _buildPortraitLayout() {
    return SingleChildScrollView(
      physics: const ClampingScrollPhysics(),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildHeader(),
          const SizedBox(height: 10),
          _buildNameField(),
          const SizedBox(height: 8),
          _buildGenderSelector(),
          const SizedBox(height: 8),
          _buildAvatarSelector(),
          const SizedBox(height: 8),
          _buildAgeSelector(),
          const SizedBox(height: 8),
          _buildTimeLimitsSection(),
          const SizedBox(height: 12),
          _buildSaveButton(),
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
                // Left Column
                Expanded(
                  flex: 5,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _buildNameField(),
                      const SizedBox(height: 8),
                      _buildGenderSelector(),
                      const SizedBox(height: 8),
                      _buildAvatarSelector(),
                    ],
                  ),
                ),
                const SizedBox(width: 16),
                // Right Column
                Expanded(
                  flex: 5,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [_buildAgeSelector(), const SizedBox(height: 6), _buildTimeLimitsSection()],
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 10),
        _buildSaveButton(),
      ],
    );
  }
}
