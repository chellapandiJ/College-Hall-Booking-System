<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { exit('Unauthorized'); }
require 'db.php';

$type = $_GET['report_type'] ?? 'dept_rooms';
$dept = $_GET['department'] ?? 'all';
$from = $_GET['from_date'] ?? date('Y-m-01');
$to = $_GET['to_date'] ?? date('Y-m-t');
$format = $_GET['format'] ?? 'view';

// Build Query
$sql = "SELECT rb.*, dr.room_type as dr_type, dr.room_number as dr_num, cr.room_name as cr_name, cr.room_number as cr_num 
        FROM room_bookings rb 
        LEFT JOIN department_rooms dr ON rb.dept_room_id = dr.id 
        LEFT JOIN common_event_rooms cr ON rb.common_room_id = cr.id 
        WHERE rb.booking_date BETWEEN ? AND ?";

if ($type === 'dept_rooms') {
    $sql .= " AND rb.dept_room_id IS NOT NULL";
    if ($dept !== 'all') {
        $sql .= " AND rb.department = ?";
    }
} else {
    $sql .= " AND rb.common_room_id IS NOT NULL";
    $dept = 'all'; // Ignore dept filter for event halls
}

$sql .= " ORDER BY rb.booking_date ASC";

$stmt = $conn->prepare($sql);
if ($dept !== 'all') {
    $stmt->bind_param("sss", $from, $to, $dept);
} else {
    $stmt->bind_param("ss", $from, $to);
}
$stmt->execute();
$result = $stmt->get_result();

// Export to Excel (CSV)
if ($format === 'excel') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=booking_report_' . date('Ymd') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Date', 'Room/Hall', 'User Name', 'Dept', 'Role', 'Duration/Period', 'Purpose', 'Status']);
    
    while ($row = $result->fetch_assoc()) {
        $room = ($type === 'dept_rooms') ? ($row['dr_type'] . " - " . $row['dr_num']) : ($row['cr_name'] . " (" . $row['cr_num'] . ")");
        $duration = $row['duration_type'] == 'period' ? "Period " . $row['period'] : $row['duration_type'];
        if($row['duration_type'] == 'hourly') $duration = "{$row['start_time']} - {$row['end_time']}";
        elseif($row['duration_type'] == 'half_day') $duration = "Half Day ({$row['half_day_type']})";
        
        fputcsv($output, [
            $row['id'], $row['booking_date'], $room, $row['user_name'], $row['department'], 
            $row['user_role'], $duration, $row['purpose'], $row['status']
        ]);
    }
    fclose($output);
    exit;
}

// PDF/View Output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Report</title>
    <style>
        body { font-family: sans-serif; padding: 40px; color: #333; }
        h1, h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #f4f4f4; }
        .no-print { margin-bottom: 20px; display: flex; gap: 10px; justify-content: center; }
        button { padding: 10px 20px; cursor: pointer; background: #4F46E5; color: #fff; border: none; border-radius: 6px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Print / Save as PDF</button>
        <button onclick="window.close()">Close</button>
    </div>
    <h1>College Room Booking Management System</h1>
    <h2><?= ($type === 'dept_rooms') ? 'Department Rooms' : 'Common Event Halls' ?> Booking Report</h2>
    <p style="text-align:center;">From: <strong><?= $from ?></strong> To: <strong><?= $to ?></strong> | Dept: <strong><?= $dept ?></strong></p>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Room/Hall</th>
                <th>Requested By</th>
                <th>Department</th>
                <th>Duration/Period</th>
                <th>Purpose</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows === 0): ?>
                <tr><td colspan="7" style="text-align:center;">No records found.</td></tr>
            <?php else: ?>
                <?php while ($row = $result->fetch_assoc()): 
                    $room = ($type === 'dept_rooms') ? ($row['dr_type'] . " - " . $row['dr_num']) : ($row['cr_name'] . " (" . $row['cr_num'] . ")");
                    $duration = $row['duration_type'] == 'period' ? "Period " . $row['period'] : $row['duration_type'];
                    if($row['duration_type'] == 'hourly') $duration = "{$row['start_time']} - {$row['end_time']}";
                    elseif($row['duration_type'] == 'half_day') $duration = "Half Day ({$row['half_day_type']})";
                ?>
                    <tr>
                        <td><?= $row['booking_date'] ?></td>
                        <td><?= htmlspecialchars($room) ?></td>
                        <td><?= htmlspecialchars($row['user_name']) ?> (<?= $row['user_role'] ?>)</td>
                        <td><?= htmlspecialchars($row['department']) ?></td>
                        <td><?= $duration ?></td>
                        <td><?= htmlspecialchars($row['purpose']) ?></td>
                        <td><?= $row['status'] ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
