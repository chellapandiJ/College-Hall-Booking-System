USE `college_booking_sys`;

ALTER TABLE `room_bookings` 
ADD COLUMN `duration_type` ENUM('period', 'hourly', 'half_day', 'full_day') DEFAULT 'period' AFTER `period`,
ADD COLUMN `start_time` TIME DEFAULT NULL AFTER `duration_type`,
ADD COLUMN `end_time` TIME DEFAULT NULL AFTER `start_time`,
ADD COLUMN `half_day_type` ENUM('Morning', 'Afternoon') DEFAULT NULL AFTER `end_time`;

-- Ensure VP has a way to handle approvals
-- The current schema has status enum('Pending','Approved','Rejected','Expired')
-- VP will approve bookings where common_room_id IS NOT NULL 
