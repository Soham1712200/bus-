-- =======================================================
-- Jayambe Travels - Bus Booking Web Application
-- Database Schema & Comprehensive Seed Data
-- =======================================================

CREATE DATABASE IF NOT EXISTS `jayambe_travels` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `jayambe_travels`;

-- Drop tables if they already exist in reverse order of foreign keys
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `cancellations`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `buses`;
DROP TABLE IF EXISTS `routes`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `contact_inquiries`;
SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------
-- 1. Table: Users (Passenger Accounts & Admins)
-- -------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('passenger', 'admin') DEFAULT 'passenger',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 2. Table: Routes (Major Gujarat Corridors)
-- -------------------------------------------------------
CREATE TABLE `routes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `source_city` VARCHAR(100) NOT NULL,
  `destination_city` VARCHAR(100) NOT NULL,
  `distance_km` INT NOT NULL,
  `estimated_time` VARCHAR(50) NOT NULL,
  `via_cities` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`source_city`),
  INDEX (`destination_city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 3. Table: Buses (Fleet Assignment - 20 buses per route)
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
  INDEX (`route_id`),
  INDEX (`bus_class`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 4. Table: Bookings (Reservations & Ticket Records)
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
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`bus_id`) REFERENCES `buses`(`id`) ON DELETE CASCADE,
  INDEX (`booking_reference`),
  INDEX (`passenger_phone`),
  INDEX (`journey_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 5. Table: Cancellations (Ticket Cancellation Logs)
-- -------------------------------------------------------
CREATE TABLE `cancellations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `booking_reference` VARCHAR(50) NOT NULL,
  `refund_amount` DECIMAL(10, 2) NOT NULL,
  `cancellation_charge` DECIMAL(10, 2) NOT NULL,
  `cancellation_reason` VARCHAR(255) DEFAULT 'Passenger Request',
  `cancelled_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  INDEX (`booking_reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 6. Table: Contact Inquiries
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =======================================================
-- SEED DATA: Users (Default Demo Account)
-- Default password: "password123"
-- =======================================================
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password_hash`, `role`) VALUES
(1, 'Jayesh Patel', 'demo@jayambetravels.com', '9876543210', '$2y$10$wT8mQ0ZcRkP.bL1fP6j8y.Mh0gW1X7K2zE6f34n7W5sK0mU4W7w4a', 'passenger'),
(2, 'Admin Jayambe', 'admin@jayambetravels.com', '9825012345', '$2y$10$wT8mQ0ZcRkP.bL1fP6j8y.Mh0gW1X7K2zE6f34n7W5sK0mU4W7w4a', 'admin');

-- =======================================================
-- SEED DATA: Routes across all major Gujarat Cities
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
