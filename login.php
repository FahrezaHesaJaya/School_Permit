<?php
session_start();

// Database connection
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

// Auth class for handling login
class Auth {
    private $db;
    public $error_message = "";

    public function __construct($db) {
        $this->db = $db;
    }

    public function login($username, $password, $role) {
        $sql = "SELECT * FROM users WHERE username = ? AND role = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $username, $role);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                // Store session variables
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['student_id'] = $row['student_id'];
                $_SESSION['kelas'] = $row['kelas'];

                // Redirect based on role
                if ($row['role'] == 'admin') {
                    header("Location: admin_dashboard.php");
                } elseif ($row['role'] == 'siswa') {
                    header("Location: siswa_dashboard.php"); // Redirect to siswa_dashboard.php for siswa role
                } else {
                    header("Location: dashboard.php"); // Default fallback
                }
                exit;
            } else {
                $this->error_message = "Invalid password.";
            }
        } else {
            $this->error_message = "No user found with that username and role.";
        }
    }
}

// Initialize database connection
$db = new Database('localhost', 'root', '', 'izin_sekolah');
$auth = new Auth($db->getConnection());

// Handle login
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Attempt to login the user
    $auth->login($username, $password, $role);
}

$error_message = $auth->error_message;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" type="text/css" href="css/style_login.css">
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <?php if (!empty($error_message)) { ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
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
                <label for="role">Login as:</label>
                <select id="role" name="role" required>
                    <option value="siswa">Siswa</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <input type="submit" value="Login">
        </form>
        <a href="register.php">Register</a>
    </div>
</body>
</html>
