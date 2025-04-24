<?php

require_once 'cors.php';
require_once 'config.php';

header('Content-Type: application/json');
$start_time = microtime(true);

// Bật hiển thị lỗi để debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $conn = getDBConnection();

    $data = json_decode(file_get_contents('php://input'), true);
// Sau khi đã decode $data
$number_of_groups = isset($data['number_of_groups']) 
    ? (int) $data['number_of_groups'] 
    : 0;

    if (!isset($data['session_id']) || !isset($data['mode'])) {
        throw new Exception('Thiếu thông tin bắt buộc');
    }

    $session_id = $data['session_id'];
    $mode = $data['mode'];
    $min_members = isset($data['min_members']) ? (int) $data['min_members'] : 2;
    $max_members = isset($data['max_members']) ? (int) $data['max_members'] : 5;
    $students = isset($data['students']) ? $data['students'] : [];

    if ($min_members > $max_members) {
        throw new Exception('Số thành viên tối thiểu không thể lớn hơn tối đa');
    }

    // Log dữ liệu nhận được
    error_log("Received data: " . print_r($data, true));

    $sql_students = "SELECT s.mssv 
                     FROM students s 
                     INNER JOIN class_session_students css ON s.mssv = css.mssv 
                     WHERE css.session_id = ?";
    $stmt_students = $conn->prepare($sql_students);
    $stmt_students->bind_param("i", $session_id);
    $stmt_students->execute();
    $result_students = $stmt_students->get_result();

    $all_students = [];
    while ($row = $result_students->fetch_assoc()) {
        $all_students[] = $row['mssv'];
    }

    if (empty($all_students)) {
        throw new Exception('Không có sinh viên nào trong ca học này');
    }

    // Kiểm tra số lượng sinh viên trước khi chia nhóm
    $total_students = count($all_students);

    if ($total_students > $max_members && $total_students < 2 * $min_members) {
        throw new Exception('Số lượng sinh viên ít hơn yêu cầu tối thiểu');
    }

    if ($mode === 'random') {
        $sql_already_assigned = "SELECT gm.mssv 
        FROM group_members gm 
        INNER JOIN student_groups sg ON gm.group_id = sg.id 
        WHERE sg.session_id = ? AND sg.mode IN ('teacher', 'random')";
        $stmt_assigned = $conn->prepare($sql_already_assigned);
        $stmt_assigned->bind_param("i", $session_id);
        $stmt_assigned->execute();
        $result_assigned = $stmt_assigned->get_result();

        $already_assigned_students = [];
        while ($row = $result_assigned->fetch_assoc()) {
            $already_assigned_students[] = $row['mssv'];
        }

        // Loại bỏ tất cả sinh viên đã được chia (bởi teacher hoặc random trước đó)
        $available_students = array_diff($all_students, $already_assigned_students);
        $available_students = array_values($available_students); // reset chỉ số mảng

        if (empty($available_students)) {
            throw new Exception('Tất cả sinh viên đã được chia nhóm');
        }

        shuffle($available_students); // Xáo trộn danh sách sinh viên
        $total_students = count($available_students);
        $remaining_students = $available_students;
        $group_index = 1;
        $created_groups = [];

        $min_groups = ceil($total_students / $max_members);
        $max_possible_groups = floor($total_students / $min_members);

        $overLimitWarning = false;

        while (!empty($remaining_students) && count($created_groups) < $max_possible_groups) {
            $remaining_count = count($remaining_students);
            $remaining_groups = $max_possible_groups - count($created_groups);

            $min_for_this_group = $min_members;
            $max_for_this_group = min($max_members, $remaining_count);

            if ($remaining_groups > 1) {
                $max_for_this_group = min($max_members, $remaining_count - ($remaining_groups - 1) * $min_members);
            }

            $members_count = rand($min_for_this_group, $max_for_this_group);

            if ($remaining_count - $members_count < $min_members && $remaining_count > $members_count) {
                $members_count = $remaining_count - $min_members;
            }
            if ($members_count < $min_members) {
                $members_count = $remaining_count;
            }

            if ($members_count > $max_members) {
                $overLimitWarning = true;
            }

            $group_name = "Nhóm $group_index";

            $insert_group_sql = "INSERT INTO student_groups (name, mode, min_members, max_members, session_id) 
                                VALUES (?, ?, ?, ?, ?)";
            $insert_group_stmt = $conn->prepare($insert_group_sql);
            $insert_group_stmt->bind_param("ssiii", $group_name, $mode, $min_members, $max_members, $session_id);
            $insert_group_stmt->execute();
            $group_id = $conn->insert_id;

            $group_members = array_splice($remaining_students, 0, $members_count);

            $insert_member_sql = "INSERT INTO group_members (group_id, mssv) VALUES (?, ?)";
            $insert_member_stmt = $conn->prepare($insert_member_sql);
            foreach ($group_members as $mssv) {
                $insert_member_stmt->bind_param("is", $group_id, $mssv);
                $insert_member_stmt->execute();
            }

            $created_groups[] = [
                'id' => $group_id,
                'name' => $group_name,
                'mode' => $mode,
                'min_members' => $min_members,
                'max_members' => $max_members
            ];

            $group_index++;
        }
        
    $execution_time = round(microtime(true) - $start_time, 4);
        $response_message = $overLimitWarning
            ? 'Một số nhóm đã vượt quá số thành viên tối đa nhưng vẫn được tạo.'
            : 'Tạo tất cả nhóm thành công';

        echo json_encode([
            'success' => true,
            'message' => $response_message,
            'groups' => $created_groups,
    'execution_time' => $execution_time . ' giây'

        ]);
    }

    else if ($mode === 'teacher') {
        if (empty($students)) {
            throw new Exception('Vui lòng cung cấp danh sách sinh viên cho chế độ này');
        }
        $overLimitWarning = false;

        shuffle($students);
        $total_students = count($students);
        $remaining_students = $students;

        $group_index = 1;
        $created_groups = [];

        $min_groups = ceil($total_students / $max_members);
        $max_possible_groups = floor($total_students / $min_members);

        $count_groups_sql = "SELECT COUNT(*) as group_count FROM student_groups WHERE session_id = ?";
        $count_groups_stmt = $conn->prepare($count_groups_sql);
        $count_groups_stmt->bind_param("i", $session_id);
        $count_groups_stmt->execute();
        $count_result = $count_groups_stmt->get_result();
        $current_group_count = $count_result->fetch_assoc()['group_count'];

        while (!empty($remaining_students) && count($created_groups) < $max_possible_groups) {
            $remaining_count = count($remaining_students);
            $remaining_groups = $max_possible_groups - count($created_groups);

            $min_for_this_group = $min_members;
            $max_for_this_group = min($max_members, $remaining_count);

            if ($remaining_groups > 1) {
                $max_for_this_group = min($max_members, $remaining_count - ($remaining_groups - 1) * $min_members);
            }

            $members_count = rand($min_for_this_group, $max_for_this_group);

            if ($remaining_count - $members_count < $min_members && $remaining_count > $members_count) {
                $members_count = $remaining_count - $min_members;
            }

            if ($members_count < $min_members) {
                $members_count = $remaining_count;
            }
            if ($members_count > $max_members) {
                $overLimitWarning = true;
            }

            $group_name = "Nhóm " . ($current_group_count + $group_index);
            $insert_group_sql = "INSERT INTO student_groups (name, mode, min_members, max_members, session_id) 
                                VALUES (?, ?, ?, ?, ?)";
            $insert_group_stmt = $conn->prepare($insert_group_sql);
            $insert_group_stmt->bind_param("ssiii", $group_name, $mode, $min_members, $max_members, $session_id);
            $insert_group_stmt->execute();
            $group_id = $conn->insert_id;

            $group_members = array_splice($remaining_students, 0, $members_count);

            $insert_member_sql = "INSERT INTO group_members (group_id, mssv) VALUES (?, ?)";
            $insert_member_stmt = $conn->prepare($insert_member_sql);
            foreach ($group_members as $mssv) {
                $insert_member_stmt->bind_param("is", $group_id, $mssv);
                $insert_member_stmt->execute();
            }

            $created_groups[] = [
                'id' => $group_id,
                'name' => $group_name,
                'mode' => $mode,
                'min_members' => $min_members,
                'max_members' => $max_members
            ];

            $group_index++;
        }

        $response_message = $overLimitWarning
            ? 'Một số nhóm đã vượt quá số thành viên tối đa nhưng vẫn được tạo.'
            : 'Tạo nhóm giáo viên chia thành công';

        echo json_encode([
            'success' => true,
            'message' => $response_message,
            'groups' => $created_groups
        ]);
    }
    // else if ($mode === 'student') {
    //     // Tạo các nhóm rỗng
    //     $created_groups = [];
    //     $count_groups_sql = "SELECT COUNT(*) as group_count FROM student_groups WHERE session_id = ?";
    //     $count_groups_stmt = $conn->prepare($count_groups_sql);
    //     $count_groups_stmt->bind_param("i", $session_id);
    //     $count_groups_stmt->execute();
    //     $count_result = $count_groups_stmt->get_result();
    //     $group_count = $count_result->fetch_assoc()['group_count'];

    //     for ($i = 1; $i <= $number_of_groups; $i++) {
    //         $group_name = "Nhóm " . ($group_count + $i);
    //         $insert_group_sql = "INSERT INTO student_groups (name, mode, min_members, max_members, session_id) 
    //                             VALUES (?, ?, ?, ?, ?)";
    //         $insert_group_stmt = $conn->prepare($insert_group_sql);
    //         $insert_group_stmt->bind_param("ssiii", $group_name, $mode, $min_members, $max_members, $session_id);
    //         $insert_group_stmt->execute();
    //         $group_id = $conn->insert_id;

    //         $created_groups[] = [
    //             'id' => $group_id,
    //             'name' => $group_name,
    //             'mode' => $mode,
    //             'min_members' => $min_members,
    //             'max_members' => $max_members
    //         ];
    //     }

    //     echo json_encode([
    //         'success' => true,
    //         'message' => 'Tạo các nhóm rỗng thành công',
    //         'groups' => $created_groups
    //     ]);
    // } 
    else if ($mode === 'student') {
        // 1. Đếm nhóm đã có
        $stmt = $conn->prepare(
            "SELECT COUNT(*) as group_count 
             FROM student_groups 
             WHERE session_id = ? AND mode = 'student'"
        );
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $group_count = $stmt->get_result()->fetch_assoc()['group_count'];
    
        // 2. Tạo đúng $number_of_groups đã validate ở trên
        $created_groups = [];
        for ($i = 1; $i <= $number_of_groups; $i++) {
            $group_name = "Nhóm " . ($group_count + $i);
            $insert = $conn->prepare(
                "INSERT INTO student_groups 
                 (name, mode, min_members, max_members, session_id) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $insert->bind_param(
                "ssiii",
                $group_name,
                $mode,
                $min_members,
                $max_members,
                $session_id
            );
            $insert->execute();
            $gid = $conn->insert_id;
            $created_groups[] = [
                'id'          => $gid,
                'name'        => $group_name,
                'mode'        => $mode,
                'min_members' => $min_members,
                'max_members' => $max_members
            ];
        }
    
        // 3. Trả về đúng JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => "Tạo thành công $number_of_groups nhóm rỗng",
            'groups'  => $created_groups
        ]);
    }
    

    else {
        throw new Exception('Chế độ không hợp lệ');
    }

} catch (Exception $e) {
    error_log("Error in create_group.php: " . $e->getMessage());


    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
} finally {
    if (isset($conn)) {
        closeDBConnection($conn);
    }
}
