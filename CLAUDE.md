# KidsWorld – Hướng dẫn cho Claude Code

App học tiếng Anh cho trẻ em (Flutter) + CMS/API nội dung (PHP + SQLite).
Người dùng giao tiếp bằng **tiếng Việt** → trả lời bằng tiếng Việt.

## Cấu trúc
- `source/` – Flutter app (`com.kidsedu.kidsworld.kids_world`), version hiện tại **1.0.2+3**
  (nhớ cập nhật đồng thời `pubspec.yaml` và `kAppVersion` trong `lib/core/app_info.dart`).
- `backend_web/` – CMS + API PHP (front controller `public/index.php`, chạy local: `start_server.bat` → http://localhost:8000, emulator dùng http://10.0.2.2:8000).
- `releases/` – APK đã phát hành + CHANGELOG.
- Quy tắc code/UI: `.rules` (gốc) và `source/.rules` – **đọc trước khi sửa**. Skill UI/UX: `.agents/skills/ui-ux/`.

## Quy tắc bắt buộc
- **KHÔNG tự `git commit`/`git push`** khi người dùng chưa yêu cầu rõ ràng.
- App **chỉ chạy màn hình ngang** (`kAppOrientations` trong `lib/core/app_info.dart`, manifest `sensorLandscape`). Không viết thêm layout dọc.
- Sau khi sửa Dart: chạy `flutter analyze` (trong `source/`). Sau khi sửa PHP: `php -l <file>`.
- Người dùng test bằng Android Studio trên **emulator**; thay đổi AndroidManifest/main.dart/DB cần Stop → Run (không Hot Reload).
- Chưa public: **cố ý chưa làm bảo mật** (CMS không đăng nhập, cho upload SVG, PIN mặc định 1234, APK ký debug key). Không tự sửa phần này trừ khi được yêu cầu.

## Kiến trúc chính (đã thay đổi gần đây)
- **Giới hạn giờ chơi**: `core/services/screen_time_service.dart` lưu SharedPreferences
  `screen_time_<childId>_<yyyy-MM-dd>` (giây đã dùng) + `screen_time_bonus_<childId>_<ngày>` (gia hạn). Tự qua ngày lúc nửa đêm.
- **Màn khoá** phủ toàn app qua `MaterialApp.builder` trong `main.dart` (`_ScreenTimeLockOverlay`), dùng `appNavigatorKey` (`core/navigation.dart`). Màn hình con KHÔNG tự kiểm tra lockout nữa.
- **SQLite app** (`core/database/database_helper.dart`) version **4** (v4: bảng `artworks`): bật `PRAGMA foreign_keys` (onConfigure), bản vá dữ liệu mẫu `_patchSeedContent` chỉ chạy khi tạo/nâng cấp DB.
- **Asset thiếu**: `BundledAssets` (trong `core/utils/image_helper.dart`) đọc AssetManifest lúc splash; đường dẫn `assets/...` không có thật bị bỏ qua (thư mục `assets/images`, `assets/audio` hiện đang rỗng).
- **Âm thanh** (`core/services/audio_service.dart`): `playPronunciation` ưu tiên file phát âm từ CMS rồi mới TTS; pitch luôn 1.0; tự chọn giọng offline chất lượng cao; mỗi yêu cầu mới huỷ yêu cầu cũ (`_requestId`); log `[Audio hh:mm:ss]` ở debug. `speakVietnameseAfterTransition` cho câu chào khi mở màn hình.
- **Máy chủ nội dung**: `ContentServerConfigService.instance.ready` phải được `await` trước khi gọi mạng. API `/api/packs/{id}/download` trả URL file trên server dạng tương đối (`/uploads/...`), app tự ghép domain qua `ImageHelper.resolveUrl`.
- **CMS**: `AdminController::makeUniqueId` tránh trùng ID; AI dùng Gemini `gemini-2.5-flash` (đổi qua env `GEMINI_MODEL`), lỗi trả về `data.warning`.
- **Góc Sáng Tạo & Thành Tích**: tô màu bấm ♥ → `ArtworkService` lưu PNG vào `<documents>/artworks/<childId>/<id>.png` + nét vẽ JSON trong bảng `artworks` (mở lại tô tiếp qua `ColoringGameScreen(resumeArtwork:)`). Màn `screens/profile/child_showcase_screen.dart` (mở từ popup Hồ Sơ) có tab tranh + tab thành tích; huy hiệu tính từ `DatabaseHelper.getAchievementCounts` (activity_logs, thêm action `win_memory_game`, `save_artwork`).

## UI đã làm lại gần đây
- `screens/learning/games/coloring_game_screen.dart`: toàn màn hình (immersiveSticky), thanh trên 44dp gồm ← | nút bảng màu (giữa, mở lưới 12 màu) | ⇄ đổi tranh (lưới ảnh thu nhỏ) + ♥ lưu; khung vẽ không viền, cao hết cỡ; tranh nét (`coloring_outline_url`) vẽ multiply đè lên nét tô; nét lưu toạ độ chuẩn hoá.
- `screens/home/home_screen.dart`: nút chủ đề dạng bong bóng emoji (đọc to tên khi chọn) + thẻ chủ đề cỡ lớn vuốt ngang (`_BigTopicCard`).

## Việc còn mở
- Âm thanh rè trên emulator (người dùng tạm bỏ qua; nghi do emulator). Cần logcat nếu xử lý tiếp.
- Tải media về máy để học offline (gói hiện chỉ lưu URL).
- URL server mặc định vẫn là `http://10.0.2.2:8000` (chỉ emulator).
- Nút Back Android khi đang bị khoá vẫn pop route bên dưới (màn khoá vẫn giữ).
- Chưa chạy `flutter analyze` cho các thay đổi gần đây → **việc đầu tiên nên làm**.

Chi tiết review gốc: Project claude.ai "KidWorlds" → `claude/code-review-2026-10-08.md`.
