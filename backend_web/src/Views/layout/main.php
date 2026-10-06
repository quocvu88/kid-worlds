<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Kids World CMS') ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts: Plus Jakarta Sans (thanh mảnh, gọn gàng) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Tông màu xanh dương nhạt (Soft Light Blue Theme - evondevKit style) */
            --kw-primary: #0ea5e9;
            --kw-primary-hover: #0284c7;
            --kw-primary-light: #f0f9ff;
            --kw-primary-subtle: #e0f2fe;
            --kw-primary-border: #bae6fd;
            --kw-primary-ring: rgba(14, 165, 233, 0.16);

            /* Neutral Surfaces & Canvas */
            --kw-bg: #f8fafc;
            --kw-surface: #ffffff;
            --kw-border: #cbd5e1; /* Đường viền tóc 1px sắc nét chuẩn Slate 300 */
            --kw-border-light: #e2e8f0; /* Đường chia nội bộ / separator */
            --kw-border-subtle: #f1f5f9;
            --kw-border-hover: #94a3b8;

            /* Card Tokens (evondevKit & UI-UX Standard) */
            --kw-card-bg: #ffffff;
            --kw-card-border: #cbd5e1;
            --kw-card-border-hover: #7dd3fc;
            --kw-card-radius: 12px;
            --kw-card-pad-standard: 1.25rem; /* 20px - Tiêu chuẩn p-5 evondevKit */
            --kw-card-pad-compact: 1rem;     /* 16px */
            --kw-card-pad-roomy: 1.5rem;     /* 24px */

            /* Typography */
            --kw-text-main: #1e293b;
            --kw-text-muted: #64748b;
            --kw-text-light: #94a3b8;

            /* Status */
            --kw-success: #10b981;
            --kw-success-bg: #ecfdf5;
            --kw-warning: #f59e0b;
            --kw-warning-bg: #fffbeb;
            --kw-danger: #ef4444;
            --kw-danger-bg: #fef2f2;

            /* Bo tròn vừa phải */
            --kw-radius: 12px;
            --kw-radius-sm: 8px;
            --kw-radius-pill: 9999px;

            /* Đổ bóng nhẹ chuẩn M13/M15 (Card dùng viền tóc 1px, shadow chỉ siêu nhẹ) */
            --kw-shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.04);
            --kw-shadow-btn: 0 2px 4px rgba(14, 165, 233, 0.18), 0 1px 2px rgba(14, 165, 233, 0.1);
            --kw-shadow-btn-hover: 0 4px 10px rgba(14, 165, 233, 0.25);
            --kw-shadow-card: 0 1px 3px rgba(15, 23, 42, 0.04), 0 1px 2px rgba(15, 23, 42, 0.02);
            --kw-shadow-card-hover: 0 4px 14px rgba(14, 165, 233, 0.09), 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--kw-bg);
            color: var(--kw-text-main);
            min-height: 100vh;
            letter-spacing: -0.01em;
            -webkit-font-smoothing: antialiased;
        }

        /* Navbar: Nền trắng thanh lịch, viền dưới thanh mảnh */
        .navbar-kw {
            background-color: var(--kw-surface);
            border-bottom: 1px solid var(--kw-border);
            padding: 0.75rem 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.15rem;
            color: #0f172a !important;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            letter-spacing: -0.02em;
        }

        .brand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: var(--kw-primary-light);
            border: 1px solid var(--kw-primary-border);
            color: var(--kw-primary);
            border-radius: var(--kw-radius-sm);
        }

        /* Navbar Links */
        .navbar-kw .nav-link {
            color: var(--kw-text-muted) !important;
            font-weight: 500;
            font-size: 0.9rem;
            padding: 0.5rem 0.85rem !important;
            border-radius: var(--kw-radius-sm);
            transition: all 0.15s ease;
        }

        .navbar-kw .nav-link:hover {
            color: var(--kw-primary-hover) !important;
            background: var(--kw-primary-light);
        }

        .navbar-kw .nav-link.active {
            color: var(--kw-primary-hover) !important;
            background: var(--kw-primary-subtle);
            font-weight: 600;
        }

        /* Nav Pills (Chuẩn evondevKit: Active nền xanh - chữ trắng tương phản cao hiển thị cực rõ) */
        .nav-pills .nav-link {
            color: var(--kw-text-main) !important;
            background-color: var(--kw-surface);
            border: 1px solid var(--kw-border);
            border-radius: var(--kw-radius-sm);
            font-weight: 500;
            font-size: 0.875rem;
            padding: 0.5rem 1rem !important;
            transition: all 0.15s ease;
        }

        .nav-pills .nav-link:hover {
            color: var(--kw-primary-hover) !important;
            background-color: var(--kw-primary-light);
            border-color: var(--kw-primary-border);
        }

        .nav-pills .nav-link.active,
        .nav-pills .show > .nav-link {
            color: #ffffff !important; /* Chữ trắng tinh khiết, tương phản tuyệt đối trên nền xanh */
            background-color: var(--kw-primary) !important;
            border-color: var(--kw-primary) !important;
            box-shadow: 0 2px 8px rgba(14, 165, 233, 0.35) !important;
            font-weight: 600;
        }

        .nav-pills .nav-link.active *,
        .nav-pills .nav-link.active i,
        .nav-pills .nav-link.active span {
            color: #ffffff !important; /* Đảm bảo cả icon và text span đều có màu trắng */
        }

        /* Nav Tabs (Chuẩn evondevKit: Tab phẳng gạch chân xanh) */
        .nav-tabs .nav-link {
            color: var(--kw-text-muted) !important;
            font-weight: 500;
            border: 1px solid transparent;
            border-top-left-radius: var(--kw-radius-sm);
            border-top-right-radius: var(--kw-radius-sm);
        }

        .nav-tabs .nav-link:hover {
            color: var(--kw-primary-hover) !important;
            border-color: transparent;
        }

        .nav-tabs .nav-link.active {
            color: var(--kw-primary-hover) !important;
            background-color: #ffffff !important;
            border-color: var(--kw-border) var(--kw-border) #ffffff !important;
            border-bottom: 2px solid var(--kw-primary) !important;
            font-weight: 600;
        }

        /* ==========================================================================
           QUY CHUẨN THIẾT KẾ CARD THEO EVONDEVKIT & UI-UX SPECIFICATION
           ==========================================================================
           - Viền: Đường tóc 1px sắc nét (#cbd5e1), không mờ nhạt, tách biệt rõ trên canvas
           - Bo góc: 12px (var(--kw-card-radius))
           - Nền: Trắng tinh khiết (#ffffff)
           - Đổ bóng: Tối giản (0 1px 3px rgba(15,23,42,0.04)), tuân thủ luật M13 & M15
           - Padding Quy chuẩn:
             * .card-p-standard (20px / 1.25rem - chuẩn p-5 evondevKit): Dành cho stat card & card nội dung
             * .card-p-compact  (16px / 1rem): Dành cho widget nhỏ hoặc side items
             * .card-p-roomy    (24px / 1.5rem): Dành cho banner, hero block, form lớn
           - Header & Footer: Phân cách bằng hairline border 1px (#e2e8f0), padding đồng bộ
           ========================================================================== */
        .card-custom {
            border: 1px solid var(--kw-card-border) !important;
            border-radius: var(--kw-card-radius) !important;
            box-shadow: var(--kw-shadow-card) !important;
            background-color: var(--kw-card-bg) !important;
            transition: border-color 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .card-custom:hover {
            border-color: var(--kw-card-border-hover) !important;
            box-shadow: var(--kw-shadow-card-hover) !important;
        }

        /* Card Padding Standards */
        .card-p-standard, .card-custom.card-p-standard {
            padding: var(--kw-card-pad-standard) !important; /* 20px */
        }

        .card-p-compact, .card-custom.card-p-compact {
            padding: var(--kw-card-pad-compact) !important; /* 16px */
        }

        .card-p-roomy, .card-custom.card-p-roomy {
            padding: var(--kw-card-pad-roomy) !important; /* 24px */
        }

        /* Padding Utility Compatibility (Khắc phục triệt để lỗi khi dùng class p-3.5 trong Bootstrap) */
        .p-3\.5, .p-3-5 { padding: 1.25rem !important; }
        .pt-3\.5 { padding-top: 1.25rem !important; }
        .pb-3\.5 { padding-bottom: 1.25rem !important; }
        .px-3\.5 { padding-left: 1.25rem !important; padding-right: 1.25rem !important; }
        .py-3\.5 { padding-top: 1.25rem !important; padding-bottom: 1.25rem !important; }

        /* Card Header & Footer Quy chuẩn */
        .card-custom-header {
            padding: 1rem 1.25rem !important; /* 16px dọc, 20px ngang */
            border-bottom: 1px solid var(--kw-border-light) !important;
            background-color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .card-custom-footer {
            padding: 0.75rem 1.25rem !important; /* 12px dọc, 20px ngang */
            border-top: 1px solid var(--kw-border-light) !important;
            background-color: #f8fafc;
        }

        /* Card Stat Component Helpers */
        .card-stat-box {
            display: flex;
            flex-direction: column;
            height: 100%;
            justify-content: space-between;
        }

        .card-stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.85rem;
        }

        .card-stat-icon {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--kw-radius-sm);
            border: 1px solid transparent;
            flex-shrink: 0;
        }

        .card-stat-footer {
            margin-top: auto;
            padding-top: 0.75rem;
            border-top: 1px solid var(--kw-border-subtle);
            font-size: 0.775rem;
            color: var(--kw-text-muted);
            display: flex;
            align-items: center;
        }

        /* Buttons: Đổ bóng nhẹ, nét thanh mảnh, bo tròn vừa phải */
        .btn-kw-primary {
            background-color: var(--kw-primary);
            color: #ffffff !important;
            border: 1px solid rgba(14, 165, 233, 0.2);
            border-radius: var(--kw-radius-sm);
            font-weight: 500;
            font-size: 0.875rem;
            padding: 0.45rem 1rem;
            box-shadow: var(--kw-shadow-btn);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .btn-kw-primary:hover {
            background-color: var(--kw-primary-hover);
            box-shadow: var(--kw-shadow-btn-hover);
            transform: translateY(-1px);
        }

        .btn-kw-primary.active,
        .btn-kw-primary:active {
            background-color: var(--kw-primary) !important;
            border-color: var(--kw-primary) !important;
            color: #ffffff !important;
            font-weight: 600;
        }

        .btn-kw-primary.active *,
        .btn-kw-primary.active span,
        .btn-kw-primary.active i {
            color: #ffffff !important;
        }

        .btn-kw-outline {
            background-color: var(--kw-surface);
            color: var(--kw-text-main);
            border: 1px solid var(--kw-border);
            border-radius: var(--kw-radius-sm);
            font-weight: 500;
            font-size: 0.875rem;
            padding: 0.45rem 0.85rem;
            box-shadow: var(--kw-shadow-sm);
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .btn-kw-outline:hover {
            background-color: var(--kw-primary-light);
            border-color: var(--kw-primary-border);
            color: var(--kw-primary-hover);
            transform: translateY(-1px);
        }

        .btn-kw-outline.active,
        .btn-kw-outline:active {
            background-color: var(--kw-primary) !important;
            border-color: var(--kw-primary) !important;
            color: #ffffff !important;
            font-weight: 600;
            box-shadow: 0 1px 3px rgba(14, 165, 233, 0.25) !important;
        }

        .btn-kw-outline.active *,
        .btn-kw-outline.active span,
        .btn-kw-outline.active i {
            color: #ffffff !important;
        }

        .btn-kw-subtle {
            background-color: var(--kw-primary-light);
            color: var(--kw-primary-hover);
            border: 1px solid var(--kw-primary-border);
            border-radius: var(--kw-radius-sm);
            font-weight: 500;
            font-size: 0.875rem;
            padding: 0.45rem 0.85rem;
            box-shadow: var(--kw-shadow-sm);
            transition: all 0.15s ease;
        }

        .btn-kw-subtle:hover {
            background-color: var(--kw-primary-subtle);
            color: #0369a1;
            transform: translateY(-1px);
        }

        /* Form Controls: Nét thanh mảnh, focus xanh dương nhạt */
        .form-control, .form-select {
            border: 1px solid var(--kw-border);
            border-radius: var(--kw-radius-sm);
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
            color: var(--kw-text-main);
            background-color: var(--kw-surface);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--kw-primary);
            box-shadow: 0 0 0 3px var(--kw-primary-ring);
            outline: none;
        }

        .form-label {
            font-size: 0.825rem;
            font-weight: 500;
            color: #334155;
            margin-bottom: 0.35rem;
        }

        /* Badges: Nét thanh mảnh, màu xanh dương nhạt / pastel tinh tế */
        .badge-kw-blue {
            background-color: var(--kw-primary-light);
            color: #0284c7;
            border: 1px solid var(--kw-primary-border);
            font-weight: 500;
            font-size: 0.75rem;
            border-radius: 6px;
            padding: 0.25rem 0.55rem;
        }

        .badge-kw-slate {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid var(--kw-border);
            font-weight: 500;
            font-size: 0.75rem;
            border-radius: 6px;
            padding: 0.25rem 0.55rem;
        }

        .badge-kw-green {
            background-color: var(--kw-success-bg);
            color: #059669;
            border: 1px solid #a7f3d0;
            font-weight: 500;
            font-size: 0.75rem;
            border-radius: 6px;
            padding: 0.25rem 0.55rem;
        }

        /* Table: Thanh mảnh, viền mỏng, không rối mắt */
        .table-custom-wrapper {
            border: 1px solid var(--kw-border);
            border-radius: var(--kw-radius);
            overflow: hidden;
            background: var(--kw-surface);
            box-shadow: var(--kw-shadow-sm);
        }

        .table-custom {
            margin-bottom: 0;
            font-size: 0.875rem;
        }

        .table-custom thead th {
            background: #f8fafc;
            color: var(--kw-text-muted);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.75rem 1.25rem;
            border-bottom: 1px solid var(--kw-border-light);
        }

        .table-custom tbody td {
            padding: 0.85rem 1.25rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--kw-border-subtle);
            color: var(--kw-text-main);
        }

        .table-custom tbody tr:hover {
            background-color: #f8fbfe;
        }

        .table-custom tbody tr:last-child td {
            border-bottom: none;
        }

        .topic-thumb-sm {
            width: 44px;
            height: 44px;
            border-radius: var(--kw-radius-sm);
            object-fit: cover;
            border: 1px solid var(--kw-border);
        }

        /* Server Status Pill: Tinh tế thanh thoát */
        .server-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--kw-success-bg);
            color: #059669;
            border: 1px solid #a7f3d0;
            padding: 3px 10px;
            border-radius: var(--kw-radius-pill);
            font-size: 0.75rem;
            font-weight: 500;
        }

        .pulse-dot {
            width: 7px;
            height: 7px;
            background-color: var(--kw-success);
            border-radius: 50%;
            animation: kwPulse 1.8s infinite;
        }

        @keyframes kwPulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6); }
            70% { transform: scale(1); box-shadow: 0 0 0 5px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* Banner info / tip */
        .banner-light-blue {
            background-color: var(--kw-primary-light);
            border: 1px solid var(--kw-primary-border) !important;
            border-radius: var(--kw-card-radius) !important;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 1px 3px rgba(14, 165, 233, 0.05);
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-kw sticky-top">
        <div class="container-fluid max-w-7xl px-lg-4">
            <a class="navbar-brand" href="/">
                <span class="brand-icon">
                    <i class="bi bi-balloon-heart fs-6"></i>
                </span>
                <span>Kids World <span class="fw-normal text-muted" style="font-size: 0.85rem;">CMS</span></span>
            </a>
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <i class="bi bi-list fs-3 text-secondary"></i>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3 gap-1">
                    <li class="nav-item">
                        <a class="nav-link <?= ($_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '/dashboard') ? 'active' : '' ?>" href="/">
                            <i class="bi bi-grid me-1"></i> Bảng Điều Khiển
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'], '/packs') ? 'active' : '' ?>" href="/packs">
                            <i class="bi bi-collection me-1"></i> Quản Lý Gói Chủ Đề
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'], '/guide') ? 'active' : '' ?>" href="/guide">
                            <i class="bi bi-hdd-network me-1"></i> Cấu Hình Domain Cho App
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'], '/docs') ? 'active' : '' ?>" href="/docs">
                            <i class="bi bi-journal-bookmark me-1 text-primary"></i> Tiêu Chuẩn Bộ Học
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'], '/ai-generator') ? 'active' : '' ?>" href="/ai-generator">
                            <i class="bi bi-stars me-1 text-primary"></i> AI Content Studio
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <a href="/ai-generator" class="btn btn-kw-subtle">
                        <i class="bi bi-stars"></i> Tạo Bằng AI
                    </a>
                    <a href="/packs/create" class="btn btn-kw-primary">
                        <i class="bi bi-plus-lg"></i> Thêm Gói Mới
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container py-4">
        <!-- Notification Alert -->
        <?php if (isset($_GET['created'])): ?>
            <div class="alert alert-success d-flex align-items-center justify-content-between border-0 shadow-sm rounded-3 py-2 px-3 mb-4" style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0 !important;" role="alert">
                <div class="d-flex align-items-center gap-2 font-medium" style="font-size: 0.875rem;">
                    <i class="bi bi-check-circle-fill text-success"></i> Đã tạo thành công dữ liệu mới!
                </div>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif (isset($_GET['updated'])): ?>
            <div class="alert alert-info d-flex align-items-center justify-content-between border-0 shadow-sm rounded-3 py-2 px-3 mb-4" style="background: var(--kw-primary-light); color: #0369a1; border: 1px solid var(--kw-primary-border) !important;" role="alert">
                <div class="d-flex align-items-center gap-2 font-medium" style="font-size: 0.875rem;">
                    <i class="bi bi-info-circle-fill" style="color: var(--kw-primary);"></i> Đã cập nhật thông tin và tăng phiên bản version của gói thành công!
                </div>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif (isset($_GET['deleted'])): ?>
            <div class="alert alert-warning d-flex align-items-center justify-content-between border-0 shadow-sm rounded-3 py-2 px-3 mb-4" style="background: var(--kw-warning-bg); color: #92400e; border: 1px solid #fde68a !important;" role="alert">
                <div class="d-flex align-items-center gap-2 font-medium" style="font-size: 0.875rem;">
                    <i class="bi bi-trash3-fill text-warning"></i> Đã xóa mục được chọn khỏi cơ sở dữ liệu.
                </div>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php require $contentView; ?>
    </div>

    <!-- Reusable Image Picker Modal -->
    <?php require __DIR__ . '/image_picker_modal.php'; ?>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
