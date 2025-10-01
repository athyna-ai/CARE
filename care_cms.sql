-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 01, 2025 at 09:17 AM
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
-- Database: `care_cms`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `user_type` enum('admin','student','faculty','system') DEFAULT 'admin',
  `action` varchar(64) NOT NULL,
  `description` text DEFAULT NULL,
  `action_description` text DEFAULT NULL,
  `location` varchar(100) NOT NULL,
  `rfid_used` varchar(50) DEFAULT NULL,
  `success` tinyint(1) DEFAULT 1,
  `error_message` text DEFAULT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `created_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `user_type`, `action`, `description`, `action_description`, `location`, `rfid_used`, `success`, `error_message`, `session_id`, `timestamp`, `ip_address`, `user_agent`, `created_timestamp`, `archived`) VALUES
(281, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 10:15:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 10:15:15', 1),
(282, NULL, 'admin', 'visitation_logged', 'Added visitation record for patient ID 22 - Reason: cold', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-21 10:17:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 10:17:27', 1),
(283, NULL, 'admin', 'visitation_logged', 'Added visitation record for patient ID 22 - Reason: stomach_ache', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-21 10:17:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 10:17:31', 1),
(284, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 10:17:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 10:17:41', 1),
(285, NULL, 'admin', 'login', 'Admin login', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-21 11:21:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 11:21:43', 1),
(286, NULL, 'admin', 'logout', 'Admin logout', NULL, 'auth/logout', NULL, 1, NULL, NULL, '2025-09-21 11:32:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 11:32:11', 1),
(287, NULL, 'admin', 'login', 'Admin login', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-21 11:32:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 11:32:14', 1),
(288, NULL, 'admin', 'logout', 'Admin logout', NULL, 'auth/logout', NULL, 1, NULL, NULL, '2025-09-21 11:32:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 11:32:51', 1),
(289, NULL, 'admin', 'login', 'Admin login', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-21 11:34:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 11:34:45', 1),
(290, NULL, 'admin', 'unauthorized_access', 'Unauthorized access attempt to: ', NULL, 'auth/unauthorized', NULL, 1, NULL, NULL, '2025-09-21 11:37:05', '', '', '2025-09-21 11:37:05', 1),
(291, NULL, 'admin', 'unauthorized_access', 'Unauthorized access attempt to: ', NULL, 'auth/unauthorized', NULL, 1, NULL, NULL, '2025-09-21 11:38:38', '', '', '2025-09-21 11:38:38', 1),
(292, NULL, 'admin', 'unauthorized_access', 'Unauthorized access attempt to: ', NULL, 'auth/unauthorized', NULL, 1, NULL, NULL, '2025-09-21 11:39:47', '', '', '2025-09-21 11:39:47', 1),
(293, NULL, 'admin', 'student_register', 'Registered new student: Thena- (Senior High School)', NULL, 'student_form', NULL, 1, NULL, NULL, '2025-09-21 11:42:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 11:42:37', 1),
(294, NULL, 'admin', 'unauthorized_access', 'Unauthorized access attempt to: ', NULL, 'auth/unauthorized', NULL, 1, NULL, NULL, '2025-09-21 11:47:42', '', '', '2025-09-21 11:47:42', 1),
(295, NULL, 'admin', 'unauthorized_access', 'Unauthorized access attempt to: ', NULL, 'auth/unauthorized', NULL, 1, NULL, NULL, '2025-09-21 11:57:34', '', '', '2025-09-21 11:57:34', 1),
(296, NULL, 'admin', 'logout', 'Admin logout', NULL, 'auth/logout', NULL, 1, NULL, NULL, '2025-09-21 12:02:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 12:02:02', 1),
(297, NULL, 'admin', 'login', 'Admin login', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-21 12:02:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 12:02:05', 1),
(298, NULL, 'admin', 'unauthorized_access', 'Unauthorized access attempt to: ', NULL, 'auth/unauthorized', NULL, 1, NULL, NULL, '2025-09-21 12:02:46', '', '', '2025-09-21 12:02:46', 1),
(299, NULL, 'admin', 'logout', 'Admin logout', NULL, 'auth/logout', NULL, 1, NULL, NULL, '2025-09-21 12:02:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 12:02:58', 1),
(300, NULL, 'admin', 'login', 'Admin login', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-21 12:03:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 12:03:00', 1),
(301, NULL, 'admin', 'unauthorized_access', 'Unauthorized access attempt to: ', NULL, 'auth/unauthorized', NULL, 1, NULL, NULL, '2025-09-21 12:03:32', '', '', '2025-09-21 12:03:32', 1),
(302, NULL, 'admin', 'student_register', 'Registered new student: teena (College)', NULL, 'student_form', NULL, 1, NULL, NULL, '2025-09-21 13:32:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 13:32:42', 1),
(303, NULL, 'admin', 'medical_history_created', 'Created medical history form for patient ID 22', NULL, 'medical/history', NULL, 1, NULL, NULL, '2025-09-21 13:33:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 13:33:53', 1),
(304, NULL, 'admin', 'visitation_logged', 'Added visitation record for patient ID 22 - Reason: cold', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-21 13:34:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 13:34:14', 1),
(305, NULL, 'admin', 'archive_visitation', 'Archived visitation record #56 for Student ID 22', NULL, 'patient_view.php', NULL, 1, NULL, NULL, '2025-09-21 13:35:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 13:35:19', 1),
(306, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 13:48:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 13:48:30', 1),
(307, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 13:51:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 13:51:10', 1),
(308, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 13:57:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 13:57:52', 1),
(309, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 13:59:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 13:59:36', 1),
(310, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 13:59:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 13:59:48', 1),
(311, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 14:01:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:01:20', 1),
(312, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 14:02:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:02:47', 1),
(313, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 14:04:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:04:21', 1),
(314, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 14:05:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:05:55', 1),
(315, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 14:06:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:06:10', 1),
(316, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 14:06:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:06:49', 1),
(317, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 14:07:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:07:06', 1),
(318, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 14:08:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:08:32', 1),
(319, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 14:08:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:08:42', 1),
(320, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 14:10:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:10:14', 1),
(321, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 14:11:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:11:11', 1),
(322, NULL, 'admin', 'student_archived', 'Archived student: Angelic Ta (Senior High School)', NULL, 'archive_student', NULL, 1, NULL, NULL, '2025-09-21 14:37:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:37:10', 1),
(323, NULL, 'admin', 'student_archived', 'Archived student: Angelic Taw (High School)', NULL, 'archive_student', NULL, 1, NULL, NULL, '2025-09-21 14:41:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:41:51', 1),
(324, NULL, 'admin', 'student_archived', 'Archived student: Thena- (High School)', NULL, 'archive_student', NULL, 1, NULL, NULL, '2025-09-21 14:43:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:43:16', 1),
(325, NULL, 'admin', 'student_archived', 'Archived student: tuuuna (High School)', NULL, 'archive_student', NULL, 1, NULL, NULL, '2025-09-21 14:43:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:43:20', 1),
(326, NULL, 'admin', 'student_restored', 'Restored student: tuuuna (High School)', NULL, 'restore_record', NULL, 1, NULL, NULL, '2025-09-21 14:44:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:44:16', 1),
(327, NULL, 'admin', 'student_restored', 'Restored student: Thena- (High School)', NULL, 'restore_record', NULL, 1, NULL, NULL, '2025-09-21 14:44:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:44:19', 1),
(328, NULL, 'admin', 'student_restored', 'Restored student: Angelic Taw (High School)', NULL, 'restore_record', NULL, 1, NULL, NULL, '2025-09-21 14:44:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:44:21', 1),
(329, NULL, 'admin', 'student_restored', 'Restored student: Angelic Ta (Senior High School)', NULL, 'restore_record', NULL, 1, NULL, NULL, '2025-09-21 14:44:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:44:23', 1),
(330, NULL, 'admin', 'student_archived', 'Archived student: Angelic Taw (High School)', NULL, 'archive_student', NULL, 1, NULL, NULL, '2025-09-21 14:45:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:45:22', 1),
(331, NULL, 'admin', 'student_archived', 'Archived student: teena (College)', NULL, 'archive_student', NULL, 1, NULL, NULL, '2025-09-21 14:48:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:48:24', 1),
(332, NULL, 'admin', 'update_patient', 'Updated student information for patient ID: 6', NULL, 'patient_view', NULL, 1, NULL, NULL, '2025-09-21 14:58:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 14:58:32', 1),
(333, NULL, 'admin', 'update_patient', 'Updated student information for patient ID: 28', NULL, 'patient_view', NULL, 1, NULL, NULL, '2025-09-21 15:00:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 15:00:13', 1),
(334, NULL, 'admin', 'visitation_logged', 'Added visitation record for patient ID 28 - Reason: cold', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-21 15:00:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 15:00:37', 1),
(335, NULL, 'admin', 'archive_visitation', 'Archived visitation record #57 for Student ID 28', NULL, 'patient_view.php', NULL, 1, NULL, NULL, '2025-09-21 15:00:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 15:00:39', 1),
(336, NULL, 'admin', 'restore_visitation', 'Restored archived visitation record #15 (now #58) for Student ID 28', NULL, 'patient_archive.php', NULL, 1, NULL, NULL, '2025-09-21 15:02:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 15:02:04', 1),
(337, NULL, 'admin', 'unauthorized_access', 'Unauthorized access attempt to: /Care/admin/dashboard.php', NULL, 'auth/unauthorized', NULL, 1, NULL, NULL, '2025-09-21 16:15:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 16:15:46', 1),
(338, NULL, 'admin', 'login', 'Admin login', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-21 16:15:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 16:15:57', 1),
(339, NULL, 'admin', 'login', 'Admin login', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-21 17:13:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 17:13:04', 1),
(340, NULL, 'admin', 'student_archived', 'Archived student: dadadad (Elementary)', NULL, 'archive_student', NULL, 1, NULL, NULL, '2025-09-21 17:13:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 17:13:08', 1),
(341, NULL, 'admin', 'student_register', 'Registered new student: sampleee (Elementary)', NULL, 'student_form', NULL, 1, NULL, NULL, '2025-09-21 17:13:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 17:13:58', 1),
(342, NULL, 'admin', 'medical_history_created', 'Created medical history form for patient ID 29', NULL, 'medical/history', NULL, 1, NULL, NULL, '2025-09-21 17:14:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 17:14:18', 1),
(343, NULL, 'admin', 'visitation_logged', 'Added visitation record for patient ID 29 - Reason: headache', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-21 17:14:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 17:14:35', 1),
(344, NULL, 'admin', 'archive_visitation', 'Archived visitation record #59 for Student ID 29', NULL, 'patient_view.php', NULL, 1, NULL, NULL, '2025-09-21 17:14:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 17:14:46', 1),
(345, NULL, 'admin', 'restore_visitation', 'Restored archived visitation record #16 (now #60) for Student ID 29', NULL, 'patient_archive.php', NULL, 1, NULL, NULL, '2025-09-21 17:14:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 17:14:52', 1),
(346, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 17:15:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 17:15:16', 1),
(347, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 17:19:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 17:19:51', 1),
(348, NULL, 'admin', 'unauthorized_access', 'Unauthorized access attempt to: ', NULL, 'auth/unauthorized', NULL, 1, NULL, NULL, '2025-09-21 17:23:52', '', '', '2025-09-21 17:23:52', 1),
(349, NULL, 'admin', 'visitation_logged', 'Added visitation record for patient ID 26 - Reason: dizziness', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-21 17:25:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 17:25:59', 1),
(350, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 17:26:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 17:26:10', 1),
(351, NULL, 'admin', 'visitation_logged', 'Added visitation record for patient ID 20 - Reason: headache', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-21 17:27:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 17:27:27', 1),
(352, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 17:27:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 17:27:33', 1),
(353, NULL, 'admin', 'student_restored', 'Restored student: dadadad (Elementary)', NULL, 'restore_record', NULL, 1, NULL, NULL, '2025-09-21 17:28:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 17:28:14', 1),
(354, NULL, 'admin', 'student_restored', 'Restored student: teena (College)', NULL, 'restore_record', NULL, 1, NULL, NULL, '2025-09-21 17:28:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 17:28:17', 1),
(355, NULL, 'admin', 'student_restored', 'Restored student: Angelic Taw (High School)', NULL, 'restore_record', NULL, 1, NULL, NULL, '2025-09-21 17:28:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 17:28:19', 1),
(356, NULL, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-21 17:28:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 17:28:27', 1),
(357, NULL, 'admin', 'logout', 'Admin logout', NULL, 'auth/logout', NULL, 1, NULL, NULL, '2025-09-21 17:34:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-21 17:34:34', 1),
(358, NULL, 'admin', 'login', 'Admin login', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 04:55:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 04:55:54', 1),
(359, NULL, 'admin', 'admin_created', 'Created new admin: Tunapay', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-22 05:00:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:00:39', 1),
(360, NULL, 'admin', 'logout', 'Admin logout', NULL, 'auth/logout', NULL, 1, NULL, NULL, '2025-09-22 05:00:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:00:41', 1),
(361, 4, 'admin', 'login', 'Admin login', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:00:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:00:44', 1),
(362, 4, 'admin', 'logout', 'Admin logout', NULL, 'auth/logout', NULL, 1, NULL, NULL, '2025-09-22 05:01:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:01:16', 1),
(363, 4, 'admin', 'login', 'Admin login', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:01:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:01:25', 1),
(364, 4, 'admin', 'logout', 'Admin logout', NULL, 'auth/logout', NULL, 1, NULL, NULL, '2025-09-22 05:01:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:01:27', 1),
(365, NULL, 'admin', 'rfid_verification_failed', 'Failed RFID verification for user: Tunapay (RFID: 3546657229)', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:03:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:03:36', 1),
(366, NULL, 'admin', 'login_failed', 'Invalid credentials for: admin@care.com', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:04:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:04:37', 1),
(367, NULL, 'admin', 'login_failed', 'Invalid credentials for: admin@care.com', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:04:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:04:42', 1),
(368, NULL, 'admin', 'login_failed', 'Invalid credentials for: admin@care.com', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:05:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:05:19', 1),
(369, NULL, 'admin', 'login_failed', 'Invalid credentials for: admin@care.com', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:06:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:06:07', 1),
(370, NULL, 'admin', 'rfid_verification_failed', 'Failed RFID verification for user: Tunapay (RFID: ADMIN001)', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:07:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:07:16', 1),
(371, NULL, 'admin', 'login_failed', 'Invalid credentials for: admin@care.com', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:09:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:09:16', 1),
(372, NULL, 'admin', 'rfid_verification_failed', 'Failed RFID verification for user: Tunapay (RFID: 3546657229)', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:09:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:09:20', 1),
(373, NULL, 'admin', 'rfid_verification_failed', 'Failed RFID verification for user: Tunapay (RFID: 3546657229)', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:09:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:09:21', 1),
(374, NULL, 'admin', 'rfid_verification_failed', 'Failed RFID verification for user: Tunapay (RFID: 3546657229)', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:09:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:09:22', 1),
(375, NULL, 'admin', 'rfid_verification_failed', 'Failed RFID verification for user: Tunapay (RFID: 3546657229)', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:10:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:10:03', 1),
(376, NULL, 'admin', 'rfid_verification_failed', 'Failed RFID verification for user: Tunapay (RFID: 3546657229)', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:10:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:10:52', 1),
(377, NULL, 'admin', 'rfid_verification_failed', 'Failed RFID verification for user: Tunapay (RFID: 3546657229)', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:13:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:13:14', 1),
(378, NULL, 'admin', 'login_failed', 'Invalid credentials for: admin@care.com', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:14:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:14:51', 1),
(379, NULL, 'admin', 'rfid_verification_failed', 'Failed RFID verification for user: Tunapay (RFID: ADMIN001)', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:14:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:14:58', 1),
(380, NULL, 'admin', 'rfid_verification_failed', 'Failed RFID verification for user: Tunapay (RFID: 3546657229)', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:15:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:15:05', 1),
(381, NULL, 'admin', 'rfid_verification_failed', 'Failed RFID verification for user: Tunapay (RFID: 3546657229)', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:15:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:15:11', 1),
(382, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:16:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:16:15', 1),
(383, 4, 'admin', 'logout', 'Admin logout', NULL, 'auth/logout', NULL, 1, NULL, NULL, '2025-09-22 05:16:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:16:50', 1),
(384, NULL, 'admin', 'rfid_verification_failed', 'Failed RFID verification for user: Tunapay (RFID: 3546657229)', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:16:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:16:55', 1),
(385, NULL, 'admin', 'rfid_verification_failed', 'Failed RFID verification for user: Tunapay (RFID: 3546657229)', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:17:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:17:52', 1),
(386, NULL, 'admin', 'rfid_verification_failed', 'Failed RFID verification for user: Tunapay (RFID: 3546657229)', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:18:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:18:02', 1),
(387, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:19:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:19:09', 1),
(388, 4, 'admin', 'logout', 'Admin logout', NULL, 'auth/logout', NULL, 1, NULL, NULL, '2025-09-22 05:20:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:20:37', 1),
(389, NULL, 'admin', 'rfid_verification_failed', 'Failed RFID verification for user: Tunapay (RFID: 3546657229)', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:20:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:20:40', 1),
(390, NULL, 'admin', 'rfid_verification_failed', 'Failed RFID verification for user: Tunapay (RFID: 3546657229)', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:22:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:22:04', 1),
(391, NULL, 'admin', 'rfid_verification_failed', 'Failed RFID verification for user: Tunapay (RFID: 3546657229)', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:22:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:22:49', 1),
(392, NULL, 'admin', 'rfid_verification_failed', 'Failed RFID verification for user: Tunapay (RFID: 3546657229)', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:22:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:22:55', 1),
(393, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:25:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:25:02', 1),
(394, 4, 'admin', 'logout', 'Admin logout', NULL, 'auth/logout', NULL, 1, NULL, NULL, '2025-09-22 05:25:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:25:34', 1),
(395, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 05:25:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:25:36', 1),
(396, 4, 'admin', 'faculty_register', 'Registered new faculty: Mria (canteen)', NULL, 'faculty_form', NULL, 1, NULL, NULL, '2025-09-22 05:41:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:41:15', 1),
(397, 4, 'admin', 'rfid_search', 'Found faculty: Mria (canteen)', NULL, 'rfid_portal', NULL, 1, NULL, NULL, '2025-09-22 05:41:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:41:30', 1),
(398, 4, 'admin', 'medical_history_created', 'Created medical history form for patient ID 5', NULL, 'medical/history', NULL, 1, NULL, NULL, '2025-09-22 05:41:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:41:45', 1),
(399, 4, 'admin', 'visitation_logged', 'Added visitation record for patient ID 5 - Reason: dizziness', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-22 05:41:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:41:51', 1),
(400, 4, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-22 05:45:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:45:08', 1),
(401, 4, 'admin', 'visitation_logged', 'Added visitation record for patient ID 32 - Reason: injury', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-22 05:53:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 05:53:52', 1),
(402, 4, 'admin', 'visitation_logged', 'Added visitation record for patient ID 28 - Reason: dizziness', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-22 06:01:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 06:01:26', 1),
(403, 4, 'admin', 'visitation_logged', 'Added visitation record for patient ID 28 - Reason: cough', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-22 06:10:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 06:10:22', 1),
(404, 4, 'admin', 'visitation_logged', 'Added visitation record for patient ID 28 - Reason: headache', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-22 06:11:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 06:11:15', 1),
(405, 4, 'admin', 'visitation_logged', 'Added visitation record for patient ID 28 - Reason: headache', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-22 06:14:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 06:14:15', 1),
(406, 4, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-22 06:14:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 06:14:55', 1),
(407, NULL, 'admin', 'unauthorized_access', 'Unauthorized access attempt to: /Care/admin/dashboard.php', NULL, 'auth/unauthorized', NULL, 1, NULL, NULL, '2025-09-22 06:46:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 06:46:33', 1),
(408, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 06:46:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 06:46:46', 1),
(409, 4, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-22 07:26:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 07:26:33', 1),
(410, NULL, 'admin', 'unauthorized_access', 'Unauthorized access attempt to: /Care/admin/settings.php?section=account_settings', NULL, 'auth/unauthorized', NULL, 1, NULL, NULL, '2025-09-22 07:43:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 07:43:47', 1),
(411, NULL, 'admin', 'unauthorized_access', 'Unauthorized access attempt to: /Care/admin/settings.php?section=register_admin', NULL, 'auth/unauthorized', NULL, 1, NULL, NULL, '2025-09-22 07:43:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 07:43:49', 1),
(412, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 07:44:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 07:44:01', 1),
(413, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 08:51:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 08:51:52', 1),
(414, 4, 'admin', 'rfid_search', 'Found student: Angelic Ta (Senior High School)', NULL, 'rfid_portal', NULL, 1, NULL, NULL, '2025-09-22 08:53:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 08:53:16', 1),
(415, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 10:24:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 10:24:59', 1),
(416, 4, 'admin', 'visitation_logged', 'Added visitation record for patient ID 30 - Reason: fever', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-22 10:26:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 10:26:17', 1),
(417, 4, 'admin', 'medical_history_created', 'Created medical history form for patient ID 30', NULL, 'medical/history', NULL, 1, NULL, NULL, '2025-09-22 10:26:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 10:26:44', 1),
(418, 4, 'admin', 'archive_visitation', 'Archived visitation record #69 for Student ID 30', NULL, 'patient_view.php', NULL, 1, NULL, NULL, '2025-09-22 10:26:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 10:26:52', 1),
(419, 4, 'admin', 'restore_visitation', 'Restored archived visitation record #17 (now #70) for Student ID 30', NULL, 'patient_archive.php', NULL, 1, NULL, NULL, '2025-09-22 10:27:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 10:27:02', 1),
(420, 4, 'admin', 'medical_record_updated', 'Updated medical record ID: 20 for patient ID: 30 (student)', NULL, '', NULL, 1, NULL, NULL, '2025-09-22 10:28:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 10:28:22', 1),
(421, 4, 'admin', 'update_patient', 'Updated student information for patient ID: 31', NULL, 'patient_view', NULL, 1, NULL, NULL, '2025-09-22 10:29:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 10:29:21', 1),
(422, 4, 'admin', 'student_register', 'Registered new student: juan (College)', NULL, 'student_form', NULL, 1, NULL, NULL, '2025-09-22 10:38:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 10:38:45', 1),
(423, 4, 'admin', 'student_register', 'Registered new student: Juan Dela Cruz (Senior High School)', NULL, 'student_form', NULL, 1, NULL, NULL, '2025-09-22 10:45:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 10:45:14', 1),
(424, 4, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-22 10:46:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 10:46:17', 1),
(425, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 11:07:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 11:07:43', 1),
(426, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-22 11:29:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 11:29:14', 1),
(427, 4, 'admin', 'update_patient', 'Updated student information for patient ID: 30', NULL, 'patient_view', NULL, 1, NULL, NULL, '2025-09-22 11:29:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 11:29:47', 1),
(428, NULL, 'admin', 'unauthorized_access', 'Unauthorized access attempt to: /Care/patients/school_listing.php?level=High%20School&type=students', NULL, 'auth/unauthorized', NULL, 1, NULL, NULL, '2025-09-23 03:07:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-23 03:07:21', 1),
(429, NULL, 'admin', 'unauthorized_access', 'Unauthorized access attempt to: /Care/admin/settings.php?section=register_admin', NULL, 'auth/unauthorized', NULL, 1, NULL, NULL, '2025-09-23 03:07:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-23 03:07:25', 1),
(430, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-23 03:07:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-23 03:07:59', 1),
(431, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-24 12:07:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-24 12:07:46', 1),
(432, 4, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-24 12:08:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-24 12:08:07', 1),
(433, 4, 'admin', 'visitation_logged', 'Added visitation record for patient ID 32 - Reason: Vitals', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-24 12:08:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-24 12:08:40', 1),
(434, 4, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-24 12:09:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-24 12:09:01', 1),
(435, 4, 'admin', 'logout', 'Admin logout', NULL, 'auth/logout', NULL, 1, NULL, NULL, '2025-09-24 12:09:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-24 12:09:49', 1),
(436, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-25 11:11:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-25 11:11:49', 1),
(437, 4, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-25 11:18:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-25 11:18:20', 1),
(438, 4, 'admin', 'medical_form_created', 'Created general medical form for patient ID 28', NULL, 'medical/forms', NULL, 1, NULL, NULL, '2025-09-25 12:20:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-25 12:20:42', 1),
(439, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-26 05:44:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 05:44:36', 1),
(440, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-26 06:27:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 06:27:38', 1),
(441, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-26 06:58:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 06:58:38', 1),
(442, 4, 'admin', 'visitation_logged', 'Added visitation record for patient ID 16 - Reason: cough', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-26 07:21:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 07:21:01', 1),
(443, 4, 'admin', 'student_reenroll', 'Re-enrolled student: Crumbs. with new RFID 4534353', NULL, 'reenroll_student', NULL, 1, NULL, NULL, '2025-09-26 07:32:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 07:32:31', 1),
(444, 4, 'admin', 'update_patient', 'Updated student information for patient ID: 32', NULL, 'patient_view', NULL, 1, NULL, NULL, '2025-09-26 08:24:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 08:24:14', 1),
(445, 4, 'admin', 'visitation_logged', 'Added visitation record for patient ID 28 - Reason: dizziness', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-26 10:22:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 10:22:33', 1),
(446, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-26 11:04:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 11:04:46', 1),
(447, 4, 'admin', 'visitation_logged', 'Added visitation record for patient ID 16 - Reason: cold', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-26 11:10:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 11:10:09', 1),
(448, 4, 'admin', 'medical_history_created', 'Created medical_history form for patient ID 16', NULL, 'medical/history', NULL, 1, NULL, NULL, '2025-09-26 11:10:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 11:10:21', 1),
(449, 4, 'admin', 'student_register', 'Registered new student: Grid (Pre-school)', NULL, 'student_form', NULL, 1, NULL, NULL, '2025-09-26 11:48:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 11:48:11', 1),
(450, 4, 'admin', 'visitation_logged', 'Added visitation record for patient ID 35 - Reason: cough', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-26 11:48:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 11:48:18', 1);
INSERT INTO `activity_logs` (`id`, `user_id`, `user_type`, `action`, `description`, `action_description`, `location`, `rfid_used`, `success`, `error_message`, `session_id`, `timestamp`, `ip_address`, `user_agent`, `created_timestamp`, `archived`) VALUES
(451, 4, 'admin', 'visitation_logged', 'Added visitation record for patient ID 35 - Reason: fever', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-26 11:48:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 11:48:27', 1),
(452, 4, 'admin', 'visitation_logged', 'Added visitation record for patient ID 35 - Reason: stomach_ache', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-26 11:48:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 11:48:31', 1),
(453, 4, 'admin', 'medical_history_created', 'Created medical_history form for patient ID 35', NULL, 'medical/history', NULL, 1, NULL, NULL, '2025-09-26 11:57:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 11:57:22', 1),
(454, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-26 15:51:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 15:51:31', 1),
(455, 4, 'admin', 'update_patient', 'Updated student information for patient ID: 26', NULL, 'patient_view', NULL, 1, NULL, NULL, '2025-09-26 15:51:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 15:51:42', 1),
(456, 4, 'admin', 'general_checkup_created', 'Created general_checkup form for patient ID 26', NULL, 'medical/history', NULL, 1, NULL, NULL, '2025-09-26 16:09:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 16:09:08', 1),
(457, 4, 'admin', 'medical_history_created', 'Created medical_history form for patient ID 26', NULL, 'medical/history', NULL, 1, NULL, NULL, '2025-09-26 16:09:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 16:09:41', 1),
(458, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-26 16:24:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 16:24:56', 1),
(459, 4, 'admin', 'medical_history_created', 'Created medical_history form for patient ID 32', NULL, 'medical/history', NULL, 1, NULL, NULL, '2025-09-26 16:58:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 16:58:51', 1),
(460, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-26 16:59:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 16:59:01', 1),
(461, 4, 'admin', 'medical_history_created', 'Created medical_history form for patient ID 26', NULL, 'medical/history', NULL, 1, NULL, NULL, '2025-09-26 16:59:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 16:59:32', 1),
(462, 4, 'admin', 'general_checkup_created', 'Created general_checkup form for patient ID 26', NULL, 'medical/history', NULL, 1, NULL, NULL, '2025-09-26 17:00:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 17:00:24', 1),
(463, 4, 'admin', 'archive_medical_record', 'Archived medical record #28 for Student ID 26', NULL, 'medical_forms_management.php', NULL, 1, NULL, NULL, '2025-09-26 17:28:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 17:28:28', 1),
(464, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-26 17:28:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 17:28:38', 1),
(465, 4, 'admin', 'restore_medical_record', 'Restored medical record #28 for Student ID 26', NULL, 'patient_archive.php', NULL, 1, NULL, NULL, '2025-09-26 17:43:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 17:43:57', 1),
(466, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-26 17:54:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 17:54:47', 1),
(467, 4, 'admin', 'medical_history_created', 'Created medical_history form for patient ID 35', NULL, 'medical/history', NULL, 1, NULL, NULL, '2025-09-26 17:55:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-26 17:55:00', 1),
(468, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-27 11:13:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 11:13:41', 1),
(469, 4, 'admin', 'logs_archived', 'Archived today\'s logs to main logs display', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-27 11:13:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 11:13:59', 1),
(470, 4, 'admin', 'visitation_logged', 'Added visitation record for patient ID 16 - Reason: cough', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-09-27 11:35:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 11:35:01', 1),
(471, 4, 'admin', 'logs_all_archived', 'Archived all unarchived logs (1 dates, 1 activities, 1 visitations)', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-27 11:38:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 11:38:10', 1),
(472, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-09-29 05:01:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-29 05:01:01', 1),
(473, 4, 'admin', 'logs_all_archived', 'Archived all unarchived logs (2 dates, 2 activities, 0 visitations)', NULL, 'settings', NULL, 1, NULL, NULL, '2025-09-29 05:10:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-29 05:10:34', 1),
(474, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-10-01 04:15:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-01 04:15:57', 1),
(475, 4, 'admin', 'rfid_search', 'RFID not found: 0284152906', NULL, 'rfid_portal', NULL, 1, NULL, NULL, '2025-10-01 04:16:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-01 04:16:04', 1),
(476, 4, 'admin', 'student_register', 'Registered new student: Jeremae Lalo (College)', NULL, 'student_form', NULL, 1, NULL, NULL, '2025-10-01 04:17:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-01 04:17:56', 1),
(477, 4, 'admin', 'visitation_logged', 'Added visitation record for patient ID 36 - Reason: cold', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-10-01 04:18:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-01 04:18:15', 1),
(478, 4, 'admin', 'general_checkup_created', 'Created general_checkup form for patient ID 36', NULL, 'medical/history', NULL, 1, NULL, NULL, '2025-10-01 04:19:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-01 04:19:06', 1),
(479, 4, 'admin', 'logs_all_archived', 'Archived all unarchived logs (2 dates, 6 activities, 1 visitations)', NULL, 'settings', NULL, 1, NULL, NULL, '2025-10-01 04:20:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-01 04:20:33', 0),
(480, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-10-01 05:42:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-01 05:42:08', 0),
(481, 4, 'admin', 'visitation_logged', 'Added visitation record for patient ID 36 - Reason: dizziness', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-10-01 05:42:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-01 05:42:58', 0),
(482, 4, 'admin', 'visitation_logged', 'Added visitation record for patient ID 32 - Reason: cold', NULL, 'save_visitation', NULL, 1, NULL, NULL, '2025-10-01 05:44:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-01 05:44:09', 0),
(483, 4, 'admin', 'archive_visitation', 'Archived visitation record #81 for Student ID 32', NULL, 'patient_view.php', NULL, 1, NULL, NULL, '2025-10-01 05:44:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-01 05:44:14', 0),
(484, 4, 'admin', 'restore_visitation', 'Restored archived visitation record #18 (now #82) for Student ID 32', NULL, 'patient_archive.php', NULL, 1, NULL, NULL, '2025-10-01 05:46:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-01 05:46:08', 0),
(485, 4, 'admin', 'login', 'Admin login with RFID verification', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-10-01 07:04:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-01 07:04:07', 0),
(486, 4, 'admin', 'general_checkup_created', 'Created general_checkup form for patient ID 32', NULL, 'medical/history', NULL, 1, NULL, NULL, '2025-10-01 07:04:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-01 07:04:44', 0),
(487, 4, 'admin', 'rfid_search', 'Found faculty: tuna (canteen)', NULL, 'rfid_portal', NULL, 1, NULL, NULL, '2025-10-01 07:06:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-01 07:06:42', 0),
(488, 4, 'admin', 'rfid_search', 'RFID not found: shan', NULL, 'rfid_portal', NULL, 1, NULL, NULL, '2025-10-01 07:06:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-01 07:06:53', 0),
(489, 4, 'admin', 'logout', 'Admin logout', NULL, 'auth/logout', NULL, 1, NULL, NULL, '2025-10-01 07:16:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-01 07:16:04', 0),
(490, NULL, 'admin', 'login_failed', 'Invalid credentials for: admin@care.com', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-10-01 07:16:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-01 07:16:06', 0),
(491, NULL, 'admin', 'login_failed', 'Invalid credentials for: admin@care.com', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-10-01 07:16:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-01 07:16:12', 0),
(492, NULL, 'admin', 'login_failed', 'Invalid credentials for: sample@gmail.com', NULL, 'auth/login', NULL, 1, NULL, NULL, '2025-10-01 07:16:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-01 07:16:32', 0);

-- --------------------------------------------------------

--
-- Table structure for table `archived_medical_records`
--

CREATE TABLE `archived_medical_records` (
  `id` int(11) NOT NULL,
  `original_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `patient_type` enum('student','faculty') NOT NULL,
  `form_type` enum('general_checkup','medical_history','athlete','emergency') NOT NULL,
  `form_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`form_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archived_medical_records`
--

INSERT INTO `archived_medical_records` (`id`, `original_id`, `patient_id`, `patient_type`, `form_type`, `form_data`, `created_at`, `archived_at`, `archived_by`) VALUES
(1, 27, 26, 'student', 'medical_history', '{\n    \"ongoing_conditions\": [],\n    \"ongoing_conditions_other\": \"\",\n    \"surgery_status\": \"no\",\n    \"surgery_details\": \"\",\n    \"family_conditions\": [\n        \"hypertension\"\n    ],\n    \"family_conditions_other\": \"\",\n    \"smoke_exposure\": \"no\",\n    \"immunization\": [],\n    \"covid_vaccine\": [\n        \"second_dose\"\n    ],\n    \"covid_positive\": \"no\",\n    \"covid_details\": \"\",\n    \"allergies\": \"\"\n}', '2025-09-26 16:59:32', '2025-09-26 17:02:59', 1);

-- --------------------------------------------------------

--
-- Stand-in structure for view `athlete_medical_view`
-- (See below for the actual view)
--
CREATE TABLE `athlete_medical_view` (
`id` int(11)
,`patient_id` int(11)
,`patient_type` enum('student','faculty')
,`created_at` timestamp
,`created_by` int(11)
,`created_by_name` varchar(100)
,`sport` longtext
,`position` longtext
,`height` longtext
,`weight` longtext
,`medical_history` longtext
,`physical_exam` longtext
,`recommendations` longtext
);

-- --------------------------------------------------------

--
-- Table structure for table `checkup_forms`
--

CREATE TABLE `checkup_forms` (
  `id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `patient_type` enum('student','faculty') NOT NULL,
  `checkup_date` datetime NOT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `bmi` decimal(4,1) DEFAULT NULL,
  `bmi_category` enum('Underweight','Normal','Overweight','Obese') DEFAULT NULL,
  `blood_pressure_systolic` int(11) DEFAULT NULL,
  `blood_pressure_diastolic` int(11) DEFAULT NULL,
  `blood_pressure_status` enum('Normal','High','Low') DEFAULT NULL,
  `temperature` decimal(4,2) DEFAULT NULL,
  `temperature_status` enum('Normal','Fever','Hypothermia') DEFAULT NULL,
  `heart_rate` int(11) DEFAULT NULL,
  `heart_rate_status` enum('Normal','High','Low') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_logs`
--

CREATE TABLE `daily_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `log_date` date NOT NULL,
  `activity_logs_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`activity_logs_data`)),
  `visitation_logs_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`visitation_logs_data`)),
  `total_activities` int(11) NOT NULL DEFAULT 0,
  `total_visitations` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_logs`
--

INSERT INTO `daily_logs` (`id`, `log_date`, `activity_logs_data`, `visitation_logs_data`, `total_activities`, `total_visitations`, `created_at`) VALUES
(4, '2025-09-21', '[{\"id\":321,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"logs_archived\",\"description\":\"Archived today\'s logs to main logs display\",\"action_description\":null,\"location\":\"settings\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-21 22:11:11\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-21 22:11:11\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":322,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"student_archived\",\"description\":\"Archived student: Angelic Ta (Senior High School)\",\"action_description\":null,\"location\":\"archive_student\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-21 22:37:10\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-21 22:37:10\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":323,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"student_archived\",\"description\":\"Archived student: Angelic Taw (High School)\",\"action_description\":null,\"location\":\"archive_student\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-21 22:41:51\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-21 22:41:51\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":324,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"student_archived\",\"description\":\"Archived student: Thena- (High School)\",\"action_description\":null,\"location\":\"archive_student\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-21 22:43:16\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-21 22:43:16\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":325,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"student_archived\",\"description\":\"Archived student: tuuuna (High School)\",\"action_description\":null,\"location\":\"archive_student\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-21 22:43:20\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-21 22:43:20\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":326,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"student_restored\",\"description\":\"Restored student: tuuuna (High School)\",\"action_description\":null,\"location\":\"restore_record\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-21 22:44:16\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-21 22:44:16\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":327,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"student_restored\",\"description\":\"Restored student: Thena- (High School)\",\"action_description\":null,\"location\":\"restore_record\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-21 22:44:19\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-21 22:44:19\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":328,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"student_restored\",\"description\":\"Restored student: Angelic Taw (High School)\",\"action_description\":null,\"location\":\"restore_record\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-21 22:44:21\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-21 22:44:21\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":329,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"student_restored\",\"description\":\"Restored student: Angelic Ta (Senior High School)\",\"action_description\":null,\"location\":\"restore_record\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-21 22:44:23\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-21 22:44:23\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":330,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"student_archived\",\"description\":\"Archived student: Angelic Taw (High School)\",\"action_description\":null,\"location\":\"archive_student\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-21 22:45:22\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-21 22:45:22\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":331,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"student_archived\",\"description\":\"Archived student: teena (College)\",\"action_description\":null,\"location\":\"archive_student\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-21 22:48:24\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-21 22:48:24\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":332,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"update_patient\",\"description\":\"Updated student information for patient ID: 6\",\"action_description\":null,\"location\":\"patient_view\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-21 22:58:32\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-21 22:58:32\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":333,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"update_patient\",\"description\":\"Updated student information for patient ID: 28\",\"action_description\":null,\"location\":\"patient_view\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-21 23:00:13\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-21 23:00:13\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":334,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"visitation_logged\",\"description\":\"Added visitation record for patient ID 28 - Reason: cold\",\"action_description\":null,\"location\":\"save_visitation\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-21 23:00:37\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-21 23:00:37\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":335,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"archive_visitation\",\"description\":\"Archived visitation record #57 for Student ID 28\",\"action_description\":null,\"location\":\"patient_view.php\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-21 23:00:39\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-21 23:00:39\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":336,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"restore_visitation\",\"description\":\"Restored archived visitation record #15 (now #58) for Student ID 28\",\"action_description\":null,\"location\":\"patient_archive.php\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-21 23:02:04\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-21 23:02:04\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"}]', '[{\"id\":58,\"patient_id\":28,\"patient_type\":\"student\",\"reason\":\"cold\",\"visit_date\":\"2025-09-21 23:00:00\",\"symptoms\":null,\"heart_rate\":null,\"blood_pressure\":null,\"temperature\":null,\"other_notes\":null,\"medication_given\":0,\"medication_name\":null,\"other_treatment\":null,\"medication_notes\":null,\"injury\":0,\"first_aid_given\":0,\"first_aid_type\":null,\"nurse_name\":\"Unknown\",\"created_at\":\"2025-09-21 23:02:04\",\"created_by\":3,\"updated_at\":\"2025-09-21 23:02:04\",\"archived\":0,\"patient_name\":\"Angelic Ta\",\"patient_rfid\":\"3546657229\",\"grade_level_department\":\"Grade 12\",\"course_section_strand\":\"TVL\"}]', 16, 1, '2025-09-21 14:11:11');
INSERT INTO `daily_logs` (`id`, `log_date`, `activity_logs_data`, `visitation_logs_data`, `total_activities`, `total_visitations`, `created_at`) VALUES
(5, '2025-09-22', '[{\"id\":348,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"unauthorized_access\",\"description\":\"Unauthorized access attempt to: \",\"action_description\":null,\"location\":\"auth\\/unauthorized\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 01:23:52\",\"ip_address\":\"\",\"user_agent\":\"\",\"created_timestamp\":\"2025-09-22 01:23:52\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":337,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"unauthorized_access\",\"description\":\"Unauthorized access attempt to: \\/Care\\/admin\\/dashboard.php\",\"action_description\":null,\"location\":\"auth\\/unauthorized\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 00:15:46\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 00:15:46\",\"archived\":1,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":338,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 00:15:57\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 00:15:57\",\"archived\":1,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":339,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 01:13:04\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 01:13:04\",\"archived\":1,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":340,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"student_archived\",\"description\":\"Archived student: dadadad (Elementary)\",\"action_description\":null,\"location\":\"archive_student\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 01:13:08\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 01:13:08\",\"archived\":1,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":341,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"student_register\",\"description\":\"Registered new student: sampleee (Elementary)\",\"action_description\":null,\"location\":\"student_form\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 01:13:58\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 01:13:58\",\"archived\":1,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":342,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"medical_history_created\",\"description\":\"Created medical history form for patient ID 29\",\"action_description\":null,\"location\":\"medical\\/history\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 01:14:18\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 01:14:18\",\"archived\":1,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":343,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"visitation_logged\",\"description\":\"Added visitation record for patient ID 29 - Reason: headache\",\"action_description\":null,\"location\":\"save_visitation\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 01:14:35\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 01:14:35\",\"archived\":1,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":344,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"archive_visitation\",\"description\":\"Archived visitation record #59 for Student ID 29\",\"action_description\":null,\"location\":\"patient_view.php\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 01:14:46\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 01:14:46\",\"archived\":1,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":345,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"restore_visitation\",\"description\":\"Restored archived visitation record #16 (now #60) for Student ID 29\",\"action_description\":null,\"location\":\"patient_archive.php\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 01:14:52\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 01:14:52\",\"archived\":1,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":346,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"logs_archived\",\"description\":\"Archived today\'s logs to main logs display\",\"action_description\":null,\"location\":\"settings\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 01:15:16\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 01:15:16\",\"archived\":1,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":347,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"logs_archived\",\"description\":\"Archived today\'s logs to main logs display\",\"action_description\":null,\"location\":\"settings\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 01:19:51\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 01:19:51\",\"archived\":1,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":349,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"visitation_logged\",\"description\":\"Added visitation record for patient ID 26 - Reason: dizziness\",\"action_description\":null,\"location\":\"save_visitation\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 01:25:59\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 01:25:59\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":350,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"logs_archived\",\"description\":\"Archived today\'s logs to main logs display\",\"action_description\":null,\"location\":\"settings\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 01:26:10\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 01:26:10\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":351,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"visitation_logged\",\"description\":\"Added visitation record for patient ID 20 - Reason: headache\",\"action_description\":null,\"location\":\"save_visitation\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 01:27:27\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 01:27:27\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":352,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"logs_archived\",\"description\":\"Archived today\'s logs to main logs display\",\"action_description\":null,\"location\":\"settings\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 01:27:33\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 01:27:33\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":353,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"student_restored\",\"description\":\"Restored student: dadadad (Elementary)\",\"action_description\":null,\"location\":\"restore_record\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 01:28:14\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 01:28:14\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":354,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"student_restored\",\"description\":\"Restored student: teena (College)\",\"action_description\":null,\"location\":\"restore_record\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 01:28:17\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 01:28:17\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":355,\"user_id\":3,\"user_type\":\"admin\",\"action\":\"student_restored\",\"description\":\"Restored student: Angelic Taw (High School)\",\"action_description\":null,\"location\":\"restore_record\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 01:28:19\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 01:28:19\",\"archived\":0,\"user_name\":\"thena\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$jneCqUTq2Z8H4BJzC43YXeRwPwFKi4PvpaaQZ2BsJRl\"},{\"id\":356,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"logs_archived\",\"description\":\"Archived today\'s logs to main logs display\",\"action_description\":null,\"location\":\"settings\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 01:28:27\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 01:28:27\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":357,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"logout\",\"description\":\"Admin logout\",\"action_description\":null,\"location\":\"auth\\/logout\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 01:34:34\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 01:34:34\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":358,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 12:55:54\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 12:55:54\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":359,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"admin_created\",\"description\":\"Created new admin: Tunapay\",\"action_description\":null,\"location\":\"settings\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:00:39\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:00:39\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":360,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"logout\",\"description\":\"Admin logout\",\"action_description\":null,\"location\":\"auth\\/logout\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:00:41\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:00:41\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":361,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:00:44\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:00:44\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":362,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"logout\",\"description\":\"Admin logout\",\"action_description\":null,\"location\":\"auth\\/logout\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:01:16\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:01:16\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":363,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:01:25\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:01:25\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":364,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"logout\",\"description\":\"Admin logout\",\"action_description\":null,\"location\":\"auth\\/logout\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:01:27\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:01:27\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":365,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"rfid_verification_failed\",\"description\":\"Failed RFID verification for user: Tunapay (RFID: 3546657229)\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:03:36\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:03:36\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":366,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"login_failed\",\"description\":\"Invalid credentials for: admin@care.com\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:04:37\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:04:37\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":367,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"login_failed\",\"description\":\"Invalid credentials for: admin@care.com\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:04:42\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:04:42\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":368,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"login_failed\",\"description\":\"Invalid credentials for: admin@care.com\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:05:19\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:05:19\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":369,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"login_failed\",\"description\":\"Invalid credentials for: admin@care.com\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:06:07\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:06:07\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":370,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"rfid_verification_failed\",\"description\":\"Failed RFID verification for user: Tunapay (RFID: ADMIN001)\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:07:16\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:07:16\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":371,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"login_failed\",\"description\":\"Invalid credentials for: admin@care.com\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:09:16\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:09:16\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":372,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"rfid_verification_failed\",\"description\":\"Failed RFID verification for user: Tunapay (RFID: 3546657229)\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:09:20\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:09:20\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":373,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"rfid_verification_failed\",\"description\":\"Failed RFID verification for user: Tunapay (RFID: 3546657229)\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:09:21\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:09:21\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":374,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"rfid_verification_failed\",\"description\":\"Failed RFID verification for user: Tunapay (RFID: 3546657229)\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:09:22\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:09:22\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":375,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"rfid_verification_failed\",\"description\":\"Failed RFID verification for user: Tunapay (RFID: 3546657229)\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:10:03\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:10:03\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":376,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"rfid_verification_failed\",\"description\":\"Failed RFID verification for user: Tunapay (RFID: 3546657229)\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:10:52\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:10:52\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":377,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"rfid_verification_failed\",\"description\":\"Failed RFID verification for user: Tunapay (RFID: 3546657229)\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:13:14\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:13:14\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":378,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"login_failed\",\"description\":\"Invalid credentials for: admin@care.com\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:14:51\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:14:51\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":379,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"rfid_verification_failed\",\"description\":\"Failed RFID verification for user: Tunapay (RFID: ADMIN001)\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:14:58\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:14:58\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":380,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"rfid_verification_failed\",\"description\":\"Failed RFID verification for user: Tunapay (RFID: 3546657229)\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:15:05\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:15:05\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":381,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"rfid_verification_failed\",\"description\":\"Failed RFID verification for user: Tunapay (RFID: 3546657229)\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:15:11\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:15:11\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":382,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:16:15\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:16:15\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":383,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"logout\",\"description\":\"Admin logout\",\"action_description\":null,\"location\":\"auth\\/logout\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:16:50\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:16:50\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":384,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"rfid_verification_failed\",\"description\":\"Failed RFID verification for user: Tunapay (RFID: 3546657229)\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:16:55\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:16:55\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":385,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"rfid_verification_failed\",\"description\":\"Failed RFID verification for user: Tunapay (RFID: 3546657229)\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:17:52\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:17:52\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":386,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"rfid_verification_failed\",\"description\":\"Failed RFID verification for user: Tunapay (RFID: 3546657229)\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:18:02\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:18:02\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":387,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:19:09\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:19:09\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":388,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"logout\",\"description\":\"Admin logout\",\"action_description\":null,\"location\":\"auth\\/logout\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:20:37\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:20:37\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":389,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"rfid_verification_failed\",\"description\":\"Failed RFID verification for user: Tunapay (RFID: 3546657229)\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:20:40\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:20:40\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":390,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"rfid_verification_failed\",\"description\":\"Failed RFID verification for user: Tunapay (RFID: 3546657229)\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:22:04\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:22:04\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":391,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"rfid_verification_failed\",\"description\":\"Failed RFID verification for user: Tunapay (RFID: 3546657229)\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:22:49\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:22:49\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":392,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"rfid_verification_failed\",\"description\":\"Failed RFID verification for user: Tunapay (RFID: 3546657229)\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:22:55\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:22:55\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":393,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:25:02\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:25:02\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":394,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"logout\",\"description\":\"Admin logout\",\"action_description\":null,\"location\":\"auth\\/logout\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:25:34\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:25:34\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":395,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:25:36\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:25:36\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":396,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"faculty_register\",\"description\":\"Registered new faculty: Mria (canteen)\",\"action_description\":null,\"location\":\"faculty_form\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:41:15\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:41:15\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":397,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"rfid_search\",\"description\":\"Found faculty: Mria (canteen)\",\"action_description\":null,\"location\":\"rfid_portal\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:41:30\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:41:30\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":398,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"medical_history_created\",\"description\":\"Created medical history form for patient ID 5\",\"action_description\":null,\"location\":\"medical\\/history\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:41:45\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:41:45\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":399,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"visitation_logged\",\"description\":\"Added visitation record for patient ID 5 - Reason: dizziness\",\"action_description\":null,\"location\":\"save_visitation\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:41:51\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:41:51\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":400,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"logs_archived\",\"description\":\"Archived today\'s logs to main logs display\",\"action_description\":null,\"location\":\"settings\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:45:08\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:45:08\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":401,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"visitation_logged\",\"description\":\"Added visitation record for patient ID 32 - Reason: injury\",\"action_description\":null,\"location\":\"save_visitation\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 13:53:52\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 13:53:52\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":402,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"visitation_logged\",\"description\":\"Added visitation record for patient ID 28 - Reason: dizziness\",\"action_description\":null,\"location\":\"save_visitation\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 14:01:26\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 14:01:26\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":403,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"visitation_logged\",\"description\":\"Added visitation record for patient ID 28 - Reason: cough\",\"action_description\":null,\"location\":\"save_visitation\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 14:10:22\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 14:10:22\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":404,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"visitation_logged\",\"description\":\"Added visitation record for patient ID 28 - Reason: headache\",\"action_description\":null,\"location\":\"save_visitation\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 14:11:15\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 14:11:15\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":405,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"visitation_logged\",\"description\":\"Added visitation record for patient ID 28 - Reason: headache\",\"action_description\":null,\"location\":\"save_visitation\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 14:14:15\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 14:14:15\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":406,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"logs_archived\",\"description\":\"Archived today\'s logs to main logs display\",\"action_description\":null,\"location\":\"settings\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 14:14:55\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 14:14:55\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":407,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"unauthorized_access\",\"description\":\"Unauthorized access attempt to: \\/Care\\/admin\\/dashboard.php\",\"action_description\":null,\"location\":\"auth\\/unauthorized\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 14:46:33\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 14:46:33\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":408,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 14:46:46\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 14:46:46\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":409,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"logs_archived\",\"description\":\"Archived today\'s logs to main logs display\",\"action_description\":null,\"location\":\"settings\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 15:26:33\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 15:26:33\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":410,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"unauthorized_access\",\"description\":\"Unauthorized access attempt to: \\/Care\\/admin\\/settings.php?section=account_settings\",\"action_description\":null,\"location\":\"auth\\/unauthorized\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 15:43:47\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 15:43:47\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":411,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"unauthorized_access\",\"description\":\"Unauthorized access attempt to: \\/Care\\/admin\\/settings.php?section=register_admin\",\"action_description\":null,\"location\":\"auth\\/unauthorized\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 15:43:49\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 15:43:49\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":412,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 15:44:01\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 15:44:01\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":413,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 16:51:52\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 16:51:52\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":414,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"rfid_search\",\"description\":\"Found student: Angelic Ta (Senior High School)\",\"action_description\":null,\"location\":\"rfid_portal\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 16:53:16\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 16:53:16\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":415,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 18:24:59\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 18:24:59\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":416,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"visitation_logged\",\"description\":\"Added visitation record for patient ID 30 - Reason: fever\",\"action_description\":null,\"location\":\"save_visitation\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 18:26:17\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 18:26:17\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":417,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"medical_history_created\",\"description\":\"Created medical history form for patient ID 30\",\"action_description\":null,\"location\":\"medical\\/history\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 18:26:44\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 18:26:44\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":418,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"archive_visitation\",\"description\":\"Archived visitation record #69 for Student ID 30\",\"action_description\":null,\"location\":\"patient_view.php\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 18:26:52\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 18:26:52\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":419,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"restore_visitation\",\"description\":\"Restored archived visitation record #17 (now #70) for Student ID 30\",\"action_description\":null,\"location\":\"patient_archive.php\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 18:27:02\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 18:27:02\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":420,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"medical_record_updated\",\"description\":\"Updated medical record ID: 20 for patient ID: 30 (student)\",\"action_description\":null,\"location\":\"\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 18:28:22\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 18:28:22\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":421,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"update_patient\",\"description\":\"Updated student information for patient ID: 31\",\"action_description\":null,\"location\":\"patient_view\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 18:29:21\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 18:29:21\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":422,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"student_register\",\"description\":\"Registered new student: juan (College)\",\"action_description\":null,\"location\":\"student_form\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 18:38:45\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 18:38:45\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":423,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"student_register\",\"description\":\"Registered new student: Juan Dela Cruz (Senior High School)\",\"action_description\":null,\"location\":\"student_form\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-22 18:45:14\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-22 18:45:14\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"}]', '[{\"id\":60,\"patient_id\":29,\"patient_type\":\"student\",\"reason\":\"headache\",\"visit_date\":\"2025-09-22 01:14:00\",\"symptoms\":null,\"heart_rate\":5,\"blood_pressure\":\"90\",\"temperature\":\"36.0\",\"other_notes\":null,\"medication_given\":0,\"medication_name\":null,\"other_treatment\":null,\"medication_notes\":null,\"injury\":0,\"first_aid_given\":0,\"first_aid_type\":null,\"nurse_name\":\"Unknown\",\"created_at\":\"2025-09-22 01:14:52\",\"created_by\":3,\"updated_at\":\"2025-09-22 01:15:16\",\"archived\":1,\"patient_name\":\"sampleee\",\"patient_rfid\":\"7867865\",\"grade_level_department\":\"Grade 4\",\"course_section_strand\":\"mabiit\"},{\"id\":61,\"patient_id\":26,\"patient_type\":\"student\",\"reason\":\"dizziness\",\"visit_date\":\"2025-09-22 01:25:00\",\"symptoms\":null,\"heart_rate\":null,\"blood_pressure\":null,\"temperature\":null,\"other_notes\":null,\"medication_given\":0,\"medication_name\":null,\"other_treatment\":null,\"medication_notes\":null,\"injury\":0,\"first_aid_given\":0,\"first_aid_type\":null,\"nurse_name\":\"thena\",\"created_at\":\"2025-09-22 01:25:59\",\"created_by\":3,\"updated_at\":\"2025-09-22 01:25:59\",\"archived\":0,\"patient_name\":\"Thena-\",\"patient_rfid\":\"43324234\",\"grade_level_department\":\"Grade 8\",\"course_section_strand\":\"mabiit\"},{\"id\":62,\"patient_id\":20,\"patient_type\":\"student\",\"reason\":\"headache\",\"visit_date\":\"2025-09-22 01:27:00\",\"symptoms\":null,\"heart_rate\":null,\"blood_pressure\":null,\"temperature\":null,\"other_notes\":null,\"medication_given\":0,\"medication_name\":null,\"other_treatment\":null,\"medication_notes\":null,\"injury\":0,\"first_aid_given\":0,\"first_aid_type\":null,\"nurse_name\":\"thena\",\"created_at\":\"2025-09-22 01:27:27\",\"created_by\":3,\"updated_at\":\"2025-09-22 01:27:27\",\"archived\":0,\"patient_name\":\"dddd\",\"patient_rfid\":\"345667575\",\"grade_level_department\":\"Grade 4\",\"course_section_strand\":\"adadaa\"},{\"id\":63,\"patient_id\":5,\"patient_type\":\"faculty\",\"reason\":\"dizziness\",\"visit_date\":\"2025-09-22 13:41:00\",\"symptoms\":null,\"heart_rate\":null,\"blood_pressure\":null,\"temperature\":null,\"other_notes\":null,\"medication_given\":0,\"medication_name\":null,\"other_treatment\":null,\"medication_notes\":null,\"injury\":0,\"first_aid_given\":0,\"first_aid_type\":null,\"nurse_name\":\"Tunapay\",\"created_at\":\"2025-09-22 13:41:51\",\"created_by\":4,\"updated_at\":\"2025-09-22 13:41:51\",\"archived\":0,\"patient_name\":\"Mria\",\"patient_rfid\":\"6756544\",\"grade_level_department\":\"canteen\",\"course_section_strand\":null},{\"id\":64,\"patient_id\":32,\"patient_type\":\"student\",\"reason\":\"injury\",\"visit_date\":\"2025-09-22 13:53:00\",\"symptoms\":null,\"heart_rate\":null,\"blood_pressure\":null,\"temperature\":null,\"other_notes\":null,\"medication_given\":0,\"medication_name\":null,\"other_treatment\":null,\"medication_notes\":null,\"injury\":0,\"first_aid_given\":0,\"first_aid_type\":null,\"nurse_name\":\"Tunapay\",\"created_at\":\"2025-09-22 13:53:52\",\"created_by\":4,\"updated_at\":\"2025-09-22 13:53:52\",\"archived\":0,\"patient_name\":\"Angelic Taw\",\"patient_rfid\":\"4232342353\",\"grade_level_department\":\"Grade 9\",\"course_section_strand\":\"adadaa\"},{\"id\":65,\"patient_id\":28,\"patient_type\":\"student\",\"reason\":\"dizziness\",\"visit_date\":\"2025-09-22 14:01:00\",\"symptoms\":null,\"heart_rate\":null,\"blood_pressure\":null,\"temperature\":null,\"other_notes\":null,\"medication_given\":0,\"medication_name\":null,\"other_treatment\":null,\"medication_notes\":null,\"injury\":0,\"first_aid_given\":0,\"first_aid_type\":null,\"nurse_name\":\"Tunapay\",\"created_at\":\"2025-09-22 14:01:26\",\"created_by\":4,\"updated_at\":\"2025-09-22 14:01:26\",\"archived\":0,\"patient_name\":\"Angelic Ta\",\"patient_rfid\":\"3546657229\",\"grade_level_department\":\"Grade 12\",\"course_section_strand\":\"TVL\"},{\"id\":66,\"patient_id\":28,\"patient_type\":\"student\",\"reason\":\"cough\",\"visit_date\":\"2025-09-22 14:10:00\",\"symptoms\":null,\"heart_rate\":null,\"blood_pressure\":null,\"temperature\":null,\"other_notes\":null,\"medication_given\":0,\"medication_name\":null,\"other_treatment\":null,\"medication_notes\":null,\"injury\":0,\"first_aid_given\":0,\"first_aid_type\":null,\"nurse_name\":\"Tunapay\",\"created_at\":\"2025-09-22 14:10:22\",\"created_by\":4,\"updated_at\":\"2025-09-22 14:10:22\",\"archived\":0,\"patient_name\":\"Angelic Ta\",\"patient_rfid\":\"3546657229\",\"grade_level_department\":\"Grade 12\",\"course_section_strand\":\"TVL\"},{\"id\":67,\"patient_id\":28,\"patient_type\":\"student\",\"reason\":\"headache\",\"visit_date\":\"2025-09-22 14:10:00\",\"symptoms\":null,\"heart_rate\":null,\"blood_pressure\":null,\"temperature\":null,\"other_notes\":null,\"medication_given\":0,\"medication_name\":null,\"other_treatment\":null,\"medication_notes\":null,\"injury\":1,\"first_aid_given\":1,\"first_aid_type\":\"ice_pack\",\"nurse_name\":\"Tunapay\",\"created_at\":\"2025-09-22 14:11:15\",\"created_by\":4,\"updated_at\":\"2025-09-22 14:11:15\",\"archived\":0,\"patient_name\":\"Angelic Ta\",\"patient_rfid\":\"3546657229\",\"grade_level_department\":\"Grade 12\",\"course_section_strand\":\"TVL\"},{\"id\":68,\"patient_id\":28,\"patient_type\":\"student\",\"reason\":\"headache\",\"visit_date\":\"2025-09-22 14:14:00\",\"symptoms\":\"N\\/A\",\"heart_rate\":null,\"blood_pressure\":null,\"temperature\":null,\"other_notes\":\"N\\/A\",\"medication_given\":1,\"medication_name\":\"ice_pack\",\"other_treatment\":null,\"medication_notes\":\"N\\/A\",\"injury\":0,\"first_aid_given\":0,\"first_aid_type\":null,\"nurse_name\":\"Tunapay\",\"created_at\":\"2025-09-22 14:14:15\",\"created_by\":4,\"updated_at\":\"2025-09-22 14:14:15\",\"archived\":0,\"patient_name\":\"Angelic Ta\",\"patient_rfid\":\"3546657229\",\"grade_level_department\":\"Grade 12\",\"course_section_strand\":\"TVL\"},{\"id\":70,\"patient_id\":30,\"patient_type\":\"student\",\"reason\":\"fever\",\"visit_date\":\"2025-09-22 18:25:00\",\"symptoms\":\"N\\/A\",\"heart_rate\":79,\"blood_pressure\":\"90\",\"temperature\":\"36.0\",\"other_notes\":\"N\\/A\",\"medication_given\":0,\"medication_name\":null,\"other_treatment\":null,\"medication_notes\":\"N\\/A\",\"injury\":0,\"first_aid_given\":0,\"first_aid_type\":null,\"nurse_name\":\"Unknown\",\"created_at\":\"2025-09-22 18:27:02\",\"created_by\":4,\"updated_at\":\"2025-09-22 18:27:02\",\"archived\":0,\"patient_name\":\"dadadad\",\"patient_rfid\":\"34235523\",\"grade_level_department\":\"Grade 4\",\"course_section_strand\":\"adada\"}]', 87, 10, '2025-09-22 10:46:17');
INSERT INTO `daily_logs` (`id`, `log_date`, `activity_logs_data`, `visitation_logs_data`, `total_activities`, `total_visitations`, `created_at`) VALUES
(6, '2025-09-24', '[{\"id\":431,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-24 20:07:46\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-24 20:07:46\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":432,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"logs_archived\",\"description\":\"Archived today\'s logs to main logs display\",\"action_description\":null,\"location\":\"settings\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-24 20:08:07\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-24 20:08:07\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":433,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"visitation_logged\",\"description\":\"Added visitation record for patient ID 32 - Reason: Vitals\",\"action_description\":null,\"location\":\"save_visitation\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-24 20:08:40\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-24 20:08:40\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"}]', '[{\"id\":71,\"patient_id\":32,\"patient_type\":\"student\",\"reason\":\"Vitals\",\"visit_date\":\"2025-09-24 20:08:00\",\"symptoms\":\"N\\/A\",\"heart_rate\":30,\"blood_pressure\":\"30\",\"temperature\":\"35.0\",\"other_notes\":\"N\\/A\",\"medication_given\":0,\"medication_name\":null,\"other_treatment\":null,\"medication_notes\":\"N\\/A\",\"injury\":0,\"first_aid_given\":0,\"first_aid_type\":null,\"nurse_name\":\"Tunapay\",\"created_at\":\"2025-09-24 20:08:40\",\"created_by\":4,\"updated_at\":\"2025-09-24 20:08:40\",\"archived\":0,\"patient_name\":\"Angelic Taw\",\"patient_rfid\":\"4232342353\",\"grade_level_department\":\"Grade 9\",\"course_section_strand\":\"adadaa\"}]', 3, 1, '2025-09-24 12:09:01'),
(7, '2025-09-25', '[{\"id\":436,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-25 19:11:49\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-25 19:11:49\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"}]', '[]', 1, 0, '2025-09-25 11:18:20'),
(8, '2025-09-27', '[{\"id\":456,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"general_checkup_created\",\"description\":\"Created general_checkup form for patient ID 26\",\"action_description\":null,\"location\":\"medical\\/history\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-27 00:09:08\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-27 00:09:08\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":457,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"medical_history_created\",\"description\":\"Created medical_history form for patient ID 26\",\"action_description\":null,\"location\":\"medical\\/history\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-27 00:09:41\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-27 00:09:41\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":458,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-27 00:24:56\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-27 00:24:56\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":459,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"medical_history_created\",\"description\":\"Created medical_history form for patient ID 32\",\"action_description\":null,\"location\":\"medical\\/history\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-27 00:58:51\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-27 00:58:51\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":460,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-27 00:59:01\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-27 00:59:01\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":461,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"medical_history_created\",\"description\":\"Created medical_history form for patient ID 26\",\"action_description\":null,\"location\":\"medical\\/history\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-27 00:59:32\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-27 00:59:32\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":462,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"general_checkup_created\",\"description\":\"Created general_checkup form for patient ID 26\",\"action_description\":null,\"location\":\"medical\\/history\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-27 01:00:24\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-27 01:00:24\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":463,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"archive_medical_record\",\"description\":\"Archived medical record #28 for Student ID 26\",\"action_description\":null,\"location\":\"medical_forms_management.php\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-27 01:28:28\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-27 01:28:28\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":464,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-27 01:28:38\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-27 01:28:38\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":465,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"restore_medical_record\",\"description\":\"Restored medical record #28 for Student ID 26\",\"action_description\":null,\"location\":\"patient_archive.php\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-27 01:43:57\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-27 01:43:57\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":466,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-27 01:54:47\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-27 01:54:47\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":467,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"medical_history_created\",\"description\":\"Created medical_history form for patient ID 35\",\"action_description\":null,\"location\":\"medical\\/history\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-27 01:55:00\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-27 01:55:00\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":468,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-27 19:13:41\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-27 19:13:41\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"}]', '[]', 13, 0, '2025-09-27 11:13:59'),
(9, '2025-09-26', '[{\"id\":439,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-26 13:44:36\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-26 13:44:36\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":440,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-26 14:27:38\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-26 14:27:38\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":441,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-26 14:58:38\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-26 14:58:38\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":442,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"visitation_logged\",\"description\":\"Added visitation record for patient ID 16 - Reason: cough\",\"action_description\":null,\"location\":\"save_visitation\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-26 15:21:01\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-26 15:21:01\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":443,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"student_reenroll\",\"description\":\"Re-enrolled student: Crumbs. with new RFID 4534353\",\"action_description\":null,\"location\":\"reenroll_student\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-26 15:32:31\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-26 15:32:31\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":444,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"update_patient\",\"description\":\"Updated student information for patient ID: 32\",\"action_description\":null,\"location\":\"patient_view\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-26 16:24:14\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-26 16:24:14\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":445,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"visitation_logged\",\"description\":\"Added visitation record for patient ID 28 - Reason: dizziness\",\"action_description\":null,\"location\":\"save_visitation\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-26 18:22:33\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-26 18:22:33\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":446,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-26 19:04:46\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-26 19:04:46\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":447,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"visitation_logged\",\"description\":\"Added visitation record for patient ID 16 - Reason: cold\",\"action_description\":null,\"location\":\"save_visitation\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-26 19:10:09\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-26 19:10:09\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":448,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"medical_history_created\",\"description\":\"Created medical_history form for patient ID 16\",\"action_description\":null,\"location\":\"medical\\/history\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-26 19:10:21\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-26 19:10:21\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":449,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"student_register\",\"description\":\"Registered new student: Grid (Pre-school)\",\"action_description\":null,\"location\":\"student_form\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-26 19:48:11\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-26 19:48:11\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":450,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"visitation_logged\",\"description\":\"Added visitation record for patient ID 35 - Reason: cough\",\"action_description\":null,\"location\":\"save_visitation\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-26 19:48:18\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-26 19:48:18\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":451,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"visitation_logged\",\"description\":\"Added visitation record for patient ID 35 - Reason: fever\",\"action_description\":null,\"location\":\"save_visitation\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-26 19:48:27\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-26 19:48:27\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":452,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"visitation_logged\",\"description\":\"Added visitation record for patient ID 35 - Reason: stomach_ache\",\"action_description\":null,\"location\":\"save_visitation\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-26 19:48:31\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-26 19:48:31\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":453,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"medical_history_created\",\"description\":\"Created medical_history form for patient ID 35\",\"action_description\":null,\"location\":\"medical\\/history\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-26 19:57:22\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-26 19:57:22\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":454,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-26 23:51:31\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-26 23:51:31\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":455,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"update_patient\",\"description\":\"Updated student information for patient ID: 26\",\"action_description\":null,\"location\":\"patient_view\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-26 23:51:42\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-26 23:51:42\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"}]', '[{\"id\":72,\"patient_id\":16,\"patient_type\":\"student\",\"reason\":\"cough\",\"visit_date\":\"2025-09-26 15:20:00\",\"symptoms\":\"N\\/A\",\"heart_rate\":null,\"blood_pressure\":null,\"temperature\":null,\"other_notes\":\"N\\/A\",\"medication_given\":0,\"medication_name\":null,\"other_treatment\":null,\"medication_notes\":\"N\\/A\",\"injury\":0,\"first_aid_given\":0,\"first_aid_type\":null,\"nurse_name\":\"Tunapay\",\"created_at\":\"2025-09-26 15:21:01\",\"created_by\":4,\"updated_at\":\"2025-09-26 15:21:01\",\"archived\":0,\"patient_name\":\"Crumbs.\",\"patient_rfid\":\"4534353\",\"grade_level_department\":\"Grade 1\",\"course_section_strand\":\"mabaet\"},{\"id\":73,\"patient_id\":28,\"patient_type\":\"student\",\"reason\":\"dizziness\",\"visit_date\":\"2025-09-26 17:34:00\",\"symptoms\":\"N\\/A\",\"heart_rate\":null,\"blood_pressure\":null,\"temperature\":null,\"other_notes\":\"N\\/A\",\"medication_given\":0,\"medication_name\":null,\"other_treatment\":null,\"medication_notes\":\"N\\/A\",\"injury\":0,\"first_aid_given\":0,\"first_aid_type\":null,\"nurse_name\":\"Tunapay\",\"created_at\":\"2025-09-26 18:22:33\",\"created_by\":4,\"updated_at\":\"2025-09-26 18:22:33\",\"archived\":0,\"patient_name\":\"Angelic Ta\",\"patient_rfid\":\"453443\",\"grade_level_department\":\"1st Year\",\"course_section_strand\":\"BS in Information Technology\"},{\"id\":74,\"patient_id\":16,\"patient_type\":\"student\",\"reason\":\"cold\",\"visit_date\":\"2025-09-26 19:09:00\",\"symptoms\":\"N\\/A\",\"heart_rate\":90,\"blood_pressure\":\"120\\/80\",\"temperature\":\"36.0\",\"other_notes\":\"N\\/A\",\"medication_given\":0,\"medication_name\":null,\"other_treatment\":null,\"medication_notes\":\"N\\/A\",\"injury\":0,\"first_aid_given\":0,\"first_aid_type\":null,\"nurse_name\":\"Tunapay\",\"created_at\":\"2025-09-26 19:10:09\",\"created_by\":4,\"updated_at\":\"2025-09-26 19:10:09\",\"archived\":0,\"patient_name\":\"Crumbs.\",\"patient_rfid\":\"4534353\",\"grade_level_department\":\"Grade 1\",\"course_section_strand\":\"mabaet\"},{\"id\":75,\"patient_id\":35,\"patient_type\":\"student\",\"reason\":\"cough\",\"visit_date\":\"2025-09-26 19:48:00\",\"symptoms\":\"N\\/A\",\"heart_rate\":null,\"blood_pressure\":null,\"temperature\":null,\"other_notes\":\"N\\/A\",\"medication_given\":0,\"medication_name\":null,\"other_treatment\":null,\"medication_notes\":\"N\\/A\",\"injury\":0,\"first_aid_given\":0,\"first_aid_type\":null,\"nurse_name\":\"Tunapay\",\"created_at\":\"2025-09-26 19:48:18\",\"created_by\":4,\"updated_at\":\"2025-09-26 19:48:18\",\"archived\":0,\"patient_name\":\"Grid\",\"patient_rfid\":\"63453534\",\"grade_level_department\":\"\",\"course_section_strand\":\"magolo\"},{\"id\":76,\"patient_id\":35,\"patient_type\":\"student\",\"reason\":\"fever\",\"visit_date\":\"2025-09-26 19:48:00\",\"symptoms\":\"N\\/A\",\"heart_rate\":null,\"blood_pressure\":null,\"temperature\":null,\"other_notes\":\"N\\/A\",\"medication_given\":0,\"medication_name\":null,\"other_treatment\":null,\"medication_notes\":\"N\\/A\",\"injury\":0,\"first_aid_given\":0,\"first_aid_type\":null,\"nurse_name\":\"Tunapay\",\"created_at\":\"2025-09-26 19:48:27\",\"created_by\":4,\"updated_at\":\"2025-09-26 19:48:27\",\"archived\":0,\"patient_name\":\"Grid\",\"patient_rfid\":\"63453534\",\"grade_level_department\":\"\",\"course_section_strand\":\"magolo\"},{\"id\":77,\"patient_id\":35,\"patient_type\":\"student\",\"reason\":\"stomach_ache\",\"visit_date\":\"2025-09-26 19:48:00\",\"symptoms\":\"N\\/A\",\"heart_rate\":null,\"blood_pressure\":null,\"temperature\":null,\"other_notes\":\"N\\/A\",\"medication_given\":0,\"medication_name\":null,\"other_treatment\":null,\"medication_notes\":\"N\\/A\",\"injury\":0,\"first_aid_given\":0,\"first_aid_type\":null,\"nurse_name\":\"Tunapay\",\"created_at\":\"2025-09-26 19:48:31\",\"created_by\":4,\"updated_at\":\"2025-09-26 19:48:31\",\"archived\":0,\"patient_name\":\"Grid\",\"patient_rfid\":\"63453534\",\"grade_level_department\":\"\",\"course_section_strand\":\"magolo\"}]', 17, 6, '2025-09-27 11:29:31'),
(10, '2025-09-23', '[{\"id\":428,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"unauthorized_access\",\"description\":\"Unauthorized access attempt to: \\/Care\\/patients\\/school_listing.php?level=High%20School&type=students\",\"action_description\":null,\"location\":\"auth\\/unauthorized\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-23 11:07:21\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-23 11:07:21\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":429,\"user_id\":null,\"user_type\":\"admin\",\"action\":\"unauthorized_access\",\"description\":\"Unauthorized access attempt to: \\/Care\\/admin\\/settings.php?section=register_admin\",\"action_description\":null,\"location\":\"auth\\/unauthorized\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-23 11:07:25\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-23 11:07:25\",\"archived\":0,\"user_name\":null,\"user_email\":null,\"user_rfid\":null},{\"id\":430,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-23 11:07:59\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-23 11:07:59\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"}]', '[]', 3, 0, '2025-09-27 11:29:31'),
(11, '2025-09-29', '[{\"id\":472,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-09-29 13:01:01\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-09-29 13:01:01\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"}]', '[]', 1, 0, '2025-09-29 05:10:34'),
(12, '2025-10-01', '[{\"id\":474,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"login\",\"description\":\"Admin login with RFID verification\",\"action_description\":null,\"location\":\"auth\\/login\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-10-01 12:15:57\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-10-01 12:15:57\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":475,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"rfid_search\",\"description\":\"RFID not found: 0284152906\",\"action_description\":null,\"location\":\"rfid_portal\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-10-01 12:16:04\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-10-01 12:16:04\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":476,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"student_register\",\"description\":\"Registered new student: Jeremae Lalo (College)\",\"action_description\":null,\"location\":\"student_form\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-10-01 12:17:56\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-10-01 12:17:56\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":477,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"visitation_logged\",\"description\":\"Added visitation record for patient ID 36 - Reason: cold\",\"action_description\":null,\"location\":\"save_visitation\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-10-01 12:18:15\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-10-01 12:18:15\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"},{\"id\":478,\"user_id\":4,\"user_type\":\"admin\",\"action\":\"general_checkup_created\",\"description\":\"Created general_checkup form for patient ID 36\",\"action_description\":null,\"location\":\"medical\\/history\",\"rfid_used\":null,\"success\":1,\"error_message\":null,\"session_id\":null,\"timestamp\":\"2025-10-01 12:19:06\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36\",\"created_timestamp\":\"2025-10-01 12:19:06\",\"archived\":0,\"user_name\":\"Tunapay\",\"user_email\":\"er@gmail.com\",\"user_rfid\":\"$2y$10$yUBPRinX5VUQEho7\\/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO\"}]', '[{\"id\":79,\"patient_id\":36,\"patient_type\":\"student\",\"reason\":\"cold\",\"visit_date\":\"2025-10-01 00:00:00\",\"symptoms\":\"N\\/A\",\"heart_rate\":null,\"blood_pressure\":null,\"temperature\":null,\"other_notes\":\"N\\/A\",\"medication_given\":0,\"medication_name\":null,\"other_treatment\":null,\"medication_notes\":\"N\\/A\",\"injury\":0,\"first_aid_given\":0,\"first_aid_type\":null,\"nurse_name\":\"Tunapay\",\"created_at\":\"2025-10-01 12:18:15\",\"created_by\":4,\"updated_at\":\"2025-10-01 12:18:15\",\"archived\":0,\"patient_name\":\"Jeremae Lalo\",\"patient_rfid\":\"0284152906\",\"grade_level_department\":\"3rd Year\",\"course_section_strand\":\"BS in Information Technology\"}]', 5, 1, '2025-10-01 04:20:33');

-- --------------------------------------------------------

--
-- Stand-in structure for view `emergency_medical_view`
-- (See below for the actual view)
--
CREATE TABLE `emergency_medical_view` (
`id` int(11)
,`patient_id` int(11)
,`patient_type` enum('student','faculty')
,`created_at` timestamp
,`created_by` int(11)
,`created_by_name` varchar(100)
,`emergency_type` longtext
,`severity` longtext
,`emergency_description` longtext
,`immediate_actions` longtext
,`heart_rate` longtext
,`blood_pressure` longtext
,`temperature` longtext
,`follow_up` longtext
);

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_history`
--

CREATE TABLE `enrollment_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `enrollment_type` enum('initial','re_enrollment','level_change','status_change') NOT NULL,
  `previous_level` varchar(50) DEFAULT NULL,
  `new_level` varchar(50) NOT NULL,
  `previous_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) NOT NULL,
  `previous_year_grade` varchar(40) DEFAULT NULL,
  `new_year_grade` varchar(40) DEFAULT NULL,
  `previous_section` varchar(50) DEFAULT NULL,
  `new_section` varchar(50) DEFAULT NULL,
  `previous_strand` varchar(50) DEFAULT NULL,
  `new_strand` varchar(50) DEFAULT NULL,
  `previous_course` varchar(120) DEFAULT NULL,
  `new_course` varchar(120) DEFAULT NULL,
  `previous_block` varchar(10) DEFAULT NULL,
  `new_block` varchar(10) DEFAULT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `enrollment_year` varchar(10) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `previous_rfid` varchar(50) DEFAULT NULL,
  `new_rfid` varchar(50) DEFAULT NULL,
  `previous_name` varchar(200) DEFAULT NULL,
  `previous_gender` varchar(10) DEFAULT NULL,
  `previous_dob` date DEFAULT NULL,
  `previous_age` int(11) DEFAULT NULL,
  `previous_religion` varchar(80) DEFAULT NULL,
  `previous_barangay` varchar(100) DEFAULT NULL,
  `previous_municipality` varchar(100) DEFAULT NULL,
  `previous_province` varchar(100) DEFAULT NULL,
  `previous_guardian_name` varchar(200) DEFAULT NULL,
  `previous_emergency_contact` varchar(120) DEFAULT NULL,
  `previous_contacts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`previous_contacts`)),
  `previous_allergies` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollment_history`
--

INSERT INTO `enrollment_history` (`id`, `student_id`, `enrollment_type`, `previous_level`, `new_level`, `previous_status`, `new_status`, `previous_year_grade`, `new_year_grade`, `previous_section`, `new_section`, `previous_strand`, `new_strand`, `previous_course`, `new_course`, `previous_block`, `new_block`, `enrollment_date`, `enrollment_year`, `semester`, `notes`, `created_by`, `created_at`, `previous_rfid`, `new_rfid`, `previous_name`, `previous_gender`, `previous_dob`, `previous_age`, `previous_religion`, `previous_barangay`, `previous_municipality`, `previous_province`, `previous_guardian_name`, `previous_emergency_contact`, `previous_contacts`, `previous_allergies`) VALUES
(3, 16, 're_enrollment', 'Pre-school', 'Elementary', 'Graduated', 'Active', '', 'Grade 1', 'magolo', 'mabaet', '', '', '', '', NULL, '', '2025-09-26 07:32:31', '2025', NULL, 'Re-enrolled with new RFID: 23232313 → 4534353', 4, '2025-09-26 07:32:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 32, 're_enrollment', 'Senior High School', 'Senior High School', 'Graduated', 'Active', 'Grade 11', 'Grade 11', 'adadaa', 'adadaa', 'ABM', 'ABM', '', '', '', '', '2025-09-26 09:24:54', NULL, NULL, 'Re-enrolled from Senior High School to Senior High School', 4, '2025-09-26 09:24:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 32, 're_enrollment', 'Senior High School', 'Senior High School', 'Graduated', 'Active', 'Grade 11', 'Grade 11', 'adadaa', 'adadaa', 'ABM', 'ABM', '', '', '', '', '2025-09-26 09:26:12', NULL, NULL, 'Re-enrolled from Senior High School to Senior High School', 4, '2025-09-26 09:26:12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 28, 're_enrollment', 'Senior High School', 'College', 'Graduated', 'Active', 'Grade 12', '1st Year', 'N/A', 'N/A', 'TVL', 'TVL', 'N/A', 'BS in Information Technology', 'N/A', 'B', '2025-09-26 09:34:52', NULL, NULL, 'Re-enrolled from Senior High School to College', 4, '2025-09-26 09:34:52', '453443', NULL, 'Angelic Ta', 'Female', '2007-11-02', 17, 'Roman Catholic', 'somewhere/onlyweknow', 'sdfdf', 'fsf', 'BreaD', '09767894542', '[\"09767894542\"]', 'N/A'),
(8, 35, 'initial', NULL, 'Pre-school', NULL, 'Active', NULL, '', NULL, 'magolo', NULL, '', NULL, '', NULL, '', '2025-09-26 11:48:11', '2025', NULL, 'Initial enrollment in Pre-school', 4, '2025-09-26 11:48:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 35, 're_enrollment', 'Pre-school', 'Elementary', 'Graduated', 'Active', '', '', 'magolo', 'magolo', '', '', '', '', '', '', '2025-09-26 17:55:30', NULL, NULL, 'Re-enrolled from Pre-school to Elementary', 4, '2025-09-26 17:55:30', '34322342', NULL, 'Grid', 'Male', '2019-11-02', 5, 'Roman Catholic', 'Sffdf', 'Fdffd', 'Dfdfd', 'Breadee', NULL, '[\"09876543212\"]', 'N/A'),
(13, 36, 'initial', NULL, 'College', NULL, 'Active', NULL, '3rd Year', NULL, '', NULL, '', NULL, 'BS in Information Technology', NULL, 'B', '2025-10-01 04:17:56', '2025', NULL, 'Initial enrollment in College', 4, '2025-10-01 04:17:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `rfid` varchar(50) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `sr` tinyint(1) NOT NULL DEFAULT 0,
  `dob` date DEFAULT NULL,
  `religion` varchar(80) DEFAULT NULL,
  `emergency_contact` varchar(120) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `contacts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`contacts`)),
  `medical_notes` text DEFAULT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive','Retired','Resigned') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`id`, `name`, `department`, `gender`, `rfid`, `address`, `age`, `sr`, `dob`, `religion`, `emergency_contact`, `allergies`, `contacts`, `medical_notes`, `employee_id`, `position`, `status`, `created_at`, `updated_at`) VALUES
(3, 'tuna', 'canteen', 'Female', '3546657229', 'somewhere/onlyweknow', 41, 0, '1983-11-11', 'Roman Catholic', '97678945423', 'N/A', NULL, NULL, NULL, NULL, 'Active', '2025-09-17 14:06:33', '2025-09-17 14:06:33'),
(4, 'Thena-', 'canteen', 'Female', '423432', 'somewhere/onlyweknow', 41, 0, '1983-11-11', 'Buddhist', '', 'N/A', NULL, NULL, NULL, NULL, 'Active', '2025-09-19 01:57:48', '2025-09-19 01:57:48'),
(5, 'Mria', 'canteen', 'Female', '6756544', '', 64, 1, '1960-11-11', 'United Methodist', '09976789454', 'N/A', NULL, NULL, NULL, NULL, 'Active', '2025-09-22 05:41:14', '2025-09-22 05:41:14');

-- --------------------------------------------------------

--
-- Table structure for table `faculty_archive`
--

CREATE TABLE `faculty_archive` (
  `id` int(11) NOT NULL,
  `original_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `sr` tinyint(1) DEFAULT 0,
  `dob` date DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `rfid` varchar(50) DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `faculty_masked`
-- (See below for the actual view)
--
CREATE TABLE `faculty_masked` (
`id` int(10) unsigned
,`name` longtext
,`department` varchar(100)
,`gender` enum('Male','Female','Other')
,`rfid` longtext
,`address` longtext
,`age` int(11)
,`sr` tinyint(1)
,`dob` date
,`religion` varchar(80)
,`emergency_contact` longtext
,`allergies` text
,`medical_notes` text
,`employee_id` varchar(50)
,`position` varchar(100)
,`status` enum('Active','Inactive','Retired','Resigned')
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `faculty_medical_archive`
--

CREATE TABLE `faculty_medical_archive` (
  `id` int(10) UNSIGNED NOT NULL,
  `original_id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `patient_type` enum('student','faculty') NOT NULL,
  `form_type` varchar(100) NOT NULL,
  `form_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`form_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_by` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculty_visitation_archive`
--

CREATE TABLE `faculty_visitation_archive` (
  `id` int(10) UNSIGNED NOT NULL,
  `original_id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `patient_type` enum('student','faculty') NOT NULL,
  `reason` varchar(255) NOT NULL,
  `visit_date` datetime NOT NULL,
  `symptoms` text DEFAULT NULL,
  `other_notes` text DEFAULT NULL,
  `heart_rate` int(11) DEFAULT NULL,
  `blood_pressure` varchar(50) DEFAULT NULL,
  `temperature` decimal(4,2) DEFAULT NULL,
  `medication_given` tinyint(1) DEFAULT 0,
  `medication_name` varchar(255) DEFAULT NULL,
  `other_treatment` varchar(255) DEFAULT NULL,
  `medication_notes` text DEFAULT NULL,
  `injury` tinyint(1) DEFAULT 0,
  `first_aid_given` tinyint(1) DEFAULT 0,
  `first_aid_type` varchar(255) DEFAULT NULL,
  `nurse_name` varchar(100) DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_by` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `general_medical_view`
-- (See below for the actual view)
--
CREATE TABLE `general_medical_view` (
`id` int(11)
,`patient_id` int(11)
,`patient_type` enum('student','faculty')
,`created_at` timestamp
,`created_by` int(11)
,`created_by_name` varchar(100)
,`chief_complaint` longtext
,`duration` longtext
,`history_present` longtext
,`past_medical` longtext
,`physical_exam` longtext
,`assessment_plan` longtext
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `medical_history_view`
-- (See below for the actual view)
--
CREATE TABLE `medical_history_view` (
`id` int(11)
,`patient_id` int(11)
,`patient_type` enum('student','faculty')
,`created_at` timestamp
,`created_by` int(11)
,`created_by_name` varchar(100)
,`ongoing_conditions` longtext
,`ongoing_conditions_other` longtext
,`surgery_status` longtext
,`surgery_details` longtext
,`family_conditions` longtext
,`family_conditions_other` longtext
,`smoke_exposure` longtext
,`immunization` longtext
,`covid_vaccine` longtext
,`covid_positive` longtext
,`covid_details` longtext
);

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `patient_type` enum('student','faculty') NOT NULL,
  `form_type` varchar(50) NOT NULL,
  `form_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`form_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`id`, `patient_id`, `patient_type`, `form_type`, `form_data`, `created_at`, `created_by`, `updated_at`) VALUES
(1, 1, 'student', 'medical_history', '{\"ongoing_conditions\": [\"asthma\", \"error_refraction\"], \"ongoing_conditions_other\": \"None\", \"surgery_status\": \"no\", \"surgery_details\": \"\", \"family_conditions\": [\"diabetes\", \"hypertension\"], \"family_conditions_other\": \"None\", \"smoke_exposure\": \"no\", \"immunization\": [\"mmr\", \"dpt\", \"bcg\", \"hepatitis_b\"], \"covid_vaccine\": [\"first_dose\", \"second_dose\", \"booster_1\"], \"covid_positive\": \"no\", \"covid_details\": \"\"}', '2025-09-17 15:23:15', 1, '2025-09-17 15:23:15'),
(2, 1, 'student', 'medical_history', '{\"ongoing_conditions\": [\"asthma\", \"error_refraction\"], \"ongoing_conditions_other\": \"None\", \"surgery_status\": \"no\", \"surgery_details\": \"\", \"family_conditions\": [\"diabetes\", \"hypertension\"], \"family_conditions_other\": \"None\", \"smoke_exposure\": \"no\", \"immunization\": [\"mmr\", \"dpt\", \"bcg\", \"hepatitis_b\"], \"covid_vaccine\": [\"first_dose\", \"second_dose\", \"booster_1\"], \"covid_positive\": \"no\", \"covid_details\": \"\"}', '2025-09-17 15:23:31', 1, '2025-09-17 15:23:31'),
(3, 1, 'student', 'medical_history', '{\"ongoing_conditions\": [\"asthma\", \"error_refraction\"], \"ongoing_conditions_other\": \"None\", \"surgery_status\": \"no\", \"surgery_details\": \"\", \"family_conditions\": [\"diabetes\", \"hypertension\"], \"family_conditions_other\": \"None\", \"smoke_exposure\": \"no\", \"immunization\": [\"mmr\", \"dpt\", \"bcg\", \"hepatitis_b\"], \"covid_vaccine\": [\"first_dose\", \"second_dose\", \"booster_1\"], \"covid_positive\": \"no\", \"covid_details\": \"\"}', '2025-09-17 15:24:11', 1, '2025-09-17 15:24:11'),
(4, 1, 'student', 'athlete', '{\"sport\": \"Basketball\", \"position\": \"Point Guard\", \"height\": \"175\", \"weight\": \"70\", \"medical_history\": \"No previous injuries, regular checkups\", \"physical_exam\": \"Heart rate: 65 bpm, BP: 120/80, Normal\", \"recommendations\": \"Continue regular training, maintain hydration\"}', '2025-09-17 15:24:11', 1, '2025-09-17 15:24:11'),
(5, 1, 'student', 'general', '{\"chief_complaint\": \"Headache and fever\", \"duration\": \"2 days\", \"history_present\": \"Patient reports headache starting 2 days ago, fever developed yesterday\", \"past_medical\": \"No significant medical history\", \"physical_exam\": \"Temp: 38.5┬░C, HR: 85 bpm, BP: 110/70, No neck stiffness\", \"assessment_plan\": \"Viral infection, rest and fluids, follow up if symptoms worsen\"}', '2025-09-17 15:24:11', 1, '2025-09-17 15:24:11'),
(6, 1, 'student', 'emergency', '{\"emergency_type\": \"injury\", \"severity\": \"moderate\", \"emergency_description\": \"Student fell during PE class, injured left ankle\", \"immediate_actions\": \"Applied ice pack, elevated leg, called parents\", \"heart_rate\": \"95\", \"blood_pressure\": \"115/75\", \"temperature\": \"36.8\", \"follow_up\": \"Refer to orthopedic specialist, X-ray recommended\"}', '2025-09-17 15:24:11', 1, '2025-09-17 15:24:11'),
(7, 1, 'student', 'medical_history', '{\"ongoing_conditions\": [\"asthma\", \"error_refraction\"], \"ongoing_conditions_other\": \"None\", \"surgery_status\": \"no\", \"surgery_details\": \"\", \"family_conditions\": [\"diabetes\", \"hypertension\"], \"family_conditions_other\": \"None\", \"smoke_exposure\": \"no\", \"immunization\": [\"mmr\", \"dpt\", \"bcg\", \"hepatitis_b\"], \"covid_vaccine\": [\"first_dose\", \"second_dose\", \"booster_1\"], \"covid_positive\": \"no\", \"covid_details\": \"\"}', '2025-09-17 15:24:18', 1, '2025-09-17 15:24:18'),
(8, 1, 'student', 'athlete', '{\"sport\": \"Basketball\", \"position\": \"Point Guard\", \"height\": \"175\", \"weight\": \"70\", \"medical_history\": \"No previous injuries, regular checkups\", \"physical_exam\": \"Heart rate: 65 bpm, BP: 120/80, Normal\", \"recommendations\": \"Continue regular training, maintain hydration\"}', '2025-09-17 15:24:18', 1, '2025-09-17 15:24:18'),
(9, 1, 'student', 'general', '{\"chief_complaint\": \"Headache and fever\", \"duration\": \"2 days\", \"history_present\": \"Patient reports headache starting 2 days ago, fever developed yesterday\", \"past_medical\": \"No significant medical history\", \"physical_exam\": \"Temp: 38.5°C, HR: 85 bpm, BP: 110/70, No neck stiffness\", \"assessment_plan\": \"Viral infection, rest and fluids, follow up if symptoms worsen\"}', '2025-09-17 15:24:18', 1, '2025-09-17 15:24:18'),
(10, 1, 'student', 'emergency', '{\"emergency_type\": \"injury\", \"severity\": \"moderate\", \"emergency_description\": \"Student fell during PE class, injured left ankle\", \"immediate_actions\": \"Applied ice pack, elevated leg, called parents\", \"heart_rate\": \"95\", \"blood_pressure\": \"115/75\", \"temperature\": \"36.8\", \"follow_up\": \"Refer to orthopedic specialist, X-ray recommended\"}', '2025-09-17 15:24:18', 1, '2025-09-17 15:24:18'),
(11, 5, 'student', 'medical_history', '{\n    \"ongoing_conditions\": [],\n    \"ongoing_conditions_other\": \"\",\n    \"surgery_status\": \"no\",\n    \"surgery_details\": \"\",\n    \"family_conditions\": [],\n    \"family_conditions_other\": \"\",\n    \"smoke_exposure\": \"no\",\n    \"immunization\": [\n        \"chicken_pox\"\n    ],\n    \"covid_vaccine\": [],\n    \"covid_positive\": \"no\",\n    \"covid_details\": \"\"\n}', '2025-09-17 15:31:32', 2, '2025-09-18 09:21:37'),
(12, 4, 'faculty', 'medical_history', '{\n    \"ongoing_conditions\": [],\n    \"ongoing_conditions_other\": \"\",\n    \"surgery_status\": \"no\",\n    \"surgery_details\": \"\",\n    \"family_conditions\": [],\n    \"family_conditions_other\": \"\",\n    \"smoke_exposure\": \"no\",\n    \"immunization\": [],\n    \"covid_vaccine\": [],\n    \"covid_positive\": \"no\",\n    \"covid_details\": \"\"\n}', '2025-09-19 09:42:47', 3, '2025-09-19 09:42:47'),
(13, 3, 'faculty', 'medical_history', '{\n    \"ongoing_conditions\": [\n        \"seizure\"\n    ],\n    \"ongoing_conditions_other\": \"\",\n    \"surgery_status\": \"no\",\n    \"surgery_details\": \"\",\n    \"family_conditions\": [],\n    \"family_conditions_other\": \"\",\n    \"smoke_exposure\": \"no\",\n    \"immunization\": [],\n    \"covid_vaccine\": [\n        \"booster_1\"\n    ],\n    \"covid_positive\": \"no\",\n    \"covid_details\": \"\"\n}', '2025-09-19 09:45:45', 3, '2025-09-19 09:45:45'),
(14, 7, 'student', 'medical_history', '{\n    \"ongoing_conditions\": [\n        \"heart_problem\"\n    ],\n    \"ongoing_conditions_other\": \"\",\n    \"surgery_status\": \"no\",\n    \"surgery_details\": \"\",\n    \"family_conditions\": [],\n    \"family_conditions_other\": \"\",\n    \"smoke_exposure\": \"no\",\n    \"immunization\": [],\n    \"covid_vaccine\": [],\n    \"covid_positive\": \"no\",\n    \"covid_details\": \"\"\n}', '2025-09-19 10:18:26', 3, '2025-09-19 10:18:26'),
(15, 19, 'student', 'medical_history', '{\n    \"ongoing_conditions\": [\n        \"error_refraction\"\n    ],\n    \"ongoing_conditions_other\": \"\",\n    \"surgery_status\": \"no\",\n    \"surgery_details\": \"\",\n    \"family_conditions\": [\n        \"cancer\"\n    ],\n    \"family_conditions_other\": \"\",\n    \"smoke_exposure\": \"no\",\n    \"immunization\": [\n        \"mmr\",\n        \"chicken_pox\"\n    ],\n    \"covid_vaccine\": [],\n    \"covid_positive\": \"no\",\n    \"covid_details\": \"\"\n}', '2025-09-19 12:37:00', 3, '2025-09-19 12:37:00'),
(17, 22, 'student', 'medical_history', '{\n    \"ongoing_conditions\": [\n        \"error_refraction\"\n    ],\n    \"ongoing_conditions_other\": \"\",\n    \"surgery_status\": \"no\",\n    \"surgery_details\": \"\",\n    \"family_conditions\": [\n        \"diabetes\"\n    ],\n    \"family_conditions_other\": \"\",\n    \"smoke_exposure\": \"yes\",\n    \"immunization\": [],\n    \"covid_vaccine\": [\n        \"second_dose\"\n    ],\n    \"covid_positive\": \"no\",\n    \"covid_details\": \"\"\n}', '2025-09-21 13:33:53', 3, '2025-09-21 13:33:53'),
(18, 29, 'student', 'medical_history', '{\n    \"ongoing_conditions\": [\n        \"asthma\"\n    ],\n    \"ongoing_conditions_other\": \"\",\n    \"surgery_status\": \"no\",\n    \"surgery_details\": \"\",\n    \"family_conditions\": [],\n    \"family_conditions_other\": \"\",\n    \"smoke_exposure\": \"no\",\n    \"immunization\": [\n        \"mmr\"\n    ],\n    \"covid_vaccine\": [],\n    \"covid_positive\": \"no\",\n    \"covid_details\": \"\"\n}', '2025-09-21 17:14:18', 3, '2025-09-21 17:14:18'),
(19, 5, 'faculty', 'medical_history', '{\n    \"ongoing_conditions\": [\n        \"asthma\"\n    ],\n    \"ongoing_conditions_other\": \"\",\n    \"surgery_status\": \"no\",\n    \"surgery_details\": \"\",\n    \"family_conditions\": [],\n    \"family_conditions_other\": \"\",\n    \"smoke_exposure\": \"no\",\n    \"immunization\": [],\n    \"covid_vaccine\": [\n        \"second_dose\"\n    ],\n    \"covid_positive\": \"no\",\n    \"covid_details\": \"\"\n}', '2025-09-22 05:41:45', 4, '2025-09-22 05:41:45'),
(20, 30, 'student', 'medical_history', '{\n    \"ongoing_conditions\": [\n        \"diabetes\"\n    ],\n    \"ongoing_conditions_other\": \"\",\n    \"surgery_status\": \"no\",\n    \"surgery_details\": \"\",\n    \"family_conditions\": [],\n    \"family_conditions_other\": \"\",\n    \"smoke_exposure\": \"no\",\n    \"immunization\": [],\n    \"covid_vaccine\": [],\n    \"covid_positive\": \"no\",\n    \"covid_details\": \"\"\n}', '2025-09-22 10:26:44', 4, '2025-09-22 10:28:22'),
(21, 28, 'student', 'general', '{\n    \"assessment_plan\": \"N\\/A\",\n    \"height\": \"164\",\n    \"weight\": \"64\",\n    \"heart_rate\": \"78\",\n    \"temperature\": \"34\",\n    \"blood_pressure\": \"130\\/60\"\n}', '2025-09-25 12:20:42', 4, '2025-09-25 12:20:42'),
(22, 16, 'student', 'medical_history', '{\n    \"ongoing_conditions\": [\n        \"asthma\"\n    ],\n    \"ongoing_conditions_other\": \"\",\n    \"surgery_status\": \"no\",\n    \"surgery_details\": \"\",\n    \"family_conditions\": [],\n    \"family_conditions_other\": \"\",\n    \"smoke_exposure\": \"no\",\n    \"immunization\": [],\n    \"covid_vaccine\": [],\n    \"covid_positive\": \"no\",\n    \"covid_details\": \"\"\n}', '2025-09-26 11:10:21', 4, '2025-09-26 11:10:21'),
(23, 35, 'student', 'medical_history', '{\n    \"ongoing_conditions\": [\n        \"asthma\",\n        \"diabetes\"\n    ],\n    \"ongoing_conditions_other\": \"\",\n    \"surgery_status\": \"no\",\n    \"surgery_details\": \"\",\n    \"family_conditions\": [\n        \"hypertension\"\n    ],\n    \"family_conditions_other\": \"\",\n    \"smoke_exposure\": \"no\",\n    \"immunization\": [\n        \"hepatitis_b\"\n    ],\n    \"covid_vaccine\": [\n        \"moderna\"\n    ],\n    \"covid_positive\": \"no\",\n    \"covid_details\": \"\",\n    \"allergies\": \"N\\/A\"\n}', '2025-09-26 11:57:22', 4, '2025-09-26 11:57:22'),
(24, 26, 'student', 'general_checkup', '{\n    \"assessment_plan\": \"N\\/A\",\n    \"height\": \"164\",\n    \"weight\": \"64\",\n    \"bmi\": \"23.8\",\n    \"bmi_status\": \"Normal\",\n    \"heart_rate\": \"70\",\n    \"heart_rate_status\": \"Normal\",\n    \"temperature\": \"37\",\n    \"temperature_status\": \"Normal\",\n    \"blood_pressure\": \"130\\/60\",\n    \"blood_pressure_status\": \"High\"\n}', '2025-09-26 16:09:08', 4, '2025-09-26 16:09:08'),
(25, 26, 'student', 'medical_history', '{\n    \"ongoing_conditions\": [\n        \"diabetes\"\n    ],\n    \"ongoing_conditions_other\": \"\",\n    \"surgery_status\": \"no\",\n    \"surgery_details\": \"\",\n    \"family_conditions\": [\n        \"hypertension\"\n    ],\n    \"family_conditions_other\": \"\",\n    \"smoke_exposure\": \"no\",\n    \"immunization\": [],\n    \"covid_vaccine\": [\n        \"pfizer\"\n    ],\n    \"covid_positive\": \"no\",\n    \"covid_details\": \"\",\n    \"allergies\": \"N\\/A\"\n}', '2025-09-26 16:09:41', 4, '2025-09-26 16:09:41'),
(26, 32, 'student', 'medical_history', '{\n    \"ongoing_conditions\": [],\n    \"ongoing_conditions_other\": \"\",\n    \"surgery_status\": \"no\",\n    \"surgery_details\": \"\",\n    \"family_conditions\": [\n        \"depression\"\n    ],\n    \"family_conditions_other\": \"\",\n    \"smoke_exposure\": \"no\",\n    \"immunization\": [],\n    \"covid_vaccine\": [\n        \"second_dose\"\n    ],\n    \"covid_positive\": \"no\",\n    \"covid_details\": \"\",\n    \"allergies\": \"\"\n}', '2025-09-26 16:58:51', 4, '2025-09-26 16:58:51'),
(29, 26, 'student', 'general_checkup', '{\n    \"assessment_plan\": \"N\\/A\",\n    \"height\": \"164\",\n    \"weight\": \"60\",\n    \"bmi\": \"22.3\",\n    \"bmi_status\": \"Normal\",\n    \"heart_rate\": \"70\",\n    \"heart_rate_status\": \"Normal\",\n    \"temperature\": \"37\",\n    \"temperature_status\": \"Normal\",\n    \"blood_pressure\": \"120\\/80\",\n    \"blood_pressure_status\": \"Normal\"\n}', '2025-09-26 17:00:24', 4, '2025-09-26 17:43:57'),
(30, 35, 'student', 'medical_history', '{\n    \"ongoing_conditions\": [],\n    \"ongoing_conditions_other\": \"\",\n    \"surgery_status\": \"no\",\n    \"surgery_details\": \"\",\n    \"family_conditions\": [\n        \"depression\"\n    ],\n    \"family_conditions_other\": \"\",\n    \"smoke_exposure\": \"no\",\n    \"immunization\": [],\n    \"covid_vaccine\": [],\n    \"covid_positive\": \"no\",\n    \"covid_details\": \"\",\n    \"allergies\": \"\"\n}', '2025-09-26 17:55:00', 4, '2025-09-26 17:55:00'),
(31, 36, 'student', 'general_checkup', '{\n    \"assessment_plan\": \"N\\/A\",\n    \"height\": \"155\",\n    \"weight\": \"61\",\n    \"bmi\": \"25.4\",\n    \"bmi_status\": \"Overweight\",\n    \"heart_rate\": \"\",\n    \"heart_rate_status\": \"N\\/A\",\n    \"temperature\": \"\",\n    \"temperature_status\": \"N\\/A\",\n    \"blood_pressure\": \"\",\n    \"blood_pressure_status\": \"N\\/A\"\n}', '2025-10-01 04:19:06', 4, '2025-10-01 04:19:06'),
(32, 32, 'student', 'general_checkup', '{\n    \"assessment_plan\": \"N\\/A\",\n    \"height\": \"164\",\n    \"weight\": \"64\",\n    \"bmi\": \"23.8\",\n    \"bmi_status\": \"Normal\",\n    \"heart_rate\": \"\",\n    \"heart_rate_status\": \"N\\/A\",\n    \"temperature\": \"\",\n    \"temperature_status\": \"N\\/A\",\n    \"blood_pressure\": \"\",\n    \"blood_pressure_status\": \"N\\/A\"\n}', '2025-10-01 07:04:44', 4, '2025-10-01 07:04:44');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `level` enum('Pre-school','Elementary','High School','Senior High School','College') NOT NULL,
  `course` varchar(120) DEFAULT NULL,
  `block` varchar(10) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `strand` varchar(50) DEFAULT NULL,
  `year_grade` varchar(40) DEFAULT NULL,
  `rfid` varchar(50) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `religion` varchar(80) DEFAULT NULL,
  `guardian` varchar(120) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `contacts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`contacts`)),
  `emergency_contact` varchar(120) DEFAULT NULL,
  `medical_notes` text DEFAULT NULL,
  `status` enum('Active','Inactive','Graduated','Transferred') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `name`, `gender`, `level`, `course`, `block`, `section`, `strand`, `year_grade`, `rfid`, `address`, `age`, `dob`, `religion`, `guardian`, `allergies`, `contacts`, `emergency_contact`, `medical_notes`, `status`, `created_at`, `updated_at`) VALUES
(6, 'Thena-', 'Female', 'College', 'BS in Information Technology', 'B', 'N/A', 'N/A', '3rd Year', '0285695914', 'somewhere/onlyweknow', 19, '2005-11-02', 'United Methodist', 'BreaD', 'N/A', '[\"09767894542\"]', '', NULL, 'Active', '2025-09-17 14:20:10', '2025-09-21 14:58:32'),
(8, 'tunasansan', 'Female', 'Senior High School', '', NULL, '', 'STEM', '', '0285695916', 'somewhere/onlyweknow', 19, '2005-11-02', 'Roman Catholic', 'BreaD', 'N/A', '[\"9765768323\"]', '', NULL, 'Active', '2025-09-17 16:08:19', '2025-09-17 16:08:19'),
(12, 'Thena-', 'Male', 'Elementary', 'N/A', NULL, 'magolo', 'N/A', 'Grade 5', '2324353', 'somewhere/onlyweknow', 10, '2014-11-02', 'Roman Catholic', 'BreaDee', 'N/A', NULL, NULL, NULL, 'Active', '2025-09-19 01:25:46', '2025-09-19 01:38:22'),
(15, 'Test Student', NULL, 'Elementary', 'N/A', NULL, 'Section A', 'N/A', 'Grade 3', '35466', 'Test Address', 11, '2014-03-15', 'Christian', 'Test Guardian', 'None', NULL, '', NULL, 'Active', '2025-09-19 02:47:01', '2025-09-19 02:47:01'),
(16, 'Crumbs.', 'Female', 'Elementary', '', '', 'mabaet', '', 'Grade 1', '4534353', 'adad, ada, adad', 4, '2020-11-11', 'Hindu', 'BreaD', 'N/A', '[\"09876543212\"]', NULL, NULL, 'Active', '2025-09-19 10:21:30', '2025-09-29 05:03:39'),
(19, 'frwee', 'Male', 'Elementary', 'N/A', NULL, 'ewfef', 'N/A', 'Grade 3', '4324333', 'sdfsf, fg, eww', 10, '2014-11-02', 'Roman Catholic', 'weewqew', 'N/A', '[\"09765768323\"]', NULL, NULL, 'Active', '2025-09-19 12:35:15', '2025-09-19 12:36:37'),
(20, 'dddd', 'Female', 'Elementary', '', NULL, 'adadaa', '', 'Grade 4', '345667575', 'fsdfdff, dre, erwew', 8, '2016-11-02', 'Iglesia ni Cristo', 'BreaDee', 'N/A', '[\"09765768323\"]', NULL, NULL, 'Active', '2025-09-21 08:45:26', '2025-09-21 08:45:26'),
(21, 'gthtgyrt', 'Female', 'Elementary', '', NULL, 'mabiit', '', 'Grade 5', '35466565', 'afadsad, asda, asddad', 17, '2007-11-02', 'Hindu', 'weewqew', 'N/A', '[\"09876543212\"]', NULL, NULL, 'Active', '2025-09-21 08:45:58', '2025-09-21 08:45:58'),
(23, 'Thena-', 'Male', 'Senior High School', '', NULL, '', 'STEM', 'Grade 12', '9080989078', 'fdgfdf, fggd, dfdg', 15, '2009-11-02', 'Born Again Christian', 'BreaD', 'N/A', '[\"09767894542\"]', NULL, NULL, 'Active', '2025-09-21 11:42:37', '2025-09-21 11:42:37'),
(25, 'tuuuna', NULL, 'High School', 'N/A', NULL, 'magolo', 'N/A', 'Grade 8', '23123421', 'somewhere/onlyweknow', 15, '2009-11-02', 'Roman Catholic', 'BreaD', 'samlple', NULL, NULL, NULL, 'Active', '2025-09-21 14:44:16', '2025-09-21 14:44:16'),
(26, 'Thena-', 'Female', 'High School', 'N/A', 'N/A', 'mabiit', 'N/A', 'Grade 8', '43324234', 'adad, , qweqeq', 15, '2009-11-02', 'Islam', 'BreaDee', 'N/A', '[\"09767894542\"]', NULL, NULL, 'Active', '2025-09-21 14:44:19', '2025-09-26 15:51:42'),
(28, 'Angelic Ta', 'Female', 'College', 'BS in Information Technology', 'B', 'N/A', 'TVL', '1st Year', '453443', 'somewhere/onlyweknow, sdfdf, fsf', 17, '2007-11-02', 'Roman Catholic', 'BreaD', 'N/A', '[\"09767894542\"]', '', NULL, 'Active', '2025-09-21 14:44:23', '2025-09-26 09:34:52'),
(29, 'sampleee', 'Female', 'Elementary', '', '', 'mabiit', '', 'Grade 4', '7867865', 'fsfs, sdfsfsd, sfsdds', 9, '2015-11-02', '', 'BreaD', 'N/A', '[\"09876543212\"]', NULL, NULL, 'Active', '2025-09-21 17:13:58', '2025-09-21 17:13:58'),
(30, 'dadadad', 'Female', 'Elementary', 'N/A', 'N/A', 'adada', 'N/A', 'Grade 4', '34235523', 'dgawa, asdasda, adg', 17, '2007-11-02', 'Roman Catholic', 'rhrws', 'N/A', '[\"09767894542\"]', NULL, NULL, 'Active', '2025-09-21 17:28:14', '2025-09-22 11:29:47'),
(31, 'teena', 'Female', 'College', 'BS in Information Technology', '', 'N/A', 'N/A', '3rd Year', '5453434543', 'sdfasdasd, adad, ada', 19, '2005-11-02', 'United Methodist', 'BreaD', 'N/A', '[\"09765768323\"]', NULL, NULL, 'Active', '2025-09-21 17:28:17', '2025-09-22 10:29:21'),
(32, 'Angelic Taw', 'Female', 'Senior High School', '', '', 'adadaa', 'ABM', 'Grade 11', '6873465', 'adad, adad, adad', 15, '2009-11-02', 'Roman Catholic', 'adada', 'N/A', '[\"09876543212\"]', NULL, NULL, 'Graduated', '2025-09-21 17:28:19', '2025-10-01 05:46:16'),
(33, 'juan', 'Male', 'College', 'BS in Information Technology', 'B', '', '', '3rd Year', '56754646', 'adad, dadad, asda', 19, '2005-11-02', 'Roman Catholic', 'BreaDee', 'N/A', '[\"09767894542\"]', NULL, NULL, 'Active', '2025-09-22 10:38:45', '2025-09-22 10:38:45'),
(34, 'Juan Dela Cruz', 'Male', 'Senior High School', '', '', '', 'HUMSS', 'Grade 12', '564645645', 'Fdsf, Dfsfs, Sfsfs dfsf', 17, '2007-11-02', 'Iglesia ni Cristo', 'Ewfwefw Fwfwf', 'sfsds sqdsd wqwedqw', '[\"09876543212\"]', NULL, NULL, 'Active', '2025-09-22 10:45:14', '2025-09-22 10:45:14'),
(35, 'Grid', 'Male', 'Elementary', '', '', 'magolo', '', '', '63453534', 'Sffdf, Fdffd, Dfdfd', 5, '2019-11-02', 'Roman Catholic', 'Breadee', 'N/A', '[\"09876543212\"]', NULL, NULL, 'Active', '2025-09-26 11:48:11', '2025-09-26 17:55:30'),
(36, 'Jeremae Lalo', 'Female', 'College', 'BS in Information Technology', 'B', '', '', '3rd Year', '0284152906', 'San Bernardino, Guimba, Nueva Ecija', 20, '2005-05-12', 'Roman Catholic', 'Juanita Lalo', 'N/A', '[\"09291735907\"]', NULL, NULL, 'Active', '2025-10-01 04:17:56', '2025-10-01 05:42:50');

-- --------------------------------------------------------

--
-- Table structure for table `students_archive`
--

CREATE TABLE `students_archive` (
  `id` int(11) NOT NULL,
  `original_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `level` varchar(50) NOT NULL,
  `year_grade` varchar(20) DEFAULT NULL,
  `section` varchar(100) DEFAULT NULL,
  `strand` varchar(100) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `rfid` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `guardian` varchar(255) DEFAULT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `students_masked`
-- (See below for the actual view)
--
CREATE TABLE `students_masked` (
`id` int(10) unsigned
,`name` longtext
,`level` enum('Pre-school','Elementary','High School','Senior High School','College')
,`course` varchar(120)
,`section` varchar(50)
,`strand` varchar(50)
,`year_grade` varchar(40)
,`rfid` longtext
,`address` longtext
,`age` int(11)
,`dob` date
,`religion` varchar(80)
,`guardian` longtext
,`allergies` text
,`contacts` longtext
,`emergency_contact` longtext
,`medical_notes` text
,`status` enum('Active','Inactive','Graduated','Transferred')
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `student_medical_archive`
--

CREATE TABLE `student_medical_archive` (
  `id` int(10) UNSIGNED NOT NULL,
  `original_id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `patient_type` enum('student','faculty') NOT NULL,
  `form_type` varchar(100) NOT NULL,
  `form_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`form_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_by` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_visitation_archive`
--

CREATE TABLE `student_visitation_archive` (
  `id` int(10) UNSIGNED NOT NULL,
  `original_id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `patient_type` enum('student','faculty') NOT NULL,
  `reason` varchar(255) NOT NULL,
  `visit_date` datetime NOT NULL,
  `symptoms` text DEFAULT NULL,
  `other_notes` text DEFAULT NULL,
  `heart_rate` int(11) DEFAULT NULL,
  `blood_pressure` varchar(50) DEFAULT NULL,
  `temperature` decimal(4,2) DEFAULT NULL,
  `medication_given` tinyint(1) DEFAULT 0,
  `medication_name` varchar(255) DEFAULT NULL,
  `other_treatment` varchar(255) DEFAULT NULL,
  `medication_notes` text DEFAULT NULL,
  `injury` tinyint(1) DEFAULT 0,
  `first_aid_given` tinyint(1) DEFAULT 0,
  `first_aid_type` varchar(255) DEFAULT NULL,
  `nurse_name` varchar(100) DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_by` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rfid` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `verify_token` varchar(100) DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `failed_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `rfid`, `is_admin`, `is_active`, `verified`, `verify_token`, `reset_token`, `reset_expiry`, `last_login`, `failed_attempts`, `locked_until`, `created_at`, `updated_at`) VALUES
(4, 'Tunapay', 'er@gmail.com', '$2y$10$Nf.QGdQUIlUHkJh/uPxCkO1nIBG0jW/faceAR3nydAcZhOrPTcZE.', '$2y$10$yUBPRinX5VUQEho7/MZVA.vob3ZvKQ0ZhMANRymJeLZsX4oxTfIcO', 1, 1, 0, NULL, NULL, NULL, NULL, 0, NULL, '2025-09-22 05:00:39', '2025-09-22 05:24:47');

-- --------------------------------------------------------

--
-- Stand-in structure for view `users_masked`
-- (See below for the actual view)
--
CREATE TABLE `users_masked` (
`id` int(10) unsigned
,`name` longtext
,`email` longtext
,`rfid` longtext
,`is_admin` tinyint(1)
,`verified` tinyint(1)
,`last_login` timestamp
,`failed_attempts` int(11)
,`locked_until` timestamp
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `visitation_logs`
--

CREATE TABLE `visitation_logs` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `patient_type` enum('student','faculty') NOT NULL,
  `reason` varchar(100) NOT NULL,
  `visit_date` datetime NOT NULL,
  `symptoms` text DEFAULT NULL,
  `heart_rate` int(11) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `other_notes` text DEFAULT NULL,
  `medication_given` tinyint(1) DEFAULT 0,
  `medication_name` varchar(100) DEFAULT NULL,
  `other_treatment` varchar(100) DEFAULT NULL,
  `medication_notes` text DEFAULT NULL,
  `injury` tinyint(1) DEFAULT 0,
  `first_aid_given` tinyint(1) DEFAULT 0,
  `first_aid_type` varchar(100) DEFAULT NULL,
  `nurse_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visitation_logs`
--

INSERT INTO `visitation_logs` (`id`, `patient_id`, `patient_type`, `reason`, `visit_date`, `symptoms`, `heart_rate`, `blood_pressure`, `temperature`, `other_notes`, `medication_given`, `medication_name`, `other_treatment`, `medication_notes`, `injury`, `first_aid_given`, `first_aid_type`, `nurse_name`, `created_at`, `created_by`, `updated_at`, `archived`) VALUES
(54, 22, 'student', 'cold', '2025-09-21 18:17:00', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, 0, NULL, 'thena', '2025-09-21 10:17:27', 3, '2025-09-21 10:17:41', 1),
(55, 22, 'student', 'stomach_ache', '2025-09-21 18:17:00', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, 0, NULL, 'thena', '2025-09-21 10:17:31', 3, '2025-09-21 10:17:41', 1),
(58, 28, 'student', 'cold', '2025-09-21 23:00:00', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, 0, NULL, 'Unknown', '2025-09-21 15:02:04', 3, '2025-09-21 17:24:31', 1),
(60, 29, 'student', 'headache', '2025-09-22 01:14:00', NULL, 5, '90', 36.0, NULL, 0, NULL, NULL, NULL, 0, 0, NULL, 'Unknown', '2025-09-21 17:14:52', 3, '2025-09-21 17:15:16', 1),
(61, 26, 'student', 'dizziness', '2025-09-22 01:25:00', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, 0, NULL, 'thena', '2025-09-21 17:25:59', 3, '2025-09-21 17:26:10', 1),
(62, 20, 'student', 'headache', '2025-09-22 01:27:00', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, 0, NULL, 'thena', '2025-09-21 17:27:27', 3, '2025-09-21 17:27:33', 1),
(63, 5, 'faculty', 'dizziness', '2025-09-22 13:41:00', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, 0, NULL, 'Tunapay', '2025-09-22 05:41:51', 4, '2025-09-22 05:45:08', 1),
(64, 32, 'student', 'injury', '2025-09-22 13:53:00', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, 0, NULL, 'Tunapay', '2025-09-22 05:53:52', 4, '2025-09-22 06:14:55', 1),
(65, 28, 'student', 'dizziness', '2025-09-22 14:01:00', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, 0, NULL, 'Tunapay', '2025-09-22 06:01:26', 4, '2025-09-22 06:14:55', 1),
(66, 28, 'student', 'cough', '2025-09-22 14:10:00', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, 0, NULL, 'Tunapay', '2025-09-22 06:10:22', 4, '2025-09-22 06:14:55', 1),
(67, 28, 'student', 'headache', '2025-09-22 14:10:00', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 1, 1, 'ice_pack', 'Tunapay', '2025-09-22 06:11:15', 4, '2025-09-22 06:14:55', 1),
(68, 28, 'student', 'headache', '2025-09-22 14:14:00', 'N/A', NULL, NULL, NULL, 'N/A', 1, 'ice_pack', NULL, 'N/A', 0, 0, NULL, 'Tunapay', '2025-09-22 06:14:15', 4, '2025-09-22 06:14:55', 1),
(70, 30, 'student', 'fever', '2025-09-22 18:25:00', 'N/A', 79, '90', 36.0, 'N/A', 0, NULL, NULL, 'N/A', 0, 0, NULL, 'Unknown', '2025-09-22 10:27:02', 4, '2025-09-22 10:46:17', 1),
(71, 32, 'student', 'Vitals', '2025-09-24 20:08:00', 'N/A', 30, '30', 35.0, 'N/A', 0, NULL, NULL, 'N/A', 0, 0, NULL, 'Tunapay', '2025-09-24 12:08:40', 4, '2025-09-24 12:09:01', 1),
(72, 16, 'student', 'cough', '2025-09-26 15:20:00', 'N/A', NULL, NULL, NULL, 'N/A', 0, NULL, NULL, 'N/A', 0, 0, NULL, 'Tunapay', '2025-09-26 07:21:01', 4, '2025-09-27 11:29:31', 1),
(73, 28, 'student', 'dizziness', '2025-09-26 17:34:00', 'N/A', NULL, NULL, NULL, 'N/A', 0, NULL, NULL, 'N/A', 0, 0, NULL, 'Tunapay', '2025-09-26 10:22:33', 4, '2025-09-27 11:29:31', 1),
(74, 16, 'student', 'cold', '2025-09-26 19:09:00', 'N/A', 90, '120/80', 36.0, 'N/A', 0, NULL, NULL, 'N/A', 0, 0, NULL, 'Tunapay', '2025-09-26 11:10:09', 4, '2025-09-27 11:29:31', 1),
(75, 35, 'student', 'cough', '2025-09-26 19:48:00', 'N/A', NULL, NULL, NULL, 'N/A', 0, NULL, NULL, 'N/A', 0, 0, NULL, 'Tunapay', '2025-09-26 11:48:18', 4, '2025-09-27 11:29:31', 1),
(76, 35, 'student', 'fever', '2025-09-26 19:48:00', 'N/A', NULL, NULL, NULL, 'N/A', 0, NULL, NULL, 'N/A', 0, 0, NULL, 'Tunapay', '2025-09-26 11:48:27', 4, '2025-09-27 11:29:31', 1),
(77, 35, 'student', 'stomach_ache', '2025-09-26 19:48:00', 'N/A', NULL, NULL, NULL, 'N/A', 0, NULL, NULL, 'N/A', 0, 0, NULL, 'Tunapay', '2025-09-26 11:48:31', 4, '2025-09-27 11:29:31', 1),
(78, 16, 'student', 'cough', '2025-09-27 00:00:00', 'N/A', NULL, NULL, NULL, 'N/A', 0, NULL, NULL, 'N/A', 0, 0, NULL, 'Tunapay', '2025-09-27 11:35:01', 4, '2025-09-27 11:38:10', 1),
(79, 36, 'student', 'cold', '2025-10-01 00:00:00', 'N/A', NULL, NULL, NULL, 'N/A', 0, NULL, NULL, 'N/A', 0, 0, NULL, 'Tunapay', '2025-10-01 04:18:15', 4, '2025-10-01 04:20:33', 1),
(80, 36, 'student', 'dizziness', '2025-10-01 00:00:00', 'N/A', NULL, NULL, NULL, 'N/A', 0, NULL, NULL, 'N/A', 0, 0, NULL, 'Tunapay', '2025-10-01 05:42:58', 4, '2025-10-01 05:42:58', 0),
(82, 32, 'student', 'cold', '2025-10-01 00:00:00', 'N/A', NULL, NULL, NULL, 'N/A', 0, NULL, NULL, 'N/A', 0, 0, NULL, 'Unknown', '2025-10-01 05:46:08', 4, '2025-10-01 05:46:08', 0);

-- --------------------------------------------------------

--
-- Structure for view `athlete_medical_view`
--
DROP TABLE IF EXISTS `athlete_medical_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `athlete_medical_view`  AS SELECT `mr`.`id` AS `id`, `mr`.`patient_id` AS `patient_id`, `mr`.`patient_type` AS `patient_type`, `mr`.`created_at` AS `created_at`, `mr`.`created_by` AS `created_by`, `u`.`name` AS `created_by_name`, json_extract(`mr`.`form_data`,'$.sport') AS `sport`, json_extract(`mr`.`form_data`,'$.position') AS `position`, json_extract(`mr`.`form_data`,'$.height') AS `height`, json_extract(`mr`.`form_data`,'$.weight') AS `weight`, json_extract(`mr`.`form_data`,'$.medical_history') AS `medical_history`, json_extract(`mr`.`form_data`,'$.physical_exam') AS `physical_exam`, json_extract(`mr`.`form_data`,'$.recommendations') AS `recommendations` FROM (`medical_records` `mr` left join `users` `u` on(`mr`.`created_by` = `u`.`id`)) WHERE `mr`.`form_type` = 'athlete' ;

-- --------------------------------------------------------

--
-- Structure for view `emergency_medical_view`
--
DROP TABLE IF EXISTS `emergency_medical_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `emergency_medical_view`  AS SELECT `mr`.`id` AS `id`, `mr`.`patient_id` AS `patient_id`, `mr`.`patient_type` AS `patient_type`, `mr`.`created_at` AS `created_at`, `mr`.`created_by` AS `created_by`, `u`.`name` AS `created_by_name`, json_extract(`mr`.`form_data`,'$.emergency_type') AS `emergency_type`, json_extract(`mr`.`form_data`,'$.severity') AS `severity`, json_extract(`mr`.`form_data`,'$.emergency_description') AS `emergency_description`, json_extract(`mr`.`form_data`,'$.immediate_actions') AS `immediate_actions`, json_extract(`mr`.`form_data`,'$.heart_rate') AS `heart_rate`, json_extract(`mr`.`form_data`,'$.blood_pressure') AS `blood_pressure`, json_extract(`mr`.`form_data`,'$.temperature') AS `temperature`, json_extract(`mr`.`form_data`,'$.follow_up') AS `follow_up` FROM (`medical_records` `mr` left join `users` `u` on(`mr`.`created_by` = `u`.`id`)) WHERE `mr`.`form_type` = 'emergency' ;

-- --------------------------------------------------------

--
-- Structure for view `faculty_masked`
--
DROP TABLE IF EXISTS `faculty_masked`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `faculty_masked`  AS SELECT `faculty`.`id` AS `id`, concat(left(`faculty`.`name`,1),repeat('*',greatest(0,char_length(`faculty`.`name`) - 2)),right(`faculty`.`name`,1)) AS `name`, `faculty`.`department` AS `department`, `faculty`.`gender` AS `gender`, concat(repeat('*',greatest(0,char_length(`faculty`.`rfid`) - 4)),right(`faculty`.`rfid`,4)) AS `rfid`, concat(left(`faculty`.`address`,3),repeat('*',greatest(0,char_length(`faculty`.`address`) - 6)),right(`faculty`.`address`,3)) AS `address`, `faculty`.`age` AS `age`, `faculty`.`sr` AS `sr`, `faculty`.`dob` AS `dob`, `faculty`.`religion` AS `religion`, concat(left(`faculty`.`emergency_contact`,1),repeat('*',greatest(0,char_length(`faculty`.`emergency_contact`) - 2)),right(`faculty`.`emergency_contact`,1)) AS `emergency_contact`, `faculty`.`allergies` AS `allergies`, `faculty`.`medical_notes` AS `medical_notes`, `faculty`.`employee_id` AS `employee_id`, `faculty`.`position` AS `position`, `faculty`.`status` AS `status`, `faculty`.`created_at` AS `created_at`, `faculty`.`updated_at` AS `updated_at` FROM `faculty` ;

-- --------------------------------------------------------

--
-- Structure for view `general_medical_view`
--
DROP TABLE IF EXISTS `general_medical_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `general_medical_view`  AS SELECT `mr`.`id` AS `id`, `mr`.`patient_id` AS `patient_id`, `mr`.`patient_type` AS `patient_type`, `mr`.`created_at` AS `created_at`, `mr`.`created_by` AS `created_by`, `u`.`name` AS `created_by_name`, json_extract(`mr`.`form_data`,'$.chief_complaint') AS `chief_complaint`, json_extract(`mr`.`form_data`,'$.duration') AS `duration`, json_extract(`mr`.`form_data`,'$.history_present') AS `history_present`, json_extract(`mr`.`form_data`,'$.past_medical') AS `past_medical`, json_extract(`mr`.`form_data`,'$.physical_exam') AS `physical_exam`, json_extract(`mr`.`form_data`,'$.assessment_plan') AS `assessment_plan` FROM (`medical_records` `mr` left join `users` `u` on(`mr`.`created_by` = `u`.`id`)) WHERE `mr`.`form_type` = 'general' ;

-- --------------------------------------------------------

--
-- Structure for view `medical_history_view`
--
DROP TABLE IF EXISTS `medical_history_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `medical_history_view`  AS SELECT `mr`.`id` AS `id`, `mr`.`patient_id` AS `patient_id`, `mr`.`patient_type` AS `patient_type`, `mr`.`created_at` AS `created_at`, `mr`.`created_by` AS `created_by`, `u`.`name` AS `created_by_name`, json_extract(`mr`.`form_data`,'$.ongoing_conditions') AS `ongoing_conditions`, json_extract(`mr`.`form_data`,'$.ongoing_conditions_other') AS `ongoing_conditions_other`, json_extract(`mr`.`form_data`,'$.surgery_status') AS `surgery_status`, json_extract(`mr`.`form_data`,'$.surgery_details') AS `surgery_details`, json_extract(`mr`.`form_data`,'$.family_conditions') AS `family_conditions`, json_extract(`mr`.`form_data`,'$.family_conditions_other') AS `family_conditions_other`, json_extract(`mr`.`form_data`,'$.smoke_exposure') AS `smoke_exposure`, json_extract(`mr`.`form_data`,'$.immunization') AS `immunization`, json_extract(`mr`.`form_data`,'$.covid_vaccine') AS `covid_vaccine`, json_extract(`mr`.`form_data`,'$.covid_positive') AS `covid_positive`, json_extract(`mr`.`form_data`,'$.covid_details') AS `covid_details` FROM (`medical_records` `mr` left join `users` `u` on(`mr`.`created_by` = `u`.`id`)) WHERE `mr`.`form_type` = 'medical_history' ;

-- --------------------------------------------------------

--
-- Structure for view `students_masked`
--
DROP TABLE IF EXISTS `students_masked`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `students_masked`  AS SELECT `students`.`id` AS `id`, concat(left(`students`.`name`,1),repeat('*',greatest(0,char_length(`students`.`name`) - 2)),right(`students`.`name`,1)) AS `name`, `students`.`level` AS `level`, `students`.`course` AS `course`, `students`.`section` AS `section`, `students`.`strand` AS `strand`, `students`.`year_grade` AS `year_grade`, concat(repeat('*',greatest(0,char_length(`students`.`rfid`) - 4)),right(`students`.`rfid`,4)) AS `rfid`, concat(left(`students`.`address`,3),repeat('*',greatest(0,char_length(`students`.`address`) - 6)),right(`students`.`address`,3)) AS `address`, `students`.`age` AS `age`, `students`.`dob` AS `dob`, `students`.`religion` AS `religion`, concat(left(`students`.`guardian`,1),repeat('*',greatest(0,char_length(`students`.`guardian`) - 2)),right(`students`.`guardian`,1)) AS `guardian`, `students`.`allergies` AS `allergies`, `students`.`contacts` AS `contacts`, concat(left(`students`.`emergency_contact`,1),repeat('*',greatest(0,char_length(`students`.`emergency_contact`) - 2)),right(`students`.`emergency_contact`,1)) AS `emergency_contact`, `students`.`medical_notes` AS `medical_notes`, `students`.`status` AS `status`, `students`.`created_at` AS `created_at`, `students`.`updated_at` AS `updated_at` FROM `students` ;

-- --------------------------------------------------------

--
-- Structure for view `users_masked`
--
DROP TABLE IF EXISTS `users_masked`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `users_masked`  AS SELECT `users`.`id` AS `id`, concat(left(`users`.`name`,1),repeat('*',greatest(0,char_length(`users`.`name`) - 2)),right(`users`.`name`,1)) AS `name`, concat(left(`users`.`email`,2),repeat('*',greatest(0,char_length(`users`.`email`) - 4)),right(`users`.`email`,2)) AS `email`, concat(repeat('*',greatest(0,char_length(`users`.`rfid`) - 4)),right(`users`.`rfid`,4)) AS `rfid`, `users`.`is_admin` AS `is_admin`, `users`.`verified` AS `verified`, `users`.`last_login` AS `last_login`, `users`.`failed_attempts` AS `failed_attempts`, `users`.`locked_until` AS `locked_until`, `users`.`created_at` AS `created_at`, `users`.`updated_at` AS `updated_at` FROM `users` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_timestamp` (`timestamp`),
  ADD KEY `idx_success` (`success`),
  ADD KEY `idx_activity_archived` (`archived`);

--
-- Indexes for table `archived_medical_records`
--
ALTER TABLE `archived_medical_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient` (`patient_id`,`patient_type`),
  ADD KEY `idx_archived_at` (`archived_at`),
  ADD KEY `idx_original_id` (`original_id`);

--
-- Indexes for table `checkup_forms`
--
ALTER TABLE `checkup_forms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_patient_type` (`patient_type`),
  ADD KEY `idx_checkup_date` (`checkup_date`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `daily_logs`
--
ALTER TABLE `daily_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_log_date` (`log_date`);

--
-- Indexes for table `enrollment_history`
--
ALTER TABLE `enrollment_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_enrollment_type` (`enrollment_type`),
  ADD KEY `idx_enrollment_date` (`enrollment_date`),
  ADD KEY `idx_enrollment_year` (`enrollment_year`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rfid` (`rfid`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_rfid` (`rfid`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `faculty_archive`
--
ALTER TABLE `faculty_archive`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_original_id` (`original_id`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_archived_at` (`archived_at`);

--
-- Indexes for table `faculty_medical_archive`
--
ALTER TABLE `faculty_medical_archive`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_original_id` (`original_id`),
  ADD KEY `idx_archived_at` (`archived_at`),
  ADD KEY `idx_patient_type` (`patient_type`),
  ADD KEY `archived_by` (`archived_by`);

--
-- Indexes for table `faculty_visitation_archive`
--
ALTER TABLE `faculty_visitation_archive`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_original_id` (`original_id`),
  ADD KEY `idx_archived_at` (`archived_at`),
  ADD KEY `idx_patient_type` (`patient_type`),
  ADD KEY `archived_by` (`archived_by`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient` (`patient_id`,`patient_type`),
  ADD KEY `idx_form_type` (`form_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_medical_records_form_type` (`form_type`),
  ADD KEY `idx_medical_records_patient_type` (`patient_type`),
  ADD KEY `idx_medical_records_created_at` (`created_at`),
  ADD KEY `idx_medical_records_patient` (`patient_id`,`patient_type`),
  ADD KEY `idx_medical_records_type` (`form_type`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rfid` (`rfid`),
  ADD KEY `idx_level` (`level`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_rfid` (`rfid`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `students_archive`
--
ALTER TABLE `students_archive`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_original_id` (`original_id`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_level` (`level`),
  ADD KEY `idx_archived_at` (`archived_at`);

--
-- Indexes for table `student_medical_archive`
--
ALTER TABLE `student_medical_archive`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_original_id` (`original_id`),
  ADD KEY `idx_archived_at` (`archived_at`),
  ADD KEY `idx_patient_type` (`patient_type`),
  ADD KEY `archived_by` (`archived_by`);

--
-- Indexes for table `student_visitation_archive`
--
ALTER TABLE `student_visitation_archive`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_original_id` (`original_id`),
  ADD KEY `idx_archived_at` (`archived_at`),
  ADD KEY `idx_patient_type` (`patient_type`),
  ADD KEY `archived_by` (`archived_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_rfid` (`rfid`),
  ADD KEY `idx_is_admin` (`is_admin`);

--
-- Indexes for table `visitation_logs`
--
ALTER TABLE `visitation_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient` (`patient_id`,`patient_type`),
  ADD KEY `idx_visit_date` (`visit_date`),
  ADD KEY `idx_reason` (`reason`),
  ADD KEY `idx_visitation_logs_patient` (`patient_id`,`patient_type`),
  ADD KEY `idx_visitation_logs_date` (`visit_date`),
  ADD KEY `idx_visitation_archived` (`archived`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=493;

--
-- AUTO_INCREMENT for table `archived_medical_records`
--
ALTER TABLE `archived_medical_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `checkup_forms`
--
ALTER TABLE `checkup_forms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_logs`
--
ALTER TABLE `daily_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `enrollment_history`
--
ALTER TABLE `enrollment_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `faculty_archive`
--
ALTER TABLE `faculty_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faculty_medical_archive`
--
ALTER TABLE `faculty_medical_archive`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faculty_visitation_archive`
--
ALTER TABLE `faculty_visitation_archive`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `students_archive`
--
ALTER TABLE `students_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `student_medical_archive`
--
ALTER TABLE `student_medical_archive`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student_visitation_archive`
--
ALTER TABLE `student_visitation_archive`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `visitation_logs`
--
ALTER TABLE `visitation_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `checkup_forms`
--
ALTER TABLE `checkup_forms`
  ADD CONSTRAINT `checkup_forms_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollment_history`
--
ALTER TABLE `enrollment_history`
  ADD CONSTRAINT `enrollment_history_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollment_history_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `faculty_medical_archive`
--
ALTER TABLE `faculty_medical_archive`
  ADD CONSTRAINT `faculty_medical_archive_ibfk_1` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `faculty_visitation_archive`
--
ALTER TABLE `faculty_visitation_archive`
  ADD CONSTRAINT `faculty_visitation_archive_ibfk_1` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_medical_archive`
--
ALTER TABLE `student_medical_archive`
  ADD CONSTRAINT `student_medical_archive_ibfk_1` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_visitation_archive`
--
ALTER TABLE `student_visitation_archive`
  ADD CONSTRAINT `student_visitation_archive_ibfk_1` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
