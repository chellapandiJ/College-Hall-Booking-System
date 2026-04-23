<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'vp') { exit('Unauthorized'); }
require 'db.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=vp_booking_history_' . date('Ymd') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Booking Date', 'Hall Name', 'Room #', 'Requested By', 'Department', 'Duration', 'Purpose', 'Status', 'Processed At']);

$history_sql = "SELECT rb.*, cr.room_name, cr.room_number 
                FROM room_bookings rb 
                LEFT JOIN common_event_rooms cr ON rb.common_room_id = cr.id 
                WHERE rb.common_room_id IS NOT NULL AND rb.status != 'Pending'
                ORDER BY rb.created_at DESC";

$result = $conn->query($history_sql);

while ($row = $result->fetch_assoc()) {
    $duration = $row['duration_type'] == 'period' ? "Period " . $row['period'] : $row['duration_type'];
    if($row['duration_type'] == 'hourly') $duration = "{$row['start_time']} - {$row['end_time']}";
    elseif($row['duration_type'] == 'half_day') $duration = "Half Day ({$row['half_day_type']})";
    
    fputcsv($output, [
        $row['id'], 
        $row['booking_date'], 
        $row['room_name'], 
        $row['room_number'], 
        $row['user_name'], 
        $row['department'], 
        $duration, 
        $row['purpose'], 
        $row['status'],
        $row['created_at']
    ]);
}

fclose($output);
exit;
