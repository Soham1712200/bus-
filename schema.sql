-- =======================================================
-- GOPI TRAVELS COMPLETE DATABASE SCHEMA & SEED SCRIPT
-- Total Routes: 28 | Total Buses: 350
-- =======================================================
CREATE DATABASE IF NOT EXISTS `gopi_travels` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `gopi_travels`;

-- Disable foreign key checks for safe schema setup/recreation
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `contact_inquiries`;
DROP TABLE IF EXISTS `cancellations`;
DROP TABLE IF EXISTS `seat_locks`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `buses`;
DROP TABLE IF EXISTS `routes`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------
-- 1. Table: Users
-- -------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('passenger', 'admin', 'counter_agent') DEFAULT 'passenger',
  `branch_office` VARCHAR(100) DEFAULT 'Ahmedabad Head Office',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 2. Table: Routes
-- -------------------------------------------------------
CREATE TABLE `routes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `source_city` VARCHAR(100) NOT NULL,
  `destination_city` VARCHAR(100) NOT NULL,
  `distance_km` INT NOT NULL,
  `estimated_time` VARCHAR(50) NOT NULL,
  `via_cities` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_source_city` (`source_city`),
  INDEX `idx_destination_city` (`destination_city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 3. Table: Buses
-- -------------------------------------------------------
CREATE TABLE `buses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bus_number` VARCHAR(30) NOT NULL UNIQUE,
  `route_id` INT NOT NULL,
  `bus_class` ENUM('Express', 'Gurjarnagri', 'Sleeper 2+1', 'AC Seater', 'Volvo AC 2+2', 'Electric AC') NOT NULL,
  `total_seats` INT NOT NULL DEFAULT 40,
  `fare` DECIMAL(10, 2) NOT NULL,
  `departure_time` TIME NOT NULL,
  `arrival_time` TIME NOT NULL,
  `is_ac` TINYINT(1) DEFAULT 0,
  `is_sleeper` TINYINT(1) DEFAULT 0,
  `amenities` TEXT DEFAULT NULL,
  `status` ENUM('Active', 'Maintenance', 'Cancelled') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`route_id`) REFERENCES `routes`(`id`) ON DELETE CASCADE,
  INDEX `idx_route_id` (`route_id`),
  INDEX `idx_bus_class` (`bus_class`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 4. Table: Bookings
-- -------------------------------------------------------
CREATE TABLE `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_reference` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT NULL,
  `bus_id` INT NOT NULL,
  `journey_date` DATE NOT NULL,
  `seat_numbers` VARCHAR(100) NOT NULL,
  `total_passengers` INT NOT NULL DEFAULT 1,
  `base_fare` DECIMAL(10, 2) NOT NULL,
  `gst_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10, 2) NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT 'Net Banking',
  `payment_status` ENUM('Pending', 'Paid', 'Failed', 'Refunded') DEFAULT 'Paid',
  `transaction_id` VARCHAR(100) NOT NULL,
  `passenger_name` VARCHAR(100) NOT NULL,
  `passenger_phone` VARCHAR(20) NOT NULL,
  `passenger_email` VARCHAR(150) NOT NULL,
  `passenger_details` JSON DEFAULT NULL,
  `booking_status` ENUM('Confirmed', 'Cancelled') DEFAULT 'Confirmed',
  `booking_channel` ENUM('online', 'counter') DEFAULT 'online',
  `booked_by_user_id` INT NULL,
  `boarding_point` VARCHAR(150) DEFAULT 'Central Office Terminal',
  `counter_payment_mode` VARCHAR(50) DEFAULT 'Net Banking',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`booked_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`bus_id`) REFERENCES `buses`(`id`) ON DELETE CASCADE,
  INDEX `idx_booking_ref` (`booking_reference`),
  INDEX `idx_passenger_phone` (`passenger_phone`),
  INDEX `idx_journey_date` (`journey_date`),
  INDEX `idx_booking_channel` (`booking_channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 5. Table: Seat Locks
-- -------------------------------------------------------
CREATE TABLE `seat_locks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bus_id` INT NOT NULL,
  `journey_date` DATE NOT NULL,
  `seat_number` VARCHAR(10) NOT NULL,
  `session_token` VARCHAR(100) NOT NULL,
  `locked_by_channel` ENUM('online', 'counter') NOT NULL DEFAULT 'counter',
  `locked_by_name` VARCHAR(100) DEFAULT 'Staff',
  `locked_until` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_bus_seat_lock` (`bus_id`, `journey_date`, `seat_number`),
  FOREIGN KEY (`bus_id`) REFERENCES `buses`(`id`) ON DELETE CASCADE,
  INDEX `idx_journey_date_lock` (`journey_date`),
  INDEX `idx_locked_until` (`locked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 6. Table: Cancellations
-- -------------------------------------------------------
CREATE TABLE `cancellations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `booking_reference` VARCHAR(50) NOT NULL,
  `refund_amount` DECIMAL(10, 2) NOT NULL,
  `cancellation_charge` DECIMAL(10, 2) NOT NULL,
  `cancellation_reason` VARCHAR(255) DEFAULT 'Passenger Request',
  `refund_upi_id` VARCHAR(50) DEFAULT NULL,
  `cancelled_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  INDEX `idx_cancel_booking_ref` (`booking_reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 7. Table: Contact Inquiries
-- -------------------------------------------------------
CREATE TABLE `contact_inquiries` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `subject` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('New', 'In Progress', 'Resolved') DEFAULT 'New',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- SEED DATA: Users
-- =======================================================
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password_hash`, `role`, `branch_office`) VALUES
(1, 'Jayesh Patel', 'demo@gopitravels.com', '9876543210', '$2y$12$FucW.nMpBIgrtYIpuxgq/.KUiWtISqX4fgc3HtoxTm079hqOrv3xa', 'passenger', NULL),
(2, 'Admin Operations Manager', 'admin@gopitravels.com', '9825012345', '$2y$12$FucW.nMpBIgrtYIpuxgq/.KUiWtISqX4fgc3HtoxTm079hqOrv3xa', 'admin', 'Ahmedabad Head Office'),
(3, 'Ramesh Shah (Ahmedabad Counter)', 'counter.ahmedabad@gopitravels.com', '9825100111', '$2y$12$FucW.nMpBIgrtYIpuxgq/.KUiWtISqX4fgc3HtoxTm079hqOrv3xa', 'counter_agent', 'Ahmedabad Head Office (Geeta Mandir)'),
(4, 'Suresh Mehta (Surat Counter)', 'counter.surat@gopitravels.com', '9825200222', '$2y$12$FucW.nMpBIgrtYIpuxgq/.KUiWtISqX4fgc3HtoxTm079hqOrv3xa', 'counter_agent', 'Surat Central Branch Office (Ring Road)'),
(5, 'Dipak Joshi (Vadodara Counter)', 'counter.vadodara@gopitravels.com', '9825300333', '$2y$12$FucW.nMpBIgrtYIpuxgq/.KUiWtISqX4fgc3HtoxTm079hqOrv3xa', 'counter_agent', 'Vadodara Central Branch Office (Sayajigunj)');

-- =======================================================
-- SEED DATA: Routes (28 Routes)
-- =======================================================
INSERT INTO `routes` (`id`, `source_city`, `destination_city`, `distance_km`, `estimated_time`, `via_cities`) VALUES
(1, 'Ahmedabad', 'Surat', 265, '4 hrs 30 mins', 'Nadiad, Anand, Vadodara, Bharuch, Ankleshwar'),
(2, 'Surat', 'Ahmedabad', 265, '4 hrs 30 mins', 'Ankleshwar, Bharuch, Vadodara, Anand, Nadiad'),
(3, 'Ahmedabad', 'Rajkot', 215, '3 hrs 45 mins', 'Sanand, Bagodara, Limbdi, Chotila'),
(4, 'Rajkot', 'Ahmedabad', 215, '3 hrs 45 mins', 'Chotila, Limbdi, Bagodara, Sanand'),
(5, 'Surat', 'Vadodara', 150, '2 hrs 45 mins', 'Ankleshwar, Bharuch, Karjan'),
(6, 'Vadodara', 'Surat', 150, '2 hrs 45 mins', 'Karjan, Bharuch, Ankleshwar'),
(7, 'Ahmedabad', 'Vadodara', 115, '2 hrs 00 mins', 'Nadiad, Anand, Vasad'),
(8, 'Vadodara', 'Ahmedabad', 115, '2 hrs 00 mins', 'Vasad, Anand, Nadiad'),
(9, 'Ahmedabad', 'Bhavnagar', 170, '3 hrs 30 mins', 'Dholera, Fedra, Pipli, Vallabhipur'),
(10, 'Bhavnagar', 'Ahmedabad', 170, '3 hrs 30 mins', 'Vallabhipur, Pipli, Fedra, Dholera'),
(11, 'Ahmedabad', 'Bhuj', 335, '6 hrs 30 mins', 'Viramgam, Dhrangadhra, Halvad, Maliya, Gandhidham, Anjar'),
(12, 'Bhuj', 'Ahmedabad', 335, '6 hrs 30 mins', 'Anjar, Gandhidham, Maliya, Halvad, Dhrangadhra, Viramgam'),
(13, 'Surat', 'Rajkot', 440, '7 hrs 45 mins', 'Vadodara, Anand, Bagodara, Chotila'),
(14, 'Rajkot', 'Surat', 440, '7 hrs 45 mins', 'Chotila, Bagodara, Anand, Vadodara'),
(15, 'Surat', 'Bhavnagar', 360, '6 hrs 30 mins', 'Bharuch, Dahej, Tarapur'),
(16, 'Bhavnagar', 'Surat', 360, '6 hrs 30 mins', 'Tarapur, Bharuch, Ankleshwar'),
(17, 'Ahmedabad', 'Junagadh', 315, '5 hrs 45 mins', 'Bagodara, Limbdi, Rajkot, Gondal, Jetpur'),
(18, 'Junagadh', 'Ahmedabad', 315, '5 hrs 45 mins', 'Jetpur, Gondal, Rajkot, Limbdi, Bagodara'),
(19, 'Ahmedabad', 'Jamnagar', 305, '5 hrs 30 mins', 'Limbdi, Chotila, Rajkot, Dhrol'),
(20, 'Jamnagar', 'Ahmedabad', 305, '5 hrs 30 mins', 'Dhrol, Rajkot, Chotila, Limbdi'),
(21, 'Gandhinagar', 'Surat', 290, '5 hrs 00 mins', 'Ahmedabad, Anand, Vadodara, Bharuch'),
(22, 'Surat', 'Gandhinagar', 290, '5 hrs 00 mins', 'Bharuch, Vadodara, Anand, Ahmedabad'),
(23, 'Ahmedabad', 'Somnath', 410, '7 hrs 30 mins', 'Rajkot, Gondal, Jetpur, Junagadh, Veraval'),
(24, 'Somnath', 'Ahmedabad', 410, '7 hrs 30 mins', 'Veraval, Junagadh, Jetpur, Gondal, Rajkot'),
(25, 'Surat', 'Vapi', 115, '2 hrs 00 mins', 'Navsari, Bilimora, Valsad'),
(26, 'Vapi', 'Surat', 115, '2 hrs 00 mins', 'Valsad, Bilimora, Navsari'),
(27, 'Ahmedabad', 'Dwarka', 440, '8 hrs 00 mins', 'Rajkot, Jamnagar, Khambhalia, Bhatiya'),
(28, 'Dwarka', 'Ahmedabad', 440, '8 hrs 00 mins', 'Bhatiya, Khambhalia, Jamnagar, Rajkot');

-- =======================================================
-- SEED DATA: Buses (Generates Exactly 350 Buses)
-- =======================================================
INSERT INTO `buses` (
  `bus_number`, 
  `route_id`, 
  `bus_class`, 
  `total_seats`, 
  `fare`, 
  `departure_time`, 
  `arrival_time`, 
  `is_ac`, 
  `is_sleeper`, 
  `amenities`, 
  `status`
)
WITH RECURSIVE BusTemplates AS (
    SELECT 1 AS idx, 'Express' AS bclass, 52 AS seats, TIME('05:00:00') AS dep_time, 0 AS ac, 0 AS sleeper, 1.00 AS fmult, 'Reclining Seats, Charging Point' AS amen
    UNION ALL SELECT 2, 'Gurjarnagri', 48, TIME('06:15:00'), 0, 0, 1.10, 'Comfortable Seating, Reading Light'
    UNION ALL SELECT 3, 'Sleeper 2+1', 30, TIME('07:30:00'), 0, 1, 1.40, 'Pillow, Blanket, Charging Point, Luggage Space'
    UNION ALL SELECT 4, 'AC Seater', 45, TIME('08:45:00'), 1, 0, 1.50, 'Air Conditioner, USB Charger, Reclining Seats'
    UNION ALL SELECT 5, 'Volvo AC 2+2', 40, TIME('10:00:00'), 1, 0, 2.00, 'AC, Water Bottle, High-Speed Wi-Fi, Entertainment'
    UNION ALL SELECT 6, 'Electric AC', 38, TIME('11:30:00'), 1, 0, 1.80, 'Silent Electric Drive, AC, USB Ports, GPS Tracking'
    UNION ALL SELECT 7, 'Express', 52, TIME('13:00:00'), 0, 0, 1.00, 'Reclining Seats, Fan'
    UNION ALL SELECT 8, 'Gurjarnagri', 48, TIME('14:30:00'), 0, 0, 1.10, 'Comfortable Seats, Overhead Storage'
    UNION ALL SELECT 9, 'AC Seater', 45, TIME('16:00:00'), 1, 0, 1.50, 'AC, Reclining Seats, Emergency Button'
    UNION ALL SELECT 10, 'Volvo AC 2+2', 40, TIME('17:45:00'), 1, 0, 2.05, 'AC, Live Tracking, Snacks, Wi-Fi'
    UNION ALL SELECT 11, 'Sleeper 2+1', 30, TIME('19:30:00'), 1, 1, 1.85, 'AC, Clean Bedding, Reading Lamp, Charging Port'
    UNION ALL SELECT 12, 'Volvo AC 2+2', 40, TIME('21:15:00'), 1, 0, 2.10, 'AC, Water Bottle, Wi-Fi, Blankets'
    UNION ALL SELECT 13, 'Sleeper 2+1', 30, TIME('22:45:00'), 1, 1, 1.90, 'AC Sleeper, Bedspread, Pillow, Individual Lights'
)
SELECT 
    CONCAT(
        'GJ-', 
        LPAD(CAST(((r.id * 2 + t.idx) % 38) + 1 AS CHAR), 2, '0'), 
        '-GT-', 
        LPAD(CAST(1000 + ((r.id - 1) * 13 + t.idx) AS CHAR), 4, '0')
    ) AS bus_number,
    
    r.id AS route_id,
    t.bclass AS bus_class,
    t.seats AS total_seats,
    ROUND(r.distance_km * 1.40 * t.fmult, 2) AS fare,
    t.dep_time AS departure_time,
    
    ADDTIME(
        t.dep_time, 
        SEC_TO_TIME(
            (CAST(SUBSTRING_INDEX(r.estimated_time, ' hrs', 1) AS UNSIGNED) * 3600) +
            (CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(r.estimated_time, ' mins', 1), 'hrs ', -1) AS UNSIGNED) * 60)
        )
    ) AS arrival_time,
    
    t.ac AS is_ac,
    t.sleeper AS is_sleeper,
    t.amen AS amenities,
    'Active' AS status

FROM `routes` r
CROSS JOIN BusTemplates t
WHERE ((r.id - 1) * 13 + t.idx) <= 350
ORDER BY r.id, t.idx;