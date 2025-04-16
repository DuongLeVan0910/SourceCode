<?php
error_log("✅ Đã vào db.php");
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "qlsv";

// Kết nối database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
error_log("✅ Kết nối DB thành công");
?>
