<?php
session_start();
if (!isset($_SESSION['role'])) {
    header('Location: index.php');
    exit;
}
$role = $_SESSION['role'];
if ($role === 'admin') { header('Location: admin_dashboard.php'); exit; }
if ($role === 'staff') { header('Location: staff_dashboard.php'); exit; }
if ($role === 'hod') { header('Location: hod_dashboard.php'); exit; }
if ($role === 'vp') { header('Location: vp_dashboard.php'); exit; }
?>
