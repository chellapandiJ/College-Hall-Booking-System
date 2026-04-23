<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') { header('Location: index.php'); exit; }
require 'db.php';

$staff_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->bind_param("s", $staff_id);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();
$department_name = $staff['department'];

// Get department ID
$stmt2 = $conn->prepare("SELECT id FROM departments WHERE dept_name = ?");
$stmt2->bind_param("s", $department_name);
$stmt2->execute();
$dept_result = $stmt2->get_result();
$dept_id = ($dept_result->num_rows > 0) ? $dept_result->fetch_assoc()['id'] : null;

// Handle selected date
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$today = date('Y-m-d');
date_default_timezone_set('Asia/Kolkata');
$current_time = date('H:i:s');

// Fetch rooms
$rooms = [];
if ($dept_id) {
    $stmt3 = $conn->prepare("SELECT * FROM department_rooms WHERE department_id = ?");
    $stmt3->bind_param("i", $dept_id);
    $stmt3->execute();
    $res = $stmt3->get_result();
    while ($row = $res->fetch_assoc()) { $rooms[] = $row; }
}

// Fetch common event halls
$common_rooms = [];
$res_cr = $conn->query("SELECT * FROM common_event_rooms");
while ($row = $res_cr->fetch_assoc()) { $common_rooms[] = $row; }

// Fetch bookings
$bookings = [];
$stmt4 = $conn->prepare("SELECT * FROM room_bookings WHERE booking_date = ? AND status != 'Rejected'");
$stmt4->bind_param("s", $selected_date);
$stmt4->execute();
$res4 = $stmt4->get_result();
while ($row = $res4->fetch_assoc()) {
    if ($row['dept_room_id']) {
        $bookings['dept'][$row['dept_room_id']][$row['period']] = $row;
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
    <title>Staff Dashboard - Room Booking</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --pending: #f59e0b;
            --approved: #3b82f6;
            --available: #10b981;
            --rejected: #ef4444;
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
        .room-title { font-size: 18px; font-weight: 600; margin-bottom: 15px; display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; color: #fff; }
        .slot-list { display: flex; flex-direction: column; gap: 8px; }
        .slot-item { padding: 10px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; font-size: 14px; border: 1px solid transparent; cursor: pointer;}
        .slot-item.available { background: rgba(16, 185, 129, 0.1); border-color: var(--available); color: var(--available); }
        .slot-item.pending { background: rgba(245, 158, 11, 0.1); border-color: var(--pending); color: var(--pending); }
        .slot-item.approved { background: rgba(59, 130, 246, 0.1); border-color: var(--approved); color: var(--approved); }
        .slot-item.rejected { background: rgba(239, 68, 68, 0.1); border-color: var(--rejected); color: var(--rejected); }

        .filters { background: var(--bg-glass); padding: 20px; border-radius: 12px; margin-bottom: 25px; border: 1px solid var(--border-glass); display: flex; gap: 15px; align-items: center; }
        .filters input[type="date"] { padding: 10px; border-radius: 8px; border: 1px solid var(--border-glass); background: rgba(255,255,255,0.1); color: #fff; }
        .filters button { padding: 10px 20px; background: var(--primary); color: #fff; border: none; border-radius: 8px; cursor: pointer; }

        .hidden { display: none; }
    </style>
</head>
<body>
    <header>
        <div class="logo">Staff Portal</div>
        <div class="auth-nav">
            <span>Welcome, <?= htmlspecialchars($staff['staff_name']) ?> (<?= htmlspecialchars($department_name) ?>)</span>
            <button onclick="location.href='logout.php'">Logout</button>
        </div>
    </header>

    <div class="container">
        <div class="filters">
            <form method="GET" style="display: flex; gap:10px; align-items: center;">
                <label>Select Date:</label>
                <input type="date" name="date" value="<?= $selected_date ?>" min="<?= $today ?>">
                <button type="submit">Check Availability</button>
            </form>
        </div>

        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('dept_rooms')">My Department Rooms</button>
            <button class="tab-btn" onclick="showTab('common_rooms')">Common Event Halls</button>
        </div>

        <div id="dept_rooms" class="tab-content active">
            <?php if (empty($rooms)): ?>
                <div class="card"><p>No rooms found for your department.</p></div>
            <?php else: ?>
                <div class="room-grid">
                    <?php foreach ($rooms as $room): ?>
                        <div class="room-card">
                            <div class="room-title"><?= htmlspecialchars($room['room_type']) ?> - <?= htmlspecialchars($room['room_number']) ?></div>
                            <div class="slot-list">
                                <?php foreach ($periods as $p_num => $p): 
                                    $status = 'Available'; $class = 'available'; $meta = '';
                                    if (isset($bookings['dept'][$room['id']][$p_num])) {
                                        $b = $bookings['dept'][$room['id']][$p_num];
                                        $status = $b['status'];
                                        $class = strtolower($b['status']);
                                        $meta = "By: " . htmlspecialchars($b['user_name']);
                                    }
                                    $is_past = ($selected_date == $today && $current_time >= $p['start']) || ($selected_date < $today);
                                    if ($is_past && $status == 'Available') { $status = 'Expired'; $class = 'rejected'; }
                                ?>
                                    <div class="slot-item <?= $class ?>" onclick="openBookingModal('dept', <?= $room['id'] ?>, '<?= $room['room_type'] ?> - <?= $room['room_number'] ?>', <?= $p_num ?>, '<?= $p['label'] ?>', '<?= $status ?>')">
                                        <span>Period <?= $p_num ?> (<?= $p['label'] ?>)</span>
                                        <span><?= $meta ?: $status ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div id="common_rooms" class="tab-content">
            <div class="room-grid">
                <?php foreach ($common_rooms as $room): ?>
                    <div class="room-card">
                        <div class="room-title"><?= htmlspecialchars($room['room_name']) ?></div>
                        <div class="slot-list">
                            <?php 
                                $hall_bookings = $bookings['common'][$room['id']] ?? [];
                                if (!empty($hall_bookings)): ?>
                                    <div style="font-weight:bold; font-size:12px; color:var(--approved); margin-bottom:5px;">Present & Future Bookings:</div>
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
                            <!-- Request Hall button removed for Staff (HOD only) -->
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal" id="bookingModal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('bookingModal')">&times;</span>
            <h2 id="modal_title">Book Room</h2>
            <form action="booking_actions.php" method="POST">
                <input type="hidden" name="action" id="modal_action" value="staff_book">
                <input type="hidden" name="dept_room_id" id="modal_dept_room_id">
                <input type="hidden" name="room_type_id" id="modal_type_id">
                <input type="hidden" name="room_id" id="modal_room_id">
                <input type="hidden" name="booking_date" value="<?= $selected_date ?>">
                
                <p id="modal_info" style="margin-bottom: 20px; font-weight: bold; color: var(--approved);"></p>

                <div id="booking_fields">
                    <div class="input-group" id="period_group">
                        <label>Period</label>
                        <select name="period" id="modal_period" style="width:100%; padding:12px; background:rgba(255,255,255,0.05); color:#fff; border:1px solid var(--border-glass); border-radius:8px;">
                            <?php foreach($periods as $p_num => $p): ?>
                                <option value="<?= $p_num ?>"><?= $p_num ?> (<?= $p['label'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-group" id="class_name_group">
                        <label>Class Name</label>
                        <input type="text" name="class_name" id="modal_class_name" placeholder="e.g. III Year CS A">
                    </div>

                    <div id="duration_type_group" class="hidden">
                        <div class="input-group" style="margin-bottom:15px;">
                            <label>Duration Type</label>
                            <select name="duration_type" id="duration_type" onchange="toggleDurationInputs()" style="width:100%; padding:12px; background:rgba(255,255,255,0.05); color:#fff; border:1px solid var(--border-glass); border-radius:8px;">
                                <option value="period">Specific Period</option>
                                <option value="hourly">Hourly</option>
                                <option value="half_day">Half Day</option>
                                <option value="full_day">Full Day</option>
                            </select>
                        </div>
                    </div>

                    <div id="hourly_inputs" class="hidden">
                        <div style="display:flex; gap:10px; margin-bottom:15px;">
                            <input type="time" name="start_time" placeholder="Start" style="flex:1;">
                            <input type="time" name="end_time" placeholder="End" style="flex:1;">
                        </div>
                    </div>

                    <div id="half_day_inputs" class="hidden">
                        <select name="half_day_type" style="width:100%; padding:12px; background:rgba(255,255,255,0.05); color:#fff; border:1px solid var(--border-glass); border-radius:8px; margin-bottom:15px;">
                            <option value="Morning">Morning</option>
                            <option value="Afternoon">Afternoon</option>
                        </select>
                    </div>
                </div>

                <div class="input-group">
                    <label>Purpose of Booking</label>
                    <input type="text" name="purpose" required placeholder="e.g. Practical Exam">
                </div>
                <button type="submit" class="submit-btn" style="background:var(--approved);">Send Request</button>
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

        function openBookingModal(type, id, name, period, periodLabel, status) {
            if (status !== 'Available') return;
            
            document.getElementById('modal_type_id').value = type;
            if(type === 'dept') {
                document.getElementById('modal_action').value = 'staff_book';
                document.getElementById('modal_dept_room_id').value = id;
                document.getElementById('modal_period').value = period;
                document.getElementById('duration_type_group').classList.add('hidden');
                document.getElementById('class_name_group').classList.remove('hidden');
                document.getElementById('modal_info').innerText = "Booking " + name + " for Period " + period;
                
                const modal = document.getElementById('bookingModal');
                modal.style.display = 'flex';
                setTimeout(() => modal.classList.add('active'), 10);
            } else {
                // Common hall booking restricted to HOD
                return;
            }
            
            toggleDurationInputs();
            // Modal display moved inside if(type==='dept') for Staff
        }

        function toggleDurationInputs() {
            const type = document.getElementById('duration_type').value;
            const roomType = document.getElementById('modal_type_id').value;
            
            document.getElementById('hourly_inputs').classList.add('hidden');
            document.getElementById('half_day_inputs').classList.add('hidden');
            
            // Period field visibility
            const periodGroup = document.getElementById('period_group');
            if (roomType === 'dept' || type === 'period') {
                periodGroup.classList.remove('hidden');
            } else {
                periodGroup.classList.add('hidden');
            }
            
            if (type === 'hourly') document.getElementById('hourly_inputs').classList.remove('hidden');
            else if (type === 'half_day') document.getElementById('half_day_inputs').classList.remove('hidden');
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            modal.classList.remove('active');
            setTimeout(() => modal.style.display = 'none', 300);
        }
    </script>
</body>
</html>
