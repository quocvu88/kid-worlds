# QUY CHUẨN THIẾT KẾ CARD - KIDS WORLD CMS WEB
*(Tuân thủ tiêu chuẩn evondevKit & Bộ nguyên tắc UI-UX Design System)*

Tài liệu này xác lập quy chuẩn kỹ thuật và thiết kế cho toàn bộ **Card (Khung chứa nội dung)** trên hệ thống quản trị Kids World Web, bảo đảm tính nhất quán về padding, viền (border), độ bo góc, đổ bóng và tỷ lệ khoảng cách (spacing).

---

## 1. Cấu trúc Giải phẫu Card (Card Anatomy)

Mỗi Card trong hệ thống bao gồm tối đa 4 khu vực:

```
+-----------------------------------------------------------+
| [Header] Tiêu đề khối (h6/text-base)      [Action Slot]   |
+-----------------------------------------------------------+ (Đường tóc 1px #e2e8f0)
|                                                           |
| [Body] Nội dung chính: Số liệu metric / Danh sách / Form  |
|                                                           |
+-----------------------------------------------------------+ (Đường tóc 1px #e2e8f0)
| [Footer] Thông tin trạng thái / Phân trang / Thao tác phụ |
+-----------------------------------------------------------+
```

---

## 2. Quy chuẩn Viền, Bo góc & Đổ bóng (Border, Radius & Shadow)

| Thuộc tính | Giá trị CSS | Token / Biến | Ghi chú & Luật áp dụng |
| :--- | :--- | :--- | :--- |
| **Đường viền (Border)** | `1px solid #cbd5e1` (Slate 300) | `var(--kw-card-border)` | **Bắt buộc**: Tách biệt rõ nét card khỏi nền canvas `#f8fafc`. Không dùng border mờ nhạt gây mất viền (Luật `M13`). |
| **Viền khi Hover** | `1px solid #7dd3fc` (Sky 300) | `var(--kw-card-border-hover)` | Phản hồi tương tác thị giác dịu mắt tông xanh dương nhạt. |
| **Bo góc (Radius)** | `12px` | `var(--kw-card-radius)` | Bo góc vừa phải thanh thoát, không bo tròn quá đà làm hẹp diện tích nội dung. |
| **Đổ bóng (Shadow)** | `0 1px 3px rgba(15, 23, 42, 0.04)` | `var(--kw-shadow-card)` | Tối giản, thanh mảnh. **Không dùng bóng đậm** cho card phẳng (Luật `M15`). |
| **Nền (Background)** | `#ffffff` | `var(--kw-card-bg)` | Nền trắng tinh khiết trên nền canvas `#f8fafc`. |

---

## 3. Thang đo Padding Quy Chuẩn (Padding Scale)

Tuyệt đối **không tự chế padding** hoặc dùng các class không tồn tại trong hệ thống (như `p-3.5` trong Bootstrap thuần sẽ biến thành `padding: 0`). Áp dụng 3 cấp độ padding sau:

| Class Quy Chuẩn | Kích thước | Tương đương rem | Ứng dụng |
| :--- | :--- | :--- | :--- |
| `.card-p-standard` | **20px** | `1.25rem` | **Mặc định** (Chuẩn `p-5` evondevKit): Stat cards, Metric blocks, Card nội dung tổng quan. |
| `.card-p-compact` | **16px** | `1.00rem` | Widget nhỏ, thẻ thông tin phụ ở cột sidebar, chip/badge groups. |
| `.card-p-roomy` | **24px** | `1.50rem` | Hero banner, Sync Station, Form cấu hình lớn, modal dialog container. |

---

## 4. Quy chuẩn Spacing & Header / Footer

### 4.1. Header Card (`.card-custom-header`)
- **Padding**: `1rem 1.25rem` (16px dọc, 20px ngang).
- **Phân cách**: `border-bottom: 1px solid var(--kw-border-light)` (`#e2e8f0`).
- **Khoảng cách**: Giữa Header và Body luôn duy trì `16px` (`mb-3.5` hoặc `mb-4`).
- **Slot Action**: Luôn căn phải góc trên, `align-items: center` khi tiêu đề 1 dòng hoặc `items-start` khi tiêu đề dài.

### 4.2. Footer Card (`.card-custom-footer`)
- **Padding**: `0.75rem 1.25rem` (12px dọc, 20px ngang).
- **Phân cách**: `border-top: 1px solid var(--kw-border-light)` (`#e2e8f0`).
- **Nền**: `#f8fafc` nhẹ nhàng, font chữ phụ `0.775rem` - `0.8rem` màu `text-muted`.

### 4.3. Card Bảng Dữ Liệu (Table & Flush List - Luật `F3`, `F13`)
- Card bọc ngoài: `class="card card-custom overflow-hidden"`.
- Header: `.card-custom-header py-3 px-4`.
- Body: `.card-body p-0` (không lồng padding card vào ngoài bảng tránh khoảng trắng thừa).
- Cột tiêu đề bảng `th`: `padding: 0.75rem 1.25rem;` nền `#f8fafc`, chữ hoa `0.75rem` `fw-semibold`.
- Dòng dữ liệu `td`: `padding: 0.85rem 1.25rem;` phân cách bằng hairline border `var(--kw-border-subtle)`.
- Footer tóm tắt: `.card-custom-footer px-4 py-2.5`.

---

## 5. Mẫu Code Chuẩn (Ready-to-Use Snippets)

### 5.1. Thẻ Thống Kê Số Liệu (Metric Stat Card)
```html
<div class="card card-custom card-p-standard h-100">
    <div class="card-stat-header">
        <div>
            <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.725rem; letter-spacing: 0.05em;">Tên Chỉ Số</span>
            <h3 class="fw-bold mb-0 mt-1" style="color: #0284c7; font-size: 1.75rem; letter-spacing: -0.02em;">1,250</h3>
        </div>
        <div class="card-stat-icon" style="background: var(--kw-primary-light); border-color: var(--kw-primary-border); color: var(--kw-primary);">
            <i class="bi bi-collection-play fs-5"></i>
        </div>
    </div>
    <div class="card-stat-footer">
        <i class="bi bi-check2-circle text-success me-1.5"></i> Trạng thái đồng bộ sẵn sàng
    </div>
</div>
```

### 5.2. Card Chứa Bảng Dữ Liệu (Table Card)
```html
<div class="card card-custom overflow-hidden mb-4">
    <div class="card-custom-header py-3 px-4">
        <div>
            <h6 class="fw-semibold mb-0 text-dark">Tiêu Đề Bảng Dữ Liệu</h6>
            <small class="text-muted">Mô tả ngắn gọn về danh mục quản lý</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <!-- Action / Search Slot -->
            <a href="/create" class="btn btn-kw-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Thêm Mới
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom">
                <thead>
                    <tr><th>Cột 1</th><th>Cột 2</th><th class="text-end">Thao Tác</th></tr>
                </thead>
                <tbody>
                    <tr><td>Dữ liệu A</td><td>Dữ liệu B</td><td class="text-end">...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-custom-footer px-4 py-2.5 d-flex justify-content-between align-items-center">
        <small class="text-muted">Hiển thị 10 / 10 mục</small>
        <small class="text-muted"><i class="bi bi-shield-check text-success me-1"></i>Đồng bộ tự động</small>
    </div>
</div>
```

---

## 6. Các Lỗi Cần Tránh (Anti-patterns)

1. ❌ **Không dùng class `p-3.5` hoặc số thập phân trong Bootstrap**: Bootstrap 5.3 chỉ hỗ trợ `p-0` đến `p-5`. Luôn dùng `.card-p-standard` (20px) hoặc các class quy chuẩn của hệ thống.
2. ❌ **Không bỏ viền (borderless card)**: Card không có viền sẽ hòa lẫn vào nền xám nhạt, làm mất phân cấp thị giác.
3. ❌ **Không dùng bóng đổ quá đậm (deep shadow)**: Khiến giao diện bị nặng nề, sai lệch phong cách thanh mảnh của evondevKit.
4. ❌ **Không để Action Button trôi dạt vào thân card**: Nút thao tác toàn khối (Tải, Thêm, Lọc) phải nằm ở slot phải của Header.
