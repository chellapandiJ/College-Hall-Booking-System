<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Handle Staff booking department rooms
    if ($action === 'staff_book' && $_SESSION['role'] === 'staff') {
        $dept_room_id = $_POST['dept_room_id'];
        $booking_date = $_POST['booking_date'];
        $period = $_POST['period'];
        $class_name = trim($_POST['class_name']);
        $purpose = trim($_POST['purpose']);
        $user_id = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("SELECT staff_name, department FROM staff WHERE staff_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $staff = $stmt->get_result()->fetch_assoc();
        
        $sql = "INSERT INTO room_bookings (dept_room_id, user_role, user_id, user_name, department, booking_date, period, class_name, purpose, status) 
                VALUES (?, 'staff', ?, ?, ?, ?, ?, ?, ?, 'Pending')";
        $ins = $conn->prepare($sql);
        $ins->bind_param("isssssss", $dept_room_id, $user_id, $staff['staff_name'], $staff['department'], $booking_date, $period, $class_name, $purpose);
        $ins->execute();
        
        header('Location: staff_dashboard.php?date=' . $booking_date);
        exit;
    } 
    // Handle HOD booking or Staff/HOD booking common halls
    elseif (($action === 'hod_book') && ($_SESSION['role'] === 'hod' || $_SESSION['role'] === 'staff')) {
        $type = $_POST['room_type_id'] ?? 'common'; 
        
        // Security Check: Only HOD can book common halls
        if ($type === 'common' && $_SESSION['role'] !== 'hod') {
            header('Location: staff_dashboard.php?error=unauthorized');
            exit;
        }
        $room_id = $_POST['room_id'] ?? 0;
        $booking_date = $_POST['booking_date'];
        $purpose = trim($_POST['purpose']);
        $duration_type = $_POST['duration_type'] ?? 'period';
        $period = $_POST['period'] ?? 0;
        $start_time = $_POST['start_time'] ?: null;
        $end_time = $_POST['end_time'] ?: null;
        $half_day_type = $_POST['half_day_type'] ?: null;
        $user_id = $_SESSION['user_id'];
        $role = $_SESSION['role'];

        if ($role === 'hod') {
            $stmt = $conn->prepare("SELECT hod_name as name, department FROM hod WHERE hod_id = ?");
        } else {
            $stmt = $conn->prepare("SELECT staff_name as name, department FROM staff WHERE staff_id = ?");
        }
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $user_data = $stmt->get_result()->fetch_assoc();

        // Common halls ALWAYS go to VP (Pending)
        // Dept rooms for HOD are Approved instantly
        $status = ($type === 'common') ? 'Pending' : 'Approved';
        $dept_room_id = ($type === 'dept') ? $room_id : null;
        $common_room_id = ($type === 'common') ? $room_id : null;

        $sql = "INSERT INTO room_bookings (dept_room_id, common_room_id, user_role, user_id, user_name, department, booking_date, period, duration_type, start_time, end_time, half_day_type, purpose, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $ins = $conn->prepare($sql);
        $ins->bind_param("iissssssssssss", $dept_room_id, $common_room_id, $role, $user_id, $user_data['name'], $user_data['department'], $booking_date, $period, $duration_type, $start_time, $end_time, $half_day_type, $purpose, $status);
        $ins->execute();

        $redirect = ($role === 'hod') ? 'hod_dashboard.php' : 'staff_dashboard.php';
        header("Location: $redirect?date=$booking_date");
        exit;
    }
    // HOD Approving Staff Requests
    elseif ($action === 'hod_action' && $_SESSION['role'] === 'hod') {
        $booking_id = $_POST['booking_id'];
        $status = $_POST['status'];
        
        $sql = "UPDATE room_bookings SET status = ? WHERE id = ?";
        $upd = $conn->prepare($sql);
        $upd->bind_param("si", $status, $booking_id);
        $upd->execute();
        
        header('Location: hod_dashboard.php');
        exit;
    }
    // VP Approving Common Hall Requests
    elseif ($action === 'vp_action' && $_SESSION['role'] === 'vp') {
        $booking_id = $_POST['booking_id'];
        $status = $_POST['status'];
        
        $sql = "UPDATE room_bookings SET status = ? WHERE id = ?";
        $upd = $conn->prepare($sql);
        $upd->bind_param("si", $status, $booking_id);
        $upd->execute();
        
        header('Location: vp_dashboard.php');
        exit;
    }
    // VP Editing & Approving Common Hall Requests
    elseif ($action === 'vp_edit_booking' && $_SESSION['role'] === 'vp') {
        $booking_id = $_POST['booking_id'];
        $booking_date = $_POST['booking_date'];
        $duration_type = $_POST['duration_type'];
        $period = $_POST['period'] ?? 0;
        $start_time = $_POST['start_time'] ?: null;
        $end_time = $_POST['end_time'] ?: null;
        $half_day_type = $_POST['half_day_type'] ?: null;
        $purpose = trim($_POST['purpose']);
        
        $sql = "UPDATE room_bookings SET 
                booking_date = ?, 
                duration_type = ?, 
                period = ?, 
                start_time = ?, 
                end_time = ?, 
                half_day_type = ?, 
                purpose = ?, 
                status = 'Approved' 
                WHERE id = ?";
        $upd = $conn->prepare($sql);
        $upd->bind_param("sssssssi", $booking_date, $duration_type, $period, $start_time, $end_time, $half_day_type, $purpose, $booking_id);
        $upd->execute();
        
        header('Location: vp_dashboard.php');
        exit;
    }
    // VP Deleting Booking
    elseif ($action === 'vp_delete' && $_SESSION['role'] === 'vp') {
        $booking_id = $_POST['booking_id'];
        
        $sql = "DELETE FROM room_bookings WHERE id = ?";
        $del = $conn->prepare($sql);
        $del->bind_param("i", $booking_id);
        $del->execute();
        
        header('Location: vp_dashboard.php');
        exit;
    }
}
?>
