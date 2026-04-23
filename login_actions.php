<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'staff_signup') {
        $staff_id = $conn->real_escape_string($_POST['staff_id']);
        $staff_name = $conn->real_escape_string($_POST['staff_name']);
        $department = $conn->real_escape_string($_POST['department']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $check = $conn->query("SELECT id FROM staff WHERE staff_id = '$staff_id'");
        if ($check->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Staff ID already exists']);
            exit;
        }

        $sql = "INSERT INTO staff (staff_id, staff_name, department, password) VALUES ('$staff_id', '$staff_name', '$department', '$password')";
        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Staff Registration Successful']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Registration Failed']);
        }
    } 
    elseif ($action === 'staff_login') {
        $staff_id = $conn->real_escape_string($_POST['staff_id']);
        $password = $_POST['password'];

        $result = $conn->query("SELECT * FROM staff WHERE staff_id = '$staff_id'");
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['role'] = 'staff';
                $_SESSION['user_id'] = $user['staff_id'];
                echo json_encode(['status' => 'success', 'message' => 'Login Successful']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid Password']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Staff ID not found']);
        }
    }
    elseif ($action === 'hod_signup') {
        $hod_id = $conn->real_escape_string($_POST['hod_id']);
        $hod_name = $conn->real_escape_string($_POST['hod_name']);
        $department = $conn->real_escape_string($_POST['department']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $check = $conn->query("SELECT id FROM hod WHERE hod_id = '$hod_id'");
        if ($check->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'HOD ID already exists']);
            exit;
        }

        $sql = "INSERT INTO hod (hod_id, hod_name, department, password) VALUES ('$hod_id', '$hod_name', '$department', '$password')";
        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'message' => 'HOD Registration Successful']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Registration Failed']);
        }
    }
    elseif ($action === 'hod_login') {
        $hod_id = $conn->real_escape_string($_POST['hod_id']);
        $password = $_POST['password'];

        $result = $conn->query("SELECT * FROM hod WHERE hod_id = '$hod_id'");
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['role'] = 'hod';
                $_SESSION['user_id'] = $user['hod_id'];
                echo json_encode(['status' => 'success', 'message' => 'Login Successful']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid Password']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'HOD ID not found']);
        }
    }
    elseif ($action === 'vp_login') {
        $username = $conn->real_escape_string($_POST['username']);
        $password = $_POST['password'];

        $result = $conn->query("SELECT * FROM vice_principal WHERE username = '$username'");
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // Since it's a default value, we verify plain text (or we can use password hash, but per requirements we set default vp/vp123)
            if ($password === $user['password']) {
                $_SESSION['role'] = 'vp';
                $_SESSION['user_id'] = $user['username'];
                echo json_encode(['status' => 'success', 'message' => 'Login Successful']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid Password']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Username not found']);
        }
    }
    elseif ($action === 'admin_login') {
        $username = $conn->real_escape_string($_POST['username']);
        $password = $_POST['password'];

        $result = $conn->query("SELECT * FROM admin WHERE username = '$username'");
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($password === $user['password']) {
                $_SESSION['role'] = 'admin';
                $_SESSION['user_id'] = $user['username'];
                echo json_encode(['status' => 'success', 'message' => 'Login Successful']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid Password']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Username not found']);
        }
    }
}
?>
