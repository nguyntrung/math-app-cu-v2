<?php
session_start();

if (!isset($_SESSION['MaNguoiDung'])) {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['HoTen'];
include '../../database/db.php';

$selectedTable = isset($_GET['table']) ? $_GET['table'] : null;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addData'])) {
    // Thêm dữ liệu
    try {
        $table = htmlspecialchars($selectedTable);
        $stmt = $conn->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $sql = "INSERT INTO $table (" . implode(", ", $columns) . ") VALUES (:" . implode(", :", $columns) . ")";
        $stmt = $conn->prepare($sql);

        foreach ($columns as $column) {
            if (isset($_POST[$column])) {
                $stmt->bindValue(":" . $column, $_POST[$column]);
            }
        }

        $stmt->execute();
        $message = "Thêm dữ liệu thành công!";
    } catch (PDOException $e) {
        $message = "Lỗi khi thêm dữ liệu: " . $e->getMessage();
    }
}

// Sửa dữ liệu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editData'])) {
    try {
        $id = $_POST['Ma']; // Khóa chính
        $setPart = '';

        // Lấy tên cột chính
        $stmt = $conn->query("SHOW COLUMNS FROM " . htmlspecialchars($selectedTable));
        $primaryKeyColumn = null;

        // Xác định cột khóa chính
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['Key'] === 'PRI') {
                $primaryKeyColumn = $row['Field'];
                break;
            }
        }

        // Kiểm tra nếu không tìm thấy cột khóa chính
        if (!$primaryKeyColumn) {
            throw new Exception("Không tìm thấy cột khóa chính trong bảng.");
        }

        // Tạo phần SET cho câu truy vấn
        foreach ($_POST as $key => $value) {
            if ($key !== 'editData' && $key !== 'Ma') {
                $setPart .= "`$key` = :$key, ";
            }
        }

        // Xóa dấu phẩy cuối cùng nếu nó tồn tại
        $setPart = rtrim($setPart, ', '); // Bỏ dấu phẩy cuối cùng

        // Kiểm tra nếu không có cột nào được cập nhật
        if (empty($setPart)) {
            throw new Exception("Không có dữ liệu nào để cập nhật.");
        }

        // Câu truy vấn UPDATE
        $sql = "UPDATE " . htmlspecialchars($selectedTable) . " SET $setPart WHERE `$primaryKeyColumn` = :id"; 
        $stmt = $conn->prepare($sql);
        
        // Gán giá trị cho khóa chính
        $stmt->bindParam(':id', $id); 

        // Gán giá trị cho các tham số trong phần SET
        foreach ($_POST as $key => $value) {
            if ($key !== 'editData' && $key !== 'Ma') {
                $stmt->bindValue(":$key", $value);
            }
        }

        // Thực thi câu truy vấn
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                $message = "Sửa dữ liệu thành công!";
            } else {
                $message = "Không có bản ghi nào được cập nhật.";
            }
        } else {
            $message = "Lỗi khi sửa dữ liệu: " . $stmt->errorInfo()[2];
        }
    } catch (PDOException $e) {
        $message = "Lỗi khi sửa dữ liệu: " . $e->getMessage();
    } catch (Exception $e) {
        $message = "Lỗi: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteData'])) {
    // Xóa dữ liệu
    try {
        $id = $_POST['deleteMa']; 

        // Lấy tên cột chính
        $stmt = $conn->query("SHOW COLUMNS FROM " . htmlspecialchars($selectedTable));
        $primaryKeyColumn = null;

        // Xác định cột khóa chính
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['Key'] === 'PRI') {
                $primaryKeyColumn = $row['Field'];
                break;
            }
        }

        $sql = "DELETE FROM " . htmlspecialchars($selectedTable) . " WHERE $primaryKeyColumn = :id"; 
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id); 

        $stmt->execute();
        $message = "Xóa dữ liệu thành công!";
    } catch (PDOException $e) {
        $message = "Lỗi khi xóa dữ liệu: " . $e->getMessage();
    }
}

$data = [];
if ($selectedTable) {
    try {
        $stmt = $conn->prepare("SELECT * FROM " . htmlspecialchars($selectedTable));
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Lỗi khi lấy dữ liệu: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Quản Lý Dữ Liệu</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
</head>
<style>
.collapse-inner {
    max-height: 300px;
    overflow-y: auto;
}
</style>

<body id="page-top">
    <div id="wrapper">
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="admin.php">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Quản lý Dữ Liệu</div>
            </a>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Quản Lý Dữ Liệu</div>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo"
                    aria-expanded="true" aria-controls="collapseTwo">
                    <i class="fas fa-fw fa-cog"></i>
                    <span>Dữ Liệu</span>
                </a>
                <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Danh sách các dữ liệu</h6>
                        <?php
                        function convertTableName($tableName) {
                            switch ($tableName) {
                                case 'baigiai':
                                    return 'Bài Giải';
                                case 'baihoc':
                                    return 'Bài Học';
                                case 'cauhoitracnghiem':
                                    return 'Câu Hỏi Trắc Nghiệm';
                                case 'cauhoituluan':
                                    return 'Câu Hỏi Tự Luận';
                                case 'cautraloi':
                                    return 'Câu Trả Lời';
                                case 'chuonghoc':
                                    return 'Chương Học';
                                case 'lythuyet':
                                    return 'Lý Thuyết';
                                case 'dangkythanhvien':
                                    return 'Đăng Ký Thành Viên';
                                case 'nguoidung':
                                    return 'Người Dùng';
                                case 'thanhtoan':
                                    return 'Thanh Toán';
                                case 'tiendohoctap':
                                    return 'Tiến Độ Học Tập';
                                case 'videobaihoc':
                                    return 'Video Bài Học';
                                default:
                                    return ucwords(str_replace('_', ' ', $tableName));
                            }
                        }

                        try {
                            $stmt = $conn->query("SHOW TABLES");
                            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        } catch (PDOException $e) {
                            echo "Lỗi khi lấy danh sách bảng: " . $e->getMessage();
                        }

                        $excludeTables = ['dangkythanhvien', 'nguoidung', 'tiendohoctap', 'thanhtoan', 'cautraloi'];

                        if ($tables): ?>
                        <?php foreach ($tables as $table): ?>
                        <?php if (!in_array($table, $excludeTables)): ?>
                        <a class="collapse-item" href="view_data.php?table=<?php echo htmlspecialchars($table); ?>">
                            <?php echo htmlspecialchars(convertTableName($table)); ?>
                        </a>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <p class="collapse-item">Không có bảng nào trong cơ sở dữ liệu.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseUtilities"
                    aria-expanded="true" aria-controls="collapseUtilities">
                    <i class="fas fa-fw fa-wrench"></i>
                    <span>Thao Tác</span>
                </a>
                <div id="collapseUtilities" class="collapse" aria-labelledby="headingUtilities"
                    data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded" style="max-height: 300px; overflow-y: auto;">
                        <?php if ($selectedTable): ?>
                        <h6 class="collapse-header">Bảng
                            <?php echo htmlspecialchars(convertTableName($selectedTable)); ?></h6>
                        <a class="collapse-item" data-toggle="modal" data-target="#addModal">Thêm dữ liệu</a>
                        <a class="collapse-item" data-toggle="modal" data-target="#editModal">Sửa dữ liệu</a>
                        <a class="collapse-item" data-toggle="modal" data-target="#deleteModal">Xóa dữ liệu</a>
                        <?php endif; ?>
                    </div>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="admin.php">
                    <i class="fas fa-fw fa-sign-out-alt"></i>
                    <span>Thoát</span>
                </a>
            </li>
        </ul>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <h2><?php echo htmlspecialchars($selectedTable ? convertTableName($selectedTable) : 'Chọn bảng'); ?>
                    </h2>
                </nav>

                <div class="container-fluid">
                    <?php if ($message): ?>
                    <div class="alert alert-info"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <?php if ($data): ?>
                                    <?php foreach (array_keys($data[0]) as $column): ?>
                                    <th><?php echo htmlspecialchars($column); ?></th>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($data): ?>
                                <?php foreach ($data as $row): ?>
                                <tr>
                                    <?php foreach ($row as $value): ?>
                                    <td><?php echo htmlspecialchars($value); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="100%">Không có dữ liệu trong bảng.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Thêm -->
    <div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Thêm Dữ Liệu</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <?php
                        if ($selectedTable) {
                            $stmt = $conn->query("DESCRIBE " . htmlspecialchars($selectedTable));
                            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            foreach ($columns as $column) {
                                echo '<div class="form-group">
                                        <label for="' . htmlspecialchars($column) . '">' . htmlspecialchars($column) . '</label>
                                        <input type="text" class="form-control" name="' . htmlspecialchars($column) . '" required>
                                      </div>';
                            }
                        }
                        ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary" name="addData">Thêm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Sửa -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Sửa Dữ Liệu</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="Ma">Chọn Mã (Khóa Chính)</label>
                            <select class="form-control" name="Ma" id="primaryKeySelect" required>
                                <?php
                                // Lấy tên cột khóa chính
                                if ($selectedTable) {
                                    $stmt = $conn->query("SHOW COLUMNS FROM " . htmlspecialchars($selectedTable));
                                    $primaryKeyColumn = null;
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        if ($row['Key'] === 'PRI') {
                                            $primaryKeyColumn = $row['Field'];
                                            break;
                                        }
                                    }

                                    if ($primaryKeyColumn) {
                                        // Lấy dữ liệu từ bảng để chọn khóa chính
                                        $stmt = $conn->prepare("SELECT $primaryKeyColumn FROM " . htmlspecialchars($selectedTable));
                                        $stmt->execute();
                                        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($rows as $row) {
                                            echo '<option value="' . htmlspecialchars($row[$primaryKeyColumn]) . '">' . htmlspecialchars($row[$primaryKeyColumn]) . '</option>';
                                        }
                                    } else {
                                        echo '<option disabled>Không tìm thấy cột khóa chính</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <?php
                        // Tạo các trường dữ liệu khác
                        if ($selectedTable) {
                            $stmt = $conn->query("DESCRIBE " . htmlspecialchars($selectedTable));
                            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            foreach ($columns as $column) {
                                if ($column !== $primaryKeyColumn) { // Bỏ qua cột khóa chính
                                    echo '<div class="form-group">
                                            <label for="' . htmlspecialchars($column) . '">' . htmlspecialchars($column) . '</label>
                                            <input type="text" class="form-control" name="' . htmlspecialchars($column) . '" id="' . htmlspecialchars($column) . '" data-column="' . htmlspecialchars($column) . '">
                                        </div>';
                                }
                            }
                        }
                        ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary" name="editData">Sửa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('#primaryKeySelect').on('change', function() {
            var selectedId = $(this).val();
            var tableName = "<?php echo htmlspecialchars($selectedTable); ?>"; // Lấy tên bảng hiện tại
            $.ajax({
                url: 'get_data.php', // Đường dẫn đến file PHP để lấy dữ liệu
                type: 'GET',
                data: {
                    id: selectedId,
                    table: tableName
                },
                success: function(response) {
                    var data = JSON.parse(response);
                    // Điền dữ liệu vào các input
                    <?php
                    foreach ($columns as $column) {
                        if ($column !== $primaryKeyColumn) {
                            echo "if (data.hasOwnProperty('" . htmlspecialchars($column) . "')) {
                                $('#" . htmlspecialchars($column) . "').val(data['" . htmlspecialchars($column) . "']);
                            }";
                        }
                    }
                    ?>
                }
            });
        });
    });
    </script>

    <!-- Modal Xóa -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Xóa Dữ Liệu</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="deleteMa">Chọn Mã (Khóa Chính)</label>
                            <select class="form-control" name="deleteMa" required>
                                <?php
                            if ($selectedTable) {
                                // Lấy tên cột khóa chính
                                $stmt = $conn->query("SHOW COLUMNS FROM " . htmlspecialchars($selectedTable));
                                $primaryKeyColumn = null;
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    if ($row['Key'] === 'PRI') {
                                        $primaryKeyColumn = $row['Field'];
                                        break;
                                    }
                                }

                                if ($primaryKeyColumn) {
                                    // Lấy dữ liệu từ bảng để chọn khóa chính
                                    $stmt = $conn->prepare("SELECT $primaryKeyColumn FROM " . htmlspecialchars($selectedTable));
                                    $stmt->execute();
                                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($rows as $row) {
                                        echo '<option value="' . htmlspecialchars($row[$primaryKeyColumn]) . '">' . htmlspecialchars($row[$primaryKeyColumn]) . '</option>';
                                    }
                                } else {
                                    echo '<option disabled>Không tìm thấy cột khóa chính</option>';
                                }
                            }
                            ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-danger" name="deleteData">Xóa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
</body>

</html>