-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 26, 2025 at 11:23 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `chmsu_bao`
--

-- --------------------------------------------------------

--
-- Table structure for table `billing_statements`
--

CREATE TABLE `billing_statements` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `client` varchar(255) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `date_covered` date NOT NULL,
  `no_of_days` int(11) NOT NULL,
  `vehicle` varchar(50) NOT NULL,
  `bus_no` varchar(20) NOT NULL,
  `no_of_vehicles` int(11) NOT NULL,
  `from_location` varchar(255) NOT NULL,
  `to_location` varchar(255) NOT NULL,
  `distance_km` decimal(10,2) NOT NULL,
  `total_distance_km` decimal(10,2) NOT NULL,
  `fuel_rate` decimal(10,2) NOT NULL DEFAULT 70.00,
  `computed_distance` decimal(10,2) NOT NULL,
  `runtime_liters` decimal(10,2) NOT NULL,
  `fuel_cost` decimal(10,2) NOT NULL,
  `runtime_cost` decimal(10,2) NOT NULL,
  `maintenance_cost` decimal(10,2) NOT NULL,
  `standby_cost` decimal(10,2) NOT NULL,
  `additive_cost` decimal(10,2) NOT NULL,
  `rate_per_bus` decimal(10,2) NOT NULL,
  `subtotal_per_vehicle` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','cancelled') DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT NULL,
  `prepared_by` varchar(255) DEFAULT NULL,
  `recommending_approval` varchar(255) DEFAULT NULL,
  `approved_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing_statements`
--

INSERT INTO `billing_statements` (`id`, `schedule_id`, `client`, `destination`, `purpose`, `date_covered`, `no_of_days`, `vehicle`, `bus_no`, `no_of_vehicles`, `from_location`, `to_location`, `distance_km`, `total_distance_km`, `fuel_rate`, `computed_distance`, `runtime_liters`, `fuel_cost`, `runtime_cost`, `maintenance_cost`, `standby_cost`, `additive_cost`, `rate_per_bus`, `subtotal_per_vehicle`, `total_amount`, `payment_status`, `payment_date`, `prepared_by`, `recommending_approval`, `approved_by`, `created_at`, `updated_at`) VALUES
(1, 14, 'CHMSU - Central Philippine State University - OSAS', 'CHMSU - Carlos Hilado Memorial State University, Talisay City - La Salle Bacolod', 'Bayanihan', '0000-00-00', 1, '0', '2', 1, '0', '0', 8.00, 16.00, 70.00, 8.00, 25.00, 560.00, 1750.00, 5000.00, 1500.00, 1500.00, 1500.00, 11810.00, 11810.00, 'pending', NULL, 'crisalwinmaapn', 'NEUYER JAN C. BALA-AN, Director, Business Affairs Office', NULL, '2025-10-24 16:48:07', '2025-10-24 16:48:07'),
(2, 15, 'CHMSU - Central Philippine State University - OSAS', 'CHMSU - Carlos Hilado Memorial State University, Talisay City - Silay Airport', 'Grad', '0000-00-00', 1, '0', '3', 1, '0', '0', 7.00, 14.00, 70.00, 7.00, 25.00, 490.00, 1750.00, 5000.00, 1500.00, 1500.00, 1500.00, 11740.00, 11740.00, 'pending', NULL, 'crisalwinmaapn', 'NEUYER JAN C. BALA-AN, Director, Business Affairs Office', NULL, '2025-10-24 16:49:31', '2025-10-24 16:49:31'),
(3, 16, 'CHMSU - Central Philippine State University - OSAS', 'CHMSU - Carlos Hilado Memorial State University, Talisay City - Barangay 1, Bacolod City', 'Bayanihan', '0000-00-00', 1, '0', '1', 1, '0', '0', 8.00, 16.00, 70.00, 8.00, 25.00, 560.00, 1750.00, 5000.00, 1500.00, 1500.00, 1500.00, 11810.00, 11810.00, 'pending', NULL, 'Joemarie D. Damasing', 'NEUYER JAN C. BALA-AN, Director, Business Affairs Office', NULL, '2025-10-26 06:55:19', '2025-10-26 06:55:19');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `booking_id` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `facility_type` enum('gym','bus') NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `purpose` text NOT NULL,
  `attendees` int(11) DEFAULT NULL,
  `status` enum('pending','confirmed','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `additional_info` text DEFAULT NULL COMMENT 'JSON data for additional booking information'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `booking_id`, `user_id`, `facility_type`, `date`, `start_time`, `end_time`, `purpose`, `attendees`, `status`, `created_at`, `updated_at`, `additional_info`) VALUES
(1, 'GYM-2025-001', 13, 'gym', '2025-05-14', '08:30:00', '15:30:00', 'Graduation Ceremony', 100, 'confirmed', '2025-05-06 14:19:32', '2025-05-07 15:40:15', '{\"organization\": \"TUP\", \"contact_person\": \"Joemarie Dayon Damasing\", \"contact_number\": \"09105772609\", \"admin_remarks\": \"Approve\"}'),
(2, 'GYM-2025-002', 13, 'gym', '2025-05-07', '09:30:00', '15:30:00', 'School Program', 120, '', '2025-05-06 15:16:06', '2025-05-07 15:50:42', '{\"organization\":\"notre\",\"contact_person\":\"QWQ\",\"contact_number\":\"09106934023\"}'),
(3, 'GYM-2025-003', 13, 'gym', '2025-05-15', '08:30:00', '13:30:00', 'Conference', 100, 'confirmed', '2025-05-07 16:55:53', '2025-05-07 16:56:38', '{\"organization\": \"LA\", \"contact_person\": \"WKEE\", \"contact_number\": \"09105772609\", \"admin_remarks\": \"\"}'),
(4, 'GYM-2025-004', 13, 'gym', '2025-05-16', '07:30:00', '17:30:00', 'Other', 100, 'rejected', '2025-05-08 15:52:03', '2025-05-08 16:04:08', '{\"organization\": \"UP\", \"contact_person\": \"UP\", \"contact_number\": \"09979097300\", \"admin_remarks\": \"Wla lang\"}'),
(5, 'GYM-2025-005', 13, 'gym', '2025-05-16', '10:30:00', '13:30:00', 'Conference', 5, 'confirmed', '2025-05-08 17:17:43', '2025-05-18 06:04:12', '{\"organization\": \"UAAP\", \"contact_person\": \"UAAP\", \"contact_number\": \"0996909111\", \"admin_remarks\": \"\"}'),
(6, 'GYM-2025-006', 12, 'gym', '2025-05-17', '00:30:00', '17:30:00', 'School Program', 10, 'confirmed', '2025-05-08 17:20:55', '2025-05-12 17:34:48', '{\"organization\": \"STI\", \"contact_person\": \"STI\", \"contact_number\": \"09969097300\", \"admin_remarks\": \"\"}'),
(7, 'GYM-2025-007', 12, 'gym', '2025-05-18', '09:30:00', '14:30:00', 'Cultural Event', 20, 'confirmed', '2025-05-12 17:40:47', '2025-05-12 17:41:11', '{\"organization\": \"LLC\", \"contact_person\": \"LLC\", \"contact_number\": \"09969097300\", \"admin_remarks\": \"\"}'),
(8, 'GYM-2025-008', 12, 'gym', '2025-05-13', '07:30:00', '13:30:00', 'School Program', 10, 'confirmed', '2025-05-12 17:46:54', '2025-05-18 14:56:11', '{\"organization\": \"JB\", \"contact_person\": \"JB\", \"contact_number\": \"09105772609\", \"admin_remarks\": \"\"}'),
(9, 'GYM-2025-009', 12, 'gym', '2025-05-19', '05:30:00', '17:30:00', 'Graduation Ceremony', 500, 'confirmed', '2025-05-12 18:10:09', '2025-05-12 19:09:23', '{\"organization\": \"Tai\", \"contact_person\": \"Tai\", \"contact_number\": \"09979097303\", \"admin_remarks\": \"\"}'),
(10, 'GYM-2025-010', 13, 'gym', '2025-05-20', '08:30:00', '13:00:00', 'Graduation Ceremony', 100, 'confirmed', '2025-05-19 06:01:03', '2025-05-19 06:01:43', '{\"organization\": \"YUPP\", \"contact_person\": \"YUP\", \"contact_number\": \"09814354708\", \"admin_remarks\": \"\"}'),
(11, 'GYM-2025-011', 13, 'gym', '0000-00-00', '10:30:00', '15:30:00', 'Sports Tournament', 100, 'confirmed', '2025-05-19 07:22:11', '2025-05-19 07:23:21', '{\"organization\": \"YUPP\", \"contact_person\": \"YUPP\", \"contact_number\": \"09105772609\", \"admin_remarks\": \"\"}'),
(12, 'GYM-2025-012', 13, 'gym', '2025-05-21', '04:30:00', '17:30:00', 'Conference', 100, 'confirmed', '2025-05-19 14:17:22', '2025-05-19 14:17:53', '{\"organization\": \"POOO\", \"contact_person\": \"POOO\", \"contact_number\": \"09106934023\", \"admin_remarks\": \"\"}'),
(13, 'GYM-2025-013', 13, 'gym', '2025-05-22', '06:00:00', '15:00:00', 'Sports Tournament', 100, 'confirmed', '2025-05-20 14:30:59', '2025-05-20 14:32:08', '{\"organization\": \"Non\", \"contact_person\": \"Non\", \"contact_number\": \"09969097300\", \"admin_remarks\": \"\"}'),
(14, 'GYM-2025-014', 8, 'gym', '2025-09-10', '08:00:00', '11:00:00', 'Sports Tournament', 50, 'pending', '2025-09-10 07:28:35', '2025-09-10 07:28:35', '{\"organization\":\"CHMSU FORTUNE\",\"contact_person\":\"JONALD\",\"contact_number\":\"09969097300\"}'),
(15, 'GYM-2025-015', 8, 'gym', '2025-09-16', '08:00:00', '10:30:00', 'Graduation Ceremony', 100, 'pending', '2025-09-16 12:17:39', '2025-09-16 12:17:39', '{\"organization\":\"Notre\",\"contact_person\":\"Joemarie D. Damasing\",\"contact_number\":\"09814354707\"}'),
(16, 'GYM-2025-016', 8, 'gym', '2025-09-17', '08:00:00', '10:00:00', 'Sports Tournament', 50, 'confirmed', '2025-09-16 12:21:01', '2025-09-16 12:42:09', '{\"organization\": \"UP\", \"contact_person\": \"Jonard\", \"contact_number\": \"09969097300\", \"admin_remarks\": \"Approve\"}'),
(17, 'GYM-2025-017', 8, 'gym', '2025-09-18', '08:00:00', '10:00:00', 'Conference', 20, 'pending', '2025-09-16 12:27:26', '2025-09-16 12:27:26', '{\"organization\":\"USS\",\"contact_person\":\"QWQW\",\"contact_number\":\"09106934023\"}'),
(18, 'GYM-2025-018', 8, 'gym', '2025-09-20', '08:00:00', '10:00:00', 'School Program', 100, 'pending', '2025-09-20 12:28:39', '2025-09-20 12:28:39', '{\"organization\":\"TUP\",\"contact_person\":\"tatatatat\",\"contact_number\":\"09106934023\"}'),
(19, 'GYM-2025-019', 9, 'gym', '2025-09-21', '08:00:00', '18:00:00', 'Graduation Ceremony', 100, 'pending', '2025-09-20 12:46:14', '2025-09-20 12:46:14', '{\"organization\":\"BGC\",\"contact_person\":\"KEW\",\"contact_number\":\"09814354707\"}'),
(20, 'GYM-2025-020', 9, 'gym', '2025-09-22', '08:00:00', '10:00:00', 'Cultural Event', 50, 'pending', '2025-09-20 13:02:45', '2025-09-20 13:02:45', '{\"organization\":\"II\",\"contact_person\":\"WW\",\"contact_number\":\"09814354707\"}'),
(21, 'GYM-2025-021', 9, 'gym', '2025-09-22', '13:00:00', '15:00:00', 'Sports Tournament', 30, 'pending', '2025-09-20 13:05:55', '2025-09-20 13:05:55', '{\"organization\":\"UU\",\"contact_person\":\"Joemarie D. Damasing\",\"contact_number\":\"09814354707\"}'),
(22, 'GYM-2025-022', 8, 'gym', '2025-09-20', '13:00:00', '15:00:00', 'Graduation Ceremony', 4, 'pending', '2025-09-20 13:15:30', '2025-09-20 13:15:30', '{\"organization\":\"TUP\",\"contact_person\":\"Joemarie D. Damasing\",\"contact_number\":\"09814354707\"}'),
(23, 'GYM-2025-023', 8, 'gym', '2025-09-23', '08:00:00', '18:00:00', 'Graduation Ceremony', 222, 'pending', '2025-09-20 13:18:56', '2025-09-20 13:18:56', '{\"organization\":\"UAAP\",\"contact_person\":\"Joemarie Dayon Damasing\",\"contact_number\":\"09105772609\"}'),
(24, 'GYM-2025-024', 8, 'gym', '2025-10-06', '08:00:00', '18:00:00', 'Graduation Ceremony', 100, 'pending', '2025-10-06 09:32:37', '2025-10-06 09:32:37', '{\"organization\":\"TUP\",\"contact_person\":\"toe\",\"contact_number\":\"09106934023\"}'),
(25, 'GYM-2025-025', 8, 'gym', '2025-10-07', '08:00:00', '10:00:00', 'Cultural Event', 20, 'pending', '2025-10-06 09:34:56', '2025-10-06 09:34:56', '{\"organization\":\"WWP\",\"contact_person\":\"Joemarie D. Damasing\",\"contact_number\":\"09814354707\"}'),
(26, 'GYM-2025-026', 8, 'gym', '2025-10-07', '13:00:00', '15:00:00', 'Sports Tournament', 100, 'pending', '2025-10-06 09:37:50', '2025-10-06 09:37:50', '{\"organization\":\"WQWQ\",\"contact_person\":\"Joemarie D. Damasing\",\"contact_number\":\"09814354707\"}'),
(27, 'GYM-2025-027', 8, 'gym', '2025-10-08', '08:00:00', '12:00:00', 'Graduation Ceremony', 10, 'pending', '2025-10-06 10:07:28', '2025-10-06 10:07:28', '{\"organization\":\"notre\",\"contact_person\":\"eee\",\"contact_number\":\"09969097300\"}'),
(28, 'GYM-2025-028', 8, 'gym', '2025-10-08', '13:00:00', '15:00:00', 'Graduation Ceremony', 70, 'pending', '2025-10-06 10:08:43', '2025-10-06 10:08:43', '{\"organization\":\"UAAP\",\"contact_person\":\"Joemarie Dayon Damasing\",\"contact_number\":\"09105772609\"}'),
(29, 'GYM-2025-029', 8, 'gym', '2025-10-08', '17:00:00', '18:00:00', 'Graduation Ceremony', 100, 'pending', '2025-10-06 10:09:43', '2025-10-06 10:09:43', '{\"organization\":\"UP\",\"contact_person\":\"Joemarie Dayon Damasing\",\"contact_number\":\"09105772609\"}'),
(30, 'GYM-2025-030', 7, 'gym', '2025-10-25', '08:00:00', '18:00:00', 'Graduation Ceremony', 100, 'confirmed', '2025-10-24 17:10:03', '2025-10-24 18:04:30', '{\"organization\": \"TUP\", \"contact_person\": \"Joemarie D. Damasing\", \"contact_number\": \"09814354707\", \"admin_remarks\": \"\"}'),
(31, 'GYM-2025-031', 7, 'gym', '2025-10-26', '08:30:00', '10:30:00', 'Sports Tournament', 50, 'confirmed', '2025-10-24 17:11:25', '2025-10-24 18:27:37', '{\"organization\": \"LA\", \"contact_person\": \"Joemarie\", \"contact_number\": \"09814354707\", \"admin_remarks\": \"Can you come for your time\"}'),
(32, 'GYM-2025-032', 7, 'gym', '2025-10-26', '10:30:00', '11:30:00', 'Conference', 200, 'rejected', '2025-10-24 17:27:55', '2025-10-24 18:30:19', '{\"organization\": \"CHEEE\", \"contact_person\": \"Joemarie Dayon Damasing\", \"contact_number\": \"09105772609\", \"admin_remarks\": \"Basta\"}'),
(33, 'GYM-2025-033', 7, 'gym', '2025-10-26', '13:00:00', '18:00:00', 'Conference', 333, 'pending', '2025-10-24 17:52:40', '2025-10-24 17:52:40', '{\"organization\":\"UAAP\",\"contact_person\":\"Joemarie\",\"contact_number\":\"09814354707\"}'),
(34, 'GYM-2025-034', 7, 'gym', '2025-10-27', '08:00:00', '10:30:00', 'Sports Tournament', 111, 'pending', '2025-10-24 17:55:34', '2025-10-24 17:55:34', '{\"organization\":\"notre\",\"contact_person\":\"Joemarie\",\"contact_number\":\"09814354707\"}'),
(35, 'GYM-2025-035', 7, 'gym', '2025-10-27', '10:30:00', '11:30:00', 'School Program', 122, 'pending', '2025-10-24 17:58:12', '2025-10-24 17:58:12', '{\"organization\":\"UP\",\"contact_person\":\"TERESITA SAYON DAYON\",\"contact_number\":\"09106934023\"}'),
(36, 'GYM-2025-036', 7, 'gym', '2025-10-27', '13:00:00', '14:00:00', 'School Program', 121, 'pending', '2025-10-24 17:59:13', '2025-10-24 17:59:13', '{\"organization\":\"LA\",\"contact_person\":\"Joemarie Dayon Damasing\",\"contact_number\":\"09105772609\"}'),
(37, 'GYM-2025-037', 7, 'gym', '2025-10-27', '14:00:00', '15:00:00', 'Graduation Ceremony', 20, 'pending', '2025-10-24 18:00:04', '2025-10-24 18:00:04', '{\"organization\":\"UAAP\",\"contact_person\":\"Joemarie\",\"contact_number\":\"09814354707\"}'),
(38, 'GYM-2025-038', 7, 'gym', '2025-10-27', '15:00:00', '16:00:00', 'School Program', 66, 'pending', '2025-10-24 18:02:39', '2025-10-24 18:02:39', '{\"organization\":\"LA\",\"contact_person\":\"Joemarie Dayon Damasing\",\"contact_number\":\"09105772609\"}'),
(39, 'GYM-2025-039', 7, 'gym', '2025-10-27', '16:00:00', '18:00:00', 'Sports Tournament', 100, 'pending', '2025-10-24 18:03:22', '2025-10-24 18:03:22', '{\"organization\":\"II\",\"contact_person\":\"Joemarie\",\"contact_number\":\"09814354707\"}');

-- --------------------------------------------------------

--
-- Table structure for table `buses`
--

CREATE TABLE `buses` (
  `id` int(11) NOT NULL,
  `bus_number` varchar(20) NOT NULL,
  `vehicle_type` varchar(50) NOT NULL,
  `capacity` int(11) NOT NULL,
  `status` enum('available','booked','maintenance','out_of_service') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buses`
--

INSERT INTO `buses` (`id`, `bus_number`, `vehicle_type`, `capacity`, `status`, `created_at`, `updated_at`) VALUES
(1, '1', 'Bus', 50, 'booked', '2025-10-24 16:47:38', '2025-10-24 16:56:36'),
(2, '2', 'Bus', 50, 'booked', '2025-10-24 16:47:38', '2025-10-26 06:56:13'),
(3, '3', 'Bus', 50, 'available', '2025-10-24 16:47:38', '2025-10-24 16:47:38');

-- --------------------------------------------------------

--
-- Table structure for table `bus_bookings`
--

CREATE TABLE `bus_bookings` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `bus_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `status` enum('active','cancelled','completed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bus_bookings`
--

INSERT INTO `bus_bookings` (`id`, `schedule_id`, `bus_id`, `booking_date`, `status`, `created_at`) VALUES
(1, 14, 2, '2025-10-25', 'active', '2025-10-24 16:48:06'),
(2, 15, 3, '2025-10-25', 'active', '2025-10-24 16:49:29'),
(3, 14, 1, '2025-10-25', 'active', '2025-10-24 16:56:36'),
(4, 16, 1, '2025-10-26', 'active', '2025-10-26 06:55:18'),
(5, 15, 2, '2025-10-25', 'active', '2025-10-26 06:56:13');

-- --------------------------------------------------------

--
-- Table structure for table `bus_schedules`
--

CREATE TABLE `bus_schedules` (
  `id` int(11) NOT NULL,
  `client` varchar(100) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `date_covered` date NOT NULL,
  `vehicle` varchar(100) NOT NULL,
  `bus_no` varchar(20) NOT NULL,
  `no_of_days` int(11) NOT NULL,
  `no_of_vehicles` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('student','admin','staff','external') DEFAULT 'student',
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bus_schedules`
--

INSERT INTO `bus_schedules` (`id`, `client`, `destination`, `purpose`, `date_covered`, `vehicle`, `bus_no`, `no_of_days`, `no_of_vehicles`, `user_id`, `user_type`, `status`, `created_at`, `updated_at`) VALUES
(1, 'OSAS', 'Binalbagan', 'Bayanihan', '2025-05-13', 'Bus', '1', 1, 3, NULL, 'student', 'rejected', '2025-05-13 15:16:23', '2025-10-24 16:30:02'),
(14, 'CHMSU - Central Philippine State University - OSAS', 'CHMSU - Carlos Hilado Memorial State University, Talisay City - La Salle Bacolod', 'Bayanihan', '2025-10-25', 'Bus', '2', 1, 1, 5, 'student', 'approved', '0000-00-00 00:00:00', '2025-10-24 16:56:36'),
(15, 'CHMSU - Central Philippine State University - OSAS', 'CHMSU - Carlos Hilado Memorial State University, Talisay City - Silay Airport', 'Grad', '2025-10-25', 'Bus', '3', 1, 1, 5, 'student', 'approved', '0000-00-00 00:00:00', '2025-10-26 06:56:13'),
(16, 'CHMSU - Central Philippine State University - OSAS', 'CHMSU - Carlos Hilado Memorial State University, Talisay City - Barangay 1, Bacolod City', 'Bayanihan', '2025-10-26', 'Bus', '1', 1, 1, 8, 'student', 'pending', '0000-00-00 00:00:00', '2025-10-26 06:55:18');

-- --------------------------------------------------------

--
-- Table structure for table `facilities`
--

CREATE TABLE `facilities` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `type` enum('gym','other') DEFAULT 'other',
  `status` enum('active','inactive','maintenance') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facilities`
--

INSERT INTO `facilities` (`id`, `name`, `description`, `capacity`, `type`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Gymnasium', 'Main gymnasium for sports and events', 500, 'gym', 'active', '2025-05-18 05:35:12', '2025-05-18 05:35:12'),
(2, 'Swimming Pool', 'Olympic-sized swimming pool', 100, 'gym', 'active', '2025-05-18 05:35:12', '2025-05-18 05:35:12'),
(3, 'Tennis Court', 'Outdoor tennis court', 4, 'gym', 'active', '2025-05-18 05:35:12', '2025-05-18 05:35:12'),
(4, 'Basketball Court', 'Indoor basketball court', 50, 'gym', 'active', '2025-05-18 05:35:12', '2025-05-18 05:35:12'),
(5, 'Conference Room', 'Meeting and conference room', 30, 'other', 'active', '2025-05-18 05:35:12', '2025-05-18 05:35:12'),
(6, 'Gymnasium', 'Main gymnasium for sports and events', 500, 'gym', 'active', '2025-05-18 05:43:53', '2025-05-18 05:43:53'),
(7, 'Swimming Pool', 'Olympic-sized swimming pool', 100, 'gym', 'active', '2025-05-18 05:43:53', '2025-05-18 05:43:53'),
(8, 'Tennis Court', 'Outdoor tennis court', 4, 'gym', 'active', '2025-05-18 05:43:53', '2025-05-18 05:43:53'),
(9, 'Basketball Court', 'Indoor basketball court', 50, 'gym', 'active', '2025-05-18 05:43:53', '2025-05-18 05:43:53'),
(10, 'Conference Room', 'Meeting and conference room', 30, 'other', 'active', '2025-05-18 05:43:53', '2025-05-18 05:43:53'),
(11, 'Gymnasium', 'Main gymnasium for sports and events', 500, 'gym', 'active', '2025-05-18 05:43:57', '2025-05-18 05:43:57'),
(12, 'Swimming Pool', 'Olympic-sized swimming pool', 100, 'gym', 'active', '2025-05-18 05:43:57', '2025-05-18 05:43:57'),
(13, 'Tennis Court', 'Outdoor tennis court', 4, 'gym', 'active', '2025-05-18 05:43:57', '2025-05-18 05:43:57'),
(14, 'Basketball Court', 'Indoor basketball court', 50, 'gym', 'active', '2025-05-18 05:43:57', '2025-05-18 05:43:57'),
(15, 'Conference Room', 'Meeting and conference room', 30, 'other', 'active', '2025-05-18 05:43:57', '2025-05-18 05:43:57'),
(16, 'Gymnasium', 'Main gymnasium for sports and events', 500, 'gym', 'active', '2025-05-18 05:44:15', '2025-05-18 05:44:15'),
(17, 'Swimming Pool', 'Olympic-sized swimming pool', 100, 'gym', 'active', '2025-05-18 05:44:15', '2025-05-18 05:44:15'),
(18, 'Tennis Court', 'Outdoor tennis court', 4, 'gym', 'active', '2025-05-18 05:44:15', '2025-05-18 05:44:15'),
(19, 'Basketball Court', 'Indoor basketball court', 50, 'gym', 'active', '2025-05-18 05:44:15', '2025-05-18 05:44:15'),
(20, 'Conference Room', 'Meeting and conference room', 30, 'other', 'active', '2025-05-18 05:44:15', '2025-05-18 05:44:15'),
(21, 'Gymnasium', 'Main gymnasium for sports and events', 500, 'gym', 'active', '2025-05-18 05:49:32', '2025-05-18 05:49:32'),
(22, 'Swimming Pool', 'Olympic-sized swimming pool', 100, 'gym', 'active', '2025-05-18 05:49:32', '2025-05-18 05:49:32'),
(23, 'Tennis Court', 'Outdoor tennis court', 4, 'gym', 'active', '2025-05-18 05:49:32', '2025-05-18 05:49:32'),
(24, 'Basketball Court', 'Indoor basketball court', 50, 'gym', 'active', '2025-05-18 05:49:32', '2025-05-18 05:49:32'),
(25, 'Conference Room', 'Meeting and conference room', 30, 'other', 'active', '2025-05-18 05:49:32', '2025-05-18 05:49:32'),
(26, 'Gymnasium', 'Main gymnasium for sports and events', 500, 'gym', 'active', '2025-05-18 05:49:52', '2025-05-18 05:49:52'),
(27, 'Swimming Pool', 'Olympic-sized swimming pool', 100, 'gym', 'active', '2025-05-18 05:49:52', '2025-05-18 05:49:52'),
(28, 'Tennis Court', 'Outdoor tennis court', 4, 'gym', 'active', '2025-05-18 05:49:52', '2025-05-18 05:49:52'),
(29, 'Basketball Court', 'Indoor basketball court', 50, 'gym', 'active', '2025-05-18 05:49:52', '2025-05-18 05:49:52'),
(30, 'Conference Room', 'Meeting and conference room', 30, 'other', 'active', '2025-05-18 05:49:52', '2025-05-18 05:49:52');

-- --------------------------------------------------------

--
-- Table structure for table `gym_bookings`
--

CREATE TABLE `gym_bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `facility_id` int(11) NOT NULL,
  `facility` varchar(50) NOT NULL,
  `booking_date` date NOT NULL,
  `time_slot` varchar(20) NOT NULL,
  `purpose` text NOT NULL,
  `participants` int(11) NOT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `admin_remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gym_facilities`
--

CREATE TABLE `gym_facilities` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive','maintenance') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gym_facilities`
--

INSERT INTO `gym_facilities` (`id`, `name`, `capacity`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Main Gymnasium', 500, 'Main indoor gymnasium for basketball, volleyball, and other indoor sports', 'active', '2025-05-06 22:33:13', NULL),
(2, 'Fitness Center', 50, 'Fitness center with cardio and weight training equipment', 'active', '2025-05-06 22:33:13', NULL),
(3, 'Swimming Pool', 100, 'Olympic-sized swimming pool', 'active', '2025-05-06 22:33:13', NULL),
(4, 'Tennis Court', 20, 'Outdoor tennis court', 'active', '2025-05-06 22:33:13', NULL),
(5, 'Badminton Court', 30, 'Indoor badminton court', 'active', '2025-05-06 22:33:13', NULL),
(7, 'Basketball', 10, 'Gymm', 'active', '2025-05-20 20:49:55', '2025-05-20 20:50:10'),
(8, 'Dance', 100, 'DANCEE', 'active', '2025-10-25 02:37:34', '2025-10-25 02:37:50');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `in_stock` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image_path` varchar(255) DEFAULT NULL,
  `sizes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `name`, `description`, `price`, `quantity`, `in_stock`, `created_at`, `updated_at`, `image_path`, `sizes`) VALUES
(1, 'P.E T-Shirt', 'Official CHMSU Physical Education uniform', 150.00, 45, 1, '2025-04-07 15:13:40', '2025-10-24 15:30:48', 'uploads/inventory/1761319848_P.E_TSHIRT-removebg-preview.png', NULL),
(3, 'NSTP Shirt - ROTC', 'NSTP Shirt - ROTC', 120.00, 193, 1, '2025-04-07 15:13:40', '2025-10-24 15:48:22', 'uploads/inventory/1761319817_NSTP-ROTC_TSHIRT-removebg-preview.png', '[]'),
(4, 'Faculty - ID - Cord', 'Faculty - ID - Cord', 90.00, 136, 1, '2025-04-07 15:13:40', '2025-10-24 15:52:48', 'uploads/inventory/1761319666_FACULTU_ID_CORD-removebg-preview.png', NULL),
(5, 'NSTP Shirt - CWTS', 'NSTP Shirt - CWTS', 150.00, 73, 1, '2025-04-07 15:13:40', '2025-10-24 15:29:34', 'uploads/inventory/1761319774_NSTP-CWTS_TSHIRT-removebg-preview.png', '[\"XS\",\"S\",\"M\",\"L\",\"XL\",\"2XL\",\"3XL\"]'),
(6, 'ROTC - CAP', 'ROTC - CAP', 80.00, 18, 1, '2025-04-07 15:13:40', '2025-10-24 15:31:04', 'uploads/inventory/1761319864_ROTC_CAP-removebg-preview.png', NULL),
(7, 'Logo', 'CHMSU - Logo', 80.00, 19, 1, '2025-04-08 15:54:14', '2025-10-24 15:55:38', 'uploads/inventory/1761319713_CHMSU_LOGO-removebg-preview.png', NULL),
(8, 'P.E - Pants', 'Official CHMSU Physical Education uniform', 150.00, 20, 1, '2025-05-04 15:55:56', '2025-10-24 15:30:35', 'uploads/inventory/1761319835_photo_6150139696538305965_y-removebg-preview.png', NULL),
(11, 'NSTP Shirt - LTS', 'NSTP Shirt - LTS', 150.00, 49, 1, '2025-05-19 13:12:19', '2025-10-24 15:30:03', 'uploads/inventory/1761319803_NSTP-LTS_TSHIRT-removebg-preview.png', '[\"XS\",\"S\",\"M\",\"L\",\"XL\",\"2XL\",\"3XL\"]'),
(12, 'BSIT OJT - Shirt', 'BSIT OJT - Shirt', 150.00, 21, 1, '2025-05-19 13:13:33', '2025-10-26 06:45:21', 'uploads/inventory/1761319996_BSIT-OJT_TSHIRT-removebg-preview.png', '[\"XS\",\"XL\"]'),
(19, 'ID CORD', 'ID CORD', 80.00, 50, 1, '2025-10-24 15:23:36', '2025-10-24 15:23:36', 'uploads/inventory/1761319416_ID_CORD-removebg-preview.png', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `item_id` int(11) NOT NULL,
  `order_type` varchar(100) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `priority` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_id`, `user_id`, `inventory_id`, `quantity`, `total_price`, `status`, `created_at`, `updated_at`, `total_amount`, `item_id`, `order_type`, `purpose`, `priority`) VALUES
(1, 'ORD-2025-310', 4, 7, 1, 80.00, 'approved', '2025-04-08 15:54:47', '2025-04-16 03:23:21', 0.00, 0, NULL, NULL, NULL),
(2, 'ORD-2025-056', 4, 7, 2, 160.00, 'approved', '2025-04-09 08:24:09', '2025-04-16 03:23:21', 0.00, 0, NULL, NULL, NULL),
(3, 'ORD-2025-769', 4, 2, 1, 350.00, 'approved', '2025-04-15 07:17:48', '2025-04-16 03:21:08', 0.00, 0, NULL, NULL, NULL),
(4, 'ORD-2025-750', 4, 1, 4, 2200.00, 'approved', '2025-04-15 08:55:39', '2025-04-16 03:21:08', 0.00, 0, NULL, NULL, NULL),
(5, 'ORD-2025-089', 4, 4, 1, 80.00, 'approved', '2025-04-16 03:06:36', '2025-04-16 03:21:08', 0.00, 0, NULL, NULL, NULL),
(6, 'ORD-2025-477', 4, 4, 3, 240.00, 'approved', '2025-04-16 03:20:10', '2025-04-16 03:21:08', 0.00, 0, NULL, NULL, NULL),
(7, 'ORD-2025-283', 9, 4, 2, 160.00, 'approved', '2025-04-16 03:27:59', '2025-04-16 03:28:57', 0.00, 0, NULL, NULL, NULL),
(8, 'ORD-2025-670', 4, 4, 1, 80.00, 'approved', '2025-04-16 16:07:54', '2025-04-17 14:55:49', 0.00, 0, NULL, NULL, NULL),
(9, 'ORD-2025-814', 4, 2, 2, 700.00, 'approved', '2025-04-16 16:23:41', '2025-04-17 14:55:49', 0.00, 0, NULL, NULL, NULL),
(10, 'ORD-2025-740', 4, 4, 1, 80.00, 'approved', '2025-04-16 16:25:01', '2025-04-17 14:55:49', 0.00, 0, NULL, NULL, NULL),
(11, 'ORD-2025-734', 4, 4, 1, 80.00, 'approved', '2025-04-16 16:29:13', '2025-04-17 14:55:49', 0.00, 0, NULL, NULL, NULL),
(12, 'ORD-2025-989', 4, 2, 3, 1050.00, 'approved', '2025-04-17 14:43:41', '2025-04-17 14:55:49', 0.00, 0, NULL, NULL, NULL),
(13, 'ORD-2025-461', 4, 2, 1, 350.00, 'approved', '2025-04-17 14:46:23', '2025-04-17 14:55:49', 0.00, 0, NULL, NULL, NULL),
(14, 'ORD-2025-084', 4, 2, 1, 350.00, 'approved', '2025-04-25 07:16:16', '2025-05-03 23:33:10', 0.00, 0, NULL, NULL, NULL),
(15, 'ORD-2025-751', 4, 7, 2, 160.00, 'approved', '2025-05-03 23:40:11', '2025-05-04 14:59:59', 0.00, 0, NULL, NULL, NULL),
(16, 'ORD-2025-276', 4, 4, 1, 80.00, 'approved', '2025-05-04 15:18:21', '2025-05-04 15:18:33', 0.00, 0, NULL, NULL, NULL),
(17, 'ORD-2025-582', 4, 2, 1, 350.00, 'rejected', '2025-05-04 15:18:57', '2025-05-04 15:20:03', 0.00, 0, NULL, NULL, NULL),
(18, 'ORD-2025-947', 4, 2, 1, 350.00, 'approved', '2025-05-04 15:33:31', '2025-05-04 15:33:43', 0.00, 0, NULL, NULL, NULL),
(19, 'ORD-2025-797', 4, 8, 1, 150.00, 'completed', '2025-05-04 15:56:30', '2025-05-06 01:24:28', 0.00, 0, NULL, NULL, NULL),
(20, 'ORD-2025-436', 4, 4, 1, 80.00, 'approved', '2025-05-07 15:21:59', '2025-05-08 02:14:25', 0.00, 0, NULL, NULL, NULL),
(21, 'ORD-2025-138', 4, 2, 2, 700.00, 'approved', '2025-05-11 06:25:23', '2025-05-11 07:36:19', 0.00, 0, NULL, NULL, NULL),
(22, 'ORD-2025-806', 4, 3, 2, 240.00, 'approved', '2025-05-11 06:54:17', '2025-05-11 07:36:19', 0.00, 0, NULL, NULL, NULL),
(23, 'ORD-2025-060', 4, 3, 2, 240.00, 'approved', '2025-05-11 06:54:44', '2025-05-11 07:36:19', 0.00, 0, NULL, NULL, NULL),
(24, 'ORD-2025-993', 4, 3, 2, 240.00, 'completed', '2025-05-11 06:56:31', '2025-05-18 06:01:53', 0.00, 0, NULL, NULL, NULL),
(25, 'ORD-2025-917', 4, 8, 2, 300.00, 'completed', '2025-05-11 07:41:17', '2025-05-12 15:59:41', 0.00, 0, NULL, NULL, NULL),
(26, 'ORD-2025-299', 4, 5, 1, 100.00, 'completed', '2025-05-11 07:47:13', '2025-05-12 15:59:37', 0.00, 0, NULL, NULL, NULL),
(27, 'ORD-2025-414', 2, 3, 1, 120.00, 'completed', '2025-05-11 07:52:05', '2025-05-12 15:58:55', 0.00, 0, NULL, NULL, NULL),
(28, 'ORD-2025-451', 2, 8, 3, 450.00, 'completed', '2025-05-11 08:43:11', '2025-05-11 08:45:05', 0.00, 0, NULL, NULL, NULL),
(29, 'ORD-2025-195', 2, 7, 1, 80.00, 'completed', '2025-05-11 10:07:39', '2025-05-12 16:17:07', 0.00, 0, NULL, NULL, NULL),
(30, 'ORD-2025-643', 2, 4, 1, 80.00, 'completed', '2025-05-12 16:11:36', '2025-05-12 16:13:47', 0.00, 0, NULL, NULL, NULL),
(31, 'ORD-2025-069', 4, 4, 2, 160.00, 'completed', '2025-05-12 19:17:02', '2025-05-12 19:21:24', 0.00, 0, NULL, NULL, NULL),
(32, 'ORD-2025-269', 4, 2, 1, 350.00, 'approved', '2025-05-14 07:48:16', '2025-05-14 16:09:50', 0.00, 0, NULL, NULL, NULL),
(33, 'ORD-2025-544', 4, 4, 1, 80.00, 'approved', '2025-05-14 09:06:30', '2025-05-14 16:09:50', 0.00, 0, NULL, NULL, NULL),
(34, 'ORD-2025-144', 4, 2, 1, 350.00, 'approved', '2025-05-14 13:58:18', '2025-05-14 16:09:50', 0.00, 0, NULL, NULL, NULL),
(35, 'ORD-2025-871', 4, 2, 1, 350.00, 'approved', '2025-05-14 14:44:06', '2025-05-14 16:09:50', 0.00, 0, NULL, NULL, NULL),
(36, 'ORD-2025-748', 4, 4, 1, 80.00, 'approved', '2025-05-14 16:10:39', '2025-05-14 16:11:31', 0.00, 0, NULL, NULL, NULL),
(37, 'ORD-2025-524', 4, 4, 1, 80.00, 'approved', '2025-05-14 16:11:04', '2025-05-14 16:11:31', 0.00, 0, NULL, NULL, NULL),
(38, 'ORD-2025-101', 4, 4, 2, 160.00, 'approved', '2025-05-14 16:12:30', '2025-05-14 16:13:04', 0.00, 0, NULL, NULL, NULL),
(39, 'ORD-2025-420', 4, 2, 1, 350.00, 'approved', '2025-05-14 16:16:10', '2025-05-14 16:16:26', 0.00, 0, NULL, NULL, NULL),
(42, 'ORD-2025-093', 4, 4, 2, 160.00, 'approved', '2025-05-14 16:17:29', '2025-05-14 16:17:45', 0.00, 0, NULL, NULL, NULL),
(44, 'ORD-2025-533', 4, 2, 2, 700.00, 'approved', '2025-05-14 16:21:27', '2025-05-18 13:46:58', 0.00, 0, NULL, NULL, NULL),
(45, 'ORD-2025-506', 4, 4, 2, 160.00, 'approved', '2025-05-14 16:22:11', '2025-05-18 14:09:05', 0.00, 0, NULL, NULL, NULL),
(46, 'ORD-2025-204', 23, 7, 3, 240.00, 'completed', '2025-05-18 14:27:27', '2025-05-18 14:42:55', 0.00, 0, NULL, NULL, NULL),
(47, 'ORD-2025-810', 23, 7, 2, 160.00, 'completed', '2025-05-18 14:49:11', '2025-05-18 14:50:08', 0.00, 0, NULL, NULL, NULL),
(48, 'ORD-2025-424', 23, 2, 2, 700.00, 'approved', '2025-05-19 06:42:48', '2025-05-19 06:43:07', 0.00, 0, NULL, NULL, NULL),
(49, 'ORD-2025-602', 4, 2, 1, 350.00, 'approved', '2025-05-19 07:14:38', '2025-05-19 13:26:28', 0.00, 0, NULL, NULL, NULL),
(50, 'ORD-2025-411', 4, 12, 1, 150.00, 'approved', '2025-05-19 13:25:57', '2025-05-19 13:26:28', 0.00, 0, NULL, NULL, NULL),
(52, 'ORD-2025-786', 4, 12, 2, 300.00, 'approved', '2025-05-19 13:57:46', '2025-05-19 13:58:00', 0.00, 0, NULL, NULL, NULL),
(53, 'ORD-2025-520', 4, 5, 1, 150.00, 'approved', '2025-05-19 14:01:53', '2025-05-19 14:02:06', 0.00, 0, NULL, NULL, NULL),
(54, 'ORD-2025-287', 4, 12, 3, 450.00, 'completed', '2025-05-19 14:04:48', '2025-05-19 14:05:50', 0.00, 0, NULL, NULL, NULL),
(55, 'ORD-2025-640', 25, 12, 1, 150.00, 'approved', '2025-05-20 00:29:15', '2025-05-20 00:30:09', 0.00, 0, NULL, NULL, NULL),
(56, 'ORD-2025-258', 4, 6, 1, 80.00, 'completed', '2025-05-20 11:34:58', '2025-05-20 12:05:22', 0.00, 0, NULL, NULL, NULL),
(57, 'ORD-2025-225', 4, 6, 5, 400.00, 'approved', '2025-05-20 14:19:06', '2025-05-20 14:21:37', 0.00, 0, NULL, NULL, NULL),
(58, 'ORD-2025-332', 4, 17, 1, 380.00, 'approved', '2025-05-21 02:41:51', '2025-05-21 02:42:09', 0.00, 0, NULL, NULL, NULL),
(59, 'ORD-2025-245', 4, 17, 1, 380.00, 'approved', '2025-05-21 02:50:04', '2025-05-21 02:50:43', 0.00, 0, NULL, NULL, NULL),
(60, 'ORD-2025-631', 4, 12, 1, 150.00, 'pending', '2025-05-21 02:53:52', '2025-05-21 02:53:52', 0.00, 0, NULL, NULL, NULL),
(61, 'ORD-2025-166', 27, 12, 1, 150.00, 'pending', '2025-05-28 14:21:54', '2025-05-28 14:21:54', 0.00, 0, NULL, NULL, NULL),
(62, 'ORD-2025-481', 1, 11, 1, 150.00, 'pending', '2025-05-29 05:21:37', '2025-05-29 05:21:37', 0.00, 0, NULL, NULL, NULL),
(63, 'ORD-2025-092', 7, 6, 1, 80.00, 'completed', '2025-09-09 04:09:25', '2025-09-13 05:53:17', 0.00, 0, NULL, NULL, NULL),
(64, 'ORD-2025-376', 7, 6, 1, 80.00, 'approved', '2025-09-13 05:49:59', '2025-09-13 05:59:17', 0.00, 0, NULL, NULL, NULL),
(65, 'ORD-2025-313', 7, 18, 2, 200.00, 'approved', '2025-09-13 05:58:33', '2025-09-13 05:59:17', 0.00, 0, NULL, NULL, NULL),
(66, 'ORD-2025-730', 7, 18, 1, 100.00, 'pending', '2025-09-16 03:36:54', '2025-09-16 03:36:54', 0.00, 0, NULL, NULL, NULL),
(69, 'ORD-20251024-175248-', 5, 4, 1, 90.00, 'approved', '2025-10-24 15:52:48', '2025-10-24 15:53:02', 0.00, 0, NULL, NULL, NULL),
(70, 'ORD-20251024-175324-', 5, 12, 1, 150.00, 'approved', '2025-10-24 15:53:24', '2025-10-24 15:53:57', 0.00, 0, NULL, NULL, NULL),
(71, 'ORD-20251024-175538-', 5, 7, 1, 80.00, 'approved', '2025-10-24 15:55:38', '2025-10-24 15:56:26', 0.00, 0, NULL, NULL, NULL),
(72, 'ORD-20251026-074521-', 8, 12, 1, 150.00, 'approved', '2025-10-26 06:45:21', '2025-10-26 06:46:12', 0.00, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `created_at`, `expires_at`) VALUES
(2, 'newadmin@chmsu.edu.ph', '5e0d92ca8b13423b706fb65d1655372081aa2f5e2d0337fc492207ba852ec2df', '2025-04-15 08:43:25', '2025-04-15 03:43:25'),
(3, 'admin@chmsu.edu.ph', 'f55b1ece2dbdc8e89fa4612863e4dce5723227176da2aeb0dc2080a22b5f00ea', '2025-04-25 05:26:17', '2025-04-25 00:26:17'),
(6, 'sabordojonald@gmail.com', 'd75b77062b18e0ac1301ded765b071ee9d215f55d259f33c2bfeb6cd8704867a', '2025-09-10 07:00:28', '2025-09-10 02:00:28');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `request_id` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `details` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `request_id`, `user_id`, `type`, `details`, `status`, `created_at`, `updated_at`) VALUES
(1, 'REQ-2025-204', 4, 'Logo Order', 'Order for 1 Logo', 'approved', '2025-04-08 15:54:47', '2025-04-09 08:15:01'),
(2, 'REQ-2025-858', 4, 'Logo Order', 'Order for 2 Logo', 'approved', '2025-04-09 08:24:09', '2025-04-09 08:25:36'),
(3, 'REQ-2025-266', 4, 'Graduation Cord Order', 'Order for 1 Graduation Cord', 'approved', '2025-04-15 07:17:48', '2025-04-15 09:17:29'),
(4, 'REQ-2025-386', 4, 'PE Uniform Order', 'Order for 4 PE Uniform (Size: S)', 'approved', '2025-04-15 08:55:39', '2025-04-15 09:02:32'),
(5, 'REQ-2025-137', 4, 'Logo Order', 'www', 'approved', '2025-04-16 02:38:50', '2025-04-16 02:40:10'),
(6, 'REQ-2025-843', 4, 'ID Lace Order', 'Order for 1 ID Lace', 'approved', '2025-04-16 03:06:36', '2025-04-16 03:11:29'),
(7, 'REQ-2025-955', 4, 'ID Lace Order', 'please confirm', 'approved', '2025-04-16 03:07:01', '2025-04-16 03:10:30'),
(8, 'REQ-2025-613', 4, 'ID Lace Order', 'Order for 3 ID Lace', 'approved', '2025-04-16 03:20:10', '2025-04-16 03:21:08'),
(9, 'REQ-2025-805', 9, 'ID Lace Order', 'Order for 2 ID Lace', 'approved', '2025-04-16 03:28:00', '2025-04-16 03:28:57'),
(10, 'REQ-2025-089', 4, 'ID Lace Order', 'Order for 1 ID Lace', 'approved', '2025-04-16 16:07:54', '2025-04-16 16:08:41'),
(11, 'REQ-2025-930', 4, 'Graduation Cord Order', 'Order for 2 Graduation Cord', 'approved', '2025-04-16 16:23:41', '2025-04-16 16:24:22'),
(12, 'REQ-2025-543', 4, 'ID Lace Order', 'Order for 1 ID Lace', 'approved', '2025-04-16 16:25:01', '2025-04-17 14:45:43'),
(13, 'REQ-2025-449', 4, 'ID Lace Order', 'Order for 1 ID Lace', 'approved', '2025-04-16 16:29:14', '2025-04-17 14:46:02'),
(14, 'REQ-2025-185', 4, 'Graduation Cord Order', 'Order for 3 Graduation Cord', 'approved', '2025-04-17 14:43:41', '2025-04-17 14:44:18'),
(15, 'REQ-2025-918', 4, 'Graduation Cord Order', 'Order for 1 Graduation Cord', 'approved', '2025-04-17 14:46:23', '2025-04-17 14:46:59'),
(16, 'REQ-2025-835', 4, 'Graduation Cord Order', 'Order for 1 Graduation Cord', 'approved', '2025-04-25 07:16:16', '2025-05-03 23:33:10'),
(17, 'REQ-2025-192', 4, 'Logo Order', 'Order for 2 Logo', 'approved', '2025-05-03 23:40:11', '2025-05-04 14:59:59'),
(18, 'REQ-2025-046', 4, 'ID Lace Order', 'Order for 1 ID Lace', 'approved', '2025-05-04 15:18:21', '2025-05-04 15:18:33'),
(19, 'REQ-2025-007', 4, 'Graduation Cord Order', 'Order for 1 Graduation Cord', 'rejected', '2025-05-04 15:18:57', '2025-05-04 15:20:03'),
(20, 'REQ-2025-726', 4, 'Graduation Cord Order', 'Order for 1 Graduation Cord', 'approved', '2025-05-04 15:33:31', '2025-05-04 15:33:43'),
(21, 'REQ-2025-698', 4, 'ROTC CAP Order', 'Order for 1 ROTC CAP', 'approved', '2025-05-04 15:56:30', '2025-05-04 15:56:42'),
(22, 'REQ-2025-446', 4, 'ID Lace Order', 'Order for 1 ID Lace', 'approved', '2025-05-07 15:21:59', '2025-05-11 08:29:34'),
(23, 'REQ-2025-586', 4, 'Graduation Cord Order', 'Order for 2 Graduation Cord', 'approved', '2025-05-11 06:25:23', '2025-05-11 08:29:26'),
(24, 'REQ-2025-109', 4, 'School Logo Patch Order', 'Order for 2 School Logo Patch', 'approved', '2025-05-11 06:54:17', '2025-05-11 08:29:10'),
(25, 'REQ-2025-253', 4, 'School Logo Patch Order', 'Order for 2 School Logo Patch', 'approved', '2025-05-11 06:54:44', '2025-05-11 07:38:06'),
(26, 'REQ-2025-742', 4, 'School Logo Patch Order', 'Order for 2 School Logo Patch', 'approved', '2025-05-11 06:56:31', '2025-05-11 07:36:19'),
(27, 'REQ-2025-603', 4, 'ROTC CAP Order', 'Order for 2 ROTC CAP', 'approved', '2025-05-11 07:41:17', '2025-05-11 07:44:09'),
(28, 'REQ-2025-224', 4, 'School Pin Order', 'Order for 1 School Pin', 'approved', '2025-05-11 07:47:13', '2025-05-11 07:48:17'),
(29, 'REQ-2025-432', 2, 'School Logo Patch Order', 'Order for 1 School Logo Patch', 'approved', '2025-05-11 07:52:05', '2025-05-11 07:52:40'),
(30, 'REQ-2025-156', 2, 'ROTC CAP Order', 'Order for 3 ROTC CAP', 'approved', '2025-05-11 08:43:11', '2025-05-11 08:43:46'),
(31, 'REQ-2025-600', 2, 'Logo Order', 'Order for 1 Logo', 'approved', '2025-05-11 10:07:39', '2025-05-12 16:16:58'),
(32, 'REQ-2025-070', 2, 'ID Lace Order', 'Order for 1 ID Lace', 'approved', '2025-05-12 16:11:36', '2025-05-12 16:12:07'),
(33, 'REQ-2025-124', 4, 'ID Lace Order', 'Order for 2 ID Lace', 'approved', '2025-05-12 19:17:02', '2025-05-12 19:17:55'),
(34, 'REQ-2025-636', 4, 'Graduation Cord Order', 'Order for 1 Graduation Cord', 'approved', '2025-05-14 07:48:16', '2025-05-14 08:35:19'),
(35, 'REQ-2025-628', 4, 'ID Lace Order', 'Order for 1 ID Lace', 'approved', '2025-05-14 09:06:30', '2025-05-14 16:10:21'),
(36, 'REQ-2025-187', 4, 'Graduation Cord Order', 'Order for 1 Graduation Cord', 'approved', '2025-05-14 13:58:18', '2025-05-14 16:10:07'),
(37, 'REQ-2025-920', 4, 'Graduation Cord Order', 'Order for 1 Graduation Cord', 'approved', '2025-05-14 14:44:06', '2025-05-14 16:09:32'),
(38, 'REQ-2025-494', 4, 'ID Lace Order', 'Order for 1 ID Lace', 'approved', '2025-05-14 16:10:39', '2025-05-14 16:12:18'),
(39, 'REQ-2025-532', 4, 'ID Lace Order', 'Order for 1 ID Lace', 'approved', '2025-05-14 16:11:04', '2025-05-14 16:11:31'),
(40, 'REQ-2025-292', 4, 'ID Lace Order', 'Order for 2 ID Lace', 'approved', '2025-05-14 16:12:30', '2025-05-14 16:13:04'),
(41, 'REQ-2025-958', 4, 'Graduation Cord Order', 'Order for 1 Graduation Cord', 'approved', '2025-05-14 16:16:10', '2025-05-14 16:16:26'),
(43, 'REQ-2025-969', 4, 'ID Lace Order', 'Order for 2 ID Lace', 'approved', '2025-05-14 16:17:29', '2025-05-14 16:17:45'),
(44, 'REQ-2025-812', 4, 'Graduation Cord Order', 'Order for 2 Graduation Cord', 'approved', '2025-05-14 16:21:27', '2025-05-18 14:09:58'),
(45, 'REQ-2025-354', 4, 'ID Lace Order', 'Order for 2 ID Lace', 'approved', '2025-05-14 16:22:11', '2025-05-18 14:09:05'),
(46, 'REQ-2025-317', 23, 'Logo Order', 'Order for 3 Logo', 'approved', '2025-05-18 14:27:27', '2025-05-18 14:27:44'),
(47, 'REQ-2025-453', 23, 'Logo Order', 'Order for 2 Logo', 'approved', '2025-05-18 14:49:11', '2025-05-18 14:49:45'),
(48, 'REQ-2025-601', 23, 'Graduation Cord Order', 'Order for 2 Graduation Cord', 'approved', '2025-05-19 06:42:48', '2025-05-19 06:43:07'),
(49, 'REQ-2025-895', 4, 'School Pin Order', 'Please approve it', 'approved', '2025-05-19 06:50:09', '2025-05-19 06:50:42'),
(50, 'REQ-2025-183', 4, 'Graduation Cord Order', 'Order for 1 Graduation Cord', 'approved', '2025-05-19 07:14:38', '2025-05-20 07:02:15'),
(51, 'REQ-2025-364', 4, 'BSIT OJT - Shirt Order', 'Order for 1 BSIT OJT - Shirt (Size: S)', 'approved', '2025-05-19 13:25:57', '2025-05-19 13:26:28'),
(52, 'REQ-2025-900', 4, 'BSIT OJT - Shirt Order', 'Order for 2 BSIT OJT - Shirt (Size: XS)', 'approved', '2025-05-19 13:57:46', '2025-05-19 13:58:00'),
(53, 'REQ-2025-425', 4, 'NSTP Shirt - CWTS Order', 'Order for 1 NSTP Shirt - CWTS (Size: 2XL)', 'approved', '2025-05-19 14:01:53', '2025-05-19 14:02:06'),
(54, 'REQ-2025-348', 4, 'BSIT OJT - Shirt Order', 'Order for 3 BSIT OJT - Shirt (Size: XS)', 'approved', '2025-05-19 14:04:48', '2025-05-19 14:05:05'),
(55, 'REQ-2025-988', 25, 'BSIT OJT - Shirt Order', 'Order for 1 BSIT OJT - Shirt (Size: M)', 'approved', '2025-05-20 00:29:15', '2025-05-20 00:30:09'),
(56, 'REQ-2025-236', 4, 'ROTC - CAP Order', 'Order for 1 ROTC - CAP', 'approved', '2025-05-20 11:34:58', '2025-05-20 11:35:24'),
(57, 'REQ-2025-583', 4, 'ROTC - CAP Order', 'Order for 5 ROTC - CAP', 'approved', '2025-05-20 14:19:06', '2025-05-20 14:21:37'),
(58, 'REQ-2025-471', 4, 'BSIS Order', 'Order for 1 BSIS', 'approved', '2025-05-21 02:41:51', '2025-05-21 02:42:09'),
(59, 'REQ-2025-775', 4, 'BSIS Order', 'Order for 1 BSIS', 'approved', '2025-05-21 02:50:04', '2025-05-21 02:50:43'),
(60, 'REQ-2025-949', 4, 'BSIT OJT - Shirt Order', 'Order for 1 BSIT OJT - Shirt (Size: XS)', 'pending', '2025-05-21 02:53:52', '2025-05-21 02:53:52'),
(61, 'REQ-2025-411', 27, 'BSIT OJT - Shirt Order', 'Order for 1 BSIT OJT - Shirt (Size: XS)', 'pending', '2025-05-28 14:21:54', '2025-05-28 14:21:54'),
(62, 'REQ-2025-631', 1, 'NSTP Shirt - LTS Order', 'Order for 1 NSTP Shirt - LTS (Size: M)', 'pending', '2025-05-29 05:21:37', '2025-05-29 05:21:37'),
(63, 'REQ-2025-464', 7, 'ROTC - CAP Order', 'Order for 1 ROTC - CAP', 'approved', '2025-09-09 04:09:26', '2025-09-09 04:11:38'),
(64, 'REQ-2025-676', 7, 'ROTC - CAP Order', 'Order for 1 ROTC - CAP', 'pending', '2025-09-13 05:49:59', '2025-09-13 05:49:59'),
(65, 'REQ-2025-577', 7, 'balon Order', 'Order for 2 balon', 'approved', '2025-09-13 05:58:33', '2025-09-13 05:59:17'),
(66, 'REQ-2025-216', 7, 'balon Order', 'Order for 1 balon', 'pending', '2025-09-16 03:36:54', '2025-09-16 03:36:54'),
(67, 'REQ-20251024-175248-', 5, 'Faculty - ID - Cord Order', 'Order for 1 Faculty - ID - Cord', 'approved', '2025-10-24 15:52:48', '2025-10-24 15:53:02'),
(68, 'REQ-20251024-175324-', 5, 'BSIT OJT - Shirt Order', 'Order for 1 BSIT OJT - Shirt', 'approved', '2025-10-24 15:53:24', '2025-10-24 15:53:57'),
(69, 'REQ-20251024-175538-', 5, 'Logo Order', 'Order for 1 Logo', 'approved', '2025-10-24 15:55:38', '2025-10-24 15:56:26'),
(70, 'REQ-20251026-074521-', 8, 'BSIT OJT - Shirt Order', 'Order for 1 BSIT OJT - Shirt (Size: XS)', 'approved', '2025-10-26 06:45:21', '2025-10-26 06:46:12');

-- --------------------------------------------------------

--
-- Table structure for table `request_comments`
--

CREATE TABLE `request_comments` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `request_comments`
--

INSERT INTO `request_comments` (`id`, `request_id`, `user_id`, `comment`, `created_at`) VALUES
(12, 16, 10, 'Related orders have been automatically approved.', '2025-05-03 23:33:10'),
(13, 18, 10, 'Related orders have been automatically approved.', '2025-05-04 15:18:33'),
(14, 19, 10, 'Related orders have been automatically rejected.', '2025-05-04 15:20:03'),
(15, 20, 10, 'Related orders have been automatically approved.', '2025-05-04 15:33:43'),
(16, 21, 10, 'Related orders have been automatically approved.', '2025-05-04 15:56:42'),
(17, 26, 10, 'Related orders have been automatically approved.', '2025-05-11 07:36:19'),
(18, 29, 10, 'Related orders have been automatically approved.', '2025-05-11 07:52:40'),
(19, 30, 10, 'Related orders have been automatically approved.', '2025-05-11 08:43:46'),
(20, 32, 10, 'Related orders have been automatically approved.', '2025-05-12 16:12:07'),
(21, 31, 10, 'Related orders have been automatically approved.', '2025-05-12 16:16:58'),
(22, 33, 10, 'Related orders have been automatically approved.', '2025-05-12 19:17:55'),
(23, 34, 10, 'Related orders have been automatically approved.', '2025-05-14 08:35:19'),
(24, 37, 10, 'Related orders have been automatically approved.', '2025-05-14 16:09:32'),
(25, 37, 10, 'Related orders have been manually updated to Pending.', '2025-05-14 16:09:42'),
(26, 37, 10, 'Related orders have been manually updated to Approved.', '2025-05-14 16:09:50'),
(27, 39, 10, 'Related orders have been automatically approved.', '2025-05-14 16:11:31'),
(28, 40, 10, 'Related orders have been automatically approved.', '2025-05-14 16:13:04'),
(29, 41, 10, 'Related orders have been automatically approved.', '2025-05-14 16:16:26'),
(30, 43, 10, 'Related orders have been automatically approved.', '2025-05-14 16:17:45'),
(31, 45, 10, 'Related orders have been automatically approved.', '2025-05-18 14:09:05'),
(32, 46, 10, 'Related orders have been automatically approved.', '2025-05-18 14:27:44'),
(33, 47, 10, 'Related orders have been automatically approved.', '2025-05-18 14:49:45'),
(34, 48, 10, 'Related orders have been automatically approved.', '2025-05-19 06:43:07'),
(35, 51, 10, 'Related orders have been automatically approved.', '2025-05-19 13:26:28'),
(36, 52, 10, 'Related orders have been automatically approved.', '2025-05-19 13:58:00'),
(37, 53, 10, 'Related orders have been automatically approved.', '2025-05-19 14:02:06'),
(38, 54, 10, 'Related orders have been automatically approved.', '2025-05-19 14:05:05'),
(39, 55, 10, 'Related orders have been automatically approved.', '2025-05-20 00:30:09'),
(40, 50, 10, 'You can get your order', '2025-05-20 07:02:15'),
(41, 56, 10, 'Related orders have been automatically approved.', '2025-05-20 11:35:24'),
(42, 57, 10, 'Related orders have been automatically approved.', '2025-05-20 14:21:37'),
(43, 58, 10, 'Related orders have been automatically approved.', '2025-05-21 02:42:09'),
(44, 59, 10, 'Related orders have been automatically approved.', '2025-05-21 02:50:43'),
(45, 63, 5, 'Related orders have been automatically approved.', '2025-09-09 04:11:38'),
(46, 65, 5, 'Related orders have been automatically approved.', '2025-09-13 05:59:17'),
(47, 67, 6, 'Related orders have been automatically approved.', '2025-10-24 15:53:02'),
(48, 68, 6, 'Related orders have been automatically approved.', '2025-10-24 15:53:57'),
(49, 69, 6, 'Related orders have been automatically approved.', '2025-10-24 15:56:26'),
(50, 70, 6, 'Related orders have been automatically approved.', '2025-10-26 06:46:12');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('student','staff','admin','external') NOT NULL,
  `organization` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_number` varchar(255) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `user_type`, `organization`, `created_at`, `updated_at`, `id_number`, `phone`, `department`) VALUES
(1, 'damasing', 'damasingjoemarie@gmail.com', '$2y$10$7aeg/j6ZBZPaP3Uvmu6Gs..Bwn.udlwe6nC34EEKhfxUAnfKGYzGe', 'student', '', '2025-05-28 14:42:56', '2025-05-28 14:42:56', '', NULL, NULL),
(2, 'admin', 'bsis2a.damasing.joemarie@gmail.com', '$2y$10$JYKHLnGBOra0Dmbocw7hj.6PegeDSahplRmu0OCfbsJc6pzmdGHOu', 'student', '', '2025-05-29 07:14:48', '2025-05-29 07:14:48', '', NULL, NULL),
(3, 'ADMIN', 'adminbao@gmail.com', '$2y$10$yoHxpk./QPf6FBWUuBOD/uTgX3G6EkhkLZOjqzrPIvQsBdtaXlAPy', 'admin', NULL, '2025-07-08 07:50:10', '2025-07-08 07:50:10', '', NULL, NULL),
(4, 'adminbao2', 'adminbao2@gmail.com', '$2y$10$pATIqcFjdPpG7RbOfXsd.OTKwGaAjx4lvLejgX0ZLKEFeej7Z0HnG', 'admin', '', '2025-07-08 08:02:10', '2025-07-08 08:02:10', '', NULL, NULL),
(5, 'admin3', 'admin3@gmail.com', '$2y$10$qCVyjP5qupfW7A5n07B...Fqr.Y7N4P8pn.HEMUT2LKYf6uY0bjxC', 'admin', NULL, '2025-07-08 08:09:25', '2025-07-08 08:09:25', '', NULL, NULL),
(7, 'Jonald Sabordo', 'sabordojonald@gmail.com', '$2y$10$D.lBwqX8.VN9Zw2FveV0vOR.cmzE3RAT/3msDOZG6N2NOvsravWQe', 'student', '', '2025-09-06 07:15:45', '2025-09-06 07:15:45', '', NULL, NULL),
(8, 'gjnavarez.chmsu@gmail.com', 'gjnavarez.chmsu@gmail.com', '$2y$10$0FHOaD1AsN4a/UcYBwY9CeJuiy9t8J3fAEaMldwcEXcK5Ac83Qp/e', 'external', 'TUP', '2025-09-09 12:56:19', '2025-09-09 12:56:19', '', NULL, NULL),
(9, 'Carin', 'benjamincarin33@gmail.com', '$2y$10$jEIEtPRJyFkFw.U2gf7DZ.I0WDOzVD/QyUZUpenpEsgAF95tzz3TS', 'external', 'NOTRE', '2025-09-20 12:33:09', '2025-09-20 12:33:09', '', NULL, NULL),
(10, 'Wela', 'rowelaalumba04@gmail.com', '$2y$10$J4uVoZTqtlLxD/HsyYoRNuuNJGx6K/A20Q9qPnwH45JDAtyp.5aNq', 'student', '', '2025-10-06 09:30:10', '2025-10-06 09:30:10', '', NULL, NULL),
(11, 'Sarah Navarez', 'sbdelacruz.chmsu@gmail.com', '$2y$10$RpcaL6HbRK2k4H2o5TLLyOP35b1PaCCkdH0FOcUuRUrzuEu3fTABm', 'student', '', '2025-10-07 03:32:09', '2025-10-07 03:32:09', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_accounts`
--

CREATE TABLE `user_accounts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('admin','staff','student','external') NOT NULL,
  `organization` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_accounts`
--

INSERT INTO `user_accounts` (`id`, `name`, `email`, `password`, `user_type`, `organization`, `phone`, `address`, `profile_pic`, `status`, `created_at`, `updated_at`) VALUES
(1, 'System Administrator', 'admin@chmsu.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, NULL, NULL, NULL, 'active', '2025-10-23 03:16:26', '2025-10-23 03:16:26'),
(2, 'BAO Staff', 'staff@chmsu.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, NULL, NULL, NULL, 'active', '2025-10-23 03:16:26', '2025-10-23 03:16:26'),
(3, 'Test Student', 'student@chmsu.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', NULL, NULL, NULL, NULL, 'active', '2025-10-23 03:16:26', '2025-10-23 03:16:26'),
(4, 'External User', 'external@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'external', 'Sample Organization', NULL, NULL, NULL, 'active', '2025-10-23 03:16:26', '2025-10-23 03:16:26'),
(5, 'crisalwinmaapn', 'crisalwinmaapni@gmail.com', '$2y$10$NMZMatrYBNWs7ZTykPOEv.a0NJyoH3hleVHMYmB2vXlB5HIURmiZq', 'student', 'CHMSU', '09814354707', 'PUROK MAPALARON', 'uploads/profile_pics/user_5_1761322363.jpg', 'active', '2025-10-23 03:21:57', '2025-10-24 16:13:14'),
(6, 'Balaan', 'Balaan@gmail.com', '$2y$10$h5x5URIfqfMhveLOflkE6eJsJFqZovKbMPrbbUnFZnMZeTzq3VYoe', 'admin', NULL, NULL, NULL, NULL, 'active', '2025-10-23 03:23:35', '2025-10-23 03:23:35'),
(7, 'AJ', 'adledesma.chmsu@gmail.com', '$2y$10$un1myIQ37hwVMSbt8rtLf.u4VJWym2u2QGf.Q5PbvSXCpEHT1HMt6', 'external', 'CHMSU', '09106934023', 'PUROK MAPALARON', 'uploads/profile_pics/user_7_1761330546.png', 'active', '2025-10-24 17:08:58', '2025-10-24 18:29:06'),
(8, 'Joemarie D. Damasing', 'damasingjoemarie@gmail.com', '$2y$10$2.jhTFpr70BikZFHyETFWu95hF05lvkqg9XHk69CtnuGVBS45nWx.', 'student', '', NULL, NULL, NULL, 'active', '2025-10-26 06:00:10', '2025-10-26 06:00:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `billing_statements`
--
ALTER TABLE `billing_statements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_booking_date` (`date`);

--
-- Indexes for table `buses`
--
ALTER TABLE `buses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bus_number` (`bus_number`);

--
-- Indexes for table `bus_bookings`
--
ALTER TABLE `bus_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `bus_id` (`bus_id`);

--
-- Indexes for table `bus_schedules`
--
ALTER TABLE `bus_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bus_schedules_user_id` (`user_id`),
  ADD KEY `idx_bus_schedules_status` (`status`),
  ADD KEY `idx_bus_schedules_date` (`date_covered`);

--
-- Indexes for table `facilities`
--
ALTER TABLE `facilities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gym_bookings`
--
ALTER TABLE `gym_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `gym_bookings_ibfk_2` (`facility_id`);

--
-- Indexes for table `gym_facilities`
--
ALTER TABLE `gym_facilities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventory_stock` (`in_stock`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `inventory_id` (`inventory_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`),
  ADD KEY `token` (`token`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_id` (`request_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `request_comments`
--
ALTER TABLE `request_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_accounts`
--
ALTER TABLE `user_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_type` (`user_type`),
  ADD KEY `idx_user_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `billing_statements`
--
ALTER TABLE `billing_statements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `buses`
--
ALTER TABLE `buses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bus_bookings`
--
ALTER TABLE `bus_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `bus_schedules`
--
ALTER TABLE `bus_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `facilities`
--
ALTER TABLE `facilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `gym_bookings`
--
ALTER TABLE `gym_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gym_facilities`
--
ALTER TABLE `gym_facilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `request_comments`
--
ALTER TABLE `request_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user_accounts`
--
ALTER TABLE `user_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `billing_statements`
--
ALTER TABLE `billing_statements`
  ADD CONSTRAINT `billing_statements_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `bus_schedules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bus_bookings`
--
ALTER TABLE `bus_bookings`
  ADD CONSTRAINT `bus_bookings_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `bus_schedules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bus_bookings_ibfk_2` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gym_bookings`
--
ALTER TABLE `gym_bookings`
  ADD CONSTRAINT `gym_bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `gym_bookings_ibfk_2` FOREIGN KEY (`facility_id`) REFERENCES `gym_facilities` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
