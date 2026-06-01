-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 01, 2026 at 10:46 AM
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
-- Database: `medisource_hris`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance_adjustment_requests`
--

CREATE TABLE `attendance_adjustment_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ref_no` varchar(255) NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `attendance_log_id` bigint(20) UNSIGNED DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `original_clock_in` time DEFAULT NULL,
  `original_clock_out` time DEFAULT NULL,
  `requested_clock_in` time NOT NULL,
  `requested_clock_out` time DEFAULT NULL,
  `reason` text NOT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','supervisor_approved','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `supervisor_approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `supervisor_approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_by` bigint(20) UNSIGNED DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_corrections`
--

CREATE TABLE `attendance_corrections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `shift_id` bigint(20) UNSIGNED DEFAULT NULL,
  `holiday_id` bigint(20) UNSIGNED DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `work_setup` enum('office','wfh') DEFAULT NULL,
  `clock_in` datetime DEFAULT NULL,
  `break_start` timestamp NULL DEFAULT NULL,
  `break_end` timestamp NULL DEFAULT NULL,
  `break_minutes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `clock_out` datetime DEFAULT NULL,
  `late_minutes` int(11) DEFAULT 0,
  `undertime_minutes` int(11) DEFAULT 0,
  `overtime_minutes` int(11) DEFAULT 0,
  `total_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status` enum('present','late','absent','on_leave','holiday','undertime','overtime','incomplete') NOT NULL DEFAULT 'present'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_logs`
--

INSERT INTO `attendance_logs` (`id`, `employee_id`, `shift_id`, `holiday_id`, `attendance_date`, `created_at`, `updated_at`, `work_setup`, `clock_in`, `break_start`, `break_end`, `break_minutes`, `clock_out`, `late_minutes`, `undertime_minutes`, `overtime_minutes`, `total_hours`, `status`) VALUES
(2, 6, NULL, NULL, '2026-04-13', '2026-04-09 23:49:45', '2026-04-30 03:50:17', NULL, NULL, NULL, NULL, 0, '2026-04-13 23:59:59', 0, 0, 0, 0.00, 'incomplete'),
(3, 6, NULL, NULL, '2026-04-14', '2026-04-09 23:49:45', '2026-04-24 01:42:54', NULL, NULL, NULL, NULL, 0, '2026-04-14 23:59:59', 0, 0, 0, 0.00, 'incomplete'),
(7, 1, 1, NULL, '2026-04-15', '2026-04-15 11:54:40', '2026-04-15 11:54:50', 'office', '2026-04-15 19:54:40', '2026-04-15 11:54:43', '2026-04-15 11:54:48', 0, '2026-04-15 19:54:50', 774, 0, 0, 0.00, 'late'),
(55, 5, 2, NULL, '2026-04-16', '2026-04-16 12:20:10', '2026-04-16 12:20:24', 'wfh', '2026-04-16 20:20:10', NULL, NULL, 0, '2026-04-16 20:20:24', 0, 579, 0, 0.00, 'undertime'),
(56, 14, 2, NULL, '2026-04-17', '2026-04-16 19:27:00', '2026-04-26 23:50:16', 'wfh', '2026-04-17 22:00:00', NULL, NULL, 0, '2026-04-18 06:00:00', 0, 1591, 0, 8.00, 'present'),
(57, 1, 1, NULL, '2026-04-20', '2026-04-19 23:58:13', '2026-04-19 23:58:25', 'office', '2026-04-20 07:58:13', NULL, NULL, 0, '2026-04-20 07:58:25', 58, 481, 0, 0.00, 'late'),
(58, 1, 1, NULL, '2026-04-21', '2026-04-21 05:25:32', '2026-04-21 05:29:02', 'office', '2026-04-21 13:25:32', NULL, NULL, 0, '2026-04-21 13:29:02', 385, 150, 0, 0.02, 'late'),
(59, 6, 1, NULL, '2026-04-30', '2026-04-30 03:50:17', '2026-05-06 07:46:35', 'office', '2026-04-30 11:50:17', NULL, NULL, 0, '2026-04-30 16:00:00', 290, 0, 0, 4.15, 'incomplete'),
(60, 1, 1, NULL, '2026-04-30', '2026-04-30 07:32:39', '2026-04-30 07:32:41', 'office', '2026-04-30 15:32:39', NULL, NULL, 0, '2026-04-30 15:32:41', 512, 27, 0, 0.00, 'late'),
(61, 5, 2, NULL, '2026-05-05', '2026-05-05 00:52:06', '2026-05-05 00:52:06', 'wfh', '2026-05-05 08:52:06', NULL, NULL, 0, NULL, 0, 0, 0, 0.00, 'present'),
(62, 2, 1, NULL, '2026-05-05', '2026-05-05 00:54:08', '2026-05-05 00:54:08', 'wfh', '2026-05-05 08:54:07', NULL, NULL, 0, NULL, 114, 0, 0, 0.00, 'late'),
(63, 14, 2, NULL, '2026-05-05', '2026-05-05 01:13:01', '2026-05-05 01:13:01', 'wfh', '2026-05-05 09:13:01', NULL, NULL, 0, NULL, 0, 0, 0, 0.00, 'present'),
(64, 6, 1, NULL, '2026-05-06', '2026-05-06 07:46:35', '2026-05-06 23:51:46', 'office', '2026-05-06 15:46:35', NULL, NULL, 0, '2026-05-06 16:00:00', 526, 0, 0, 0.22, 'incomplete'),
(65, 1, 1, NULL, '2026-05-07', '2026-05-06 23:38:32', '2026-05-07 06:01:26', 'office', '2026-05-07 07:38:31', NULL, NULL, 0, '2026-05-07 14:01:26', 38, 118, 0, 6.37, 'late'),
(66, 18, 1, NULL, '2026-05-07', '2026-05-07 00:01:23', '2026-05-07 05:53:00', 'wfh', '2026-05-07 08:01:23', NULL, NULL, 0, '2026-05-07 13:53:00', 61, 126, 0, 5.02, 'late'),
(67, 5, NULL, NULL, '2026-05-07', '2026-05-07 02:33:35', '2026-05-07 02:33:35', NULL, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0.00, 'on_leave'),
(68, 5, NULL, NULL, '2026-05-08', '2026-05-07 02:33:35', '2026-05-07 02:33:35', NULL, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0.00, 'on_leave'),
(69, 5, NULL, NULL, '2026-05-09', '2026-05-07 02:33:35', '2026-05-07 02:33:35', NULL, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0.00, 'on_leave');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_sessions`
--

CREATE TABLE `attendance_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `attendance_log_id` bigint(20) UNSIGNED NOT NULL,
  `clock_in` datetime NOT NULL,
  `clock_out` datetime DEFAULT NULL,
  `break_start` timestamp NULL DEFAULT NULL,
  `break_end` timestamp NULL DEFAULT NULL,
  `break_minutes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_sessions`
--

INSERT INTO `attendance_sessions` (`id`, `attendance_log_id`, `clock_in`, `clock_out`, `break_start`, `break_end`, `break_minutes`, `created_at`, `updated_at`) VALUES
(1, 55, '2026-04-16 20:20:10', '2026-04-16 20:20:24', '2026-04-16 12:20:17', '2026-04-16 12:20:21', 0, '2026-04-16 12:20:10', '2026-04-16 12:20:24'),
(2, 56, '2026-04-17 03:27:00', '2026-04-17 03:27:09', '2026-04-16 19:27:04', '2026-04-16 19:27:06', 0, '2026-04-16 19:27:00', '2026-04-16 19:27:09'),
(3, 56, '2026-04-17 03:27:15', '2026-04-17 03:28:26', NULL, NULL, 0, '2026-04-16 19:27:15', '2026-04-16 19:28:26'),
(4, 57, '2026-04-20 07:58:13', '2026-04-20 07:58:21', '2026-04-19 23:58:16', '2026-04-19 23:58:18', 0, '2026-04-19 23:58:13', '2026-04-19 23:58:21'),
(5, 57, '2026-04-20 07:58:22', '2026-04-20 07:58:25', NULL, NULL, 0, '2026-04-19 23:58:22', '2026-04-19 23:58:25'),
(6, 58, '2026-04-21 13:25:32', '2026-04-21 13:27:02', '2026-04-21 05:26:26', '2026-04-21 05:26:29', 0, '2026-04-21 05:25:32', '2026-04-21 05:27:02'),
(7, 58, '2026-04-21 13:27:20', '2026-04-21 13:28:04', NULL, NULL, 0, '2026-04-21 05:27:20', '2026-04-21 05:28:04'),
(8, 58, '2026-04-21 13:28:18', '2026-04-21 13:29:02', NULL, NULL, 0, '2026-04-21 05:28:18', '2026-04-21 05:29:02'),
(9, 59, '2026-04-30 11:50:17', '2026-04-30 16:00:00', '2026-04-30 04:00:09', NULL, 0, '2026-04-30 03:50:17', '2026-05-06 07:46:35'),
(10, 60, '2026-04-30 15:32:39', '2026-04-30 15:32:41', NULL, NULL, 0, '2026-04-30 07:32:39', '2026-04-30 07:32:41'),
(11, 61, '2026-05-05 08:52:06', NULL, NULL, NULL, 0, '2026-05-05 00:52:06', '2026-05-05 00:52:06'),
(12, 62, '2026-05-05 08:54:07', NULL, NULL, NULL, 0, '2026-05-05 00:54:08', '2026-05-05 00:54:08'),
(13, 63, '2026-05-05 09:13:01', NULL, NULL, NULL, 0, '2026-05-05 01:13:01', '2026-05-05 01:13:01'),
(14, 64, '2026-05-06 15:46:35', '2026-05-06 16:00:00', NULL, NULL, 0, '2026-05-06 07:46:35', '2026-05-06 23:51:46'),
(15, 65, '2026-05-07 07:38:31', '2026-05-07 14:01:26', NULL, NULL, 0, '2026-05-06 23:38:32', '2026-05-07 06:01:26'),
(16, 66, '2026-05-07 08:01:23', '2026-05-07 08:01:40', '2026-05-07 00:01:31', '2026-05-07 00:01:34', 0, '2026-05-07 00:01:23', '2026-05-07 00:01:40'),
(17, 66, '2026-05-07 08:51:51', '2026-05-07 13:53:00', NULL, NULL, 0, '2026-05-07 00:51:51', '2026-05-07 05:53:00');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(255) NOT NULL,
  `auditable_type` varchar(255) NOT NULL,
  `auditable_id` bigint(20) UNSIGNED NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `benefits`
--

CREATE TABLE `benefits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('Allowance','Bonus','Incentive') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `tax` enum('Taxable','Non-taxable') NOT NULL DEFAULT 'Non-taxable',
  `frequency` varchar(255) NOT NULL,
  `eligibility` varchar(255) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `benefits`
--

INSERT INTO `benefits` (`id`, `name`, `type`, `amount`, `tax`, `frequency`, `eligibility`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Transpo', 'Allowance', 200.00, 'Non-taxable', 'Weekly', 'All regular employees', 'Active', '2026-03-30 01:43:18', '2026-03-30 01:43:18'),
(2, 'SAMPLE', 'Allowance', 750.00, 'Non-taxable', 'Monthly', 'All', 'Active', '2026-05-05 02:15:40', '2026-05-05 02:15:40');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contribution_settings`
--

CREATE TABLE `contribution_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` decimal(15,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contribution_settings`
--

INSERT INTO `contribution_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(1, 'sss_employee_rate', 5.0000, NULL, NULL),
(2, 'sss_employer_rate', 10.0000, NULL, NULL),
(3, 'sss_max_msc', 30000.0000, NULL, NULL),
(4, 'philhealth_rate', 5.0000, NULL, NULL),
(5, 'philhealth_floor', 10000.0000, NULL, NULL),
(6, 'philhealth_ceiling', 100000.0000, NULL, NULL),
(7, 'pagibig_low_rate', 1.0000, NULL, NULL),
(8, 'pagibig_high_rate', 2.0000, NULL, NULL),
(9, 'pagibig_max', 200.0000, NULL, NULL),
(10, 'wtax_bracket_1', 250000.0000, NULL, NULL),
(11, 'wtax_bracket_2', 400000.0000, NULL, NULL),
(12, 'wtax_bracket_3', 800000.0000, NULL, NULL),
(13, 'wtax_bracket_4', 2000000.0000, NULL, NULL),
(14, 'wtax_rate_1', 15.0000, NULL, NULL),
(15, 'wtax_rate_2', 20.0000, NULL, NULL),
(16, 'wtax_rate_3', 25.0000, NULL, NULL),
(17, 'wtax_rate_4', 30.0000, NULL, NULL),
(18, 'pagibig_threshold', 10000.0000, NULL, NULL),
(19, 'pagibig_low_amount', 100.0000, NULL, NULL),
(20, 'pagibig_high_amount', 200.0000, NULL, NULL),
(21, 'wtax_bracket_5', 8000000.0000, NULL, NULL),
(22, 'wtax_rate_5', 35.0000, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'IT', NULL, '2026-03-01 22:18:31', '2026-03-05 20:52:53'),
(2, 'Healthcare', NULL, '2026-03-01 22:18:31', '2026-03-05 19:36:47'),
(3, 'Finance & Accounting', NULL, '2026-03-01 22:18:31', '2026-03-05 19:36:41'),
(4, 'Human Resources', NULL, '2026-03-01 22:18:31', '2026-03-01 22:18:31'),
(5, 'Operations', NULL, '2026-03-01 22:18:44', '2026-03-01 22:18:44');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(255) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `uploaded_by` bigint(20) UNSIGNED NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `employee_id`, `file_name`, `file_path`, `file_type`, `file_size`, `uploaded_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'HRIS - Access Matrix.pdf', 'employee_documents/GxcvZ5Ulktf2dW6es5httYiEIgd3e5xKaI91LiWx.pdf', 'pdf', 893236, 1, NULL, '2026-03-31 00:25:17', '2026-03-31 00:25:17'),
(2, 2, 'How the Settings.docx', 'employee_documents/T2TF4HX6esx05416atmCOwywMFW0N73hsPIOYvtJ.docx', 'docx', 18357, 1, NULL, '2026-04-08 05:57:51', '2026-04-08 05:57:51'),
(3, 2, 'Observation on the Influence of Sunlight in Growing Mung Beans.docx', 'employee_documents/gFcegyFz2p9F9tw4i3nClOs1tu3ZmyF5wQHjrpQv.docx', 'docx', 18382, 2, NULL, '2026-04-08 06:02:01', '2026-04-08 06:02:01'),
(4, 1, 'How the Settings.docx', 'employee_documents/m4uWNkfmweso1Y6HwDOHBQkOUI9aYlQw6sIMnWoE.docx', 'docx', 18357, 20, '2026-04-08 06:17:51', '2026-04-08 06:02:26', '2026-04-08 06:17:51');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `employment_type` varchar(255) DEFAULT NULL,
  `contract_period` enum('3 months','6 months','1 year','2 years','Indefinite') DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `employee_code` varchar(255) NOT NULL,
  `fname` varchar(255) NOT NULL,
  `mi` varchar(255) DEFAULT NULL,
  `lname` varchar(255) NOT NULL,
  `suffix` varchar(255) DEFAULT NULL,
  `gender` varchar(50) NOT NULL,
  `date_of_birth` date NOT NULL,
  `contact_no` varchar(255) NOT NULL,
  `employment_status` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `job_title_id` bigint(20) UNSIGNED NOT NULL,
  `salary_grade_id` bigint(20) UNSIGNED DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `employment_type`, `contract_period`, `start_date`, `end_date`, `employee_code`, `fname`, `mi`, `lname`, `suffix`, `gender`, `date_of_birth`, `contact_no`, `employment_status`, `address`, `department_id`, `job_title_id`, `salary_grade_id`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'Full-time', NULL, '2026-01-01', NULL, 'ADM001', 'Joshuaa', 'M', 'Adm', NULL, 'M', '1999-01-01', '09123456789', 'Active', 'Urdaneta City', 1, 1, NULL, NULL, '2026-03-01 22:26:35', '2026-04-08 06:05:09'),
(2, 2, NULL, NULL, '2026-03-06', NULL, 'HRM001', 'John', 'Dev', 'HR', NULL, 'M', '1998-05-15', '09123456780', 'Active', 'Urdaneta City', 4, 9, NULL, NULL, '2026-03-01 22:26:36', '2026-03-08 17:43:11'),
(5, 5, NULL, NULL, '2026-03-02', NULL, 'FIN001', 'Zed', NULL, 'Pay', NULL, 'M', '1995-12-05', '09123456783', 'Active', 'Urdaneta City', 3, 6, 2, NULL, '2026-03-01 22:26:36', '2026-04-01 00:28:36'),
(6, 6, NULL, NULL, '2026-03-02', NULL, 'EMP001', 'Rafael', NULL, 'Emp', NULL, 'F', '2000-03-10', '09123456784', 'Active', 'Urdaneta City', 2, 12, 1, NULL, '2026-03-01 22:26:43', '2026-03-30 07:25:53'),
(14, 20, 'Full-time', '3 months', '2026-03-06', NULL, 'SUP001', 'Daz', NULL, 'Sup', NULL, 'Male', '2026-03-02', '09013746273', 'Active', 'weqeqe', 3, 20, NULL, NULL, '2026-03-05 17:41:17', '2026-05-07 03:08:09'),
(18, 24, 'Full-time', '3 months', '2026-03-17', NULL, 'PAY001', 'Kene', NULL, 'Pay', NULL, 'Male', '2026-03-02', '09346436745', 'Active', 'Urdaneta', 3, 7, 2, NULL, '2026-03-17 01:45:31', '2026-04-01 00:28:36'),
(106, 114, 'Full-time', NULL, '2026-04-20', NULL, 'ADM002', 'Test', NULL, 'T', NULL, 'Prefer not to say', '2026-04-03', '09271928311', 'Active', 'unknwowon', 1, 1, NULL, NULL, '2026-04-19 23:02:01', '2026-04-19 23:02:01'),
(107, 115, 'Full-time', 'Indefinite', '2026-05-07', NULL, 'EMP002', 'John Paul', 'M', 'Guzman', NULL, 'Male', '2026-05-06', '09619912345', 'Active', 'Bayaoas', 2, 13, NULL, NULL, '2026-05-06 23:13:18', '2026-05-06 23:13:18'),
(108, 116, 'Full-time', '1 year', '2026-05-07', '2026-05-14', 'SUP002', 'Phoebe Hanna', 'G', 'Estoesta', NULL, 'Female', '2004-08-14', '09123456789', 'Active', 'PALINA', 3, 20, NULL, NULL, '2026-05-07 03:03:56', '2026-05-07 03:03:56');

-- --------------------------------------------------------

--
-- Table structure for table `employee_benefits`
--

CREATE TABLE `employee_benefits` (
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `benefit_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_benefits`
--

INSERT INTO `employee_benefits` (`employee_id`, `benefit_id`) VALUES
(1, 1),
(2, 1),
(5, 1),
(6, 1),
(14, 1),
(18, 1);

-- --------------------------------------------------------

--
-- Table structure for table `employee_shifts`
--

CREATE TABLE `employee_shifts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `shift_id` bigint(20) UNSIGNED NOT NULL,
  `work_setup` enum('office','wfh') DEFAULT NULL,
  `days_off` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`days_off`)),
  `effective_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `break_schedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`break_schedule`)),
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_shifts`
--

INSERT INTO `employee_shifts` (`id`, `employee_id`, `shift_id`, `work_setup`, `days_off`, `effective_date`, `end_date`, `is_active`, `break_schedule`, `description`, `created_at`, `updated_at`) VALUES
(2, 2, 1, 'wfh', '[\"Sat\",\"Sun\"]', '2026-03-18', NULL, 1, NULL, NULL, '2026-03-18 05:24:25', '2026-03-18 05:55:48'),
(3, 14, 2, 'wfh', '[\"Sat\",\"Sun\"]', '2026-03-18', NULL, 1, NULL, NULL, '2026-03-18 12:23:01', '2026-03-18 12:23:01'),
(4, 6, 1, 'office', '[]', '2026-03-21', NULL, 1, NULL, NULL, '2026-03-21 06:56:02', '2026-03-21 06:56:02'),
(5, 1, 1, 'office', '[\"Sat\",\"Sun\"]', '2026-03-22', NULL, 1, NULL, NULL, '2026-03-22 12:03:12', '2026-03-22 12:03:12'),
(6, 5, 2, 'wfh', '[\"Sat\",\"Sun\"]', '2026-03-22', NULL, 1, NULL, NULL, '2026-03-22 12:03:28', '2026-03-22 12:03:28'),
(7, 18, 1, 'wfh', '[\"Sat\",\"Sun\"]', '2026-03-22', NULL, 1, NULL, NULL, '2026-03-22 12:03:41', '2026-03-22 12:03:41');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `type` enum('regular','special','local') NOT NULL,
  `pay_rate` varchar(10) NOT NULL DEFAULT '200%',
  `region` varchar(100) DEFAULT NULL,
  `yearly` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `name`, `date`, `type`, `pay_rate`, `region`, `yearly`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Eid al-Fitr', '2026-03-20', 'regular', '100%', NULL, 0, NULL, '2026-03-18 06:26:45', '2026-04-08 06:51:01'),
(2, 'test', '2026-04-25', 'special', '100%', NULL, 0, NULL, '2026-04-20 00:14:38', '2026-04-20 00:14:38');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_titles`
--

CREATE TABLE `job_titles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_titles`
--

INSERT INTO `job_titles` (`id`, `title`, `department_id`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'System Administrator', 1, NULL, '2026-03-04 23:31:57', '2026-03-08 16:02:22'),
(2, 'Senior Programmer', 1, NULL, '2026-03-04 23:31:57', '2026-03-08 16:02:23'),
(3, 'Junior Programmer', 1, NULL, '2026-03-04 23:31:57', '2026-03-08 16:02:23'),
(4, 'Web Designer', 1, NULL, '2026-03-04 23:31:57', '2026-03-08 16:02:23'),
(5, 'QA Tester', 1, '2026-03-05 19:37:13', '2026-03-04 23:31:57', '2026-03-05 19:37:13'),
(6, 'Finance Officer', 3, NULL, '2026-03-04 23:31:57', '2026-03-05 19:36:41'),
(7, 'Payroll Officer', 3, NULL, '2026-03-04 23:31:57', '2026-03-05 19:36:41'),
(8, 'Accountant', 3, NULL, '2026-03-04 23:31:57', '2026-03-05 19:36:41'),
(9, 'HR Manager', 4, NULL, '2026-03-04 23:31:57', '2026-03-04 23:31:57'),
(10, 'HR Officer', 4, NULL, '2026-03-04 23:31:57', '2026-03-04 23:31:57'),
(11, 'Recruiter', 4, NULL, '2026-03-04 23:31:57', '2026-03-04 23:31:57'),
(12, 'Physician', 2, NULL, '2026-03-04 23:31:57', '2026-03-05 20:58:56'),
(13, 'Nurse', 2, NULL, '2026-03-04 23:31:57', '2026-03-05 20:58:56'),
(14, 'Healthcare Administrator', 2, NULL, '2026-03-04 23:31:57', '2026-03-05 20:58:57'),
(15, 'Operations Manager', 5, NULL, '2026-03-04 23:31:57', '2026-03-04 23:31:57'),
(16, 'Project Manager', 5, NULL, '2026-03-04 23:31:57', '2026-03-04 23:31:57'),
(17, 'Administrative Officer', 5, NULL, '2026-03-04 23:31:57', '2026-03-04 23:31:57'),
(18, 'Supervisor', 1, NULL, '2026-03-05 17:32:46', '2026-03-08 16:02:23'),
(19, 'Supervisor', 2, NULL, '2026-03-05 17:32:46', '2026-03-05 20:58:57'),
(20, 'Supervisor', 3, NULL, '2026-03-05 17:32:46', '2026-03-05 19:36:41'),
(21, 'Supervisor', 4, NULL, '2026-03-05 17:32:46', '2026-03-05 17:32:46'),
(22, 'Supervisor', 5, NULL, '2026-03-05 17:32:46', '2026-03-05 17:32:46');

-- --------------------------------------------------------

--
-- Table structure for table `leave_credits`
--

CREATE TABLE `leave_credits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `leave_type_id` bigint(20) UNSIGNED NOT NULL,
  `year` year(4) NOT NULL,
  `total_days` decimal(5,1) NOT NULL DEFAULT 0.0,
  `used_days` decimal(5,1) NOT NULL DEFAULT 0.0,
  `remaining_days` decimal(5,1) NOT NULL DEFAULT 0.0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_credits`
--

INSERT INTO `leave_credits` (`id`, `employee_id`, `leave_type_id`, `year`, `total_days`, `used_days`, `remaining_days`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026', 30.0, 0.0, 30.0, '2026-03-19 05:11:50', '2026-03-19 05:11:50'),
(2, 2, 1, '2026', 30.0, 0.0, 30.0, '2026-03-19 05:11:50', '2026-03-19 05:11:50'),
(3, 5, 1, '2026', 30.0, 0.0, 30.0, '2026-03-19 05:11:50', '2026-03-19 05:11:50'),
(4, 6, 1, '2026', 30.0, 4.0, 26.0, '2026-03-19 05:11:50', '2026-04-10 00:14:01'),
(5, 14, 1, '2026', 30.0, 0.0, 30.0, '2026-03-19 05:11:50', '2026-03-19 05:11:50'),
(6, 18, 1, '2026', 30.0, 0.0, 30.0, '2026-03-19 05:11:50', '2026-03-19 05:11:50'),
(58, 1, 2, '2026', 0.0, 0.0, 0.0, '2026-04-15 12:31:57', '2026-04-15 12:31:57'),
(59, 6, 2, '2026', 0.0, 1.0, 0.0, '2026-04-16 00:02:17', '2026-04-16 00:02:57'),
(60, 5, 2, '2026', 0.0, 2.0, 0.0, '2026-05-07 02:28:52', '2026-05-07 02:33:35'),
(61, 18, 2, '2026', 0.0, 0.0, 0.0, '2026-05-07 02:44:44', '2026-05-07 02:44:44');

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ref_no` varchar(20) NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `leave_type_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` decimal(5,1) NOT NULL,
  `reason` text NOT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','supervisor_approved','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `hr_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `ref_no`, `employee_id`, `leave_type_id`, `start_date`, `end_date`, `total_days`, `reason`, `document_path`, `status`, `approved_by`, `approved_at`, `rejection_reason`, `hr_notes`, `created_at`, `updated_at`) VALUES
(1, 'REQ-0001', 6, 1, '2026-04-13', '2026-04-14', 2.0, 'Leave', 'leave_documents/IUFaqUXLWfo1srIiA3dG8wqg5hoii6YK1Oumm0a7.docx', 'approved', 2, '2026-04-10 08:14:01', NULL, NULL, '2026-04-10 00:12:40', '2026-04-10 00:14:01'),
(62, 'REQ-0002', 1, 2, '2026-04-16', '2026-04-16', 1.0, 'assaas', NULL, 'rejected', NULL, NULL, 'asas', NULL, '2026-04-15 12:31:57', '2026-04-15 12:32:49'),
(63, 'REQ-0003', 1, 2, '2026-04-16', '2026-04-16', 1.0, 'fds', NULL, 'rejected', NULL, NULL, 'dsd', NULL, '2026-04-15 12:38:28', '2026-04-15 12:38:44'),
(64, 'REQ-0004', 6, 2, '2026-04-17', '2026-04-17', 1.0, 'qwwq', NULL, 'approved', 2, '2026-04-16 08:02:57', NULL, NULL, '2026-04-16 00:02:17', '2026-04-16 00:02:57'),
(65, 'REQ-0005', 5, 2, '2026-05-07', '2026-05-09', 2.0, 'Yes I\'m SIck', NULL, 'approved', 1, '2026-05-07 10:33:35', NULL, NULL, '2026-05-07 02:28:52', '2026-05-07 02:33:35'),
(66, 'REQ-0006', 5, 1, '2026-05-15', '2026-05-17', 1.0, 'Going to Boracay', NULL, 'pending', NULL, NULL, NULL, NULL, '2026-05-07 02:36:05', '2026-05-07 02:36:05'),
(67, 'REQ-0007', 18, 2, '2026-05-21', '2026-05-28', 6.0, 'GOING TO SAGADA', NULL, 'pending', NULL, NULL, NULL, NULL, '2026-05-07 02:44:44', '2026-05-07 02:44:44'),
(68, 'REQ-0008', 6, 1, '2026-05-21', '2026-05-28', 6.0, 'GOING TO BAYAOAS REALLY IMPORTANT', NULL, 'pending', NULL, NULL, NULL, NULL, '2026-05-07 02:53:20', '2026-05-07 02:53:20'),
(69, 'REQ-0009', 14, 1, '2026-05-08', '2026-05-21', 10.0, 'SPORTS', NULL, 'pending', NULL, NULL, NULL, NULL, '2026-05-07 03:00:03', '2026-05-07 03:00:03');

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `days_entitled` int(11) DEFAULT NULL,
  `is_paid` tinyint(1) NOT NULL DEFAULT 1,
  `requires_document` tinyint(1) NOT NULL DEFAULT 0,
  `applicable_to` varchar(255) DEFAULT NULL,
  `carry_over` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_types`
--

INSERT INTO `leave_types` (`id`, `name`, `code`, `days_entitled`, `is_paid`, `requires_document`, `applicable_to`, `carry_over`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Vacation Leave', 'VL', 30, 0, 0, 'All regular employees', 1, 1, '2026-03-19 05:11:50', '2026-03-19 05:11:50'),
(2, 'Sick Leave', 'SL', NULL, 0, 0, 'All regular employees', 1, 1, '2026-03-21 06:56:34', '2026-03-21 06:56:34');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_03_01_055713_modify_users_table_add_role_remove_name', 2),
(5, '2026_03_01_060158_create_departments_table', 3),
(6, '2026_03_01_060928_create_job_titles_table', 3),
(7, '2026_03_01_061508_create_employees_table', 4),
(8, '2026_03_01_061804_create_documents_table', 5),
(9, '2026_03_01_061905_create_audit_logs_table', 6),
(10, '2026_03_02_052214_create_shifts_table', 7),
(11, '2026_03_02_052258_create_employee_shifts_table', 8),
(12, '2026_03_02_052421_create_holidays_table', 9),
(13, '2026_03_02_052451_create_attendance_logs_table', 10),
(14, '2026_03_02_052603_create_attendance_corrections_table', 11),
(15, '2026_03_02_052629_create_overtime_requests_table', 12),
(16, '2026_03_04_034636_modify_employees_table_structure', 13),
(17, '2026_03_04_062842_update_employees_table_add_remove_columns', 14),
(18, '2026_03_04_063015_remove_access_level_from_employees_table', 15),
(19, '2026_03_04_063127_drop_access_level_from_employees_table', 16),
(20, '2026_03_05_032221_rename_mname_to_mi_in_employees_table', 17),
(21, '2026_03_05_082226_fix_contract_period_enum', 18),
(22, '2026_03_06_001408_fix_gender_column_in_employees_table', 19),
(23, '2026_03_06_013201_add_supervisor_to_job_titles', 20),
(24, '2026_03_11_013848_add_work_setup_and_status_to_attendance_logs', 21),
(25, '2026_03_11_021827_add_missing_columns_to_attendance_logs', 22),
(26, '2026_03_13_081717_add_break_columns_to_attendance_logs', 23),
(27, '2026_03_15_142136_create_permissions_table', 24),
(28, '2026_03_18_113926_add_work_setup_and_days_off_to_employee_shifts', 25),
(29, '2026_03_18_140935_add_columns_to_holidays_table', 26),
(30, '2026_03_19_112246_create_leave_types_table', 27),
(31, '2026_03_19_112318_create_leave_credits_table', 27),
(32, '2026_03_19_112337_create_leave_requests_table', 27),
(33, '2026_03_21_152550_create_shift_change_requests_table', 28),
(34, '2026_03_21_152816_add_missing_columns_to_overtime_requests_table', 29),
(35, '2026_03_21_164322_add_supervisor_approved_status_to_requests', 30),
(36, '2026_03_21_192731_add_supervisor_approved_to_status_enums', 31),
(37, '2026_03_25_000001_create_notifications_table', 32),
(38, '2026_03_30_000001_create_salary_grades_table', 33),
(39, '2026_03_30_000002_add_salary_grade_to_employees_table', 33),
(40, '2026_03_30_000003_create_payroll_items_table', 33),
(41, '2026_03_30_000004_create_benefits_table', 33),
(42, '2026_03_30_000005_create_payroll_periods_table', 33),
(43, '2026_03_30_000006_create_payslips_table', 33),
(45, '2026_03_30_091307_create_contribution_settings_table', 34),
(46, '2026_03_30_144101_add_released_status_to_payroll_periods_table', 35),
(47, '2026_03_30_144341_add_released_status_to_payslips_table', 36),
(48, '2026_04_08_144547_fix_holidays_table_columns', 37),
(49, '2026_04_14_000001_create_sss_contributions_table', 38),
(50, '2026_04_14_000002_add_pagibig_threshold_and_wtax_bracket5_to_contribution_settings', 38),
(51, '2026_04_16_000001_create_attendance_sessions_table', 39),
(52, '2026_04_17_000001_create_employee_benefits_table', 40),
(53, '2026_04_24_000001_add_missing_statuses_to_attendance_logs', 41),
(54, '2026_04_24_000002_add_late_deduction_to_payslips_table', 42),
(55, '2026_04_24_000003_seed_default_payroll_items', 43),
(56, '2026_04_24_000004_add_other_items_to_payslips_table', 43),
(57, '2026_04_27_000001_create_attendance_adjustment_requests_table', 44),
(58, '2026_04_28_080647_add_flexi_fields_to_shifts_table', 45);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `icon` varchar(255) NOT NULL DEFAULT 'notice',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `icon`, `link`, `is_read`, `created_at`, `updated_at`) VALUES
(1, 20, 'BISAYA', 'BISAYA LANG MALAKAS', 'notice', NULL, 1, '2026-03-25 01:05:26', '2026-03-25 01:05:26'),
(5, 5, 'BISAYA', 'BISAYA LANG MALAKAS', 'notice', NULL, 1, '2026-03-25 01:05:26', '2026-03-25 01:05:26'),
(6, 20, 'TAGALOG', 'TAGALOG LANG MALAKAS', 'notice', NULL, 1, '2026-03-25 01:05:54', '2026-03-25 01:05:54'),
(10, 5, 'TAGALOG', 'TAGALOG LANG MALAKAS', 'notice', NULL, 1, '2026-03-25 01:05:54', '2026-03-25 01:05:54'),
(13, 20, 'Payroll', 'Payroll Released', 'process_done', NULL, 1, '2026-04-08 02:13:21', '2026-04-08 02:13:21'),
(17, 5, 'Payroll', 'Payroll Released', 'process_done', NULL, 1, '2026-04-08 02:13:21', '2026-04-08 02:13:21'),
(20, 20, 'Test', 'Hi', 'warning', NULL, 1, '2026-04-10 00:10:01', '2026-04-10 00:10:01'),
(24, 5, 'Test', 'Hi', 'warning', NULL, 1, '2026-04-10 00:10:01', '2026-04-10 00:10:01'),
(32, 20, 'New Overtime Request', 'Rafael Emp filed an overtime request (OT-0001) on 2026-04-10 (0.48 hrs).', 'notice', NULL, 0, '2026-04-10 00:30:40', '2026-04-10 00:30:40'),
(35, 20, 'TESTING', 'HELLO WORLD\n\n— Sent by Joshuaa M. Adm (Admin)', 'notice', NULL, 0, '2026-04-10 00:46:41', '2026-04-10 00:46:41'),
(40, 5, 'TESTING', 'HELLO WORLD\n\n— Sent by Joshuaa M. Adm (Admin)', 'notice', NULL, 1, '2026-04-10 00:46:41', '2026-04-10 00:46:41'),
(41, 20, 'BISAYA VS TAGALOG', 'D:\n\n— Sent by Joshuaa M. Adm (Admin)', 'warning', NULL, 0, '2026-04-10 00:48:22', '2026-04-10 00:48:22'),
(45, 5, 'BISAYA VS TAGALOG', 'D:\n\n— Sent by Joshuaa M. Adm (Admin)', 'warning', NULL, 1, '2026-04-10 00:48:22', '2026-04-10 00:48:22'),
(205, 1, 'Leave Request Rejected', 'Your leave request (REQ-0003) has been rejected. Reason: dsd', 'warning', NULL, 1, '2026-04-15 12:38:44', '2026-04-15 12:38:44'),
(206, 1, 'New Leave Request', 'Rafael Emp filed a leave request (REQ-0004) from 2026-04-17 to 2026-04-17.', 'notice', NULL, 1, '2026-04-16 00:02:17', '2026-04-16 00:02:17'),
(208, 20, 'New Leave Request', 'Rafael Emp filed a leave request (REQ-0004) from 2026-04-17 to 2026-04-17.', 'notice', NULL, 0, '2026-04-16 00:02:17', '2026-04-16 00:02:17'),
(212, 1, 'New Overtime Request', 'Rafael Emp filed an overtime request (OT-0002) on 2026-04-16 (2.45 hrs).', 'notice', NULL, 1, '2026-04-16 06:24:17', '2026-04-16 06:24:17'),
(215, 20, 'New Overtime Request', 'Rafael Emp filed an overtime request (OT-0002) on 2026-04-16 (2.45 hrs).', 'notice', NULL, 0, '2026-04-16 06:24:17', '2026-04-16 06:24:17'),
(218, 5, 'Payroll Submitted for Approval', 'Payroll period \"Test\" has been submitted by the admin and is awaiting finance approval.', 'notice', NULL, 1, '2026-04-16 19:44:58', '2026-04-16 19:44:58'),
(220, 5, 'Payroll Released', 'Payroll period \"Test\" has been approved and released to employees.', 'process_done', NULL, 1, '2026-04-16 19:56:47', '2026-04-16 19:56:47'),
(222, 1, 'Payroll Submitted for Approval', 'Payroll period \"Test II\" has been submitted and is awaiting your approval.', 'notice', NULL, 0, '2026-04-16 20:15:50', '2026-04-16 20:15:50'),
(223, 5, 'Payroll Submitted for Approval', 'Payroll period \"Test II\" has been submitted and is awaiting your approval.', 'notice', NULL, 1, '2026-04-16 20:15:50', '2026-04-16 20:15:50'),
(225, 1, 'Payroll Released', 'Payroll period \"Test II\" has been approved and released to employees.', 'process_done', NULL, 0, '2026-04-16 20:16:24', '2026-04-16 20:16:24'),
(230, 1, 'Payroll Submitted for Approval', 'Payroll period \"Test\" has been submitted and is awaiting your approval.', 'notice', NULL, 0, '2026-04-20 03:36:24', '2026-04-20 03:36:24'),
(231, 5, 'Payroll Submitted for Approval', 'Payroll period \"Test\" has been submitted and is awaiting your approval.', 'notice', NULL, 1, '2026-04-20 03:36:24', '2026-04-20 03:36:24'),
(232, 114, 'Payroll Submitted for Approval', 'Payroll period \"Test\" has been submitted and is awaiting your approval.', 'notice', NULL, 0, '2026-04-20 03:36:24', '2026-04-20 03:36:24'),
(233, 1, 'Payroll Released', 'Payroll period \"Test\" has been approved and released to employees.', 'process_done', NULL, 0, '2026-04-20 03:42:59', '2026-04-20 03:42:59'),
(235, 114, 'Payroll Released', 'Payroll period \"Test\" has been approved and released to employees.', 'process_done', NULL, 0, '2026-04-20 03:42:59', '2026-04-20 03:42:59'),
(236, 1, 'Break Time', 'It\'s break time! Your break runs until 13:00.', 'notice', NULL, 0, '2026-04-20 04:00:49', '2026-04-20 04:00:49'),
(237, 1, 'Break Over', 'Your break has ended. Time to get back to work!', 'notice', NULL, 0, '2026-04-20 05:00:49', '2026-04-20 05:00:49'),
(238, 1, 'Break Time', 'It\'s break time! Your break runs until 13:00.', 'notice', NULL, 0, '2026-04-21 04:00:56', '2026-04-21 04:00:56'),
(239, 1, 'Break Over', 'Your break has ended. Time to get back to work!', 'notice', NULL, 0, '2026-04-21 05:00:56', '2026-04-21 05:00:56'),
(240, 1, 'Break Time', 'It\'s break time! Your break runs until 13:00.', 'notice', NULL, 0, '2026-04-24 04:00:26', '2026-04-24 04:00:26'),
(241, 1, 'Shift Ended', 'Your shift (Day Shift) has ended. Don\'t forget to clock out!', 'notice', NULL, 0, '2026-04-28 08:00:17', '2026-04-28 08:00:17'),
(242, 1, 'Break Time', 'It\'s break time! Your break runs until 13:00.', 'notice', NULL, 0, '2026-04-29 04:00:24', '2026-04-29 04:00:24'),
(243, 1, 'Break Over', 'Your break has ended. Time to get back to work!', 'notice', NULL, 0, '2026-04-29 05:00:43', '2026-04-29 05:00:43'),
(244, 1, 'Shift Ended', 'Your shift (Day Shift) has ended. Don\'t forget to clock out!', 'notice', NULL, 0, '2026-04-29 08:00:11', '2026-04-29 08:00:11'),
(245, 1, 'Shift Ended', 'Your shift (Day Shift) has ended. Don\'t forget to clock out!', 'notice', NULL, 0, '2026-04-30 08:00:44', '2026-04-30 08:00:44'),
(247, 1, 'Shift Ended', 'Your shift (Day Shift) has ended. Don\'t forget to clock out!', 'notice', NULL, 0, '2026-05-04 08:00:24', '2026-05-04 08:00:24'),
(248, 1, 'Break Time', 'It\'s break time! Your break runs until 13:00.', 'notice', NULL, 0, '2026-05-05 04:00:07', '2026-05-05 04:00:07'),
(249, 1, 'Break Over', 'Your break has ended. Time to get back to work!', 'notice', NULL, 0, '2026-05-05 05:00:06', '2026-05-05 05:00:06'),
(251, 1, 'Shift Ended', 'Your shift (Day Shift) has ended. Don\'t forget to clock out!', 'notice', NULL, 1, '2026-05-06 08:00:00', '2026-05-06 08:00:00'),
(252, 20, 'Hi this is a test', 'Hi this is a test talaga\n\n— Sent by Joshuaa M. Adm (Admin)', 'notice', NULL, 0, '2026-05-06 23:38:07', '2026-05-06 23:38:07'),
(253, 115, 'Hi this is a test', 'Hi this is a test talaga\n\n— Sent by Joshuaa M. Adm (Admin)', 'notice', NULL, 0, '2026-05-06 23:38:07', '2026-05-06 23:38:07'),
(257, 114, 'Hi this is a test', 'Hi this is a test talaga\n\n— Sent by Joshuaa M. Adm (Admin)', 'notice', NULL, 0, '2026-05-06 23:38:07', '2026-05-06 23:38:07'),
(258, 5, 'Hi this is a test', 'Hi this is a test talaga\n\n— Sent by Joshuaa M. Adm (Admin)', 'notice', NULL, 0, '2026-05-06 23:38:07', '2026-05-06 23:38:07'),
(259, 1, 'New Leave Request', 'Zed Pay filed a leave request (REQ-0005) from 2026-05-07 to 2026-05-09.', 'notice', NULL, 1, '2026-05-07 02:28:52', '2026-05-07 02:28:52'),
(261, 114, 'New Leave Request', 'Zed Pay filed a leave request (REQ-0005) from 2026-05-07 to 2026-05-09.', 'notice', NULL, 0, '2026-05-07 02:28:52', '2026-05-07 02:28:52'),
(262, 5, 'Leave Request Approved', 'Your leave request (REQ-0005) from 2026-05-07 00:00:00 to 2026-05-09 00:00:00 has been approved.', 'process_done', NULL, 0, '2026-05-07 02:33:35', '2026-05-07 02:33:35'),
(263, 1, 'New Leave Request', 'Zed Pay filed a leave request (REQ-0006) from 2026-05-15 to 2026-05-17.', 'notice', NULL, 0, '2026-05-07 02:36:05', '2026-05-07 02:36:05'),
(265, 114, 'New Leave Request', 'Zed Pay filed a leave request (REQ-0006) from 2026-05-15 to 2026-05-17.', 'notice', NULL, 0, '2026-05-07 02:36:05', '2026-05-07 02:36:05'),
(266, 1, 'New Leave Request', 'Kene Pay filed a leave request (REQ-0007) from 2026-05-21 to 2026-05-28.', 'notice', NULL, 0, '2026-05-07 02:44:44', '2026-05-07 02:44:44'),
(268, 114, 'New Leave Request', 'Kene Pay filed a leave request (REQ-0007) from 2026-05-21 to 2026-05-28.', 'notice', NULL, 0, '2026-05-07 02:44:44', '2026-05-07 02:44:44'),
(269, 1, 'New Leave Request', 'Rafael Emp filed a leave request (REQ-0008) from 2026-05-21 to 2026-05-28.', 'notice', NULL, 1, '2026-05-07 02:53:20', '2026-05-07 02:53:20'),
(271, 114, 'New Leave Request', 'Rafael Emp filed a leave request (REQ-0008) from 2026-05-21 to 2026-05-28.', 'notice', NULL, 0, '2026-05-07 02:53:20', '2026-05-07 02:53:20'),
(272, 20, 'New Leave Request', 'Rafael Emp filed a leave request (REQ-0008) from 2026-05-21 to 2026-05-28.', 'notice', NULL, 0, '2026-05-07 02:53:20', '2026-05-07 02:53:20'),
(274, 20, 'PLEASE READ THIS', 'I LOVE YOU\n\n— Sent by Joshuaa M. Adm (Admin)', 'warning', NULL, 0, '2026-05-07 06:01:59', '2026-05-07 06:01:59'),
(275, 115, 'PLEASE READ THIS', 'I LOVE YOU\n\n— Sent by Joshuaa M. Adm (Admin)', 'warning', NULL, 0, '2026-05-07 06:01:59', '2026-05-07 06:01:59'),
(276, 2, 'PLEASE READ THIS', 'I LOVE YOU\n\n— Sent by Joshuaa M. Adm (Admin)', 'warning', NULL, 0, '2026-05-07 06:01:59', '2026-05-07 06:01:59'),
(277, 24, 'PLEASE READ THIS', 'I LOVE YOU\n\n— Sent by Joshuaa M. Adm (Admin)', 'warning', NULL, 0, '2026-05-07 06:01:59', '2026-05-07 06:01:59'),
(278, 116, 'PLEASE READ THIS', 'I LOVE YOU\n\n— Sent by Joshuaa M. Adm (Admin)', 'warning', NULL, 0, '2026-05-07 06:01:59', '2026-05-07 06:01:59'),
(279, 6, 'PLEASE READ THIS', 'I LOVE YOU\n\n— Sent by Joshuaa M. Adm (Admin)', 'warning', NULL, 0, '2026-05-07 06:01:59', '2026-05-07 06:01:59'),
(280, 114, 'PLEASE READ THIS', 'I LOVE YOU\n\n— Sent by Joshuaa M. Adm (Admin)', 'warning', NULL, 0, '2026-05-07 06:01:59', '2026-05-07 06:01:59'),
(281, 5, 'PLEASE READ THIS', 'I LOVE YOU\n\n— Sent by Joshuaa M. Adm (Admin)', 'warning', NULL, 0, '2026-05-07 06:01:59', '2026-05-07 06:01:59'),
(282, 1, 'Shift Ended', 'Your shift (Day Shift) has ended. Don\'t forget to clock out!', 'notice', NULL, 0, '2026-05-07 08:00:59', '2026-05-07 08:00:59'),
(283, 2, 'Break Time', 'It\'s break time! Your break runs until 13:00.', 'notice', NULL, 0, '2026-05-08 04:00:11', '2026-05-08 04:00:11'),
(284, 1, 'Break Time', 'It\'s break time! Your break runs until 13:00.', 'notice', NULL, 0, '2026-05-08 04:00:56', '2026-05-08 04:00:56'),
(285, 1, 'Break Over', 'Your break has ended. Time to get back to work!', 'notice', NULL, 0, '2026-05-08 05:00:55', '2026-05-08 05:00:55');

-- --------------------------------------------------------

--
-- Table structure for table `overtime_requests`
--

CREATE TABLE `overtime_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ref_no` varchar(20) NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `attendance_log_id` bigint(20) UNSIGNED DEFAULT NULL,
  `requested_by` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `ot_date` date NOT NULL,
  `ot_start_time` time DEFAULT NULL,
  `ot_end_time` time DEFAULT NULL,
  `requested_hours` decimal(4,2) NOT NULL,
  `approved_hours` decimal(4,2) DEFAULT NULL,
  `reason` text NOT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','supervisor_approved','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `overtime_requests`
--

INSERT INTO `overtime_requests` (`id`, `ref_no`, `employee_id`, `attendance_log_id`, `requested_by`, `approved_by`, `ot_date`, `ot_start_time`, `ot_end_time`, `requested_hours`, `approved_hours`, `reason`, `document_path`, `status`, `approved_at`, `rejection_reason`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'OT-0001', 6, NULL, 6, 2, '2026-04-10', '16:01:00', '16:30:00', 0.48, 0.48, 'OT', NULL, 'approved', '2026-04-10 08:31:45', NULL, NULL, '2026-04-10 00:30:40', '2026-04-10 00:31:45'),
(2, 'OT-0002', 6, NULL, 6, NULL, '2026-04-16', '17:00:00', '19:27:00', 2.45, NULL, 'as', NULL, 'rejected', NULL, 'bad', NULL, '2026-04-16 06:24:17', '2026-04-16 06:24:42');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`email`, `token`, `created_at`) VALUES
('hris@gmail.com', '$2y$12$aPPTxP7kUJfcbvjgx1eFguNLc1DMzV1LzDyNnXoYtDzaQfJPzpXNO', '2026-04-07 01:24:34'),
('johnpaulguzman787@gmail.com', '$2y$12$3m4Z9kd6NpppbByITGOwQOAZB/MKC7miqd7GEwiu3cgFe1iB80GsW', '2026-05-06 23:12:51'),
('joshauxd050@gmail.com', '$2y$12$G4htN2SQ1bqEVJ9tk78xeuPsct2IzUJQXjlwKJhm64I5OnzwnpG1G', '2026-04-16 02:02:09'),
('phoebe@gmail.com', '$2y$12$JWYt/OI6vQFcCLa7BvEkqef3NzkNe3JRMUvOFHg5WUNhzWYRbGkzS', '2026-05-07 03:03:30'),
('phoebehanna04@gmail.com', '$2y$12$h35SOn1IH9xbi.L7YudgLuH1FPGXsnEnE.feskBy6v/WTc20Fb8M2', '2026-05-07 03:05:58'),
('test@gmail.com', '$2y$12$pIaEFs3629ZjmscfGKGbM.2tKRVb/QHlh46Izjp86r7D15XYc8LO2', '2026-04-16 01:50:40'),
('test@mailinator.com', '$2y$12$XBcsCubNONeuJ.y4bDoEuOKaOdbFxh7ldi6geatgdhIQzRMM/FmMe', '2026-04-19 23:22:27'),
('zionxd030@gmail.com', '$2y$12$2boETFFjFQedXs5s.E.cGeODz/fjnqbF8N7Qp0rMT36uNLVoTz0W.', '2026-04-17 14:07:04');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_items`
--

CREATE TABLE `payroll_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `multiplier` decimal(6,2) NOT NULL DEFAULT 1.00,
  `type` enum('Addition','Deduction') NOT NULL,
  `basis` varchar(255) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_items`
--

INSERT INTO `payroll_items` (`id`, `name`, `multiplier`, `type`, `basis`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Regular OT', 1.25, 'Addition', 'Per hour', 'Active', '2026-03-30 02:14:41', '2026-03-30 05:35:43'),
(2, 'Late Deduction', 1.00, 'Deduction', 'Hourly Rate', 'Active', '2026-04-24 05:33:07', '2026-04-24 05:33:07');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_periods`
--

CREATE TABLE `payroll_periods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `payout_date` date NOT NULL,
  `status` enum('Pending','Submitted','Released') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_periods`
--

INSERT INTO `payroll_periods` (`id`, `name`, `start_date`, `end_date`, `payout_date`, `status`, `created_at`, `updated_at`) VALUES
(14, 'Test Payroll This today', '2026-05-01', '2026-05-15', '2026-05-16', 'Released', '2026-04-20 03:25:01', '2026-05-05 08:38:20'),
(15, 'qwe', '2026-05-18', '2026-05-30', '2026-05-31', 'Pending', '2026-04-20 03:27:30', '2026-04-24 04:52:57'),
(16, 'April Period 3', '2026-04-17', '2026-05-01', '2026-05-05', 'Pending', '2026-05-03 23:26:56', '2026-05-03 23:26:56');

-- --------------------------------------------------------

--
-- Table structure for table `payslips`
--

CREATE TABLE `payslips` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payroll_period_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `basic_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `ot_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `benefits_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `other_additions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gross_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sss` decimal(12,2) NOT NULL DEFAULT 0.00,
  `philhealth` decimal(12,2) NOT NULL DEFAULT 0.00,
  `pagibig` decimal(12,2) NOT NULL DEFAULT 0.00,
  `late_deduction` decimal(12,2) NOT NULL DEFAULT 0.00,
  `other_deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `withholding_tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('Pending','Submitted','Released') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payslips`
--

INSERT INTO `payslips` (`id`, `payroll_period_id`, `employee_id`, `basic_pay`, `ot_pay`, `benefits_total`, `other_additions`, `gross_pay`, `sss`, `philhealth`, `pagibig`, `late_deduction`, `other_deductions`, `withholding_tax`, `total_deductions`, `net_pay`, `status`, `created_at`, `updated_at`) VALUES
(76, 14, 5, 13500.00, 0.00, 200.00, 0.00, 13700.00, 500.00, 337.50, 100.00, 0.00, 0.00, 351.88, 1289.38, 12410.62, 'Released', '2026-04-20 03:25:01', '2026-04-20 03:42:59'),
(77, 14, 6, 11000.00, 0.00, 200.00, 0.00, 11200.00, 500.00, 275.00, 100.00, 0.00, 0.00, 0.00, 875.00, 10325.00, 'Released', '2026-04-20 03:25:01', '2026-04-20 03:42:59'),
(78, 14, 18, 13500.00, 0.00, 200.00, 0.00, 13700.00, 500.00, 337.50, 100.00, 0.00, 0.00, 351.88, 1289.38, 12410.62, 'Released', '2026-04-20 03:25:01', '2026-04-20 03:42:59'),
(79, 15, 5, 13500.00, 0.00, 200.00, 0.00, 13700.00, 500.00, 337.50, 100.00, 0.00, 0.00, 351.88, 1289.38, 12410.62, 'Submitted', '2026-04-20 03:27:30', '2026-05-05 03:27:45'),
(80, 15, 6, 11000.00, 0.00, 200.00, 0.00, 11200.00, 500.00, 275.00, 100.00, 0.00, 0.00, 0.00, 875.00, 10325.00, 'Pending', '2026-04-20 03:27:30', '2026-04-20 03:27:30'),
(81, 15, 18, 13500.00, 0.00, 200.00, 0.00, 13700.00, 500.00, 337.50, 100.00, 0.00, 0.00, 351.88, 1289.38, 12410.62, 'Pending', '2026-04-20 03:27:30', '2026-04-20 03:27:30'),
(82, 16, 5, 13500.00, 0.00, 200.00, 0.00, 13700.00, 500.00, 337.50, 100.00, 0.00, 0.00, 351.88, 1289.38, 12410.62, 'Pending', '2026-05-03 23:26:56', '2026-05-03 23:26:56'),
(83, 16, 6, 11000.00, 0.00, 200.00, 0.00, 11200.00, 500.00, 275.00, 100.00, 611.11, 0.00, 0.00, 1486.11, 9713.89, 'Pending', '2026-05-03 23:26:56', '2026-05-03 23:26:56'),
(84, 16, 18, 13500.00, 0.00, 200.00, 0.00, 13700.00, 500.00, 337.50, 100.00, 0.00, 0.00, 351.88, 1289.38, 12410.62, 'Pending', '2026-05-03 23:26:56', '2026-05-03 23:26:56');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role` varchar(255) NOT NULL,
  `module` varchar(255) NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 0,
  `can_create` tinyint(1) NOT NULL DEFAULT 0,
  `can_edit` tinyint(1) NOT NULL DEFAULT 0,
  `can_archive` tinyint(1) NOT NULL DEFAULT 0,
  `can_import` tinyint(1) NOT NULL DEFAULT 0,
  `can_export` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `role`, `module`, `can_view`, `can_create`, `can_edit`, `can_archive`, `can_import`, `can_export`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'Employee Management', 1, 1, 1, 0, 1, 1, '2026-03-15 06:32:03', '2026-03-15 06:32:03'),
(2, 'hr_manager', 'Employee Management', 1, 1, 1, 0, 0, 0, '2026-03-15 06:32:03', '2026-05-11 23:22:36'),
(3, 'supervisor', 'Employee Management', 1, 1, 1, 0, 0, 0, '2026-03-15 06:32:03', '2026-04-27 00:56:14'),
(4, 'employee', 'Employee Management', 1, 0, 0, 0, 0, 0, '2026-03-15 06:32:03', '2026-04-27 00:55:33'),
(5, 'admin', 'Time & Attendance', 1, 1, 1, 0, 1, 1, '2026-03-22 11:53:43', '2026-03-22 11:53:43'),
(6, 'admin', 'Leave Management', 1, 1, 1, 0, 0, 0, '2026-03-22 11:53:43', '2026-03-22 11:53:43'),
(7, 'admin', 'Requests & Approval', 1, 1, 1, 0, 0, 0, '2026-03-22 11:53:43', '2026-03-22 11:53:43'),
(8, 'hr_manager', 'Time & Attendance', 1, 1, 1, 0, 1, 1, '2026-03-22 11:53:43', '2026-03-22 11:53:43'),
(9, 'hr_manager', 'Leave Management', 1, 1, 1, 0, 0, 1, '2026-03-22 11:53:43', '2026-04-24 02:12:20'),
(10, 'hr_manager', 'Requests & Approval', 1, 1, 1, 0, 0, 0, '2026-03-22 11:53:43', '2026-03-22 11:53:43'),
(11, 'supervisor', 'Time & Attendance', 1, 1, 1, 0, 0, 1, '2026-03-22 11:53:43', '2026-04-27 00:56:10'),
(12, 'supervisor', 'Leave Management', 1, 1, 1, 0, 0, 1, '2026-03-22 11:53:43', '2026-04-24 02:12:21'),
(13, 'supervisor', 'Requests & Approval', 1, 1, 1, 0, 0, 0, '2026-03-22 11:53:43', '2026-03-22 11:53:43'),
(14, 'employee', 'Time & Attendance', 1, 1, 0, 0, 0, 0, '2026-03-22 11:53:43', '2026-03-22 11:53:43'),
(15, 'employee', 'Leave Management', 1, 1, 0, 0, 0, 0, '2026-03-22 11:53:43', '2026-03-22 11:53:43'),
(16, 'employee', 'Requests & Approval', 1, 1, 0, 0, 0, 0, '2026-03-22 11:53:43', '2026-03-22 11:53:43'),
(17, 'payroll_officer', 'Employee Management', 1, 0, 0, 0, 0, 0, '2026-03-22 11:53:43', '2026-04-27 00:55:32'),
(18, 'payroll_officer', 'Time & Attendance', 1, 1, 0, 0, 0, 1, '2026-03-22 11:53:43', '2026-03-22 11:53:43'),
(19, 'payroll_officer', 'Leave Management', 1, 1, 0, 0, 0, 1, '2026-03-22 11:53:43', '2026-04-24 02:12:21'),
(20, 'payroll_officer', 'Requests & Approval', 1, 1, 0, 0, 0, 0, '2026-03-22 11:53:43', '2026-03-22 11:53:43'),
(21, 'finance_officer', 'Employee Management', 1, 0, 0, 0, 0, 0, '2026-03-22 11:53:43', '2026-04-27 00:55:31'),
(22, 'finance_officer', 'Time & Attendance', 1, 1, 0, 0, 0, 1, '2026-03-22 11:53:43', '2026-03-22 11:53:43'),
(23, 'finance_officer', 'Leave Management', 1, 1, 0, 0, 0, 1, '2026-03-22 11:53:43', '2026-04-24 02:12:22'),
(24, 'finance_officer', 'Requests & Approval', 1, 1, 0, 0, 0, 0, '2026-03-22 11:53:43', '2026-03-22 11:53:43'),
(25, 'admin', 'Payroll', 1, 1, 1, 0, 0, 1, '2026-04-10 05:35:20', '2026-04-10 05:35:20'),
(26, 'hr_manager', 'Payroll', 1, 0, 0, 0, 0, 1, '2026-04-10 05:35:20', '2026-04-10 05:35:20'),
(27, 'supervisor', 'Payroll', 1, 0, 0, 0, 0, 0, '2026-04-10 05:35:20', '2026-04-10 05:35:20'),
(28, 'payroll_officer', 'Payroll', 1, 1, 0, 0, 0, 1, '2026-04-10 05:35:20', '2026-04-10 05:35:20'),
(29, 'finance_officer', 'Payroll', 1, 0, 1, 0, 0, 1, '2026-04-10 05:35:20', '2026-04-10 05:35:20'),
(30, 'employee', 'Payroll', 1, 0, 0, 0, 0, 0, '2026-04-10 05:35:20', '2026-04-10 05:35:20');

-- --------------------------------------------------------

--
-- Table structure for table `salary_grades`
--

CREATE TABLE `salary_grades` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `grade_code` varchar(255) NOT NULL,
  `level_name` varchar(255) NOT NULL,
  `monthly_basic_salary` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `salary_grades`
--

INSERT INTO `salary_grades` (`id`, `grade_code`, `level_name`, `monthly_basic_salary`, `created_at`, `updated_at`) VALUES
(1, 'Grade 1', 'Entry Level', 22000.00, '2026-03-30 05:39:44', '2026-03-30 05:39:44'),
(2, 'Grade 2', 'Junior Level', 27000.00, '2026-04-01 00:28:35', '2026-04-01 00:28:35');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('nUiXFwvqRmQnZYQTVGMBBGpFqSiMZMRMofGiLQ0w', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiaW1OUzlCN0xucEN4ekdmcXBza0E4RlZGbGtFZnZNUzNNeklUTDlaMSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fX0=', 1777337855);

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_flexi` tinyint(1) NOT NULL DEFAULT 0,
  `required_hours` decimal(4,2) DEFAULT NULL,
  `break_schedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`break_schedule`)),
  `description` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shifts`
--

INSERT INTO `shifts` (`id`, `name`, `code`, `start_time`, `end_time`, `is_active`, `is_flexi`, `required_hours`, `break_schedule`, `description`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Day Shift', 'DS-001', '07:00:00', '16:00:00', 1, 0, NULL, '{\"start\":\"12:00\",\"end\":\"13:00\"}', NULL, NULL, '2026-03-10 18:28:44', '2026-04-15 07:02:32'),
(2, 'Night Shift', 'NS-001', '22:00:00', '06:00:00', 1, 0, NULL, NULL, NULL, NULL, '2026-03-10 19:15:54', '2026-03-10 19:15:54'),
(4, 'Flexible Schedule', 'FLEXI', '08:00:00', '17:00:00', 1, 1, 8.00, NULL, 'Flexible schedule — no fixed start/end time. Status is based on total hours worked vs required hours.', NULL, '2026-04-28 00:07:26', '2026-04-28 00:52:43');

-- --------------------------------------------------------

--
-- Table structure for table `shift_change_requests`
--

CREATE TABLE `shift_change_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `current_shift_id` bigint(20) UNSIGNED DEFAULT NULL,
  `requested_shift_id` bigint(20) UNSIGNED NOT NULL,
  `ref_no` varchar(20) NOT NULL,
  `work_setup` varchar(10) DEFAULT NULL,
  `effective_from` date NOT NULL,
  `effective_until` date DEFAULT NULL,
  `reason` text NOT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','supervisor_approved','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sss_contributions`
--

CREATE TABLE `sss_contributions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `salary_from` decimal(10,2) NOT NULL,
  `salary_to` decimal(10,2) DEFAULT NULL,
  `employee_share` decimal(10,2) NOT NULL,
  `employer_share` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sss_contributions`
--

INSERT INTO `sss_contributions` (`id`, `salary_from`, `salary_to`, `employee_share`, `employer_share`, `created_at`, `updated_at`) VALUES
(1, 0.00, 5249.99, 250.00, 528.00, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(2, 5250.00, 5749.99, 275.00, 580.56, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(3, 5750.00, 6249.99, 300.00, 633.33, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(4, 6250.00, 6749.99, 325.00, 686.11, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(5, 6750.00, 7249.99, 350.00, 738.89, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(6, 7250.00, 7749.99, 375.00, 791.67, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(7, 7750.00, 8249.99, 400.00, 844.44, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(8, 8250.00, 8749.99, 425.00, 897.22, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(9, 8750.00, 9249.99, 450.00, 950.00, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(10, 9250.00, 9749.99, 475.00, 1002.78, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(11, 9750.00, 10249.99, 500.00, 1055.56, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(12, 10250.00, 10749.99, 525.00, 1108.33, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(13, 10750.00, 11249.99, 550.00, 1161.11, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(14, 11250.00, 11749.99, 575.00, 1213.89, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(15, 11750.00, 12249.99, 600.00, 1266.67, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(16, 12250.00, 12749.99, 625.00, 1319.44, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(17, 12750.00, 13249.99, 650.00, 1372.22, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(18, 13250.00, 13749.99, 675.00, 1425.00, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(19, 13750.00, 14249.99, 700.00, 1477.78, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(20, 14250.00, 14749.99, 725.00, 1530.56, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(21, 14750.00, 15249.99, 750.00, 1583.33, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(22, 15250.00, 15749.99, 775.00, 1636.11, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(23, 15750.00, 16249.99, 800.00, 1688.89, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(24, 16250.00, 16749.99, 825.00, 1741.67, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(25, 16750.00, 17249.99, 850.00, 1794.44, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(26, 17250.00, 17749.99, 875.00, 1847.22, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(27, 17750.00, 18249.99, 900.00, 1900.00, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(28, 18250.00, 18749.99, 925.00, 1952.78, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(29, 18750.00, 19249.99, 950.00, 2005.56, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(30, 19250.00, 19749.99, 975.00, 2058.33, '2026-04-20 02:19:14', '2026-04-20 02:19:14'),
(31, 19750.00, NULL, 1000.00, 2111.11, '2026-04-20 02:19:14', '2026-04-20 02:19:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'employee',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `email_verified_at`, `password`, `role`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'joshuaxd050@gmail.com', '2026-03-05 16:28:54', '$2y$12$ETuPGa4DwFGm3q8o2kf9HuZ1Y0ilO.lGJZYkJsoXAqaHXPr2NzYq2', 'admin', NULL, '2026-03-01 21:53:44', '2026-04-27 00:57:41'),
(2, 'joshua.developerr@gmail.com', '2026-03-05 16:28:54', '$2y$12$eS9sDIeMZR2b/AgYUTemxeLqn0g/wyqM8oy8RKrL9Ob8NWR6b.z0C', 'hr_manager', NULL, '2026-03-01 21:53:45', '2026-04-20 11:28:50'),
(5, 'zedxkenn@gmail.com', '2026-03-05 16:28:54', '$2y$12$KnI5OlRZYnWczgbPoO3pGevSkvcglsDZnYzPKVcAbYt9MDKVkBNai', 'finance_officer', NULL, '2026-03-01 21:53:45', '2026-03-22 11:38:30'),
(6, 'rxd43524@gmail.com', '2026-03-05 16:28:54', '$2y$12$yu7D5Xk1epCK0icbM8mLZO3Td17UrNFEtrF0MWb/JybHyqYPYalhC', 'employee', NULL, '2026-03-01 21:53:53', '2026-03-05 16:28:54'),
(20, 'dazzledev23@gmail.com', '2026-03-05 17:41:36', '$2y$12$AFaK6.5oehgHPQUILU/nseO6aHdhW8T1o0Q2eCjwBLb03zSJH33Pe', 'supervisor', NULL, '2026-03-05 17:41:13', '2026-03-05 17:42:19'),
(24, 'keenmagalong123@gmail.com', '2026-03-22 09:01:58', '$2y$12$qC6Jc06enzRvAosqOFn.4Oll..i654GSi5Pgcc8fj/ArZBi7Jo8Ma', 'payroll_officer', NULL, '2026-03-17 01:45:27', '2026-03-22 09:02:46'),
(114, 'test@mailinator.com', '2026-04-28 00:41:57', '$2y$12$Neqp7f35khXmxNsfek7ZMeootd5ZdARRoSvU26FbjDFSdL9WU/74u', 'admin', NULL, '2026-04-19 23:01:44', '2026-04-19 23:01:44'),
(115, 'johnpaulguzman787@gmail.com', NULL, '$2y$12$7/BL7GnwLf4h8uvkEZmIiObpjuFMbLFBe9mFVHx0d5mnWxcc6iIcm', 'employee', NULL, '2026-05-06 23:12:50', '2026-05-06 23:12:50'),
(116, 'phoebehanna04@gmail.com', NULL, '$2y$12$zTUSV84m5FMVi0ejhQM0lOF8m0QeX2SWAotkg8JSNR22LEI1VJWPG', 'supervisor', NULL, '2026-05-07 03:03:28', '2026-05-07 03:04:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance_adjustment_requests`
--
ALTER TABLE `attendance_adjustment_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attendance_adjustment_requests_ref_no_unique` (`ref_no`),
  ADD KEY `attendance_adjustment_requests_employee_id_foreign` (`employee_id`),
  ADD KEY `attendance_adjustment_requests_attendance_log_id_foreign` (`attendance_log_id`),
  ADD KEY `attendance_adjustment_requests_supervisor_approved_by_foreign` (`supervisor_approved_by`),
  ADD KEY `attendance_adjustment_requests_approved_by_foreign` (`approved_by`),
  ADD KEY `attendance_adjustment_requests_rejected_by_foreign` (`rejected_by`);

--
-- Indexes for table `attendance_corrections`
--
ALTER TABLE `attendance_corrections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attendance_logs_employee_id_attendance_date_unique` (`employee_id`,`attendance_date`),
  ADD KEY `attendance_logs_shift_id_foreign` (`shift_id`),
  ADD KEY `attendance_logs_holiday_id_foreign` (`holiday_id`);

--
-- Indexes for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendance_sessions_attendance_log_id_foreign` (`attendance_log_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_user_id_foreign` (`user_id`);

--
-- Indexes for table `benefits`
--
ALTER TABLE `benefits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `contribution_settings`
--
ALTER TABLE `contribution_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `contribution_settings_key_unique` (`key`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `documents_employee_id_foreign` (`employee_id`),
  ADD KEY `documents_uploaded_by_foreign` (`uploaded_by`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employees_employee_code_unique` (`employee_code`),
  ADD KEY `employees_user_id_foreign` (`user_id`),
  ADD KEY `employees_department_id_foreign` (`department_id`),
  ADD KEY `employees_job_title_id_foreign` (`job_title_id`),
  ADD KEY `employees_salary_grade_id_foreign` (`salary_grade_id`);

--
-- Indexes for table `employee_benefits`
--
ALTER TABLE `employee_benefits`
  ADD PRIMARY KEY (`employee_id`,`benefit_id`),
  ADD KEY `employee_benefits_benefit_id_foreign` (`benefit_id`);

--
-- Indexes for table `employee_shifts`
--
ALTER TABLE `employee_shifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_shifts_employee_id_foreign` (`employee_id`),
  ADD KEY `employee_shifts_shift_id_foreign` (`shift_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_titles`
--
ALTER TABLE `job_titles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_titles_department_id_foreign` (`department_id`);

--
-- Indexes for table `leave_credits`
--
ALTER TABLE `leave_credits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_credits_employee_id_leave_type_id_year_unique` (`employee_id`,`leave_type_id`,`year`),
  ADD KEY `leave_credits_leave_type_id_foreign` (`leave_type_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_requests_ref_no_unique` (`ref_no`),
  ADD KEY `leave_requests_employee_id_foreign` (`employee_id`),
  ADD KEY `leave_requests_leave_type_id_foreign` (`leave_type_id`),
  ADD KEY `leave_requests_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_types_code_unique` (`code`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_user_id_is_read_index` (`user_id`,`is_read`);

--
-- Indexes for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `overtime_requests_ref_no_unique` (`ref_no`),
  ADD KEY `overtime_requests_employee_id_foreign` (`employee_id`),
  ADD KEY `overtime_requests_attendance_log_id_foreign` (`attendance_log_id`),
  ADD KEY `overtime_requests_requested_by_foreign` (`requested_by`),
  ADD KEY `overtime_requests_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payroll_items`
--
ALTER TABLE `payroll_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payslips`
--
ALTER TABLE `payslips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payslips_payroll_period_id_employee_id_unique` (`payroll_period_id`,`employee_id`),
  ADD KEY `payslips_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_role_module_unique` (`role`,`module`);

--
-- Indexes for table `salary_grades`
--
ALTER TABLE `salary_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `salary_grades_grade_code_unique` (`grade_code`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shifts_code_unique` (`code`);

--
-- Indexes for table `shift_change_requests`
--
ALTER TABLE `shift_change_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shift_change_requests_ref_no_unique` (`ref_no`),
  ADD KEY `shift_change_requests_employee_id_foreign` (`employee_id`),
  ADD KEY `shift_change_requests_current_shift_id_foreign` (`current_shift_id`),
  ADD KEY `shift_change_requests_requested_shift_id_foreign` (`requested_shift_id`),
  ADD KEY `shift_change_requests_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `sss_contributions`
--
ALTER TABLE `sss_contributions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance_adjustment_requests`
--
ALTER TABLE `attendance_adjustment_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_corrections`
--
ALTER TABLE `attendance_corrections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `benefits`
--
ALTER TABLE `benefits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contribution_settings`
--
ALTER TABLE `contribution_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `employee_shifts`
--
ALTER TABLE `employee_shifts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_titles`
--
ALTER TABLE `job_titles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `leave_credits`
--
ALTER TABLE `leave_credits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=286;

--
-- AUTO_INCREMENT for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payroll_items`
--
ALTER TABLE `payroll_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `payslips`
--
ALTER TABLE `payslips`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `salary_grades`
--
ALTER TABLE `salary_grades`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `shift_change_requests`
--
ALTER TABLE `shift_change_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sss_contributions`
--
ALTER TABLE `sss_contributions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_adjustment_requests`
--
ALTER TABLE `attendance_adjustment_requests`
  ADD CONSTRAINT `attendance_adjustment_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `attendance_adjustment_requests_attendance_log_id_foreign` FOREIGN KEY (`attendance_log_id`) REFERENCES `attendance_logs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `attendance_adjustment_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_adjustment_requests_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `attendance_adjustment_requests_supervisor_approved_by_foreign` FOREIGN KEY (`supervisor_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `attendance_logs_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_logs_holiday_id_foreign` FOREIGN KEY (`holiday_id`) REFERENCES `holidays` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `attendance_logs_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD CONSTRAINT `attendance_sessions_attendance_log_id_foreign` FOREIGN KEY (`attendance_log_id`) REFERENCES `attendance_logs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employees_job_title_id_foreign` FOREIGN KEY (`job_title_id`) REFERENCES `job_titles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employees_salary_grade_id_foreign` FOREIGN KEY (`salary_grade_id`) REFERENCES `salary_grades` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_benefits`
--
ALTER TABLE `employee_benefits`
  ADD CONSTRAINT `employee_benefits_benefit_id_foreign` FOREIGN KEY (`benefit_id`) REFERENCES `benefits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_benefits_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_shifts`
--
ALTER TABLE `employee_shifts`
  ADD CONSTRAINT `employee_shifts_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_shifts_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job_titles`
--
ALTER TABLE `job_titles`
  ADD CONSTRAINT `job_titles_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_credits`
--
ALTER TABLE `leave_credits`
  ADD CONSTRAINT `leave_credits_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_credits_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leave_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_requests_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  ADD CONSTRAINT `overtime_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `overtime_requests_attendance_log_id_foreign` FOREIGN KEY (`attendance_log_id`) REFERENCES `attendance_logs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `overtime_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `overtime_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payslips`
--
ALTER TABLE `payslips`
  ADD CONSTRAINT `payslips_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payslips_payroll_period_id_foreign` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shift_change_requests`
--
ALTER TABLE `shift_change_requests`
  ADD CONSTRAINT `shift_change_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `shift_change_requests_current_shift_id_foreign` FOREIGN KEY (`current_shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `shift_change_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shift_change_requests_requested_shift_id_foreign` FOREIGN KEY (`requested_shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
