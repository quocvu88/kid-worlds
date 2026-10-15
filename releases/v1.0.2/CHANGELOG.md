# KidsWorld Release Notes - Version 1.0.2 (Build 3)

**Release Date:** 2026-10-10  
**Package:** `com.kidsedu.kidsworld.kids_world`  
**Target Platform:** Android (Universal APK, ký debug key – chưa phát hành chính thức)  
**Commit:** `5a5053d`

---

## 🚀 Điểm mới

### ⏱️ Giới hạn giờ chơi
- Màn khoá phủ toàn app (không còn phụ thuộc từng màn hình), tự sang ngày mới lúc nửa đêm, hỗ trợ gia hạn thêm giờ.

### 🎨 Tô màu
- Toàn màn hình; thanh trên gồm nút quay lại, bảng 12 màu, đổi tranh (lưới ảnh thu nhỏ) và lưu.
- Tranh nét vẽ đè lên nét tô (multiply) nên tô không bị lem ra ngoài viền.

### 🏠 Trang chủ
- Nút chủ đề dạng bong bóng emoji, đọc to tên khi chọn; thẻ chủ đề cỡ lớn vuốt ngang.
- Chỉ hiện danh mục có gói phù hợp tuổi bé (không còn màn trống).

### 📚 Flashcard
- Mặc định hiển thị ảnh thật (nếu có); lớp phủ tối sau chữ gọn hơn, không che ảnh.

### 🔊 Âm thanh
- Ưu tiên file phát âm từ CMS rồi mới dùng TTS; tự chọn giọng offline chất lượng cao; yêu cầu mới huỷ yêu cầu cũ.

### 🛠️ Khác
- App chỉ chạy màn hình ngang.
- SQLite v3: bật khoá ngoại, vá dữ liệu mẫu khi tạo/nâng cấp DB.
- Bỏ qua đường dẫn asset không tồn tại; gói tải về dùng URL tương đối, app tự ghép domain máy chủ.

## ⚠️ Còn tồn tại
- URL máy chủ mặc định vẫn là `http://10.0.2.2:8000` (chỉ dùng được trên emulator).
- Media của gói chưa tải về máy để học offline.
- Nút Back Android khi đang bị khoá vẫn đóng màn hình bên dưới.
