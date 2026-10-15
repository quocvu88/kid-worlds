# KidsWorld Release Notes - Version 1.0.3 (Build 4)

**Release Date:** 2026-10-10  
**Package:** `com.kidsedu.kidsworld.kids_world`  
**Target Platform:** Android (Universal APK, ký debug key – chưa phát hành chính thức)  
**Nguồn:** commit `5a5053d` + thay đổi chưa commit trong `source/` (tính năng Góc Sáng Tạo)

---

## 🚀 Điểm mới

### 🖼️ Hồ sơ bé: Góc Sáng Tạo & Thành Tích
- Màn hình mới `ChildShowcaseScreen`, mở từ popup hồ sơ bé:
  - **Góc Sáng Tạo**: xem lại các tranh bé đã tô.
  - **Thành Tích**: số liệu học tập và huy hiệu.
- Tranh tô màu được lưu lại theo từng bé (`ArtworkService`); xoá hồ sơ bé sẽ xoá luôn tranh của bé.

### 🎨 Tô màu / 🃏 Lật thẻ
- Tô màu: lưu tranh thành ảnh PNG vào Góc Sáng Tạo, mở lại tranh cũ để tô tiếp (lưu ghi đè), zoom/kéo tranh bằng 2 ngón.
- Lật thẻ: ghi lại mỗi lần thắng để tính Thành Tích.

### 🛠️ Khác
- SQLite nâng lên **version 4** (bảng lưu tranh) – cài đè lên bản cũ sẽ tự nâng cấp DB.

## ⚠️ Còn tồn tại
- URL máy chủ mặc định vẫn là `http://10.0.2.2:8000` (chỉ dùng được trên emulator).
- Media của gói chưa tải về máy để học offline.
- Nút Back Android khi đang bị khoá vẫn đóng màn hình bên dưới.
