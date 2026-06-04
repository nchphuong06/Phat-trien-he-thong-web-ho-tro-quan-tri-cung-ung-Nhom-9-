<?php
/**
 * About Us Page - FlowLink SCM
 * Ngôn ngữ: PHP thuần, Bootstrap 5
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check login status
$is_logged_in = false;
if (isset($_SESSION['user_login_status']) && $_SESSION['user_login_status'] == 1) {
    $is_logged_in = true;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - FlowLink SCM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1E2A38; /* Primary Navy */
            --accent-color: #178978;  /* Accent Emerald */
            --text-muted: #64748b;
            --bg-light: #F4F6F9;
        }
        body {
            font-family: 'Roboto', sans-serif !important;
            color: #1e293b;
            overflow-x: hidden;
            background-color: var(--bg-light);
        }
        .navbar {
            background-color: var(--primary-color);
            backdrop-filter: blur(6px);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: all 0.3s ease;
        }
        .hero-section {
            background: linear-gradient(180deg, rgba(30,42,56,0.95) 0%, rgba(23,137,120,0.05) 100%);
            color: #ffffff;
            padding: 120px 0 80px 0;
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
            font-weight: 600;
            padding: 10px 24px;
            border-radius: 8px;
            border: none;
            transition: all 0.18s ease;
        }
        .btn-accent:hover {
            background-color: #149061;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(23,137,120,0.12);
        }
        footer {
            background-color: var(--primary-color);
            color: #cbd5da;
            padding: 48px 0 28px 0;
            font-size: 0.95rem;
        }
        .footer-link { color: rgba(255,255,255,0.9); text-decoration: none; }
        .footer-small { color: rgba(255,255,255,0.7); }
        .social-icon { color: white; opacity: 0.9; margin-left: 8px; }
        .about-content {
            background: white;
            padding: 60px 0;
            border-top: 1px solid rgba(30,42,56,0.04);
        }
        .about-text {
            font-family: 'Roboto', sans-serif;
            color: #1E2A38;
            font-size: 1.05rem;
            line-height: 1.8;
            text-align: left;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center fw-bold text-white" href="landing.php" style="gap:10px;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <rect x="2" y="2" width="8" height="8" stroke="white" stroke-width="1.2" fill="none" rx="1" />
                    <rect x="14" y="2" width="8" height="8" stroke="white" stroke-width="1.2" fill="none" rx="1" />
                    <rect x="2" y="14" width="8" height="8" stroke="white" stroke-width="1.2" fill="none" rx="1" />
                    <rect x="14" y="14" width="8" height="8" stroke="white" stroke-width="1.2" fill="none" rx="1" />
                </svg>
                <span style="font-size:1rem;">FlowLink <span style="color: #178978;">SCM</span></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-2 mt-3 mt-lg-0">
                    <li class="nav-item"><a class="nav-link px-3 text-white" href="landing.php#features">Tính năng</a></li>
                    <li class="nav-item"><a class="nav-link px-3 text-white" href="about.php">About us</a></li>
                    <li class="nav-item"><a class="nav-link px-3 text-white" href="landing.php#stats">Số liệu thực tế</a></li>
                    <li class="nav-item ms-lg-2">
                        <?php if ($is_logged_in): ?>
                            <a class="btn btn-accent" href="index.php"><i class="fa-solid fa-chart-pie me-2"></i>Vào Dashboard</a>
                        <?php else: ?>
                            <a class="btn btn-outline-light me-2 px-3" href="index.php?action=login">Đăng nhập</a>
                            <a class="btn btn-accent" href="index.php?action=register">Dùng thử ngay</a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section d-flex align-items-center">
        <div class="container-fluid px-4 px-md-5">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <h1 style="font-size: 2.8rem; font-weight: 700; margin-bottom: 20px; line-height: 1.15;">
                        Về FlowLink SCM
                    </h1>
                    <p style="font-size: 1.15rem; opacity: 0.95; margin-bottom: 30px; line-height: 1.7;">
                        Hệ thống quản trị chuỗi cung ứng toàn diện dành cho các doanh nghiệp logistics hiện đại.
                    </p>
                </div>
                <div class="col-lg-6">
                    <div style="background: rgba(255,255,255,0.1); border-radius: 16px; padding: 40px; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); text-align: center;">
                        <p style="font-size: 1.2rem; font-weight: 600; margin-bottom: 15px;">Giải pháp kỹ thuật số</p>
                        <p style="opacity: 0.9;">Tối ưu hóa hiệu suất khai thác mặt bằng kho lên đến 200%</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="about-content">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <p class="about-text">
                        FlowLink SCM là hệ thống quản trị chuỗi cung ứng và điều phối vận hành kho bãi toàn diện. Chúng tôi mang đến giải pháp chuyển đổi số mạnh mẽ, giúp tối ưu hóa 200% hiệu suất khai thác mặt bằng, tự động hóa luồng luân chuyển hàng hóa (Picking) từ chứng từ thực tế, và thiết lập hệ thống cảnh báo ngưỡng an toàn thông minh. Với tư duy thiết kế tối giản và tập trung, FlowLink SCM loại bỏ hoàn toàn các thao tác thủ công phức tạp, giúp doanh nghiệp kiểm soát chính xác mọi biến động vật chất trong kho theo thời gian thực.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="row mb-4 align-items-start">
                <div class="col-12 d-flex align-items-center mb-3">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="margin-right:12px;">
                        <rect x="2" y="2" width="8" height="8" stroke="white" stroke-width="1.2" fill="none" rx="1" />
                        <rect x="14" y="2" width="8" height="8" stroke="white" stroke-width="1.2" fill="none" rx="1" />
                        <rect x="2" y="14" width="8" height="8" stroke="white" stroke-width="1.2" fill="none" rx="1" />
                        <rect x="14" y="14" width="8" height="8" stroke="white" stroke-width="1.2" fill="none" rx="1" />
                    </svg>
                    <div>
                        <div style="font-weight:700; color: white; font-size:1.05rem;">FlowLink <span style="color: #17B978;">SCM</span></div>
                        <div class="footer-small">Kết nối dữ liệu — Điều phối thông minh — Tối ưu vận hành</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <h6 class="text-white fw-bold">GIẢI PHÁP (Mô-đun)</h6>
                    <ul class="list-unstyled mt-3">
                        <li class="mb-2"><span class="footer-small">Quản lý kho hàng (WMS)</span></li>
                        <li class="mb-2"><span class="footer-small">Điều phối dịch chuyển</span></li>
                        <li class="mb-2"><span class="footer-small">Kiểm kê & Đối soát</span></li>
                        <li class="mb-2"><span class="footer-small">Báo cáo vĩ mô</span></li>
                        <li class="mb-2"><span class="footer-small">Trợ lý AI Cố vấn</span></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="text-white fw-bold">GIỚI THIỆU CÔNG TY</h6>
                    <ul class="list-unstyled mt-3">
                        <li class="mb-2"><a class="footer-link" href="about.php">About us</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="text-white fw-bold">KHUNG THÔNG TIN ĐỊNH DANH</h6>
                    <p class="footer-small mt-3">FlowLink SCM là hệ thống quản trị chuỗi cung ứng và vận hành linh hoạt, giúp kết nối dữ liệu, tối ưu quy trình và nâng cao hiệu quả điều phối cho mọi quy mô doanh nghiệp.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-12 text-center footer-small">© <?php echo date('Y'); ?> FlowLink SCM. All rights reserved.</div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
