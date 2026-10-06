import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../core/services/content_server_config_service.dart';
import '../../core/services/topic_store_service.dart';
import '../../core/utils/image_helper.dart';
import '../../core/theme/app_theme.dart';
import '../../widgets/animated_playful_background.dart';
import '../../widgets/content_server_config_dialog.dart';

class TopicStoreScreen extends StatefulWidget {
  const TopicStoreScreen({super.key});

  @override
  State<TopicStoreScreen> createState() => _TopicStoreScreenState();
}

class _TopicStoreScreenState extends State<TopicStoreScreen> {
  @override
  void initState() {
    super.initState();
    _loadPacks();
  }

  void _loadPacks() {
    TopicStoreService.instance.fetchPacks();
  }

  void _openConfigDomain() {
    HapticFeedback.lightImpact();
    ContentServerConfigDialog.show(context, onSaved: () {
      _loadPacks();
    });
  }

  Future<void> _downloadPack(ServerTopicPack pack) async {
    HapticFeedback.mediumImpact();
    final success = await TopicStoreService.instance.downloadAndInstallPack(pack.id);

    if (!mounted) return;
    if (success) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('🎉 Đã tải và cài đặt thành công gói: ${pack.titleVi}!'),
          backgroundColor: AppColors.success,
          behavior: SnackBarBehavior.floating,
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('❌ Không thể tải gói: ${pack.titleVi}. Hãy kiểm tra kết nối server!'),
          backgroundColor: AppColors.coralRed,
          behavior: SnackBarBehavior.floating,
        ),
      );
    }
  }

  Future<void> _deletePack(ServerTopicPack pack) async {
    HapticFeedback.lightImpact();
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Text('Gỡ Gói Chủ Đề?', style: AppTextStyles.configTitle),
        content: Text('Bé sẽ không thấy chủ đề "${pack.titleVi}" nữa. Bạn có thể tải lại bất kỳ lúc nào.'),
        actions: [
          TextButton(onPressed: () => Navigator.of(context).pop(false), child: const Text('Hủy')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.coralRed),
            onPressed: () => Navigator.of(context).pop(true),
            child: const Text('Xóa', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirm == true) {
      await TopicStoreService.instance.deletePack(pack.id);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Đã gỡ bỏ gói ${pack.titleVi}!'), behavior: SnackBarBehavior.floating),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedPlayfulBackground(
      child: Scaffold(
        backgroundColor: Colors.transparent,
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          surfaceTintColor: Colors.transparent,
          elevation: 0,
          leading: IconButton(
            icon: const Icon(Icons.arrow_back_ios_new_rounded, color: AppColors.textDark, size: 20),
            onPressed: () => Navigator.of(context).pop(),
          ),
          title: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(Icons.cloud_download_rounded, size: 18, color: AppColors.primary),
              ),
              const SizedBox(width: 8),
              Text('Kho Gói Chủ Đề', style: AppTextStyles.kidTitle.copyWith(fontSize: 18)),
            ],
          ),
          actions: [
            IconButton(
              icon: const Icon(Icons.dns_outlined, color: AppColors.textBody, size: 22),
              tooltip: 'Cấu hình Domain máy chủ',
              onPressed: _openConfigDomain,
            ),
            IconButton(
              icon: const Icon(Icons.refresh_rounded, color: AppColors.textBody, size: 22),
              tooltip: 'Tải lại danh sách',
              onPressed: _loadPacks,
            ),
            const SizedBox(width: 8),
          ],
        ),
        body: SafeArea(
          child: Column(
            children: [
              // Server Banner
              _buildServerBanner(),

              // Body Packs List / Grid
              Expanded(
                child: ListenableBuilder(
                  listenable: TopicStoreService.instance,
                  builder: (context, _) {
                    final service = TopicStoreService.instance;

                    if (service.isLoading) {
                      return const Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            CircularProgressIndicator(),
                            SizedBox(height: 12),
                            Text('Đang kết nối máy chủ nội dung...', style: TextStyle(color: AppColors.textLight)),
                          ],
                        ),
                      );
                    }

                    if (service.error != null) {
                      return Center(
                        child: Padding(
                          padding: const EdgeInsets.all(24.0),
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(Icons.cloud_off_rounded, size: 48, color: AppColors.coralRed),
                              const SizedBox(height: 12),
                              Text('Chưa kết nối được máy chủ!', style: AppTextStyles.configTitle),
                              const SizedBox(height: 6),
                              Text(
                                service.error!,
                                textAlign: TextAlign.center,
                                style: AppTextStyles.configCaption.copyWith(color: AppColors.textBody),
                              ),
                              const SizedBox(height: 16),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  ElevatedButton.icon(
                                    onPressed: _openConfigDomain,
                                    icon: const Icon(Icons.settings, size: 16, color: Colors.white),
                                    label: const Text('Cấu Hình Domain', style: TextStyle(color: Colors.white)),
                                    style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary),
                                  ),
                                  const SizedBox(width: 8),
                                  OutlinedButton.icon(
                                    onPressed: _loadPacks,
                                    icon: const Icon(Icons.refresh, size: 16),
                                    label: const Text('Thử Lại'),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      );
                    }

                    if (service.serverPacks.isEmpty) {
                      return Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(Icons.inbox_outlined, size: 48, color: AppColors.textLight),
                            const SizedBox(height: 12),
                            Text('Chưa có gói chủ đề nào trên máy chủ', style: AppTextStyles.configTitle),
                            const SizedBox(height: 4),
                            Text('Hãy thêm gói mới trên Web Admin CMS nhé!', style: AppTextStyles.configCaption),
                          ],
                        ),
                      );
                    }

                    return RefreshIndicator(
                      onRefresh: () async => TopicStoreService.instance.fetchPacks(),
                      color: AppColors.primary,
                      child: OrientationBuilder(
                        builder: (context, orientation) {
                          final isLandscape = orientation == Orientation.landscape;

                          if (isLandscape) {
                            return GridView.builder(
                              physics: const AlwaysScrollableScrollPhysics(parent: BouncingScrollPhysics()),
                              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                                crossAxisCount: 2,
                                crossAxisSpacing: 14,
                                mainAxisSpacing: 12,
                                childAspectRatio: 2.3,
                              ),
                              itemCount: service.serverPacks.length,
                              itemBuilder: (context, index) {
                                return _buildPackCard(service.serverPacks[index], isLandscape: true);
                              },
                            );
                          }

                          return ListView.builder(
                            physics: const AlwaysScrollableScrollPhysics(parent: BouncingScrollPhysics()),
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                            itemCount: service.serverPacks.length,
                            itemBuilder: (context, index) {
                              return _buildPackCard(service.serverPacks[index], isLandscape: false);
                            },
                          );
                        },
                      ),
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildServerBanner() {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.9),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              const Icon(Icons.wifi_tethering_rounded, size: 16, color: AppColors.success),
              const SizedBox(width: 6),
              Text(
                'Máy chủ: ${ContentServerConfigService.instance.currentServerUrl}',
                style: AppTextStyles.configCaption.copyWith(
                  fontFamily: 'monospace',
                  color: AppColors.textBody,
                  fontSize: 11,
                ),
              ),
            ],
          ),
          InkWell(
            onTap: _openConfigDomain,
            child: Text(
              'Đổi domain',
              style: AppTextStyles.configCaption.copyWith(
                color: AppColors.primary,
                fontWeight: FontWeight.w600,
                fontSize: 11,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPackCard(ServerTopicPack pack, {required bool isLandscape}) {
    final isDownloading = TopicStoreService.instance.isDownloading(pack.id);
    final progress = TopicStoreService.instance.downloadingProgress[pack.id] ?? 0.0;

    return Card(
      elevation: 0,
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(14),
        side: BorderSide(
          color: pack.isInstalled ? AppColors.success.withValues(alpha: 0.4) : AppColors.border,
          width: pack.isInstalled ? 1.5 : 1,
        ),
      ),
      color: Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(12.0),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            // Thumbnail
            ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: SizedBox(
                width: isLandscape ? 70 : 80,
                height: isLandscape ? 70 : 80,
                child: ImageHelper.buildSafeImage(
                  pack.thumbnailUrl,
                  width: isLandscape ? 70 : 80,
                  height: isLandscape ? 70 : 80,
                  fallback: Container(
                    color: AppColors.surfaceMuted,
                    child: const Center(
                      child: Icon(Icons.category_rounded, size: 32, color: AppColors.textLight),
                    ),
                  ),
                ),
              ),
            ),
            const SizedBox(width: 12),

            // Info
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                        decoration: BoxDecoration(
                          color: AppColors.secondary.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text(
                          pack.category.toUpperCase(),
                          style: AppTextStyles.configCaption.copyWith(
                            fontSize: 9,
                            fontWeight: FontWeight.w700,
                            color: AppColors.secondary,
                          ),
                        ),
                      ),
                      const SizedBox(width: 6),
                      Text(
                        '${pack.targetAgeMin}-${pack.targetAgeMax} tuổi',
                        style: AppTextStyles.configCaption.copyWith(fontSize: 10),
                      ),
                      const Spacer(),
                      Text(
                        '${pack.sizeMb} MB',
                        style: AppTextStyles.configCaption.copyWith(
                          fontSize: 10,
                          fontWeight: FontWeight.w600,
                          color: AppColors.textLight,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    pack.titleVi,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: AppTextStyles.configTitle.copyWith(fontSize: 14),
                  ),
                  Text(
                    '${pack.titleEn} • ${pack.itemCount} thẻ từ vựng',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: AppTextStyles.configCaption.copyWith(fontSize: 11),
                  ),
                  const SizedBox(height: 8),

                  // Action Buttons / Download Progress
                  if (isDownloading) ...[
                    Row(
                      children: [
                        Expanded(
                          child: ClipRRect(
                            borderRadius: BorderRadius.circular(4),
                            child: LinearProgressIndicator(
                              value: progress > 0 ? progress : null,
                              backgroundColor: AppColors.surfaceMuted,
                              valueColor: const AlwaysStoppedAnimation<Color>(AppColors.primary),
                              minHeight: 6,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          '${(progress * 100).toInt()}%',
                          style: AppTextStyles.configCaption.copyWith(fontWeight: FontWeight.w600),
                        ),
                      ],
                    ),
                  ] else if (pack.isInstalled) ...[
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: AppColors.success.withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(Icons.check_circle_rounded, size: 13, color: AppColors.success),
                              const SizedBox(width: 4),
                              Text(
                                'Đã Cài Đặt (v${pack.localVersion ?? pack.version})',
                                style: AppTextStyles.configCaption.copyWith(
                                  color: AppColors.success,
                                  fontSize: 10.5,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ),
                        ),
                        Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            // Nút Tải lại / Đồng bộ lại từ CMS
                            InkWell(
                              onTap: () => _downloadPack(pack),
                              borderRadius: BorderRadius.circular(6),
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                decoration: BoxDecoration(
                                  color: pack.hasUpdate
                                      ? AppColors.accent
                                      : AppColors.primary.withValues(alpha: 0.12),
                                  borderRadius: BorderRadius.circular(6),
                                  border: Border.all(
                                    color: pack.hasUpdate
                                        ? AppColors.accent
                                        : AppColors.primary.withValues(alpha: 0.35),
                                  ),
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(
                                      Icons.sync_rounded,
                                      size: 13,
                                      color: pack.hasUpdate ? Colors.white : AppColors.primary,
                                    ),
                                    const SizedBox(width: 4),
                                    Text(
                                      pack.hasUpdate ? 'Cập Nhật (v${pack.version})' : 'Đồng bộ lại',
                                      style: AppTextStyles.configCaption.copyWith(
                                        color: pack.hasUpdate ? Colors.white : AppColors.primary,
                                        fontSize: 10.5,
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                            const SizedBox(width: 6),
                            IconButton(
                              icon: const Icon(Icons.delete_outline_rounded, size: 18, color: AppColors.textLight),
                              tooltip: 'Gỡ bỏ gói',
                              padding: EdgeInsets.zero,
                              constraints: const BoxConstraints(),
                              onPressed: () => _deletePack(pack),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ] else ...[
                    SizedBox(
                      height: 30,
                      child: ElevatedButton.icon(
                        onPressed: () => _downloadPack(pack),
                        icon: const Icon(Icons.download_rounded, size: 14, color: Colors.white),
                        label: Text(
                          'Tải Về (${pack.sizeMb} MB)',
                          style: AppTextStyles.configButton.copyWith(fontSize: 11, color: Colors.white),
                        ),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          elevation: 0,
                          padding: const EdgeInsets.symmetric(horizontal: 12),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
