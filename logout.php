<?php
class Logout {
    public function execute() {
        // Memulai sesi jika belum dimulai
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Hancurkan sesi
        session_destroy();

        // Redirect ke halaman login
        header("Location: login.php");
        exit;
    }
}

// Eksekusi logout
$logout = new Logout();
$logout->execute();
?>
