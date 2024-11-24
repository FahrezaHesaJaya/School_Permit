<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require('fpdf/fpdf.php');
include('db.php'); // Include the database connection file

// Check if session variables are set
if (!isset($_SESSION['username']) || !isset($_SESSION['kelas'])) {
    die('Data sesi tidak ditemukan. Harap login kembali.');
}

// Initialize the database connection using the Singleton pattern
$db = Database::getInstance(); // Get the Singleton instance
$conn = $db->getConnection();  // Get the connection object

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['permission_id'])) {
    $permission_id = intval($_POST['permission_id']);

    // Query to get permission and user data
    $sql = "
        SELECT 
            p.*, 
            u.* 
        FROM permissions p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = '$permission_id'
    ";
    $result = $conn->query($sql); // Use the connection object to execute the query

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Check if logos exist
        if (!file_exists('images/logo_sekolah.png') || !file_exists('images/logo_kementrian.png')) {
            die('File logo tidak ditemukan.');
        }

        // Create PDF
        $pdf = new FPDF();
        $pdf->AddPage();
        ob_start();

        // Add logos
        $pdf->Image('images/logo_sekolah.png', 10, 6, 30);
        $pdf->Image('images/logo_kementrian.png', 170, 6, 30);

        // Header and separator line
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'KEMENTERIAN PENDIDIKAN DAN KEBUDAYAAN', 0, 1, 'C');
        $pdf->Cell(0, 10, 'SEKOLAH XYZ', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, 'Alamat Sekolah: Jalan Pendidikan No. 123, Kota ABC', 0, 1, 'C');
        $pdf->Ln(5);
        $pdf->Cell(0, 0, '', 1, 1, 'C'); // Separator line
        $pdf->Ln(10);

        // Student Information
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Data Siswa', 0, 1);
        $pdf->SetFont('Arial', '', 12);
        foreach ($row as $key => $value) {
            if (!in_array($key, ['id', 'password'])) { // Avoid displaying password
                $pdf->Cell(50, 10, ucfirst(str_replace('_', ' ', $key)) . ':', 0, 0);
                $pdf->Cell(0, 10, $value, 0, 1);
            }
        }
        $pdf->Ln(10);

        // Description Paragraph
        $pdf->MultiCell(0, 10, 'Siswa ini telah menerima izin untuk tidak mengikuti kegiatan sekolah selama periode tertentu. Permintaan izin ini telah disetujui oleh pihak sekolah berdasarkan alasan yang diajukan. Harap memastikan bahwa izin ini digunakan sesuai dengan aturan yang berlaku.', 0, 'J');
        $pdf->Ln(10);

        // Signature
        $pdf->Cell(0, 10, 'Tanda Tangan Admin', 0, 1, 'R');
        $pdf->Ln(20);
        $pdf->Cell(0, 10, '_________________', 0, 1, 'R');

        ob_end_clean();
        $pdf->Output();
        exit;
    } else {
        die('Data izin tidak ditemukan.');
    }
} else {
    die('Permission ID tidak dikirim.');
}
?>
