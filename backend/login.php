

 <?php

// header("Access-Control-Allow-Origin: *"); // Cho phép tất cả các domain truy cập
// header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
// header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// header("Access-Control-Allow-Credentials: true");
// header("Content-Type: application/json; charset=UTF-8");
// require 'db.php';
// if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//     echo json_encode(["success" => false, "message" => "Chỉ chấp nhận phương thức POST"]);
//     exit();
// }

// if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
//     http_response_code(200);
//     exit();
// }
// $json = file_get_contents("php://input");

// // Debug xem dữ liệu có nhận được không
// file_put_contents("debug.log", "📥 Dữ liệu nhận được: " . $json . PHP_EOL, FILE_APPEND);

// // Giải mã JSON
// $data = json_decode($json, true);

// // In ra JSON để kiểm tra
// file_put_contents("debug.log", "✅ JSON decode thành công: " . print_r($data, true) . PHP_EOL, FILE_APPEND);

// echo json_encode(["success" => true, "message" => "Đăng nhập thành công"]);
// file_put_contents("debug.log", "🛠 SQL Query: " . $sql . PHP_EOL, FILE_APPEND);
// file_put_contents("debug.log", "🔍 Email nhập vào: " . $email . PHP_EOL, FILE_APPEND);
// if (!$data || !isset($data['email']) || !isset($data['password'])) {
//     echo json_encode(["success" => false, "message" => "Thiếu email hoặc mật khẩu"]);
//     exit();
// }

// $email = $data['email'];
// $password = $data['password'];

// // Kiểm tra email có tồn tại không
// $sql = "SELECT * FROM users WHERE email = ?";
// $stmt = $conn->prepare($sql);
// $stmt->bind_param("s", $email);
// $stmt->execute();
// $result = $stmt->get_result();

// if ($result->num_rows === 1) {
//     $user = $result->fetch_assoc();

//     // Kiểm tra mật khẩu có đúng không
//     if ($password === $user['password']) {
//         $token = bin2hex(random_bytes(16)); // Tạo token ngẫu nhiên
//         echo json_encode([
//             "success" => true,
//             "message" => "Đăng nhập thành công",
//             "user" => [
//                 "id" => $user['id'],
//                 "email" => $user['email'],
//                 "role" => $user['role']
//             ]
//         ]);
//     } else {
//         echo json_encode(["success" => false, "message" => "Mật khẩu không đúng"]);
//     }
// } else {
//     echo json_encode(["success" => false, "message" => "Email không tồn tại"]);
// }

// $stmt->close();
// $conn->close();


error_log("🔥 PHP đã chạy đến login.php");
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Credentials: true");


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
error_log("🔥 Đã xử lý OPTIONS, tiếp tục xử lý POST");
require 'db.php';

$json = file_get_contents("php://input");
$data = json_decode($json, true);

if (!$data || !isset($data['email']) || !isset($data['password'])) {
    error_log("❌ Lỗi: Thiếu email hoặc mật khẩu");
    echo json_encode(["success" => false, "message" => "Thiếu email hoặc mật khẩu"]);
    exit();
}
error_log("✅ Nhận dữ liệu: " . json_encode($data));

$email = $data['email'];
$password = $data['password'];

$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("❌ Lỗi SQL: " . $conn->error);
    echo json_encode(["success" => false, "message" => "Lỗi truy vấn"]);
    exit();
}
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
error_log("✅ Truy vấn SQL thực thi thành công");
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    error_log("✅ User tồn tại trong DB");
    if (password_verify($password, $user['password'])) {
        error_log("✅ Mật khẩu đúng, đăng nhập thành công");
        echo json_encode([
            "success" => true,
            "message" => "Đăng nhập thành công",
            "user" => [
                "id" => $user['id'],
                "email" => $user['email'],
                "role" => $user['role']
            ]
        ]);
    } else {
        error_log("❌ Mật khẩu sai");
        echo json_encode(["success" => false, "message" => "Mật khẩu không đúng"]);
    }
} else {
    error_log("❌ Email không tồn tại");
    echo json_encode(["success" => false, "message" => "Email không tồn tại"]);
}

$stmt->close();
$conn->close();
