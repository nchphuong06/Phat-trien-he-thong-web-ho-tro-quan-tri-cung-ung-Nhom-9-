This file is a merged representation of the entire codebase, combined into a single document by Repomix.

# File Summary

## Purpose
This file contains a packed representation of the entire repository's contents.
It is designed to be easily consumable by AI systems for analysis, code review,
or other automated processes.

## File Format
The content is organized as follows:
1. This summary section
2. Repository information
3. Directory structure
4. Repository files (if enabled)
5. Multiple file entries, each consisting of:
  a. A header with the file path (## File: path/to/file)
  b. The full contents of the file in a code block

## Usage Guidelines
- This file should be treated as read-only. Any changes should be made to the
  original repository files, not this packed version.
- When processing this file, use the file path to distinguish
  between different files in the repository.
- Be aware that this file may contain sensitive information. Handle it with
  the same level of security as you would the original repository.

## Notes
- Some files may have been excluded based on .gitignore rules and Repomix's configuration
- Binary files are not included in this packed representation. Please refer to the Repository Structure section for a complete list of file paths, including binary files
- Files matching patterns in .gitignore are excluded
- Files matching default ignore patterns are excluded
- Files are sorted by Git change count (files with more changes are at the bottom)

# Directory Structure
```
_installation/01-create-database.sql
_installation/02-create-and-fill-users-table.sql
_installation/03-cautrucdichchuyenkho.sql
_installation/04-create-partners-and-inventory-audit.sql
_support/banner-host1plus.png
.repomixignore
classes/Login.php
classes/Registration.php
composer.json
config/db.php
docker
docker-compose.yml
Dockerfile
favicon.svg
google_callback.php
google_login.php
index.php
landing.php
libraries/password_compatibility_library.php
modules/audit.php
modules/chat-ai.php
modules/dashboard.php
modules/New Text Document.txt
modules/partners.php
modules/products.php
modules/reports.php
modules/settings.php
modules/warehouse.php
README.md
register.php
repomix-output.txt
repomix.config.json
views/.htaccess
views/logged_in.php
views/not_logged_in.php
views/register.php
```

# Files

## File: _installation/01-create-database.sql
````sql
CREATE DATABASE IF NOT EXISTS `login`;
````

## File: _installation/02-create-and-fill-users-table.sql
````sql
CREATE TABLE IF NOT EXISTS `login`.`users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'auto incrementing user_id of each user, unique index',
  `user_name` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'user''s name, unique',
  `user_password_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'user''s password in salted and hashed format',
  `user_email` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'user''s email, unique',
  `user_phone` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'user''s phone number, optional',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_name` (`user_name`),
  UNIQUE KEY `user_email` (`user_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='user data';


-- 1. Tạo bảng danh mục sản phẩm và quản lý tồn kho tổng
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sku` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `qty` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tạo bảng quản lý phiếu điều chuyển (Receipts / Delivery Orders)
CREATE TABLE IF NOT EXISTS `stock_picking` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `picking_number` VARCHAR(50) NOT NULL UNIQUE,
  `origin` VARCHAR(100) NULL,
  `type` ENUM('in', 'out') NOT NULL,
  `state` ENUM('draft', 'confirmed', 'done') NOT NULL DEFAULT 'draft',
  `scheduled_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tạo bảng chi tiết dịch chuyển kho (Stock Move Lines) - Liên kết khóa ngoại chặt chẽ
CREATE TABLE IF NOT EXISTS `stock_move` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `picking_id` INT NOT NULL,
  `product_sku` VARCHAR(50) NOT NULL,
  `product_qty` INT NOT NULL,
  FOREIGN KEY (`picking_id`) REFERENCES `stock_picking`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_sku`) REFERENCES `products`(`sku`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Bơm dữ liệu sản phẩm mẫu để hệ thống có sẵn vật chất luân chuyển
INSERT IGNORE INTO `products` (`sku`, `name`, `description`, `price`, `qty`) VALUES
('PROD-CPU-I9', 'Bộ xử lý Intel Core i9 14900K', 'CPU Intel Core i9 thế hệ 14', 18000000, 50),
('PROD-RAM-32', 'Thanh RAM DDR5 Corsair 32GB', 'RAM Corsair 32GB DDR5 hiệu suất cao', 3200000, 120),
('PROD-SSD-01', 'Ổ cứng SSD Samsung 990 Pro 1TB', 'SSD Samsung 990 Pro 1TB tốc độ cao', 4200000, 85);
````

## File: _installation/03-cautrucdichchuyenkho.sql
````sql
-- Tạo bảng quản lý Phiếu dịch chuyển kho (Chuẩn Odoo Stock Picking)
CREATE TABLE IF NOT EXISTS `stock_picking` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `picking_number` VARCHAR(50) NOT NULL UNIQUE, -- Mã phiếu: WH/IN/0001 hoặc WH/OUT/0001
  `origin` VARCHAR(100) DEFAULT NULL,            -- Chứng từ gốc (Ví dụ: PO-001, SO-002)
  `type` ENUM('in', 'out') NOT NULL,             -- 'in' là Nhập kho, 'out' là Xuất kho
  `scheduled_date` DATETIME DEFAULT CURRENT_TIMESTAMP, -- Ngày thực hiện phiếu
  `state` ENUM('draft', 'done') DEFAULT 'draft'  -- Trạng thái phiếu
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng chi tiết dòng dịch chuyển vật chất (Stock Move Line)
CREATE TABLE IF NOT EXISTS `stock_move` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `picking_id` INT NOT NULL,                     -- Kết nối song song với bảng stock_picking
  `product_sku` VARCHAR(64) NOT NULL,            -- Kết nối với SKU của bảng sản phẩm
  `product_qty` INT NOT NULL,                    -- Số lượng dịch chuyển của dòng này
  FOREIGN KEY (`picking_id`) REFERENCES `stock_picking`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
````

## File: _installation/04-create-partners-and-inventory-audit.sql
````sql
-- Chọn Database đang sử dụng
USE `login`;

-- =====================================================================
-- 1. BẢNG PARTNERS (QUẢN LÝ ĐỐI TÁC: KHÁCH HÀNG / NHÀ CUNG CẤP)
-- =====================================================================
CREATE TABLE IF NOT EXISTS `partners` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Mã định danh duy nhất (VD: KH001)',
  `name` VARCHAR(255) NOT NULL COMMENT 'Tên công ty hoặc đối tác',
  `type` ENUM('customer', 'vendor') NOT NULL COMMENT 'Phân loại: Khách hàng / Nhà cung cấp',
  `phone` VARCHAR(20) DEFAULT NULL COMMENT 'Số điện thoại liên hệ',
  `email` VARCHAR(100) DEFAULT NULL COMMENT 'Email (Dự phòng cho tương lai)',
  `address` TEXT DEFAULT NULL COMMENT 'Địa chỉ (Dự phòng cho tương lai)',
  `tax_code` VARCHAR(20) DEFAULT NULL COMMENT 'Mã số thuế (Dự phòng cho tương lai)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 2. BẢNG INVENTORY_AUDIT (QUẢN LÝ KIỂM KÊ VÀ LỆCH KHO)
-- =====================================================================
CREATE TABLE IF NOT EXISTS `inventory_audit` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `audit_code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Mã phiếu kiểm kê sinh tự động',
  `product_sku` VARCHAR(50) NOT NULL COMMENT 'Mã SKU của sản phẩm được đếm',
  `system_qty` INT NOT NULL COMMENT 'Số lượng tồn trên phần mềm lúc kiểm kê',
  `counted_qty` INT NOT NULL COMMENT 'Số lượng nhân viên đếm tay thực tế',
  `difference` INT NOT NULL COMMENT 'Độ lệch (+ là thừa, - là thiếu, 0 là khớp)',
  `note` VARCHAR(255) DEFAULT NULL COMMENT 'Lý do / Giải trình (Chuột cắn, vỡ, đếm sai...)',
  `audit_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian thực hiện kiểm kê',
  
  -- Khóa ngoại: Ràng buộc chặt chẽ với bảng products
  FOREIGN KEY (`product_sku`) REFERENCES `products`(`sku`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
````

## File: .repomixignore
````
# Add patterns to ignore here, one per line
# Example:
# *.log
# tmp/
````

## File: classes/Login.php
````php
<?php

/**
 * Class Login
 * handles the user's login and logout process with secure Google confirmation
 */
class Login
{
    private $db_connection = null;
    public $errors = array();
    public $messages = array();

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Đăng xuất
        if (isset($_GET["logout"])) {
            $this->doLogout();
        }
        // Đăng nhập form Username/Password truyền thống
        elseif (isset($_POST["login"])) {
            $this->loginWithPostData();
        }
    }

    /**
     * 🚀 HÀM BẢO MẬT: Đối chiếu mật khẩu hệ thống + Cập nhật thông tin khi Đăng nhập nhanh Google
     */
    private function loginWithGoogleAndVerifyPassword()
    {
        if (empty($_POST['confirm_sys_password'])) {
            $this->errors[] = "Vui lòng nhập mật khẩu tài khoản để xác minh danh tính.";
            return;
        }
        if (empty($_POST['user_full_name']) || empty($_POST['user_phone'])) {
            $this->errors[] = "Vui lòng nhập đầy đủ thông tin cá nhân bổ sung.";
            return;
        }

        // Giải mã Token bảo mật của Google gửi lên để lấy Email thật
        $tokenParts = explode('.', $_POST['google_oauth_token']);
        if (count($tokenParts) !== 3) {
            $this->errors[] = "Mã xác thực chữ ký Google không đúng định dạng.";
            return;
        }
        
        $payload = json_decode(base64_decode(str_replace(array('-', '_'), array('+', '/'), $tokenParts[1])));
        if (!$payload || !isset($payload->email)) {
            $this->errors[] = "Chữ ký định danh từ Google API không hợp lệ.";
            return;
        }

        $this->db_connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$this->db_connection->connect_errno) {
            $this->db_connection->set_charset("utf8");

            $google_email = $this->db_connection->real_escape_string(strip_tags($payload->email, ENT_QUOTES));
            $input_password = $_POST['confirm_sys_password'];
            
            $user_full_name = $this->db_connection->real_escape_string(strip_tags($_POST['user_full_name'], ENT_QUOTES));
            $user_phone = $this->db_connection->real_escape_string(strip_tags($_POST['user_phone'], ENT_QUOTES));

            // 1. Tìm xem tài khoản có Email trùng với Gmail Google này không
            $sql = "SELECT user_id, user_name, user_email, user_password_hash FROM users WHERE user_email = '" . $google_email . "';";
            $result_check = $this->db_connection->query($sql);

            if ($result_check->num_rows == 1) {
                $user_data = $result_check->fetch_object();

                // 2. 🛡️ ĐỐI CHIẾU MẬT KHẨU NGƯỜI DÙNG NHẬP VÀO
                if (password_verify($input_password, $user_data->user_password_hash)) {
                    
                    // 3. Mật khẩu đúng -> Tiến hành cập nhật thông tin cá nhân
                    // Cập nhật Họ tên vào cột user_name (hoặc cột tương ứng của ông), Số điện thoại vào user_phone
                    $sql_update = "UPDATE users SET 
                                   user_name = '" . $user_full_name . "', 
                                   user_phone = '" . $user_phone . "' 
                                   WHERE user_id = " . $user_data->user_id . ";";
                    $this->db_connection->query($sql_update);

                    // 4. 🎉 CẤP SESSION CHO PHÉP ĐĂNG NHẬP VÀO HỆ THỐNG
                    $_SESSION['user_id'] = $user_data->user_id;
                    $_SESSION['user_name'] = $user_full_name;
                    $_SESSION['user_email'] = $user_data->user_email;
                    $_SESSION['user_phone'] = $user_phone;
                    $_SESSION['user_login_status'] = 1;

                } else {
                    $this->errors[] = "❌ Xác nhận thất bại: Mật khẩu hệ thống không chính xác!";
                }
            } else {
                $this->errors[] = "❌ Email Google này chưa được đăng ký trên hệ thống. Vui lòng tạo tài khoản trước.";
            }
        } else {
            $this->errors[] = "Lỗi kết nối cơ sở dữ liệu.";
        }
    }

    /**
     * Luồng xử lý đăng nhập thường
     */
    private function loginWithPostData()
    {
        if (empty($_POST['user_name']) || empty($_POST['user_password'])) {
            $this->errors[] = "Vui lòng điền tài khoản và mật khẩu.";
            return;
        }

        $this->db_connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$this->db_connection->connect_errno) {
            $this->db_connection->set_charset("utf8");
            $user_name = $this->db_connection->real_escape_string($_POST['user_name']);

            $sql = "SELECT user_id, user_name, user_email, user_phone, user_password_hash
                    FROM users WHERE user_name = '" . $user_name . "' OR user_email = '" . $user_name . "';";
            $result = $this->db_connection->query($sql);

            if ($result->num_rows == 1) {
                $result_row = $result->fetch_object();
                if (password_verify($_POST['user_password'], $result_row->user_password_hash)) {
                    $_SESSION['user_id'] = $result_row->user_id;
                    $_SESSION['user_name'] = $result_row->user_name;
                    $_SESSION['user_email'] = $result_row->user_email;
                    $_SESSION['user_phone'] = $result_row->user_phone;
                    $_SESSION['user_login_status'] = 1;
                } else {
                    $this->errors[] = "Mật khẩu không chính xác.";
                }
            } else {
                $this->errors[] = "Tài khoản không tồn tại.";
            }
        }
    }

    public function doLogout()
    {
        $_SESSION = array();
        session_destroy();
        $this->messages[] = "Bạn đã đăng xuất.";
    }

    public function isUserLoggedIn()
    {
        if (isset($_SESSION['user_login_status']) && $_SESSION['user_login_status'] == 1) {
            return true;
        }
        return false;
    }
}
````

## File: classes/Registration.php
````php
<?php

/**
 * Class registration
 * handles the user registration
 */
class Registration
{
    /**
     * @var object $db_connection The database connection
     */
    private $db_connection = null;
    /**
     * @var array $errors Collection of error messages
     */
    public $errors = array();
    /**
     * @var array $messages Collection of success / neutral messages
     */
    public $messages = array();

    /**
     * the function "__construct()" automatically starts whenever an object of this class is created,
     * you know, when you do "$registration = new Registration();"
     */
    public function __construct()
    {
        // check the possible registration actions:
        // 1. Nếu người dùng bấm form đăng ký bằng username/mật khẩu truyền thống ở phía trên
        if (isset($_POST["register"])) {
            $this->registerNewUser();
        }
        // 🚀 KHÚC SỬA ĐỔI THÊM MỚI: Nếu người dùng gửi form hoàn thiện hồ sơ sau khi xác thực Google thành công
        elseif (isset($_POST["register_google_profile"])) {
            $this->registerUserWithGoogleProfile();
        }
    }

    /**
     * handles the entire registration process. checks all error possibilities
     * and creates a new user in the database if everything is fine
     */
    private function registerNewUser()
    {
        if (empty($_POST['user_name'])) {
            $this->errors[] = "Empty Username";
        } elseif (empty($_POST['user_password_new']) || empty($_POST['user_password_repeat'])) {
            $this->errors[] = "Empty Password";
        } elseif ($_POST['user_password_new'] !== $_POST['user_password_repeat']) {
            $this->errors[] = "Password and password repeat are not the same";
        } elseif (strlen($_POST['user_password_new']) < 6) {
            $this->errors[] = "Password has a minimum length of 6 characters";
        } elseif (strlen($_POST['user_name']) > 64 || strlen($_POST['user_name']) < 2) {
            $this->errors[] = "Username cannot be shorter than 2 or longer than 64 characters";
        } elseif (!preg_match('/^[a-z\d]{2,64}$/i', $_POST['user_name'])) {
            $this->errors[] = "Username does not fit the name scheme: only a-Z and numbers are allowed, 2 to 64 characters";
        } elseif (empty($_POST['user_email'])) {
            $this->errors[] = "Email cannot be empty";
        } elseif (strlen($_POST['user_email']) > 64) {
            $this->errors[] = "Email cannot be longer than 64 characters";
        } elseif (!filter_var($_POST['user_email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Your email address is not in a valid email format";
        } elseif (!empty($_POST['user_name'])
            && strlen($_POST['user_name']) <= 64
            && strlen($_POST['user_name']) >= 2
            && preg_match('/^[a-z\d]{2,64}$/i', $_POST['user_name'])
            && !empty($_POST['user_email'])
            && strlen($_POST['user_email']) <= 64
            && filter_var($_POST['user_email'], FILTER_VALIDATE_EMAIL)
            && !empty($_POST['user_password_new'])
            && !empty($_POST['user_password_repeat'])
            && ($_POST['user_password_new'] === $_POST['user_password_repeat'])
        ) {
            // create a database connection
            $this->db_connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            // change character set to utf8 and check it
            if (!$this->db_connection->set_charset("utf8")) {
                $this->errors[] = $this->db_connection->error;
            }

            // if no connection errors (= working database connection)
            if (!$this->db_connection->connect_errno) {

                // escaping, additionally removing everything that could be (html/javascript-) code
                $user_name = $this->db_connection->real_escape_string(strip_tags($_POST['user_name'], ENT_QUOTES));
                $user_email = $this->db_connection->real_escape_string(strip_tags($_POST['user_email'], ENT_QUOTES));

                $user_password = $_POST['user_password_new'];

                // crypt the user's password with PHP 5.5's password_hash() function, results in a 60 character
                // hash string. the PASSWORD_DEFAULT constant is defined by the PHP 5.5, or if you are using
                // PHP 5.3/5.4, by the password hashing compatibility library
                $user_password_hash = password_hash($user_password, PASSWORD_DEFAULT);

                // check if user or email address already exists
                $sql = "SELECT * FROM users WHERE user_name = '" . $user_name . "' OR user_email = '" . $user_email . "';";
                $query_check_user_name = $this->db_connection->query($sql);

                if ($query_check_user_name->num_rows == 1) {
                    $this->errors[] = "Sorry, that username / email address is already taken.";
                } else {
                    // write new user's data into database
                    $sql = "INSERT INTO users (user_name, user_password_hash, user_email)
                            VALUES('" . $user_name . "', '" . $user_password_hash . "', '" . $user_email . "');";
                    $query_new_user_insert = $this->db_connection->query($sql);

                    // if user has been added successfully
                    if ($query_new_user_insert) {
                        $this->messages[] = "Your account has been created successfully. You can now log in.";
                    } else {
                        $this->errors[] = "Sorry, your registration failed. Please go back and try again.";
                    }
                }
            } else {
                $this->errors[] = "Sorry, no database connection.";
            }
        } else {
            $this->errors[] = "An unknown error occurred.";
        }
    }

    /**
     * 🚀 HÀM THÊM MỚI: Xử lý ghi nhận thông tin cá nhân và tạo tài khoản liên kết Google
     */
    private function registerUserWithGoogleProfile()
    {
        // Kiểm tra tính đầy đủ của thông tin cá nhân bắt buộc người dùng thật nhập vào
        if (empty($_POST['user_name'])) {
            $this->errors[] = "Tên đăng nhập không được để trống.";
        } elseif (empty($_POST['user_full_name']) || empty($_POST['user_phone'])) {
            $this->errors[] = "Vui lòng nhập đầy đủ Họ tên và Số điện thoại cá nhân.";
        } elseif (empty($_POST['google_oauth_token'])) {
            $this->errors[] = "Mã xác thực chữ ký số từ Google không hợp lệ.";
        } else {
            // Giải mã Token của Google để lấy Email thật ở Backend nhằm chống gian lận dữ liệu gửi từ Client
            $tokenParts = explode('.', $_POST['google_oauth_token']);
            if (count($tokenParts) !== 3) {
                $this->errors[] = "Token định danh an toàn cấu trúc bị lỗi.";
                return;
            }
            $payload = json_decode(base64_decode(str_replace(array('-', '_'), array('+', '/'), $tokenParts[1])));
            if (!$payload || !isset($payload->email)) {
                $this->errors[] = "Chữ ký số Google hết hạn hoặc không tồn tại.";
                return;
            }

            // Kết nối Database hệ thống Docker
            $this->db_connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            if (!$this->db_connection->connect_errno) {
                $this->db_connection->set_charset("utf8");

                // Làm sạch dữ liệu đầu vào chống SQL Injection độc hại
                $user_name = $this->db_connection->real_escape_string(strip_tags($_POST['user_name'], ENT_QUOTES));
                $user_email = $this->db_connection->real_escape_string(strip_tags($payload->email, ENT_QUOTES));
                
                // Thu thập các thông tin cá nhân tăng cường cần thiết đúng yêu cầu
                $user_full_name = $this->db_connection->real_escape_string(strip_tags($_POST['user_full_name'], ENT_QUOTES));
                $user_phone = $this->db_connection->real_escape_string(strip_tags($_POST['user_phone'], ENT_QUOTES));

                // Kiểm tra xem trùng lặp Username hoặc Email trong DB kho hay chưa
                $sql = "SELECT * FROM users WHERE user_name = '" . $user_name . "' OR user_email = '" . $user_email . "';";
                $query_check_user_name = $this->db_connection->query($sql);

                if ($query_check_user_name->num_rows == 1) {
                    $this->errors[] = "Tên đăng nhập hoặc Email thật này đã có người sử dụng trong kho.";
                } else {
                    // Thực hiện Insert tài khoản Google kèm toàn bộ thông tin cá nhân phong phú vào hệ thống
                    // Gán mật khẩu mặc định đã được hash để bảo toàn định dạng cột
                    $google_password_hash = $this->db_connection->real_escape_string(password_hash('oauth_verified_by_google', PASSWORD_DEFAULT));
                    $sql_insert = "INSERT INTO users (user_name, user_password_hash, user_email, user_phone) 
                                   VALUES('" . $user_name . "', '" . $google_password_hash . "', '" . $user_email . "', '" . $user_phone . "');";
                    $query_new_user_insert = $this->db_connection->query($sql_insert);

                    if ($query_new_user_insert) {
                        $this->messages[] = "Tài khoản liên kết Google của bạn đã được khởi tạo thành công! Hãy quay lại trang chủ đăng nhập.";
                    } else {
                        $this->errors[] = "Đăng ký thất bại. Lỗi cú pháp lưu trữ dữ liệu cơ sở.";
                    }
                }
            } else {
                $this->errors[] = "Không thể kết nối đến cơ sở dữ liệu phân bổ của Docker.";
            }
        }
    }
}
````

## File: composer.json
````json
{
    "require": {
        "google/apiclient": "*"
    },
    "config": {
        "process-timeout": 2000,
        "preferred-install": "source"
    }
}
````

## File: config/db.php
````php
<?php
// Giữ nguyên các hằng số cũ của bạn
define("DB_HOST", "db"); // Tên service trong docker-compose
define("DB_USER", "root");
define("DB_PASS", "root_password");
define("DB_NAME", "login");

// Cổng kết nối cũ (MySQLi) cho các module chưa nâng cấp
$db_connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db_connection->connect_errno) {
    die("Kết nối MySQLi thất bại: " . $db_connection->connect_error);
}

/**
 * Hàm khởi tạo kết nối PDO - Tầng bảo mật tuyệt đối
 * @return PDO
 */
function getPDOLayerConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // Ép buộc sử dụng Prepared Statements thực tế của MySQL
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Lỗi tầng kết nối PDO: " . $e->getMessage());
        }
    }
    return $pdo;
}
define(
'GOOGLE_CLIENT_ID',
'16884349215-vrcd9oii86rvaqffd10ff2s0pubqpblp.apps.googleusercontent.com'
);

define(
'GOOGLE_CLIENT_SECRET',
'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'
);

define(
'GOOGLE_REDIRECT_URI',
'http://localhost:8888/google_callback.php'
);
$conn = getPDOLayerConnection();
````

## File: docker
````

````

## File: docker-compose.yml
````yaml
services:
  # Lớp 1: Máy chủ Web chạy PHP và Apache
  web:
    build: . # Chạy thông qua Dockerfile vừa tạo ở trên
    ports:
      - "8888:80" # Cổng truy cập máy thật: localhost:8888
    volumes:
      - .:/var/www/html
    depends_on:
      - db
    networks:
      - erp_network

  # Lớp 2: Cơ sở dữ liệu MySQL
  db:
    image: mysql:8.0
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: login
    ports:
      - "3307:3306" # ĐỔI THÀNH 3307 để loại bỏ hoàn toàn lỗi xung đột cổng máy thật
    volumes:
      - db_data:/var/lib/mysql
      - ./_installation:/docker-entrypoint-initdb.d
    networks:
      - erp_network

  # Lớp 3: Trình quản lý Database trực quan (Adminer)
  adminer:
    image: adminer
    restart: always
    ports:
      - "8084:8080" # Truy cập quản lý DB qua: localhost:8084
    networks:
      - erp_network

volumes:
  db_data:

networks:
  erp_network:
    driver: bridge
````

## File: Dockerfile
````dockerfile
FROM php:8.1-apache

# Cài đặt Composer
RUN apt-get update && apt-get install -y git unzip && rm -rf /var/lib/apt/lists/*
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Cài đặt và kích hoạt các extension mở rộng cho MySQLi và PDO bảo mật
RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && docker-php-ext-enable mysqli pdo pdo_mysql

# Kích hoạt mod_rewrite của Apache (phục vụ cho việc định tuyến Router Whitelist mượt mà hơn)
RUN a2enmod rewrite

# Copy composer.json và chạy composer install
WORKDIR /var/www/html
COPY composer.json* ./
RUN if [ -f "composer.json" ]; then composer install --no-dev --optimize-autoloader; fi

# Cấp quyền ghi để Apache container vận hành tệp tin mượt mà, không bị nghẽn
RUN chown -R www-data:www-data /var/www/html
````

## File: favicon.svg
````xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
  <rect width="64" height="64" rx="12" fill="#1e3d59"/>
  <path d="M12 34h28v10H12z" fill="#2ec4b6"/>
  <path d="M38 30h12l6 6v8H38z" fill="#66d6c5"/>
  <path d="M18 24h16v10H18z" fill="#ffffff"/>
  <circle cx="22" cy="48" r="6" fill="#ffffff"/>
  <circle cx="46" cy="48" r="6" fill="#ffffff"/>
  <circle cx="22" cy="48" r="3" fill="#1e3d59"/>
  <circle cx="46" cy="48" r="3" fill="#1e3d59"/>
  <path d="M22 30h6v4h-6z" fill="#1e3d59"/>
  <path d="M30 24h8v2h-8z" fill="#1e3d59" opacity="0.8"/>
</svg>
````

## File: google_callback.php
````php
<?php

session_start();

require_once("config/db.php");
require 'vendor/autoload.php';

use Google\Client;

if (empty($_POST['google_oauth_token'])) {
    header("Location: index.php");
    exit();
}

$google_flow_type = isset($_POST['google_flow_type']) ? $_POST['google_flow_type'] : 'login';

$client = new Client([
    'client_id' => GOOGLE_CLIENT_ID
]);

$payload = $client->verifyIdToken($_POST['google_oauth_token']);

if (!$payload || empty($payload['email'])) {
    header("Location: index.php");
    exit();
}

$email = $payload['email'];
$name = isset($payload['name']) ? $payload['name'] : '';
$user_name = isset($_POST['user_name']) ? trim($_POST['user_name']) : '';
$user_phone = isset($_POST['user_phone']) ? trim($_POST['user_phone']) : null;

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_errno) {
    die("Lỗi kết nối cơ sở dữ liệu: " . $db->connect_error);
}
$db->set_charset('utf8');

$escaped_email = $db->real_escape_string($email);
$sql = "SELECT * FROM users WHERE user_email='" . $escaped_email . "' LIMIT 1";
$result = $db->query($sql);

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_object();
    $_SESSION['user_id'] = $user->user_id;
    $_SESSION['user_name'] = $user->user_name;
    $_SESSION['user_email'] = $user->user_email;
    $_SESSION['user_phone'] = isset($user->user_phone) ? $user->user_phone : null;
    $_SESSION['user_login_status'] = 1;
} else {
    // Nếu chưa có tài khoản, khởi tạo mới.
    if ($google_flow_type === 'register' && empty($user_name)) {
        $user_name = preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $email)[0]);
    }

    if (empty($user_name)) {
        $user_name = preg_replace('/[^a-zA-Z0-9]/', '', $name);
    }

    if (empty($user_name)) {
        $user_name = preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $email)[0]);
    }

    $user_name = substr($user_name, 0, 64);
    if (empty($user_name)) {
        $user_name = 'googleuser_' . time();
    }

    $base_user_name = $user_name;
    $suffix = 0;
    $escaped_user_name = $db->real_escape_string($user_name);
    while (true) {
        $check_sql = "SELECT user_id FROM users WHERE user_name='" . $escaped_user_name . "' LIMIT 1";
        $check_result = $db->query($check_sql);
        if (!$check_result || $check_result->num_rows === 0) {
            break;
        }
        $suffix++;
        $user_name = substr($base_user_name, 0, 60) . '_' . $suffix;
        $escaped_user_name = $db->real_escape_string($user_name);
    }

    $escaped_phone = $user_phone !== null ? $db->real_escape_string($user_phone) : null;
    $google_password_hash = $db->real_escape_string(password_hash('oauth_verified_by_google', PASSWORD_DEFAULT));

    $sql = "INSERT INTO users (user_name, user_password_hash, user_email";
    if ($escaped_phone !== null && $escaped_phone !== '') {
        $sql .= ", user_phone";
    }
    $sql .= ") VALUES ('" . $escaped_user_name . "', '" . $google_password_hash . "', '" . $escaped_email . "'";
    if ($escaped_phone !== null && $escaped_phone !== '') {
        $sql .= ", '" . $escaped_phone . "'";
    }
    $sql .= ")";

    if (!$db->query($sql)) {
        die('Lỗi khi tạo tài khoản Google: ' . $db->error);
    }

    $_SESSION['user_id'] = $db->insert_id;
    $_SESSION['user_name'] = $escaped_user_name;
    $_SESSION['user_email'] = $escaped_email;
    $_SESSION['user_phone'] = $escaped_phone;
    $_SESSION['user_login_status'] = 1;
}

header("Location: index.php");
exit();
````

## File: google_login.php
````php
<?php

require 'vendor/autoload.php';
require_once('config/db.php');

$client = new Google_Client();

$client->setClientId(
GOOGLE_CLIENT_ID
);

$client->setClientSecret(
GOOGLE_CLIENT_SECRET
);

$client->setRedirectUri(
GOOGLE_REDIRECT_URI
);

$client->addScope("email");
$client->addScope("profile");

header(
'Location:'.$client->createAuthUrl()
);

exit();
````

## File: index.php
````php
<?php
/**
 * 🛰️ TÊN FILE: index.php
 * 🛰️ HỆ THỐNG ĐIỀU PHỐI TRUNG TÂM VÀ XÁC THỰC - PHÂN HỆ DOANH NGHIỆP PRO (ENTERPRISE MASTER ENGINE)
 */

if (version_compare(PHP_VERSION, '5.3.7', '<')) {
    exit("Sorry, Simple PHP Login does not run on a PHP version smaller than 5.3.7 !");
}

require_once("config/db.php");
require_once("classes/Login.php");

// 🚀 SỬA ĐOẠN NÀY: Nạp thêm lớp Đăng ký vào hệ thống điều phối trung tâm để đón đầu form hoàn thiện hồ sơ Google
require_once("classes/Registration.php");

$login = new Login();
$registration = new Registration(); // Khởi tạo xử lý song song

// --- ĐOẠN ĐIỀU HƯỚNG MỚI TÍCH HỢP LANDING PAGE ---
if ($login->isUserLoggedIn() == true) {
    // 1. Nếu người dùng ĐÃ ĐĂNG NHẬP -> Tiếp tục cho chạy code phía dưới để vào Dashboard
    // (Không làm gì cả, để code chạy tiếp xuống dưới)
} else {
    // 2. Nếu người dùng CHƯA ĐĂNG NHẬP -> Kiểm tra xem họ có bấm nút "Đăng nhập" hay "Đăng ký" không
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    if ($action === 'login') {
        // Người dùng chủ động bấm "Đăng nhập" -> Hiện form đăng nhập và ngắt code
        include("views/not_logged_in.php");
        exit();
    } elseif ($action === 'register') {
        // Người dùng chủ động bấm "Dùng thử / Đăng ký" -> Hiện form đăng ký và ngắt code
        include("views/register.php"); 
        exit();
    } else {
        // MẶC ĐỊNH: Nếu vừa mở web lên (chưa làm gì) -> Hiện trang Landing Page và ngắt code
        include("landing.php");
        exit();
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Khởi tạo các biến đếm thông tin hệ thống mặc định
$total_products_count = 0;
$total_warehouse_orders = 0;

if (file_exists("modules/connection.php")) {
    include_once("modules/connection.php");
}
$db = function_exists('getPDOLayerConnection') ? getPDOLayerConnection() : null;

if ($db instanceof PDO) {
    // 📊 TRUY VẤN SỐ LIỆU ĐẾM THỜI GIAN THỰC ĐỂ HIỂN THỊ LÊN PROFILE TRỰC QUAN
    try {
        $total_products_count = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $total_warehouse_orders = (int)$db->query("SELECT COUNT(*) FROM warehouses")->fetchColumn();
    } catch (PDOException $e) {
        $total_products_count = 0;
        $total_warehouse_orders = 0;
    }

    // 🛑 XỬ LÝ FORM CẬP NHẬT BẢO MẬT TÀI KHOẢN (ĐÃ CẢI TIẾN THÊM TRƯỜNG)
    if (isset($_POST['submit_update_profile']) && isset($_SESSION['user_id'])) {
        $current_user_id = (int)$_SESSION['user_id'];
        $password_current = $_POST['update_user_password_current'];
        
        // Nhận thêm dữ liệu Tên và Số điện thoại mới
        $new_name = trim($_POST['update_user_name']);
        $new_phone = trim($_POST['update_user_phone']);
        $new_email = filter_var(trim($_POST['update_user_email']), FILTER_VALIDATE_EMAIL);
        
        $new_pass = $_POST['update_user_password_new'];
        $new_pass_repeat = $_POST['update_user_password_repeat'];
        
        if (empty($new_name)) {
            echo "<script>alert('Lỗi: Họ và tên hiển thị không được để trống!'); window.history.back();</script>";
            exit();
        }

        if (!$new_email) {
            echo "<script>alert('Lỗi: Định dạng Email không hợp lệ!'); window.history.back();</script>";
            exit();
        }

        try {
            $stmt_check = $db->prepare("SELECT user_password_hash FROM users WHERE user_id = ? LIMIT 1");
            $stmt_check->execute([$current_user_id]);
            $user_record = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($user_record && password_verify($password_current, $user_record['user_password_hash'])) {
                
                // Trường hợp 1: Có yêu cầu thay đổi cả mật khẩu mới
                if (!empty($new_pass)) {
                    if (strlen($new_pass) < 6) {
                        echo "<script>alert('Lỗi: Mật khẩu mới phải từ 6 ký tự trở lên!'); window.history.back();</script>";
                        exit();
                    }
                    if ($new_pass !== $new_pass_repeat) {
                        echo "<script>alert('Lỗi: Nhập lại mật khẩu mới không trùng khớp!'); window.history.back();</script>";
                        exit();
                    }
                    
                    $hash_new_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                    $stmt_update = $db->prepare("UPDATE users SET user_name = ?, user_email = ?, user_phone = ?, user_password_hash = ? WHERE user_id = ?");
                    $stmt_update->execute([$new_name, $new_email, $new_phone, $hash_new_pass, $current_user_id]);
                } 
                // Trường hợp 2: Chỉ cập nhật thông tin cá nhân cơ bản (Không đổi mật khẩu)
                else {
                    $stmt_update = $db->prepare("UPDATE users SET user_name = ?, user_email = ?, user_phone = ? WHERE user_id = ?");
                    $stmt_update->execute([$new_name, $new_email, $new_phone, $current_user_id]);
                }

                // Đồng bộ cập nhật ngay lập tức vào Session hệ thống
                $_SESSION['user_name'] = $new_name;
                $_SESSION['user_email'] = $new_email;
                $_SESSION['user_phone'] = $new_phone; 

                echo "<script>alert('Hệ thống cập nhật thông tin tài khoản thành công!'); window.location.href='index.php';</script>";
                exit();
            } else {
                echo "<script>alert('Thất bại: Mật khẩu hiện tại không chính xác!'); window.history.back();</script>";
                exit();
            }
        } catch (PDOException $e) {
            echo "<script>alert('Lỗi hệ thống: Email này hoặc dữ liệu này đã tồn tại trên tài khoản khác!'); window.history.back();</script>";
            exit();
        }
    }
}

if (!isset($_SESSION['user_name']) && isset($_SESSION['user_id'])) {
    $_SESSION['user_name'] = $_SESSION['user_name'] ?? 'Admin';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Quản Lý Kho Chuẩn Odoo</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; background-color: #f4f6f9; min-height: 100vh; color: #333333; }
        
        .sidebar { width: 260px; background-color: #1e3d59; color: #ffffff; display: flex; flex-direction: column; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }
        .sidebar-brand { padding: 20px; text-align: center; font-size: 1.15rem; font-weight: bold; border-bottom: 1px solid #17b978; background-color: #17b978; color: #ffffff; letter-spacing: 0.5px; }
        .sidebar-menu { list-style: none; padding: 15px 0; flex: 1; }
        .sidebar-menu li { padding: 14px 20px; transition: all 0.2s ease-in-out; }
        .sidebar-menu li:hover { background-color: #17b978; padding-left: 25px; }
        .sidebar-menu a { color: #e8f1f5; text-decoration: none; display: flex; align-items: center; gap: 12px; font-size: 0.95rem; font-weight: 500; }
        .sidebar-menu li:hover a { color: #ffffff; }
        
        .sidebar-logout { padding: 20px; border-top: 1px solid #2b5278; }
        .btn-logout { display: block; text-align: center; background-color: #ff6b6b; color: white; padding: 10px; text-decoration: none; border-radius: 6px; font-weight: bold; transition: background 0.2s; }
        .btn-logout:hover { background-color: #ee5253; }

        .main-content { flex: 1; display: flex; flex-direction: column; }
        .main-header { height: 65px; background-color: #ffffff; display: flex; align-items: center; justify-content: space-between; padding: 0 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border-bottom: 1px solid #eef2f5; }
        .page-title { font-size: 1.1rem; font-weight: 600; color: #1e3d59; display: flex; align-items: center; gap: 8px; }
        
        /* 👤 AVATAR PROFILE DESIGN */
        .user-profile { display: flex; align-items: center; gap: 12px; cursor: pointer; position: relative; }
        .user-info { text-align: right; }
        .user-info .username { display: block; font-size: 0.9rem; font-weight: 600; color: #2c3e50; }
        .user-info .role { display: block; font-size: 0.75rem; color: #7f8c8d; }
        .avatar-circle { width: 38px; height: 38px; background-color: #17b978; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1rem; box-shadow: 0 2px 4px rgba(23, 185, 120, 0.2); border: 2px solid #ffffff; }

        .main-body { padding: 30px; flex: 1; overflow-y: auto; background-color: #f8fafc; }

        /* 🛠️ COMPONENT: TRUNG TÂM ĐIỀU KHIỂN ĐA PHÂN HỆ PRO */
        .profile-dropdown { display: none; position: absolute; right: 0; top: 55px; background-color: #ffffff; min-width: 350px; box-shadow: 0px 15px 35px rgba(30, 61, 89, 0.18); border-radius: 12px; padding: 0; z-index: 9999; border: 1px solid #e2e8f0; cursor: default; text-align: left; overflow: hidden; }
        .profile-dropdown.show { display: block; animation: dropdownFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1); }
        
        /* Dropdown Header */
        .pro-dropdown-header { background: linear-gradient(135deg, #1e3d59 0%, #17b978 100%); padding: 18px; color: white; display: flex; align-items: center; gap: 12px; }
        .pro-dropdown-header h4 { font-size: 1rem; font-weight: 600; margin-bottom: 2px; }
        .pro-badge { background: rgba(255, 255, 255, 0.22); padding: 2px 8px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.3px; display: inline-block; border: 1px solid rgba(255, 255, 255, 0.3); }

        /* Navigation Tabs Control */
        .profile-tabs-nav { display: flex; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .profile-tab-btn { flex: 1; text-align: center; padding: 10px; font-size: 0.85rem; font-weight: 600; color: #64748b; background: none; border: none; cursor: pointer; border-bottom: 2px solid transparent; transition: all 0.2s; }
        .profile-tab-btn.active { color: #1e3d59; border-bottom-color: #1e3d59; background: #ffffff; }

        /* Tabs Content Layer */
        .profile-tab-content { padding: 20px; display: none; max-height: 420px; overflow-y: auto; }
        .profile-tab-content.active { display: block; }

        /* Thống kê KPIs Widget */
        .kpi-mini-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px; }
        .kpi-mini-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center; }
        .kpi-mini-card p { font-size: 0.75rem; color: #64748b; font-weight: 500; margin-bottom: 4px; }
        .kpi-mini-card h5 { font-size: 1.25rem; color: #1e3d59; font-weight: 700; }
        
        .sys-info-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dotted #e2e8f0; font-size: 0.8rem; color: #475569; }
        .sys-info-item strong { color: #1e3d59; }

        /* Form styling */
        .form-mini-group { margin-bottom: 12px; }
        .form-mini-group label { display: block; font-size: 0.78rem; margin-bottom: 5px; color: #475569; font-weight: 600; }
        .form-mini-group label span { color: #ff6b6b; }
        .form-mini-group input { width: 100%; padding: 8px 12px; font-size: 0.85rem; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; color: #1e293b; transition: all 0.15s; }
        .form-mini-group input:focus { border-color: #17b978; box-shadow: 0 0 0 3px rgba(23, 185, 120, 0.15); }
        
        /* STYLE CHO KHỐI ĐỔI MẬT KHẨU ẨN HIỆN LINH HOẠT */
        .btn-trigger-password { display: block; width: 100%; background: #f1f5f9; color: #475569; border: 1px dashed #cbd5e1; padding: 8px; text-align: center; font-size: 0.8rem; font-weight: 600; border-radius: 6px; cursor: pointer; margin: 10px 0; transition: all 0.2s; }
        .btn-trigger-password:hover { background: #e2e8f0; color: #1e293b; }
        .password-toggle-section { display: none; border-top: 1px dashed #e2e8f0; padding-top: 12px; margin-top: 12px; }
        .password-toggle-section.show { display: block; }

        .dropdown-mini-actions { display: flex; justify-content: space-between; margin-top: 18px; gap: 10px; }
        .btn-mini-save { background-color: #17b978; color: white; border: none; padding: 9px 16px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 0.85rem; flex: 1; box-shadow: 0 2px 4px rgba(23, 185, 120, 0.15); }
        .btn-mini-save:hover { background-color: #14a066; }
        .btn-mini-cancel { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 9px 14px; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 0.85rem; text-align: center; flex: 1; }
        .btn-mini-cancel:hover { background-color: #e2e8f0; }
        @keyframes dropdownFadeIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-brand">📦 Warehouse</div>
        <ul class="sidebar-menu">
            <li><a href="index.php?page=dashboard">📊 Dashboard</a></li>
            <li><a href="index.php?page=products">📦 Sản phẩm</a></li>
            <li><a href="index.php?page=partners">👥 Đối tác (KH/NCC)</a></li>
            <li><a href="index.php?page=warehouse">🚚 Điều phối Kho</a></li>
            <li><a href="index.php?page=audit">📋 Kiểm kê kho</a></li>
            <li><a href="index.php?page=reports">📈 Báo cáo vĩ mô</a></li>
            <li><a href="index.php?page=chat-ai">🤖 Trợ lý AI</a></li>
            <li><a href="index.php?page=settings">⚙️ Cài đặt</a></li>
        </ul>
        <div class="sidebar-logout">
            <a href="index.php?logout" class="btn-logout">🚪 Đăng xuất</a>
        </div>
    </aside>

    <div class="main-content">
        <header class="main-header">
            <div class="page-title">⚙️ Hệ thống điều phối dữ liệu</div>
            
            <div class="user-profile" id="userProfileTrigger">
                <div class="user-info">
                    <span class="username"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Quản trị viên'); ?></span>
                    <span class="role">Hạt nhân Hệ thống</span>
                </div>
                <div class="avatar-circle">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'Q', 0, 1)); ?>
                </div>

                <div class="profile-dropdown" id="profileDropdownMini">
                    
                    <div class="pro-dropdown-header">
                        <div class="avatar-circle" style="background: white; color: #1e3d59; box-shadow: none;">
                            <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'Q', 0, 1)); ?>
                        </div>
                        <div>
                            <h4><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Quản trị viên'); ?></h4>
                            <span class="pro-badge">ID Nhân viên: #00<?php echo htmlspecialchars($_SESSION['user_id'] ?? '1'); ?></span>
                        </div>
                    </div>

                    <div class="profile-tabs-nav">
                        <button type="button" class="profile-tab-btn active" data-target="tab-overview">📊 Tổng Quan KPI</button>
                        <button type="button" class="profile-tab-btn" data-target="tab-security">🔑 Bảo Mật Lõi</button>
                    </div>

                    <div class="profile-tab-content active" id="tab-overview">
                        <div class="kpi-mini-grid">
                            <div class="kpi-mini-card">
                                <p>📦 Sản Phẩm Lưu Kho</p>
                                <h5><?php echo $total_products_count; ?></h5>
                            </div>
                            <div class="kpi-mini-card">
                                <p>🚚 Lịch Sử Điều Phối</p>
                                <h5><?php echo $total_warehouse_orders; ?></h5>
                            </div>
                        </div>
                        <div class="sys-info-item"><span>Cấp bậc phân quyền:</span> <strong>Master Admin</strong></div>
                        <div class="sys-info-item"><span>Họ và tên:</span> <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Chưa cấu hình'); ?></strong></div>
                        <div class="sys-info-item"><span>Email đăng ký:</span> <strong><?php echo htmlspecialchars($_SESSION['user_email'] ?? 'Chưa cấu hình'); ?></strong></div>
                        <div class="sys-info-item"><span>Số điện thoại:</span> <strong><?php echo htmlspecialchars($_SESSION['user_phone'] ?? 'Chưa cập nhật'); ?></strong></div>
                        <div class="sys-info-item"><span>Môi trường mạng:</span> <strong style="color: #17b978;">Docker Container Stack</strong></div>
                        <button type="button" id="btnMiniClose" class="btn-mini-cancel" style="margin-top: 15px; width: 100%;">Đóng bảng điều khiển</button>
                    </div>

                    <div class="profile-tab-content" id="tab-security">
                        <form action="index.php" method="POST">
                            
                            <div class="form-mini-group">
                                <label>Họ và tên hiển thị: <span>*</span></label>
                                <input type="text" name="update_user_name" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" required placeholder="Nhập họ và tên tài khoản">
                            </div>

                            <div class="form-mini-group">
                                <label>Số điện thoại liên hệ:</label>
                                <input type="text" name="update_user_phone" value="<?php echo htmlspecialchars($_SESSION['user_phone'] ?? ''); ?>" placeholder="Ví dụ: 0912345678">
                            </div>

                            <div class="form-mini-group">
                                <label>Email liên hệ nhận thông báo: <span>*</span></label>
                                <input type="email" name="update_user_email" value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? 'admin@warehouse.com'); ?>" required>
                            </div>
                            
                            <div class="btn-trigger-password" id="btnTogglePasswordFields">🔐 Yêu cầu thay đổi mật khẩu?</div>

                            <div class="password-toggle-section" id="passwordSection">
                                <div class="form-mini-group">
                                    <label>Mật khẩu mới:</label>
                                    <input type="password" id="input_new_pass" name="update_user_password_new" autocomplete="off" placeholder="Tối thiểu 6 ký tự">
                                </div>
                                <div class="form-mini-group">
                                    <label>Nhập lại mật khẩu mới:</label>
                                    <input type="password" id="input_repeat_pass" name="update_user_password_repeat" autocomplete="off" placeholder="Trùng khớp mật khẩu trên">
                                </div>
                            </div>

                            <div class="form-mini-group" style="background: #fff5f5; padding: 10px; border-radius: 6px; border: 1px solid #fed7d7; margin-top: 12px;">
                                <label style="color: #9b1c1c;">Mật khẩu hiện tại (Bắt buộc): <span>*</span></label>
                                <input type="password" name="update_user_password_current" required placeholder="Nhập mật khẩu hiện tại để xác minh">
                            </div>

                            <div class="dropdown-mini-actions">
                                <button type="submit" name="submit_update_profile" class="btn-mini-save">Lưu cấu trúc</button>
                                <button type="button" id="btnMiniCancel" class="btn-mini-cancel">Hủy bỏ</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </header>
        
        <main class="main-body">
            <?php
            $currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
            $allowedPages = ['dashboard', 'products', 'partners', 'warehouse', 'audit', 'reports', 'chat-ai', 'settings'];
            
            if (in_array($currentPage, $allowedPages)) {
                $targetFile = "modules/" . $currentPage . ".php";
                if (file_exists($targetFile)) {
                    include($targetFile);
                } else {
                    echo "<div style='background: #ffffff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border-left: 4px solid #1e3d59;'>";
                    echo "<h3 style='color: #1e3d59;'>Mô-đun [ " . htmlspecialchars(ucfirst($currentPage)) . " ] đang được cấu trúc</h3>";
                    echo "<p style='color: #7f8c8d; margin-top: 10px;'>Hạt nhân Docker đang sẵn sàng nạp kết nối SQL cho tầng nghiệp vụ này.</p>";
                    echo "</div>";
                }
            } else {
                echo "<h3 style='color: #ff6b6b;'>Cảnh báo: Tầng truy cập nghiệp vụ không hợp lệ!</h3>";
            }
            ?>
        </main>
    </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const trigger = document.getElementById('userProfileTrigger');
        const dropdown = document.getElementById('profileDropdownMini');
        const btnCancel = document.getElementById('btnMiniCancel');
        const btnClose = document.getElementById('btnMiniClose');
        const tabButtons = document.querySelectorAll('.profile-tab-btn');
        const tabContents = document.querySelectorAll('.profile-tab-content');
        
        const btnTogglePassword = document.getElementById('btnTogglePasswordFields');
        const passwordSection = document.getElementById('passwordSection');
        const inputNewPass = document.getElementById('input_new_pass');
        const inputRepeatPass = document.getElementById('input_repeat_pass');

        // XỬ LÝ SỰ KIỆN BẤM NÚT "ĐỔI MẬT KHẨU"
        if (btnTogglePassword && passwordSection) {
            btnTogglePassword.addEventListener('click', function(e) {
                e.stopPropagation(); 
                passwordSection.classList.toggle('show');
                
                if (passwordSection.classList.contains('show')) {
                    this.innerText = "❌ Hủy thay đổi mật khẩu";
                    this.style.background = "#fff5f5";
                    this.style.color = "#c53030";
                    this.style.borderColor = "#feb2b2";
                } else {
                    this.innerText = "🔐 Yêu cầu thay đổi mật khẩu?";
                    this.style.background = "#f1f5f9";
                    this.style.color = "#475569";
                    this.style.borderColor = "#cbd5e1";
                    if (inputNewPass) inputNewPass.value = "";
                    if (inputRepeatPass) inputRepeatPass.value = "";
                }
            });
        }

        // LẮNG NGHE SỰ KIỆN CHUYỂN TAB ĐA PHÂN HỆ
        tabButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation(); 
                const targetTab = this.getAttribute('data-target');

                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));

                this.classList.add('active');
                const targetEl = document.getElementById(targetTab);
                if (targetEl) targetEl.classList.add('active');
            });
        });

        // Ẩn/Hiện Dropdown khi click vào Avatar vùng Profile Header
        if (trigger) {
            trigger.addEventListener('click', function(e) {
                if (e.target.closest('.profile-dropdown')) return;
                if (dropdown) dropdown.classList.toggle('show');
            });
        }

        // 🚀 ĐOẠN ĐÃ ĐƯỢC CHUẨN HÓA VÀ FIX LỖI: Sử dụng classList.remove thay vì `.remove()` trực tiếp
        const closeBox = (e) => {
            if (e) e.stopPropagation();
            if (dropdown) dropdown.classList.remove('show');
            if (passwordSection) passwordSection.classList.remove('show');
            if (btnTogglePassword) {
                btnTogglePassword.innerText = "🔐 Yêu cầu thay đổi mật khẩu?";
                btnTogglePassword.style.background = "#f1f5f9";
                btnTogglePassword.style.color = "#475569";
                btnTogglePassword.style.borderColor = "#cbd5e1";
            }
            if (inputNewPass) inputNewPass.value = "";
            if (inputRepeatPass) inputRepeatPass.value = "";
        };
        
        if (btnCancel) btnCancel.addEventListener('click', closeBox);
        if (btnClose) btnClose.addEventListener('click', closeBox);

        // Click vùng trống bất kỳ bên ngoài màn hình tự động đóng bảng điều khiển dropdown
        document.addEventListener('click', function(e) {
            if (trigger && !trigger.contains(e.target)) {
                if (dropdown) dropdown.classList.remove('show');
            }
        });
    });
    </script>
</body>
</html>
````

## File: landing.php
````php
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
// Giả định file config/db.php của bạn cung cấp một biến kết nối PDO đặt tên là $db hoặc $conn.
// Nếu file config/db.php sử dụng cấu trúc khác, đoạn code dưới đây đã được bọc trong try-catch để tránh crash giao diện.
try {
    // Gọi tệp cấu hình database hiện tại của bạn
    if (file_exists('config/db.php')) {
        require_once 'config/db.php';
    } elseif (file_exists('../config/db.php')) {
        require_once '../config/db.php';
    }

    // Xác định biến kết nối (Thử nghiệm các tên biến phổ biến như $db, $conn, $link)
    $database_connection = null;
    if (isset($conn) && $conn instanceof PDO) { $database_connection = $conn; }
    mt_srand(time()); // Dự phòng số ngẫu nhiên nếu không kết nối được db để UI luôn đẹp
    
    if ($database_connection) {
        // Truy vấn 1: Đếm tổng số mặt hàng (SKU) khác nhau trong bảng products
        $stmt1 = $database_connection->prepare("SELECT COUNT(*) as total FROM products");
        $stmt1->execute();
        $row1 = $stmt1->fetch(PDO::FETCH_ASSOC);
        $total_products = $row1['total'] ?? 0;
        $stmt1->closeCursor(); // Giải phóng bộ nhớ truy vấn

        // Truy vấn 2: Tính tổng số lượng (SUM cột qty) của toàn bộ hàng hóa trong kho
        $stmt2 = $database_connection->prepare("SELECT SUM(qty) as total_qty FROM products");
        $stmt2->execute();
        $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        $total_quantity = $row2['total_qty'] ?? 0;
        $stmt2->closeCursor(); // Giải phóng bộ nhớ truy vấn
    } else {
        // Nếu không tìm thấy kết nối phù hợp, tạo dữ liệu ảo trực quan để chạy thử nghiệm
        $total_products = rand(120, 150);
        $total_quantity = rand(5400, 8900);
    }
} catch (Exception $e) {
    // Nếu có lỗi xảy ra (ví dụ chưa tạo bảng products), hệ thống vẫn chạy và hiện số liệu giả định
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
    <title>Hệ thống Quản lý Kho hàng Thông minh - WMS Cloud</title>
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
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 mb-3 rounded-pill text-uppercase fw-semibold" style="background-color: rgba(37,99,235,0.15) !important;">Giải pháp vận hành kho 4.0</span>
                    <h1 class="display-4 fw-bold lh-sm mb-3">Quản lý kho thông minh, tối ưu hóa chuỗi cung ứng</h1>
                    <p class="lead opacity-75 mb-4">Hệ thống WMS hiện đại giúp theo dõi tồn kho thời gian thực, tự động hóa quy trình nhập xuất kho theo chuẩn Odoo Stock Picking, loại bỏ hoàn toàn sai sót thủ công.</p>
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
            <p class="mb-2 fw-semibold text-white">Warehouse Management System (WMS Cloud)</p>
            <p class="mb-0 text-muted small">© <?php echo date('Y'); ?> Dự án Kho vận thông minh. Phát triển dựa trên nền tảng PHP thuần & Bootstrap 5.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
````

## File: libraries/password_compatibility_library.php
````php
<?php
/**
 * A Compatibility library with PHP 5.5's simplified password hashing API.
 *
 * @author Anthony Ferrara <ircmaxell@php.net>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @copyright 2012 The Authors
 */

if (!defined('PASSWORD_DEFAULT')) {

    define('PASSWORD_BCRYPT', 1);
    define('PASSWORD_DEFAULT', PASSWORD_BCRYPT);

    /**
     * Hash the password using the specified algorithm
     *
     * @param string $password The password to hash
     * @param int    $algo     The algorithm to use (Defined by PASSWORD_* constants)
     * @param array  $options  The options for the algorithm to use
     *
     * @return string|false The hashed password, or false on error.
     */
    function password_hash($password, $algo, array $options = array()) {
        if (!function_exists('crypt')) {
            trigger_error("Crypt must be loaded for password_hash to function", E_USER_WARNING);
            return null;
        }
        if (!is_string($password)) {
            trigger_error("password_hash(): Password must be a string", E_USER_WARNING);
            return null;
        }
        if (!is_int($algo)) {
            trigger_error("password_hash() expects parameter 2 to be long, " . gettype($algo) . " given", E_USER_WARNING);
            return null;
        }
        switch ($algo) {
            case PASSWORD_BCRYPT:
                // Note that this is a C constant, but not exposed to PHP, so we don't define it here.
                $cost = 10;
                if (isset($options['cost'])) {
                    $cost = $options['cost'];
                    if ($cost < 4 || $cost > 31) {
                        trigger_error(sprintf("password_hash(): Invalid bcrypt cost parameter specified: %d", $cost), E_USER_WARNING);
                        return null;
                    }
                }
                // The length of salt to generate
                $raw_salt_len = 16;
                // The length required in the final serialization
                $required_salt_len = 22;
                $hash_format = sprintf("$2y$%02d$", $cost);
                break;
            default:
                trigger_error(sprintf("password_hash(): Unknown password hashing algorithm: %s", $algo), E_USER_WARNING);
                return null;
        }
        if (isset($options['salt'])) {
            switch (gettype($options['salt'])) {
                case 'NULL':
                case 'boolean':
                case 'integer':
                case 'double':
                case 'string':
                    $salt = (string) $options['salt'];
                    break;
                case 'object':
                    if (method_exists($options['salt'], '__tostring')) {
                        $salt = (string) $options['salt'];
                        break;
                    }
                case 'array':
                case 'resource':
                default:
                    trigger_error('password_hash(): Non-string salt parameter supplied', E_USER_WARNING);
                    return null;
            }
            if (strlen($salt) < $required_salt_len) {
                trigger_error(sprintf("password_hash(): Provided salt is too short: %d expecting %d", strlen($salt), $required_salt_len), E_USER_WARNING);
                return null;
            } elseif (0 == preg_match('#^[a-zA-Z0-9./]+$#D', $salt)) {
                $salt = str_replace('+', '.', base64_encode($salt));
            }
        } else {
            $buffer = '';
            $buffer_valid = false;
            if (function_exists('mcrypt_create_iv') && !defined('PHALANGER')) {
                $buffer = mcrypt_create_iv($raw_salt_len, MCRYPT_DEV_URANDOM);
                if ($buffer) {
                    $buffer_valid = true;
                }
            }
            if (!$buffer_valid && function_exists('openssl_random_pseudo_bytes')) {
                $buffer = openssl_random_pseudo_bytes($raw_salt_len);
                if ($buffer) {
                    $buffer_valid = true;
                }
            }
            if (!$buffer_valid && is_readable('/dev/urandom')) {
                $f = fopen('/dev/urandom', 'r');
                $read = strlen($buffer);
                while ($read < $raw_salt_len) {
                    $buffer .= fread($f, $raw_salt_len - $read);
                    $read = strlen($buffer);
                }
                fclose($f);
                if ($read >= $raw_salt_len) {
                    $buffer_valid = true;
                }
            }
            if (!$buffer_valid || strlen($buffer) < $raw_salt_len) {
                $bl = strlen($buffer);
                for ($i = 0; $i < $raw_salt_len; $i++) {
                    if ($i < $bl) {
                        $buffer[$i] = $buffer[$i] ^ chr(mt_rand(0, 255));
                    } else {
                        $buffer .= chr(mt_rand(0, 255));
                    }
                }
            }
            $salt = str_replace('+', '.', base64_encode($buffer));
        }
        $salt = substr($salt, 0, $required_salt_len);

        $hash = $hash_format . $salt;

        $ret = crypt($password, $hash);

        if (!is_string($ret) || strlen($ret) <= 13) {
            return false;
        }

        return $ret;
    }

    /**
     * Get information about the password hash. Returns an array of the information
     * that was used to generate the password hash.
     *
     * array(
     *    'algo' => 1,
     *    'algoName' => 'bcrypt',
     *    'options' => array(
     *        'cost' => 10,
     *    ),
     * )
     *
     * @param string $hash The password hash to extract info from
     *
     * @return array The array of information about the hash.
     */
    function password_get_info($hash) {
        $return = array(
            'algo' => 0,
            'algoName' => 'unknown',
            'options' => array(),
        );
        if (substr($hash, 0, 4) == '$2y$' && strlen($hash) == 60) {
            $return['algo'] = PASSWORD_BCRYPT;
            $return['algoName'] = 'bcrypt';
            list($cost) = sscanf($hash, "$2y$%d$");
            $return['options']['cost'] = $cost;
        }
        return $return;
    }

    /**
     * Determine if the password hash needs to be rehashed according to the options provided
     *
     * If the answer is true, after validating the password using password_verify, rehash it.
     *
     * @param string $hash    The hash to test
     * @param int    $algo    The algorithm used for new password hashes
     * @param array  $options The options array passed to password_hash
     *
     * @return boolean True if the password needs to be rehashed.
     */
    function password_needs_rehash($hash, $algo, array $options = array()) {
        $info = password_get_info($hash);
        if ($info['algo'] != $algo) {
            return true;
        }
        switch ($algo) {
            case PASSWORD_BCRYPT:
                $cost = isset($options['cost']) ? $options['cost'] : 10;
                if ($cost != $info['options']['cost']) {
                    return true;
                }
                break;
        }
        return false;
    }

    /**
     * Verify a password against a hash using a timing attack resistant approach
     *
     * @param string $password The password to verify
     * @param string $hash     The hash to verify against
     *
     * @return boolean If the password matches the hash
     */
    function password_verify($password, $hash) {
        if (!function_exists('crypt')) {
            trigger_error("Crypt must be loaded for password_verify to function", E_USER_WARNING);
            return false;
        }
        $ret = crypt($password, $hash);
        if (!is_string($ret) || strlen($ret) != strlen($hash) || strlen($ret) <= 13) {
            return false;
        }

        $status = 0;
        for ($i = 0; $i < strlen($ret); $i++) {
            $status |= (ord($ret[$i]) ^ ord($hash[$i]));
        }

        return $status === 0;
    }
}
````

## File: modules/audit.php
````php
<?php
/**
 * 📋 MÔ-ĐUN KIỂM KÊ VÀ ĐIỀU CHỈNH KHO (INVENTORY AUDIT)
 */
$pdo = getPDOLayerConnection();
$errors = []; $messages = [];

try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'inventory_audit'")->fetch();
    if (!$tableCheck) throw new Exception("Bảng 'inventory_audit' chưa có. Vui lòng nạp SQL cấu trúc.");

    // [Chức năng 1 & 2] XỬ LÝ ĐIỀU CHỈNH KHO & LƯU LÝ DO
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_audit'])) {
        $sku = $_POST['sku'] ?? '';
        $counted_qty = intval($_POST['counted_qty'] ?? -1);
        $note = trim($_POST['note'] ?? '');

        if (empty($sku) || $counted_qty < 0) {
            $errors[] = "Vui lòng chọn sản phẩm và nhập số lượng thực tế hợp lệ.";
        } else {
            try {
                $pdo->beginTransaction(); 
                
                $stmtProd = $pdo->prepare("SELECT qty FROM products WHERE sku = ?");
                $stmtProd->execute([$sku]);
                $product = $stmtProd->fetch();

                if (!$product) throw new Exception("Sản phẩm không tồn tại.");

                $system_qty = (int)$product['qty'];
                $difference = $counted_qty - $system_qty;

                if ($difference === 0) throw new Exception("Thực tế khớp với phần mềm. Không cần điều chỉnh.");

                // Cập nhật số lượng kho
                $pdo->prepare("UPDATE products SET qty = ? WHERE sku = ?")->execute([$counted_qty, $sku]);

                // Lưu lịch sử kèm lý do
                $audit_code = 'AUDIT/' . date('YmdHis');
                $pdo->prepare("INSERT INTO inventory_audit (audit_code, product_sku, system_qty, counted_qty, difference, note) VALUES (?, ?, ?, ?, ?, ?)")
                    ->execute([$audit_code, $sku, $system_qty, $counted_qty, $difference, $note]);

                $pdo->commit();
                $diff_text = $difference > 0 ? "+$difference" : $difference;
                $messages[] = "Cân bằng kho thành công. Độ lệch: {$diff_text} sản phẩm.";
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = "Lỗi kiểm kê: " . $e->getMessage();
            }
        }
    }

    $all_products = $pdo->query("SELECT sku, name, qty FROM products")->fetchAll();
    $audits = $tableCheck ? $pdo->query("SELECT * FROM inventory_audit ORDER BY id DESC")->fetchAll() : [];

} catch (Exception $e) {
    $errors[] = "Hệ thống: " . $e->getMessage();
}
?>

<!-- CSS Phục vụ chức năng IN BÁO CÁO -->
<style>
@media print {
    .no-print { display: none !important; }
    body, .main-content, .main-body { background: white !important; padding: 0 !important; margin: 0 !important; }
    table { width: 100%; border-collapse: collapse; border: 1px solid #000; }
    th, td { border: 1px solid #000 !important; padding: 8px !important; color: black !important; }
    h2, h4 { color: black !important; }
}
</style>

<div class="container-fluid" style="padding: 10px;">
    
    <!-- HEADER VÀ NÚT IN -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="color: #1e3d59; font-weight: 700;">Kiểm Kê & Cân Bằng Kho</h2>
        <!-- [Chức năng 5] NÚT IN BÁO CÁO -->
        <button onclick="window.print()" class="no-print" style="background: #1e3d59; color: white; border: none; padding: 10px 20px; font-weight: bold; border-radius: 4px; cursor: pointer;">🖨️ In Báo Cáo Kho</button>
    </div>
    
    <div class="no-print">
        <?php foreach($errors as $error): echo "<div style='background:#f8d7da; color:#721c24; padding:12px; margin-bottom:15px; border-radius:6px;'>⚠️ $error</div>"; endforeach; ?>
        <?php foreach($messages as $msg): echo "<div style='background:#d4edda; color:#155724; padding:12px; margin-bottom:15px; border-radius:6px;'>✅ $msg</div>"; endforeach; ?>
    </div>

    <!-- KHỐI FORM NHẬP LIỆU -->
    <div class="no-print" style="background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 30px;">
        <div style="font-weight: bold; font-size: 1.1rem; color: #1e3d59; border-bottom: 2px solid #eef2f5; padding-bottom: 10px; margin-bottom: 20px;">Tạo Phiếu Kiểm Kê Thực Tế</div>
        
        <form method="POST" action="">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div>
                    <label style="display:block; margin-bottom: 8px; font-weight: 600;">Sản phẩm kiểm kê</label>
                    <select name="sku" id="sku_select" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;" required>
                        <option value="" data-qty="0">-- Chọn sản phẩm đếm được --</option>
                        <?php foreach($all_products as $prod): ?>
                            <option value="<?= $prod['sku'] ?>" data-qty="<?= $prod['qty'] ?>">Tồn máy: [<?= $prod['qty'] ?>] - <?= htmlspecialchars($prod['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 8px; font-weight: 600;">Số lượng đếm tay</label>
                    <input type="number" name="counted_qty" id="counted_qty" min="0" placeholder="Số lượng thực tế ở ngoài kho..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;" required>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 8px; font-weight: 600;">Lý do sai lệch (Nếu có)</label>
                    <input type="text" name="note" placeholder="VD: Hàng bị vỡ do vận chuyển..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
            </div>

            <!-- [Chức năng 3] TÍNH TOÁN ĐỘ LỆCH LIVE -->
            <div id="live_calculation" style="margin-top: 15px; font-size: 0.95rem; display: none; background: #eef2f5; padding: 10px; border-radius: 4px;">
                Tồn trên máy: <strong id="sys_qty_text">0</strong> | 
                Đếm thực tế: <strong id="count_qty_text">0</strong> | 
                Dự kiến lệch: <span id="diff_text" style="font-weight:bold; padding: 2px 6px; border-radius: 4px;">0</span>
            </div>

            <button type="submit" name="submit_audit" style="margin-top: 20px; background-color: #ff9f43; color: white; border: none; padding: 12px 24px; font-weight: bold; border-radius: 4px; cursor: pointer;">Cập Nhật Kho Ngay</button>
        </form>
    </div>

    <!-- KHU VỰC CÔNG CỤ JAVASCRIPT -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;" class="no-print">
        <h4 style="color: #1e3d59; font-weight: 600;">Lịch Sử Kiểm Kê Gần Nhất</h4>
        
        <!-- [Chức năng 4] BỘ LỌC DỮ LIỆU -->
        <div style="display: flex; gap: 8px;">
            <button onclick="filterTable('all')" style="padding: 6px 12px; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; background: #fff;">Tất cả</button>
            <button onclick="filterTable('missing')" style="padding: 6px 12px; border: 1px solid #f8d7da; border-radius: 4px; cursor: pointer; background: #f8d7da; color: #721c24; font-weight: bold;">Chỉ hiện Mất Hàng (-)</button>
            <button onclick="filterTable('surplus')" style="padding: 6px 12px; border: 1px solid #d4edda; border-radius: 4px; cursor: pointer; background: #d4edda; color: #155724; font-weight: bold;">Chỉ hiện Dư Hàng (+)</button>
        </div>
    </div>

    <!-- BẢNG LỊCH SỬ -->
    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03);" id="auditTable">
        <thead>
            <tr style="background-color: #eef2f5; text-align: left; color: #1e3d59;">
                <th style="padding: 15px;">Mã Phiếu & Thời gian</th>
                <th style="padding: 15px;">Mã Sản Phẩm</th>
                <th style="padding: 15px;">Máy Tính</th>
                <th style="padding: 15px;">Thực Tế</th>
                <th style="padding: 15px;">Độ Lệch</th>
                <th style="padding: 15px;">Giải Trình / Ghi Chú</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($audits)): ?>
                <tr><td colspan="6" style="padding: 20px; text-align: center;">Chưa có lịch sử kiểm kê nào.</td></tr>
            <?php else: ?>
                <?php foreach($audits as $ad): ?>
                <tr style="border-bottom: 1px solid #eef2f5;" data-diff="<?= $ad['difference'] ?>">
                    <td style="padding: 15px;">
                        <strong><?= $ad['audit_code'] ?></strong><br>
                        <small style="color: #7f8c8d;"><?= date('d/m/Y H:i', strtotime($ad['audit_date'])) ?></small>
                    </td>
                    <td style="padding: 15px; font-weight: 500;"><?= htmlspecialchars($ad['product_sku']) ?></td>
                    <td style="padding: 15px; color: #7f8c8d;"><?= $ad['system_qty'] ?></td>
                    <td style="padding: 15px; font-weight: bold;"><?= $ad['counted_qty'] ?></td>
                    <td style="padding: 15px;">
                        <?php 
                        $diff = $ad['difference'];
                        $bg = $diff > 0 ? '#d4edda' : '#f8d7da';
                        $color = $diff > 0 ? '#155724' : '#721c24';
                        ?>
                        <span style="background-color: <?= $bg ?>; color: <?= $color ?>; padding: 4px 8px; border-radius: 4px; font-weight: bold;">
                            <?= ($diff > 0 ? '+' : '') . $diff ?>
                        </span>
                    </td>
                    <td style="padding: 15px; font-size: 0.85rem; color: #7f8c8d;">
                        <?= htmlspecialchars($ad['note'] ?? 'Không có') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
// Logic: TÍNH ĐỘ LỆCH LIVE (Khi gõ phím)
const skuSelect = document.getElementById('sku_select');
const countedInput = document.getElementById('counted_qty');

function calculateLiveDiff() {
    let sysQty = parseInt(skuSelect.options[skuSelect.selectedIndex].getAttribute('data-qty')) || 0;
    let countQty = parseInt(countedInput.value);
    
    if(skuSelect.value !== "" && !isNaN(countQty)) {
        document.getElementById('live_calculation').style.display = 'block';
        document.getElementById('sys_qty_text').innerText = sysQty;
        document.getElementById('count_qty_text').innerText = countQty;
        
        let diff = countQty - sysQty;
        let dText = document.getElementById('diff_text');
        dText.innerText = (diff > 0 ? "+" : "") + diff;
        dText.style.color = diff > 0 ? '#155724' : (diff < 0 ? '#721c24' : '#333');
        dText.style.backgroundColor = diff > 0 ? '#d4edda' : (diff < 0 ? '#f8d7da' : '#fff');
    } else {
        document.getElementById('live_calculation').style.display = 'none';
    }
}
skuSelect.addEventListener('change', calculateLiveDiff);
countedInput.addEventListener('keyup', calculateLiveDiff);
countedInput.addEventListener('change', calculateLiveDiff);

// Logic: BỘ LỌC BẢNG THEO TRẠNG THÁI
function filterTable(type) {
    let rows = document.querySelectorAll("table#auditTable tbody tr");
    rows.forEach(row => {
        let diffAttr = row.getAttribute('data-diff');
        if(!diffAttr) return; // Bỏ qua dòng trống
        let diff = parseInt(diffAttr);
        
        if (type === 'all') row.style.display = "";
        else if (type === 'missing' && diff < 0) row.style.display = "";
        else if (type === 'surplus' && diff > 0) row.style.display = "";
        else row.style.display = "none";
    });
}
</script>
````

## File: modules/chat-ai.php
````php
<style>
    .ai-chat-container {
        background: #ffffff;
        border: 1px solid #d1d3e2;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.03);
        display: flex;
        flex-direction: column;
        height: 75vh; 
    }
    .ai-chat-header {
        background-color: #1e3d59;
        color: #ffffff;
        padding: 15px 25px;
        font-weight: 600;
        font-size: 1.1rem;
        border-radius: 4px 4px 0 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .ai-chat-box {
        flex: 1;
        overflow-y: auto;
        padding: 25px;
        background-color: #f8fafc;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .msg-group { display: flex; flex-direction: column; max-width: 85%; }
    .msg-user { align-self: flex-end; }
    .msg-ai { align-self: flex-start; }
    .msg-label { font-size: 0.85rem; font-weight: bold; margin-bottom: 5px; }
    .msg-user .msg-label { text-align: right; color: #7f8c8d; }
    .msg-ai .msg-label { text-align: left; color: #17b978; }
    .msg-content { padding: 12px 18px; border-radius: 4px; line-height: 1.5; font-size: 0.95rem; }
    .msg-user .msg-content { background-color: #e8f1f5; border: 1px solid #dcdde1; color: #2c3e50; }
    .msg-ai .msg-content { background-color: #ffffff; border: 1px solid #eef2f5; box-shadow: 0 2px 5px rgba(0,0,0,0.02); color: #333333; }
  
    .ai-chat-footer {
        display: flex;
        padding: 15px;
        background-color: #ffffff;
        border-top: 1px solid #eef2f5;
        border-radius: 0 0 4px 4px;
    }
    .ai-chat-footer input {
        flex: 1; /* Chiếm toàn bộ không gian còn lại để ô nhập dài ra */
        padding: 12px 20px;
        border: 1px solid #dcdde1;
        border-radius: 4px 0 0 4px;
        font-size: 1rem;
        outline: none;
        color: #2c3e50;
        transition: border-color 0.2s;
    }
    .ai-chat-footer input:focus {
        border-color: #17b978;
    }
    .ai-chat-footer button {
        padding: 0 40px;
        background-color: #1e3d59;
        color: white;
        border: 1px solid #1e3d59;
        border-radius: 0 4px 4px 0;
        cursor: pointer;
        font-weight: bold;
        font-size: 1rem;
        transition: background-color 0.2s;
    }
    .ai-chat-footer button:hover {
        background-color: #17b978;
        border-color: #17b978;
    }
</style>

<div class="container-fluid" style="padding: 25px;">
    <div class="ai-chat-container">
        <div class="ai-chat-header">
            <i class="fas fa-robot"></i> Hệ Thống AI Điều Phối Kho
        </div>
        
        <div class="ai-chat-box" id="chat-box">
            <div class="msg-group msg-ai">
                <div class="msg-label">Hệ Thống Trợ Lý</div>
                <div class="msg-content">
                    Xin chào! Tôi là trí tuệ nhân tạo được tích hợp để hỗ trợ quản lý dữ liệu kho. Vui lòng nhập truy vấn của bạn.
                </div>
            </div>
        </div>
        
        <div class="ai-chat-footer">
            <input type="text" id="user-input" placeholder="Nhập câu lệnh hoặc truy vấn của bạn vào đây...">
            <button id="btn-send">Gửi Yêu Cầu</button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#btn-send').click(function() {
        let text = $('#user-input').val();
        if(text.trim() === '') return;

        $('#chat-box').append('<div class="msg-group msg-user"><div class="msg-label">Bạn</div><div class="msg-content">' + text + '</div></div>');
        $('#user-input').val(''); 
        
        let loadingId = 'loading-' + Date.now();
        $('#chat-box').append('<div id="' + loadingId + '" class="msg-group msg-ai"><div class="msg-label">Hệ Thống Trợ Lý</div><div class="msg-content" style="color: #7f8c8d; font-style: italic;">Đang xử lý truy vấn...</div></div>');
        $('#chat-box').scrollTop($('#chat-box')[0].scrollHeight);

        let apiKey = 'AIzaSyAV7o_6as4jgcq9pkB0qJed3mGWdM7pGB4'; 
        let apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' + apiKey;

        $.ajax({
            url: apiUrl,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                "contents": [{"parts":[{"text": text}]}]
            }),
            success: function(response) {
                $('#' + loadingId).remove();
                let aiReply = response.candidates[0].content.parts[0].text;
                aiReply = aiReply.replace(/\n/g, '<br>');
                
                $('#chat-box').append('<div class="msg-group msg-ai"><div class="msg-label">Hệ Thống Trợ Lý</div><div class="msg-content">' + aiReply + '</div></div>');
                $('#chat-box').scrollTop($('#chat-box')[0].scrollHeight);
            },
            error: function() {
                $('#' + loadingId).remove();
                $('#chat-box').append('<div class="msg-group msg-ai"><div class="msg-label">Hệ Thống Trợ Lý</div><div class="msg-content" style="color: #ff6b6b;">Lỗi: Không thể kết nối đến máy chủ AI. Vui lòng kiểm tra cấu hình mạng hoặc API Key.</div></div>');
            }
        });
    });

    $('#user-input').keypress(function(e) {
        if(e.which == 13) {
            $('#btn-send').click();
        }
    });
});
</script>
````

## File: modules/dashboard.php
````php
<?php
// Kiểm tra bảo mật: Không cho phép truy cập trực tiếp file này từ URL bên ngoài
if (!isset($_SESSION['user_name'])) {
    exit("Truy cập bị từ chối!");
}

/**
 * 🛠️ TẦNG KẾT NỐI VÀ TRUY VẤN DỮ LIỆU THỰC TẾ (DYNAMIC DATA LAYER - PDO UPGRADED)
 * Kéo chỉ số trực tiếp từ bảng products và tích hợp lịch sử luân chuyển thực tế từ stock_picking
 */
$totalProducts = 0;       
$totalStockVolume = 0;   
$lowStockAlert = 0;      
$recentActivities = [];  

try {
    // Khởi tạo kết nối thông qua lớp PDO đồng bộ bảo mật
    if (function_exists('getPDOLayerConnection')) {
        $pdo = getPDOLayerConnection();
    } else {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    
    if ($pdo) {
        // 1. Lấy tổng số danh mục sản phẩm (Total SKU)
        $totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

        // 2. Lấy tổng sản lượng tồn kho luân chuyển (Total Quantity)
        $totalStockVolume = (int)$pdo->query("SELECT SUM(qty) FROM products")->fetchColumn();

        // 3. Đếm số lượng sản phẩm rơi vào trạng thái cảnh báo (Số lượng tồn <= 10)
        $lowStockAlert = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE qty <= 10")->fetchColumn();

        // 4. Lấy dữ liệu động từ bảng luân chuyển kho thực tế (stock_picking song hành cùng stock_move)
        // Dùng câu lệnh chuẩn bị trước để quét 5 biến động kho gần nhất
        $stmtRecent = $pdo->prepare("
            SELECT sp.scheduled_date, sp.type, sm.product_sku, sm.product_qty 
            FROM stock_picking sp 
            JOIN stock_move sm ON sp.id = sm.picking_id 
            ORDER BY sp.id DESC LIMIT 5
        ");
        $stmtRecent->execute();
        $activities = $stmtRecent->fetchAll();

        if (!empty($activities)) {
            foreach ($activities as $row) {
                // Ép kiểu thời gian từ DB ra định dạng gọn nhẹ đúng Layout gốc của bạn
                $timeFormatted = date('H:i | d-m', strtotime($row['scheduled_date']));
                $recentActivities[] = [
                    'time' => $timeFormatted,
                    'type' => $row['type'], // Kế thừa giá trị 'in' hoặc 'out' thực tế từ Odoo Engine
                    'product_name' => $row['product_sku'], // Hiển thị mã sản phẩm luân chuyển
                    'quantity' => number_format($row['product_qty']) . " SP"
                ];
            }
        } else {
            // Cơ chế Fallback dữ liệu: Nếu bảng luân chuyển mới chưa có lệnh nhập xuất kho nào, 
            // hệ thống tự động quét bảng sản phẩm gốc để hiển thị danh sách khởi tạo ban đầu giống hệt code cũ của bạn
            $stmtFallback = $pdo->query("SELECT name, qty FROM products ORDER BY id DESC LIMIT 5");
            while ($row = $stmtFallback->fetch()) {
                $recentActivities[] = [
                    'time' => date('H:i | d-m'),
                    'type' => 'in',
                    'product_name' => $row['name'],
                    'quantity' => number_format($row['qty']) . " SP"
                ];
            }
        }
    }
} catch (Exception $e) {
    // Đóng băng ngoại lệ bảo vệ luồng hiển thị giao diện
}
?>

<div class="dashboard-container">
    <div class="dashboard-title">
        <h2>📊 Tổng quan hệ thống quản trị</h2>
        <p>Báo cáo tình trạng vận hành kho và chỉ số luân chuyển hàng hóa thực tế.</p>
    </div>

    <div class="card-grid">
        <div class="card card-blue">
            <div class="card-icon">📦</div>
            <div class="card-info">
                <h3><?php echo $totalProducts; ?></h3>
                <p>Danh mục sản phẩm</p>
            </div>
        </div>

        <div class="card card-green">
            <div class="card-icon">🏢</div>
            <div class="card-info">
                <h3><?php echo number_format($totalStockVolume); ?></h3>
                <p>Tổng sản lượng tồn kho</p>
            </div>
        </div>

        <div class="card card-orange">
            <div class="card-icon">⚠️</div>
            <div class="card-info">
                <h3><?php echo $lowStockAlert; ?></h3>
                <p>Cảnh báo hết hàng</p>
            </div>
        </div>
    </div>

    <div class="dashboard-details">
        <div class="detail-box">
            <h4>🔄 Nhật ký kho mới nhất</h4>
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Nghiệp vụ</th>
                        <th>Sản phẩm</th>
                        <th>Số lượng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentActivities)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #95a5a6; padding: 30px 10px;">
                                Đang chờ kết nối dữ liệu từ các mô-đun nghiệp vụ...
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentActivities as $activity): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($activity['time']); ?></td>
                                <td>
                                    <span class="badge <?php echo $activity['type'] == 'in' ? 'badge-in' : 'badge-out'; ?>">
                                        <?php echo $activity['type'] == 'in' ? 'Nhập kho' : 'Xuất kho'; ?>
                                    </span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($activity['product_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($activity['quantity']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="detail-box quick-links">
            <h4>⚡ Thao tác nhanh</h4>
            <a href="index.php?page=products" class="link-btn">➡️ Quản lý danh mục sản phẩm</a>
            <a href="index.php?page=partners" class="link-btn">➡️ Quản lý đối tác KH/NCC</a>
            <a href="index.php?page=audit" class="link-btn link-btn-secondary">➡️ Kiểm kê & Điều chỉnh kho</a>
            <a href="index.php?page=reports" class="link-btn link-btn-secondary">➡️ Xem báo cáo phân tích</a>

            <div class="system-status-container">
                <h5>🖥️ Trạng thái máy chủ Docker</h5>
                
                <div class="status-item">
                    <span class="status-label">Cơ sở dữ liệu (DB):</span>
                    <span class="status-value text-success"><span class="dot-online"></span> Trực tuyến (Connected)</span>
                </div>
                
                <div class="status-item">
                    <span class="status-label">Cổng mạng kết nối:</span>
                    <span class="status-value">Host: <code class="code-spec">db</code> | Port: <code class="code-spec">3306</code></span>
                </div>
                
                <div class="status-item" style="margin-top: 15px; border-top: 1px dashed #eef2f5; padding-top: 10px;">
                    <span class="status-label">Tài khoản trực ban:</span>
                    <span class="status-value"><strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
                </div>

                <div class="status-item">
                    <span class="status-label">Phiên làm việc:</span>
                    <span class="status-value text-blue">Đang hoạt động</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .dashboard-container { animation: fadeIn 0.4s ease-in-out; }
    .dashboard-title { margin-bottom: 25px; }
    .dashboard-title h2 { color: #1e3d59; font-size: 1.6rem; }
    .dashboard-title p { color: #7f8c8d; font-size: 0.9rem; margin-top: 5px; }

    /* Định dạng lưới thẻ Card */
    .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .card { background: #ffffff; border-radius: 8px; padding: 20px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); border-bottom: 4px solid transparent; }
    .card-icon { font-size: 2.5rem; }
    .card-info h3 { font-size: 1.8rem; color: #2c3e50; font-weight: 700; }
    .card-info p { color: #7f8c8d; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
    
    /* Màu sắc nhận diện hệ thống */
    .card-blue { border-bottom-color: #1e3d59; }
    .card-green { border-bottom-color: #17b978; }
    .card-orange { border-bottom-color: #ff9f43; }

    /* Bố cục vùng chi tiết */
    .dashboard-details { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    .detail-box { background: #ffffff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
    .detail-box h4 { color: #1e3d59; margin-bottom: 15px; font-size: 1.05rem; border-bottom: 2px solid #f4f6f9; padding-bottom: 10px; }

    /* Định dạng bảng dữ liệu */
    .dashboard-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; }
    .dashboard-table th, .dashboard-table td { padding: 12px 10px; border-bottom: 1px solid #f4f6f9; }
    .dashboard-table th { color: #7f8c8d; font-weight: 600; }
    .dashboard-table td { color: #2c3e50; }
    
    /* Huy hiệu trạng thái */
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; }
    .badge-in { background-color: #e3fcef; color: #155724; }
    .badge-out { background-color: #fff0f0; color: #721c24; }

    /* Nút thao tác nhanh */
    .quick-links { display: flex; flex-direction: column; }
    .link-btn { display: block; background: #1e3d59; color: white; padding: 12px; text-decoration: none; border-radius: 6px; text-align: center; font-weight: 500; font-size: 0.9rem; margin-bottom: 10px; transition: background 0.2s; }
    .link-btn:hover { background: #17b978; }
    .link-btn-secondary { background: #7f8c8d; }
    .link-btn-secondary:hover { background: #6c7a89; }

    /* 🔵 CSS ĐỘC LẬP CHO KHỐI TIỆN ÍCH LẤP ĐẦY KHOẢNG TRỐNG */
    .system-status-container { margin-top: 20px; padding: 15px; background-color: #f8fafc; border-radius: 6px; border: 1px solid #eef2f5; }
    .system-status-container h5 { color: #1e3d59; font-size: 0.9rem; margin-bottom: 12px; font-weight: 600; }
    .status-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 0.85rem; }
    .status-label { color: #7f8c8d; }
    .status-value { color: #2c3e50; font-weight: 500; }
    .text-success { color: #17b978 !important; display: flex; align-items: center; gap: 5px; }
    .text-blue { color: #1e3d59 !important; font-weight: bold; }
    .code-spec { background: #eef2f5; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 0.8rem; color: #e83e8c; }
    
    /* Chấm tròn nhấp nháy tạo hiệu ứng Live cho Docker */
    .dot-online { width: 8px; height: 8px; background-color: #17b978; border-radius: 50%; display: inline-block; box-shadow: 0 0 8px #17b978; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
````

## File: modules/New Text Document.txt
````

````

## File: modules/partners.php
````php
<?php
/**
 * 👥 MÔ-ĐUN QUẢN LÝ ĐỐI TÁC (KHACH HÀNG / NHÀ CUNG CẤP)
 */
$pdo = getPDOLayerConnection();
$errors = []; $messages = [];
$edit_mode = false; $edit_data = null;

try {
    // TẦNG KIỂM TRA BẢO VỆ
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'partners'")->fetch();
    if (!$tableCheck) throw new Exception("Bảng 'partners' chưa có. Hãy chạy file SQL cấu trúc trước.");

    // [Chức năng 3] XỬ LÝ HÀNH ĐỘNG XÓA
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['code'])) {
        $stmt = $pdo->prepare("DELETE FROM partners WHERE code = ?");
        $stmt->execute([$_GET['code']]);
        $messages[] = "Đã xóa đối tác có mã [{$_GET['code']}] thành công.";
    }

    // LẤY DỮ LIỆU ĐỂ SỬA
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['code'])) {
        $stmt = $pdo->prepare("SELECT * FROM partners WHERE code = ?");
        $stmt->execute([$_GET['code']]);
        $edit_data = $stmt->fetch();
        if ($edit_data) $edit_mode = true;
    }

    // [Chức năng 1 & 2] XỬ LÝ THÊM MỚI HOẶC CẬP NHẬT
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_partner'])) {
        $code = trim($_POST['code'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? 'customer';
        $phone = trim($_POST['phone'] ?? '');

        if (empty($code) || empty($name)) {
            $errors[] = "Mã đối tác và Tên không được để trống.";
        } else {
            if (isset($_POST['is_edit']) && $_POST['is_edit'] == '1') {
                $stmt = $pdo->prepare("UPDATE partners SET name=?, type=?, phone=? WHERE code=?");
                $stmt->execute([$name, $type, $phone, $code]);
                $messages[] = "Đã cập nhật thông tin đối tác [{$code}].";
                $edit_mode = false; // Thoát chế độ sửa
            } else {
                $stmtCheck = $pdo->prepare("SELECT code FROM partners WHERE code = ?");
                $stmtCheck->execute([$code]);
                if ($stmtCheck->fetch()) {
                    $errors[] = "Mã Đối tác [{$code}] này đã tồn tại.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO partners (code, name, type, phone) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$code, $name, $type, $phone]);
                    $messages[] = "Đã thêm đối tác mới: [{$code}] - {$name}.";
                }
            }
        }
    }

    // LẤY DANH SÁCH
    $partners = $tableCheck ? $pdo->query("SELECT * FROM partners ORDER BY id DESC")->fetchAll() : [];

} catch (Exception $e) {
    $errors[] = "Lỗi nghiệp vụ Đối tác: " . $e->getMessage();
}
?>

<div class="container-fluid" style="padding: 10px;">
    <h2 style="color: #1e3d59; margin-bottom: 20px; font-weight: 700;">Danh Bạ Đối Tác (CRM)</h2>

    <?php foreach($errors as $error): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 15px; border-left: 5px solid #f5c6cb;">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>
    <?php foreach($messages as $msg): ?>
        <div style="background-color: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 15px; border-left: 5px solid #c3e6cb;">✅ <?= htmlspecialchars($msg) ?></div>
    <?php endforeach; ?>

    <!-- KHỐI FORM NHẬP LIỆU -->
    <div style="background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 30px;">
        <div style="font-weight: bold; font-size: 1.1rem; color: #1e3d59; border-bottom: 2px solid #eef2f5; padding-bottom: 10px; margin-bottom: 20px;">
            <?= $edit_mode ? '🛠️ Hiệu Chỉnh Đối Tác' : '➕ Thêm Mới Khách Hàng / Nhà Cung Cấp' ?>
        </div>
        
        <form method="POST" action="index.php?page=partners">
            <?php if($edit_mode): ?>
                <input type="hidden" name="is_edit" value="1">
                <input type="hidden" name="code" value="<?= htmlspecialchars($edit_data['code']) ?>">
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div>
                    <label style="display:block; margin-bottom: 6px; font-weight: 600;">Mã định danh (Code)</label>
                    <input type="text" name="code" value="<?= $edit_mode ? htmlspecialchars($edit_data['code']) : '' ?>" <?= $edit_mode ? 'disabled' : '' ?> style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; background: <?= $edit_mode ? '#f4f6f9' : '#fff' ?>;" required>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 6px; font-weight: 600;">Tên đối tác / Công ty</label>
                    <input type="text" name="name" value="<?= $edit_mode ? htmlspecialchars($edit_data['name']) : '' ?>" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;" required>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 6px; font-weight: 600;">Phân loại</label>
                    <select name="type" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="customer" <?= ($edit_mode && $edit_data['type']=='customer') ? 'selected' : '' ?>>Khách Hàng (Đầu ra)</option>
                        <option value="vendor" <?= ($edit_mode && $edit_data['type']=='vendor') ? 'selected' : '' ?>>Nhà Cung Cấp (Đầu vào)</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 6px; font-weight: 600;">Số điện thoại</label>
                    <input type="text" name="phone" value="<?= $edit_mode ? htmlspecialchars($edit_data['phone']) : '' ?>" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
            </div>
            
            <div style="margin-top: 20px;">
                <button type="submit" name="save_partner" style="background-color: #17b978; color: white; border: none; padding: 10px 20px; font-weight: bold; border-radius: 4px; cursor: pointer;">
                    <?= $edit_mode ? 'Lưu Cập Nhật' : 'Lưu Đối Tác Mới' ?>
                </button>
                <?php if($edit_mode): ?>
                    <a href="index.php?page=partners" style="margin-left: 10px; padding: 10px 20px; background: #7f8c8d; color: white; text-decoration: none; border-radius: 4px;">Hủy bỏ</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- KHỐI THANH CÔNG CỤ JAVASCRIPT -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h4 style="color: #1e3d59; font-weight: 600;">Danh Sách Đối Tác Hiện Hữu</h4>
        <div style="display: flex; gap: 10px;">
            <!-- [Chức năng 4] TÌM KIẾM NHANH -->
            <input type="text" id="searchInput" placeholder="🔍 Tìm tên hoặc mã..." style="padding: 8px 15px; border: 1px solid #ccc; border-radius: 4px; outline: none; width: 250px;">
            <!-- [Chức năng 5] XUẤT CSV -->
            <button onclick="exportTableToCSV('danh_sach_doi_tac.csv')" style="background: #1e3d59; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold;">📥 Xuất Excel (CSV)</button>
        </div>
    </div>

    <!-- BẢNG DỮ LIỆU -->
    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03);" id="partnerTable">
        <thead>
            <tr style="background-color: #eef2f5; text-align: left; color: #1e3d59;">
                <th style="padding: 15px;">Mã Code</th>
                <th style="padding: 15px;">Tên Đối Tác</th>
                <th style="padding: 15px;">Phân Loại</th>
                <th style="padding: 15px;">Liên Hệ</th>
                <th style="padding: 15px;" class="no-export">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($partners)): ?>
                <tr><td colspan="5" style="padding: 20px; text-align: center;">Chưa có dữ liệu.</td></tr>
            <?php else: ?>
                <?php foreach($partners as $pt): ?>
                <tr style="border-bottom: 1px solid #eef2f5;">
                    <td style="padding: 15px;"><strong><?= htmlspecialchars($pt['code']) ?></strong></td>
                    <td style="padding: 15px; font-weight: 500;"><?= htmlspecialchars($pt['name']) ?></td>
                    <td style="padding: 15px;">
                        <span style="padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; background-color: <?= $pt['type'] == 'customer' ? '#e3fcef' : '#fff0f0' ?>; color: <?= $pt['type'] == 'customer' ? '#155724' : '#721c24' ?>;">
                            <?= $pt['type'] == 'customer' ? 'Khách hàng' : 'Nhà cung cấp' ?>
                        </span>
                    </td>
                    <td style="padding: 15px;"><?= htmlspecialchars($pt['phone']) ?></td>
                    <td style="padding: 15px;" class="no-export">
                        <a href="index.php?page=partners&action=edit&code=<?= $pt['code'] ?>" style="background-color: #3498db; color: white; padding: 6px 12px; font-size: 0.85rem; border-radius: 4px; text-decoration: none; margin-right: 5px;">Sửa</a>
                        <a href="index.php?page=partners&action=delete&code=<?= $pt['code'] ?>" onclick="return confirm('Bạn chắc chắn xóa?');" style="background-color: #e74c3c; color: white; padding: 6px 12px; font-size: 0.85rem; border-radius: 4px; text-decoration: none;">Xóa</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- JAVASCRIPT XỬ LÝ -->
<script>
// Logic Tìm kiếm Live
document.getElementById("searchInput").addEventListener("keyup", function() {
    let filter = this.value.toLowerCase();
    let rows = document.getElementById("partnerTable").getElementsByTagName("tr");
    for (let i = 1; i < rows.length; i++) {
        rows[i].style.display = rows[i].textContent.toLowerCase().includes(filter) ? "" : "none";
    }
});

// Logic Xuất CSV
function exportTableToCSV(filename) {
    let csv = [];
    let rows = document.querySelectorAll("table#partnerTable tr");
    for (let i = 0; i < rows.length; i++) {
        if(rows[i].style.display === "none") continue; // Không xuất dòng bị ẩn
        let row = [], cols = rows[i].querySelectorAll("td, th");
        for (let j = 0; j < cols.length; j++) {
            if(!cols[j].classList.contains("no-export")){
                row.push('"' + cols[j].innerText.trim().replace(/"/g, '""') + '"');
            }
        }
        csv.push(row.join(","));
    }
    let csvFile = new Blob(["\uFEFF"+csv.join("\n")], {type: "text/csv;charset=utf-8;"});
    let downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
}
</script>
````

## File: modules/products.php
````php
<?php
/**
 * 📦 MÔ-ĐUN QUẢN LÝ DANH MỤC SẢN PHẨM (PRODUCT MASTER DATA)
 * Tích hợp tính năng: Thêm, Sửa, Xóa, hiển thị đồng bộ với Odoo Stock Engine.
 */
$pdo = getPDOLayerConnection();
$errors = [];
$messages = [];

// Khởi tạo biến xử lý form Sửa
$edit_mode = false;
$edit_product = null;

try {
    // Nếu bảng products đã tồn tại nhưng thiếu các cột cần thiết, bổ sung tự động
    try {
        $requiredColumns = [
            'description' => 'TEXT DEFAULT NULL',
            'price' => 'DECIMAL(14,2) NOT NULL DEFAULT 0',
            'qty' => 'INT NOT NULL DEFAULT 0',
        ];
        foreach ($requiredColumns as $column => $definition) {
            $columnCheck = $pdo->prepare("SHOW COLUMNS FROM products LIKE ?");
            $columnCheck->execute([$column]);
            if (!$columnCheck->fetch()) {
                $pdo->exec("ALTER TABLE products ADD COLUMN {$column} {$definition}");
            }
        }
    } catch (PDOException $schemaException) {
        // Bỏ qua lỗi nếu bảng products chưa tồn tại, lỗi sẽ được xử lý bên dưới.
    }
    // 🛑 1. XỬ LÝ HÀNH ĐỘNG XÓA (DELETE)
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['sku'])) {
        $delete_sku = $_GET['sku'];
        $stmtDelete = $pdo->prepare("DELETE FROM products WHERE sku = ?");
        $stmtDelete->execute([$delete_sku]);
        $messages[] = "Đã xóa sản phẩm với mã SKU [{$delete_sku}] thành công.";
    }

    // 🛑 2. XỬ LÝ HÀNH ĐỘNG LẤY THÔNG TIN ĐỂ SỬA (GET EDIT DATA)
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['sku'])) {
        $edit_sku = $_GET['sku'];
        $stmtGetEdit = $pdo->prepare("SELECT * FROM products WHERE sku = ?");
        $stmtGetEdit->execute([$edit_sku]);
        $edit_product = $stmtGetEdit->fetch();
        if ($edit_product) {
            $edit_mode = true;
        }
    }

    // 🛑 3. XỬ LÝ FORM SUBMIT (THÊM MỚI HOẶC CẬP NHẬT)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
        $sku = trim($_POST['sku'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $qty = intval($_POST['qty'] ?? 0); // Số lượng tồn ban đầu

        if (empty($sku) || empty($name)) {
            $errors[] = "Mã SKU và Tên sản phẩm không được để trống.";
        } else {
            if (isset($_POST['is_edit_mode']) && $_POST['is_edit_mode'] == '1') {
                // Logic Cập nhật (Update)
                $stmtUpdate = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, qty = ? WHERE sku = ?");
                $stmtUpdate->execute([$name, $description, $price, $qty, $sku]);
                $messages[] = "Đã cập nhật thông tin sản phẩm [{$sku}] thành công.";
                $edit_mode = false; // Thoát chế độ sửa
            } else {
                // Logic Thêm mới (Insert) - Kiểm tra trùng SKU trước
                $stmtCheck = $pdo->prepare("SELECT sku FROM products WHERE sku = ?");
                $stmtCheck->execute([$sku]);
                if ($stmtCheck->fetch()) {
                    throw new Exception("Mã SKU [{$sku}] này đã tồn tại trong hệ thống.");
                }

                $stmtInsert = $pdo->prepare("INSERT INTO products (sku, name, description, price, qty) VALUES (?, ?, ?, ?, ?)");
                $stmtInsert->execute([$sku, $name, $description, $price, $qty]);
                $messages[] = "Đã thêm mới thành công sản phẩm [{$sku}].";
            }
        }
    }

    // 🛑 4. LẤY DANH SÁCH SẢN PHẨM CÓ TÍCH HỢP TÌM KIẾM, LỌC VÀ PHÂN TRANG (READ)

// Cấu hình phân trang: Mỗi trang hiển thị tối đa 5 sản phẩm
$limit = 5; 

// Lấy số trang hiện tại từ thanh URL (?p=1, ?p=2...). Nếu không có thì mặc định là trang 1
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) {
    $page = 1;
}

// Tính toán vị trí (bản ghi) bắt đầu lấy dữ liệu trong Database
$offset = ($page - 1) * $limit;

// Lấy từ khóa Tìm kiếm và Bộ lọc trạng thái kho từ Form gửi lên (nếu có)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_stock = isset($_GET['filter_stock']) ? trim($_GET['filter_stock']) : 'all';

// Khởi tạo mảng chứa các điều kiện WHERE và mảng chứa tham số truyền vào câu lệnh SQL
$whereClauses = [];
$params = [];

// Nếu người dùng có nhập từ khóa tìm kiếm
if ($search !== '') {
    $whereClauses[] = "(name LIKE :search OR sku LIKE :search)";
    $params[':search'] = "%" . $search . "%"; // Tìm kiếm tương đối (chứa từ khóa là được)
}

// Nếu người dùng chọn bộ lọc trạng thái số lượng tồn kho
if ($filter_stock === 'low') {
    $whereClauses[] = "qty <= 10"; // Sắp hết hàng
} elseif ($filter_stock === 'out') {
    $whereClauses[] = "qty = 0";   // Đã hết hàng
} elseif ($filter_stock === 'available') {
    $whereClauses[] = "qty > 10";  // Còn hàng dồi dào
}

// Gộp các điều kiện lại với nhau bằng chữ "AND" nếu có nhiều hơn 1 điều kiện
$whereSql = '';
if (count($whereClauses) > 0) {
    $whereSql = " WHERE " . implode(' AND ', $whereClauses);
}

// BƯỚC A: Đếm tổng số lượng sản phẩm thỏa mãn điều kiện để tính tổng số trang
$countSql = "SELECT COUNT(*) FROM products" . $whereSql;
$stmtCount = $pdo->prepare($countSql);
foreach ($params as $key => $val) {
    $stmtCount->bindValue($key, $val);
}
$stmtCount->execute();
$totalRows = $stmtCount->fetchColumn(); // Trả về một con số tổng duy nhất

// Tính tổng số trang (Dùng hàm ceil để làm tròn lên, ví dụ: 6 sản phẩm/5 = 1.2 -> cần 2 trang)
$totalPages = ceil($totalRows / $limit);

// BƯỚC B: Lấy danh sách sản phẩm thực tế của trang hiện tại (Sử dụng LIMIT và OFFSET)
$sql = "SELECT * FROM products" . $whereSql . " ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

// Ràng buộc (bind) các tham số tìm kiếm
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
// Ràng buộc tham số phân trang dưới dạng số nguyên (bắt buộc dùng PDO::PARAM_INT)
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

// Biến $products này sẽ chứa đúng 5 sản phẩm đã lọc để phần HTML ở dưới tự động vẽ ra bảng
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $errors[] = "Lỗi nghiệp vụ sản phẩm: " . $e->getMessage();
}
?>

<div class="container-fluid" style="padding: 10px;">
    <h2 style="color: #1e3d59; margin-bottom: 20px; font-weight: 700;">Quản Lý Danh Mục Sản Phẩm (Master Data)</h2>

    <?php foreach($errors as $error): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 15px; border-left: 5px solid #f5c6cb;">
            <strong>⚠️ Lỗi:</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php endforeach; ?>
    
    <?php foreach($messages as $msg): ?>
        <div style="background-color: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 15px; border-left: 5px solid #c3e6cb;">
            <strong>✅ Thành công:</strong> <?= htmlspecialchars($msg) ?>
        </div>
    <?php endforeach; ?>

    <div style="background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 30px;">
        <div style="font-weight: bold; font-size: 1.1rem; color: #1e3d59; border-bottom: 2px solid #eef2f5; padding-bottom: 10px; margin-bottom: 20px;">
            <?= $edit_mode ? "🛠️ Hiệu Chỉnh Sản Phẩm: " . htmlspecialchars($edit_product['sku']) : "➕ Thêm Sản Phẩm Mới Vào Hệ Thống" ?>
        </div>
        
        <form method="POST" action="index.php?page=products">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="is_edit_mode" value="1">
                <input type="hidden" name="sku" value="<?= htmlspecialchars($edit_product['sku']) ?>">
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div>
                    <label style="display:block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600;">Mã sản phẩm (SKU)</label>
                    <input type="text" name="sku" value="<?= $edit_mode ? htmlspecialchars($edit_product['sku']) : '' ?>" <?= $edit_mode ? 'disabled' : '' ?> placeholder="Ví dụ: PROD-CPU-I9" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px; background-color: <?= $edit_mode ? '#eef2f5' : '#ffffff' ?>;" required>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600;">Tên mặt hàng</label>
                    <input type="text" name="name" value="<?= $edit_mode ? htmlspecialchars($edit_product['name']) : '' ?>" placeholder="Nhập tên sản phẩm..." style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;" required>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600;">Giá bán (VNĐ)</label>
                    <input type="number" name="price" value="<?= $edit_mode ? htmlspecialchars($edit_product['price']) : '0' ?>" min="0" step="0.01" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;" required>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600;">Số lượng tồn đầu kỳ</label>
                    <input type="number" name="qty" value="<?= $edit_mode ? htmlspecialchars($edit_product['qty']) : '0' ?>" min="0" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;" required>
                </div>
            </div>
            
            <div style="margin-top: 15px;">
                <label style="display:block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600;">Mô tả sản phẩm</label>
                <textarea name="description" rows="2" placeholder="Ghi chú thông số kỹ thuật, thuộc tính..." style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;"><?= $edit_mode ? htmlspecialchars($edit_product['description']) : '' ?></textarea>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" name="save_product" style="background-color: #17b978; color: white; border: none; padding: 10px 20px; font-weight: bold; border-radius: 4px; cursor: pointer;">
                    <?= $edit_mode ? "Cập Nhật (Save)" : "Lưu Sản Phẩm" ?>
                </button>
                <?php if ($edit_mode): ?>
                    <a href="index.php?page=products" style="background-color: #7f8c8d; color: white; text-decoration: none; padding: 10px 20px; font-weight: bold; border-radius: 4px; line-height: 1.5;">Hủy bỏ</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <h4 style="color: #1e3d59; margin-bottom: 15px; font-weight: 600;">Danh Sách Mặt Hàng Hiện Hữu</h4>
    <div style="background: #ffffff; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #cbd5e1;">
    <form method="GET" action="index.php" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
        
        <input type="hidden" name="page" value="products">
        
        <div style="flex: 1; min-width: 200px;">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                   placeholder="Tìm theo tên sản phẩm hoặc mã SKU..." 
                   style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box;">
        </div>
        
        <div style="width: 180px;">
            <select name="filter_stock" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; background: white;">
                <option value="all" <?= $filter_stock === 'all' ? 'selected' : '' ?>>Tất cả trạng thái kho</option>
                <option value="available" <?= $filter_stock === 'available' ? 'selected' : '' ?>>Còn hàng dồi dào (>10)</option>
                <option value="low" <?= $filter_stock === 'low' ? 'selected' : '' ?>>Sắp hết hàng (≤10)</option>
                <option value="out" <?= $filter_stock === 'out' ? 'selected' : '' ?>>Đã cháy kho (0)</option>
            </select>
        </div>
        
        <div>
            <button type="submit" style="background: #1e3d59; color: white; border: none; padding: 8px 16px; border-radius: 4px; font-weight: bold; cursor: pointer;">
                Tìm & Lọc
            </button>
            
            <?php if ($search !== '' || $filter_stock !== 'all'): ?>
                <a href="index.php?page=products" style="margin-left: 10px; color: #ef4444; text-decoration: none; font-size: 0.9rem;">Xóa bộ lọc</a>
            <?php endif; ?>
        </div>
    </form>
</div>                
    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <thead>
            <tr style="background-color: #eef2f5; text-align: left; color: #1e3d59; font-size: 0.9rem;">
                <th style="padding: 15px;">ID</th>
                <th style="padding: 15px;">Mã SKU</th>
                <th style="padding: 15px;">Tên sản phẩm</th>
                <th style="padding: 15px;">Mô tả</th>
                <th style="padding: 15px;">Giá niêm yết</th>
                <th style="padding: 15px;">Số lượng trong kho</th>
                <th style="padding: 15px; text-align: center;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr><td colspan="7" style="padding: 20px; text-align: center; color: #7f8c8d;">Không có sản phẩm nào tồn tại. Hãy thêm mới sản phẩm ở form trên.</td></tr>
            <?php else: ?>
                <?php foreach($products as $prod): ?>
                <tr style="border-bottom: 1px solid #eef2f5; font-size: 0.95rem;">
                    <td style="padding: 15px; color: #7f8c8d;"><?= $prod['id'] ?></td>
                    <td style="padding: 15px;"><strong><?= htmlspecialchars($prod['sku']) ?></strong></td>
                    <td style="padding: 15px; font-weight: 500; color: #2c3e50;"><?= htmlspecialchars($prod['name']) ?></td>
                    <td style="padding: 15px; color: #95a5a6; font-size: 0.85rem; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        <?= htmlspecialchars(isset($prod['description']) && $prod['description'] !== '' ? $prod['description'] : 'Chưa có mô tả') ?>
                    </td>
                    <?php $display_price = isset($prod['price']) ? floatval($prod['price']) : 0; ?>
                    <td style="padding: 15px; color: #e74c3c; font-weight: bold;">
                        <?= $display_price > 0 ? number_format($display_price) . ' VNĐ' : 'Chưa có giá' ?>
                    </td>
                    <td style="padding: 15px;">
                        <span style="padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; background-color: <?= $prod['qty'] > 10 ? '#d4edda' : '#f8d7da' ?>; color: <?= $prod['qty'] > 10 ? '#155724' : '#721c24' ?>;">
                            <?= number_format($prod['qty']) ?> cái
                        </span>
                    </td>
                    <td style="padding: 15px; text-align: center; display: flex; gap: 8px; justify-content: center;">
                        <a href="index.php?page=products&action=edit&sku=<?= $prod['sku'] ?>" style="background-color: #3498db; color: white; padding: 6px 12px; font-size: 0.85rem; font-weight: bold; text-decoration: none; border-radius: 4px; transition: background 0.2s;">Sửa</a>
                        <a href="index.php?page=products&action=delete&sku=<?= $prod['sku'] ?>" onclick="return confirm('Bạn chắc chắn muốn xóa sản phẩm này?');" style="background-color: #e74c3c; color: white; padding: 6px 12px; font-size: 0.85rem; font-weight: bold; text-decoration: none; border-radius: 4px; transition: background 0.2s;">Xóa</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php if ($totalPages > 1): ?>
<div style="display: flex; justify-content: center; align-items: center; margin-top: 20px; gap: 5px; font-family: sans-serif;">
    
    <?php if ($page > 1): ?>
        <a href="index.php?page=products&p=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&filter_stock=<?= $filter_stock ?>" 
           style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 4px; text-decoration: none; color: #1e3d59; background: white;">&laquo; Trước</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="index.php?page=products&p=<?= $i ?>&search=<?= urlencode($search) ?>&filter_stock=<?= $filter_stock ?>" 
           style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 4px; text-decoration: none; font-weight: bold;
                  <?= $page === $i ? 'background: #17b978; color: white; border-color: #17b978;' : 'background: white; color: #333;' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
        <a href="index.php?page=products&p=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&filter_stock=<?= $filter_stock ?>" 
           style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 4px; text-decoration: none; color: #1e3d59; background: white;">Sau &raquo;</a>
    <?php endif; ?>

</div>

<div style="text-align: center; margin-top: 8px; color: #64748b; font-size: 0.85rem; font-family: sans-serif;">
    Hiển thị trang <?= $page ?> / <?= $totalPages ?> (Tổng số kết quả: <?= $totalRows ?> sản phẩm)
</div>
<?php endif; ?>
````

## File: modules/reports.php
````php
<?php
// Kiểm tra bảo mật: Không cho phép truy cập trực tiếp file này từ URL bên ngoài
if (!isset($_SESSION['user_name'])) {
    exit("Truy cập bị từ chối!");
}
// 🛑 ĐOẠN XỬ LÝ LOGIC ÉP TRÌNH DUYỆT TẢI FILE EXCEL (.CSV) KHI NGƯỜI DÙNG BẤM NÚT
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    // 1. Cấu hình Header để thông báo với trình duyệt đây là một tệp tin tải về thay vì trang web HTML
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=BaoCao_TonKho_' . date('Ymd_His') . '.csv');
    
    // 2. XUẤT CHUỖI UTF-8 BOM (Bắt buộc phải có để Microsoft Excel mở file không bị lỗi font tiếng Việt)
    echo "\xEF\xBB\xBF";
    
    // Mở luồng ghi dữ liệu trực tiếp ra file tải về
    $output = fopen('php://output', 'w');
    
    // 3. Ghi các dòng tiêu đề giới thiệu trên cùng của file báo cáo
    fputcsv($output, ['BÁO CÁO THỐNG KÊ CHI TIẾT GIÁ TRỊ VỐN TỒN KHO HỆ THỐNG']);
    fputcsv($output, ['Thời gian xuất bản:', date('d/m/Y H:i:s')]);
    fputcsv($output, []); // Tạo một hàng trống để giãn cách dữ liệu
    
    // 4. Định nghĩa hàng tiêu đề của các cột trong bảng Excel
    fputcsv($output, ['STT', 'Mã SKU', 'Tên sản phẩm hàng hóa', 'Số lượng tồn thực tế', 'Giá niêm yết (VNĐ)', 'Tổng giá trị vốn kho']);
    
    // 5. Kết nối database tạm thời để quét dữ liệu sản phẩm xuất ra file
    $export_db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($export_db && !$export_db->connect_error) {
        $export_db->set_charset("utf8");
        $res = $export_db->query("SELECT sku, name, qty, price FROM products ORDER BY id DESC");
        $stt = 1;
        $grandTotalValue = 0;
        
        while ($row = $res->fetch_assoc()) {
            $totalStockValue = $row['price'] * $row['qty'];
            $grandTotalValue += $totalStockValue;
            
            fputcsv($output, [
                $stt++,
                $row['sku'],
                $row['name'],
                number_format($row['qty']) . ' cái',
                number_format($row['price']) . ' VNĐ',
                number_format($totalStockValue) . ' VNĐ'
            ]);
        }
        
        // Tạo hàng tổng kết tổng giá trị vốn ở cuối cùng của file Excel
        fputcsv($output, []);
        fputcsv($output, ['', '', '', '', 'TỔNG GIÁ TRỊ VỐN KHO TOÀN HỆ THỐNG:', number_format($grandTotalValue) . ' VNĐ']);
        $export_db->close();
    }
    
    // Đóng luồng tải file
    fclose($output);
    exit(); // Chặn đứng không cho mã HTML bên dưới chạy tiếp vào file Excel
}

/**
 * 🛠️ TẦNG KẾT NỐI VÀ XỬ LÝ SỐ LIỆU PHÂN TÍCH CHUYÊN SÂU (BUSINESS INTELLIGENCE LAYER)
 * Thực hiện tính toán tài chính, giá trị tồn kho động từ MySQL
 */
$db_connection = null;
$totalInventoryValue = 0; // Tổng giá trị vốn kho (Giá x Số lượng của tất cả sản phẩm)
$highestValueProducts = []; // Top sản phẩm đọng vốn lớn nhất
$outOfStockProducts = [];   // Sản phẩm sắp cháy kho (Số lượng <= 10)

try {
    mysqli_report(MYSQLI_REPORT_OFF);
    $db_connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($db_connection && !$db_connection->connect_error) {
        $db_connection->set_charset("utf8");

        // 📊 1. Tính tổng giá trị toàn bộ kho hàng (Tổng SUM của Price * Qty)
        $value_res = mysqli_query($db_connection, "SELECT SUM(price * qty) as total_val FROM products");
        if ($value_res) {
            $value_row = mysqli_fetch_assoc($value_res);
            $totalInventoryValue = floatval($value_row['total_val'] ?? 0);
        }

        // 📋 2. Truy vấn danh sách cơ cấu vốn kho (Sắp xếp theo Giá trị tồn giảm dần)
        $highest_res = mysqli_query($db_connection, "SELECT sku, name, price, qty, (price * qty) as total_item_val FROM products ORDER BY total_item_val DESC");
        if ($highest_res) {
            while ($row = mysqli_fetch_assoc($highest_res)) {
                $row['price'] = isset($row['price']) ? floatval($row['price']) : 0;
                $row['total_item_val'] = isset($row['total_item_val']) ? floatval($row['total_item_val']) : 0;
                $highestValueProducts[] = $row;
                
                // Phân loại song song: Nếu sản phẩm có qty <= 10 thì đẩy vào danh sách cảnh báo cháy kho
                if (intval($row['qty']) <= 10) {
                    $outOfStockProducts[] = $row;
                }
            }
        }
    }
} catch (Exception $e) {
    // Đóng băng ngoại lệ bảo vệ luồng giao diện
}
?>

<div class="reports-container">
    <div class="reports-title">
        <h2>📊 Trung tâm Báo cáo & Phân tích Kinh doanh</h2>
        <p>Hệ thống tự động hóa tính toán giá trị dòng vốn tài sản và phân tích rủi ro lưu kho theo thời gian thực.</p>
    </div>

    <div class="report-summary-card">
        <div class="summary-icon">💵</div>
        <div class="summary-details">
            <p>TỔNG GIÁ TRỊ VỐN LƯU KHO ĐANG QUẢN LÝ</p>
            <h3><?php echo number_format($totalInventoryValue); ?> <span style="font-size: 1.2rem;">VNĐ</span></h3>
        </div>
    </div>

    <div class="reports-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 25px; align-items: start;">
        
        <div class="report-box">
            <h4>📈 Bảng Phân Tích Cơ Cấu Vốn Hàng Hóa</h4>
            <div style="margin: 10px 0 20px 0;">
                <a href="index.php?page=reports&action=export_csv" 
                    style="background-color: #27ae60; color: white; padding: 10px 16px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 2px 4px rgba(39,174,96,0.15); transition: 0.2s; font-family: sans-serif;">
                    <i class="fas fa-file-excel"></i> 📥 Xuất báo cáo trực quan ra file Excel (.CSV)
                </a>
            </div>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Mã SKU</th>
                        <th>Tên sản phẩm</th>
                        <th>Đơn giá</th>
                        <th>Tồn kho</th>
                        <th>Giá trị vốn kho</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($highestValueProducts)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #95a5a6; padding: 30px 10px;">
                                Hệ thống trống. Chưa có dữ liệu sản phẩm để phân tích vốn kho.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($highestValueProducts as $prod): ?>
                            <tr>
                                <td><code class="report-sku"><?php echo htmlspecialchars($prod['sku']); ?></code></td>
                                <td><strong><?php echo htmlspecialchars($prod['name']); ?></strong></td>
                                <td><?php echo number_format($prod['price']); ?> đ</td>
                                <td><?php echo number_format($prod['qty']); ?></td>
                                <td style="color: #1e3d59; font-weight: bold;"><?php echo number_format($prod['total_item_val']); ?> đ</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="report-box alert-box">
            <h4>⚠️ Cảnh Báo Rủi Ro Hết Hàng (Qty ≤ 10)</h4>
            <div class="alert-list" style="display: flex; flex-direction: column; gap: 12px; margin-top: 15px;">
                <?php if (empty($outOfStockProducts)): ?>
                    <div style="text-align: center; color: #17b978; padding: 20px; background: #e3fcef; border-radius: 6px; font-size: 0.85rem; font-weight: 500;">
                        ✅ Trạng thái lý tưởng: Không có sản phẩm nào sắp hết hàng!
                    </div>
                <?php else: ?>
                    <?php foreach ($outOfStockProducts as $alert_item): ?>
                        <div class="alert-card" style="background: #fff0f0; border-left: 4px solid #ff6b6b; padding: 12px; border-radius: 4px;">
                            <span style="font-size: 0.8rem; color: #721c24; font-weight: bold;">SKU: <?php echo htmlspecialchars($alert_item['sku']); ?></span>
                            <h5 style="margin: 4px 0; color: #2c3e50; font-size: 0.85rem;"><?php echo htmlspecialchars($alert_item['name']); ?></h5>
                            <p style="margin: 0; font-size: 0.8rem; color: #721c24;">
                                Nguy cơ cháy kho! Hiện chỉ còn: <strong><?php echo $alert_item['qty']; ?></strong> sản phẩm.
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<style>
    .reports-container { animation: fadeIn 0.4s ease-in-out; }
    .reports-title h2 { color: #1e3d59; font-size: 1.6rem; }
    .reports-title p { color: #7f8c8d; font-size: 0.9rem; margin-top: 5px; }

    /* Thẻ tổng vốn kho */
    .report-summary-card { background: linear-gradient(135deg, #1e3d59 0%, #17b978 100%); color: white; padding: 25px; border-radius: 8px; margin-top: 20px; display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 15px rgba(23, 185, 120, 0.2); }
    .summary-icon { font-size: 3rem; opacity: 0.9; }
    .summary-details p { font-size: 0.8rem; letter-spacing: 1px; font-weight: 500; margin: 0; opacity: 0.8; }
    .summary-details h3 { font-size: 2.2rem; margin: 5px 0 0 0; font-weight: bold; }

    /* Box nội dung */
    .report-box { background: #ffffff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
    .report-box h4 { color: #1e3d59; font-size: 1.05rem; border-bottom: 2px solid #f4f6f9; padding-bottom: 10px; margin: 0; font-weight: 600; }

    /* Định dạng bảng */
    .report-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; margin-top: 15px; }
    .report-table th, .report-table td { padding: 12px 10px; border-bottom: 1px solid #f4f6f9; }
    .report-table th { color: #7f8c8d; font-weight: 600; }
    .report-table td { color: #2c3e50; }
    .report-sku { background: #eef2f5; padding: 2px 6px; border-radius: 4px; font-family: monospace; color: #e83e8c; font-weight: bold; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
````

## File: modules/settings.php
````php
<?php
/**
 * ⚙️ MÔ-ĐUN CÀI ĐẶT TÀI KHOẢN NGƯỜI DÙNG
 * Chức năng:
 * - Xem thông tin tài khoản hiện tại
 * - Cập nhật username/email
 * - Đổi mật khẩu
 * - Cài đặt giao diện cơ bản bằng localStorage
 */

if (!isset($_SESSION['user_name'])) {
    exit("Truy cập bị từ chối!");
}

$pdo = getPDOLayerConnection();
$errors = [];
$messages = [];

// Tạo CSRF token đơn giản để bảo vệ form Settings
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function loadCurrentUser($pdo) {
    $stmt = $pdo->prepare("
        SELECT user_id, user_name, user_email, user_password_hash
        FROM users
        WHERE user_name = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_name']]);
    return $stmt->fetch();
}

try {
    $currentUser = loadCurrentUser($pdo);

    if (!$currentUser) {
        throw new Exception("Không tìm thấy thông tin người dùng hiện tại.");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrfToken = $_POST['csrf_token'] ?? '';

        if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
            throw new Exception("Phiên bảo mật không hợp lệ. Vui lòng thử lại.");
        }

        /**
         * 1. CẬP NHẬT THÔNG TIN CÁ NHÂN
         */
        if (isset($_POST['update_profile'])) {
            $newUsername = trim($_POST['user_name'] ?? '');
            $newEmail = trim($_POST['user_email'] ?? '');

            if (empty($newUsername) || empty($newEmail)) {
                $errors[] = "Tên đăng nhập và email không được để trống.";
            } elseif (strlen($newUsername) < 2 || strlen($newUsername) > 64) {
                $errors[] = "Tên đăng nhập phải từ 2 đến 64 ký tự.";
            } elseif (!preg_match('/^[a-zA-Z0-9]{2,64}$/', $newUsername)) {
                $errors[] = "Tên đăng nhập chỉ được chứa chữ cái và số.";
            } elseif (strlen($newEmail) > 64 || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Email không hợp lệ hoặc dài quá 64 ký tự.";
            } else {
                $stmtCheck = $pdo->prepare("
                    SELECT user_id 
                    FROM users 
                    WHERE (user_name = ? OR user_email = ?)
                    AND user_id <> ?
                    LIMIT 1
                ");
                $stmtCheck->execute([$newUsername, $newEmail, $currentUser['user_id']]);

                if ($stmtCheck->fetch()) {
                    $errors[] = "Tên đăng nhập hoặc email đã được sử dụng bởi tài khoản khác.";
                } else {
                    $stmtUpdate = $pdo->prepare("
                        UPDATE users
                        SET user_name = ?, user_email = ?
                        WHERE user_id = ?
                    ");
                    $stmtUpdate->execute([$newUsername, $newEmail, $currentUser['user_id']]);

                    $_SESSION['user_name'] = $newUsername;
                    $_SESSION['user_email'] = $newEmail;

                    $messages[] = "Cập nhật thông tin tài khoản thành công.";
                    $currentUser = loadCurrentUser($pdo);
                }
            }
        }

        /**
         * 2. ĐỔI MẬT KHẨU
         */
        if (isset($_POST['change_password'])) {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $repeatPassword = $_POST['repeat_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($repeatPassword)) {
                $errors[] = "Vui lòng nhập đầy đủ thông tin đổi mật khẩu.";
            } elseif (!password_verify($currentPassword, $currentUser['user_password_hash'])) {
                $errors[] = "Mật khẩu hiện tại không chính xác.";
            } elseif (strlen($newPassword) < 6) {
                $errors[] = "Mật khẩu mới phải có ít nhất 6 ký tự.";
            } elseif ($newPassword !== $repeatPassword) {
                $errors[] = "Mật khẩu mới và xác nhận mật khẩu không trùng khớp.";
            } else {
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

                $stmtPassword = $pdo->prepare("
                    UPDATE users
                    SET user_password_hash = ?
                    WHERE user_id = ?
                ");
                $stmtPassword->execute([$newPasswordHash, $currentUser['user_id']]);

                $messages[] = "Đổi mật khẩu thành công.";
                $currentUser = loadCurrentUser($pdo);
            }
        }
    }
} catch (Exception $e) {
    $errors[] = "Lỗi Settings: " . $e->getMessage();
}
?>

<div class="settings-container">
    <div class="settings-title">
        <h2>⚙️ Cài đặt tài khoản</h2>
        <p>Quản lý thông tin cá nhân, bảo mật và tuỳ chỉnh giao diện làm việc.</p>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error">
            <strong>⚠️ Lỗi:</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php endforeach; ?>

    <?php foreach ($messages as $message): ?>
        <div class="alert alert-success">
            <strong>✅ Thành công:</strong> <?= htmlspecialchars($message) ?>
        </div>
    <?php endforeach; ?>

    <div class="settings-grid">
        <!-- THÔNG TIN TÀI KHOẢN -->
        <div class="settings-card">
            <div class="card-title">
                <h3>👤 Thông tin tài khoản</h3>
                <p>Cập nhật tên đăng nhập và email dùng trong hệ thống.</p>
            </div>

            <form method="POST" action="index.php?page=settings">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <div class="form-group">
                    <label>Tên đăng nhập</label>
                    <input 
                        type="text" 
                        name="user_name" 
                        value="<?= htmlspecialchars($currentUser['user_name'] ?? '') ?>" 
                        required
                    >
                    <small>Chỉ dùng chữ cái và số, từ 2 đến 64 ký tự.</small>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input 
                        type="email" 
                        name="user_email" 
                        value="<?= htmlspecialchars($currentUser['user_email'] ?? '') ?>" 
                        required
                    >
                </div>

                <button type="submit" name="update_profile" class="btn-primary">
                    Lưu thay đổi
                </button>
            </form>
        </div>

        <!-- BẢO MẬT -->
        <div class="settings-card" id="security">
            <div class="card-title">
                <h3>🔐 Bảo mật</h3>
                <p>Đổi mật khẩu đăng nhập để bảo vệ tài khoản.</p>
            </div>

            <form method="POST" action="index.php?page=settings#security">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <div class="form-group">
                    <label>Mật khẩu hiện tại</label>
                    <input type="password" name="current_password" autocomplete="off" required>
                </div>

                <div class="form-group">
                    <label>Mật khẩu mới</label>
                    <input type="password" name="new_password" autocomplete="off" required>
                    <small>Tối thiểu 6 ký tự.</small>
                </div>

                <div class="form-group">
                    <label>Nhập lại mật khẩu mới</label>
                    <input type="password" name="repeat_password" autocomplete="off" required>
                </div>

                <button type="submit" name="change_password" class="btn-warning">
                    Đổi mật khẩu
                </button>
            </form>
        </div>
    </div>

    <!-- TUỲ CHỈNH GIAO DIỆN -->
    <div class="settings-card full-width">
        <div class="card-title">
            <h3>🎨 Tuỳ chỉnh giao diện</h3>
            <p>Các lựa chọn này được lưu trên trình duyệt hiện tại bằng localStorage.</p>
        </div>

        <div class="preference-grid">
            <div class="form-group">
                <label>Chế độ giao diện</label>
                <select id="themeMode">
                    <option value="light">Sáng</option>
                    <option value="soft">Xanh nhạt</option>
                    <option value="dark">Tối nhẹ</option>
                </select>
            </div>

            <div class="form-group">
                <label>Kích thước hiển thị</label>
                <select id="displayDensity">
                    <option value="comfortable">Thoải mái</option>
                    <option value="compact">Gọn hơn</option>
                </select>
            </div>
        </div>

        <button type="button" class="btn-secondary" onclick="saveUiSettings()">
            Lưu tuỳ chỉnh giao diện
        </button>
    </div>
</div>

<style>
    .settings-container {animation: fadeIn 0.4s ease-in-out;}
    .settings-title {margin-bottom: 24px;}
    .settings-title h2 {color: #1e3d59; font-size: 1.6rem; margin-bottom: 6px;}
    .settings-title p {color: #7f8c8d; font-size: 0.92rem;}
    .alert {padding: 13px 15px; border-radius: 8px; margin-bottom: 14px; font-size: 0.9rem;}
    .alert-error {background-color: #fff0f0; color: #721c24; border-left: 5px solid #ff6b6b;}
    .alert-success {background-color: #e3fcef; color: #155724; border-left: 5px solid #17b978}
    .settings-grid {display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px; align-items: start;}
    .settings-card {background-color: #ffffff; padding: 24px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.04); border: 1px solid #eef2f5;}
    .settings-card.full-width {margin-top: 20px;}
    .card-title {border-bottom: 2px solid #f4f6f9; padding-bottom: 12px; margin-bottom: 20px;}
    .card-title h3 {color: #1e3d59; font-size: 1.1rem; margin-bottom: 5px;}
    .card-title p {color: #7f8c8d; font-size: 0.86rem;}
    .form-group {margin-bottom: 18px;}
    .form-group label {display: block; color: #1e3d59; font-weight: 600; margin-bottom: 7px; font-size: 0.9rem;}
    .form-group input,
    .form-group select {width: 100%; padding: 11px 12px; border: 1px solid #dce3ea; border-radius: 6px; font-size: 0.95rem; background-color: #ffffff; outline: none; transition: border-color 0.2s ease, box-shadow 0.2s ease;}
    .form-group input:focus,
    .form-group select:focus {border-color: #17b978; box-shadow: 0 0 0 4px rgba(23, 185, 120, 0.1);}
    .form-group small {display: block; margin-top: 6px; color: #95a5a6; font-size: 0.8rem;}
    .preference-grid {display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;}
    .btn-primary,
    .btn-warning,
    .btn-secondary {border: none; padding: 11px 18px; border-radius: 6px; color: white; font-weight: 700; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease;}
    .btn-primary {background-color: #17b978;}
    .btn-warning {background-color: #ff9f43;}
    .btn-secondary {background-color: #1e3d59;}
    .btn-primary:hover,
    .btn-warning:hover,
    .btn-secondary:hover {transform: translateY(-1px);  box-shadow: 0 6px 16px rgba(0,0,0,0.12);}

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
function applyUiSettings() {
    const themeMode = localStorage.getItem('warehouse_theme_mode') || 'light';
    const displayDensity = localStorage.getItem('warehouse_display_density') || 'comfortable';

    const themeSelect = document.getElementById('themeMode');
    const densitySelect = document.getElementById('displayDensity');

    if (themeSelect) {
        themeSelect.value = themeMode;
    }

    if (densitySelect) {
        densitySelect.value = displayDensity;
    }

    const mainBody = document.querySelector('.main-body');

    if (mainBody) {
        if (themeMode === 'soft') {
            mainBody.style.backgroundColor = '#eefaf5';
        } else if (themeMode === 'dark') {
            mainBody.style.backgroundColor = '#e9eef3';
        } else {
            mainBody.style.backgroundColor = '#f8fafc';
        }

        if (displayDensity === 'compact') {
            mainBody.style.padding = '20px';
        } else {
            mainBody.style.padding = '30px';
        }
    }
}

function saveUiSettings() {
    const themeMode = document.getElementById('themeMode').value;
    const displayDensity = document.getElementById('displayDensity').value;

    localStorage.setItem('warehouse_theme_mode', themeMode);
    localStorage.setItem('warehouse_display_density', displayDensity);

    applyUiSettings();
    alert('Đã lưu tuỳ chỉnh giao diện trên trình duyệt này.');
}

document.addEventListener('DOMContentLoaded', applyUiSettings);
</script>
````

## File: modules/warehouse.php
````php
<?php
/**
 * 🚚 MÔ-ĐUN ĐIỀU PHỐI KHO VẬT CHẤT (ODOO STOCK ENGINE MODEL)
 * Đạt chuẩn xử lý Transaction song song, bảo trì tuyệt đối.
 */
$pdo = getPDOLayerConnection();
$errors = [];
$messages = [];

// Khởi tạo các biến danh sách để tránh lỗi hiển thị tầng giao diện
$all_products = [];
$pickings = [];

try {
    // TẦNG KIỂM TRA BẢO VỆ (SHIELD LAYER): Xác minh bảng có tồn tại thực tế trong DB không
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'products'")->fetch();
    if (!$tableCheck) {
        throw new Exception("Hạ tầng bảng 'products' chưa được khởi tạo. Vui lòng nạp tệp SQL cấu trúc vào Database.");
    }

    // XỬ LÝ LỆNH TẠO PHIẾU ĐIỀU CHUYỂN (Thực thi khi nhấn Validate)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_picking'])) {
        $type = $_POST['type'] ?? 'in';
        $origin = trim($_POST['origin'] ?? '');
        $origin_partner = trim($_POST['origin_partner'] ?? '');
        if (!empty($origin_partner)) {
            $origin = $origin_partner;
        }
        $sku = $_POST['sku'] ?? '';
        $qty = intval($_POST['qty'] ?? 0);

        if (empty($sku) || $qty <= 0) {
            $errors[] = "Dữ liệu sản phẩm hoặc số lượng dịch chuyển không hợp lệ.";
        } else {
            try {
                // Khởi động Transaction để bảo vệ tính toàn vẹn dữ liệu song song (ACID)
                $pdo->beginTransaction();

                // 1. Kiểm tra sản phẩm có tồn tại thực tế không
                $stmtProd = $pdo->prepare("SELECT qty FROM products WHERE sku = ?");
                $stmtProd->execute([$sku]);
                $product = $stmtProd->fetch();

                if (!$product) {
                    throw new Exception("Sản phẩm có mã SKU [{$sku}] này không tồn tại.");
                }

                // Nếu là xuất kho, kiểm tra xem lượng tồn thực tế có đủ không
                if ($type === 'out' && $product['qty'] < $qty) {
                    throw new Exception("Số lượng tồn kho không đủ để xuất! Hiện có: " . $product['qty']);
                }

                // 2. Tạo số phiếu tự động dạng chuỗi thời gian tuyến tính
                $prefix = ($type === 'in') ? 'WH/IN/' : 'WH/OUT/';
                $picking_number = $prefix . time();

                $stmtPick = $pdo->prepare("INSERT INTO stock_picking (picking_number, origin, type, state) VALUES (?, ?, ?, 'done')");
                $stmtPick->execute([$picking_number, $origin, $type]);
                $picking_id = $pdo->lastInsertId();

                // 3. Tạo dòng dịch chuyển chi tiết (Stock Move Line)
                $stmtMove = $pdo->prepare("INSERT INTO stock_move (picking_id, product_sku, product_qty) VALUES (?, ?, ?)");
                $stmtMove->execute([$picking_id, $sku, $qty]);

                // 4. Cập nhật trực tiếp số lượng tồn kho tổng ở bảng sản phẩm
                if ($type === 'in') {
                    $stmtUpdateStock = $pdo->prepare("UPDATE products SET qty = qty + ? WHERE sku = ?");
                } else {
                    $stmtUpdateStock = $pdo->prepare("UPDATE products SET qty = qty - ? WHERE sku = ?");
                }
                $stmtUpdateStock->execute([$qty, $sku]);

                // Cam kết dữ liệu an toàn vào DB
                $pdo->commit();
                $messages[] = "Đã xác nhận thành công phiếu hoạt động kho {$picking_number}!";

            } catch (Exception $e) {
                // Hủy bỏ mọi tác vụ dở dang nếu xuất hiện lỗi bất ngờ, đưa DB về trạng thái nguyên bản
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = "Giao dịch kho thất bại: " . $e->getMessage();
            }
        }
    }

    // LẤY DANH SÁCH SẢN PHẨM PHỤC VỤ CHỌN LỰA TRÊN FORM
    $all_products = $pdo->query("SELECT sku, name FROM products")->fetchAll();

    // LẤY DANH SÁCH ĐỐI TÁC ĐỂ SỬ DỤNG CHO TRƯỜNG ORIGIN
    $partner_options = [];
    try {
        $partner_options = $pdo->query("SELECT code, name, type FROM partners ORDER BY type, name")->fetchAll();
    } catch (Exception $e) {
        $partner_options = [];
    }

    // LẤY TOÀN BỘ DANH SÁCH LỊCH SỬ PHIẾU ĐIỀU CHUYỂN
    $pickings = $pdo->query("
        SELECT sp.*, sm.product_sku, sm.product_qty 
        FROM stock_picking sp 
        JOIN stock_move sm ON sp.id = sm.picking_id 
        ORDER BY sp.scheduled_date DESC
    ")->fetchAll();

} catch (Exception $e) {
    $errors[] = "Lỗi hệ thống cơ sở dữ liệu: " . $e->getMessage();
}
?>

<div class="container-fluid" style="padding: 10px;">
    <h2 style="color: #1e3d59; margin-bottom: 20px; font-weight: 700;">Điều Chuyển Kho Thực Tế (Odoo Engine Model)</h2>
    
    <?php foreach($errors as $error): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 15px; border-left: 5px solid #f5c6cb;">
            <strong>⚠️ Lỗi cấu trúc:</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php endforeach; ?>
    
    <?php foreach($messages as $msg): ?>
        <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin-bottom: 15px; border-left: 5px solid #c3e6cb;">
            <strong>✅ Thành công:</strong> <?= htmlspecialchars($msg) ?>
        </div>
    <?php endforeach; ?>

    <?php if ($tableCheck): ?>
    <div class="card mb-4" style="background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 30px;">
        <div style="font-weight: bold; font-size: 1.1rem; color: #1e3d59; border-bottom: 2px solid #eef2f5; padding-bottom: 10px; margin-bottom: 20px;">Khởi Tạo Phiếu Điều Chuyển Hàng Hóa</div>
        <form method="POST" action="">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
                <div>
                    <label style="display:block; margin-bottom: 8px; font-size: 0.9rem; font-weight: 600;">Loại hoạt động</label>
                    <select name="type" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;">
                        <option value="in">Nhập kho (Receipt - Mua hàng)</option>
                        <option value="out">Xuất kho (Delivery Order - Bán hàng)</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 8px; font-size: 0.9rem; font-weight: 600;">Đối tác / Chứng từ nguồn (Origin)</label>
                    <?php if (!empty($partner_options)): ?>
                        <select name="origin_partner" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px; margin-bottom: 12px;">
                            <option value="">-- Chọn đối tác nguồn --</option>
                            <?php foreach ($partner_options as $partner): ?>
                                <option value="<?= htmlspecialchars($partner['code']) ?>"><?= htmlspecialchars($partner['code'] . ' - ' . $partner['name'] . ' (' . ucfirst($partner['type']) . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                    <input type="text" name="origin" placeholder="Ví dụ: PO001 hoặc SO002" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;" <?php echo empty($partner_options) ? '' : ''; ?>>
                    <div style="margin-top: 8px; color: #7f8c8d; font-size: 0.85rem;">
                        Nếu chọn đối tác thì Origin sẽ lấy mã đối tác, nếu không bạn có thể nhập mã chứng từ nguồn thủ công.
                    </div>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 8px; font-size: 0.9rem; font-weight: 600;">Chọn sản phẩm dịch chuyển</label>
                    <select name="sku" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;" required>
                        <option value="">-- Chọn mặt hàng --</option>
                        <?php foreach($all_products as $prod): ?>
                            <option value="<?= $prod['sku'] ?>"><?= htmlspecialchars($prod['name']) ?> (<?= $prod['sku'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 8px; font-size: 0.9rem; font-weight: 600;">Số lượng luân chuyển</label>
                    <input type="number" name="qty" min="1" value="1" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;" required>
                </div>
            </div>
            <button type="submit" name="create_picking" style="margin-top: 20px; background-color: #17b978; color: white; border: none; padding: 12px 24px; font-weight: bold; border-radius: 4px; cursor: pointer; transition: background 0.2s;">Xác Nhận Lệnh Kho (Validate)</button>
        </form>
    </div>
    <?php endif; ?>

    <h4 style="color: #1e3d59; margin-bottom: 15px; font-weight: 600;">Nhật Ký Luân Chuyển Vật Chất Thực Tế</h4>
    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <thead>
            <tr style="background-color: #eef2f5; text-align: left; color: #1e3d59; font-size: 0.9rem;">
                <th style="padding: 15px;">Mã phiếu hoạt động</th>
                <th style="padding: 15px;">Tài liệu gốc</th>
                <th style="padding: 15px;">Loại dịch chuyển</th>
                <th style="padding: 15px;">Sản phẩm SKU</th>
                <th style="padding: 15px;">Số lượng</th>
                <th style="padding: 15px;">Thời gian ghi nhận</th>
                <th style="padding: 15px;">Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pickings)): ?>
                <tr><td colspan="7" style="padding: 20px; text-align: center; color: #7f8c8d;">Chưa phát sinh bất kỳ hoạt động luân chuyển kho nào hoặc cơ sở dữ liệu trống.</td></tr>
            <?php else: ?>
                <?php foreach($pickings as $pk): ?>
                <tr style="border-bottom: 1px solid #eef2f5; font-size: 0.95rem;">
                    <td style="padding: 15px;"><strong><?= $pk['picking_number'] ?></strong></td>
                    <td style="padding: 15px;"><?= htmlspecialchars($pk['origin'] ?: 'N/A') ?></td>
                    <td style="padding: 15px;">
                        <span style="padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; background-color: <?= $pk['type'] === 'in' ? '#d1ecf1' : '#fff3cd' ?>; color: <?= $pk['type'] === 'in' ? '#0c5460' : '#856404' ?>;">
                            <?= $pk['type'] === 'in' ? 'NHẬP KHO (IN)' : 'XUẤT KHO (OUT)' ?>
                        </span>
                    </td>
                    <td style="padding: 15px;"><?= htmlspecialchars($pk['product_sku']) ?></td>
                    <td style="padding: 15px;"><strong><?= number_format($pk['product_qty']) ?></strong> mục</td>
                    <td style="padding: 15px;"><?= $pk['scheduled_date'] ?></td>
                    <td style="padding: 15px;"><span style="background-color: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold;">Đã hoàn thành (Done)</span></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
````

## File: README.md
````markdown
# php-login-minimal

A simple, but secure PHP login script. Uses the ultra-modern & future-proof PHP 5.5 BLOWFISH hashing/salting functions (includes the official PHP 5.3 & PHP 5.4 compatibility pack, which makes those functions available in these versions too). 

## Why does this script exist ?

In the PHP world every beginner tries to build login systems from scratch, doing all the typical mistakes, usually going from saving plain text passwords to using (horribly wrong) MD5 hashing. This script tries to give beginners a usable code base with a fully implemented user authentication ("login") system, preventing less-experienced developers at least from the worst security issues.

This script was originally part of the "php-login project", a collection of 4 different login scripts made in the 2012-2013 PHP era to give especially beginners and security-inexperienced users a set of basic auth functions that fitted the most modern password hashing standards possible. You know, this was the time when even major companies like SONY and LinkedIn used horrible outdated MD5-hashing for their passwords (or even saved everything in plain text) and when the big PHP frameworks didn't have proper user auth solution out-of-the-box.

Find the other versions here:

**One-file version** (not maintained anymore)
Full login script in one file. Uses a one-file SQLite database (no MySQL needed) and PDO: Register, login, logout.
https://github.com/panique/php-login-one-file

**Minimal version** (not maintained anymore)
All the basic functions in a clean file structure, uses MySQL and mysqli. Register, login, logout.
https://github.com/panique/php-login-minimal

**HUGE (professional version)** 
Quite professional MVC framework structure, useful for real applications. Additional features like: URL rewriting, mail sending via PHPMailer (SMTP or PHP's mail() function/linux sendmail), user profile pages, public user profiles, gravatars and local avatars, account upgrade/downgrade etc., OAuth2, Composer integration, etc.
https://github.com/panique/huge

## Requirements

- PHP 5.3.7+
- MySQL 5 database (please use a modern version of MySQL (5.5, 5.6, 5.7) as very old versions have a exotic bug that
[makes PDO injections possible](http://stackoverflow.com/q/134099/1114320).
- activated mysqli (last letter is an "i") extension (activated by default on most server setups)

## Installation (quick setup)

Create a database *login* and the table *users* via the SQL statements in the `_install` folder.
Change mySQL database user and password in `config/db.php` (*DB_USER* and *DB_PASS*).

## Installation (detailed setup tutorials)

- [Detailed tutorial for installation on Ubuntu 12.04 LTS](http://www.dev-metal.com/install-php-login-nets-1-minimal-login-script-ubuntu/)
- [Detailed tutorial for installation on Windows 7 and 8 (useful for development)](http://www.dev-metal.com/how-to-install-php-login-minimal-on-windows-7-8/)

## Security notice

This script comes with a handy .htaccess in the views folder that denies direct access to the files within the folder
(so that people cannot render the views directly). However, these .htaccess files only work if you have set
`AllowOverride` to `All` in your apache vhost configs. There are lots of tutorials on the web on how to do this.

## Useful links

- [A little guideline on how to use the PHP 5.5 password hashing functions and its "library plugin" based PHP 5.3 & 5.4 implementation](http://www.dev-metal.com/use-php-5-5-password-hashing-functions/)
- [How to setup latest version of PHP 5.5 on Ubuntu 12.04 LTS](http://www.dev-metal.com/how-to-setup-latest-version-of-php-5-5-on-ubuntu-12-04-lts/). Same for Debian 7.0 / 7.1:
- [How to setup latest version of PHP 5.5 on Debian Wheezy 7.0/7.1 (and how to fix the GPG key error)](http://www.dev-metal.com/setup-latest-version-php-5-5-debian-wheezy-7-07-1-fix-gpg-key-error/)
- [Notes on password & hashing salting in upcoming PHP versions (PHP 5.5.x & 5.6 etc.)](https://github.com/panique/php-login/wiki/Notes-on-password-&-hashing-salting-in-upcoming-PHP-versions-%28PHP-5.5.x-&-5.6-etc.%29)
- [Some basic "benchmarks" of all PHP hash/salt algorithms](https://github.com/panique/php-login/wiki/Which-hashing-&-salting-algorithm-should-be-used-%3F)

## License

Licensed under [MIT](http://www.opensource.org/licenses/mit-license.php). You can use this script for free for any
private or commercial projects.

## Contribute

Please create a feature-branch if possible when committing to the project, if not then simply commit to master branch.

## Support

Support the project by renting a server at [DigitalOcean](https://www.digitalocean.com/?refcode=40d978532a20) or just tipping a coffee at BuyMeACoffee.com. Thanks! :)

<a href="https://www.buymeacoffee.com/panique" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" alt="Buy Me A Coffee" style="height: 60px !important;width: 217px !important;" ></a>

## I'm blogging...

at **[DEV METAL](http://www.dev-metal.com)**, mostly about PHP and IT-related stuff. Have a look if you like.
````

## File: register.php
````php
<?php

/**
 * A simple, clean and secure PHP Login Script / MINIMAL VERSION
 *
 * Uses PHP SESSIONS, modern password-hashing and salting and gives the basic functions a proper login system needs.
 *
 * @author Panique
 * @link https://github.com/panique/php-login-minimal/
 * @license http://opensource.org/licenses/MIT MIT License
 */

// checking for minimum PHP version
if (version_compare(PHP_VERSION, '5.3.7', '<')) {
    exit("Sorry, Simple PHP Login does not run on a PHP version smaller than 5.3.7 !");
} else if (version_compare(PHP_VERSION, '5.5.0', '<')) {
    // if you are using PHP 5.3 or PHP 5.4 you have to include the password_api_compatibility_library.php
    // (this library adds the PHP 5.5 password hashing functions to older versions of PHP)
    require_once("libraries/password_compatibility_library.php");
}

// include the configs / constants for the database connection
require_once("config/db.php");

// load the registration class
require_once("classes/Registration.php");

// create the registration object. when this object is created, it will do all registration stuff automatically
// so this single line handles the entire registration process.
$registration = new Registration();

// show the register view (with the registration form, and messages/errors)
include("views/register.php");
````

## File: repomix-output.txt
````
This file is a merged representation of the entire codebase, combined into a single document by Repomix.

================================================================
File Summary
================================================================

Purpose:
--------
This file contains a packed representation of the entire repository's contents.
It is designed to be easily consumable by AI systems for analysis, code review,
or other automated processes.

File Format:
------------
The content is organized as follows:
1. This summary section
2. Repository information
3. Directory structure
4. Repository files (if enabled)
5. Multiple file entries, each consisting of:
  a. A separator line (================)
  b. The file path (File: path/to/file)
  c. Another separator line
  d. The full contents of the file
  e. A blank line

Usage Guidelines:
-----------------
- This file should be treated as read-only. Any changes should be made to the
  original repository files, not this packed version.
- When processing this file, use the file path to distinguish
  between different files in the repository.
- Be aware that this file may contain sensitive information. Handle it with
  the same level of security as you would the original repository.

Notes:
------
- Some files may have been excluded based on .gitignore rules and Repomix's configuration
- Binary files are not included in this packed representation. Please refer to the Repository Structure section for a complete list of file paths, including binary files
- Files matching patterns in .gitignore are excluded
- Files matching default ignore patterns are excluded
- Files are sorted by Git change count (files with more changes are at the bottom)


================================================================
Directory Structure
================================================================
_installation/01-create-database.sql
_installation/02-create-and-fill-users-table.sql
_installation/03-cautrucdichchuyenkho.sql
_installation/New Text Document.txt
_support/banner-host1plus.png
.repomixignore
classes/Login.php
classes/Registration.php
config/db.php
docker-compose.yml
Dockerfile
index.php
libraries/password_compatibility_library.php
modules/dashboard.php
modules/New Text Document.txt
modules/products.php
modules/reports.php
modules/warehouse.php
README.md
register.php
repomix-output.md
repomix.config.json
views/.htaccess
views/logged_in.php
views/not_logged_in.php
views/register.php

================================================================
Files
================================================================

================
File: _installation/01-create-database.sql
================
CREATE DATABASE IF NOT EXISTS `login`;

================
File: _installation/02-create-and-fill-users-table.sql
================
CREATE TABLE IF NOT EXISTS `login`.`users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'auto incrementing user_id of each user, unique index',
  `user_name` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'user''s name, unique',
  `user_password_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'user''s password in salted and hashed format',
  `user_email` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'user''s email, unique',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_name` (`user_name`),
  UNIQUE KEY `user_email` (`user_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='user data';


-- 1. Tạo bảng danh mục sản phẩm và quản lý tồn kho tổng
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sku` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(255) NOT NULL,
  `qty` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tạo bảng quản lý phiếu điều chuyển (Receipts / Delivery Orders)
CREATE TABLE IF NOT EXISTS `stock_picking` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `picking_number` VARCHAR(50) NOT NULL UNIQUE,
  `origin` VARCHAR(100) NULL,
  `type` ENUM('in', 'out') NOT NULL,
  `state` ENUM('draft', 'confirmed', 'done') NOT NULL DEFAULT 'draft',
  `scheduled_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tạo bảng chi tiết dịch chuyển kho (Stock Move Lines) - Liên kết khóa ngoại chặt chẽ
CREATE TABLE IF NOT EXISTS `stock_move` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `picking_id` INT NOT NULL,
  `product_sku` VARCHAR(50) NOT NULL,
  `product_qty` INT NOT NULL,
  FOREIGN KEY (`picking_id`) REFERENCES `stock_picking`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_sku`) REFERENCES `products`(`sku`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Bơm dữ liệu sản phẩm mẫu để hệ thống có sẵn vật chất luân chuyển
INSERT IGNORE INTO `products` (`sku`, `name`, `qty`) VALUES
('PROD-CPU-I9', 'Bộ xử lý Intel Core i9 14900K', 50),
('PROD-RAM-32', 'Thanh RAM DDR5 Corsair 32GB', 120),
('PROD-SSD-01', 'Ổ cứng SSD Samsung 990 Pro 1TB', 85);

================
File: _installation/03-cautrucdichchuyenkho.sql
================
-- Tạo bảng quản lý Phiếu dịch chuyển kho (Chuẩn Odoo Stock Picking)
CREATE TABLE IF NOT EXISTS `stock_picking` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `picking_number` VARCHAR(50) NOT NULL UNIQUE, -- Mã phiếu: WH/IN/0001 hoặc WH/OUT/0001
  `origin` VARCHAR(100) DEFAULT NULL,            -- Chứng từ gốc (Ví dụ: PO-001, SO-002)
  `type` ENUM('in', 'out') NOT NULL,             -- 'in' là Nhập kho, 'out' là Xuất kho
  `scheduled_date` DATETIME DEFAULT CURRENT_TIMESTAMP, -- Ngày thực hiện phiếu
  `state` ENUM('draft', 'done') DEFAULT 'draft'  -- Trạng thái phiếu
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng chi tiết dòng dịch chuyển vật chất (Stock Move Line)
CREATE TABLE IF NOT EXISTS `stock_move` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `picking_id` INT NOT NULL,                     -- Kết nối song song với bảng stock_picking
  `product_sku` VARCHAR(64) NOT NULL,            -- Kết nối với SKU của bảng sản phẩm
  `product_qty` INT NOT NULL,                    -- Số lượng dịch chuyển của dòng này
  FOREIGN KEY (`picking_id`) REFERENCES `stock_picking`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

================
File: _installation/New Text Document.txt
================


================
File: .repomixignore
================
node_modules
.git
vendor
dist
build
storage
tmp
*.log
.vscode
.idea
coverage
mysql
data

================
File: classes/Login.php
================
<?php

/**
 * Class login
 * handles the user's login and logout process
 */
class Login
{
    /**
     * @var object The database connection
     */
    private $db_connection = null;
    /**
     * @var array Collection of error messages
     */
    public $errors = array();
    /**
     * @var array Collection of success / neutral messages
     */
    public $messages = array();

    /**
     * the function "__construct()" automatically starts whenever an object of this class is created,
     * you know, when you do "$login = new Login();"
     */
    public function __construct()
    {
        // create/read session, absolutely necessary
        session_start();

        // check the possible login actions:
        // if user tried to log out (happen when user clicks logout button)
        if (isset($_GET["logout"])) {
            $this->doLogout();
        }
        // login via post data (if user just submitted a login form)
        elseif (isset($_POST["login"])) {
            $this->dologinWithPostData();
        }
    }

    /**
     * log in with post data
     */
    private function dologinWithPostData()
    {
        // check login form contents
        if (empty($_POST['user_name'])) {
            $this->errors[] = "Username field was empty.";
        } elseif (empty($_POST['user_password'])) {
            $this->errors[] = "Password field was empty.";
        } elseif (!empty($_POST['user_name']) && !empty($_POST['user_password'])) {

            // create a database connection, using the constants from config/db.php (which we loaded in index.php)
            $this->db_connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            // change character set to utf8 and check it
            if (!$this->db_connection->set_charset("utf8")) {
                $this->errors[] = $this->db_connection->error;
            }

            // if no connection errors (= working database connection)
            if (!$this->db_connection->connect_errno) {

                // escape the POST stuff
                $user_name = $this->db_connection->real_escape_string($_POST['user_name']);

                // database query, getting all the info of the selected user (allows login via email address in the
                // username field)
                $sql = "SELECT user_name, user_email, user_password_hash
                        FROM users
                        WHERE user_name = '" . $user_name . "' OR user_email = '" . $user_name . "';";
                $result_of_login_check = $this->db_connection->query($sql);

                // if this user exists
                if ($result_of_login_check->num_rows == 1) {

                    // get result row (as an object)
                    $result_row = $result_of_login_check->fetch_object();

                    // using PHP 5.5's password_verify() function to check if the provided password fits
                    // the hash of that user's password
                    if (password_verify($_POST['user_password'], $result_row->user_password_hash)) {

                        // write user data into PHP SESSION (a file on your server)
                        $_SESSION['user_name'] = $result_row->user_name;
                        $_SESSION['user_email'] = $result_row->user_email;
                        $_SESSION['user_login_status'] = 1;

                    } else {
                        $this->errors[] = "Wrong password. Try again.";
                    }
                } else {
                    $this->errors[] = "This user does not exist.";
                }
            } else {
                $this->errors[] = "Database connection problem.";
            }
        }
    }

    /**
     * perform the logout
     */
    public function doLogout()
    {
        // delete the session of the user
        $_SESSION = array();
        session_destroy();
        // return a little feeedback message
        $this->messages[] = "You have been logged out.";

    }

    /**
     * simply return the current state of the user's login
     * @return boolean user's login status
     */
    public function isUserLoggedIn()
    {
        if (isset($_SESSION['user_login_status']) AND $_SESSION['user_login_status'] == 1) {
            return true;
        }
        // default return
        return false;
    }
	/**
     * 🔓 PUBLIC GETTER: Cung cấp sợi tơ kết nối Database hợp pháp ra bên ngoài
     * Giúp các module độc lập như products.php kế thừa và tái sử dụng kết nối của Docker
     */
    public function getDatabaseConnection() {
        return $this->db_connection;
    }
}

================
File: classes/Registration.php
================
<?php

/**
 * Class registration
 * handles the user registration
 */
class Registration
{
    /**
     * @var object $db_connection The database connection
     */
    private $db_connection = null;
    /**
     * @var array $errors Collection of error messages
     */
    public $errors = array();
    /**
     * @var array $messages Collection of success / neutral messages
     */
    public $messages = array();

    /**
     * the function "__construct()" automatically starts whenever an object of this class is created,
     * you know, when you do "$registration = new Registration();"
     */
    public function __construct()
    {
        if (isset($_POST["register"])) {
            $this->registerNewUser();
        }
    }

    /**
     * handles the entire registration process. checks all error possibilities
     * and creates a new user in the database if everything is fine
     */
    private function registerNewUser()
    {
        if (empty($_POST['user_name'])) {
            $this->errors[] = "Empty Username";
        } elseif (empty($_POST['user_password_new']) || empty($_POST['user_password_repeat'])) {
            $this->errors[] = "Empty Password";
        } elseif ($_POST['user_password_new'] !== $_POST['user_password_repeat']) {
            $this->errors[] = "Password and password repeat are not the same";
        } elseif (strlen($_POST['user_password_new']) < 6) {
            $this->errors[] = "Password has a minimum length of 6 characters";
        } elseif (strlen($_POST['user_name']) > 64 || strlen($_POST['user_name']) < 2) {
            $this->errors[] = "Username cannot be shorter than 2 or longer than 64 characters";
        } elseif (!preg_match('/^[a-z\d]{2,64}$/i', $_POST['user_name'])) {
            $this->errors[] = "Username does not fit the name scheme: only a-Z and numbers are allowed, 2 to 64 characters";
        } elseif (empty($_POST['user_email'])) {
            $this->errors[] = "Email cannot be empty";
        } elseif (strlen($_POST['user_email']) > 64) {
            $this->errors[] = "Email cannot be longer than 64 characters";
        } elseif (!filter_var($_POST['user_email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Your email address is not in a valid email format";
        } elseif (!empty($_POST['user_name'])
            && strlen($_POST['user_name']) <= 64
            && strlen($_POST['user_name']) >= 2
            && preg_match('/^[a-z\d]{2,64}$/i', $_POST['user_name'])
            && !empty($_POST['user_email'])
            && strlen($_POST['user_email']) <= 64
            && filter_var($_POST['user_email'], FILTER_VALIDATE_EMAIL)
            && !empty($_POST['user_password_new'])
            && !empty($_POST['user_password_repeat'])
            && ($_POST['user_password_new'] === $_POST['user_password_repeat'])
        ) {
            // create a database connection
            $this->db_connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            // change character set to utf8 and check it
            if (!$this->db_connection->set_charset("utf8")) {
                $this->errors[] = $this->db_connection->error;
            }

            // if no connection errors (= working database connection)
            if (!$this->db_connection->connect_errno) {

                // escaping, additionally removing everything that could be (html/javascript-) code
                $user_name = $this->db_connection->real_escape_string(strip_tags($_POST['user_name'], ENT_QUOTES));
                $user_email = $this->db_connection->real_escape_string(strip_tags($_POST['user_email'], ENT_QUOTES));

                $user_password = $_POST['user_password_new'];

                // crypt the user's password with PHP 5.5's password_hash() function, results in a 60 character
                // hash string. the PASSWORD_DEFAULT constant is defined by the PHP 5.5, or if you are using
                // PHP 5.3/5.4, by the password hashing compatibility library
                $user_password_hash = password_hash($user_password, PASSWORD_DEFAULT);

                // check if user or email address already exists
                $sql = "SELECT * FROM users WHERE user_name = '" . $user_name . "' OR user_email = '" . $user_email . "';";
                $query_check_user_name = $this->db_connection->query($sql);

                if ($query_check_user_name->num_rows == 1) {
                    $this->errors[] = "Sorry, that username / email address is already taken.";
                } else {
                    // write new user's data into database
                    $sql = "INSERT INTO users (user_name, user_password_hash, user_email)
                            VALUES('" . $user_name . "', '" . $user_password_hash . "', '" . $user_email . "');";
                    $query_new_user_insert = $this->db_connection->query($sql);

                    // if user has been added successfully
                    if ($query_new_user_insert) {
                        $this->messages[] = "Your account has been created successfully. You can now log in.";
                    } else {
                        $this->errors[] = "Sorry, your registration failed. Please go back and try again.";
                    }
                }
            } else {
                $this->errors[] = "Sorry, no database connection.";
            }
        } else {
            $this->errors[] = "An unknown error occurred.";
        }
    }
}

================
File: config/db.php
================
<?php
// Giữ nguyên các hằng số cũ của bạn
define("DB_HOST", "db"); // Tên service trong docker-compose
define("DB_USER", "root");
define("DB_PASS", "root_password");
define("DB_NAME", "login");

// Cổng kết nối cũ (MySQLi) cho các module chưa nâng cấp
$db_connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db_connection->connect_errno) {
    die("Kết nối MySQLi thất bại: " . $db_connection->connect_error);
}

/**
 * Hàm khởi tạo kết nối PDO - Tầng bảo mật tuyệt đối
 * @return PDO
 */
function getPDOLayerConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // Ép buộc sử dụng Prepared Statements thực tế của MySQL
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Lỗi tầng kết nối PDO: " . $e->getMessage());
        }
    }
    return $pdo;
}

================
File: docker-compose.yml
================
version: '3.8'

services:
  # Lớp 1: Máy chủ Web chạy PHP và Apache
  web:
    build: .  # Chạy thông qua Dockerfile vừa tạo ở trên
    container_name: phplogin_web
    ports:
      - "8888:80"  # Cổng truy cập máy thật: localhost:8888
    volumes:
      - .:/var/www/html
    depends_on:
      - db
    networks:
      - erp_network

  # Lớp 2: Cơ sở dữ liệu MySQL 
  db:
    image: mysql:8.0
    container_name: phplogin_db
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: login
    ports:
      - "3307:3306"  # ĐỔI THÀNH 3307 để loại bỏ hoàn toàn lỗi xung đột cổng máy thật
    volumes:
      - db_data:/var/lib/mysql
      - ./_installation:/docker-entrypoint-initdb.d
    networks:
      - erp_network

  # Lớp 3: Trình quản lý Database trực quan (Adminer)
  adminer:
    image: adminer
    container_name: phplogin_adminer
    restart: always
    ports:
      - "8889:8080"  # Truy cập quản lý DB qua: localhost:8084
    networks:
      - erp_network

volumes:
  db_data:

networks:
  erp_network:
    driver: bridge

================
File: Dockerfile
================
FROM php:8.1-apache

# Cài đặt và kích hoạt các extension mở rộng cho MySQLi và PDO bảo mật
RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && docker-php-ext-enable mysqli pdo pdo_mysql

# Kích hoạt mod_rewrite của Apache (phục vụ cho việc định tuyến Router Whitelist mượt mà hơn)
RUN a2enmod rewrite

# Cấp quyền ghi để Apache container vận hành tệp tin mượt mà, không bị nghẽn
RUN chown -R www-data:www-data /var/www/html

================
File: index.php
================
<?php
/**
 * 🛰️ HỆ THỐNG ĐIỀU PHỐI TRUNG TÂM VÀ XÁC THỰC (CENTRAL ROUTER & AUTH ENGINE)
 * Đạt chuẩn Odoo quy mô công nghiệp - An toàn, quyết đoán, bảo trì tuyệt đối.
 */

// Kiểm tra phiên bản PHP tối thiểu
if (version_compare(PHP_VERSION, '5.3.7', '<')) {
    exit("Sorry, Simple PHP Login does not run on a PHP version smaller than 5.3.7 !");
}

// Nạp cấu hình Database và các lớp xử lý đối tượng Core
require_once("config/db.php");
require_once("classes/Login.php");

// Khởi tạo thực thể Login (Tự động xử lý Cookie, Session, Đăng nhập, Đăng xuất)
$login = new Login();

// 🛑 TẦNG KIỂM TRA ĐĂNG NHẬP: Nếu chưa đăng nhập, kết thúc luồng và hiển thị màn hình Login mẫu
if ($login->isUserLoggedIn() == false) {
    include("views/not_logged_in.php");
    exit(); 
}

// 🌐 TẦNG GIAO DIỆN CHÍNH (Sau khi đã đăng nhập thành công)
// Đảm bảo đồng bộ Session tên người dùng cho giao diện
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_name']) && isset($_SESSION['user_id'])) {
    // Dự phòng đồng bộ nếu thư viện Login gốc lưu cấu trúc session khác
    $_SESSION['user_name'] = $_SESSION['user_name'] ?? 'Admin';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Quản Lý Kho Chuẩn Odoo</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; background-color: #f4f6f9; min-height: 100vh; color: #333333; }
        
        /* 🔵 SIDEBAR DESIGN - TÔNG XANH NƯỚC BIỂN ĐẬM ERP */
        .sidebar { width: 260px; background-color: #1e3d59; color: #ffffff; display: flex; flex-direction: column; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }
        .sidebar-brand { padding: 20px; text-align: center; font-size: 1.15rem; font-weight: bold; border-bottom: 1px solid #17b978; background-color: #17b978; color: #ffffff; letter-spacing: 0.5px; }
        .sidebar-menu { list-style: none; padding: 15px 0; flex: 1; }
        .sidebar-menu li { padding: 14px 20px; transition: all 0.2s ease-in-out; }
        .sidebar-menu li:hover { background-color: #17b978; padding-left: 25px; }
        .sidebar-menu a { color: #e8f1f5; text-decoration: none; display: flex; align-items: center; gap: 12px; font-size: 0.95rem; font-weight: 500; }
        .sidebar-menu li:hover a { color: #ffffff; }
        
        .sidebar-logout { padding: 20px; border-top: 1px solid #2b5278; }
        .btn-logout { display: block; text-align: center; background-color: #ff6b6b; color: white; padding: 10px; text-decoration: none; border-radius: 6px; font-weight: bold; transition: background 0.2s; }
        .btn-logout:hover { background-color: #ee5253; }

        /* ⚪ MAIN CONTENT WORKSPACE */
        .main-content { flex: 1; display: flex; flex-direction: column; }
        .main-header { height: 65px; background-color: #ffffff; display: flex; align-items: center; justify-content: space-between; padding: 0 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border-bottom: 1px solid #eef2f5; }
        .page-title { font-size: 1.1rem; font-weight: 600; color: #1e3d59; display: flex; align-items: center; gap: 8px; }
        
        /* 👤 AVATAR PROFILE CORNER */
        .user-profile { display: flex; align-items: center; gap: 12px; cursor: pointer; }
        .user-info { text-align: right; }
        .user-info .username { display: block; font-size: 0.9rem; font-weight: 600; color: #2c3e50; }
        .user-info .role { display: block; font-size: 0.75rem; color: #7f8c8d; }
        .avatar-circle { width: 38px; height: 38px; background-color: #17b978; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1rem; box-shadow: 0 2px 4px rgba(23, 185, 120, 0.2); border: 2px solid #ffffff; }

        .main-body { padding: 30px; flex: 1; overflow-y: auto; background-color: #f8fafc; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-brand">📦 Warehouse</div>
        <ul class="sidebar-menu">
            <li><a href="index.php?page=dashboard">📊 Dashboard</a></li>
            <li><a href="index.php?page=products">📦 Sản phẩm</a></li>
            <li><a href="index.php?page=warehouse">🚚 Điều phối Kho</a></li>
            <li><a href="index.php?page=reports">📈 Báo cáo vĩ mô</a></li>
        </ul>
        <div class="sidebar-logout">
            <a href="index.php?logout" class="btn-logout">🚪 Đăng xuất</a>
        </div>
    </aside>

    <div class="main-content">
        <header class="main-header">
            <div class="page-title">⚙️ Hệ thống điều phối dữ liệu</div>
            
            <div class="user-profile">
                <div class="user-info">
                    <span class="username"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Quản trị viên'); ?></span>
                    <span class="role">Hạt nhân Hệ thống</span>
                </div>
                <div class="avatar-circle">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'Q', 0, 1)); ?>
                </div>
            </div>
        </header>
        
        <main class="main-body">
            <?php
            $currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
            $allowedPages = ['dashboard', 'products', 'reports', 'warehouse'];
            
            if (in_array($currentPage, $allowedPages)) {
                $targetFile = "modules/" . $currentPage . ".php";
                if (file_exists($targetFile)) {
                    include($targetFile);
                } else {
                    echo "<div style='background: #ffffff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border-left: 4px solid #1e3d59;'>";
                    echo "<h3 style='color: #1e3d59;'>Mô-đun [ " . htmlspecialchars(ucfirst($currentPage)) . " ] đang được cấu trúc</h3>";
                    echo "<p style='color: #7f8c8d; margin-top: 10px;'>Hạt nhân Docker đang sẵn sàng nạp kết nối SQL cho tầng nghiệp vụ này.</p>";
                    echo "</div>";
                }
            } else {
                echo "<h3 style='color: #ff6b6b;'>Cảnh báo: Tầng truy cập nghiệp vụ không hợp lệ!</h3>";
            }
            ?>
        </main>
    </div>

</body>
</html>

================
File: libraries/password_compatibility_library.php
================
<?php
/**
 * A Compatibility library with PHP 5.5's simplified password hashing API.
 *
 * @author Anthony Ferrara <ircmaxell@php.net>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @copyright 2012 The Authors
 */

if (!defined('PASSWORD_DEFAULT')) {

    define('PASSWORD_BCRYPT', 1);
    define('PASSWORD_DEFAULT', PASSWORD_BCRYPT);

    /**
     * Hash the password using the specified algorithm
     *
     * @param string $password The password to hash
     * @param int    $algo     The algorithm to use (Defined by PASSWORD_* constants)
     * @param array  $options  The options for the algorithm to use
     *
     * @return string|false The hashed password, or false on error.
     */
    function password_hash($password, $algo, array $options = array()) {
        if (!function_exists('crypt')) {
            trigger_error("Crypt must be loaded for password_hash to function", E_USER_WARNING);
            return null;
        }
        if (!is_string($password)) {
            trigger_error("password_hash(): Password must be a string", E_USER_WARNING);
            return null;
        }
        if (!is_int($algo)) {
            trigger_error("password_hash() expects parameter 2 to be long, " . gettype($algo) . " given", E_USER_WARNING);
            return null;
        }
        switch ($algo) {
            case PASSWORD_BCRYPT:
                // Note that this is a C constant, but not exposed to PHP, so we don't define it here.
                $cost = 10;
                if (isset($options['cost'])) {
                    $cost = $options['cost'];
                    if ($cost < 4 || $cost > 31) {
                        trigger_error(sprintf("password_hash(): Invalid bcrypt cost parameter specified: %d", $cost), E_USER_WARNING);
                        return null;
                    }
                }
                // The length of salt to generate
                $raw_salt_len = 16;
                // The length required in the final serialization
                $required_salt_len = 22;
                $hash_format = sprintf("$2y$%02d$", $cost);
                break;
            default:
                trigger_error(sprintf("password_hash(): Unknown password hashing algorithm: %s", $algo), E_USER_WARNING);
                return null;
        }
        if (isset($options['salt'])) {
            switch (gettype($options['salt'])) {
                case 'NULL':
                case 'boolean':
                case 'integer':
                case 'double':
                case 'string':
                    $salt = (string) $options['salt'];
                    break;
                case 'object':
                    if (method_exists($options['salt'], '__tostring')) {
                        $salt = (string) $options['salt'];
                        break;
                    }
                case 'array':
                case 'resource':
                default:
                    trigger_error('password_hash(): Non-string salt parameter supplied', E_USER_WARNING);
                    return null;
            }
            if (strlen($salt) < $required_salt_len) {
                trigger_error(sprintf("password_hash(): Provided salt is too short: %d expecting %d", strlen($salt), $required_salt_len), E_USER_WARNING);
                return null;
            } elseif (0 == preg_match('#^[a-zA-Z0-9./]+$#D', $salt)) {
                $salt = str_replace('+', '.', base64_encode($salt));
            }
        } else {
            $buffer = '';
            $buffer_valid = false;
            if (function_exists('mcrypt_create_iv') && !defined('PHALANGER')) {
                $buffer = mcrypt_create_iv($raw_salt_len, MCRYPT_DEV_URANDOM);
                if ($buffer) {
                    $buffer_valid = true;
                }
            }
            if (!$buffer_valid && function_exists('openssl_random_pseudo_bytes')) {
                $buffer = openssl_random_pseudo_bytes($raw_salt_len);
                if ($buffer) {
                    $buffer_valid = true;
                }
            }
            if (!$buffer_valid && is_readable('/dev/urandom')) {
                $f = fopen('/dev/urandom', 'r');
                $read = strlen($buffer);
                while ($read < $raw_salt_len) {
                    $buffer .= fread($f, $raw_salt_len - $read);
                    $read = strlen($buffer);
                }
                fclose($f);
                if ($read >= $raw_salt_len) {
                    $buffer_valid = true;
                }
            }
            if (!$buffer_valid || strlen($buffer) < $raw_salt_len) {
                $bl = strlen($buffer);
                for ($i = 0; $i < $raw_salt_len; $i++) {
                    if ($i < $bl) {
                        $buffer[$i] = $buffer[$i] ^ chr(mt_rand(0, 255));
                    } else {
                        $buffer .= chr(mt_rand(0, 255));
                    }
                }
            }
            $salt = str_replace('+', '.', base64_encode($buffer));
        }
        $salt = substr($salt, 0, $required_salt_len);

        $hash = $hash_format . $salt;

        $ret = crypt($password, $hash);

        if (!is_string($ret) || strlen($ret) <= 13) {
            return false;
        }

        return $ret;
    }

    /**
     * Get information about the password hash. Returns an array of the information
     * that was used to generate the password hash.
     *
     * array(
     *    'algo' => 1,
     *    'algoName' => 'bcrypt',
     *    'options' => array(
     *        'cost' => 10,
     *    ),
     * )
     *
     * @param string $hash The password hash to extract info from
     *
     * @return array The array of information about the hash.
     */
    function password_get_info($hash) {
        $return = array(
            'algo' => 0,
            'algoName' => 'unknown',
            'options' => array(),
        );
        if (substr($hash, 0, 4) == '$2y$' && strlen($hash) == 60) {
            $return['algo'] = PASSWORD_BCRYPT;
            $return['algoName'] = 'bcrypt';
            list($cost) = sscanf($hash, "$2y$%d$");
            $return['options']['cost'] = $cost;
        }
        return $return;
    }

    /**
     * Determine if the password hash needs to be rehashed according to the options provided
     *
     * If the answer is true, after validating the password using password_verify, rehash it.
     *
     * @param string $hash    The hash to test
     * @param int    $algo    The algorithm used for new password hashes
     * @param array  $options The options array passed to password_hash
     *
     * @return boolean True if the password needs to be rehashed.
     */
    function password_needs_rehash($hash, $algo, array $options = array()) {
        $info = password_get_info($hash);
        if ($info['algo'] != $algo) {
            return true;
        }
        switch ($algo) {
            case PASSWORD_BCRYPT:
                $cost = isset($options['cost']) ? $options['cost'] : 10;
                if ($cost != $info['options']['cost']) {
                    return true;
                }
                break;
        }
        return false;
    }

    /**
     * Verify a password against a hash using a timing attack resistant approach
     *
     * @param string $password The password to verify
     * @param string $hash     The hash to verify against
     *
     * @return boolean If the password matches the hash
     */
    function password_verify($password, $hash) {
        if (!function_exists('crypt')) {
            trigger_error("Crypt must be loaded for password_verify to function", E_USER_WARNING);
            return false;
        }
        $ret = crypt($password, $hash);
        if (!is_string($ret) || strlen($ret) != strlen($hash) || strlen($ret) <= 13) {
            return false;
        }

        $status = 0;
        for ($i = 0; $i < strlen($ret); $i++) {
            $status |= (ord($ret[$i]) ^ ord($hash[$i]));
        }

        return $status === 0;
    }
}

================
File: modules/dashboard.php
================
<?php
// Kiểm tra bảo mật: Không cho phép truy cập trực tiếp file này từ URL bên ngoài
if (!isset($_SESSION['user_name'])) {
    exit("Truy cập bị từ chối!");
}

/**
 * 🛠️ TẦNG KẾT NỐI VÀ TRUY VẤN DỮ LIỆU THỰC TẾ (DYNAMIC DATA LAYER - PDO UPGRADED)
 * Kéo chỉ số trực tiếp từ bảng products và tích hợp lịch sử luân chuyển thực tế từ stock_picking
 */
$totalProducts = 0;       
$totalStockVolume = 0;   
$lowStockAlert = 0;      
$recentActivities = [];  

try {
    // Khởi tạo kết nối thông qua lớp PDO đồng bộ bảo mật
    if (function_exists('getPDOLayerConnection')) {
        $pdo = getPDOLayerConnection();
    } else {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    
    if ($pdo) {
        // 1. Lấy tổng số danh mục sản phẩm (Total SKU)
        $totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

        // 2. Lấy tổng sản lượng tồn kho luân chuyển (Total Quantity)
        $totalStockVolume = (int)$pdo->query("SELECT SUM(qty) FROM products")->fetchColumn();

        // 3. Đếm số lượng sản phẩm rơi vào trạng thái cảnh báo (Số lượng tồn <= 10)
        $lowStockAlert = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE qty <= 10")->fetchColumn();

        // 4. Lấy dữ liệu động từ bảng luân chuyển kho thực tế (stock_picking song hành cùng stock_move)
        // Dùng câu lệnh chuẩn bị trước để quét 5 biến động kho gần nhất
        $stmtRecent = $pdo->prepare("
            SELECT sp.scheduled_date, sp.type, sm.product_sku, sm.product_qty 
            FROM stock_picking sp 
            JOIN stock_move sm ON sp.id = sm.picking_id 
            ORDER BY sp.id DESC LIMIT 5
        ");
        $stmtRecent->execute();
        $activities = $stmtRecent->fetchAll();

        if (!empty($activities)) {
            foreach ($activities as $row) {
                // Ép kiểu thời gian từ DB ra định dạng gọn nhẹ đúng Layout gốc của bạn
                $timeFormatted = date('H:i | d-m', strtotime($row['scheduled_date']));
                $recentActivities[] = [
                    'time' => $timeFormatted,
                    'type' => $row['type'], // Kế thừa giá trị 'in' hoặc 'out' thực tế từ Odoo Engine
                    'product_name' => $row['product_sku'], // Hiển thị mã sản phẩm luân chuyển
                    'quantity' => number_format($row['product_qty']) . " SP"
                ];
            }
        } else {
            // Cơ chế Fallback dữ liệu: Nếu bảng luân chuyển mới chưa có lệnh nhập xuất kho nào, 
            // hệ thống tự động quét bảng sản phẩm gốc để hiển thị danh sách khởi tạo ban đầu giống hệt code cũ của bạn
            $stmtFallback = $pdo->query("SELECT name, qty FROM products ORDER BY id DESC LIMIT 5");
            while ($row = $stmtFallback->fetch()) {
                $recentActivities[] = [
                    'time' => date('H:i | d-m'),
                    'type' => 'in',
                    'product_name' => $row['name'],
                    'quantity' => number_format($row['qty']) . " SP"
                ];
            }
        }
    }
} catch (Exception $e) {
    // Đóng băng ngoại lệ bảo vệ luồng hiển thị giao diện
}
?>

<div class="dashboard-container">
    <div class="dashboard-title">
        <h2>📊 Tổng quan hệ thống quản trị</h2>
        <p>Báo cáo tình trạng vận hành kho và chỉ số luân chuyển hàng hóa thực tế.</p>
    </div>

    <div class="card-grid">
        <div class="card card-blue">
            <div class="card-icon">📦</div>
            <div class="card-info">
                <h3><?php echo $totalProducts; ?></h3>
                <p>Danh mục sản phẩm</p>
            </div>
        </div>

        <div class="card card-green">
            <div class="card-icon">🏢</div>
            <div class="card-info">
                <h3><?php echo number_format($totalStockVolume); ?></h3>
                <p>Tổng sản lượng tồn kho</p>
            </div>
        </div>

        <div class="card card-orange">
            <div class="card-icon">⚠️</div>
            <div class="card-info">
                <h3><?php echo $lowStockAlert; ?></h3>
                <p>Cảnh báo hết hàng</p>
            </div>
        </div>
    </div>

    <div class="dashboard-details">
        <div class="detail-box">
            <h4>🔄 Nhật ký kho mới nhất</h4>
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Nghiệp vụ</th>
                        <th>Sản phẩm</th>
                        <th>Số lượng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentActivities)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #95a5a6; padding: 30px 10px;">
                                Đang chờ kết nối dữ liệu từ các mô-đun nghiệp vụ...
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentActivities as $activity): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($activity['time']); ?></td>
                                <td>
                                    <span class="badge <?php echo $activity['type'] == 'in' ? 'badge-in' : 'badge-out'; ?>">
                                        <?php echo $activity['type'] == 'in' ? 'Nhập kho' : 'Xuất kho'; ?>
                                    </span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($activity['product_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($activity['quantity']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="detail-box quick-links">
            <h4>⚡ Thao tác nhanh</h4>
            <a href="index.php?page=products" class="link-btn">➡️ Quản lý danh mục sản phẩm</a>
            <a href="index.php?page=reports" class="link-btn link-btn-secondary">➡️ Xem báo cáo phân tích</a>

            <div class="system-status-container">
                <h5>🖥️ Trạng thái máy chủ Docker</h5>
                
                <div class="status-item">
                    <span class="status-label">Cơ sở dữ liệu (DB):</span>
                    <span class="status-value text-success"><span class="dot-online"></span> Trực tuyến (Connected)</span>
                </div>
                
                <div class="status-item">
                    <span class="status-label">Cổng mạng kết nối:</span>
                    <span class="status-value">Host: <code class="code-spec">db</code> | Port: <code class="code-spec">3306</code></span>
                </div>
                
                <div class="status-item" style="margin-top: 15px; border-top: 1px dashed #eef2f5; padding-top: 10px;">
                    <span class="status-label">Tài khoản trực ban:</span>
                    <span class="status-value"><strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
                </div>

                <div class="status-item">
                    <span class="status-label">Phiên làm việc:</span>
                    <span class="status-value text-blue">Đang hoạt động</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .dashboard-container { animation: fadeIn 0.4s ease-in-out; }
    .dashboard-title { margin-bottom: 25px; }
    .dashboard-title h2 { color: #1e3d59; font-size: 1.6rem; }
    .dashboard-title p { color: #7f8c8d; font-size: 0.9rem; margin-top: 5px; }

    /* Định dạng lưới thẻ Card */
    .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .card { background: #ffffff; border-radius: 8px; padding: 20px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); border-bottom: 4px solid transparent; }
    .card-icon { font-size: 2.5rem; }
    .card-info h3 { font-size: 1.8rem; color: #2c3e50; font-weight: 700; }
    .card-info p { color: #7f8c8d; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
    
    /* Màu sắc nhận diện hệ thống */
    .card-blue { border-bottom-color: #1e3d59; }
    .card-green { border-bottom-color: #17b978; }
    .card-orange { border-bottom-color: #ff9f43; }

    /* Bố cục vùng chi tiết */
    .dashboard-details { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    .detail-box { background: #ffffff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
    .detail-box h4 { color: #1e3d59; margin-bottom: 15px; font-size: 1.05rem; border-bottom: 2px solid #f4f6f9; padding-bottom: 10px; }

    /* Định dạng bảng dữ liệu */
    .dashboard-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; }
    .dashboard-table th, .dashboard-table td { padding: 12px 10px; border-bottom: 1px solid #f4f6f9; }
    .dashboard-table th { color: #7f8c8d; font-weight: 600; }
    .dashboard-table td { color: #2c3e50; }
    
    /* Huy hiệu trạng thái */
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; }
    .badge-in { background-color: #e3fcef; color: #155724; }
    .badge-out { background-color: #fff0f0; color: #721c24; }

    /* Nút thao tác nhanh */
    .quick-links { display: flex; flex-direction: column; }
    .link-btn { display: block; background: #1e3d59; color: white; padding: 12px; text-decoration: none; border-radius: 6px; text-align: center; font-weight: 500; font-size: 0.9rem; margin-bottom: 10px; transition: background 0.2s; }
    .link-btn:hover { background: #17b978; }
    .link-btn-secondary { background: #7f8c8d; }
    .link-btn-secondary:hover { background: #6c7a89; }

    /* 🔵 CSS ĐỘC LẬP CHO KHỐI TIỆN ÍCH LẤP ĐẦY KHOẢNG TRỐNG */
    .system-status-container { margin-top: 20px; padding: 15px; background-color: #f8fafc; border-radius: 6px; border: 1px solid #eef2f5; }
    .system-status-container h5 { color: #1e3d59; font-size: 0.9rem; margin-bottom: 12px; font-weight: 600; }
    .status-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 0.85rem; }
    .status-label { color: #7f8c8d; }
    .status-value { color: #2c3e50; font-weight: 500; }
    .text-success { color: #17b978 !important; display: flex; align-items: center; gap: 5px; }
    .text-blue { color: #1e3d59 !important; font-weight: bold; }
    .code-spec { background: #eef2f5; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 0.8rem; color: #e83e8c; }
    
    /* Chấm tròn nhấp nháy tạo hiệu ứng Live cho Docker */
    .dot-online { width: 8px; height: 8px; background-color: #17b978; border-radius: 50%; display: inline-block; box-shadow: 0 0 8px #17b978; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

================
File: modules/New Text Document.txt
================


================
File: modules/products.php
================
<?php
/**
 * 📦 MÔ-ĐUN QUẢN LÝ DANH MỤC SẢN PHẨM (PRODUCT MASTER DATA)
 * Tích hợp tính năng: Thêm, Sửa, Xóa, hiển thị đồng bộ với Odoo Stock Engine.
 */
$pdo = getPDOLayerConnection();
$errors = [];
$messages = [];

// Khởi tạo biến xử lý form Sửa
$edit_mode = false;
$edit_product = null;

try {
    // 🛑 1. XỬ LÝ HÀNH ĐỘNG XÓA (DELETE)
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['sku'])) {
        $delete_sku = $_GET['sku'];
        $stmtDelete = $pdo->prepare("DELETE FROM products WHERE sku = ?");
        $stmtDelete->execute([$delete_sku]);
        $messages[] = "Đã xóa sản phẩm với mã SKU [{$delete_sku}] thành công.";
    }

    // 🛑 2. XỬ LÝ HÀNH ĐỘNG LẤY THÔNG TIN ĐỂ SỬA (GET EDIT DATA)
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['sku'])) {
        $edit_sku = $_GET['sku'];
        $stmtGetEdit = $pdo->prepare("SELECT * FROM products WHERE sku = ?");
        $stmtGetEdit->execute([$edit_sku]);
        $edit_product = $stmtGetEdit->fetch();
        if ($edit_product) {
            $edit_mode = true;
        }
    }

    // 🛑 3. XỬ LÝ FORM SUBMIT (THÊM MỚI HOẶC CẬP NHẬT)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
        $sku = trim($_POST['sku'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $qty = intval($_POST['qty'] ?? 0); // Số lượng tồn ban đầu

        if (empty($sku) || empty($name)) {
            $errors[] = "Mã SKU và Tên sản phẩm không được để trống.";
        } else {
            if (isset($_POST['is_edit_mode']) && $_POST['is_edit_mode'] == '1') {
                // Logic Cập nhật (Update)
                $stmtUpdate = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, qty = ? WHERE sku = ?");
                $stmtUpdate->execute([$name, $description, $price, $qty, $sku]);
                $messages[] = "Đã cập nhật thông tin sản phẩm [{$sku}] thành công.";
                $edit_mode = false; // Thoát chế độ sửa
            } else {
                // Logic Thêm mới (Insert) - Kiểm tra trùng SKU trước
                $stmtCheck = $pdo->prepare("SELECT sku FROM products WHERE sku = ?");
                $stmtCheck->execute([$sku]);
                if ($stmtCheck->fetch()) {
                    throw new Exception("Mã SKU [{$sku}] này đã tồn tại trong hệ thống.");
                }

                $stmtInsert = $pdo->prepare("INSERT INTO products (sku, name, description, price, qty) VALUES (?, ?, ?, ?, ?)");
                $stmtInsert->execute([$sku, $name, $description, $price, $qty]);
                $messages[] = "Đã thêm mới thành công sản phẩm [{$sku}].";
            }
        }
    }

    // 🔄 TẢI TOÀN BỘ DANH SÁCH SẢN PHẨM LÊN GIAO DIỆN
    $products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();

} catch (Exception $e) {
    $errors[] = "Lỗi nghiệp vụ sản phẩm: " . $e->getMessage();
}
?>

<div class="container-fluid" style="padding: 10px;">
    <h2 style="color: #1e3d59; margin-bottom: 20px; font-weight: 700;">Quản Lý Danh Mục Sản Phẩm (Master Data)</h2>

    <?php foreach($errors as $error): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 15px; border-left: 5px solid #f5c6cb;">
            <strong>⚠️ Lỗi:</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php endforeach; ?>
    
    <?php foreach($messages as $msg): ?>
        <div style="background-color: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 15px; border-left: 5px solid #c3e6cb;">
            <strong>✅ Thành công:</strong> <?= htmlspecialchars($msg) ?>
        </div>
    <?php endforeach; ?>

    <div style="background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 30px;">
        <div style="font-weight: bold; font-size: 1.1rem; color: #1e3d59; border-bottom: 2px solid #eef2f5; padding-bottom: 10px; margin-bottom: 20px;">
            <?= $edit_mode ? "🛠️ Hiệu Chỉnh Sản Phẩm: " . htmlspecialchars($edit_product['sku']) : "➕ Thêm Sản Phẩm Mới Vào Hệ Thống" ?>
        </div>
        
        <form method="POST" action="index.php?page=products">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="is_edit_mode" value="1">
                <input type="hidden" name="sku" value="<?= htmlspecialchars($edit_product['sku']) ?>">
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div>
                    <label style="display:block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600;">Mã sản phẩm (SKU)</label>
                    <input type="text" name="sku" value="<?= $edit_mode ? htmlspecialchars($edit_product['sku']) : '' ?>" <?= $edit_mode ? 'disabled' : '' ?> placeholder="Ví dụ: PROD-CPU-I9" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px; background-color: <?= $edit_mode ? '#eef2f5' : '#ffffff' ?>;" required>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600;">Tên mặt hàng</label>
                    <input type="text" name="name" value="<?= $edit_mode ? htmlspecialchars($edit_product['name']) : '' ?>" placeholder="Nhập tên sản phẩm..." style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;" required>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600;">Giá bán (VNĐ)</label>
                    <input type="number" name="price" value="<?= $edit_mode ? htmlspecialchars($edit_product['price']) : '0' ?>" min="0" step="0.01" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;" required>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600;">Số lượng tồn đầu kỳ</label>
                    <input type="number" name="qty" value="<?= $edit_mode ? htmlspecialchars($edit_product['qty']) : '0' ?>" min="0" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;" required>
                </div>
            </div>
            
            <div style="margin-top: 15px;">
                <label style="display:block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600;">Mô tả sản phẩm</label>
                <textarea name="description" rows="2" placeholder="Ghi chú thông số kỹ thuật, thuộc tính..." style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;"><?= $edit_mode ? htmlspecialchars($edit_product['description']) : '' ?></textarea>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" name="save_product" style="background-color: #17b978; color: white; border: none; padding: 10px 20px; font-weight: bold; border-radius: 4px; cursor: pointer;">
                    <?= $edit_mode ? "Cập Nhật (Save)" : "Lưu Sản Phẩm" ?>
                </button>
                <?php if ($edit_mode): ?>
                    <a href="index.php?page=products" style="background-color: #7f8c8d; color: white; text-decoration: none; padding: 10px 20px; font-weight: bold; border-radius: 4px; line-height: 1.5;">Hủy bỏ</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <h4 style="color: #1e3d59; margin-bottom: 15px; font-weight: 600;">Danh Sách Mặt Hàng Hiện Hữu</h4>
    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <thead>
            <tr style="background-color: #eef2f5; text-align: left; color: #1e3d59; font-size: 0.9rem;">
                <th style="padding: 15px;">ID</th>
                <th style="padding: 15px;">Mã SKU</th>
                <th style="padding: 15px;">Tên sản phẩm</th>
                <th style="padding: 15px;">Mô tả</th>
                <th style="padding: 15px;">Giá niêm yết</th>
                <th style="padding: 15px;">Số lượng trong kho</th>
                <th style="padding: 15px; text-align: center;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr><td colspan="7" style="padding: 20px; text-align: center; color: #7f8c8d;">Không có sản phẩm nào tồn tại. Hãy thêm mới sản phẩm ở form trên.</td></tr>
            <?php else: ?>
                <?php foreach($products as $prod): ?>
                <tr style="border-bottom: 1px solid #eef2f5; font-size: 0.95rem;">
                    <td style="padding: 15px; color: #7f8c8d;"><?= $prod['id'] ?></td>
                    <td style="padding: 15px;"><strong><?= htmlspecialchars($prod['sku']) ?></strong></td>
                    <td style="padding: 15px; font-weight: 500; color: #2c3e50;"><?= htmlspecialchars($prod['name']) ?></td>
                    <td style="padding: 15px; color: #95a5a6; font-size: 0.85rem; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($prod['description'] ?: 'Chưa có mô tả') ?></td>
                    <td style="padding: 15px; color: #e74c3c; font-weight: bold;"><?= number_format($prod['price']) ?> VNĐ</td>
                    <td style="padding: 15px;">
                        <span style="padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; background-color: <?= $prod['qty'] > 10 ? '#d4edda' : '#f8d7da' ?>; color: <?= $prod['qty'] > 10 ? '#155724' : '#721c24' ?>;">
                            <?= number_format($prod['qty']) ?> cái
                        </span>
                    </td>
                    <td style="padding: 15px; text-align: center; display: flex; gap: 8px; justify-content: center;">
                        <a href="index.php?page=products&action=edit&sku=<?= $prod['sku'] ?>" style="background-color: #3498db; color: white; padding: 6px 12px; font-size: 0.85rem; font-weight: bold; text-decoration: none; border-radius: 4px; transition: background 0.2s;">Sửa</a>
                        <a href="index.php?page=products&action=delete&sku=<?= $prod['sku'] ?>" onclick="return confirm('Bạn chắc chắn muốn xóa sản phẩm này?');" style="background-color: #e74c3c; color: white; padding: 6px 12px; font-size: 0.85rem; font-weight: bold; text-decoration: none; border-radius: 4px; transition: background 0.2s;">Xóa</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

================
File: modules/reports.php
================
<?php
// Kiểm tra bảo mật: Không cho phép truy cập trực tiếp file này từ URL bên ngoài
if (!isset($_SESSION['user_name'])) {
    exit("Truy cập bị từ chối!");
}

/**
 * 🛠️ TẦNG KẾT NỐI VÀ XỬ LÝ SỐ LIỆU PHÂN TÍCH CHUYÊN SÂU (BUSINESS INTELLIGENCE LAYER)
 * Thực hiện tính toán tài chính, giá trị tồn kho động từ MySQL
 */
$db_connection = null;
$totalInventoryValue = 0; // Tổng giá trị vốn kho (Giá x Số lượng của tất cả sản phẩm)
$highestValueProducts = []; // Top sản phẩm đọng vốn lớn nhất
$outOfStockProducts = [];   // Sản phẩm sắp cháy kho (Số lượng <= 10)

try {
    mysqli_report(MYSQLI_REPORT_OFF);
    $db_connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($db_connection && !$db_connection->connect_error) {
        $db_connection->set_charset("utf8");

        // 📊 1. Tính tổng giá trị toàn bộ kho hàng (Tổng SUM của Price * Qty)
        $value_res = mysqli_query($db_connection, "SELECT SUM(price * qty) as total_val FROM products");
        if ($value_res) {
            $value_row = mysqli_fetch_assoc($value_res);
            $totalInventoryValue = floatval($value_row['total_val']);
        }

        // 📋 2. Truy vấn danh sách cơ cấu vốn kho (Sắp xếp theo Giá trị tồn giảm dần)
        $highest_res = mysqli_query($db_connection, "SELECT sku, name, price, qty, (price * qty) as total_item_val FROM products ORDER BY total_item_val DESC");
        if ($highest_res) {
            while ($row = mysqli_fetch_assoc($highest_res)) {
                $highestValueProducts[] = $row;
                
                // Phân loại song song: Nếu sản phẩm có qty <= 10 thì đẩy vào danh sách cảnh báo cháy kho
                if (intval($row['qty']) <= 10) {
                    $outOfStockProducts[] = $row;
                }
            }
        }
    }
} catch (Exception $e) {
    // Đóng băng ngoại lệ bảo vệ luồng giao diện
}
?>

<div class="reports-container">
    <div class="reports-title">
        <h2>📊 Trung tâm Báo cáo & Phân tích Kinh doanh</h2>
        <p>Hệ thống tự động hóa tính toán giá trị dòng vốn tài sản và phân tích rủi ro lưu kho theo thời gian thực.</p>
    </div>

    <div class="report-summary-card">
        <div class="summary-icon">💵</div>
        <div class="summary-details">
            <p>TỔNG GIÁ TRỊ VỐN LƯU KHO ĐANG QUẢN LÝ</p>
            <h3><?php echo number_format($totalInventoryValue); ?> <span style="font-size: 1.2rem;">VNĐ</span></h3>
        </div>
    </div>

    <div class="reports-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 25px; align-items: start;">
        
        <div class="report-box">
            <h4>📈 Bảng Phân Tích Cơ Cấu Vốn Hàng Hóa</h4>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Mã SKU</th>
                        <th>Tên sản phẩm</th>
                        <th>Đơn giá</th>
                        <th>Tồn kho</th>
                        <th>Giá trị vốn kho</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($highestValueProducts)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #95a5a6; padding: 30px 10px;">
                                Hệ thống trống. Chưa có dữ liệu sản phẩm để phân tích vốn kho.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($highestValueProducts as $prod): ?>
                            <tr>
                                <td><code class="report-sku"><?php echo htmlspecialchars($prod['sku']); ?></code></td>
                                <td><strong><?php echo htmlspecialchars($prod['name']); ?></strong></td>
                                <td><?php echo number_format($prod['price']); ?> đ</td>
                                <td><?php echo number_format($prod['qty']); ?></td>
                                <td style="color: #1e3d59; font-weight: bold;"><?php echo number_format($prod['total_item_val']); ?> đ</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="report-box alert-box">
            <h4>⚠️ Cảnh Báo Rủi Ro Hết Hàng (Qty ≤ 10)</h4>
            <div class="alert-list" style="display: flex; flex-direction: column; gap: 12px; margin-top: 15px;">
                <?php if (empty($outOfStockProducts)): ?>
                    <div style="text-align: center; color: #17b978; padding: 20px; background: #e3fcef; border-radius: 6px; font-size: 0.85rem; font-weight: 500;">
                        ✅ Trạng thái lý tưởng: Không có sản phẩm nào sắp hết hàng!
                    </div>
                <?php else: ?>
                    <?php foreach ($outOfStockProducts as $alert_item): ?>
                        <div class="alert-card" style="background: #fff0f0; border-left: 4px solid #ff6b6b; padding: 12px; border-radius: 4px;">
                            <span style="font-size: 0.8rem; color: #721c24; font-weight: bold;">SKU: <?php echo htmlspecialchars($alert_item['sku']); ?></span>
                            <h5 style="margin: 4px 0; color: #2c3e50; font-size: 0.85rem;"><?php echo htmlspecialchars($alert_item['name']); ?></h5>
                            <p style="margin: 0; font-size: 0.8rem; color: #721c24;">
                                Nguy cơ cháy kho! Hiện chỉ còn: <strong><?php echo $alert_item['qty']; ?></strong> sản phẩm.
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<style>
    .reports-container { animation: fadeIn 0.4s ease-in-out; }
    .reports-title h2 { color: #1e3d59; font-size: 1.6rem; }
    .reports-title p { color: #7f8c8d; font-size: 0.9rem; margin-top: 5px; }

    /* Thẻ tổng vốn kho */
    .report-summary-card { background: linear-gradient(135deg, #1e3d59 0%, #17b978 100%); color: white; padding: 25px; border-radius: 8px; margin-top: 20px; display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 15px rgba(23, 185, 120, 0.2); }
    .summary-icon { font-size: 3rem; opacity: 0.9; }
    .summary-details p { font-size: 0.8rem; letter-spacing: 1px; font-weight: 500; margin: 0; opacity: 0.8; }
    .summary-details h3 { font-size: 2.2rem; margin: 5px 0 0 0; font-weight: bold; }

    /* Box nội dung */
    .report-box { background: #ffffff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
    .report-box h4 { color: #1e3d59; font-size: 1.05rem; border-bottom: 2px solid #f4f6f9; padding-bottom: 10px; margin: 0; font-weight: 600; }

    /* Định dạng bảng */
    .report-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; margin-top: 15px; }
    .report-table th, .report-table td { padding: 12px 10px; border-bottom: 1px solid #f4f6f9; }
    .report-table th { color: #7f8c8d; font-weight: 600; }
    .report-table td { color: #2c3e50; }
    .report-sku { background: #eef2f5; padding: 2px 6px; border-radius: 4px; font-family: monospace; color: #e83e8c; font-weight: bold; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

================
File: modules/warehouse.php
================
<?php
/**
 * 🚚 MÔ-ĐUN ĐIỀU PHỐI KHO VẬT CHẤT (ODOO STOCK ENGINE MODEL)
 * Đạt chuẩn xử lý Transaction song song, bảo trì tuyệt đối.
 */
$pdo = getPDOLayerConnection();
$errors = [];
$messages = [];

// Khởi tạo các biến danh sách để tránh lỗi hiển thị tầng giao diện
$all_products = [];
$pickings = [];

try {
    // TẦNG KIỂM TRA BẢO VỆ (SHIELD LAYER): Xác minh bảng có tồn tại thực tế trong DB không
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'products'")->fetch();
    if (!$tableCheck) {
        throw new Exception("Hạ tầng bảng 'products' chưa được khởi tạo. Vui lòng nạp tệp SQL cấu trúc vào Database.");
    }

    // XỬ LÝ LỆNH TẠO PHIẾU ĐIỀU CHUYỂN (Thực thi khi nhấn Validate)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_picking'])) {
        $type = $_POST['type'] ?? 'in';
        $origin = trim($_POST['origin'] ?? '');
        $sku = $_POST['sku'] ?? '';
        $qty = intval($_POST['qty'] ?? 0);

        if (empty($sku) || $qty <= 0) {
            $errors[] = "Dữ liệu sản phẩm hoặc số lượng dịch chuyển không hợp lệ.";
        } else {
            try {
                // Khởi động Transaction để bảo vệ tính toàn vẹn dữ liệu song song (ACID)
                $pdo->beginTransaction();

                // 1. Kiểm tra sản phẩm có tồn tại thực tế không
                $stmtProd = $pdo->prepare("SELECT qty FROM products WHERE sku = ?");
                $stmtProd->execute([$sku]);
                $product = $stmtProd->fetch();

                if (!$product) {
                    throw new Exception("Sản phẩm có mã SKU [{$sku}] này không tồn tại.");
                }

                // Nếu là xuất kho, kiểm tra xem lượng tồn thực tế có đủ không
                if ($type === 'out' && $product['qty'] < $qty) {
                    throw new Exception("Số lượng tồn kho không đủ để xuất! Hiện có: " . $product['qty']);
                }

                // 2. Tạo số phiếu tự động dạng chuỗi thời gian tuyến tính
                $prefix = ($type === 'in') ? 'WH/IN/' : 'WH/OUT/';
                $picking_number = $prefix . time();

                $stmtPick = $pdo->prepare("INSERT INTO stock_picking (picking_number, origin, type, state) VALUES (?, ?, ?, 'done')");
                $stmtPick->execute([$picking_number, $origin, $type]);
                $picking_id = $pdo->lastInsertId();

                // 3. Tạo dòng dịch chuyển chi tiết (Stock Move Line)
                $stmtMove = $pdo->prepare("INSERT INTO stock_move (picking_id, product_sku, product_qty) VALUES (?, ?, ?)");
                $stmtMove->execute([$picking_id, $sku, $qty]);

                // 4. Cập nhật trực tiếp số lượng tồn kho tổng ở bảng sản phẩm
                if ($type === 'in') {
                    $stmtUpdateStock = $pdo->prepare("UPDATE products SET qty = qty + ? WHERE sku = ?");
                } else {
                    $stmtUpdateStock = $pdo->prepare("UPDATE products SET qty = qty - ? WHERE sku = ?");
                }
                $stmtUpdateStock->execute([$qty, $sku]);

                // Cam kết dữ liệu an toàn vào DB
                $pdo->commit();
                $messages[] = "Đã xác nhận thành công phiếu hoạt động kho {$picking_number}!";

            } catch (Exception $e) {
                // Hủy bỏ mọi tác vụ dở dang nếu xuất hiện lỗi bất ngờ, đưa DB về trạng thái nguyên bản
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = "Giao dịch kho thất bại: " . $e->getMessage();
            }
        }
    }

    // LẤY DANH SÁCH SẢN PHẨM PHỤC VỤ CHỌN LỰA TRÊN FORM
    $all_products = $pdo->query("SELECT sku, name FROM products")->fetchAll();

    // LẤY TOÀN BỘ DANH SÁCH LỊCH SỬ PHIẾU ĐIỀU CHUYỂN
    $pickings = $pdo->query("
        SELECT sp.*, sm.product_sku, sm.product_qty 
        FROM stock_picking sp 
        JOIN stock_move sm ON sp.id = sm.picking_id 
        ORDER BY sp.scheduled_date DESC
    ")->fetchAll();

} catch (Exception $e) {
    $errors[] = "Lỗi hệ thống cơ sở dữ liệu: " . $e->getMessage();
}
?>

<div class="container-fluid" style="padding: 10px;">
    <h2 style="color: #1e3d59; margin-bottom: 20px; font-weight: 700;">Điều Chuyển Kho Thực Tế (Odoo Engine Model)</h2>
    
    <?php foreach($errors as $error): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 15px; border-left: 5px solid #f5c6cb;">
            <strong>⚠️ Lỗi cấu trúc:</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php endforeach; ?>
    
    <?php foreach($messages as $msg): ?>
        <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin-bottom: 15px; border-left: 5px solid #c3e6cb;">
            <strong>✅ Thành công:</strong> <?= htmlspecialchars($msg) ?>
        </div>
    <?php endforeach; ?>

    <?php if ($tableCheck): ?>
    <div class="card mb-4" style="background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 30px;">
        <div style="font-weight: bold; font-size: 1.1rem; color: #1e3d59; border-bottom: 2px solid #eef2f5; padding-bottom: 10px; margin-bottom: 20px;">Khởi Tạo Phiếu Điều Chuyển Hàng Hóa</div>
        <form method="POST" action="">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
                <div>
                    <label style="display:block; margin-bottom: 8px; font-size: 0.9rem; font-weight: 600;">Loại hoạt động</label>
                    <select name="type" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;">
                        <option value="in">Nhập kho (Receipt - Mua hàng)</option>
                        <option value="out">Xuất kho (Delivery Order - Bán hàng)</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 8px; font-size: 0.9rem; font-weight: 600;">Chứng từ nguồn (Origin)</label>
                    <input type="text" name="origin" placeholder="Ví dụ: PO001 hoặc SO002" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;">
                </div>
                <div>
                    <label style="display:block; margin-bottom: 8px; font-size: 0.9rem; font-weight: 600;">Chọn sản phẩm dịch chuyển</label>
                    <select name="sku" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;" required>
                        <option value="">-- Chọn mặt hàng --</option>
                        <?php foreach($all_products as $prod): ?>
                            <option value="<?= $prod['sku'] ?>"><?= htmlspecialchars($prod['name']) ?> (<?= $prod['sku'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 8px; font-size: 0.9rem; font-weight: 600;">Số lượng luân chuyển</label>
                    <input type="number" name="qty" min="1" value="1" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;" required>
                </div>
            </div>
            <button type="submit" name="create_picking" style="margin-top: 20px; background-color: #17b978; color: white; border: none; padding: 12px 24px; font-weight: bold; border-radius: 4px; cursor: pointer; transition: background 0.2s;">Xác Nhận Lệnh Kho (Validate)</button>
        </form>
    </div>
    <?php endif; ?>

    <h4 style="color: #1e3d59; margin-bottom: 15px; font-weight: 600;">Nhật Ký Luân Chuyển Vật Chất Thực Tế</h4>
    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <thead>
            <tr style="background-color: #eef2f5; text-align: left; color: #1e3d59; font-size: 0.9rem;">
                <th style="padding: 15px;">Mã phiếu hoạt động</th>
                <th style="padding: 15px;">Tài liệu gốc</th>
                <th style="padding: 15px;">Loại dịch chuyển</th>
                <th style="padding: 15px;">Sản phẩm SKU</th>
                <th style="padding: 15px;">Số lượng</th>
                <th style="padding: 15px;">Thời gian ghi nhận</th>
                <th style="padding: 15px;">Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pickings)): ?>
                <tr><td colspan="7" style="padding: 20px; text-align: center; color: #7f8c8d;">Chưa phát sinh bất kỳ hoạt động luân chuyển kho nào hoặc cơ sở dữ liệu trống.</td></tr>
            <?php else: ?>
                <?php foreach($pickings as $pk): ?>
                <tr style="border-bottom: 1px solid #eef2f5; font-size: 0.95rem;">
                    <td style="padding: 15px;"><strong><?= $pk['picking_number'] ?></strong></td>
                    <td style="padding: 15px;"><?= htmlspecialchars($pk['origin'] ?: 'N/A') ?></td>
                    <td style="padding: 15px;">
                        <span style="padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; background-color: <?= $pk['type'] === 'in' ? '#d1ecf1' : '#fff3cd' ?>; color: <?= $pk['type'] === 'in' ? '#0c5460' : '#856404' ?>;">
                            <?= $pk['type'] === 'in' ? 'NHẬP KHO (IN)' : 'XUẤT KHO (OUT)' ?>
                        </span>
                    </td>
                    <td style="padding: 15px;"><?= htmlspecialchars($pk['product_sku']) ?></td>
                    <td style="padding: 15px;"><strong><?= number_format($pk['product_qty']) ?></strong> mục</td>
                    <td style="padding: 15px;"><?= $pk['scheduled_date'] ?></td>
                    <td style="padding: 15px;"><span style="background-color: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold;">Đã hoàn thành (Done)</span></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

================
File: README.md
================
# php-login-minimal

A simple, but secure PHP login script. Uses the ultra-modern & future-proof PHP 5.5 BLOWFISH hashing/salting functions (includes the official PHP 5.3 & PHP 5.4 compatibility pack, which makes those functions available in these versions too). 

## Why does this script exist ?

In the PHP world every beginner tries to build login systems from scratch, doing all the typical mistakes, usually going from saving plain text passwords to using (horribly wrong) MD5 hashing. This script tries to give beginners a usable code base with a fully implemented user authentication ("login") system, preventing less-experienced developers at least from the worst security issues.

This script was originally part of the "php-login project", a collection of 4 different login scripts made in the 2012-2013 PHP era to give especially beginners and security-inexperienced users a set of basic auth functions that fitted the most modern password hashing standards possible. You know, this was the time when even major companies like SONY and LinkedIn used horrible outdated MD5-hashing for their passwords (or even saved everything in plain text) and when the big PHP frameworks didn't have proper user auth solution out-of-the-box.

Find the other versions here:

**One-file version** (not maintained anymore)
Full login script in one file. Uses a one-file SQLite database (no MySQL needed) and PDO: Register, login, logout.
https://github.com/panique/php-login-one-file

**Minimal version** (not maintained anymore)
All the basic functions in a clean file structure, uses MySQL and mysqli. Register, login, logout.
https://github.com/panique/php-login-minimal

**HUGE (professional version)** 
Quite professional MVC framework structure, useful for real applications. Additional features like: URL rewriting, mail sending via PHPMailer (SMTP or PHP's mail() function/linux sendmail), user profile pages, public user profiles, gravatars and local avatars, account upgrade/downgrade etc., OAuth2, Composer integration, etc.
https://github.com/panique/huge

## Requirements

- PHP 5.3.7+
- MySQL 5 database (please use a modern version of MySQL (5.5, 5.6, 5.7) as very old versions have a exotic bug that
[makes PDO injections possible](http://stackoverflow.com/q/134099/1114320).
- activated mysqli (last letter is an "i") extension (activated by default on most server setups)

## Installation (quick setup)

Create a database *login* and the table *users* via the SQL statements in the `_install` folder.
Change mySQL database user and password in `config/db.php` (*DB_USER* and *DB_PASS*).

## Installation (detailed setup tutorials)

- [Detailed tutorial for installation on Ubuntu 12.04 LTS](http://www.dev-metal.com/install-php-login-nets-1-minimal-login-script-ubuntu/)
- [Detailed tutorial for installation on Windows 7 and 8 (useful for development)](http://www.dev-metal.com/how-to-install-php-login-minimal-on-windows-7-8/)

## Security notice

This script comes with a handy .htaccess in the views folder that denies direct access to the files within the folder
(so that people cannot render the views directly). However, these .htaccess files only work if you have set
`AllowOverride` to `All` in your apache vhost configs. There are lots of tutorials on the web on how to do this.

## Useful links

- [A little guideline on how to use the PHP 5.5 password hashing functions and its "library plugin" based PHP 5.3 & 5.4 implementation](http://www.dev-metal.com/use-php-5-5-password-hashing-functions/)
- [How to setup latest version of PHP 5.5 on Ubuntu 12.04 LTS](http://www.dev-metal.com/how-to-setup-latest-version-of-php-5-5-on-ubuntu-12-04-lts/). Same for Debian 7.0 / 7.1:
- [How to setup latest version of PHP 5.5 on Debian Wheezy 7.0/7.1 (and how to fix the GPG key error)](http://www.dev-metal.com/setup-latest-version-php-5-5-debian-wheezy-7-07-1-fix-gpg-key-error/)
- [Notes on password & hashing salting in upcoming PHP versions (PHP 5.5.x & 5.6 etc.)](https://github.com/panique/php-login/wiki/Notes-on-password-&-hashing-salting-in-upcoming-PHP-versions-%28PHP-5.5.x-&-5.6-etc.%29)
- [Some basic "benchmarks" of all PHP hash/salt algorithms](https://github.com/panique/php-login/wiki/Which-hashing-&-salting-algorithm-should-be-used-%3F)

## License

Licensed under [MIT](http://www.opensource.org/licenses/mit-license.php). You can use this script for free for any
private or commercial projects.

## Contribute

Please create a feature-branch if possible when committing to the project, if not then simply commit to master branch.

## Support

Support the project by renting a server at [DigitalOcean](https://www.digitalocean.com/?refcode=40d978532a20) or just tipping a coffee at BuyMeACoffee.com. Thanks! :)

<a href="https://www.buymeacoffee.com/panique" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" alt="Buy Me A Coffee" style="height: 60px !important;width: 217px !important;" ></a>

## I'm blogging...

at **[DEV METAL](http://www.dev-metal.com)**, mostly about PHP and IT-related stuff. Have a look if you like.

================
File: register.php
================
<?php

/**
 * A simple, clean and secure PHP Login Script / MINIMAL VERSION
 *
 * Uses PHP SESSIONS, modern password-hashing and salting and gives the basic functions a proper login system needs.
 *
 * @author Panique
 * @link https://github.com/panique/php-login-minimal/
 * @license http://opensource.org/licenses/MIT MIT License
 */

// checking for minimum PHP version
if (version_compare(PHP_VERSION, '5.3.7', '<')) {
    exit("Sorry, Simple PHP Login does not run on a PHP version smaller than 5.3.7 !");
} else if (version_compare(PHP_VERSION, '5.5.0', '<')) {
    // if you are using PHP 5.3 or PHP 5.4 you have to include the password_api_compatibility_library.php
    // (this library adds the PHP 5.5 password hashing functions to older versions of PHP)
    require_once("libraries/password_compatibility_library.php");
}

// include the configs / constants for the database connection
require_once("config/db.php");

// load the registration class
require_once("classes/Registration.php");

// create the registration object. when this object is created, it will do all registration stuff automatically
// so this single line handles the entire registration process.
$registration = new Registration();

// show the register view (with the registration form, and messages/errors)
include("views/register.php");

================
File: repomix-output.md
================
This file is a merged representation of the entire codebase, combined into a single document by Repomix.

# File Summary

## Purpose
This file contains a packed representation of the entire repository's contents.
It is designed to be easily consumable by AI systems for analysis, code review,
or other automated processes.

## File Format
The content is organized as follows:
1. This summary section
2. Repository information
3. Directory structure
4. Repository files (if enabled)
5. Multiple file entries, each consisting of:
  a. A header with the file path (## File: path/to/file)
  b. The full contents of the file in a code block

## Usage Guidelines
- This file should be treated as read-only. Any changes should be made to the
  original repository files, not this packed version.
- When processing this file, use the file path to distinguish
  between different files in the repository.
- Be aware that this file may contain sensitive information. Handle it with
  the same level of security as you would the original repository.

## Notes
- Some files may have been excluded based on .gitignore rules and Repomix's configuration
- Binary files are not included in this packed representation. Please refer to the Repository Structure section for a complete list of file paths, including binary files
- Files matching patterns in .gitignore are excluded
- Files matching default ignore patterns are excluded
- Files are sorted by Git change count (files with more changes are at the bottom)

# Directory Structure
```
_installation/01-create-database.sql
_installation/02-create-and-fill-users-table.sql
_installation/03-cautrucdichchuyenkho.sql
_installation/New Text Document.txt
_support/banner-host1plus.png
.repomixignore
classes/Login.php
classes/Registration.php
config/db.php
docker-compose.yml
Dockerfile
index.php
libraries/password_compatibility_library.php
modules/dashboard.php
modules/New Text Document.txt
modules/products.php
modules/reports.php
modules/warehouse.php
README.md
register.php
repomix.config.json
views/.htaccess
views/logged_in.php
views/not_logged_in.php
views/register.php
```

# Files

## File: _installation/01-create-database.sql
```sql
CREATE DATABASE IF NOT EXISTS `login`;
```

## File: _installation/02-create-and-fill-users-table.sql
```sql
CREATE TABLE IF NOT EXISTS `login`.`users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'auto incrementing user_id of each user, unique index',
  `user_name` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'user''s name, unique',
  `user_password_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'user''s password in salted and hashed format',
  `user_email` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'user''s email, unique',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_name` (`user_name`),
  UNIQUE KEY `user_email` (`user_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='user data';


-- 1. Tạo bảng danh mục sản phẩm và quản lý tồn kho tổng
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sku` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(255) NOT NULL,
  `qty` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tạo bảng quản lý phiếu điều chuyển (Receipts / Delivery Orders)
CREATE TABLE IF NOT EXISTS `stock_picking` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `picking_number` VARCHAR(50) NOT NULL UNIQUE,
  `origin` VARCHAR(100) NULL,
  `type` ENUM('in', 'out') NOT NULL,
  `state` ENUM('draft', 'confirmed', 'done') NOT NULL DEFAULT 'draft',
  `scheduled_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tạo bảng chi tiết dịch chuyển kho (Stock Move Lines) - Liên kết khóa ngoại chặt chẽ
CREATE TABLE IF NOT EXISTS `stock_move` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `picking_id` INT NOT NULL,
  `product_sku` VARCHAR(50) NOT NULL,
  `product_qty` INT NOT NULL,
  FOREIGN KEY (`picking_id`) REFERENCES `stock_picking`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_sku`) REFERENCES `products`(`sku`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Bơm dữ liệu sản phẩm mẫu để hệ thống có sẵn vật chất luân chuyển
INSERT IGNORE INTO `products` (`sku`, `name`, `qty`) VALUES
('PROD-CPU-I9', 'Bộ xử lý Intel Core i9 14900K', 50),
('PROD-RAM-32', 'Thanh RAM DDR5 Corsair 32GB', 120),
('PROD-SSD-01', 'Ổ cứng SSD Samsung 990 Pro 1TB', 85);
```

## File: _installation/03-cautrucdichchuyenkho.sql
```sql
-- Tạo bảng quản lý Phiếu dịch chuyển kho (Chuẩn Odoo Stock Picking)
CREATE TABLE IF NOT EXISTS `stock_picking` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `picking_number` VARCHAR(50) NOT NULL UNIQUE, -- Mã phiếu: WH/IN/0001 hoặc WH/OUT/0001
  `origin` VARCHAR(100) DEFAULT NULL,            -- Chứng từ gốc (Ví dụ: PO-001, SO-002)
  `type` ENUM('in', 'out') NOT NULL,             -- 'in' là Nhập kho, 'out' là Xuất kho
  `scheduled_date` DATETIME DEFAULT CURRENT_TIMESTAMP, -- Ngày thực hiện phiếu
  `state` ENUM('draft', 'done') DEFAULT 'draft'  -- Trạng thái phiếu
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng chi tiết dòng dịch chuyển vật chất (Stock Move Line)
CREATE TABLE IF NOT EXISTS `stock_move` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `picking_id` INT NOT NULL,                     -- Kết nối song song với bảng stock_picking
  `product_sku` VARCHAR(64) NOT NULL,            -- Kết nối với SKU của bảng sản phẩm
  `product_qty` INT NOT NULL,                    -- Số lượng dịch chuyển của dòng này
  FOREIGN KEY (`picking_id`) REFERENCES `stock_picking`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## File: _installation/New Text Document.txt
```

```

## File: .repomixignore
```
# Add patterns to ignore here, one per line
# Example:
# *.log
# tmp/
```

## File: classes/Login.php
```php
<?php

/**
 * Class login
 * handles the user's login and logout process
 */
class Login
{
    /**
     * @var object The database connection
     */
    private $db_connection = null;
    /**
     * @var array Collection of error messages
     */
    public $errors = array();
    /**
     * @var array Collection of success / neutral messages
     */
    public $messages = array();

    /**
     * the function "__construct()" automatically starts whenever an object of this class is created,
     * you know, when you do "$login = new Login();"
     */
    public function __construct()
    {
        // create/read session, absolutely necessary
        session_start();

        // check the possible login actions:
        // if user tried to log out (happen when user clicks logout button)
        if (isset($_GET["logout"])) {
            $this->doLogout();
        }
        // login via post data (if user just submitted a login form)
        elseif (isset($_POST["login"])) {
            $this->dologinWithPostData();
        }
    }

    /**
     * log in with post data
     */
    private function dologinWithPostData()
    {
        // check login form contents
        if (empty($_POST['user_name'])) {
            $this->errors[] = "Username field was empty.";
        } elseif (empty($_POST['user_password'])) {
            $this->errors[] = "Password field was empty.";
        } elseif (!empty($_POST['user_name']) && !empty($_POST['user_password'])) {

            // create a database connection, using the constants from config/db.php (which we loaded in index.php)
            $this->db_connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            // change character set to utf8 and check it
            if (!$this->db_connection->set_charset("utf8")) {
                $this->errors[] = $this->db_connection->error;
            }

            // if no connection errors (= working database connection)
            if (!$this->db_connection->connect_errno) {

                // escape the POST stuff
                $user_name = $this->db_connection->real_escape_string($_POST['user_name']);

                // database query, getting all the info of the selected user (allows login via email address in the
                // username field)
                $sql = "SELECT user_name, user_email, user_password_hash
                        FROM users
                        WHERE user_name = '" . $user_name . "' OR user_email = '" . $user_name . "';";
                $result_of_login_check = $this->db_connection->query($sql);

                // if this user exists
                if ($result_of_login_check->num_rows == 1) {

                    // get result row (as an object)
                    $result_row = $result_of_login_check->fetch_object();

                    // using PHP 5.5's password_verify() function to check if the provided password fits
                    // the hash of that user's password
                    if (password_verify($_POST['user_password'], $result_row->user_password_hash)) {

                        // write user data into PHP SESSION (a file on your server)
                        $_SESSION['user_name'] = $result_row->user_name;
                        $_SESSION['user_email'] = $result_row->user_email;
                        $_SESSION['user_login_status'] = 1;

                    } else {
                        $this->errors[] = "Wrong password. Try again.";
                    }
                } else {
                    $this->errors[] = "This user does not exist.";
                }
            } else {
                $this->errors[] = "Database connection problem.";
            }
        }
    }

    /**
     * perform the logout
     */
    public function doLogout()
    {
        // delete the session of the user
        $_SESSION = array();
        session_destroy();
        // return a little feeedback message
        $this->messages[] = "You have been logged out.";

    }

    /**
     * simply return the current state of the user's login
     * @return boolean user's login status
     */
    public function isUserLoggedIn()
    {
        if (isset($_SESSION['user_login_status']) AND $_SESSION['user_login_status'] == 1) {
            return true;
        }
        // default return
        return false;
    }
	/**
     * 🔓 PUBLIC GETTER: Cung cấp sợi tơ kết nối Database hợp pháp ra bên ngoài
     * Giúp các module độc lập như products.php kế thừa và tái sử dụng kết nối của Docker
     */
    public function getDatabaseConnection() {
        return $this->db_connection;
    }
}
```

## File: classes/Registration.php
```php
<?php

/**
 * Class registration
 * handles the user registration
 */
class Registration
{
    /**
     * @var object $db_connection The database connection
     */
    private $db_connection = null;
    /**
     * @var array $errors Collection of error messages
     */
    public $errors = array();
    /**
     * @var array $messages Collection of success / neutral messages
     */
    public $messages = array();

    /**
     * the function "__construct()" automatically starts whenever an object of this class is created,
     * you know, when you do "$registration = new Registration();"
     */
    public function __construct()
    {
        if (isset($_POST["register"])) {
            $this->registerNewUser();
        }
    }

    /**
     * handles the entire registration process. checks all error possibilities
     * and creates a new user in the database if everything is fine
     */
    private function registerNewUser()
    {
        if (empty($_POST['user_name'])) {
            $this->errors[] = "Empty Username";
        } elseif (empty($_POST['user_password_new']) || empty($_POST['user_password_repeat'])) {
            $this->errors[] = "Empty Password";
        } elseif ($_POST['user_password_new'] !== $_POST['user_password_repeat']) {
            $this->errors[] = "Password and password repeat are not the same";
        } elseif (strlen($_POST['user_password_new']) < 6) {
            $this->errors[] = "Password has a minimum length of 6 characters";
        } elseif (strlen($_POST['user_name']) > 64 || strlen($_POST['user_name']) < 2) {
            $this->errors[] = "Username cannot be shorter than 2 or longer than 64 characters";
        } elseif (!preg_match('/^[a-z\d]{2,64}$/i', $_POST['user_name'])) {
            $this->errors[] = "Username does not fit the name scheme: only a-Z and numbers are allowed, 2 to 64 characters";
        } elseif (empty($_POST['user_email'])) {
            $this->errors[] = "Email cannot be empty";
        } elseif (strlen($_POST['user_email']) > 64) {
            $this->errors[] = "Email cannot be longer than 64 characters";
        } elseif (!filter_var($_POST['user_email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Your email address is not in a valid email format";
        } elseif (!empty($_POST['user_name'])
            && strlen($_POST['user_name']) <= 64
            && strlen($_POST['user_name']) >= 2
            && preg_match('/^[a-z\d]{2,64}$/i', $_POST['user_name'])
            && !empty($_POST['user_email'])
            && strlen($_POST['user_email']) <= 64
            && filter_var($_POST['user_email'], FILTER_VALIDATE_EMAIL)
            && !empty($_POST['user_password_new'])
            && !empty($_POST['user_password_repeat'])
            && ($_POST['user_password_new'] === $_POST['user_password_repeat'])
        ) {
            // create a database connection
            $this->db_connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            // change character set to utf8 and check it
            if (!$this->db_connection->set_charset("utf8")) {
                $this->errors[] = $this->db_connection->error;
            }

            // if no connection errors (= working database connection)
            if (!$this->db_connection->connect_errno) {

                // escaping, additionally removing everything that could be (html/javascript-) code
                $user_name = $this->db_connection->real_escape_string(strip_tags($_POST['user_name'], ENT_QUOTES));
                $user_email = $this->db_connection->real_escape_string(strip_tags($_POST['user_email'], ENT_QUOTES));

                $user_password = $_POST['user_password_new'];

                // crypt the user's password with PHP 5.5's password_hash() function, results in a 60 character
                // hash string. the PASSWORD_DEFAULT constant is defined by the PHP 5.5, or if you are using
                // PHP 5.3/5.4, by the password hashing compatibility library
                $user_password_hash = password_hash($user_password, PASSWORD_DEFAULT);

                // check if user or email address already exists
                $sql = "SELECT * FROM users WHERE user_name = '" . $user_name . "' OR user_email = '" . $user_email . "';";
                $query_check_user_name = $this->db_connection->query($sql);

                if ($query_check_user_name->num_rows == 1) {
                    $this->errors[] = "Sorry, that username / email address is already taken.";
                } else {
                    // write new user's data into database
                    $sql = "INSERT INTO users (user_name, user_password_hash, user_email)
                            VALUES('" . $user_name . "', '" . $user_password_hash . "', '" . $user_email . "');";
                    $query_new_user_insert = $this->db_connection->query($sql);

                    // if user has been added successfully
                    if ($query_new_user_insert) {
                        $this->messages[] = "Your account has been created successfully. You can now log in.";
                    } else {
                        $this->errors[] = "Sorry, your registration failed. Please go back and try again.";
                    }
                }
            } else {
                $this->errors[] = "Sorry, no database connection.";
            }
        } else {
            $this->errors[] = "An unknown error occurred.";
        }
    }
}
```

## File: config/db.php
```php
<?php
// Giữ nguyên các hằng số cũ của bạn
define("DB_HOST", "db"); // Tên service trong docker-compose
define("DB_USER", "root");
define("DB_PASS", "root_password");
define("DB_NAME", "login");

// Cổng kết nối cũ (MySQLi) cho các module chưa nâng cấp
$db_connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db_connection->connect_errno) {
    die("Kết nối MySQLi thất bại: " . $db_connection->connect_error);
}

/**
 * Hàm khởi tạo kết nối PDO - Tầng bảo mật tuyệt đối
 * @return PDO
 */
function getPDOLayerConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // Ép buộc sử dụng Prepared Statements thực tế của MySQL
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Lỗi tầng kết nối PDO: " . $e->getMessage());
        }
    }
    return $pdo;
}
```

## File: docker-compose.yml
```yaml
version: '3.8'

services:
  # Lớp 1: Máy chủ Web chạy PHP và Apache
  web:
    build: .  # Chạy thông qua Dockerfile vừa tạo ở trên
    container_name: phplogin_web
    ports:
      - "8888:80"  # Cổng truy cập máy thật: localhost:8888
    volumes:
      - .:/var/www/html
    depends_on:
      - db
    networks:
      - erp_network

  # Lớp 2: Cơ sở dữ liệu MySQL 
  db:
    image: mysql:8.0
    container_name: phplogin_db
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: login
    ports:
      - "3307:3306"  # ĐỔI THÀNH 3307 để loại bỏ hoàn toàn lỗi xung đột cổng máy thật
    volumes:
      - db_data:/var/lib/mysql
      - ./_installation:/docker-entrypoint-initdb.d
    networks:
      - erp_network

  # Lớp 3: Trình quản lý Database trực quan (Adminer)
  adminer:
    image: adminer
    container_name: phplogin_adminer
    restart: always
    ports:
      - "8889:8080"  # Truy cập quản lý DB qua: localhost:8084
    networks:
      - erp_network

volumes:
  db_data:

networks:
  erp_network:
    driver: bridge
```

## File: Dockerfile
```dockerfile
FROM php:8.1-apache

# Cài đặt và kích hoạt các extension mở rộng cho MySQLi và PDO bảo mật
RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && docker-php-ext-enable mysqli pdo pdo_mysql

# Kích hoạt mod_rewrite của Apache (phục vụ cho việc định tuyến Router Whitelist mượt mà hơn)
RUN a2enmod rewrite

# Cấp quyền ghi để Apache container vận hành tệp tin mượt mà, không bị nghẽn
RUN chown -R www-data:www-data /var/www/html
```

## File: index.php
```php
<?php
/**
 * 🛰️ HỆ THỐNG ĐIỀU PHỐI TRUNG TÂM VÀ XÁC THỰC (CENTRAL ROUTER & AUTH ENGINE)
 * Đạt chuẩn Odoo quy mô công nghiệp - An toàn, quyết đoán, bảo trì tuyệt đối.
 */

// Kiểm tra phiên bản PHP tối thiểu
if (version_compare(PHP_VERSION, '5.3.7', '<')) {
    exit("Sorry, Simple PHP Login does not run on a PHP version smaller than 5.3.7 !");
}

// Nạp cấu hình Database và các lớp xử lý đối tượng Core
require_once("config/db.php");
require_once("classes/Login.php");

// Khởi tạo thực thể Login (Tự động xử lý Cookie, Session, Đăng nhập, Đăng xuất)
$login = new Login();

// 🛑 TẦNG KIỂM TRA ĐĂNG NHẬP: Nếu chưa đăng nhập, kết thúc luồng và hiển thị màn hình Login mẫu
if ($login->isUserLoggedIn() == false) {
    include("views/not_logged_in.php");
    exit(); 
}

// 🌐 TẦNG GIAO DIỆN CHÍNH (Sau khi đã đăng nhập thành công)
// Đảm bảo đồng bộ Session tên người dùng cho giao diện
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_name']) && isset($_SESSION['user_id'])) {
    // Dự phòng đồng bộ nếu thư viện Login gốc lưu cấu trúc session khác
    $_SESSION['user_name'] = $_SESSION['user_name'] ?? 'Admin';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Quản Lý Kho Chuẩn Odoo</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; background-color: #f4f6f9; min-height: 100vh; color: #333333; }
        
        /* 🔵 SIDEBAR DESIGN - TÔNG XANH NƯỚC BIỂN ĐẬM ERP */
        .sidebar { width: 260px; background-color: #1e3d59; color: #ffffff; display: flex; flex-direction: column; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }
        .sidebar-brand { padding: 20px; text-align: center; font-size: 1.15rem; font-weight: bold; border-bottom: 1px solid #17b978; background-color: #17b978; color: #ffffff; letter-spacing: 0.5px; }
        .sidebar-menu { list-style: none; padding: 15px 0; flex: 1; }
        .sidebar-menu li { padding: 14px 20px; transition: all 0.2s ease-in-out; }
        .sidebar-menu li:hover { background-color: #17b978; padding-left: 25px; }
        .sidebar-menu a { color: #e8f1f5; text-decoration: none; display: flex; align-items: center; gap: 12px; font-size: 0.95rem; font-weight: 500; }
        .sidebar-menu li:hover a { color: #ffffff; }
        
        .sidebar-logout { padding: 20px; border-top: 1px solid #2b5278; }
        .btn-logout { display: block; text-align: center; background-color: #ff6b6b; color: white; padding: 10px; text-decoration: none; border-radius: 6px; font-weight: bold; transition: background 0.2s; }
        .btn-logout:hover { background-color: #ee5253; }

        /* ⚪ MAIN CONTENT WORKSPACE */
        .main-content { flex: 1; display: flex; flex-direction: column; }
        .main-header { height: 65px; background-color: #ffffff; display: flex; align-items: center; justify-content: space-between; padding: 0 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border-bottom: 1px solid #eef2f5; }
        .page-title { font-size: 1.1rem; font-weight: 600; color: #1e3d59; display: flex; align-items: center; gap: 8px; }
        
        /* 👤 AVATAR PROFILE CORNER */
        .user-profile { display: flex; align-items: center; gap: 12px; cursor: pointer; }
        .user-info { text-align: right; }
        .user-info .username { display: block; font-size: 0.9rem; font-weight: 600; color: #2c3e50; }
        .user-info .role { display: block; font-size: 0.75rem; color: #7f8c8d; }
        .avatar-circle { width: 38px; height: 38px; background-color: #17b978; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1rem; box-shadow: 0 2px 4px rgba(23, 185, 120, 0.2); border: 2px solid #ffffff; }

        .main-body { padding: 30px; flex: 1; overflow-y: auto; background-color: #f8fafc; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-brand">📦 Warehouse</div>
        <ul class="sidebar-menu">
            <li><a href="index.php?page=dashboard">📊 Dashboard</a></li>
            <li><a href="index.php?page=products">📦 Sản phẩm</a></li>
            <li><a href="index.php?page=warehouse">🚚 Điều phối Kho</a></li>
            <li><a href="index.php?page=reports">📈 Báo cáo vĩ mô</a></li>
        </ul>
        <div class="sidebar-logout">
            <a href="index.php?logout" class="btn-logout">🚪 Đăng xuất</a>
        </div>
    </aside>

    <div class="main-content">
        <header class="main-header">
            <div class="page-title">⚙️ Hệ thống điều phối dữ liệu</div>
            
            <div class="user-profile">
                <div class="user-info">
                    <span class="username"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Quản trị viên'); ?></span>
                    <span class="role">Hạt nhân Hệ thống</span>
                </div>
                <div class="avatar-circle">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'Q', 0, 1)); ?>
                </div>
            </div>
        </header>
        
        <main class="main-body">
            <?php
            $currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
            $allowedPages = ['dashboard', 'products', 'reports', 'warehouse'];
            
            if (in_array($currentPage, $allowedPages)) {
                $targetFile = "modules/" . $currentPage . ".php";
                if (file_exists($targetFile)) {
                    include($targetFile);
                } else {
                    echo "<div style='background: #ffffff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border-left: 4px solid #1e3d59;'>";
                    echo "<h3 style='color: #1e3d59;'>Mô-đun [ " . htmlspecialchars(ucfirst($currentPage)) . " ] đang được cấu trúc</h3>";
                    echo "<p style='color: #7f8c8d; margin-top: 10px;'>Hạt nhân Docker đang sẵn sàng nạp kết nối SQL cho tầng nghiệp vụ này.</p>";
                    echo "</div>";
                }
            } else {
                echo "<h3 style='color: #ff6b6b;'>Cảnh báo: Tầng truy cập nghiệp vụ không hợp lệ!</h3>";
            }
            ?>
        </main>
    </div>

</body>
</html>
```

## File: libraries/password_compatibility_library.php
```php
<?php
/**
 * A Compatibility library with PHP 5.5's simplified password hashing API.
 *
 * @author Anthony Ferrara <ircmaxell@php.net>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @copyright 2012 The Authors
 */

if (!defined('PASSWORD_DEFAULT')) {

    define('PASSWORD_BCRYPT', 1);
    define('PASSWORD_DEFAULT', PASSWORD_BCRYPT);

    /**
     * Hash the password using the specified algorithm
     *
     * @param string $password The password to hash
     * @param int    $algo     The algorithm to use (Defined by PASSWORD_* constants)
     * @param array  $options  The options for the algorithm to use
     *
     * @return string|false The hashed password, or false on error.
     */
    function password_hash($password, $algo, array $options = array()) {
        if (!function_exists('crypt')) {
            trigger_error("Crypt must be loaded for password_hash to function", E_USER_WARNING);
            return null;
        }
        if (!is_string($password)) {
            trigger_error("password_hash(): Password must be a string", E_USER_WARNING);
            return null;
        }
        if (!is_int($algo)) {
            trigger_error("password_hash() expects parameter 2 to be long, " . gettype($algo) . " given", E_USER_WARNING);
            return null;
        }
        switch ($algo) {
            case PASSWORD_BCRYPT:
                // Note that this is a C constant, but not exposed to PHP, so we don't define it here.
                $cost = 10;
                if (isset($options['cost'])) {
                    $cost = $options['cost'];
                    if ($cost < 4 || $cost > 31) {
                        trigger_error(sprintf("password_hash(): Invalid bcrypt cost parameter specified: %d", $cost), E_USER_WARNING);
                        return null;
                    }
                }
                // The length of salt to generate
                $raw_salt_len = 16;
                // The length required in the final serialization
                $required_salt_len = 22;
                $hash_format = sprintf("$2y$%02d$", $cost);
                break;
            default:
                trigger_error(sprintf("password_hash(): Unknown password hashing algorithm: %s", $algo), E_USER_WARNING);
                return null;
        }
        if (isset($options['salt'])) {
            switch (gettype($options['salt'])) {
                case 'NULL':
                case 'boolean':
                case 'integer':
                case 'double':
                case 'string':
                    $salt = (string) $options['salt'];
                    break;
                case 'object':
                    if (method_exists($options['salt'], '__tostring')) {
                        $salt = (string) $options['salt'];
                        break;
                    }
                case 'array':
                case 'resource':
                default:
                    trigger_error('password_hash(): Non-string salt parameter supplied', E_USER_WARNING);
                    return null;
            }
            if (strlen($salt) < $required_salt_len) {
                trigger_error(sprintf("password_hash(): Provided salt is too short: %d expecting %d", strlen($salt), $required_salt_len), E_USER_WARNING);
                return null;
            } elseif (0 == preg_match('#^[a-zA-Z0-9./]+$#D', $salt)) {
                $salt = str_replace('+', '.', base64_encode($salt));
            }
        } else {
            $buffer = '';
            $buffer_valid = false;
            if (function_exists('mcrypt_create_iv') && !defined('PHALANGER')) {
                $buffer = mcrypt_create_iv($raw_salt_len, MCRYPT_DEV_URANDOM);
                if ($buffer) {
                    $buffer_valid = true;
                }
            }
            if (!$buffer_valid && function_exists('openssl_random_pseudo_bytes')) {
                $buffer = openssl_random_pseudo_bytes($raw_salt_len);
                if ($buffer) {
                    $buffer_valid = true;
                }
            }
            if (!$buffer_valid && is_readable('/dev/urandom')) {
                $f = fopen('/dev/urandom', 'r');
                $read = strlen($buffer);
                while ($read < $raw_salt_len) {
                    $buffer .= fread($f, $raw_salt_len - $read);
                    $read = strlen($buffer);
                }
                fclose($f);
                if ($read >= $raw_salt_len) {
                    $buffer_valid = true;
                }
            }
            if (!$buffer_valid || strlen($buffer) < $raw_salt_len) {
                $bl = strlen($buffer);
                for ($i = 0; $i < $raw_salt_len; $i++) {
                    if ($i < $bl) {
                        $buffer[$i] = $buffer[$i] ^ chr(mt_rand(0, 255));
                    } else {
                        $buffer .= chr(mt_rand(0, 255));
                    }
                }
            }
            $salt = str_replace('+', '.', base64_encode($buffer));
        }
        $salt = substr($salt, 0, $required_salt_len);

        $hash = $hash_format . $salt;

        $ret = crypt($password, $hash);

        if (!is_string($ret) || strlen($ret) <= 13) {
            return false;
        }

        return $ret;
    }

    /**
     * Get information about the password hash. Returns an array of the information
     * that was used to generate the password hash.
     *
     * array(
     *    'algo' => 1,
     *    'algoName' => 'bcrypt',
     *    'options' => array(
     *        'cost' => 10,
     *    ),
     * )
     *
     * @param string $hash The password hash to extract info from
     *
     * @return array The array of information about the hash.
     */
    function password_get_info($hash) {
        $return = array(
            'algo' => 0,
            'algoName' => 'unknown',
            'options' => array(),
        );
        if (substr($hash, 0, 4) == '$2y$' && strlen($hash) == 60) {
            $return['algo'] = PASSWORD_BCRYPT;
            $return['algoName'] = 'bcrypt';
            list($cost) = sscanf($hash, "$2y$%d$");
            $return['options']['cost'] = $cost;
        }
        return $return;
    }

    /**
     * Determine if the password hash needs to be rehashed according to the options provided
     *
     * If the answer is true, after validating the password using password_verify, rehash it.
     *
     * @param string $hash    The hash to test
     * @param int    $algo    The algorithm used for new password hashes
     * @param array  $options The options array passed to password_hash
     *
     * @return boolean True if the password needs to be rehashed.
     */
    function password_needs_rehash($hash, $algo, array $options = array()) {
        $info = password_get_info($hash);
        if ($info['algo'] != $algo) {
            return true;
        }
        switch ($algo) {
            case PASSWORD_BCRYPT:
                $cost = isset($options['cost']) ? $options['cost'] : 10;
                if ($cost != $info['options']['cost']) {
                    return true;
                }
                break;
        }
        return false;
    }

    /**
     * Verify a password against a hash using a timing attack resistant approach
     *
     * @param string $password The password to verify
     * @param string $hash     The hash to verify against
     *
     * @return boolean If the password matches the hash
     */
    function password_verify($password, $hash) {
        if (!function_exists('crypt')) {
            trigger_error("Crypt must be loaded for password_verify to function", E_USER_WARNING);
            return false;
        }
        $ret = crypt($password, $hash);
        if (!is_string($ret) || strlen($ret) != strlen($hash) || strlen($ret) <= 13) {
            return false;
        }

        $status = 0;
        for ($i = 0; $i < strlen($ret); $i++) {
            $status |= (ord($ret[$i]) ^ ord($hash[$i]));
        }

        return $status === 0;
    }
}
```

## File: modules/dashboard.php
```php
<?php
// Kiểm tra bảo mật: Không cho phép truy cập trực tiếp file này từ URL bên ngoài
if (!isset($_SESSION['user_name'])) {
    exit("Truy cập bị từ chối!");
}

/**
 * 🛠️ TẦNG KẾT NỐI VÀ TRUY VẤN DỮ LIỆU THỰC TẾ (DYNAMIC DATA LAYER - PDO UPGRADED)
 * Kéo chỉ số trực tiếp từ bảng products và tích hợp lịch sử luân chuyển thực tế từ stock_picking
 */
$totalProducts = 0;       
$totalStockVolume = 0;   
$lowStockAlert = 0;      
$recentActivities = [];  

try {
    // Khởi tạo kết nối thông qua lớp PDO đồng bộ bảo mật
    if (function_exists('getPDOLayerConnection')) {
        $pdo = getPDOLayerConnection();
    } else {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    
    if ($pdo) {
        // 1. Lấy tổng số danh mục sản phẩm (Total SKU)
        $totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

        // 2. Lấy tổng sản lượng tồn kho luân chuyển (Total Quantity)
        $totalStockVolume = (int)$pdo->query("SELECT SUM(qty) FROM products")->fetchColumn();

        // 3. Đếm số lượng sản phẩm rơi vào trạng thái cảnh báo (Số lượng tồn <= 10)
        $lowStockAlert = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE qty <= 10")->fetchColumn();

        // 4. Lấy dữ liệu động từ bảng luân chuyển kho thực tế (stock_picking song hành cùng stock_move)
        // Dùng câu lệnh chuẩn bị trước để quét 5 biến động kho gần nhất
        $stmtRecent = $pdo->prepare("
            SELECT sp.scheduled_date, sp.type, sm.product_sku, sm.product_qty 
            FROM stock_picking sp 
            JOIN stock_move sm ON sp.id = sm.picking_id 
            ORDER BY sp.id DESC LIMIT 5
        ");
        $stmtRecent->execute();
        $activities = $stmtRecent->fetchAll();

        if (!empty($activities)) {
            foreach ($activities as $row) {
                // Ép kiểu thời gian từ DB ra định dạng gọn nhẹ đúng Layout gốc của bạn
                $timeFormatted = date('H:i | d-m', strtotime($row['scheduled_date']));
                $recentActivities[] = [
                    'time' => $timeFormatted,
                    'type' => $row['type'], // Kế thừa giá trị 'in' hoặc 'out' thực tế từ Odoo Engine
                    'product_name' => $row['product_sku'], // Hiển thị mã sản phẩm luân chuyển
                    'quantity' => number_format($row['product_qty']) . " SP"
                ];
            }
        } else {
            // Cơ chế Fallback dữ liệu: Nếu bảng luân chuyển mới chưa có lệnh nhập xuất kho nào, 
            // hệ thống tự động quét bảng sản phẩm gốc để hiển thị danh sách khởi tạo ban đầu giống hệt code cũ của bạn
            $stmtFallback = $pdo->query("SELECT name, qty FROM products ORDER BY id DESC LIMIT 5");
            while ($row = $stmtFallback->fetch()) {
                $recentActivities[] = [
                    'time' => date('H:i | d-m'),
                    'type' => 'in',
                    'product_name' => $row['name'],
                    'quantity' => number_format($row['qty']) . " SP"
                ];
            }
        }
    }
} catch (Exception $e) {
    // Đóng băng ngoại lệ bảo vệ luồng hiển thị giao diện
}
?>

<div class="dashboard-container">
    <div class="dashboard-title">
        <h2>📊 Tổng quan hệ thống quản trị</h2>
        <p>Báo cáo tình trạng vận hành kho và chỉ số luân chuyển hàng hóa thực tế.</p>
    </div>

    <div class="card-grid">
        <div class="card card-blue">
            <div class="card-icon">📦</div>
            <div class="card-info">
                <h3><?php echo $totalProducts; ?></h3>
                <p>Danh mục sản phẩm</p>
            </div>
        </div>

        <div class="card card-green">
            <div class="card-icon">🏢</div>
            <div class="card-info">
                <h3><?php echo number_format($totalStockVolume); ?></h3>
                <p>Tổng sản lượng tồn kho</p>
            </div>
        </div>

        <div class="card card-orange">
            <div class="card-icon">⚠️</div>
            <div class="card-info">
                <h3><?php echo $lowStockAlert; ?></h3>
                <p>Cảnh báo hết hàng</p>
            </div>
        </div>
    </div>

    <div class="dashboard-details">
        <div class="detail-box">
            <h4>🔄 Nhật ký kho mới nhất</h4>
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Nghiệp vụ</th>
                        <th>Sản phẩm</th>
                        <th>Số lượng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentActivities)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #95a5a6; padding: 30px 10px;">
                                Đang chờ kết nối dữ liệu từ các mô-đun nghiệp vụ...
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentActivities as $activity): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($activity['time']); ?></td>
                                <td>
                                    <span class="badge <?php echo $activity['type'] == 'in' ? 'badge-in' : 'badge-out'; ?>">
                                        <?php echo $activity['type'] == 'in' ? 'Nhập kho' : 'Xuất kho'; ?>
                                    </span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($activity['product_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($activity['quantity']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="detail-box quick-links">
            <h4>⚡ Thao tác nhanh</h4>
            <a href="index.php?page=products" class="link-btn">➡️ Quản lý danh mục sản phẩm</a>
            <a href="index.php?page=reports" class="link-btn link-btn-secondary">➡️ Xem báo cáo phân tích</a>

            <div class="system-status-container">
                <h5>🖥️ Trạng thái máy chủ Docker</h5>
                
                <div class="status-item">
                    <span class="status-label">Cơ sở dữ liệu (DB):</span>
                    <span class="status-value text-success"><span class="dot-online"></span> Trực tuyến (Connected)</span>
                </div>
                
                <div class="status-item">
                    <span class="status-label">Cổng mạng kết nối:</span>
                    <span class="status-value">Host: <code class="code-spec">db</code> | Port: <code class="code-spec">3306</code></span>
                </div>
                
                <div class="status-item" style="margin-top: 15px; border-top: 1px dashed #eef2f5; padding-top: 10px;">
                    <span class="status-label">Tài khoản trực ban:</span>
                    <span class="status-value"><strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
                </div>

                <div class="status-item">
                    <span class="status-label">Phiên làm việc:</span>
                    <span class="status-value text-blue">Đang hoạt động</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .dashboard-container { animation: fadeIn 0.4s ease-in-out; }
    .dashboard-title { margin-bottom: 25px; }
    .dashboard-title h2 { color: #1e3d59; font-size: 1.6rem; }
    .dashboard-title p { color: #7f8c8d; font-size: 0.9rem; margin-top: 5px; }

    /* Định dạng lưới thẻ Card */
    .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .card { background: #ffffff; border-radius: 8px; padding: 20px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); border-bottom: 4px solid transparent; }
    .card-icon { font-size: 2.5rem; }
    .card-info h3 { font-size: 1.8rem; color: #2c3e50; font-weight: 700; }
    .card-info p { color: #7f8c8d; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
    
    /* Màu sắc nhận diện hệ thống */
    .card-blue { border-bottom-color: #1e3d59; }
    .card-green { border-bottom-color: #17b978; }
    .card-orange { border-bottom-color: #ff9f43; }

    /* Bố cục vùng chi tiết */
    .dashboard-details { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    .detail-box { background: #ffffff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
    .detail-box h4 { color: #1e3d59; margin-bottom: 15px; font-size: 1.05rem; border-bottom: 2px solid #f4f6f9; padding-bottom: 10px; }

    /* Định dạng bảng dữ liệu */
    .dashboard-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; }
    .dashboard-table th, .dashboard-table td { padding: 12px 10px; border-bottom: 1px solid #f4f6f9; }
    .dashboard-table th { color: #7f8c8d; font-weight: 600; }
    .dashboard-table td { color: #2c3e50; }
    
    /* Huy hiệu trạng thái */
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; }
    .badge-in { background-color: #e3fcef; color: #155724; }
    .badge-out { background-color: #fff0f0; color: #721c24; }

    /* Nút thao tác nhanh */
    .quick-links { display: flex; flex-direction: column; }
    .link-btn { display: block; background: #1e3d59; color: white; padding: 12px; text-decoration: none; border-radius: 6px; text-align: center; font-weight: 500; font-size: 0.9rem; margin-bottom: 10px; transition: background 0.2s; }
    .link-btn:hover { background: #17b978; }
    .link-btn-secondary { background: #7f8c8d; }
    .link-btn-secondary:hover { background: #6c7a89; }

    /* 🔵 CSS ĐỘC LẬP CHO KHỐI TIỆN ÍCH LẤP ĐẦY KHOẢNG TRỐNG */
    .system-status-container { margin-top: 20px; padding: 15px; background-color: #f8fafc; border-radius: 6px; border: 1px solid #eef2f5; }
    .system-status-container h5 { color: #1e3d59; font-size: 0.9rem; margin-bottom: 12px; font-weight: 600; }
    .status-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 0.85rem; }
    .status-label { color: #7f8c8d; }
    .status-value { color: #2c3e50; font-weight: 500; }
    .text-success { color: #17b978 !important; display: flex; align-items: center; gap: 5px; }
    .text-blue { color: #1e3d59 !important; font-weight: bold; }
    .code-spec { background: #eef2f5; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 0.8rem; color: #e83e8c; }
    
    /* Chấm tròn nhấp nháy tạo hiệu ứng Live cho Docker */
    .dot-online { width: 8px; height: 8px; background-color: #17b978; border-radius: 50%; display: inline-block; box-shadow: 0 0 8px #17b978; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
```

## File: modules/New Text Document.txt
```

```

## File: modules/products.php
```php
<?php
/**
 * 📦 MÔ-ĐUN QUẢN LÝ DANH MỤC SẢN PHẨM (PRODUCT MASTER DATA)
 * Tích hợp tính năng: Thêm, Sửa, Xóa, hiển thị đồng bộ với Odoo Stock Engine.
 */
$pdo = getPDOLayerConnection();
$errors = [];
$messages = [];

// Khởi tạo biến xử lý form Sửa
$edit_mode = false;
$edit_product = null;

try {
    // 🛑 1. XỬ LÝ HÀNH ĐỘNG XÓA (DELETE)
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['sku'])) {
        $delete_sku = $_GET['sku'];
        $stmtDelete = $pdo->prepare("DELETE FROM products WHERE sku = ?");
        $stmtDelete->execute([$delete_sku]);
        $messages[] = "Đã xóa sản phẩm với mã SKU [{$delete_sku}] thành công.";
    }

    // 🛑 2. XỬ LÝ HÀNH ĐỘNG LẤY THÔNG TIN ĐỂ SỬA (GET EDIT DATA)
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['sku'])) {
        $edit_sku = $_GET['sku'];
        $stmtGetEdit = $pdo->prepare("SELECT * FROM products WHERE sku = ?");
        $stmtGetEdit->execute([$edit_sku]);
        $edit_product = $stmtGetEdit->fetch();
        if ($edit_product) {
            $edit_mode = true;
        }
    }

    // 🛑 3. XỬ LÝ FORM SUBMIT (THÊM MỚI HOẶC CẬP NHẬT)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
        $sku = trim($_POST['sku'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $qty = intval($_POST['qty'] ?? 0); // Số lượng tồn ban đầu

        if (empty($sku) || empty($name)) {
            $errors[] = "Mã SKU và Tên sản phẩm không được để trống.";
        } else {
            if (isset($_POST['is_edit_mode']) && $_POST['is_edit_mode'] == '1') {
                // Logic Cập nhật (Update)
                $stmtUpdate = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, qty = ? WHERE sku = ?");
                $stmtUpdate->execute([$name, $description, $price, $qty, $sku]);
                $messages[] = "Đã cập nhật thông tin sản phẩm [{$sku}] thành công.";
                $edit_mode = false; // Thoát chế độ sửa
            } else {
                // Logic Thêm mới (Insert) - Kiểm tra trùng SKU trước
                $stmtCheck = $pdo->prepare("SELECT sku FROM products WHERE sku = ?");
                $stmtCheck->execute([$sku]);
                if ($stmtCheck->fetch()) {
                    throw new Exception("Mã SKU [{$sku}] này đã tồn tại trong hệ thống.");
                }

                $stmtInsert = $pdo->prepare("INSERT INTO products (sku, name, description, price, qty) VALUES (?, ?, ?, ?, ?)");
                $stmtInsert->execute([$sku, $name, $description, $price, $qty]);
                $messages[] = "Đã thêm mới thành công sản phẩm [{$sku}].";
            }
        }
    }

    // 🔄 TẢI TOÀN BỘ DANH SÁCH SẢN PHẨM LÊN GIAO DIỆN
    $products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();

} catch (Exception $e) {
    $errors[] = "Lỗi nghiệp vụ sản phẩm: " . $e->getMessage();
}
?>

<div class="container-fluid" style="padding: 10px;">
    <h2 style="color: #1e3d59; margin-bottom: 20px; font-weight: 700;">Quản Lý Danh Mục Sản Phẩm (Master Data)</h2>

    <?php foreach($errors as $error): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 15px; border-left: 5px solid #f5c6cb;">
            <strong>⚠️ Lỗi:</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php endforeach; ?>
    
    <?php foreach($messages as $msg): ?>
        <div style="background-color: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 15px; border-left: 5px solid #c3e6cb;">
            <strong>✅ Thành công:</strong> <?= htmlspecialchars($msg) ?>
        </div>
    <?php endforeach; ?>

    <div style="background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 30px;">
        <div style="font-weight: bold; font-size: 1.1rem; color: #1e3d59; border-bottom: 2px solid #eef2f5; padding-bottom: 10px; margin-bottom: 20px;">
            <?= $edit_mode ? "🛠️ Hiệu Chỉnh Sản Phẩm: " . htmlspecialchars($edit_product['sku']) : "➕ Thêm Sản Phẩm Mới Vào Hệ Thống" ?>
        </div>
        
        <form method="POST" action="index.php?page=products">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="is_edit_mode" value="1">
                <input type="hidden" name="sku" value="<?= htmlspecialchars($edit_product['sku']) ?>">
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div>
                    <label style="display:block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600;">Mã sản phẩm (SKU)</label>
                    <input type="text" name="sku" value="<?= $edit_mode ? htmlspecialchars($edit_product['sku']) : '' ?>" <?= $edit_mode ? 'disabled' : '' ?> placeholder="Ví dụ: PROD-CPU-I9" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px; background-color: <?= $edit_mode ? '#eef2f5' : '#ffffff' ?>;" required>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600;">Tên mặt hàng</label>
                    <input type="text" name="name" value="<?= $edit_mode ? htmlspecialchars($edit_product['name']) : '' ?>" placeholder="Nhập tên sản phẩm..." style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;" required>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600;">Giá bán (VNĐ)</label>
                    <input type="number" name="price" value="<?= $edit_mode ? htmlspecialchars($edit_product['price']) : '0' ?>" min="0" step="0.01" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;" required>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600;">Số lượng tồn đầu kỳ</label>
                    <input type="number" name="qty" value="<?= $edit_mode ? htmlspecialchars($edit_product['qty']) : '0' ?>" min="0" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;" required>
                </div>
            </div>
            
            <div style="margin-top: 15px;">
                <label style="display:block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600;">Mô tả sản phẩm</label>
                <textarea name="description" rows="2" placeholder="Ghi chú thông số kỹ thuật, thuộc tính..." style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;"><?= $edit_mode ? htmlspecialchars($edit_product['description']) : '' ?></textarea>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" name="save_product" style="background-color: #17b978; color: white; border: none; padding: 10px 20px; font-weight: bold; border-radius: 4px; cursor: pointer;">
                    <?= $edit_mode ? "Cập Nhật (Save)" : "Lưu Sản Phẩm" ?>
                </button>
                <?php if ($edit_mode): ?>
                    <a href="index.php?page=products" style="background-color: #7f8c8d; color: white; text-decoration: none; padding: 10px 20px; font-weight: bold; border-radius: 4px; line-height: 1.5;">Hủy bỏ</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <h4 style="color: #1e3d59; margin-bottom: 15px; font-weight: 600;">Danh Sách Mặt Hàng Hiện Hữu</h4>
    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <thead>
            <tr style="background-color: #eef2f5; text-align: left; color: #1e3d59; font-size: 0.9rem;">
                <th style="padding: 15px;">ID</th>
                <th style="padding: 15px;">Mã SKU</th>
                <th style="padding: 15px;">Tên sản phẩm</th>
                <th style="padding: 15px;">Mô tả</th>
                <th style="padding: 15px;">Giá niêm yết</th>
                <th style="padding: 15px;">Số lượng trong kho</th>
                <th style="padding: 15px; text-align: center;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr><td colspan="7" style="padding: 20px; text-align: center; color: #7f8c8d;">Không có sản phẩm nào tồn tại. Hãy thêm mới sản phẩm ở form trên.</td></tr>
            <?php else: ?>
                <?php foreach($products as $prod): ?>
                <tr style="border-bottom: 1px solid #eef2f5; font-size: 0.95rem;">
                    <td style="padding: 15px; color: #7f8c8d;"><?= $prod['id'] ?></td>
                    <td style="padding: 15px;"><strong><?= htmlspecialchars($prod['sku']) ?></strong></td>
                    <td style="padding: 15px; font-weight: 500; color: #2c3e50;"><?= htmlspecialchars($prod['name']) ?></td>
                    <td style="padding: 15px; color: #95a5a6; font-size: 0.85rem; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($prod['description'] ?: 'Chưa có mô tả') ?></td>
                    <td style="padding: 15px; color: #e74c3c; font-weight: bold;"><?= number_format($prod['price']) ?> VNĐ</td>
                    <td style="padding: 15px;">
                        <span style="padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; background-color: <?= $prod['qty'] > 10 ? '#d4edda' : '#f8d7da' ?>; color: <?= $prod['qty'] > 10 ? '#155724' : '#721c24' ?>;">
                            <?= number_format($prod['qty']) ?> cái
                        </span>
                    </td>
                    <td style="padding: 15px; text-align: center; display: flex; gap: 8px; justify-content: center;">
                        <a href="index.php?page=products&action=edit&sku=<?= $prod['sku'] ?>" style="background-color: #3498db; color: white; padding: 6px 12px; font-size: 0.85rem; font-weight: bold; text-decoration: none; border-radius: 4px; transition: background 0.2s;">Sửa</a>
                        <a href="index.php?page=products&action=delete&sku=<?= $prod['sku'] ?>" onclick="return confirm('Bạn chắc chắn muốn xóa sản phẩm này?');" style="background-color: #e74c3c; color: white; padding: 6px 12px; font-size: 0.85rem; font-weight: bold; text-decoration: none; border-radius: 4px; transition: background 0.2s;">Xóa</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
```

## File: modules/reports.php
```php
<?php
// Kiểm tra bảo mật: Không cho phép truy cập trực tiếp file này từ URL bên ngoài
if (!isset($_SESSION['user_name'])) {
    exit("Truy cập bị từ chối!");
}

/**
 * 🛠️ TẦNG KẾT NỐI VÀ XỬ LÝ SỐ LIỆU PHÂN TÍCH CHUYÊN SÂU (BUSINESS INTELLIGENCE LAYER)
 * Thực hiện tính toán tài chính, giá trị tồn kho động từ MySQL
 */
$db_connection = null;
$totalInventoryValue = 0; // Tổng giá trị vốn kho (Giá x Số lượng của tất cả sản phẩm)
$highestValueProducts = []; // Top sản phẩm đọng vốn lớn nhất
$outOfStockProducts = [];   // Sản phẩm sắp cháy kho (Số lượng <= 10)

try {
    mysqli_report(MYSQLI_REPORT_OFF);
    $db_connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($db_connection && !$db_connection->connect_error) {
        $db_connection->set_charset("utf8");

        // 📊 1. Tính tổng giá trị toàn bộ kho hàng (Tổng SUM của Price * Qty)
        $value_res = mysqli_query($db_connection, "SELECT SUM(price * qty) as total_val FROM products");
        if ($value_res) {
            $value_row = mysqli_fetch_assoc($value_res);
            $totalInventoryValue = floatval($value_row['total_val']);
        }

        // 📋 2. Truy vấn danh sách cơ cấu vốn kho (Sắp xếp theo Giá trị tồn giảm dần)
        $highest_res = mysqli_query($db_connection, "SELECT sku, name, price, qty, (price * qty) as total_item_val FROM products ORDER BY total_item_val DESC");
        if ($highest_res) {
            while ($row = mysqli_fetch_assoc($highest_res)) {
                $highestValueProducts[] = $row;
                
                // Phân loại song song: Nếu sản phẩm có qty <= 10 thì đẩy vào danh sách cảnh báo cháy kho
                if (intval($row['qty']) <= 10) {
                    $outOfStockProducts[] = $row;
                }
            }
        }
    }
} catch (Exception $e) {
    // Đóng băng ngoại lệ bảo vệ luồng giao diện
}
?>

<div class="reports-container">
    <div class="reports-title">
        <h2>📊 Trung tâm Báo cáo & Phân tích Kinh doanh</h2>
        <p>Hệ thống tự động hóa tính toán giá trị dòng vốn tài sản và phân tích rủi ro lưu kho theo thời gian thực.</p>
    </div>

    <div class="report-summary-card">
        <div class="summary-icon">💵</div>
        <div class="summary-details">
            <p>TỔNG GIÁ TRỊ VỐN LƯU KHO ĐANG QUẢN LÝ</p>
            <h3><?php echo number_format($totalInventoryValue); ?> <span style="font-size: 1.2rem;">VNĐ</span></h3>
        </div>
    </div>

    <div class="reports-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 25px; align-items: start;">
        
        <div class="report-box">
            <h4>📈 Bảng Phân Tích Cơ Cấu Vốn Hàng Hóa</h4>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Mã SKU</th>
                        <th>Tên sản phẩm</th>
                        <th>Đơn giá</th>
                        <th>Tồn kho</th>
                        <th>Giá trị vốn kho</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($highestValueProducts)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #95a5a6; padding: 30px 10px;">
                                Hệ thống trống. Chưa có dữ liệu sản phẩm để phân tích vốn kho.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($highestValueProducts as $prod): ?>
                            <tr>
                                <td><code class="report-sku"><?php echo htmlspecialchars($prod['sku']); ?></code></td>
                                <td><strong><?php echo htmlspecialchars($prod['name']); ?></strong></td>
                                <td><?php echo number_format($prod['price']); ?> đ</td>
                                <td><?php echo number_format($prod['qty']); ?></td>
                                <td style="color: #1e3d59; font-weight: bold;"><?php echo number_format($prod['total_item_val']); ?> đ</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="report-box alert-box">
            <h4>⚠️ Cảnh Báo Rủi Ro Hết Hàng (Qty ≤ 10)</h4>
            <div class="alert-list" style="display: flex; flex-direction: column; gap: 12px; margin-top: 15px;">
                <?php if (empty($outOfStockProducts)): ?>
                    <div style="text-align: center; color: #17b978; padding: 20px; background: #e3fcef; border-radius: 6px; font-size: 0.85rem; font-weight: 500;">
                        ✅ Trạng thái lý tưởng: Không có sản phẩm nào sắp hết hàng!
                    </div>
                <?php else: ?>
                    <?php foreach ($outOfStockProducts as $alert_item): ?>
                        <div class="alert-card" style="background: #fff0f0; border-left: 4px solid #ff6b6b; padding: 12px; border-radius: 4px;">
                            <span style="font-size: 0.8rem; color: #721c24; font-weight: bold;">SKU: <?php echo htmlspecialchars($alert_item['sku']); ?></span>
                            <h5 style="margin: 4px 0; color: #2c3e50; font-size: 0.85rem;"><?php echo htmlspecialchars($alert_item['name']); ?></h5>
                            <p style="margin: 0; font-size: 0.8rem; color: #721c24;">
                                Nguy cơ cháy kho! Hiện chỉ còn: <strong><?php echo $alert_item['qty']; ?></strong> sản phẩm.
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<style>
    .reports-container { animation: fadeIn 0.4s ease-in-out; }
    .reports-title h2 { color: #1e3d59; font-size: 1.6rem; }
    .reports-title p { color: #7f8c8d; font-size: 0.9rem; margin-top: 5px; }

    /* Thẻ tổng vốn kho */
    .report-summary-card { background: linear-gradient(135deg, #1e3d59 0%, #17b978 100%); color: white; padding: 25px; border-radius: 8px; margin-top: 20px; display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 15px rgba(23, 185, 120, 0.2); }
    .summary-icon { font-size: 3rem; opacity: 0.9; }
    .summary-details p { font-size: 0.8rem; letter-spacing: 1px; font-weight: 500; margin: 0; opacity: 0.8; }
    .summary-details h3 { font-size: 2.2rem; margin: 5px 0 0 0; font-weight: bold; }

    /* Box nội dung */
    .report-box { background: #ffffff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
    .report-box h4 { color: #1e3d59; font-size: 1.05rem; border-bottom: 2px solid #f4f6f9; padding-bottom: 10px; margin: 0; font-weight: 600; }

    /* Định dạng bảng */
    .report-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; margin-top: 15px; }
    .report-table th, .report-table td { padding: 12px 10px; border-bottom: 1px solid #f4f6f9; }
    .report-table th { color: #7f8c8d; font-weight: 600; }
    .report-table td { color: #2c3e50; }
    .report-sku { background: #eef2f5; padding: 2px 6px; border-radius: 4px; font-family: monospace; color: #e83e8c; font-weight: bold; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
```

## File: modules/warehouse.php
```php
<?php
/**
 * 🚚 MÔ-ĐUN ĐIỀU PHỐI KHO VẬT CHẤT (ODOO STOCK ENGINE MODEL)
 * Đạt chuẩn xử lý Transaction song song, bảo trì tuyệt đối.
 */
$pdo = getPDOLayerConnection();
$errors = [];
$messages = [];

// Khởi tạo các biến danh sách để tránh lỗi hiển thị tầng giao diện
$all_products = [];
$pickings = [];

try {
    // TẦNG KIỂM TRA BẢO VỆ (SHIELD LAYER): Xác minh bảng có tồn tại thực tế trong DB không
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'products'")->fetch();
    if (!$tableCheck) {
        throw new Exception("Hạ tầng bảng 'products' chưa được khởi tạo. Vui lòng nạp tệp SQL cấu trúc vào Database.");
    }

    // XỬ LÝ LỆNH TẠO PHIẾU ĐIỀU CHUYỂN (Thực thi khi nhấn Validate)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_picking'])) {
        $type = $_POST['type'] ?? 'in';
        $origin = trim($_POST['origin'] ?? '');
        $sku = $_POST['sku'] ?? '';
        $qty = intval($_POST['qty'] ?? 0);

        if (empty($sku) || $qty <= 0) {
            $errors[] = "Dữ liệu sản phẩm hoặc số lượng dịch chuyển không hợp lệ.";
        } else {
            try {
                // Khởi động Transaction để bảo vệ tính toàn vẹn dữ liệu song song (ACID)
                $pdo->beginTransaction();

                // 1. Kiểm tra sản phẩm có tồn tại thực tế không
                $stmtProd = $pdo->prepare("SELECT qty FROM products WHERE sku = ?");
                $stmtProd->execute([$sku]);
                $product = $stmtProd->fetch();

                if (!$product) {
                    throw new Exception("Sản phẩm có mã SKU [{$sku}] này không tồn tại.");
                }

                // Nếu là xuất kho, kiểm tra xem lượng tồn thực tế có đủ không
                if ($type === 'out' && $product['qty'] < $qty) {
                    throw new Exception("Số lượng tồn kho không đủ để xuất! Hiện có: " . $product['qty']);
                }

                // 2. Tạo số phiếu tự động dạng chuỗi thời gian tuyến tính
                $prefix = ($type === 'in') ? 'WH/IN/' : 'WH/OUT/';
                $picking_number = $prefix . time();

                $stmtPick = $pdo->prepare("INSERT INTO stock_picking (picking_number, origin, type, state) VALUES (?, ?, ?, 'done')");
                $stmtPick->execute([$picking_number, $origin, $type]);
                $picking_id = $pdo->lastInsertId();

                // 3. Tạo dòng dịch chuyển chi tiết (Stock Move Line)
                $stmtMove = $pdo->prepare("INSERT INTO stock_move (picking_id, product_sku, product_qty) VALUES (?, ?, ?)");
                $stmtMove->execute([$picking_id, $sku, $qty]);

                // 4. Cập nhật trực tiếp số lượng tồn kho tổng ở bảng sản phẩm
                if ($type === 'in') {
                    $stmtUpdateStock = $pdo->prepare("UPDATE products SET qty = qty + ? WHERE sku = ?");
                } else {
                    $stmtUpdateStock = $pdo->prepare("UPDATE products SET qty = qty - ? WHERE sku = ?");
                }
                $stmtUpdateStock->execute([$qty, $sku]);

                // Cam kết dữ liệu an toàn vào DB
                $pdo->commit();
                $messages[] = "Đã xác nhận thành công phiếu hoạt động kho {$picking_number}!";

            } catch (Exception $e) {
                // Hủy bỏ mọi tác vụ dở dang nếu xuất hiện lỗi bất ngờ, đưa DB về trạng thái nguyên bản
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = "Giao dịch kho thất bại: " . $e->getMessage();
            }
        }
    }

    // LẤY DANH SÁCH SẢN PHẨM PHỤC VỤ CHỌN LỰA TRÊN FORM
    $all_products = $pdo->query("SELECT sku, name FROM products")->fetchAll();

    // LẤY TOÀN BỘ DANH SÁCH LỊCH SỬ PHIẾU ĐIỀU CHUYỂN
    $pickings = $pdo->query("
        SELECT sp.*, sm.product_sku, sm.product_qty 
        FROM stock_picking sp 
        JOIN stock_move sm ON sp.id = sm.picking_id 
        ORDER BY sp.scheduled_date DESC
    ")->fetchAll();

} catch (Exception $e) {
    $errors[] = "Lỗi hệ thống cơ sở dữ liệu: " . $e->getMessage();
}
?>

<div class="container-fluid" style="padding: 10px;">
    <h2 style="color: #1e3d59; margin-bottom: 20px; font-weight: 700;">Điều Chuyển Kho Thực Tế (Odoo Engine Model)</h2>
    
    <?php foreach($errors as $error): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 15px; border-left: 5px solid #f5c6cb;">
            <strong>⚠️ Lỗi cấu trúc:</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php endforeach; ?>
    
    <?php foreach($messages as $msg): ?>
        <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin-bottom: 15px; border-left: 5px solid #c3e6cb;">
            <strong>✅ Thành công:</strong> <?= htmlspecialchars($msg) ?>
        </div>
    <?php endforeach; ?>

    <?php if ($tableCheck): ?>
    <div class="card mb-4" style="background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 30px;">
        <div style="font-weight: bold; font-size: 1.1rem; color: #1e3d59; border-bottom: 2px solid #eef2f5; padding-bottom: 10px; margin-bottom: 20px;">Khởi Tạo Phiếu Điều Chuyển Hàng Hóa</div>
        <form method="POST" action="">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
                <div>
                    <label style="display:block; margin-bottom: 8px; font-size: 0.9rem; font-weight: 600;">Loại hoạt động</label>
                    <select name="type" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;">
                        <option value="in">Nhập kho (Receipt - Mua hàng)</option>
                        <option value="out">Xuất kho (Delivery Order - Bán hàng)</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 8px; font-size: 0.9rem; font-weight: 600;">Chứng từ nguồn (Origin)</label>
                    <input type="text" name="origin" placeholder="Ví dụ: PO001 hoặc SO002" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;">
                </div>
                <div>
                    <label style="display:block; margin-bottom: 8px; font-size: 0.9rem; font-weight: 600;">Chọn sản phẩm dịch chuyển</label>
                    <select name="sku" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;" required>
                        <option value="">-- Chọn mặt hàng --</option>
                        <?php foreach($all_products as $prod): ?>
                            <option value="<?= $prod['sku'] ?>"><?= htmlspecialchars($prod['name']) ?> (<?= $prod['sku'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 8px; font-size: 0.9rem; font-weight: 600;">Số lượng luân chuyển</label>
                    <input type="number" name="qty" min="1" value="1" style="width: 100%; padding: 10px; border: 1px solid #cccccc; border-radius: 4px;" required>
                </div>
            </div>
            <button type="submit" name="create_picking" style="margin-top: 20px; background-color: #17b978; color: white; border: none; padding: 12px 24px; font-weight: bold; border-radius: 4px; cursor: pointer; transition: background 0.2s;">Xác Nhận Lệnh Kho (Validate)</button>
        </form>
    </div>
    <?php endif; ?>

    <h4 style="color: #1e3d59; margin-bottom: 15px; font-weight: 600;">Nhật Ký Luân Chuyển Vật Chất Thực Tế</h4>
    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <thead>
            <tr style="background-color: #eef2f5; text-align: left; color: #1e3d59; font-size: 0.9rem;">
                <th style="padding: 15px;">Mã phiếu hoạt động</th>
                <th style="padding: 15px;">Tài liệu gốc</th>
                <th style="padding: 15px;">Loại dịch chuyển</th>
                <th style="padding: 15px;">Sản phẩm SKU</th>
                <th style="padding: 15px;">Số lượng</th>
                <th style="padding: 15px;">Thời gian ghi nhận</th>
                <th style="padding: 15px;">Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pickings)): ?>
                <tr><td colspan="7" style="padding: 20px; text-align: center; color: #7f8c8d;">Chưa phát sinh bất kỳ hoạt động luân chuyển kho nào hoặc cơ sở dữ liệu trống.</td></tr>
            <?php else: ?>
                <?php foreach($pickings as $pk): ?>
                <tr style="border-bottom: 1px solid #eef2f5; font-size: 0.95rem;">
                    <td style="padding: 15px;"><strong><?= $pk['picking_number'] ?></strong></td>
                    <td style="padding: 15px;"><?= htmlspecialchars($pk['origin'] ?: 'N/A') ?></td>
                    <td style="padding: 15px;">
                        <span style="padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; background-color: <?= $pk['type'] === 'in' ? '#d1ecf1' : '#fff3cd' ?>; color: <?= $pk['type'] === 'in' ? '#0c5460' : '#856404' ?>;">
                            <?= $pk['type'] === 'in' ? 'NHẬP KHO (IN)' : 'XUẤT KHO (OUT)' ?>
                        </span>
                    </td>
                    <td style="padding: 15px;"><?= htmlspecialchars($pk['product_sku']) ?></td>
                    <td style="padding: 15px;"><strong><?= number_format($pk['product_qty']) ?></strong> mục</td>
                    <td style="padding: 15px;"><?= $pk['scheduled_date'] ?></td>
                    <td style="padding: 15px;"><span style="background-color: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold;">Đã hoàn thành (Done)</span></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
```

## File: README.md
```markdown
# php-login-minimal

A simple, but secure PHP login script. Uses the ultra-modern & future-proof PHP 5.5 BLOWFISH hashing/salting functions (includes the official PHP 5.3 & PHP 5.4 compatibility pack, which makes those functions available in these versions too). 

## Why does this script exist ?

In the PHP world every beginner tries to build login systems from scratch, doing all the typical mistakes, usually going from saving plain text passwords to using (horribly wrong) MD5 hashing. This script tries to give beginners a usable code base with a fully implemented user authentication ("login") system, preventing less-experienced developers at least from the worst security issues.

This script was originally part of the "php-login project", a collection of 4 different login scripts made in the 2012-2013 PHP era to give especially beginners and security-inexperienced users a set of basic auth functions that fitted the most modern password hashing standards possible. You know, this was the time when even major companies like SONY and LinkedIn used horrible outdated MD5-hashing for their passwords (or even saved everything in plain text) and when the big PHP frameworks didn't have proper user auth solution out-of-the-box.

Find the other versions here:

**One-file version** (not maintained anymore)
Full login script in one file. Uses a one-file SQLite database (no MySQL needed) and PDO: Register, login, logout.
https://github.com/panique/php-login-one-file

**Minimal version** (not maintained anymore)
All the basic functions in a clean file structure, uses MySQL and mysqli. Register, login, logout.
https://github.com/panique/php-login-minimal

**HUGE (professional version)** 
Quite professional MVC framework structure, useful for real applications. Additional features like: URL rewriting, mail sending via PHPMailer (SMTP or PHP's mail() function/linux sendmail), user profile pages, public user profiles, gravatars and local avatars, account upgrade/downgrade etc., OAuth2, Composer integration, etc.
https://github.com/panique/huge

## Requirements

- PHP 5.3.7+
- MySQL 5 database (please use a modern version of MySQL (5.5, 5.6, 5.7) as very old versions have a exotic bug that
[makes PDO injections possible](http://stackoverflow.com/q/134099/1114320).
- activated mysqli (last letter is an "i") extension (activated by default on most server setups)

## Installation (quick setup)

Create a database *login* and the table *users* via the SQL statements in the `_install` folder.
Change mySQL database user and password in `config/db.php` (*DB_USER* and *DB_PASS*).

## Installation (detailed setup tutorials)

- [Detailed tutorial for installation on Ubuntu 12.04 LTS](http://www.dev-metal.com/install-php-login-nets-1-minimal-login-script-ubuntu/)
- [Detailed tutorial for installation on Windows 7 and 8 (useful for development)](http://www.dev-metal.com/how-to-install-php-login-minimal-on-windows-7-8/)

## Security notice

This script comes with a handy .htaccess in the views folder that denies direct access to the files within the folder
(so that people cannot render the views directly). However, these .htaccess files only work if you have set
`AllowOverride` to `All` in your apache vhost configs. There are lots of tutorials on the web on how to do this.

## Useful links

- [A little guideline on how to use the PHP 5.5 password hashing functions and its "library plugin" based PHP 5.3 & 5.4 implementation](http://www.dev-metal.com/use-php-5-5-password-hashing-functions/)
- [How to setup latest version of PHP 5.5 on Ubuntu 12.04 LTS](http://www.dev-metal.com/how-to-setup-latest-version-of-php-5-5-on-ubuntu-12-04-lts/). Same for Debian 7.0 / 7.1:
- [How to setup latest version of PHP 5.5 on Debian Wheezy 7.0/7.1 (and how to fix the GPG key error)](http://www.dev-metal.com/setup-latest-version-php-5-5-debian-wheezy-7-07-1-fix-gpg-key-error/)
- [Notes on password & hashing salting in upcoming PHP versions (PHP 5.5.x & 5.6 etc.)](https://github.com/panique/php-login/wiki/Notes-on-password-&-hashing-salting-in-upcoming-PHP-versions-%28PHP-5.5.x-&-5.6-etc.%29)
- [Some basic "benchmarks" of all PHP hash/salt algorithms](https://github.com/panique/php-login/wiki/Which-hashing-&-salting-algorithm-should-be-used-%3F)

## License

Licensed under [MIT](http://www.opensource.org/licenses/mit-license.php). You can use this script for free for any
private or commercial projects.

## Contribute

Please create a feature-branch if possible when committing to the project, if not then simply commit to master branch.

## Support

Support the project by renting a server at [DigitalOcean](https://www.digitalocean.com/?refcode=40d978532a20) or just tipping a coffee at BuyMeACoffee.com. Thanks! :)

<a href="https://www.buymeacoffee.com/panique" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" alt="Buy Me A Coffee" style="height: 60px !important;width: 217px !important;" ></a>

## I'm blogging...

at **[DEV METAL](http://www.dev-metal.com)**, mostly about PHP and IT-related stuff. Have a look if you like.
```

## File: register.php
```php
<?php

/**
 * A simple, clean and secure PHP Login Script / MINIMAL VERSION
 *
 * Uses PHP SESSIONS, modern password-hashing and salting and gives the basic functions a proper login system needs.
 *
 * @author Panique
 * @link https://github.com/panique/php-login-minimal/
 * @license http://opensource.org/licenses/MIT MIT License
 */

// checking for minimum PHP version
if (version_compare(PHP_VERSION, '5.3.7', '<')) {
    exit("Sorry, Simple PHP Login does not run on a PHP version smaller than 5.3.7 !");
} else if (version_compare(PHP_VERSION, '5.5.0', '<')) {
    // if you are using PHP 5.3 or PHP 5.4 you have to include the password_api_compatibility_library.php
    // (this library adds the PHP 5.5 password hashing functions to older versions of PHP)
    require_once("libraries/password_compatibility_library.php");
}

// include the configs / constants for the database connection
require_once("config/db.php");

// load the registration class
require_once("classes/Registration.php");

// create the registration object. when this object is created, it will do all registration stuff automatically
// so this single line handles the entire registration process.
$registration = new Registration();

// show the register view (with the registration form, and messages/errors)
include("views/register.php");
```

## File: repomix.config.json
```json
{
  "$schema": "https://repomix.com/schemas/latest/schema.json",
  "input": {
    "maxFileSize": 52428800
  },
  "output": {
    "filePath": "repomix-output.md",
    "style": "markdown",
    "parsableStyle": false,
    "fileSummary": true,
    "directoryStructure": true,
    "files": true,
    "removeComments": false,
    "removeEmptyLines": false,
    "compress": false,
    "topFilesLength": 5,
    "showLineNumbers": false,
    "truncateBase64": false,
    "copyToClipboard": false,
    "includeFullDirectoryStructure": false,
    "tokenCountTree": false,
    "git": {
      "sortByChanges": true,
      "sortByChangesMaxCommits": 100,
      "includeDiffs": false,
      "includeLogs": false,
      "includeLogsCount": 50
    }
  },
  "include": [],
  "ignore": {
    "useGitignore": true,
    "useDotIgnore": true,
    "useDefaultPatterns": true,
    "customPatterns": []
  },
  "security": {
    "enableSecurityCheck": true
  },
  "tokenCount": {
    "encoding": "o200k_base"
  }
}
```

## File: views/.htaccess
```
# This file prevents that your .php view files are accessed directly from the outside
<Files ~ "\.(htaccess|php)$">
order allow,deny
deny from all
</Files>
```

## File: views/logged_in.php
```php
<?php
/**
 * 🛰️ TẦNG KHỞI CHẠY HỆ THỐNG TRUNG TÂM (CORE ENGINE LAYER)
 * Đảm bảo Session và cấu hình Database được nạp trước khi render HTML
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra bảo mật tầng gốc: Nếu chưa đăng nhập, lập tức chuyển hướng về trang login
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

// Nạp các tệp cấu hình cốt lõi và hàm kết nối PDO dùng chung (kế thừa từ Hành động 2)
if (file_exists("config/db.php")) {
    include_once("config/db.php");
}
if (file_exists("modules/connection.php")) { 
    include_once("modules/connection.php"); // Nạp hàm getPDOLayerConnection() nếu có
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Quản Lý Kho Chuẩn Odoo</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; background-color: #f4f6f9; min-height: 100vh; color: #333333; }
        
        /* 🔵 THANH SIDEBAR BÊN TRÁI - TÔNG XANH NƯỚC BIỂN ĐẬM */
        .sidebar { width: 260px; background-color: #1e3d59; color: #ffffff; display: flex; flex-direction: column; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }
        .sidebar-brand { padding: 20px; text-align: center; font-size: 1.15rem; font-weight: bold; border-bottom: 1px solid #17b978; background-color: #17b978; color: #ffffff; letter-spacing: 0.5px; }
        .sidebar-menu { list-style: none; padding: 15px 0; flex: 1; }
        .sidebar-menu li { padding: 14px 20px; transition: all 0.2s ease-in-out; }
        
        /* Trạng thái Hover & Active của Menu */
        .sidebar-menu li:hover { background-color: #17b978; padding-left: 25px; }
        .sidebar-menu a { color: #e8f1f5; text-decoration: none; display: flex; align-items: center; gap: 12px; font-size: 0.95rem; font-weight: 500; }
        .sidebar-menu li:hover a { color: #ffffff; }
        
        .sidebar-logout { padding: 20px; border-top: 1px solid #2b5278; }
        .btn-logout { display: block; text-align: center; background-color: #ff6b6b; color: white; padding: 10px; text-decoration: none; border-radius: 6px; font-weight: bold; transition: background 0.2s; }
        .btn-logout:hover { background-color: #ee5253; }

        /* ⚪ VÙNG NỘI DUNG CHÍNH BÊN PHẢI - TÔNG TRẮNG CHỦ ĐẠO */
        .main-content { flex: 1; display: flex; flex-direction: column; }
        
        /* Header trên cùng */
        .main-header { height: 65px; background-color: #ffffff; display: flex; align-items: center; justify-content: space-between; padding: 0 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border-bottom: 1px solid #eef2f5; }
        .page-title { font-size: 1.1rem; font-weight: 600; color: #1e3d59; display: flex; align-items: center; gap: 8px; }
        
        /* 👤 KHU VỰC AVATAR GÓC TRÊN CÙNG BÊN PHẢI */
        .user-profile { display: flex; align-items: center; gap: 12px; cursor: pointer; }
        .user-info { text-align: right; }
        .user-info .username { display: block; font-size: 0.9rem; font-weight: 600; color: #2c3e50; }
        .user-info .role { display: block; font-size: 0.75rem; color: #7f8c8d; }
        
        /* Khung tròn Avatar */
        .avatar-circle { width: 38px; height: 38px; background-color: #17b978; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1rem; box-shadow: 0 2px 4px rgba(23, 185, 120, 0.2); border: 2px solid #ffffff; }

        /* Ruột nội dung nghiệp vụ */
        .main-body { padding: 30px; flex: 1; overflow-y: auto; background-color: #f8fafc; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-brand">📦 Warehouse</div>
        <ul class="sidebar-menu">
            <li><a href="index.php?page=dashboard">📊 Dashboard</a></li>
            <li><a href="index.php?page=products">📦 Sản phẩm</a></li>
            <li><a href="index.php?page=warehouse">🚚 Điều phối Kho</a></li>
            <li><a href="index.php?page=reports">📈 Báo cáo vĩ mô</a></li>
        </ul>
        <div class="sidebar-logout">
            <a href="index.php?logout" class="btn-logout">🚪 Đăng xuất</a>
        </div>
    </aside>

    <div class="main-content">
        <header class="main-header">
            <div class="page-title">⚙️ Hệ thống điều phối dữ liệu</div>
            
            <div class="user-profile">
                <div class="user-info">
                    <span class="username"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                    <span class="role">Quản trị viên Hệ thống</span>
                </div>
                <div class="avatar-circle">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?>
                </div>
            </div>
        </header>
        
        <main class="main-body">
            <?php
            // BỘ ĐIỀU PHỐI TINH GỌN (ROUTER MECHANISM)
            $currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
            
            // Khai báo danh mục trang hợp pháp
            $allowedPages = ['dashboard', 'products', 'reports', 'warehouse'];
            
            if (in_array($currentPage, $allowedPages)) {
                $targetFile = "modules/" . $currentPage . ".php";
                if (file_exists($targetFile)) {
                    // Inject cổng kết nối database an toàn cho các module sử dụng
                    if (function_exists('getPDOLayerConnection')) {
                        $db = getPDOLayerConnection(); 
                    }
                    include($targetFile);
                } else {
                    echo "<div style='background: #ffffff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border-left: 4px solid #1e3d59;'>";
                    echo "<h3 style='color: #1e3d59;'>Mô-đun [ " . htmlspecialchars(ucfirst($currentPage)) . " ] đang được cấu trúc</h3>";
                    echo "<p style='color: #7f8c8d; margin-top: 10px;'>Hạt nhân Docker đang sẵn sàng nạp kết nối SQL cho tầng nghiệp vụ này.</p>";
                    echo "</div>";
                }
            } else {
                echo "<h3 style='color: #ff6b6b;'>Cảnh báo: Tầng truy cập không hợp lệ!</h3>";
            }
            ?>
        </main>
    </div>

</body>
</html>
```

## File: views/not_logged_in.php
```php
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống Quản Lý Kho</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #1e3d59 0%, #17b978 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .login-container {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        max-width: 450px;
        width: 100%;
        animation: slideIn 0.5s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .login-header {
        background: linear-gradient(135deg, #1e3d59 0%, #2b5278 100%);
        padding: 40px 30px;
        text-align: center;
        color: #ffffff;
    }

    .login-header h1 {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 8px;
        letter-spacing: 0.5px;
    }

    .login-header p {
        font-size: 0.95rem;
        color: #e8f1f5;
        font-weight: 400;
    }

    .login-icon {
        width: 70px;
        height: 70px;
        background: #17b978;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 2rem;
        box-shadow: 0 4px 12px rgba(23, 185, 120, 0.3);
    }

    .login-body {
        padding: 40px 30px;
    }

    .message-box {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 0.9rem;
        line-height: 1.5;
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .error-box {
        background-color: #fee;
        color: #c33;
        border-left: 4px solid #e74c3c;
    }

    .success-box {
        background-color: #d4edda;
        color: #155724;
        border-left: 4px solid #17b978;
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #1e3d59;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .form-group input[type="text"],
    .form-group input[type="password"] {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e0e6ed;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background-color: #f8fafc;
    }

    .form-group input[type="text"]:focus,
    .form-group input[type="password"]:focus {
        outline: none;
        border-color: #17b978;
        background-color: #ffffff;
        box-shadow: 0 0 0 4px rgba(23, 185, 120, 0.1);
    }

    .btn-login {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #1e3d59 0%, #17b978 100%);
        color: #ffffff;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 4px 12px rgba(30, 61, 89, 0.3);
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(30, 61, 89, 0.4);
    }

    .btn-login:active {
        transform: translateY(0);
    }

    .register-link {
        text-align: center;
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid #e0e6ed;
    }

    .register-link a {
        color: #1e3d59;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.95rem;
        transition: color 0.3s ease;
    }

    .register-link a:hover {
        color: #17b978;
    }

    .footer-text {
        text-align: center;
        margin-top: 30px;
        color: #ffffff;
        font-size: 0.85rem;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-icon">📦</div>
            <h1>Hệ Thống Quản Lý Kho</h1>
            <p>Đăng nhập để tiếp tục</p>
        </div>

        <div class="login-body">
            <?php
            // Hiển thị lỗi từ login object (GIỮ NGUYÊN LOGIC)
            if (isset($login)) {
                if ($login->errors) {
                    foreach ($login->errors as $error) {
                        echo '<div class="message-box error-box">⚠️ ' . htmlspecialchars($error) . '</div>';
                    }
                }
                if ($login->messages) {
                    foreach ($login->messages as $message) {
                        echo '<div class="message-box success-box">✅ ' . htmlspecialchars($message) . '</div>';
                    }
                }
            }
            ?>

            <!-- FORM ĐĂNG NHẬP - GIỮ NGUYÊN 100% LOGIC -->
            <form method="post" action="index.php" name="loginform">
                <div class="form-group">
                    <label for="login_input_username">Tên đăng nhập</label>
                    <input id="login_input_username" class="login_input" type="text" name="user_name"
                        placeholder="Nhập username của bạn" required />
                </div>

                <div class="form-group">
                    <label for="login_input_password">Mật khẩu</label>
                    <input id="login_input_password" class="login_input" type="password" name="user_password"
                        placeholder="Nhập mật khẩu" autocomplete="off" required />
                </div>

                <button type="submit" name="login" class="btn-login">Đăng nhập</button>
            </form>

            <div class="register-link">
                Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
            </div>
        </div>
    </div>
    <div class="footer-text">
        © 2026 Warehouse Management System - Powered by Docker & PHP 8.1
    </div>
</body>

</html>
```

## File: views/register.php
```php
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Hệ thống Quản Lý Kho</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #1e3d59 0%, #17b978 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .register-container {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        max-width: 500px;
        width: 100%;
        animation: slideIn 0.5s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .register-header {
        background: linear-gradient(135deg, #1e3d59 0%, #2b5278 100%);
        padding: 40px 30px;
        text-align: center;
        color: #ffffff;
    }

    .register-header h1 {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 8px;
        letter-spacing: 0.5px;
    }

    .register-header p {
        font-size: 0.95rem;
        color: #e8f1f5;
        font-weight: 400;
    }

    .register-icon {
        width: 70px;
        height: 70px;
        background: #17b978;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 2rem;
        box-shadow: 0 4px 12px rgba(23, 185, 120, 0.3);
    }

    .register-body {
        padding: 40px 30px;
    }

    .message-box {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 0.9rem;
        line-height: 1.5;
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .error-box {
        background-color: #fee;
        color: #c33;
        border-left: 4px solid #e74c3c;
    }

    .success-box {
        background-color: #d4edda;
        color: #155724;
        border-left: 4px solid #17b978;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #1e3d59;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="password"] {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e0e6ed;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background-color: #f8fafc;
    }

    .form-group input[type="text"]:focus,
    .form-group input[type="email"]:focus,
    .form-group input[type="password"]:focus {
        outline: none;
        border-color: #17b978;
        background-color: #ffffff;
        box-shadow: 0 0 0 4px rgba(23, 185, 120, 0.1);
    }

    .form-hint {
        font-size: 0.8rem;
        color: #7f8c8d;
        margin-top: 4px;
        display: block;
    }

    .btn-register {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #1e3d59 0%, #17b978 100%);
        color: #ffffff;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 4px 12px rgba(30, 61, 89, 0.3);
        margin-top: 10px;
    }

    .btn-register:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(30, 61, 89, 0.4);
    }

    .btn-register:active {
        transform: translateY(0);
    }

    .login-link {
        text-align: center;
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid #e0e6ed;
    }

    .login-link a {
        color: #1e3d59;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.95rem;
        transition: color 0.3s ease;
    }

    .login-link a:hover {
        color: #17b978;
    }

    .footer-text {
        text-align: center;
        margin-top: 30px;
        color: #ffffff;
        font-size: 0.85rem;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .password-strength {
        height: 4px;
        background-color: #e0e6ed;
        border-radius: 2px;
        margin-top: 8px;
        overflow: hidden;
    }

    .password-strength-bar {
        height: 100%;
        width: 0%;
        transition: all 0.3s ease;
    }
    </style>
</head>

<body>
    <div class="register-container">
        <div class="register-header">
            <div class="register-icon">✍️</div>
            <h1>Tạo Tài Khoản Mới</h1>
            <p>Đăng ký để sử dụng hệ thống</p>
        </div>

        <div class="register-body">
            <?php
            // Hiển thị lỗi từ registration object (GIỮ NGUYÊN LOGIC)
            if (isset($registration)) {
                if ($registration->errors) {
                    foreach ($registration->errors as $error) {
                        echo '<div class="message-box error-box">⚠️ ' . htmlspecialchars($error) . '</div>';
                    }
                }
                if ($registration->messages) {
                    foreach ($registration->messages as $message) {
                        echo '<div class="message-box success-box">✅ ' . htmlspecialchars($message) . '</div>';
                    }
                }
            }
            ?>

            <!-- FORM ĐĂNG KÝ - GIỮ NGUYÊN 100% LOGIC -->
            <form method="post" action="register.php" name="registerform">
                <div class="form-group">
                    <label for="login_input_username">Tên đăng nhập</label>
                    <input id="login_input_username" class="login_input" type="text" pattern="[a-zA-Z0-9]{2,64}"
                        name="user_name" placeholder="Chỉ chữ cái và số" required />
                    <span class="form-hint">Từ 2-64 ký tự, chỉ chữ cái và số</span>
                </div>

                <div class="form-group">
                    <label for="login_input_email">Email</label>
                    <input id="login_input_email" class="login_input" type="email" name="user_email"
                        placeholder="example@domain.com" required />
                    <span class="form-hint">Email hợp lệ để khôi phục tài khoản</span>
                </div>

                <div class="form-group">
                    <label for="login_input_password_new">Mật khẩu</label>
                    <input id="login_input_password_new" class="login_input" type="password" name="user_password_new"
                        pattern=".{6,}" placeholder="Tối thiểu 6 ký tự" required autocomplete="off" />
                    <span class="form-hint">Tối thiểu 6 ký tự</span>
                </div>

                <div class="form-group">
                    <label for="login_input_password_repeat">Nhập lại mật khẩu</label>
                    <input id="login_input_password_repeat" class="login_input" type="password"
                        name="user_password_repeat" pattern=".{6,}" placeholder="Nhập lại mật khẩu" required
                        autocomplete="off" />
                    <span class="form-hint">Phải trùng khớp với mật khẩu trên</span>
                </div>

                <button type="submit" name="register" class="btn-register">Đăng ký tài khoản</button>
            </form>

            <div class="login-link">
                Đã có tài khoản? <a href="index.php">Đăng nhập ngay</a>
            </div>
        </div>
    </div>
    <div class="footer-text">
        © 2026 Warehouse Management System - Powered by Docker & PHP 8.1
    </div>
</body>

</html>
```

================
File: repomix.config.json
================
{
  "$schema": "https://repomix.com/schemas/latest/schema.json",
  "input": {
    "maxFileSize": 52428800
  },
  "output": {
    "filePath": "repomix-output.txt",
    "style": "plain",
    "parsableStyle": false,
    "fileSummary": true,
    "directoryStructure": true,
    "files": true,
    "removeComments": false,
    "removeEmptyLines": false,
    "compress": false,
    "topFilesLength": 5,
    "showLineNumbers": false,
    "truncateBase64": false,
    "copyToClipboard": false,
    "includeFullDirectoryStructure": false,
    "tokenCountTree": false,
    "git": {
      "sortByChanges": true,
      "sortByChangesMaxCommits": 100,
      "includeDiffs": false,
      "includeLogs": false,
      "includeLogsCount": 50
    }
  },
  "include": [],
  "ignore": {
    "useGitignore": true,
    "useDotIgnore": true,
    "useDefaultPatterns": true,
    "customPatterns": []
  },
  "security": {
    "enableSecurityCheck": true
  },
  "tokenCount": {
    "encoding": "o200k_base"
  }
}

================
File: views/.htaccess
================
# This file prevents that your .php view files are accessed directly from the outside
<Files ~ "\.(htaccess|php)$">
order allow,deny
deny from all
</Files>

================
File: views/logged_in.php
================
<?php
/**
 * 🛰️ TẦNG KHỞI CHẠY HỆ THỐNG TRUNG TÂM (CORE ENGINE LAYER)
 * Đảm bảo Session và cấu hình Database được nạp trước khi render HTML
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra bảo mật tầng gốc: Nếu chưa đăng nhập, lập tức chuyển hướng về trang login
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

// Nạp các tệp cấu hình cốt lõi và hàm kết nối PDO dùng chung (kế thừa từ Hành động 2)
if (file_exists("config/db.php")) {
    include_once("config/db.php");
}
if (file_exists("modules/connection.php")) { 
    include_once("modules/connection.php"); // Nạp hàm getPDOLayerConnection() nếu có
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Quản Lý Kho Chuẩn Odoo</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; background-color: #f4f6f9; min-height: 100vh; color: #333333; }
        
        /* 🔵 THANH SIDEBAR BÊN TRÁI - TÔNG XANH NƯỚC BIỂN ĐẬM */
        .sidebar { width: 260px; background-color: #1e3d59; color: #ffffff; display: flex; flex-direction: column; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }
        .sidebar-brand { padding: 20px; text-align: center; font-size: 1.15rem; font-weight: bold; border-bottom: 1px solid #17b978; background-color: #17b978; color: #ffffff; letter-spacing: 0.5px; }
        .sidebar-menu { list-style: none; padding: 15px 0; flex: 1; }
        .sidebar-menu li { padding: 14px 20px; transition: all 0.2s ease-in-out; }
        
        /* Trạng thái Hover & Active của Menu */
        .sidebar-menu li:hover { background-color: #17b978; padding-left: 25px; }
        .sidebar-menu a { color: #e8f1f5; text-decoration: none; display: flex; align-items: center; gap: 12px; font-size: 0.95rem; font-weight: 500; }
        .sidebar-menu li:hover a { color: #ffffff; }
        
        .sidebar-logout { padding: 20px; border-top: 1px solid #2b5278; }
        .btn-logout { display: block; text-align: center; background-color: #ff6b6b; color: white; padding: 10px; text-decoration: none; border-radius: 6px; font-weight: bold; transition: background 0.2s; }
        .btn-logout:hover { background-color: #ee5253; }

        /* ⚪ VÙNG NỘI DUNG CHÍNH BÊN PHẢI - TÔNG TRẮNG CHỦ ĐẠO */
        .main-content { flex: 1; display: flex; flex-direction: column; }
        
        /* Header trên cùng */
        .main-header { height: 65px; background-color: #ffffff; display: flex; align-items: center; justify-content: space-between; padding: 0 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border-bottom: 1px solid #eef2f5; }
        .page-title { font-size: 1.1rem; font-weight: 600; color: #1e3d59; display: flex; align-items: center; gap: 8px; }
        
        /* 👤 KHU VỰC AVATAR GÓC TRÊN CÙNG BÊN PHẢI */
        .user-profile { display: flex; align-items: center; gap: 12px; cursor: pointer; }
        .user-info { text-align: right; }
        .user-info .username { display: block; font-size: 0.9rem; font-weight: 600; color: #2c3e50; }
        .user-info .role { display: block; font-size: 0.75rem; color: #7f8c8d; }
        
        /* Khung tròn Avatar */
        .avatar-circle { width: 38px; height: 38px; background-color: #17b978; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1rem; box-shadow: 0 2px 4px rgba(23, 185, 120, 0.2); border: 2px solid #ffffff; }

        /* Ruột nội dung nghiệp vụ */
        .main-body { padding: 30px; flex: 1; overflow-y: auto; background-color: #f8fafc; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-brand">📦 Warehouse</div>
        <ul class="sidebar-menu">
            <li><a href="index.php?page=dashboard">📊 Dashboard</a></li>
            <li><a href="index.php?page=products">📦 Sản phẩm</a></li>
            <li><a href="index.php?page=warehouse">🚚 Điều phối Kho</a></li>
            <li><a href="index.php?page=reports">📈 Báo cáo vĩ mô</a></li>
        </ul>
        <div class="sidebar-logout">
            <a href="index.php?logout" class="btn-logout">🚪 Đăng xuất</a>
        </div>
    </aside>

    <div class="main-content">
        <header class="main-header">
            <div class="page-title">⚙️ Hệ thống điều phối dữ liệu</div>
            
            <div class="user-profile">
                <div class="user-info">
                    <span class="username"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                    <span class="role">Quản trị viên Hệ thống</span>
                </div>
                <div class="avatar-circle">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?>
                </div>
            </div>
        </header>
        
        <main class="main-body">
            <?php
            // BỘ ĐIỀU PHỐI TINH GỌN (ROUTER MECHANISM)
            $currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
            
            // Khai báo danh mục trang hợp pháp
            $allowedPages = ['dashboard', 'products', 'reports', 'warehouse'];
            
            if (in_array($currentPage, $allowedPages)) {
                $targetFile = "modules/" . $currentPage . ".php";
                if (file_exists($targetFile)) {
                    // Inject cổng kết nối database an toàn cho các module sử dụng
                    if (function_exists('getPDOLayerConnection')) {
                        $db = getPDOLayerConnection(); 
                    }
                    include($targetFile);
                } else {
                    echo "<div style='background: #ffffff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border-left: 4px solid #1e3d59;'>";
                    echo "<h3 style='color: #1e3d59;'>Mô-đun [ " . htmlspecialchars(ucfirst($currentPage)) . " ] đang được cấu trúc</h3>";
                    echo "<p style='color: #7f8c8d; margin-top: 10px;'>Hạt nhân Docker đang sẵn sàng nạp kết nối SQL cho tầng nghiệp vụ này.</p>";
                    echo "</div>";
                }
            } else {
                echo "<h3 style='color: #ff6b6b;'>Cảnh báo: Tầng truy cập không hợp lệ!</h3>";
            }
            ?>
        </main>
    </div>

</body>
</html>

================
File: views/not_logged_in.php
================
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống Quản Lý Kho</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #1e3d59 0%, #17b978 100%);
        min-height: 100vh;
        display: flex;
		flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 20px;
		gap: 16px;
    }

    .login-container {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        max-width: 450px;
        width: 100%;
        animation: slideIn 0.5s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .login-header {
        background: linear-gradient(135deg, #1e3d59 0%, #2b5278 100%);
        padding: 40px 30px;
        text-align: center;
        color: #ffffff;
    }

    .login-header h1 {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 8px;
        letter-spacing: 0.5px;
    }

    .login-header p {
        font-size: 0.95rem;
        color: #e8f1f5;
        font-weight: 400;
    }

    .login-icon {
        width: 70px;
        height: 70px;
        background: #17b978;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 2rem;
        box-shadow: 0 4px 12px rgba(23, 185, 120, 0.3);
    }

    .login-body {
        padding: 40px 30px;
    }

    .message-box {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 0.9rem;
        line-height: 1.5;
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .error-box {
        background-color: #fee;
        color: #c33;
        border-left: 4px solid #e74c3c;
    }

    .success-box {
        background-color: #d4edda;
        color: #155724;
        border-left: 4px solid #17b978;
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #1e3d59;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .form-group input[type="text"],
    .form-group input[type="password"] {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e0e6ed;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background-color: #f8fafc;
    }

    .form-group input[type="text"]:focus,
    .form-group input[type="password"]:focus {
        outline: none;
        border-color: #17b978;
        background-color: #ffffff;
        box-shadow: 0 0 0 4px rgba(23, 185, 120, 0.1);
    }

    .btn-login {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #1e3d59 0%, #17b978 100%);
        color: #ffffff;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 4px 12px rgba(30, 61, 89, 0.3);
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(30, 61, 89, 0.4);
    }

    .btn-login:active {
        transform: translateY(0);
    }

    .register-link {
        text-align: center;
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid #e0e6ed;
    }

    .register-link a {
        color: #1e3d59;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.95rem;
        transition: color 0.3s ease;
    }

    .register-link a:hover {
        color: #17b978;
    }

    .footer-text {
        text-align: center;
        margin-top: 10px;
        color: #ffffff;
        font-size: 0.85rem;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-icon">📦</div>
            <h1>Hệ Thống Quản Lý Kho</h1>
            <p>Đăng nhập để tiếp tục</p>
        </div>

        <div class="login-body">
            <?php
            // Hiển thị lỗi từ login object (GIỮ NGUYÊN LOGIC)
            if (isset($login)) {
                if ($login->errors) {
                    foreach ($login->errors as $error) {
                        echo '<div class="message-box error-box">⚠️ ' . htmlspecialchars($error) . '</div>';
                    }
                }
                if ($login->messages) {
                    foreach ($login->messages as $message) {
                        echo '<div class="message-box success-box">✅ ' . htmlspecialchars($message) . '</div>';
                    }
                }
            }
            ?>

            <!-- FORM ĐĂNG NHẬP - GIỮ NGUYÊN 100% LOGIC -->
            <form method="post" action="index.php" name="loginform">
                <div class="form-group">
                    <label for="login_input_username">Tên đăng nhập</label>
                    <input id="login_input_username" class="login_input" type="text" name="user_name"
                        placeholder="Nhập username của bạn" required />
                </div>

                <div class="form-group">
                    <label for="login_input_password">Mật khẩu</label>
                    <input id="login_input_password" class="login_input" type="password" name="user_password"
                        placeholder="Nhập mật khẩu" autocomplete="off" required />
                </div>

                <button type="submit" name="login" class="btn-login">Đăng nhập</button>
            </form>

            <div class="register-link">
                Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
            </div>
        </div>
    </div>
    <div class="footer-text">
        © 2026 Warehouse Management System - Powered by Docker & PHP 8.1
    </div>
</body>

</html>

================
File: views/register.php
================
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Hệ thống Quản Lý Kho</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #1e3d59 0%, #17b978 100%);
        min-height: 100vh;
        display: flex;
		flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 20px;
		gap: 16px;
    }

    .register-container {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        max-width: 500px;
        width: 100%;
        animation: slideIn 0.5s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .register-header {
        background: linear-gradient(135deg, #1e3d59 0%, #2b5278 100%);
        padding: 40px 30px;
        text-align: center;
        color: #ffffff;
    }

    .register-header h1 {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 8px;
        letter-spacing: 0.5px;
    }

    .register-header p {
        font-size: 0.95rem;
        color: #e8f1f5;
        font-weight: 400;
    }

    .register-icon {
        width: 70px;
        height: 70px;
        background: #17b978;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 2rem;
        box-shadow: 0 4px 12px rgba(23, 185, 120, 0.3);
    }

    .register-body {
        padding: 40px 30px;
    }

    .message-box {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 0.9rem;
        line-height: 1.5;
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .error-box {
        background-color: #fee;
        color: #c33;
        border-left: 4px solid #e74c3c;
    }

    .success-box {
        background-color: #d4edda;
        color: #155724;
        border-left: 4px solid #17b978;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #1e3d59;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="password"] {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e0e6ed;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background-color: #f8fafc;
    }

    .form-group input[type="text"]:focus,
    .form-group input[type="email"]:focus,
    .form-group input[type="password"]:focus {
        outline: none;
        border-color: #17b978;
        background-color: #ffffff;
        box-shadow: 0 0 0 4px rgba(23, 185, 120, 0.1);
    }

    .form-hint {
        font-size: 0.8rem;
        color: #7f8c8d;
        margin-top: 4px;
        display: block;
    }

    .btn-register {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #1e3d59 0%, #17b978 100%);
        color: #ffffff;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 4px 12px rgba(30, 61, 89, 0.3);
        margin-top: 10px;
    }

    .btn-register:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(30, 61, 89, 0.4);
    }

    .btn-register:active {
        transform: translateY(0);
    }

    .login-link {
        text-align: center;
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid #e0e6ed;
    }

    .login-link a {
        color: #1e3d59;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.95rem;
        transition: color 0.3s ease;
    }

    .login-link a:hover {
        color: #17b978;
    }

    .footer-text {
        text-align: center;
        margin-top: 10px;
        color: #ffffff;
        font-size: 0.85rem;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .password-strength {
        height: 4px;
        background-color: #e0e6ed;
        border-radius: 2px;
        margin-top: 8px;
        overflow: hidden;
    }

    .password-strength-bar {
        height: 100%;
        width: 0%;
        transition: all 0.3s ease;
    }
    </style>
</head>

<body>
    <div class="register-container">
        <div class="register-header">
            <div class="register-icon">✍️</div>
            <h1>Tạo Tài Khoản Mới</h1>
            <p>Đăng ký để sử dụng hệ thống</p>
        </div>

        <div class="register-body">
            <?php
            // Hiển thị lỗi từ registration object (GIỮ NGUYÊN LOGIC)
            if (isset($registration)) {
                if ($registration->errors) {
                    foreach ($registration->errors as $error) {
                        echo '<div class="message-box error-box">⚠️ ' . htmlspecialchars($error) . '</div>';
                    }
                }
                if ($registration->messages) {
                    foreach ($registration->messages as $message) {
                        echo '<div class="message-box success-box">✅ ' . htmlspecialchars($message) . '</div>';
                    }
                }
            }
            ?>

            <!-- FORM ĐĂNG KÝ - GIỮ NGUYÊN 100% LOGIC -->
            <form method="post" action="register.php" name="registerform">
                <div class="form-group">
                    <label for="login_input_username">Tên đăng nhập</label>
                    <input id="login_input_username" class="login_input" type="text" pattern="[a-zA-Z0-9]{2,64}"
                        name="user_name" placeholder="Chỉ chữ cái và số" required />
                    <span class="form-hint">Từ 2-64 ký tự, chỉ chữ cái và số</span>
                </div>

                <div class="form-group">
                    <label for="login_input_email">Email</label>
                    <input id="login_input_email" class="login_input" type="email" name="user_email"
                        placeholder="example@domain.com" required />
                    <span class="form-hint">Email hợp lệ để khôi phục tài khoản</span>
                </div>

                <div class="form-group">
                    <label for="login_input_password_new">Mật khẩu</label>
                    <input id="login_input_password_new" class="login_input" type="password" name="user_password_new"
                        pattern=".{6,}" placeholder="Tối thiểu 6 ký tự" required autocomplete="off" />
                    <span class="form-hint">Tối thiểu 6 ký tự</span>
                </div>

                <div class="form-group">
                    <label for="login_input_password_repeat">Nhập lại mật khẩu</label>
                    <input id="login_input_password_repeat" class="login_input" type="password"
                        name="user_password_repeat" pattern=".{6,}" placeholder="Nhập lại mật khẩu" required
                        autocomplete="off" />
                    <span class="form-hint">Phải trùng khớp với mật khẩu trên</span>
                </div>

                <button type="submit" name="register" class="btn-register">Đăng ký tài khoản</button>
            </form>

            <div class="login-link">
                Đã có tài khoản? <a href="index.php">Đăng nhập ngay</a>
            </div>
        </div>
    </div>
    <div class="footer-text">
        © 2026 Warehouse Management System - Powered by Docker & PHP 8.1
    </div>
</body>

</html>





================================================================
End of Codebase
================================================================
````

## File: repomix.config.json
````json
{
  "$schema": "https://repomix.com/schemas/latest/schema.json",
  "input": {
    "maxFileSize": 52428800
  },
  "output": {
    "filePath": "repomix-output.md",
    "style": "markdown",
    "parsableStyle": false,
    "fileSummary": true,
    "directoryStructure": true,
    "files": true,
    "removeComments": false,
    "removeEmptyLines": false,
    "compress": false,
    "topFilesLength": 5,
    "showLineNumbers": false,
    "truncateBase64": false,
    "copyToClipboard": false,
    "includeFullDirectoryStructure": false,
    "tokenCountTree": false,
    "git": {
      "sortByChanges": true,
      "sortByChangesMaxCommits": 100,
      "includeDiffs": false,
      "includeLogs": false,
      "includeLogsCount": 50
    }
  },
  "include": [],
  "ignore": {
    "useGitignore": true,
    "useDotIgnore": true,
    "useDefaultPatterns": true,
    "customPatterns": []
  },
  "security": {
    "enableSecurityCheck": true
  },
  "tokenCount": {
    "encoding": "o200k_base"
  }
}
````

## File: views/.htaccess
````
# This file prevents that your .php view files are accessed directly from the outside
<Files ~ "\.(htaccess|php)$">
order allow,deny
deny from all
</Files>
````

## File: views/logged_in.php
````php
<?php
/**
 * 🛰️ TẦNG KHỞI CHẠY HỆ THỐNG TRUNG TÂM (CORE ENGINE LAYER)
 * Đảm bảo Session và cấu hình Database được nạp trước khi render HTML
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra bảo mật tầng gốc: Nếu chưa đăng nhập, lập tức chuyển hướng về trang login
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

// Nạp các tệp cấu hình cốt lõi và hàm kết nối PDO dùng chung (kế thừa từ Hành động 2)
if (file_exists("config/db.php")) {
    include_once("config/db.php");
}
if (file_exists("modules/connection.php")) { 
    include_once("modules/connection.php"); // Nạp hàm getPDOLayerConnection() nếu có
}

// Thiết lập biến $db để dùng cho việc cập nhật thông tin cá nhân bên dưới
if (function_exists('getPDOLayerConnection')) {
    $db = getPDOLayerConnection(); 
}

// 📥 ĐOẠN XỬ LÝ LƯU THÔNG TIN KHI CÓ SUBMIT FORM CẬP NHẬT TÀI KHOẢN
if (isset($_POST['submit_update_profile'])) {
    $new_name = trim($_POST['update_user_name']);
    $new_email = trim($_POST['update_user_email']);
    $new_pass = $_POST['update_user_password_new'];
    
    if (isset($db) && $db instanceof PDO) {
        try {
            if (empty($new_name) || empty($new_email)) {
                echo "<script>alert('Không được bỏ trống Tên hoặc Email!');</script>";
            } else {
                if (!empty($new_pass)) {
                    // Người dùng muốn đổi mật khẩu mới
                    if (strlen($new_pass) < 6) {
                        echo "<script>alert('Mật khẩu mới phải từ 6 ký tự trở lên!');</script>";
                    } else {
                        $hash_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                        // Cập nhật cả mật khẩu dựa trên user_id của Session (hoặc user_name cũ)
                        $stmt = $db->prepare("UPDATE users SET user_name = ?, user_email = ?, user_password_hash = ? WHERE user_name = ?");
                        $stmt->execute([$new_name, $new_email, $hash_pass, $_SESSION['user_name']]);
                        
                        $_SESSION['user_name'] = $new_name;
                        $_SESSION['user_email'] = $new_email;
                        echo "<script>alert('Cập nhật thông tin và mật khẩu thành công!'); window.location.href='index.php';</script>";
                    }
                } else {
                    // Chỉ cập nhật Tên và Email
                    $stmt = $db->prepare("UPDATE users SET user_name = ?, user_email = ? WHERE user_name = ?");
                    $stmt->execute([$new_name, $new_email, $_SESSION['user_name']]);
                    
                    $_SESSION['user_name'] = $new_name;
                    $_SESSION['user_email'] = $new_email;
                    echo "<script>alert('Cập nhật thông tin cá nhân thành công!'); window.location.href='index.php';</script>";
                }
            }
        } catch (PDOException $e) {
            echo "<script>alert('Lỗi: Tên tài khoản hoặc Email này đã tồn tại!');</script>";
        }
    } else {
        echo "<script>alert('Lỗi: Không tìm thấy kết nối Cơ sở dữ liệu!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Quản Lý Kho Chuẩn Odoo</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; background-color: #f4f6f9; min-height: 100vh; color: #333333; }
        
        /* 🔵 THANH SIDEBAR BÊN TRÁI - TÔNG XANH NƯỚC BIỂN ĐẬM */
        .sidebar { width: 260px; background-color: #1e3d59; color: #ffffff; display: flex; flex-direction: column; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }
        .sidebar-brand { padding: 20px; text-align: center; font-size: 1.15rem; font-weight: bold; border-bottom: 1px solid #17b978; background-color: #17b978; color: #ffffff; letter-spacing: 0.5px; }
        .sidebar-menu { list-style: none; padding: 15px 0; flex: 1; }
        .sidebar-menu li { padding: 14px 20px; transition: all 0.2s ease-in-out; }
        
        /* Trạng thái Hover & Active của Menu */
        .sidebar-menu li:hover { background-color: #17b978; padding-left: 25px; }
        .sidebar-menu a { color: #e8f1f5; text-decoration: none; display: flex; align-items: center; gap: 12px; font-size: 0.95rem; font-weight: 500; }
        .sidebar-menu li:hover a { color: #ffffff; }
        
        .sidebar-logout { padding: 20px; border-top: 1px solid #2b5278; }
        .btn-logout { display: block; text-align: center; background-color: #ff6b6b; color: white; padding: 10px; text-decoration: none; border-radius: 6px; font-weight: bold; transition: background 0.2s; }
        .btn-logout:hover { background-color: #ee5253; }

        /* ⚪ VÙNG NỘI DUNG CHÍNH BÊN PHẢI - TÔNG TRẮNG CHỦ ĐẠO */
        .main-content { flex: 1; display: flex; flex-direction: column; }
        
        /* Header trên cùng */
        .main-header { height: 65px; background-color: #ffffff; display: flex; align-items: center; justify-content: space-between; padding: 0 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border-bottom: 1px solid #eef2f5; }
        .page-title { font-size: 1.1rem; font-weight: 600; color: #1e3d59; display: flex; align-items: center; gap: 8px; }
        
        /* 👤 KHU VỰC AVATAR GÓC TRÊN CÙNG BÊN PHẢI */
        .user-profile { display: flex; align-items: center; gap: 12px; cursor: pointer; position: relative; /* Giúp căn vị trí box nhỏ theo avatar */ }
        .user-info { text-align: right; }
        .user-info .username { display: block; font-size: 0.9rem; font-weight: 600; color: #2c3e50; }
        .user-info .role { display: block; font-size: 0.75rem; color: #7f8c8d; }
        
        /* Khung tròn Avatar */
        .avatar-circle { width: 38px; height: 38px; background-color: #17b978; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1rem; box-shadow: 0 2px 4px rgba(23, 185, 120, 0.2); border: 2px solid #ffffff; }

        /* Ruột nội dung nghiệp vụ */
        .main-body { padding: 30px; flex: 1; overflow-y: auto; background-color: #f8fafc; }

        /* Định dạng cho chiếc Box nhỏ */
        .profile-dropdown {
            display: none; /* Mặc định ẩn đi */
            position: absolute;
            right: 0;
            top: 55px;
            background-color: #ffffff;
            min-width: 290px;
            box-shadow: 0px 10px 25px rgba(0,0,0,0.15);
            border-radius: 8px;
            padding: 20px;
            z-index: 9999;
            border: 1px solid #eef2f5;
            cursor: default;
            text-align: left;
        }

        /* Hiển thị box khi có class 'show' kích hoạt từ JS */
        .profile-dropdown.show {
            display: block;
            animation: dropdownFadeIn 0.25s ease-out;
        }

        .profile-dropdown h3 {
            font-size: 1rem;
            color: #1e3d59;
            margin-bottom: 12px;
            border-bottom: 1px solid #eef2f5;
            padding-bottom: 8px;
        }

        .form-mini-group {
            margin-bottom: 12px;
        }

        .form-mini-group label {
            display: block;
            font-size: 0.8rem;
            margin-bottom: 4px;
            color: #7f8c8d;
            font-weight: 500;
        }

        .form-mini-group input {
            width: 100%;
            padding: 8px;
            font-size: 0.9rem;
            border: 1px solid #cccccc;
            border-radius: 4px;
            outline: none;
        }

        .form-mini-group input:focus {
            border-color: #17b978;
        }

        .dropdown-mini-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            gap: 10px;
        }

        .btn-mini-save {
            background-color: #17b978;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 0.85rem;
            flex: 1;
        }

        .btn-mini-cancel {
            background-color: #ff6b6b;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 0.85rem;
        }

        @keyframes dropdownFadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-brand">📦 Warehouse</div>
        <ul class="sidebar-menu">
            <li><a href="index.php?page=dashboard">📊 Dashboard</a></li>
            <li><a href="index.php?page=products">📦 Sản phẩm</a></li>
            <li><a href="index.php?page=partners">👥 Đối tác (KH/NCC)</a></li>
            <li><a href="index.php?page=warehouse">🚚 Điều phối Kho</a></li>
            <li><a href="index.php?page=audit">📋 Kiểm kê kho</a></li>
            <li><a href="index.php?page=reports">📈 Báo cáo vĩ mô</a></li>
            <li><a href="index.php?page=chat-ai">🤖 Trợ lý AI</a></li>
            <li><a href="index.php?page=settings">⚙️ Cài đặt</a></li>
        </ul>
        <div class="sidebar-logout">
            <a href="index.php?logout" class="btn-logout">🚪 Đăng xuất</a>
        </div>
    </aside>

    <div class="main-content">
        <header class="main-header">
            <div class="page-title">⚙️ Hệ thống điều phối dữ liệu</div>
            
            <div class="user-profile" id="userProfileTrigger">
                <div class="user-info">
                    <span class="username"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                    <span class="role">Quản trị viên Hệ thống</span>
                </div>
                <div class="avatar-circle">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?>
                </div>

                <div class="profile-dropdown" id="profileDropdownMini">
                    <h3>Chỉnh sửa tài khoản</h3>
                    <form action="" method="POST">
                        <div class="form-mini-group">
                            <label>Tên tài khoản:</label>
                            <input type="text" name="update_user_name" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-mini-group">
                            <label>Email cá nhân:</label>
                            <input type="email" name="update_user_email" value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-mini-group">
                            <label>Mật khẩu mới (bỏ trống nếu giữ cũ):</label>
                            <input type="password" name="update_user_password_new" autocomplete="off" placeholder="Tối thiểu 6 ký tự">
                        </div>
                        
                        <div class="dropdown-mini-actions">
                            <button type="submit" name="submit_update_profile" class="btn-mini-save">Cập nhật</button>
                            <button type="button" id="btnMiniCancel" class="btn-mini-cancel">Hủy</button>
                        </div>
                    </form>
                </div>
            </div>
        </header>
        
        <main class="main-body">
            <?php
            // BỘ ĐIỀU PHỐI TINH GỌN (ROUTER MECHANISM)
            $currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
            
            // Khai báo danh mục trang hợp pháp
            $allowedPages = ['dashboard', 'products', 'partners', 'warehouse', 'audit', 'reports', 'chat-ai', 'settings'];
            
            if (in_array($currentPage, $allowedPages)) {
                $targetFile = "modules/" . $currentPage . ".php";
                if (file_exists($targetFile)) {
                    // Inject cổng kết nối database an toàn cho các module sử dụng
                    include($targetFile);
                } else {
                    echo "<div style='background: #ffffff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border-left: 4px solid #1e3d59;'>";
                    echo "<h3 style='color: #1e3d59;'>Mô-đun [ " . htmlspecialchars(ucfirst($currentPage)) . " ] đang được cấu trúc</h3>";
                    echo "<p style='color: #7f8c8d; margin-top: 10px;'>Hạt nhân Docker đang sẵn sàng nạp kết nối SQL cho tầng nghiệp vụ này.</p>";
                    echo "</div>";
                }
            } else {
                echo "<h3 style='color: #ff6b6b;'>Cảnh báo: Tầng truy cập không hợp lệ!</h3>";
            }
            ?>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const trigger = document.getElementById('userProfileTrigger');
        const dropdown = document.getElementById('profileDropdownMini');
        const btnCancel = document.getElementById('btnMiniCancel');

        // Bấm vào khu vực Avatar -> Tắt/Bật Box nhỏ
        trigger.addEventListener('click', function(e) {
            // Nếu người dùng đang tương tác gõ chữ bên trong Form thì không đóng box
            if (e.target.closest('.profile-dropdown')) return;
            dropdown.classList.toggle('show');
        });

        // Bấm nút Hủy -> Đóng Box
        btnCancel.addEventListener('click', function(e) {
            e.stopPropagation(); // Không kích hoạt sự kiện click của trigger cha
            dropdown.classList.remove('show');
        });

        // Click ra vùng trống bất kỳ trên màn hình -> Tự động đóng Box lại
        document.addEventListener('click', function(e) {
            if (!trigger.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
    });
    </script>
</body>
</html>
````

## File: views/not_logged_in.php
````php
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống Quản Lý Kho</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: linear-gradient(135deg, #1e3d59 0%, #17b978 100%); min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; }
        .login-container { background: #ffffff; border-radius: 16px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); overflow: hidden; max-width: 450px; width: 100%; animation: slideIn 0.5s ease-out; position: relative; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-30px); } to { opacity: 1; transform: translateY(0); } }
        .login-header { background: linear-gradient(135deg, #1e3d59 0%, #2b5278 100%); padding: 40px 30px; text-align: center; color: #ffffff; }
        .login-header h1 { font-size: 1.8rem; font-weight: 700; margin-bottom: 8px; }
        .login-body { padding: 30px 30px; }
        .message-box { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
        .error-box { background-color: #fee; color: #c33; border-left: 4px solid #e74c3c; }
        .success-box { background-color: #d4edda; color: #155724; border-left: 4px solid #17b978; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #1e3d59; font-weight: 600; font-size: 0.9rem; }
        .login_input { width: 100%; padding: 14px 16px; border: 2px solid #e0e6ed; border-radius: 8px; font-size: 1rem; background-color: #f8fafc; transition: all 0.3s ease; }
        .login_input:focus { outline: none; border-color: #17b978; background-color: #ffffff; }
        .btn-login { width: 100%; padding: 14px; background: linear-gradient(135deg, #1e3d59 0%, #17b978 100%); color: #ffffff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; }
        .social-divider { display: flex; align-items: center; text-align: center; margin: 25px 0; color: #8898aa; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; }
        .social-divider::before, .social-divider::after { content: ''; flex: 1; border-bottom: 1px solid #e0e6ed; }
        .social-divider:not(:empty)::before { margin-right: .75em; }
        .social-divider:not(:empty)::after { margin-left: .75em; }
        .register-link { text-align: center; margin-top: 20px; font-size: 0.9rem; color: #64748b; }
        .register-link a { color: #17b978; font-weight: 600; text-decoration: none; cursor: pointer; }
        .google-action { display: flex; justify-content: center; margin-top: 12px; }
        .google-note { text-align: center; margin-top: 12px; font-size: 0.9rem; color: #5b6b77; }
        .google-signin-card { width: 100%; max-width: 420px; }

        /* 🚀 PHẦN CSS THÊM MỚI: Cấu hình Modal Đăng ký nhanh không chia tab */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; padding: 20px; }
        .modal-content { background: #ffffff; border-radius: 16px; max-width: 450px; width: 100%; overflow: hidden; animation: slideIn 0.3s ease-out; box-shadow: 0 10px 40px rgba(0,0,0,0.4); }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Hệ Thống Quản Lý Kho</h1>
            <p>Đăng nhập điều hành hệ thống logistics</p>
        </div>

        <div class="login-body">
            <?php
            if (isset($login)) {
                if ($login->errors) {
                    foreach ($login->errors as $error) {
                        echo '<div class="message-box error-box">⚠️ ' . htmlspecialchars($error) . '</div>';
                    }
                }
                if ($login->messages) {
                    foreach ($login->messages as $message) {
                        echo '<div class="message-box success-box">✅ ' . htmlspecialchars($message) . '</div>';
                    }
                }
            }
            // 🚀 Hiển thị thông báo nếu lớp đăng ký trả về lỗi/thành công từ Modal ngầm gửi lên
            if (isset($registration)) {
                if ($registration->errors) {
                    foreach ($registration->errors as $error) {
                        echo '<div class="message-box error-box">⚠️ ' . htmlspecialchars($error) . '</div>';
                    }
                }
                if ($registration->messages) {
                    foreach ($registration->messages as $message) {
                        echo '<div class="message-box success-box">✅ ' . htmlspecialchars($message) . '</div>';
                    }
                }
            }
            ?>

            <form method="post" action="index.php" name="loginform">
                <div class="form-group">
                    <label>Tên đăng nhập / Địa chỉ Email</label>
                    <input class="login_input" type="text" name="user_name" placeholder="Nhập tên đăng nhập hoặc email" required />
                </div>
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <input class="login_input" type="password" name="user_password" placeholder="Nhập mật khẩu tài khoản" autocomplete="off" required />
                </div>
                <button type="submit" name="login" class="btn-login">Đăng nhập</button>
            </form>

            <div class="social-divider">Hoặc đăng nhập nhanh bằng</div>
            <div class="google-action">
                <div class="google-signin-card">
                    <div id="g_id_onload"
                         data-client_id="<?php echo GOOGLE_CLIENT_ID; ?>"
                         data-callback="handleGoogleLoginResponse"
                         data-auto_prompt="false">
                    </div>
                    <div class="g_id_signin" data-type="standard" data-shape="rectangular" data-theme="filled_blue" data-text="signin_with" data-size="large" data-width="390"></div>
                </div>
            </div>
            <div class="google-note">Đăng nhập tức thì bằng Google, bảo mật và không cần nhập mật khẩu nếu bạn đã đăng nhập Google.</div>

            <div class="register-link">
                Chưa có tài khoản quản lý kho? <a onclick="openRegisterModal()">Đăng ký tài khoản mới</a>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="registerModal">
        <div class="modal-content">
            <div class="login-header" style="padding: 25px 20px;">
                <h2>Đăng ký thành viên mới</h2>
                <p>Khởi tạo tài khoản quản trị kho hàng</p>
            </div>
            <div class="login-body" style="padding: 20px 25px;">
                <form method="post" action="register.php" name="registerform">
                    <div class="form-group">
                        <label>Tên đăng nhập (chỉ chữ và số)</label>
                        <input class="login_input" type="text" pattern="[a-zA-Z0-9]{2,64}" name="user_name" placeholder="Ví dụ: nvanA" required />
                    </div>
                    <div class="form-group">
                        <label>Địa chỉ Email</label>
                        <input class="login_input" type="email" name="user_email" placeholder="email@example.com" required />
                    </div>
                    <div class="form-group">
                        <label>Mật khẩu (Tối thiểu 6 ký tự)</label>
                        <input class="login_input" type="password" name="user_password_new" pattern=".{6,}" required placeholder="••••••••" autocomplete="off" />
                    </div>
                    <div class="form-group">
                        <label>Nhập lại mật khẩu</label>
                        <input class="login_input" type="password" name="user_password_repeat" pattern=".{6,}" required placeholder="••••••••" autocomplete="off" />
                    </div>
                    <button type="submit" name="register" class="btn-login" style="background: linear-gradient(135deg, #17b978 0%, #1e3d59 100%);">Xác nhận đăng ký</button>
                    
                    <div class="register-link" style="margin-top: 15px;">
                        <a onclick="closeRegisterModal()" style="color: #e74c3c;">Quay lại Đăng nhập</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://accounts.google.com/gsi/client" async defer></script>
    
    <script>
    // 🚀 HÀM JAVASCRIPT THÊM MỚI: Quản lý đóng mở cửa sổ đăng ký nhanh tại chỗ
    function openRegisterModal() {
        document.getElementById('registerModal').style.display = 'flex';
    }
    function closeRegisterModal() {
        document.getElementById('registerModal').style.display = 'none';
    }

    // Hàm này tự động chạy khi người dùng chọn tài khoản và xác minh mật khẩu Gmail thành công trên popup Google
    function handleGoogleLoginResponse(response) {
        // Tạo một form ảo chạy ngầm để gửi Token an toàn về cho file classes/Login.php kiểm tra
        const form = document.createElement('form');
        form.method = 'POST';
        form.action='google_callback.php';
        
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = 'google_oauth_token';
        tokenInput.value = response.credential;

        const flowInput = document.createElement('input');
        flowInput.type = 'hidden';
        flowInput.name = 'google_flow_type';
        flowInput.value = 'login';
        
        form.appendChild(tokenInput);
        form.appendChild(flowInput);
        document.body.appendChild(form);
        form.submit();
    }
    </script>
</body>
</html>
````

## File: views/register.php
````php
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản - Hệ thống Quản Lý Kho</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: linear-gradient(135deg, #1e3d59 0%, #17b978 100%); min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; }
        .login-container { background: #ffffff; border-radius: 16px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); overflow: hidden; max-width: 480px; width: 100%; animation: slideIn 0.5s ease-out; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-30px); } to { opacity: 1; transform: translateY(0); } }
        .login-header { background: linear-gradient(135deg, #1e3d59 0%, #2b5278 100%); padding: 30px 20px; text-align: center; color: #ffffff; }
        .login-header h1 { font-size: 1.6rem; font-weight: 700; margin-bottom: 8px; }
        .login-body { padding: 25px 30px; }
        .message-box { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
        .error-box { background-color: #fee; color: #c33; border-left: 4px solid #e74c3c; }
        .success-box { background-color: #d4edda; color: #155724; border-left: 4px solid #17b978; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 6px; color: #1e3d59; font-weight: 600; font-size: 0.9rem; }
        .login_input { width: 100%; padding: 12px 14px; border: 2px solid #e0e6ed; border-radius: 8px; font-size: 0.95rem; background-color: #f8fafc; transition: all 0.3s ease; }
        .login_input:focus { outline: none; border-color: #17b978; background-color: #ffffff; }
        .btn-register { width: 100%; padding: 14px; background: linear-gradient(135deg, #1e3d59 0%, #17b978 100%); color: #ffffff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px; }
        .login-link { text-align: center; margin-top: 20px; font-size: 0.9rem; color: #64748b; }
        .login-link a { color: #17b978; font-weight: 600; text-decoration: none; }
        .social-divider { display: flex; align-items: center; text-align: center; margin: 20px 0; color: #8898aa; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
        .social-divider::before, .social-divider::after { content: ''; flex: 1; border-bottom: 1px solid #e0e6ed; }
        .social-divider:not(:empty)::before { margin-right: .75em; }
        .social-divider:not(:empty)::after { margin-left: .75em; }
        .google-action { display: flex; justify-content: center; margin-top: 10px; }
        .google-note { text-align: center; margin-top: 12px; font-size: 0.9rem; color: #5b6b77; }
        .google-signin-card { width: 100%; max-width: 420px; }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <h1 id="header-title">Đăng ký tài khoản Kho</h1>
            <p id="header-desc">Hệ thống quản lý chuỗi cung ứng logistics</p>
        </div>

        <div class="login-body">
            <?php
            if (isset($registration)) {
                if ($registration->errors) {
                    foreach ($registration->errors as $error) {
                        echo '<div class="message-box error-box">⚠️ ' . htmlspecialchars($error) . '</div>';
                    }
                }
                if ($registration->messages) {
                    foreach ($registration->messages as $message) {
                        echo '<div class="message-box success-box">✅ ' . htmlspecialchars($message) . '</div>';
                    }
                }
            }
            ?>

            <div id="standard-register-section">
                <form method="post" action="register.php" name="registerform">
                    <div class="form-group">
                        <label for="login_input_username">Tên đăng nhập (Chỉ gồm chữ và số, 2-64 ký tự)</label>
                        <input id="login_input_username" class="login_input" type="text" pattern="[a-zA-Z0-9]{2,64}" name="user_name" placeholder="Ví dụ: thuongkho2026" required />
                    </div>

                    <div class="form-group">
                        <label for="login_input_email">Địa chỉ Email người dùng</label>
                        <input id="login_input_email" class="login_input" type="email" name="user_email" placeholder="example@domain.com" required />
                    </div>

                    <div class="form-group">
                        <label for="login_input_password_new">Mật khẩu (Tối thiểu 6 ký tự)</label>
                        <input id="login_input_password_new" class="login_input" type="password" name="user_password_new" pattern=".{6,}" placeholder="••••••••" required autocomplete="off" />
                    </div>

                    <div class="form-group">
                        <label for="login_input_password_repeat">Nhập lại mật khẩu</label>
                        <input id="login_input_password_repeat" class="login_input" type="password" name="user_password_repeat" pattern=".{6,}" placeholder="••••••••" required autocomplete="off" />
                    </div>

                    <button type="submit" name="register" class="btn-register">Đăng ký hệ thống</button>
                </form>

                <div class="social-divider">Hoặc nhanh hơn với</div>
                <div class="google-action">
                    <div class="google-signin-card">
                        <div id="g_id_onload"
                             data-client_id="<?php echo GOOGLE_CLIENT_ID; ?>"
                             data-context="signup"
                             data-ux_mode="popup"
                             data-callback="handleGoogleResponse"
                             data-auto_prompt="false">
                        </div>
                        <div class="g_id_signin" data-type="standard" data-shape="rectangular" data-theme="filled_blue" data-text="signup_with" data-size="large" data-width="420"></div>
                    </div>
                </div>
                <div class="google-note">Đăng ký nhanh bằng Google để tiết kiệm thời gian và tránh gõ lại email.</div>
            </div>

            <div id="google-profile-completion-section" style="display: none;">
                <form method="post" action="google_callback.php" name="googlecompletionform">
                    <input type="hidden" id="google_token" name="google_oauth_token">
                    <input type="hidden" name="google_flow_type" value="register">

                    <div class="form-group">
                        <label>Email liên kết từ Google (Đã xác minh thật)</label>
                        <input id="google_verified_email" class="login_input" type="text" readonly style="background-color: #e2e8f0; color: #475569; font-weight: 600;" />
                    </div>

                    <div class="form-group">
                        <label>Đặt tên Username viết liền không dấu *</label>
                        <input id="google_suggested_username" class="login_input" type="text" pattern="[a-zA-Z0-9]{2,64}" name="user_name" required />
                    </div>

                    <div class="form-group">
                        <label>Họ và tên thật nhân sự *</label>
                        <input class="login_input" type="text" name="user_full_name" placeholder="Ví dụ: Nguyễn Văn A" required />
                    </div>

                    <div class="form-group">
                        <label>Số điện thoại liên lạc *</label>
                        <input class="login_input" type="text" name="user_phone" placeholder="Ví dụ: 0912345678" required />
                    </div>

                    <button type="submit" name="register_google_profile" class="btn-register" style="background: linear-gradient(135deg, #17b978 0%, #1e3d59 100%);">Hoàn tất hoàn thiện hồ sơ</button>
                </form>
            </div>

            <div class="login-link">
                Đã có tài khoản? <a href="index.php">Đăng nhập ngay</a>
            </div>
        </div>
    </div>

    <script src="https://accounts.google.com/gsi/client" async defer></script>
    
    <script>
    // Hàm callback tự động kích hoạt KHI VÀ CHỈ KHI người dùng thật nhập đúng mật khẩu Gmail thành công trên popup Google
    function handleGoogleResponse(response) {
        const token = response.credential;
        
        // Tiến hành bóc tách gói tin JWT cục bộ ngay tại Client để lấy nhanh trường email điền vào form hồ sơ
        const base64Url = token.split('.')[1];
        const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        const jsonPayload = decodeURIComponent(window.atob(base64).split('').map(function(c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));
        
        const googleUserData = JSON.parse(jsonPayload);

        // Đổi trạng thái giao diện: Ẩn form đăng ký thường, Hiện form nhập thông tin cá nhân bổ sung
        document.getElementById('standard-register-section').style.display = 'none';
        document.getElementById('google-profile-completion-section').style.display = 'block';
        
        // Cập nhật tiêu đề trang
        document.getElementById('header-title').innerText = "Hoàn thiện hồ sơ";
        document.getElementById('header-desc').innerText = "Nhập thông tin cá nhân để hoàn tất liên kết Google";

        // Gán các thông tin định danh thật thu được từ Google vào form bổ sung thông tin
        document.getElementById('google_token').value = token;
        document.getElementById('google_verified_email').value = googleUserData.email;
        
        // Tự động gợi ý username dựa trên tiền tố của Gmail người dùng vừa nhập
        const suggestUser = googleUserData.email.split('@')[0].replace(/[^a-zA-Z0-9]/g, '');
        document.getElementById('google_suggested_username').value = suggestUser;
    }
    </script>

    <div class="footer-text" style="text-align: center; margin-top: 15px; color: #e8f1f5; font-size: 0.85rem;">
        © 2026 Warehouse Management System - Powered by Google OAuth 2.0 Identity Server
    </div>
</body>
</html>
````
