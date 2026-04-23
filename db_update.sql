USE `college_booking_sys`;

-- Departments Table
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dept_name` varchar(100) NOT NULL UNIQUE,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rooms Table
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_number` varchar(50) NOT NULL,
  `room_type` varchar(50) NOT NULL, -- Smart Classroom, AV Room, Seminar Room, Event Hall
  `is_event_hall` tinyint(1) DEFAULT 0,
  `department` varchar(100) DEFAULT NULL,
  `status` enum('Active', 'Inactive') DEFAULT 'Active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bookings Table
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `user_role` enum('staff', 'hod') NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `booking_date` date NOT NULL,
  `time_slots` varchar(255) NOT NULL, -- Comma separated e.g., '1,2,3'
  `class_name` varchar(100) DEFAULT NULL,
  `purpose` text NOT NULL,
  `status` enum('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
