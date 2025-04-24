<?php
require_once 'cors.php';
require_once 'config.php';

header('Content-Type: application/json');

// Bật hiển thị lỗi để debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $conn = getDBConnection();

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['group_id']) || !isset($data['mssv'])) {
        throw new Exception('Thiếu thông tin bắt buộc');
    }

    $group_id = (int) $data['group_id'];
    $mssv = $data['mssv'];

    // Log dữ liệu nhận được
    error_log("Received data: group_id=$group_id, mssv=$mssv");

    // Kiểm tra nhóm tồn tại và chưa đầy
    $sql_group = "SELECT max_members, 
                         (SELECT COUNT(*) FROM group_members WHERE group_id = ?) as member_count,
                         session_id
                  FROM student_groups 
                  WHERE id = ?";
    $stmt_group = $conn->prepare($sql_group);
    $stmt_group->bind_param("ii", $group_id, $group_id);
    $stmt_group->execute();
    $result_group = $stmt_group->get_result();
    $group = $result_group->fetch_assoc();

    if (!$group) {
        throw new Exception('Không tìm thấy nhóm');
    }

    if ($group['member_count'] >= $group['max_members']) {
        throw new Exception('Nhóm đã đầy');
    }

    // Kiểm tra xem sinh viên đã ở trong nhóm nào khác trong cùng ca học chưa
    $session_id = $group['session_id'];
    $sql_check = "SELECT gm.group_id 
                  FROM group_members gm 
                  INNER JOIN student_groups sg ON gm.group_id = sg.id 
                  WHERE gm.mssv = ? AND sg.session_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("si", $mssv, $session_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        throw new Exception('Sinh viên đã ở trong một nhóm khác trong ca học này');
    }

    // Thêm sinh viên vào nhóm
    $sql_insert = "INSERT INTO group_members (group_id, mssv) VALUES (?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("is", $group_id, $mssv);
    $stmt_insert->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Tham gia nhóm thành công'
    ]);

} catch (Exception $e) {
    error_log("Error in join_group.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        closeDBConnection($conn);
    }
}
?>