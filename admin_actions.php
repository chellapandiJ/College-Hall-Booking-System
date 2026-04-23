<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('Location: index.php'); exit; }
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_dept') {
        $department_name = trim($_POST['department_name']);
        $stmt = $conn->prepare("INSERT INTO departments (dept_name) VALUES (?)");
        $stmt->bind_param("s", $department_name);
        $stmt->execute();
    } 
    elseif ($action === 'add_dept_room') {
        $department_id = $_POST['department_id'];
        $room_type = $_POST['room_type'];
        $room_number = trim($_POST['room_number']);
        $stmt = $conn->prepare("INSERT INTO department_rooms (department_id, room_type, room_number) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $department_id, $room_type, $room_number);
        $stmt->execute();
    }
    elseif ($action === 'add_common_room') {
        $room_name = trim($_POST['room_name']);
        $room_number = trim($_POST['room_number']);
        $stmt = $conn->prepare("INSERT INTO common_event_rooms (room_name, room_number) VALUES (?, ?)");
        $stmt->bind_param("ss", $room_name, $room_number);
        $stmt->execute();
    }
    elseif ($action === 'edit_user') {
        $type = $_POST['user_type']; // 'hod' or 'staff'
        $old_id = $_POST['old_id'];
        $new_id = $_POST['new_id'];
        $name = $_POST['name'];
        $dept = $_POST['department'];

        if ($type === 'hod') {
            $stmt = $conn->prepare("UPDATE hod SET hod_id = ?, hod_name = ?, department = ? WHERE hod_id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE staff SET staff_id = ?, staff_name = ?, department = ? WHERE staff_id = ?");
        }
        $stmt->bind_param("ssss", $new_id, $name, $dept, $old_id);
        $stmt->execute();
    }

    header('Location: admin_dashboard.php');
    exit;
}
