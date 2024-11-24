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

class AdminDashboard {
    private $db;
    public $message = "";

    public function __construct($db) {
        $this->db = $db;
    }

    public function checkAdminSession() {
        if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
            header("Location: login.php");
            exit;
        }
    }

    public function handleAction($permission_id, $action) {
        if ($action === 'delete') {
            $sql = "DELETE FROM permissions WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $permission_id);
        } else {
            $status = ($action === 'approve') ? 'approved' : 'rejected';
            $sql = "UPDATE permissions SET status = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("si", $status, $permission_id);
        }

        if ($stmt->execute()) {
            $this->message = ucfirst($action) . " action successfully performed!";
        } else {
            $this->message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    public function getPermissions() {
        $sql = "SELECT p.id, p.reason, p.status, u.username, u.student_id, u.kelas 
                FROM permissions p 
                JOIN users u ON p.user_id = u.id";
        $result = $this->db->query($sql);

        if (!$result) {
            die("Query Error: " . $this->db->error);
        }

        return $result;
    }

    public function getStudents() {
        $sql = "SELECT username, student_id, kelas, wali_kelas FROM users WHERE role = 'siswa'";
        $result = $this->db->query($sql);

        if (!$result) {
            die("Query Error: " . $this->db->error);
        }

        return $result;
    }
}

// Initialize Database and AdminDashboard
$db = new Database('localhost', 'root', '', 'izin_sekolah');
$conn = $db->getConnection();
$dashboard = new AdminDashboard($conn);

// Check Admin Session
$dashboard->checkAdminSession();

// Handle Actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $permission_id = $_POST['permission_id'];
    $action = $_POST['action'];
    $dashboard->handleAction($permission_id, $action);
}

// Get Permissions and Students
$permissions = $dashboard->getPermissions();
$students = $dashboard->getStudents();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/style_dashboard_admin.css">
</head>
<body>

    <div class="dashboard-container">
        <!-- Left Section: Permission Info -->
        <div class="left-section">
            <h2>Dashboard Admin - Permintaan Izin</h2>

            <?php if (!empty($dashboard->message)) { ?>
                <p class="message"><?php echo htmlspecialchars($dashboard->message); ?></p>
            <?php } ?>

            <h3>Daftar Permintaan Izin</h3>
            <table class="permission-table">
                <thead>
                    <tr>
                        <th>Nama Siswa</th>
                        <th>ID Siswa</th>
                        <th>Kelas</th>
                        <th>Alasan Izin</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($permissions->num_rows > 0) { ?>
                        <?php while ($row = $permissions->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['kelas']); ?></td>
                                <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                                <td>
                                    <form method="post" action="">
                                        <input type="hidden" name="permission_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                        <button type="submit" name="action" value="approve" class="approve-btn">Terima</button>
                                        <button type="submit" name="action" value="reject" class="reject-btn">Tolak</button>
                                        <button type="submit" name="action" value="delete" class="delete-btn">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr><td colspan="6">Tidak ada permintaan izin ditemukan.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <!-- Right Section: Student Data -->
        <div class="right-section">
            <h3>Data Seluruh Siswa</h3>
            <table class="student-table">
                <thead>
                    <tr>
                        <th>Nama Siswa</th>
                        <th>ID Siswa</th>
                        <th>Kelas</th>
                        <th>Wali Kelas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $students->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['kelas']); ?></td>
                            <td><?php echo htmlspecialchars($row['wali_kelas']); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Logout Button -->
    <form method="post" action="logout.php">
        <button type="submit" class="logout">Logout</button>
    </form>

</body>
</html>
