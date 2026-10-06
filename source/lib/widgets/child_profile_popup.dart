import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../core/database/database_helper.dart';
import '../core/services/screen_time_service.dart';
import '../core/theme/app_theme.dart';
import '../models/child_model.dart';
import '../screens/profile/add_child_dialog.dart';
import '../screens/profile/profile_selection_screen.dart';

class ChildProfilePopup extends StatefulWidget {
  final Child child;
  final VoidCallback onAvatarChanged;

  const ChildProfilePopup({super.key, required this.child, required this.onAvatarChanged});

  static Future<void> show(BuildContext context, {required Child child, required VoidCallback onAvatarChanged}) async {
    await showDialog(
      context: context,
      builder: (context) => ChildProfilePopup(child: child, onAvatarChanged: onAvatarChanged),
    );
  }

  @override
  State<ChildProfilePopup> createState() => _ChildProfilePopupState();
}

class _ChildProfilePopupState extends State<ChildProfilePopup> {
  late String _currentAvatar;
  bool _isChangingAvatar = false;

  @override
  void initState() {
    super.initState();
    _currentAvatar =
        (widget.child.avatarUrl != null &&
            widget.child.avatarUrl!.isNotEmpty &&
            !widget.child.avatarUrl!.startsWith('assets/'))
        ? widget.child.avatarUrl!
        : (widget.child.gender == 'female' ? '👧' : '👦');
  }

  Future<void> _updateAvatar(String newAvatar) async {
    HapticFeedback.selectionClick();
    setState(() {
      _currentAvatar = newAvatar;
    });

    final updatedChild = Child(
      id: widget.child.id,
      name: widget.child.name,
      gender: widget.child.gender,
      birthYear: widget.child.birthYear,
      avatarUrl: newAvatar,
      createdAt: widget.child.createdAt,
    );

    await DatabaseHelper.instance.updateChild(updatedChild);
    await ScreenTimeService.instance.setActiveChild(updatedChild);

    widget.onAvatarChanged();
    if (!mounted) return;

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Đã đổi avatar mới cho bé ${widget.child.name}!'),
        behavior: SnackBarBehavior.floating,
        duration: const Duration(seconds: 1),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isLandscape = MediaQuery.of(context).orientation == Orientation.landscape;
    final isGirl = widget.child.gender == 'female';
    final avatarList = isGirl ? AddChildDialog.girlAvatars : AddChildDialog.boyAvatars;
    final activeColor = isGirl ? AppColors.primary : AppColors.secondary;

    return Dialog(
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.transparent,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      insetPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: ConstrainedBox(
        constraints: BoxConstraints(maxWidth: isLandscape ? 440 : 340, maxHeight: isLandscape ? 330 : 440),
        child: SingleChildScrollView(
          physics: const ClampingScrollPhysics(),
          child: Padding(
            padding: EdgeInsets.fromLTRB(18, isLandscape ? 12 : 16, 18, isLandscape ? 12 : 16),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Header with Close
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('Hồ Sơ Của Bé', style: AppTextStyles.configTitle),
                    IconButton(
                      visualDensity: VisualDensity.compact,
                      padding: EdgeInsets.zero,
                      constraints: const BoxConstraints(),
                      icon: const Icon(Icons.close_rounded, size: 20, color: AppColors.textLight),
                      onPressed: () => Navigator.of(context).pop(),
                    ),
                  ],
                ),
                SizedBox(height: isLandscape ? 6 : 10),

                // Big Avatar Display
                Container(
                  width: isLandscape ? 56 : 68,
                  height: isLandscape ? 56 : 68,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: activeColor.withValues(alpha: 0.12),
                    border: Border.all(color: activeColor.withValues(alpha: 0.3), width: 2),
                  ),
                  alignment: Alignment.center,
                  child: Text(_currentAvatar, style: TextStyle(fontSize: isLandscape ? 30 : 36)),
                ),
                SizedBox(height: isLandscape ? 4 : 8),
                Text(widget.child.name, style: AppTextStyles.configValue.copyWith(fontSize: 16)),
                Text(
                  '${widget.child.age} tuổi • ${isGirl ? 'Bé Gái' : 'Bé Trai'}',
                  style: AppTextStyles.configCaption.copyWith(fontSize: 12),
                ),
                SizedBox(height: isLandscape ? 8 : 12),

                // Avatar Picker Collapsible / Toggle
                if (!_isChangingAvatar) ...[
                  OutlinedButton.icon(
                    onPressed: () => setState(() => _isChangingAvatar = true),
                    icon: const Icon(Icons.palette_outlined, size: 16),
                    label: const Text('Đổi Ảnh Đại Diện'),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: activeColor,
                      side: BorderSide(color: activeColor.withValues(alpha: 0.4)),
                      minimumSize: const Size(double.infinity, 36),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                  ),
                ] else ...[
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('CHỌN ICON MỚI', style: AppTextStyles.configSection.copyWith(fontSize: 11)),
                          InkWell(
                            onTap: () => setState(() => _isChangingAvatar = false),
                            child: Text('Thu gọn', style: AppTextStyles.configCaption.copyWith(color: activeColor)),
                          ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      SizedBox(
                        height: 42,
                        child: ListView.separated(
                          scrollDirection: Axis.horizontal,
                          itemCount: avatarList.length,
                          separatorBuilder: (_, _) => const SizedBox(width: 8),
                          itemBuilder: (context, index) {
                            final item = avatarList[index];
                            final emoji = item['emoji']!;
                            final isSelected = emoji == _currentAvatar;

                            return InkWell(
                              onTap: () => _updateAvatar(emoji),
                              borderRadius: BorderRadius.circular(21),
                              child: AnimatedContainer(
                                duration: const Duration(milliseconds: 180),
                                width: 38,
                                height: 38,
                                decoration: BoxDecoration(
                                  shape: BoxShape.circle,
                                  color: isSelected ? activeColor.withValues(alpha: 0.18) : AppColors.surfaceMuted,
                                  border: Border.all(color: isSelected ? activeColor : Colors.transparent, width: 2),
                                ),
                                alignment: Alignment.center,
                                child: Text(emoji, style: TextStyle(fontSize: isSelected ? 20 : 16)),
                              ),
                            );
                          },
                        ),
                      ),
                    ],
                  ),
                ],
                SizedBox(height: isLandscape ? 8 : 10),

                // Switch Profile Button
                OutlinedButton.icon(
                  onPressed: () {
                    Navigator.of(context).pop();
                    Navigator.of(context)
                        .pushReplacement(MaterialPageRoute(builder: (_) => const ProfileSelectionScreen()));
                  },
                  icon: const Icon(Icons.switch_account_outlined, size: 16),
                  label: const Text('Đổi Hồ Sơ Bé Khác'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.textBody,
                    side: const BorderSide(color: AppColors.border),
                    minimumSize: const Size(double.infinity, 36),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
