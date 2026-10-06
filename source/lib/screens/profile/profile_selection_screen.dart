import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../core/database/database_helper.dart';
import '../../core/services/screen_time_service.dart';
import '../../core/theme/app_theme.dart';
import '../../models/child_model.dart';
import '../../widgets/animated_playful_background.dart';
import '../../widgets/parent_gate_dialog.dart';
import '../home/home_screen.dart';
import 'add_child_dialog.dart';
import 'parent_settings_dialog.dart';

class ProfileSelectionScreen extends StatefulWidget {
  const ProfileSelectionScreen({super.key});

  @override
  State<ProfileSelectionScreen> createState() => _ProfileSelectionScreenState();
}

class _ProfileSelectionScreenState extends State<ProfileSelectionScreen> {
  List<Child> _children = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadChildren();
  }

  Future<void> _loadChildren() async {
    setState(() => _isLoading = true);
    final list = await DatabaseHelper.instance.getChildren();
    setState(() {
      _children = list;
      _isLoading = false;
    });
  }

  void _selectChild(Child child) async {
    HapticFeedback.lightImpact();
    await ScreenTimeService.instance.setActiveChild(child);
    if (!mounted) return;
    Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => const HomeScreen()));
  }

  void _onAddChildPressed() async {
    HapticFeedback.mediumImpact();
    final passed = await ParentGateDialog.verify(context, title: 'Xác Nhận Thêm Bé');
    if (passed && mounted) {
      AddChildDialog.show(context, onChildAdded: _loadChildren);
    }
  }

  void _onOpenParentSettings() async {
    HapticFeedback.lightImpact();
    final passed = await ParentGateDialog.verify(context, title: 'Cài Đặt Phụ Huynh');
    if (passed && mounted) {
      ParentSettingsDialog.show(context, onSettingsChanged: _loadChildren);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AnimatedPlayfulBackground(
        child: SafeArea(
          child: Column(
            children: [
              // Custom Header Bar
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(6),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.9),
                            shape: BoxShape.circle,
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withValues(alpha: 0.06),
                                blurRadius: 8,
                                offset: const Offset(0, 2),
                              ),
                            ],
                          ),
                          child: const Icon(Icons.stars_rounded, color: AppColors.accent, size: 22),
                        ),
                        const SizedBox(width: 8),
                        Text('Bé Là Ai Nhỉ? 🌟', style: AppTextStyles.kidTitle),
                      ],
                    ),
                    Container(
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.9),
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.05),
                            blurRadius: 6,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: IconButton(
                        icon: const Icon(Icons.settings_outlined, color: AppColors.textBody, size: 22),
                        tooltip: 'Khu vực phụ huynh',
                        onPressed: _onOpenParentSettings,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'Bé hãy chọn hình của mình để cùng học tiếng Anh nhé!',
                style: AppTextStyles.configLabel.copyWith(
                  color: AppColors.textBody.withValues(alpha: 0.8),
                  fontSize: 13,
                ),
              ),
              const SizedBox(height: 12),

              // Profiles Grid
              Expanded(
                child: _isLoading
                    ? const Center(child: CircularProgressIndicator())
                    : Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 18.0),
                        child: OrientationBuilder(
                          builder: (context, orientation) {
                            final isLandscape = orientation == Orientation.landscape;
                            return GridView.builder(
                              physics: const BouncingScrollPhysics(),
                              gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                                crossAxisCount: isLandscape ? 4 : 2,
                                crossAxisSpacing: 14,
                                mainAxisSpacing: 14,
                                childAspectRatio: isLandscape ? 1.18 : 1.05,
                              ),
                              itemCount: _children.length + 1,
                              itemBuilder: (context, index) {
                                if (index == _children.length) {
                                  return _buildAddCard();
                                }
                                return _buildChildCard(_children[index], index);
                              },
                            );
                          },
                        ),
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildChildCard(Child child, int index) {
    final avatarColor = AppColors.avatarColors[index % AppColors.avatarColors.length];
    final isGirl = child.gender == 'female';

    return Container(
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.92),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: Colors.white, width: 1.5),
        boxShadow: [BoxShadow(color: avatarColor.withValues(alpha: 0.12), blurRadius: 12, offset: const Offset(0, 4))],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () => _selectChild(child),
          borderRadius: BorderRadius.circular(18),
          child: Padding(
            padding: const EdgeInsets.all(12.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  width: 60,
                  height: 60,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: avatarColor.withValues(alpha: 0.15),
                    border: Border.all(color: avatarColor.withValues(alpha: 0.3), width: 1.5),
                  ),
                  child: Center(
                    child: Text(
                      (child.avatarUrl != null &&
                              child.avatarUrl!.isNotEmpty &&
                              !child.avatarUrl!.startsWith('assets/'))
                          ? child.avatarUrl!
                          : (isGirl ? '👧' : '👦'),
                      style: const TextStyle(fontSize: 32),
                    ),
                  ),
                ).animate().scale(duration: 350.ms, curve: Curves.easeOutBack),
                const SizedBox(height: 8),
                Text(
                  child.name,
                  style: AppTextStyles.configValue.copyWith(fontSize: 15),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: avatarColor.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    '${child.age} tuổi',
                    style: AppTextStyles.configCaption.copyWith(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: avatarColor,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildAddCard() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.82),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.4), width: 1.5),
        boxShadow: [
          BoxShadow(color: AppColors.primary.withValues(alpha: 0.08), blurRadius: 10, offset: const Offset(0, 3)),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: _onAddChildPressed,
          borderRadius: BorderRadius.circular(18),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(shape: BoxShape.circle, color: AppColors.primary.withValues(alpha: 0.12)),
                child: const Icon(Icons.add_rounded, size: 26, color: AppColors.primary),
              ),
              const SizedBox(height: 8),
              Text(
                'Thêm Bé Mới',
                style: AppTextStyles.configLabel.copyWith(
                  fontWeight: FontWeight.w600,
                  color: AppColors.primary,
                  fontSize: 13,
                ),
              ),
              const SizedBox(height: 2),
              Text('Dành cho phụ huynh', style: AppTextStyles.configCaption.copyWith(fontSize: 10.5)),
            ],
          ),
        ),
      ),
    );
  }
}
