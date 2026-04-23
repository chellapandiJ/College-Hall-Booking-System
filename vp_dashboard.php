<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'vp') { header('Location: index.php'); exit; }
require 'db.php';

date_default_timezone_set('Asia/Kolkata');
$today = date('Y-m-d');
$current_time = date('H:i:s');

// Fetch pending common hall requests
$sql = "SELECT rb.*, cr.room_name, cr.room_number 
        FROM room_bookings rb 
        JOIN common_event_rooms cr ON rb.common_room_id = cr.id 
        WHERE rb.status = 'Pending' 
        ORDER BY rb.booking_date ASC";
$result = $conn->query($sql);

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
    <title>Vice Principal Dashboard - Hall Booking Approvals</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            --accent-gradient: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
            --danger-gradient: linear-gradient(135deg, #ef4444 0%, #f43f5e 100%);
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        .container { padding: 40px 5%; margin-top: 20px;}
        .card { background: var(--bg-glass); border: 1px solid var(--border-glass); border-radius: 20px; padding: 30px; margin-bottom: 25px; backdrop-filter: blur(15px); box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .card h3 { margin-bottom: 25px; color: #fff; font-size: 1.5rem; font-weight: 600; letter-spacing: -0.5px;}
        
        .tabs { display:flex; gap:15px; margin-bottom:30px; padding: 5px; background: rgba(255,255,255,0.03); border-radius: 12px; width: fit-content; border: 1px solid var(--glass-border); }
        .tab-btn { 
            padding: 12px 25px; 
            border-radius: 10px; 
            font-weight: 600; 
            cursor: pointer; 
            border: none; 
            color: #94a3b8; 
            background: transparent; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.95rem;
            letter-spacing: 0.3px;
        }
        .tab-btn:hover { color: #fff; background: rgba(255,255,255,0.05); }
        .tab-btn.active { 
            background: var(--primary-gradient); 
            color: #fff; 
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }

        .request-item { background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); padding: 20px; border-radius: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; transition: transform 0.2s; }
        .request-item:hover { transform: translateY(-2px); border-color: rgba(255,255,255,0.2); }
        .request-details { color: #e2e8f0; line-height: 1.8; }
        .request-details strong { color: #fff; font-weight: 600; }
        
        .request-actions { display: flex; gap: 12px; }
        .btn { 
            padding: 10px 20px; 
            border-radius: 10px; 
            font-weight: 600; 
            cursor: pointer; 
            border: none; 
            color:#fff; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            text-decoration: none; 
            font-size: 0.9rem;
            transition: all 0.2s;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .btn:hover { transform: translateY(-1px); filter: brightness(1.1); box-shadow: 0 6px 15px rgba(0,0,0,0.2); }
        .btn-approve { background: var(--accent-gradient); }
        .btn-decline { background: var(--danger-gradient); }
        .btn-edit { background: #3b82f6; }
        .btn-delete { background: #64748b; }

        .tab-content { display: none; animation: fadeIn 0.4s ease-out; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Table Styling */
        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        table th { padding: 15px; text-align: left; background: rgba(255,255,255,0.02); color: #94a3b8; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; border-bottom: 1px solid var(--glass-border); }
        table td { padding: 15px; border-bottom: 1px solid var(--glass-border); color: #e2e8f0; }
        table tr:last-child td { border-bottom: none; }
        table tr:hover td { background: rgba(255,255,255,0.01); }

        /* Modal / Form Styling */
        .modal { 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.8); display: none; align-items: center; justify-content: center; 
            z-index: 1000; backdrop-filter: blur(8px);
        }
        .modal.active { display: flex; }
        .modal-content { 
            background: #1e293b; 
            border: 1px solid var(--glass-border); 
            width: 100%; max-width: 500px; 
            padding: 35px; border-radius: 24px; 
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            animation: slideUp 0.3s ease-out;
        }
        @keyframes slideUp { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        
        .modal-content h2 { color: #fff; margin-bottom: 25px; font-weight: 700; font-size: 1.5rem; letter-spacing: -0.5px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: #94a3b8; margin-bottom: 8px; font-size: 0.9rem; font-weight: 500; }
        .form-group input, .form-group select { 
            width: 100%; padding: 12px 16px; border-radius: 12px; 
            background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); 
            color: #fff; font-size: 1rem; transition: all 0.2s;
            outline: none;
        }
        .form-group input:focus, .form-group select:focus { 
            border-color: #6366f1; background: rgba(255,255,255,0.08); 
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        
        .hidden { display: none !from-code; }
    </style>
</head>
<body>
    <header>
        <div class="logo">VP Portal</div>
        <div class="auth-nav">
            <span>Welcome, Vice Principal</span>
            <button onclick="location.href='logout.php'">Logout</button>
        </div>
    </header>

    <div class="container">
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('pending_halls')">Pending Requests</button>
            <button class="tab-btn" onclick="showTab('booking_history')">Booking History</button>
        </div>

        <div id="pending_halls" class="tab-content active">
            <div class="card">
                <h3>Pending Common Hall Bookings</h3>
            
            <?php if ($result->num_rows == 0): ?>
                <p style="color:var(--text-muted); text-align:center; padding:40px;">No pending hall requests at the moment.</p>
            <?php else: ?>
                <?php while ($row = $result->fetch_assoc()): 
                    $is_expired = false;
                    if ($row['booking_date'] < $today) {
                        $is_expired = true;
                    } elseif ($row['booking_date'] == $today) {
                        if ($row['duration_type'] == 'period' && $row['period'] > 0) {
                            $is_expired = ($current_time >= $periods[$row['period']]['start']);
                        } elseif ($row['duration_type'] == 'hourly' && $row['start_time']) {
                            $is_expired = ($current_time >= $row['start_time']);
                        } elseif ($row['duration_type'] == 'half_day') {
                            $session_start = ($row['half_day_type'] == 'Morning') ? '08:30:00' : '13:00:00';
                            $is_expired = ($current_time >= $session_start);
                        }
                    }
                    
                    if ($is_expired) {
                        $conn->query("UPDATE room_bookings SET status = 'Expired' WHERE id = " . $row['id']);
                        continue; // Hide it
                    }
                ?>
                    <div class="request-item">
                        <div class="request-details">
                            <div><strong>Requested By:</strong> <?= htmlspecialchars($row['user_name']) ?> (<?= htmlspecialchars($row['department']) ?>)</div>
                            <div><strong>Hall:</strong> <?= htmlspecialchars($row['room_name']) ?> (<?= htmlspecialchars($row['room_number']) ?>)</div>
                            <div><strong>Date:</strong> <?= htmlspecialchars($row['booking_date']) ?></div>
                            <div><strong>Duration:</strong> 
                                <?php 
                                    if($row['duration_type'] == 'period') echo "Period " . $row['period'];
                                    elseif($row['duration_type'] == 'hourly') echo "Hourly ({$row['start_time']} - {$row['end_time']})";
                                    elseif($row['duration_type'] == 'half_day') echo "Half Day ({$row['half_day_type']})";
                                    else echo "Full Day";
                                ?>
                            </div>
                            <div><strong>Purpose:</strong> <?= htmlspecialchars($row['purpose']) ?></div>
                        </div>
                        <div class="request-actions">
                            <button class="btn btn-edit" onclick='openEditModal(<?= json_encode($row) ?>)'>Edit</button>
                            <form action="booking_actions.php" method="POST">
                                <input type="hidden" name="action" value="vp_action">
                                <input type="hidden" name="booking_id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="status" value="Approved">
                                <button type="submit" class="btn btn-approve">Approve</button>
                            </form>
                            <form action="booking_actions.php" method="POST">
                                <input type="hidden" name="action" value="vp_action">
                                <input type="hidden" name="booking_id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="status" value="Rejected">
                                <button type="submit" class="btn btn-decline">Decline</button>
                            </form>
                            <form action="booking_actions.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this request?')">
                                <input type="hidden" name="action" value="vp_delete">
                                <input type="hidden" name="booking_id" value="<?= $row['id'] ?>">
                                <button type="submit" class="btn btn-delete">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
            </div>
        </div>

        <div id="booking_history" class="tab-content">
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
                    <h3 style="margin:0;">Recent Booking Actions</h3>
                    <a href="vp_export_history.php" class="btn" style="background:#10b981; text-decoration:none;">Download History (Excel)</a>
                </div>
                <?php 
                    $history_sql = "SELECT rb.*, cr.room_name, cr.room_number 
                                    FROM room_bookings rb 
                                    LEFT JOIN common_event_rooms cr ON rb.common_room_id = cr.id 
                                    WHERE rb.common_room_id IS NOT NULL AND rb.status != 'Pending'
                                    ORDER BY rb.created_at DESC LIMIT 100";
                    $history_res = $conn->query($history_sql);
                    if ($history_res->num_rows == 0):
                ?>
                    <p style="color:var(--text-muted); text-align:center; padding:40px;">No history available.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Hall</th>
                                    <th>By</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($h = $history_res->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $h['booking_date'] ?></td>
                                        <td><?= htmlspecialchars($h['room_name']) ?></td>
                                        <td><?= htmlspecialchars($h['user_name']) ?> (<?= $h['department'] ?>)</td>
                                        <td>
                                            <?php 
                                                if($h['duration_type'] == 'period') echo "Period " . $h['period'];
                                                elseif($h['duration_type'] == 'hourly') echo "{$h['start_time']} - {$h['end_time']}";
                                                elseif($h['duration_type'] == 'half_day') echo "Half Day ({$h['half_day_type']})";
                                                else echo "Full Day";
                                            ?>
                                        </td>
                                        <td>
                                            <span style="color: <?= $h['status']=='Approved' ? '#10b981' : ($h['status']=='Rejected' ? '#ef4444' : ($h['status']=='Expired' ? '#f59e0b' : '#94a3b8')) ?>; font-weight:600;">
                                                <?= $h['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-btns" style="display:flex; gap:8px;">
                                                <button class="btn btn-edit" style="padding:6px 12px; font-size:0.8rem;" onclick='openEditModal(<?= json_encode($h) ?>)'>Edit</button>
                                                <form action="booking_actions.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this record?')">
                                                    <input type="hidden" name="action" value="vp_delete">
                                                    <input type="hidden" name="booking_id" value="<?= $h['id'] ?>">
                                                    <button type="submit" class="btn btn-delete" style="padding:6px 12px; font-size:0.8rem;">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Booking Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="margin:0;">Edit Booking</h2>
                <span class="close-btn" style="cursor:pointer; font-size:1.5rem; color:#94a3b8;" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form action="booking_actions.php" method="POST">
                <input type="hidden" name="action" value="vp_edit_booking">
                <input type="hidden" name="booking_id" id="edit_booking_id">
                
                <div class="form-group">
                    <label>Booking Date</label>
                    <input type="date" name="booking_date" id="edit_date" required>
                </div>

                <div class="form-group">
                    <label>Duration Type</label>
                    <select name="duration_type" id="edit_duration_type" onchange="toggleEditInputs()">
                        <option value="period">Specific Period</option>
                        <option value="hourly">Hourly Range</option>
                        <option value="half_day">Half Day Session</option>
                        <option value="full_day">Full Day</option>
                    </select>
                </div>

                <div id="edit_period_group" class="form-group">
                    <label>Select Period</label>
                    <select name="period" id="edit_period">
                        <?php foreach($periods as $p_num => $p): ?>
                            <option value="<?= $p_num ?>">Period <?= $p_num ?> (<?= $p['label'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="edit_hourly_group" class="hidden">
                    <div style="display:flex; gap:15px;">
                        <div class="form-group" style="flex:1;">
                            <label>Start Time</label>
                            <input type="time" name="start_time" id="edit_start_time">
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>End Time</label>
                            <input type="time" name="end_time" id="edit_end_time">
                        </div>
                    </div>
                </div>

                <div id="edit_half_day_group" class="hidden">
                    <div class="form-group">
                        <label>Session Type</label>
                        <select name="half_day_type" id="edit_half_day_type">
                            <option value="Morning">Morning (8:30 AM - 12:30 PM)</option>
                            <option value="Afternoon">Afternoon (1:00 PM - 5:00 PM)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Purpose / Event Description</label>
                    <input type="text" name="purpose" id="edit_purpose" required placeholder="Enter purpose of booking">
                </div>

                <button type="submit" class="btn btn-approve" style="width:100%; padding:14px; font-size:1rem; margin-top:10px;">Update & Approve Booking</button>
            </form>
        </div>
    </div>

    <script>
        function showTab(id) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            
            const activeContent = document.getElementById(id);
            if(activeContent) activeContent.classList.add('active');
            
            const activeBtn = Array.from(document.querySelectorAll('.tab-btn')).find(btn => btn.getAttribute('onclick').includes(id));
            if(activeBtn) activeBtn.classList.add('active');
        }

        function openEditModal(booking) {
            document.getElementById('edit_booking_id').value = booking.id;
            document.getElementById('edit_date').value = booking.booking_date;
            document.getElementById('edit_duration_type').value = booking.duration_type;
            document.getElementById('edit_period').value = booking.period || 1;
            document.getElementById('edit_start_time').value = booking.start_time || '';
            document.getElementById('edit_end_time').value = booking.end_time || '';
            document.getElementById('edit_half_day_type').value = booking.half_day_type || 'Morning';
            document.getElementById('edit_purpose').value = booking.purpose;
            
            toggleEditInputs();
            const modal = document.getElementById('editModal');
            modal.classList.add('active');
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            modal.classList.remove('active');
        }

        function toggleEditInputs() {
            const type = document.getElementById('edit_duration_type').value;
            document.getElementById('edit_period_group').style.display = 'none';
            document.getElementById('edit_hourly_group').style.display = 'none';
            document.getElementById('edit_half_day_group').style.display = 'none';
            
            if (type === 'period') {
                document.getElementById('edit_period_group').style.display = 'block';
            } else if (type === 'hourly') {
                document.getElementById('edit_hourly_group').style.display = 'block';
            } else if (type === 'half_day') {
                document.getElementById('edit_half_day_group').style.display = 'block';
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal(event.target.id);
            }
        }
    </script>
</body>
</html>
