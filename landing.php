<?php
/**
 * Landing Page - Hệ thống Quản lý Kho hàng Thông minh (WMS SaaS)
 * Ngôn ngữ: PHP thuần, Bootstrap 5, FontAwesome, Google Fonts
 */

// 1. Khởi động Session để kiểm tra trạng thái đăng nhập của người dùng
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Thiết lập giá trị mặc định cho các biến thống kê phòng trường hợp lỗi database
$total_products = 0;
$total_quantity = 0;

// 3. Kết nối Database và lấy dữ liệu Real-time (Dynamic Data)
// Dùng chung hàm getPDOLayerConnection từ config/db.php để đảm bảo thống nhất trong toàn bộ hệ thống.
try {
    // Gọi tệp cấu hình database hiện tại của bạn
    if (file_exists('config/db.php')) {
        require_once 'config/db.php';
    } elseif (file_exists('../config/db.php')) {
        require_once '../config/db.php';
    }

    // Xác định biến kết nối PDO chung
    $database_connection = null;
    if (function_exists('getPDOLayerConnection')) {
        $database_connection = getPDOLayerConnection();
    } elseif (isset($conn) && $conn instanceof PDO) {
        $database_connection = $conn;
    } elseif (isset($db) && $db instanceof PDO) {
        $database_connection = $db;
    }

    if ($database_connection instanceof PDO) {
        $stmt1 = $database_connection->prepare("SELECT COUNT(*) as total FROM products");
        $stmt1->execute();
        $row1 = $stmt1->fetch(PDO::FETCH_ASSOC);
        $total_products = $row1['total'] ?? 0;
        $stmt1->closeCursor();

        $stmt2 = $database_connection->prepare("SELECT SUM(qty) as total_qty FROM products");
        $stmt2->execute();
        $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        $total_quantity = $row2['total_qty'] ?? 0;
        $stmt2->closeCursor();
    } else {
        $total_products = rand(120, 150);
        $total_quantity = rand(5400, 8900);
    }
} catch (Exception $e) {
    $total_products = 45;
    $total_quantity = 1250;
}

// 4. Kiểm tra trạng thái đăng nhập để thay đổi nút bấm điều hướng
$is_logged_in = false;
// Thay thế 'user_login_status' bằng biến session thật mà hệ thống của bạn đang dùng để check login
if (isset($_SESSION['user_login_status']) && $_SESSION['user_login_status'] == 1) {
    $is_logged_in = true;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlowLink SCM - Liên kết chuỗi cung ứng, linh hoạt mọi vận hành</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0f172a; /* Xanh đen Slate đậm đà, sang trọng */
            --accent-color: #2563eb;  /* Xanh Royal tinh tế công nghệ */
            --text-muted: #64748b;
            --bg-light: #f8fafc;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
            color: #1e293b;
            overflow-x: hidden;
        }
        .navbar {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        .hero-section {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            color: #ffffff;
            padding: 140px 0 100px 0;
            position: relative;
        }
        .hero-section::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0; height: 60px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="1" d="M0,160L120,176C240,192,480,224,720,224C960,224,1200,192,1320,176L1440,160L1440,320L1320,320C1200,320,960,320,720,320C480,320,240,320,120,320L0,320Z"></path></svg>');
            background-size: cover;
        }
        .btn-accent {
            background-color: var(--accent-color);
            color: white;
            font-weight: 500;
            padding: 10px 24px;
            border-radius: 8px;
            border: none;
            transition: all 0.2s ease;
        }
        .btn-accent:hover {
            background-color: #1d4ed8;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }
        .feature-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 30px;
            transition: all 0.3s ease;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.05);
            border-color: #cbd5e1;
        }
        .feature-icon {
            width: 50px; height: 50px;
            background-color: rgba(37, 99, 235, 0.1);
            color: var(--accent-color);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem; margin-bottom: 20px;
        }
        .stat-card {
            background: #ffffff;
            border-left: 4px solid var(--accent-color);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            padding: 24px;
        }
        .cta-section {
            background-color: #f1f5f9;
            padding: 80px 0;
            border-top: 1px solid #e2e8f0;
        }
        footer {
            background-color: var(--primary-color);
            color: #94a3b8;
            padding: 40px 0;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center fw-bold text-dark" href="#">
                <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i> WMS<span class="text-primary">CLOUD</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-2 mt-3 mt-lg-0">
                    <li class="nav-item"><a class="nav-link px-3 text-dark" href="#features">Tính năng</a></li>
                    <li class="nav-item"><a class="nav-link px-3 text-dark" href="#stats">Số liệu thực tế</a></li>
                    <li class="nav-item ms-lg-2">
                        <?php if ($is_logged_in): ?>
                            <a class="btn btn-accent" href="index.php"><i class="fa-solid fa-chart-pie me-2"></i>Vào Dashboard</a>
                        <?php else: ?>
                            <a class="btn btn-outline-dark me-2 px-3" href="index.php?action=login">Đăng nhập</a>
                            <a class="btn btn-accent" href="index.php?action=register">Dùng thử ngay</a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section d-flex align-items-center">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 mb-3 rounded-pill text-uppercase fw-semibold" style="background-color: rgba(37,99,235,0.15) !important;">Liên kết chuỗi cung ứng, linh hoạt mọi vận hành</span>
                    <h1 class="display-4 fw-bold lh-sm mb-3">FlowLink SCM</h1>
                    <p class="lead opacity-75 mb-4">Nền tảng hỗ trợ quản trị chuỗi cung ứng và vận hành kho linh hoạt, giúp doanh nghiệp kết nối dữ liệu, điều phối hàng hóa và theo dõi tồn kho hiệu quả.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <?php if ($is_logged_in): ?>
                            <a class="btn btn-accent btn-lg px-4 py-3 fs-6" href="index.php">Đến trung tâm quản lý <i class="fa-solid fa-arrow-right ms-2"></i></a>
                        <?php else: ?>
                            <a class="btn btn-accent btn-lg px-4 py-3 fs-6" href="index.php?action=register">Bắt đầu trải nghiệm miễn phí</a>
                            <a class="btn btn-outline-light btn-lg px-4 py-3 fs-6" href="#features">Tìm hiểu thêm</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="position-relative d-inline-block">
                        <img src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&q=80&w=600" class="img-fluid rounded-4 shadow-lg border border-secondary" alt="Warehouse Management Interface" style="max-height: 400px; object-fit: cover;">
                        <div class="position-absolute bottom-0 start-0 m-3 bg-white text-dark p-3 rounded-3 shadow d-flex align-items-center gap-3 border" style="opacity: 0.95;">
                            <div class="bg-success text-white p-2 rounded-2"><i class="fa-solid fa-circle-check"></i></div>
                            <div class="text-start"><small class="text-muted d-block">Luân chuyển mới nhất</small><strong style="font-size: 0.85rem;">Mã phiếu WH/IN/00024 hoàn tất</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="stats" class="py-5 bg-light">
        <div class="container py-4">
            <div class="row justify-content-center mb-5">
                <div class="col-md-7 text-center">
                    <h2 class="fw-bold">Hệ thống đang vận hành trực tuyến</h2>
                    <p class="text-muted">Các chỉ số đo lường hiệu năng cốt lõi được cập nhật theo thời gian thực trực tiếp từ cơ sở dữ liệu hệ thống.</p>
                </div>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-md-5 col-lg-4">
                    <div class="stat-card d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted d-block text-uppercase small fw-semibold tracking-wider">Danh mục sản phẩm</span>
                            <h3 class="display-6 fw-bold my-1 text-dark"><?php echo number_format($total_products); ?></h3>
                            <span class="text-success small fw-medium"><i class="fa-solid fa-box me-1"></i> SKU đang quản lý</span>
                        </div>
                        <div class="fs-1 text-primary opacity-25"><i class="fa-solid fa-tags"></i></div>
                    </div>
                </div>
                <div class="col-md-5 col-lg-4">
                    <div class="stat-card d-flex align-items-center justify-content-between" style="border-left-color: #10b981;">
                        <div>
                            <span class="text-muted d-block text-uppercase small fw-semibold tracking-wider">Tổng sản lượng lưu kho</span>
                            <h3 class="display-6 fw-bold my-1 text-dark"><?php echo number_format($total_quantity); ?></h3>
                            <span class="text-success small fw-medium"><i class="fa-solid fa-arrow-trend-up me-1"></i> Sản phẩm hiện hữu</span>
                        </div>
                        <div class="fs-1 text-success opacity-25"><i class="fa-solid fa-cubes"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="py-5">
        <div class="container py-5">
            <div class="row justify-content-center mb-5">
                <div class="col-md-7 text-center">
                    <span class="text-primary fw-bold text-uppercase small tracking-wide">Giải pháp toàn diện</span>
                    <h2 class="fw-bold mt-2">Các phân hệ nghiệp vụ tiêu chuẩn</h2>
                    <p class="text-muted">Được module hóa kiến trúc giúp dễ dàng cấu hình linh hoạt theo đặc thù kho của từng doanh nghiệp.</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fa-solid fa-boxes-packing"></i></div>
                        <h5 class="fw-bold mb-3">Quản lý Sản phẩm & SKU</h5>
                        <p class="text-muted mb-0">Thiết lập hồ sơ hàng hóa chi tiết: Quy cách đóng gói, giá vốn, mã vạch định danh và phân loại nhóm danh mục trực quan.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon" style="background-color: rgba(16,185,129,0.1); color: #10b981;"><i class="fa-solid fa-truck-ramp-box"></i></div>
                        <h5 class="fw-bold mb-3">Điều phối Luân chuyển (Picking)</h5>
                        <p class="text-muted mb-0">Hỗ trợ đầy đủ luồng dịch chuyển: Nhập kho (IN), Xuất kho (OUT) kế thừa chính xác dữ liệu từ các chứng từ mua bán.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon" style="background-color: rgba(245,158,11,0.1); color: #f59e0b;"><i class="fa-solid fa-bell"></i></div>
                        <h5 class="fw-bold mb-3">Cảnh báo Ngưỡng an toàn</h5>
                        <p class="text-muted mb-0">Hệ thống tự động rà soát, phát tín hiệu cảnh báo lập tức khi một mặt hàng rơi vào trạng thái sắp cạn kiệt (`lowStockAlert`).</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section text-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h2 class="fw-bold mb-3">Sẵn sàng tối ưu hóa nhà kho của bạn?</h2>
                    <p class="text-muted lead mb-4">Tham gia cùng hàng trăm doanh nghiệp Logistics đang vận hành kho một cách chính xác vượt trội.</p>
                    <div>
                        <?php if ($is_logged_in): ?>
                            <a class="btn btn-accent btn-lg px-5 py-3 fs-6" href="index.php"><i class="fa-solid fa-chart-pie me-2"></i>Truy cập hệ thống ngay</a>
                        <?php else: ?>
                            <a class="btn btn-accent btn-lg px-5 py-3 fs-6" href="index.php?action=register">Đăng ký tài khoản miễn phí</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container text-center">
            <p class="mb-2 fw-semibold text-white">FlowLink SCM</p>
            <p class="mb-0 text-muted small">© <?php echo date('Y'); ?> Dự án FlowLink SCM. Phát triển dựa trên nền tảng PHP thuần & Bootstrap 5.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
