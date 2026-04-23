<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hod') { header('Location: index.php'); exit; }
require 'db.php';

$hod_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM hod WHERE hod_id = ?");
$stmt->bind_param("s", $hod_id);
$stmt->execute();
$hod = $stmt->get_result()->fetch_assoc();
$department_name = $hod['department'];

// Get department ID
$stmt2 = $conn->prepare("SELECT id FROM departments WHERE dept_name = ?");
$stmt2->bind_param("s", $department_name);
$stmt2->execute();
$dept_result = $stmt2->get_result();
$dept_id = ($dept_result->num_rows > 0) ? $dept_result->fetch_assoc()['id'] : null;

// Handle filters
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$today = date('Y-m-d');
date_default_timezone_set('Asia/Kolkata');
$current_time = date('H:i:s');

// Fetch pending requests for department rooms (to approve staff requests)
$pending_sql = "SELECT rb.*, dr.room_type, dr.room_number 
                FROM room_bookings rb 
                JOIN department_rooms dr ON rb.dept_room_id = dr.id 
                WHERE rb.department = ? AND rb.status = 'Pending' AND rb.dept_room_id IS NOT NULL
                ORDER BY rb.booking_date ASC, rb.period ASC";
$stmt_p = $conn->prepare($pending_sql);
$stmt_p->bind_param("s", $department_name);
$stmt_p->execute();
$pending_requests = $stmt_p->get_result();

// Fetch department rooms for booking
$dept_rooms = [];
if ($dept_id) {
    $stmt_dr = $conn->prepare("SELECT * FROM department_rooms WHERE department_id = ?");
    $stmt_dr->bind_param("i", $dept_id);
    $stmt_dr->execute();
    $res_dr = $stmt_dr->get_result();
    while ($row = $res_dr->fetch_assoc()) { $dept_rooms[] = $row; }
}

// Fetch common event halls for booking
$common_rooms = [];
$res_cr = $conn->query("SELECT * FROM common_event_rooms");
while ($row = $res_cr->fetch_assoc()) { $common_rooms[] = $row; }

// Fetch ALL bookings for the selected date to show availability
$bookings = [];
$stmt_b = $conn->prepare("SELECT rb.*, h.hod_name as h_name, s.staff_name as s_name 
                         FROM room_bookings rb 
                         LEFT JOIN hod h ON rb.user_id = h.hod_id AND rb.user_role = 'hod'
                         LEFT JOIN staff s ON rb.user_id = s.staff_id AND rb.user_role = 'staff'
                         WHERE rb.booking_date = ? AND rb.status != 'Rejected'");
$stmt_b->bind_param("s", $selected_date);
$stmt_b->execute();
$res_b = $stmt_b->get_result();
while ($row = $res_b->fetch_assoc()) {
    if ($row['dept_room_id']) {
        $bookings['dept'][$row['dept_room_id']][] = $row;
    } else {
        $bookings['common'][$row['common_room_id']][] = $row;
    }
}

$periods = [
    1 => ['label' => '08:30 AM - 09:25 AM', 'start' => '08:30:00'],
    2 => ['label' => '09:25 AM - 10:20 AM', 'start' => '09:25:00'],
    3 => ['label' => '10:20 AM - 11:15 AM', 'start' => '10:20:00'],
    4 => ['label' => '11:50 AM - 12:30 PM', 'start' => '11:50:00'],
    5 => ['label' => '12:30 PM - 01:20 PM', 'start' => '12:30:00']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Dashboard - College Hall Booking</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --pending: #f59e0b; /* Yellow */
            --approved: #3b82f6; /* Blue */
            --available: #10b981; /* Green */
            --rejected: #ef4444; /* Red */
        }
        .container { padding: 30px 5%; margin-top: 20px; }
        .tabs { display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 1px solid var(--border-glass); padding-bottom: 15px; }
        .tab-btn { padding: 10px 20px; border-radius: 8px; border: none; background: rgba(255,255,255,0.05); color: #fff; cursor: pointer; transition: 0.3s; font-weight: 500; }
        .tab-btn.active { background: var(--primary); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .card { background: var(--bg-glass); border: 1px solid var(--border-glass); border-radius: 16px; padding: 25px; margin-bottom: 25px; backdrop-filter: blur(10px); }
        .room-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .room-card { background: rgba(255,255,255,0.03); border: 1px solid var(--border-glass); border-radius: 12px; padding: 20px; }
        .room-title { font-size: 18px; font-weight: 600; margin-bottom: 15px; display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
        .slot-list { display: flex; flex-direction: column; gap: 8px; }
        .slot-item { padding: 10px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; font-size: 14px; border: 1px solid transparent; cursor: pointer;}
        .slot-item.available { background: rgba(16, 185, 129, 0.1); border-color: var(--available); color: var(--available); }
        .slot-item.pending { background: rgba(245, 158, 11, 0.1); border-color: var(--pending); color: var(--pending); }
        .slot-item.approved { background: rgba(59, 130, 246, 0.1); border-color: var(--approved); color: var(--approved); }
        .slot-item.rejected { background: rgba(239, 68, 68, 0.1); border-color: var(--rejected); color: var(--rejected); }
        
        .filters { background: var(--bg-glass); padding: 20px; border-radius: 12px; margin-bottom: 25px; border: 1px solid var(--border-glass); display: flex; gap: 15px; align-items: center; }
        .filters input[type="date"] { padding: 10px; border-radius: 8px; border: 1px solid var(--border-glass); background: rgba(255,255,255,0.1); color: #fff; }
        .filters button { padding: 10px 20px; background: var(--primary); color: #fff; border: none; border-radius: 8px; cursor: pointer; }

        .request-item { background: rgba(255,255,255,0.05); border: 1px solid var(--border-glass); padding: 15px; border-radius: 8px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        .request-actions { display: flex; gap: 10px; }
        .btn { padding: 8px 15px; border-radius: 6px; border: none; cursor: pointer; font-weight: 500; color: #fff; }
        .btn-approve { background: var(--available); }
        .btn-decline { background: var(--rejected); }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #9ca3af; font-size: 14px; }
        .form-group select, .form-group input { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-glass); background: rgba(255,255,255,0.1); color: #fff; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <header>
        <div class="logo">HOD Portal</div>
        <div class="auth-nav">
            <span>Welcome, <?= htmlspecialchars($hod['hod_name']) ?> (<?= htmlspecialchars($department_name) ?>)</span>
            <button onclick="location.href='logout.php'">Logout</button>
        </div>
    </header>

    <div class="container">
        <div class="filters">
            <form method="GET" style="display: flex; gap:10px; align-items: center;">
                <label>Check Availability For:</label>
                <input type="date" name="date" value="<?= $selected_date ?>" min="<?= $today ?>">
                <button type="submit">Refresh</button>
            </form>
        </div>

        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('pending')">Pending Approvals</button>
            <button class="tab-btn" onclick="showTab('dept_rooms')">Department Rooms</button>
            <button class="tab-btn" onclick="showTab('common_rooms')">Common Event Halls</button>
        </div>

        <!-- Pending Approvals Tab -->
        <div id="pending" class="tab-content active">
            <div class="card">
                <h3>Requests from Staff</h3>
                <?php if ($pending_requests->num_rows == 0): ?>
                    <p style="color:#9ca3af;">No pending requests from department staff.</p>
                <?php else: ?>
                    <?php while ($row = $pending_requests->fetch_assoc()): 
                        $is_expired = ($row['booking_date'] == $today && $current_time >= $periods[$row['period']]['start']) || ($row['booking_date'] < $today);
                        if ($is_expired) {
                            $conn->query("UPDATE room_bookings SET status = 'Expired' WHERE id = " . $row['id']);
                            continue;
                        }
                    ?>
                        <div class="request-item">
                            <div class="details">
                                <strong><?= htmlspecialchars($row['user_name']) ?></strong> requested <strong><?= htmlspecialchars($row['room_type']) ?> - <?= htmlspecialchars($row['room_number']) ?></strong>
                                <div style="font-size:13px; color:#9ca3af;">Date: <?= $row['booking_date'] ?> | Period: <?= $row['period'] ?> | Purpose: <?= htmlspecialchars($row['purpose']) ?></div>
                            </div>
                            <div class="request-actions">
                                <form action="booking_actions.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="hod_action">
                                    <input type="hidden" name="booking_id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="status" value="Approved">
                                    <button type="submit" class="btn btn-approve">Approve</button>
                                </form>
                                <form action="booking_actions.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="hod_action">
                                    <input type="hidden" name="booking_id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="status" value="Rejected">
                                    <button type="submit" class="btn btn-decline">Decline</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Department Rooms Tab -->
        <div id="dept_rooms" class="tab-content">
            <div class="room-grid">
                <?php foreach ($dept_rooms as $room): ?>
                    <div class="room-card">
                        <div class="room-title"><?= htmlspecialchars($room['room_type']) ?> - <?= htmlspecialchars($room['room_number']) ?></div>
                        <div class="slot-list">
                            <?php foreach ($periods as $p_num => $p): 
                                $status = 'Available'; $class = 'available'; $booked_by = '';
                                if (isset($bookings['dept'][$room['id']])) {
                                    foreach ($bookings['dept'][$room['id']] as $b) {
                                        if ($b['period'] == $p_num) {
                                            $status = $b['status'];
                                            $class = strtolower($b['status']);
                                            $booked_by = "Status: $status | By: " . htmlspecialchars($b['user_name']);
                                            break;
                                        }
                                    }
                                }
                                $is_past = ($selected_date == $today && $current_time >= $p['start']) || ($selected_date < $today);
                                if ($is_past && $status == 'Available') { $status = 'Expired'; $class = 'rejected'; }
                            ?>
                                <div class="slot-item <?= $class ?>" onclick="openBookingModal('dept', <?= $room['id'] ?>, '<?= $room['room_type'] ?> - <?= $room['room_number'] ?>', <?= $p_num ?>, '<?= $p['label'] ?>', '<?= $status ?>')">
                                    <span>Period <?= $p_num ?> (<?= $p['label'] ?>)</span>
                                    <span><?= $booked_by ?: $status ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Common Event Halls Tab -->
        <div id="common_rooms" class="tab-content">
            <div class="room-grid">
                <?php foreach ($common_rooms as $room): ?>
                    <div class="room-card">
                        <div class="room-title"><?= htmlspecialchars($room['room_name']) ?> (<?= htmlspecialchars($room['room_number']) ?>)</div>
                        <div class="slot-list">
                            <?php 
                                $hall_bookings = $bookings['common'][$room['id']] ?? [];
                                if (!empty($hall_bookings)): ?>
                                    <div style="margin-bottom:10px; font-weight:bold; font-size:12px; color:var(--approved);">Present & Future Bookings:</div>
                                    <?php foreach($hall_bookings as $b): 
                                        $b_class = strtolower($b['status']);
                                        $b_time = $b['duration_type'] == 'period' ? "Period {$b['period']}" : $b['duration_type'];
                                        if($b['duration_type'] == 'hourly') $b_time = "{$b['start_time']} - {$b['end_time']}";
                                        elseif($b['duration_type'] == 'half_day') $b_time = "Half Day ({$b['half_day_type']})";
                                    ?>
                                        <div class="slot-item <?= $b_class ?>" style="cursor:default; flex-direction:column; align-items:flex-start; gap:5px; height:auto;">
                                            <div style="display:flex; justify-content:space-between; width:100%;">
                                                <strong><?= $b_time ?></strong>
                                                <span class="status-badge" style="font-size:10px; padding:2px 6px; border-radius:4px; background:rgba(255,255,255,0.1);"><?= $b['status'] ?></span>
                                            </div>
                                            <div style="font-size:13px;"><?= htmlspecialchars($b['user_name']) ?> (<?= htmlspecialchars($b['department']) ?>)</div>
                                            <div style="font-size:12px; color:#9ca3af;">Purpose: <?= htmlspecialchars($b['purpose']) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <button class="btn btn-approve" style="width:100%; margin-top:10px;" onclick="openBookingModal('common', <?= $room['id'] ?>, '<?= htmlspecialchars($room['room_name']) ?>', 0, '', 'Available')">Book This Hall</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div class="modal" id="bookingModal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('bookingModal')">&times;</span>
            <h2 id="modal_title">Book Room</h2>
            <form action="booking_actions.php" method="POST">
                <input type="hidden" name="action" value="hod_book">
                <input type="hidden" name="room_type_id" id="modal_type_id">
                <input type="hidden" name="room_id" id="modal_room_id">
                <input type="hidden" name="booking_date" value="<?= $selected_date ?>">
                
                <p id="modal_info" style="margin-bottom: 20px; font-weight: bold; color: var(--approved);"></p>

                <div class="form-group" id="period_select_group">
                    <label>Period</label>
                    <select name="period" id="modal_period">
                        <?php foreach($periods as $p_num => $p): ?>
                            <option value="<?= $p_num ?>"><?= $p_num ?> (<?= $p['label'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="duration_group">
                    <label>Duration Type</label>
                    <select name="duration_type" id="duration_type" onchange="toggleDurationInputs()">
                        <option value="period">Specific Period</option>
                        <option value="hourly">Hourly</option>
                        <option value="half_day">Half Day</option>
                        <option value="full_day">Full Day</option>
                    </select>
                </div>

                <div id="hourly_inputs" class="hidden">
                    <div style="display:flex; gap:10px;">
                        <div class="form-group" style="flex:1;">
                            <label>Start Time</label>
                            <input type="time" name="start_time">
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>End Time</label>
                            <input type="time" name="end_time">
                        </div>
                    </div>
                </div>

                <div id="half_day_inputs" class="hidden">
                    <div class="form-group">
                        <label>Session</label>
                        <select name="half_day_type">
                            <option value="Morning">Morning (8:30 AM - 12:30 PM)</option>
                            <option value="Afternoon">Afternoon (1:00 PM - 5:00 PM)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Purpose of Booking</label>
                    <input type="text" name="purpose" required placeholder="e.g. Department Meeting">
                </div>

                <button type="submit" class="submit-btn" style="background:var(--approved);">Submit Booking Request</button>
            </form>
        </div>
    </div>

    <script>
        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            event.currentTarget.classList.add('active');
        }

        function openBookingModal(type, id, name, period, periodLabel, status) {
            if (status !== 'Available') {
                if (type === 'dept') return;
            }
            
            document.getElementById('modal_type_id').value = type;
            document.getElementById('modal_room_id').value = id;
            document.getElementById('modal_title').innerText = "Booking: " + name;
            
            const periodGroup = document.getElementById('period_select_group');
            const durationGroup = document.getElementById('duration_group');
            
            if (type === 'dept') {
                periodGroup.style.display = 'block';
                durationGroup.style.display = 'none';
                document.getElementById('modal_period').value = period;
                document.getElementById('duration_type').value = 'period';
                document.getElementById('modal_info').innerText = "Booking for Period " + period + " (" + periodLabel + ")";
            } else {
                periodGroup.style.display = 'block'; // Show period selector by default for common halls too
                durationGroup.style.display = 'block';
                document.getElementById('duration_type').value = 'period';
                document.getElementById('modal_info').innerText = "Select duration for " + name;
            }
            
            toggleDurationInputs();
            const modal = document.getElementById('bookingModal');
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('active'), 10);
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            modal.classList.remove('active');
            setTimeout(() => modal.style.display = 'none', 300);
        }

        function toggleDurationInputs() {
            const type = document.getElementById('duration_type').value;
            const roomType = document.getElementById('modal_type_id').value;
            
            document.getElementById('hourly_inputs').classList.add('hidden');
            document.getElementById('half_day_inputs').classList.add('hidden');
            
            // Period group visibility
            const periodGroup = document.getElementById('period_select_group');
            if (roomType === 'dept' || type === 'period') {
                periodGroup.style.display = 'block';
            } else {
                periodGroup.style.display = 'none';
            }
            
            if (type === 'hourly') {
                document.getElementById('hourly_inputs').classList.remove('hidden');
            } else if (type === 'half_day') {
                document.getElementById('half_day_inputs').classList.remove('hidden');
            }
        }
    </script>
</body>
</html>
