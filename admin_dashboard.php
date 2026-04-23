<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('Location: index.php'); exit; }
require 'db.php';

$result = $conn->query("SELECT * FROM departments ORDER BY dept_name ASC");
$departments = [];
while ($row = $result->fetch_assoc()) { $departments[] = $row; }

$staff_res = $conn->query("SELECT * FROM staff ORDER BY department ASC, staff_name ASC");
$all_staff = [];
while ($row = $staff_res->fetch_assoc()) { $all_staff[] = $row; }

$hod_res = $conn->query("SELECT * FROM hod ORDER BY department ASC");
$all_hods = [];
while ($row = $hod_res->fetch_assoc()) { $all_hods[] = $row; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - College Hall Booking</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .dashboard-container { padding: 40px 5%; display: flex; flex-direction: column; gap: 30px; }
        .dashboard-card { background: var(--bg-glass); border: 1px solid var(--border-glass); padding: 30px; border-radius: 16px; backdrop-filter: blur(10px); }
        .dashboard-card h3 { margin-bottom: 20px; font-size: 20px; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;}
        .form-row { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px; }
        .input-group { flex: 1; min-width: 200px; }
        .btn { padding: 12px 20px; border-radius: 8px; cursor: pointer; transition: 0.3s; font-weight: 500; border: none; color:#fff; }
        .btn-primary { background: var(--primary); }
        .btn-edit { background: #3b82f6; padding: 5px 10px; font-size: 12px; }
        .btn-delete { background: #ef4444; padding: 5px 10px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; color: #fff; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { color: #9ca3af; font-weight: 500; font-size: 14px; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab-btn { padding: 10px 20px; border-radius: 8px; background: rgba(255,255,255,0.05); color: #fff; cursor: pointer; border: none; }
        .tab-btn.active { background: var(--primary); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
    <header>
        <div class="logo">Admin Panel</div>
        <div class="auth-nav">
            <span>Welcome, Admin</span>
            <button onclick="location.href='logout.php'">Logout</button>
        </div>
    </header>

    <div class="dashboard-container">
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('manage_rooms')">Manage Rooms & Depts</button>
            <button class="tab-btn" onclick="showTab('manage_staff')">Manage Staff/HODs</button>
            <button class="tab-btn" onclick="showTab('reports')">Reports</button>
        </div>

        <!-- Manage Rooms & Depts -->
        <div id="manage_rooms" class="tab-content active">
            <div class="dashboard-card">
                <h3>Add Department</h3>
                <form action="admin_actions.php" method="POST" class="form-row">
                    <input type="hidden" name="action" value="add_dept">
                    <div class="input-group">
                        <label>Name</label><input type="text" name="department_name" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="align-self:flex-end;">Add</button>
                </form>
            </div>

            <div class="dashboard-card">
                <h3>Department Rooms</h3>
                <form action="admin_actions.php" method="POST" class="form-row">
                    <input type="hidden" name="action" value="add_dept_room">
                    <div class="input-group">
                        <label>Dept</label>
                        <select name="department_id" required>
                            <?php foreach($departments as $d): ?><option value="<?= $d['id'] ?>"><?= $d['dept_name'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Type</label>
                        <select name="room_type"><option>AV Hall</option><option>Smart Class</option><option>Seminar Hall</option></select>
                    </div>
                    <div class="input-group">
                        <label>Room #</label><input type="text" name="room_number" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="align-self:flex-end;">Add</button>
                </form>
            </div>

            <div class="dashboard-card">
                <h3>Common Event Halls</h3>
                <form action="admin_actions.php" method="POST" class="form-row">
                    <input type="hidden" name="action" value="add_common_room">
                    <div class="input-group">
                        <label>Hall Name</label><input type="text" name="room_name" required>
                    </div>
                    <div class="input-group">
                        <label>Room #</label><input type="text" name="room_number" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="align-self:flex-end;">Add</button>
                </form>
            </div>
        </div>

        <!-- Manage Staff Content -->
        <div id="manage_staff" class="tab-content">
            <div class="dashboard-card">
                <h3>HOD Management</h3>
                <table>
                    <thead><tr><th>HOD ID</th><th>Name</th><th>Department</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach($all_hods as $h): ?>
                            <tr>
                                <td><?= $h['hod_id'] ?></td>
                                <td><?= htmlspecialchars($h['hod_name']) ?></td>
                                <td><?= htmlspecialchars($h['department']) ?></td>
                                <td>
                                    <button class="btn btn-edit" onclick="editUser('hod', '<?= $h['hod_id'] ?>', '<?= addslashes($h['hod_name']) ?>', '<?= $h['department'] ?>')">Edit</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="dashboard-card">
                <h3>Staff Management</h3>
                <table>
                    <thead><tr><th>Staff ID</th><th>Name</th><th>Department</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach($all_staff as $s): ?>
                            <tr>
                                <td><?= $s['staff_id'] ?></td>
                                <td><?= htmlspecialchars($s['staff_name']) ?></td>
                                <td><?= htmlspecialchars($s['department']) ?></td>
                                <td>
                                    <button class="btn btn-edit" onclick="editUser('staff', '<?= $s['staff_id'] ?>', '<?= addslashes($s['staff_name']) ?>', '<?= $s['department'] ?>')">Edit</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Reports Tab -->
        <div id="reports" class="tab-content">
            <div class="dashboard-card">
                <h3>Generate Booking Reports</h3>
                <form action="generate_report.php" method="GET" target="_blank">
                    <div class="form-row">
                        <div class="input-group">
                            <label>Report Type</label>
                            <select name="report_type" id="report_type" onchange="toggleReportFilters()">
                                <option value="dept_rooms">Department Rooms Booking</option>
                                <option value="event_halls">Common Event Halls Booking</option>
                            </select>
                        </div>
                        <div class="input-group" id="dept_filter_group">
                            <label>Department</label>
                            <select name="department">
                                <option value="all">All Departments</option>
                                <?php foreach($departments as $d): ?><option value="<?= $d['dept_name'] ?>"><?= $d['dept_name'] ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="input-group">
                            <label>From Date</label>
                            <input type="date" name="from_date" required value="<?= date('Y-m-01') ?>">
                        </div>
                        <div class="input-group">
                            <label>To Date</label>
                            <input type="date" name="to_date" required value="<?= date('Y-m-t') ?>">
                        </div>
                    </div>
                    <div class="form-row" style="margin-top:20px; gap:20px;">
                        <button type="submit" name="format" value="view" class="btn btn-primary" style="flex:1;">View Report</button>
                        <button type="submit" name="format" value="excel" class="btn" style="flex:1; background:#10b981;">Download Excel</button>
                        <button type="submit" name="format" value="pdf" class="btn" style="flex:1; background:#ef4444;">Download PDF</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('editModal')">&times;</span>
            <h2 id="edit_title">Edit User</h2>
            <form action="admin_actions.php" method="POST">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_type" id="edit_user_type">
                <input type="hidden" name="old_id" id="edit_old_id">
                
                <div class="input-group" style="margin-bottom:15px;">
                    <label>User ID</label><input type="text" name="new_id" id="edit_new_id" required style="width:100%; border:1px solid var(--border-glass); background:rgba(255,255,255,0.05); color:#fff; padding:10px; border-radius:8px;">
                </div>
                <div class="input-group" style="margin-bottom:15px;">
                    <label>Name</label><input type="text" name="name" id="edit_name" required style="width:100%; border:1px solid var(--border-glass); background:rgba(255,255,255,0.05); color:#fff; padding:10px; border-radius:8px;">
                </div>
                <div class="input-group" style="margin-bottom:15px;">
                    <label>Department</label>
                    <select name="department" id="edit_dept" style="width:100%; border:1px solid var(--border-glass); background:rgba(255,255,255,0.05); color:#fff; padding:10px; border-radius:8px;">
                        <?php foreach($departments as $d): ?><option value="<?= $d['dept_name'] ?>"><?= $d['dept_name'] ?></option><?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Update User</button>
            </form>
        </div>
    </div>

    <script>
        function showTab(id) {
            document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            event.currentTarget.classList.add('active');
        }
        function editUser(type, id, name, dept) {
            document.getElementById('edit_user_type').value = type;
            document.getElementById('edit_old_id').value = id;
            document.getElementById('edit_new_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_dept').value = dept;
            document.getElementById('edit_title').innerText = "Edit " + type.toUpperCase();
            
            const modal = document.getElementById('editModal');
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('active'), 10);
        }
        function closeModal(id) {
            const modal = document.getElementById(id);
            modal.classList.remove('active');
            setTimeout(() => modal.style.display = 'none', 300);
        }
        function toggleReportFilters() {
            const type = document.getElementById('report_type').value;
            const deptGroup = document.getElementById('dept_filter_group');
            if (type === 'dept_rooms') {
                deptGroup.style.display = 'block';
            } else {
                deptGroup.style.display = 'none';
            }
        }
        // Run once on load
        document.addEventListener('DOMContentLoaded', toggleReportFilters);
    </script>
</body>
</html>
