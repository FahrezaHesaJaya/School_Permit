<?php
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

class Registration {
    private $db;
    public $success_message = "";
    public $error_message = "";

    public function __construct($db) {
        $this->db = $db;
    }

    public function registerUser($username, $password, $role, $student_id, $kelas, $wali_kelas) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (username, password, role, student_id, kelas, wali_kelas) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ssssss", $username, $hashed_password, $role, $student_id, $kelas, $wali_kelas);
            if ($stmt->execute()) {
                $this->success_message = "Registration successful!";
            } else {
                $this->error_message = "Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $this->error_message = "Failed to prepare statement.";
        }
    }
}

// Inisialisasi database
$db = new Database('localhost', 'root', '', 'izin_sekolah');
$registration = new Registration($db->getConnection());

// Tangani register
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $student_id = $_POST['student_id'];
    $kelas = $_POST['kelas'];
    $wali_kelas = $_POST['wali_kelas'];

    $registration->registerUser($username, $password, $role, $student_id, $kelas, $wali_kelas);
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" type="text/css" href="css/style_register.css">
</head>
<body>
    <div class="container">
        <h2>Register</h2>
        <?php if (!empty($registration->success_message)) { ?>
            <p class="success-message"><?php echo htmlspecialchars($registration->success_message); ?></p>
        <?php } ?>
        <?php if (!empty($registration->error_message)) { ?>
            <p class="error-message"><?php echo htmlspecialchars($registration->error_message); ?></p>
        <?php } ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="student_id">ID Siswa:</label>
                <input type="text" id="student_id" name="student_id" required>
            </div>
            <div class="form-group">
                <label for="kelas">Kelas:</label>
                <input type="text" id="kelas" name="kelas" required>
            </div>
            <div class="form-group">
                <label for="wali_kelas">Nama Wali Kelas:</label>
                <input type="text" id="wali_kelas" name="wali_kelas" required>
            </div>
            <div class="form-group">
                <label for="role">Daftar Sebagai:</label>
                <select name="role">
                    <option value="siswa">Siswa</option>
                </select>
            </div>
            <input type="submit" value="Register">
        </form>
        <a href="index.php">Kembali ke Halaman Utama</a>
    </div>
</body>
</html>
