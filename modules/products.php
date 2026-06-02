<?php
/**
 * 📦 MÔ-ĐUN QUẢN LÝ DANH MỤC SẢN PHẨM (PRODUCT MASTER DATA)
 * Tích hợp tính năng: Thêm, Sửa, Xóa, hiển thị đồng bộ với Odoo Stock Engine.
 */
$pdo = getPDOLayerConnection();
$errors = [];
$messages = [];

// Giá trị mặc định/khởi tạo để tránh "Undefined variable" nếu khối try bị lỗi
$limit = 5;
$page = 1;
$search = '';
$filter_stock = 'all';
$whereClauses = [];
$params = [];
$products = [];

// Khởi tạo biến xử lý form Sửa
$edit_mode = false;
$edit_product = null;

try {
    // Khởi tạo bảng products nếu chưa tồn tại, rồi bổ sung thêm các cột còn thiếu
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sku VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(255) NOT NULL,
                description TEXT DEFAULT NULL,
                price DECIMAL(14,2) NOT NULL DEFAULT 0,
                qty INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $requiredColumns = [
            'description' => 'TEXT DEFAULT NULL',
            'price' => 'DECIMAL(14,2) NOT NULL DEFAULT 0',
            'qty' => 'INT NOT NULL DEFAULT 0',
        ];
        foreach ($requiredColumns as $column => $definition) {
            // Một số server MySQL/DB driver không hỗ trợ placeholder trong SHOW COLUMNS,
            // nên sử dụng quote() để an toàn và tránh lỗi SQL chứa '?'
            $columnCheck = $pdo->query("SHOW COLUMNS FROM products LIKE " . $pdo->quote($column));
            $colExists = $columnCheck && $columnCheck->fetch();
            if (!$colExists) {
                $pdo->exec("ALTER TABLE products ADD COLUMN {$column} {$definition}");
            }
        }
    } catch (PDOException $schemaException) {
        // Nếu có lỗi cấu trúc, hiển thị chi tiết để dễ sửa.
        throw new Exception('Lỗi tạo/bổ sung bảng products: ' . $schemaException->getMessage());
    }
    // 🛑 1. XỬ LÝ HÀNH ĐỘNG XÓA (DELETE)
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['sku'])) {
        $delete_sku = trim($_GET['sku']);
        $stmtDelete = $pdo->prepare("DELETE FROM products WHERE sku = ?");
        $stmtDelete->execute([$delete_sku]);
        if ($stmtDelete->rowCount() > 0) {
            $messages[] = "Đã xóa sản phẩm với mã SKU [{$delete_sku}] thành công.";
        } else {
            $errors[] = "Không tìm thấy sản phẩm SKU [{$delete_sku}] để xóa.";
        }
    }

    // 🛑 2. XỬ LÝ HÀNH ĐỘNG LẤY THÔNG TIN ĐỂ SỬA (GET EDIT DATA)
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['sku'])) {
        $edit_sku = trim($_GET['sku']);
        $stmtGetEdit = $pdo->prepare("SELECT * FROM products WHERE sku = ?");
        $stmtGetEdit->execute([$edit_sku]);
        $edit_product = $stmtGetEdit->fetch();
        if ($edit_product) {
            $edit_mode = true;
        } else {
            $errors[] = "Không tìm thấy sản phẩm SKU [{$edit_sku}] để sửa.";
        }
    }

    // 🛑 3. XỬ LÝ FORM SUBMIT (THÊM MỚI HOẶC CẬP NHẬT)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
        $sku = trim($_POST['sku'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
        $qty = filter_var($_POST['qty'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

        if ($price === false || $price < 0) {
            $errors[] = "Giá sản phẩm phải là số hợp lệ và không được âm.";
        }
        if ($qty === false || $qty < 0) {
            $errors[] = "Số lượng tồn kho phải là số nguyên không âm.";
        }
        if (empty($sku) || empty($name)) {
            $errors[] = "Mã SKU và Tên sản phẩm không được để trống.";
        }

        if (empty($errors)) {
            if (isset($_POST['is_edit_mode']) && $_POST['is_edit_mode'] == '1') {
                $stmtUpdate = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, qty = ? WHERE sku = ?");
                $stmtUpdate->execute([$name, $description, $price, $qty, $sku]);
                if ($stmtUpdate->rowCount() > 0) {
                    $messages[] = "Đã cập nhật thông tin sản phẩm [{$sku}] thành công.";
                    $edit_mode = false;
                } else {
                    $errors[] = "Không có thay đổi nào được lưu hoặc sản phẩm SKU [{$sku}] không tồn tại.";
                }
            } else {
                $stmtCheck = $pdo->prepare("SELECT sku FROM products WHERE sku = ?");
                $stmtCheck->execute([$sku]);
                if ($stmtCheck->fetch()) {
                    $errors[] = "Mã SKU [{$sku}] này đã tồn tại trong hệ thống.";
                } else {
                    $stmtInsert = $pdo->prepare("INSERT INTO products (sku, name, description, price, qty) VALUES (?, ?, ?, ?, ?)");
                    $stmtInsert->execute([$sku, $name, $description, $price, $qty]);
                    $messages[] = "Đã thêm mới thành công sản phẩm [{$sku}]";
                }
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
$totalPages = max(1, ceil($totalRows / $limit));
$page = min(max($page, 1), $totalPages);
$offset = ($page - 1) * $limit;

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
                        <a href="index.php?page=products&action=edit&sku=<?php echo urlencode($prod['sku']); ?>" style="background-color: #3498db; color: white; padding: 6px 12px; font-size: 0.85rem; font-weight: bold; text-decoration: none; border-radius: 4px; transition: background 0.2s;">Sửa</a>
                        <a href="index.php?page=products&action=delete&sku=<?php echo urlencode($prod['sku']); ?>" onclick="return confirm('Bạn chắc chắn muốn xóa sản phẩm này?');" style="background-color: #e74c3c; color: white; padding: 6px 12px; font-size: 0.85rem; font-weight: bold; text-decoration: none; border-radius: 4px; transition: background 0.2s;">Xóa</a>
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
        <a href="index.php?page=products&p=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&filter_stock=<?= urlencode($filter_stock) ?>" 
           style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 4px; text-decoration: none; color: #1e3d59; background: white;">&laquo; Trước</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="index.php?page=products&p=<?= $i ?>&search=<?= urlencode($search) ?>&filter_stock=<?= urlencode($filter_stock) ?>" 
           style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 4px; text-decoration: none; font-weight: bold;
                  <?= $page === $i ? 'background: #17b978; color: white; border-color: #17b978;' : 'background: white; color: #333;' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
        <a href="index.php?page=products&p=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&filter_stock=<?= urlencode($filter_stock) ?>" 
           style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 4px; text-decoration: none; color: #1e3d59; background: white;">Sau &raquo;</a>
    <?php endif; ?>

</div>

<div style="text-align: center; margin-top: 8px; color: #64748b; font-size: 0.85rem; font-family: sans-serif;">
    Hiển thị trang <?= $page ?> / <?= $totalPages ?> (Tổng số kết quả: <?= $totalRows ?> sản phẩm)
</div>
<?php endif; ?>