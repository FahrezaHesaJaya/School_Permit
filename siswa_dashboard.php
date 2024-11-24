<?php
session_start();

class Database {
    private $conn;

    public function __construct($host, $user, $password, $dbname) {
        $this->conn = new mysqli($host, $user, $password, $dbname);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function __destruct() {
        $this->conn->close();
    }
}

class PermissionRequest {
    private $db;
    private $userId;

    public $successMessage = "";
    public $errorMessage = "";

    public function __construct($db, $userId) {
        $this->db = $db;
        $this->userId = $userId;
    }

    public function submitRequest($reason) {
        $sql = "INSERT INTO permissions (user_id, reason) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("is", $this->userId, $reason);

        if ($stmt->execute()) {
            $this->successMessage = "Permission request submitted!";
        } else {
            $this->errorMessage = "Error: " . $stmt->error;
        }

        $stmt->close();
    }

    public function getRequests() {
        $sql = "SELECT * FROM permissions WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $requests = [];
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }

        $stmt->close();
        return $requests;
    }
}

// Initialize database and PermissionRequest
$db = new Database('localhost', 'root', '', 'izin_sekolah');
$conn = $db->getConnection();

if (!isset($_SESSION['user_id'])) {
    die("User ID tidak ditemukan. Silakan login kembali.");
}

$userId = $_SESSION['user_id'];
$permissionRequest = new PermissionRequest($conn, $userId);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reason'])) {
    $reason = $_POST['reason'];
    $permissionRequest->submitRequest($reason);
}

// Fetch permission requests
$requests = $permissionRequest->getRequests();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siswa Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/style_dashboard_siswa.css">
</head>
<body>

    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <h2>Informasi Siswa</h2>
            <div class="user-info">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                <p><strong>ID Siswa:</strong> <?php echo htmlspecialchars($_SESSION['student_id']); ?></p>
                <p><strong>Kelas:</strong> <?php echo htmlspecialchars($_SESSION['kelas']); ?></p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Formulir Permintaan Izin -->
            <div class="form-container">
                <h2>Formulir Permintaan Izin</h2>
                <?php if (!empty($permissionRequest->successMessage)) { ?>
                    <p class="success"><?php echo htmlspecialchars($permissionRequest->successMessage); ?></p>
                <?php } elseif (!empty($permissionRequest->errorMessage)) { ?>
                    <p class="error"><?php echo htmlspecialchars($permissionRequest->errorMessage); ?></p>
                <?php } ?>

                <form method="post" action="">
                    <div class="form-group">
                        <label for="reason">Alasan Permintaan Izin:</label>
                        <textarea id="reason" name="reason" rows="5" required></textarea>
                    </div>
                    <input type="submit" value="Ajukan Izin">
                </form>
            </div>

            <!-- Status Permintaan Izin -->
            <div class="status-container">
                <h2>Status Permintaan Izin</h2>
                <ul class="permission-list">
                    <?php if (!empty($requests)) { ?>
                        <?php foreach ($requests as $row) { ?>
                            <li class="permission-item">
                                <div class="permission-info">
                                    <span><strong>Alasan:</strong> <?php echo htmlspecialchars($row['reason']); ?></span>
                                    <span class="status <?php echo strtolower($row['status']); ?>">
                                        <strong>Status:</strong> <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </div>
                                <?php if (strtolower($row['status']) === 'approved') { ?>
                                    <form method="post" action="generate_pdf.php" target="_blank">
                                        <input type="hidden" name="permission_id" value="<?php echo $row['id']; ?>">
                                        <input type="submit" value="Unduh PDF">
                                    </form>
                                <?php } ?>
                            </li>
                        <?php } ?>
                    <?php } else { ?>
                        <li><p>Tidak ada data izin yang ditemukan.</p></li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Logout Button -->
    <form method="post" action="logout.php">
        <button type="submit" class="logout">Logout</button>
    </form>

</body>
</html>
