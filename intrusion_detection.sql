-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 29, 2025 at 03:16 AM
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
-- Database: `intrusion_detection`
--

-- --------------------------------------------------------

--
-- Table structure for table `incidents`
--

CREATE TABLE `incidents` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `location` varchar(255) NOT NULL,
  `date_time` datetime NOT NULL,
  `severity` enum('Low','Medium','High','Critical') NOT NULL,
  `reported_by` varchar(100) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'New',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incidents`
--

INSERT INTO `incidents` (`id`, `title`, `description`, `location`, `date_time`, `severity`, `reported_by`, `status`, `created_at`, `updated_at`) VALUES
(1, 'jhasen', 'HAHAHAHA', 'libona', '2025-05-28 21:56:00', 'Critical', 'jhasen2', 'Resolved', '2025-05-28 13:56:47', '2025-05-28 14:49:34'),
(2, 'liljhass', 'Tabang', 'libona', '2025-05-28 16:42:05', 'High', 'jhasen2', 'In Progress', '2025-05-28 14:42:05', '2025-05-28 14:49:17'),
(3, 'JHAZZZZ', 'AHHHHHHHH TABANG LANGIT', 'libona', '2025-05-28 16:57:28', 'Low', 'jhasu', 'Closed', '2025-05-28 14:57:28', '2025-05-28 14:58:03'),
(4, 'ghgegf', 'wdd24f', 'libona', '2025-05-28 16:59:02', 'Critical', 'jhasu', 'Closed', '2025-05-28 14:59:02', '2025-05-28 14:59:31'),
(5, 'login attempt', 'someone attemp to login', 'campus', '2025-05-29 02:40:54', 'Medium', 'Jonard', 'Resolved', '2025-05-29 00:40:54', '2025-05-29 01:10:28'),
(6, 'Security Concern', 'Someone Attempt to Login', 'Libona', '2025-05-29 03:13:37', 'Medium', 'Jonard', 'New', '2025-05-29 01:13:37', '2025-05-29 01:13:37');

-- --------------------------------------------------------

--
-- Table structure for table `incident_attachments`
--

CREATE TABLE `incident_attachments` (
  `id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_notes`
--

CREATE TABLE `incident_notes` (
  `id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `added_by` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incident_notes`
--

INSERT INTO `incident_notes` (`id`, `incident_id`, `note`, `added_by`, `created_at`) VALUES
(1, 1, 'ok', 'liljhas', '2025-05-28 14:05:37'),
(2, 2, 'yezzz', 'liljhas', '2025-05-28 14:49:17'),
(3, 1, 'yezz', 'liljhas', '2025-05-28 14:49:34'),
(4, 3, 'HUMANA', 'liljhas', '2025-05-28 14:58:03'),
(5, 4, 'defrf', 'liljhas', '2025-05-28 14:59:31'),
(6, 5, 'GSGHFH', 'liljhas', '2025-05-29 00:41:35');

-- --------------------------------------------------------

--
-- Table structure for table `incident_reports`
--

CREATE TABLE `incident_reports` (
  `id` int(11) NOT NULL,
  `incident_type` varchar(50) NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `description` text NOT NULL,
  `reported_by` varchar(255) NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','investigating','resolved','closed') NOT NULL DEFAULT 'pending',
  `resolution_notes` text DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `incident_reports`
--
DELIMITER $$
CREATE TRIGGER `tr_incident_resolved` BEFORE UPDATE ON `incident_reports` FOR EACH ROW BEGIN
    IF NEW.status = 'resolved' AND OLD.status != 'resolved' THEN
        SET NEW.resolved_at = CURRENT_TIMESTAMP;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_attempt` int(11) NOT NULL,
  `blocked_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `ip_address`, `username`, `attempts`, `last_attempt`, `blocked_until`) VALUES
(12, '::1', NULL, 5, 1748481296, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `source_ip` varchar(45) NOT NULL,
  `destination_ip` varchar(45) NOT NULL,
  `protocol` varchar(50) NOT NULL,
  `alert` text NOT NULL,
  `severity` enum('info','warning','error','critical') DEFAULT 'info'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`id`, `timestamp`, `source_ip`, `destination_ip`, `protocol`, `alert`, `severity`) VALUES
(1, '2025-05-28 13:16:12', '::1', 'Server', 'Login', 'Failed Login Attempt for liljhas', 'info'),
(2, '2025-05-28 13:31:49', '::1', 'Server', 'Login', 'Successful Login for liljhas', 'info'),
(3, '2025-05-28 13:31:53', '::1', 'Server', 'Logout', 'User liljhas logged out', 'info'),
(4, '2025-05-28 13:32:27', '::1', 'Server', 'Login', 'Successful Login for liljhas', 'info'),
(5, '2025-05-28 13:34:32', '::1', 'Server', 'Login', 'Successful Login for jhasen', 'info'),
(6, '2025-05-28 13:38:07', '::1', 'Server', 'Logout', 'User jhasen logged out', 'info'),
(7, '2025-05-28 13:38:18', '::1', 'Server', 'Login', 'Successful Login for liljhas', 'info'),
(8, '2025-05-28 13:38:41', '::1', 'Server', 'Login', 'Successful Login for jhasen', 'info'),
(9, '2025-05-28 13:41:47', '::1', '', 'Security', 'Profile updated by user: jhasen2', 'info'),
(10, '2025-05-28 13:41:53', '::1', 'Server', 'Logout', 'User jhasen2 logged out', 'info'),
(11, '2025-05-28 13:42:07', '::1', 'Server', 'Login', 'Successful Login for jhasen2', 'info'),
(12, '2025-05-28 13:56:58', '::1', 'Server', 'Logout', 'User jhasen2 logged out', 'info'),
(13, '2025-05-28 13:57:06', '::1', 'Server', 'Login', 'Successful Login for liljhas', 'info'),
(14, '2025-05-28 13:57:33', '::1', 'Server', 'Login', 'Successful Login for jhasen2', 'info'),
(15, '2025-05-28 14:04:54', '::1', 'Server', 'Logout', 'User jhasen2 logged out', 'info'),
(16, '2025-05-28 14:05:06', '::1', 'Server', 'Login', 'Successful Login for liljhas', 'info'),
(17, '2025-05-28 14:05:51', '::1', 'Server', 'Login', 'Successful Login for jhasen2', 'info'),
(18, '2025-05-28 14:07:11', '::1', 'Server', 'Logout', 'User jhasen2 logged out', 'info'),
(19, '2025-05-28 14:07:21', '::1', 'Server', 'Login', 'Successful Login for liljhas', 'info'),
(20, '2025-05-28 14:11:50', '::1', 'Server', 'Logout', 'User liljhas logged out', 'info'),
(21, '2025-05-28 14:12:03', '::1', 'Server', 'Login', 'Successful Login for jhasen2', 'info'),
(22, '2025-05-28 14:48:25', '::1', 'Server', 'Logout', 'User jhasen2 logged out', 'info'),
(23, '2025-05-28 14:48:33', '::1', 'Server', 'Login', 'Successful Login for liljhas', 'info'),
(24, '2025-05-28 14:49:46', '::1', 'Server', 'Logout', 'User liljhas logged out', 'info'),
(25, '2025-05-28 14:49:55', '::1', 'Server', 'Login', 'Successful Login for jhasen2', 'info'),
(26, '2025-05-28 14:50:05', '::1', 'Server', 'Logout', 'User jhasen2 logged out', 'info'),
(27, '2025-05-28 14:50:14', '::1', 'Server', 'Login', 'Successful Login for liljhas', 'info'),
(28, '2025-05-28 14:55:22', '::1', 'Server', 'User Management', 'User deleted: jhasu (user) by liljhas', 'info'),
(29, '2025-05-28 14:56:58', '::1', 'Server', 'Login', 'Successful Login for jhasu', 'info'),
(30, '2025-05-28 14:57:30', '::1', 'Server', 'Logout', 'User jhasu logged out', 'info'),
(31, '2025-05-28 14:57:41', '::1', 'Server', 'Login', 'Successful Login for liljhas', 'info'),
(32, '2025-05-28 14:58:09', '::1', 'Server', 'Logout', 'User liljhas logged out', 'info'),
(33, '2025-05-28 14:58:24', '::1', 'Server', 'Login', 'Successful Login for jhasu', 'info'),
(34, '2025-05-28 14:59:05', '::1', 'Server', 'Logout', 'User jhasu logged out', 'info'),
(35, '2025-05-28 14:59:15', '::1', 'Server', 'Login', 'Successful Login for liljhas', 'info'),
(36, '2025-05-28 14:59:34', '::1', 'Server', 'Logout', 'User liljhas logged out', 'info'),
(37, '2025-05-28 14:59:44', '::1', 'Server', 'Login', 'Successful Login for jhasu', 'info'),
(38, '2025-05-28 15:00:33', '::1', 'Server', 'Logout', 'User jhasu logged out', 'info'),
(39, '2025-05-28 15:00:46', '::1', 'Server', 'Login', 'Successful Login for liljhas', 'info'),
(40, '2025-05-28 15:02:11', '::1', 'Server', 'Login', 'Failed Login Attempt for liljhas', 'info'),
(41, '2025-05-28 15:02:19', '::1', 'Server', 'Login', 'Failed Login Attempt for liljhas', 'info'),
(42, '2025-05-28 15:02:27', '::1', 'Server', 'Login', 'Failed Login Attempt for liljhas', 'info'),
(43, '2025-05-28 15:12:29', '::1', 'Server', 'Login', 'Successful Login for liljhas', 'info'),
(44, '2025-05-28 15:15:37', '::1', 'Server', 'Login', 'Failed Login Attempt for jhasu', 'info'),
(45, '2025-05-28 15:18:19', '::1', 'Server', 'Login', 'Successful Login for liljhas', 'info'),
(46, '2025-05-28 15:18:52', '::1', 'Server', 'Login', 'Failed Login Attempt for jhasu', 'info'),
(47, '2025-05-28 15:19:07', '::1', 'Server', 'Login', 'Failed Login Attempt for jhasu', 'info'),
(48, '2025-05-28 15:19:18', '::1', 'Server', 'Login', 'Failed Login Attempt for jhasu', 'info'),
(49, '2025-05-28 15:19:29', '::1', 'Server', 'Login', 'Failed Login Attempt for jhasu', 'info'),
(50, '2025-05-28 15:19:38', '::1', 'Server', 'Login', 'Failed Login Attempt for jhasu', 'info'),
(51, '2025-05-28 15:20:52', '::1', 'Server', 'Login', 'Successful Login for jhasu', 'info'),
(52, '2025-05-28 15:20:58', '::1', 'Server', 'Logout', 'User jhasu logged out', 'info'),
(53, '2025-05-29 00:39:37', '::1', 'Server', 'Login', 'Failed Login Attempt for Jonard', 'info'),
(54, '2025-05-29 00:39:59', '::1', 'Server', 'Login', 'Successful Login for Jonard', 'info'),
(55, '2025-05-29 00:40:59', '::1', 'Server', 'Logout', 'User Jonard logged out', 'info'),
(56, '2025-05-29 00:41:19', '::1', 'Server', 'Login', 'Successful Login for liljhas', 'info'),
(57, '2025-05-29 00:41:39', '::1', 'Server', 'Logout', 'User liljhas logged out', 'info'),
(58, '2025-05-29 00:41:54', '::1', 'Server', 'Login', 'Successful Login for Jonard', 'info'),
(59, '2025-05-29 01:05:48', '::1', 'Server', 'Login', 'Successful Login for Jonard', 'info'),
(60, '2025-05-29 01:06:26', '::1', 'Server', 'Logout', 'User Jonard logged out', 'info'),
(61, '2025-05-29 01:10:14', '::1', 'Server', 'Login', 'Successful Login for liljhas', 'info'),
(62, '2025-05-29 01:10:37', '::1', 'Server', 'Logout', 'User liljhas logged out', 'info'),
(63, '2025-05-29 01:10:49', '::1', 'Server', 'Login', 'Successful Login for Jonard', 'info'),
(64, '2025-05-29 01:13:40', '::1', 'Server', 'Logout', 'User Jonard logged out', 'info'),
(65, '2025-05-29 01:13:55', '::1', 'Server', 'Login', 'Successful Login for liljhas', 'info'),
(66, '2025-05-29 01:14:01', '::1', 'Server', 'Logout', 'User liljhas logged out', 'info'),
(67, '2025-05-29 01:14:10', '::1', 'Server', 'Login', 'Failed Login Attempt for Jonard', 'info'),
(68, '2025-05-29 01:14:24', '::1', 'Server', 'Login', 'Failed Login Attempt for Jonard', 'info'),
(69, '2025-05-29 01:14:36', '::1', 'Server', 'Login', 'Failed Login Attempt for Jonard', 'info'),
(70, '2025-05-29 01:14:46', '::1', 'Server', 'Login', 'Failed Login Attempt for Jonard', 'info'),
(71, '2025-05-29 01:14:56', '::1', 'Server', 'Login', 'Failed Login Attempt for Jonard', 'info');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_description`, `updated_at`, `updated_by`) VALUES
('lockout_time', '1', 'Account lockout duration in minutes', '2025-05-28 15:01:38', NULL),
('log_retention_days', '90', 'Number of days to keep logs', '2025-05-28 13:09:10', NULL),
('maintenance_mode', '0', 'System maintenance mode', '2025-05-28 13:09:10', NULL),
('max_login_attempts', '5', 'Maximum number of failed login attempts before lockout', '2025-05-28 15:18:40', NULL),
('min_password_length', '8', 'Minimum password length', '2025-05-28 13:09:10', NULL),
('password_expiry_days', '90', 'Number of days before password expires', '2025-05-28 13:09:10', NULL),
('require_special_chars', '1', 'Require special characters in password', '2025-05-28 13:09:10', NULL),
('session_timeout', '30', 'Session timeout in minutes', '2025-05-28 13:09:10', NULL),
('smtp_from', 'noreply@yourdomain.com', 'From email address for system notifications', '2025-05-28 13:09:10', NULL),
('smtp_host', 'smtp.gmail.com', 'SMTP server hostname', '2025-05-28 13:09:10', NULL),
('smtp_port', '587', 'SMTP server port', '2025-05-28 13:09:10', NULL),
('smtp_username', '', 'SMTP authentication username', '2025-05-28 13:09:10', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `login_attempts` int(11) DEFAULT 0,
  `last_failed_login` datetime DEFAULT NULL,
  `password_changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `phone`, `role`, `verified`, `created_at`, `updated_at`, `login_attempts`, `last_failed_login`, `password_changed_at`) VALUES
(5, 'liljhas', '$2y$10$L5xM2RrQzIxNkCwfOelWFumKSy3kjGY6aIBYQ9Nqy/YMDp.sIXsMC', 'jhasenambogna119@gmail.com', NULL, 'admin', 1, '2025-05-28 13:31:03', '2025-05-28 13:32:10', 0, NULL, '2025-05-28 13:31:03'),
(7, 'jhasu', '$2y$10$h4/UyoIbOEOU8F/MA2bfZuJ4aJR3EW2TkX1RNo5mcD.kcCAJGSE7y', '20221084@nbsc.edu.ph', NULL, 'user', 1, '2025-05-28 14:56:22', '2025-05-28 14:56:43', 0, NULL, '2025-05-28 14:56:22'),
(8, 'Jonard', '$2y$10$jyVXIsc46dV7w8BqGVY75.GHLSC3QkkYy3rdvNjU5Kv5KcUFh6q8K', 'brawlstarsupercellid79@gmail.com', NULL, 'user', 1, '2025-05-29 00:38:59', '2025-05-29 00:39:26', 0, NULL, '2025-05-29 00:38:59');

-- --------------------------------------------------------

--
-- Table structure for table `user_otps`
--

CREATE TABLE `user_otps` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_otps`
--

INSERT INTO `user_otps` (`id`, `user_id`, `otp`, `created_at`, `expires_at`, `used`) VALUES
(4, 5, '333540', '2025-05-28 13:31:03', '2025-05-28 05:36:03', 1),
(6, 7, '588470', '2025-05-28 14:56:22', '2025-05-28 07:01:22', 1),
(7, 8, '591731', '2025-05-29 00:38:59', '2025-05-28 16:43:59', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `incident_attachments`
--
ALTER TABLE `incident_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `incident_id` (`incident_id`);

--
-- Indexes for table `incident_notes`
--
ALTER TABLE `incident_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `incident_id` (`incident_id`);

--
-- Indexes for table `incident_reports`
--
ALTER TABLE `incident_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reported_by` (`reported_by`),
  ADD KEY `resolved_by` (`resolved_by`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_username` (`username`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_timestamp` (`timestamp`),
  ADD KEY `idx_severity` (`severity`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_username` (`username`),
  ADD UNIQUE KEY `idx_email` (`email`);

--
-- Indexes for table `user_otps`
--
ALTER TABLE `user_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `incidents`
--
ALTER TABLE `incidents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `incident_attachments`
--
ALTER TABLE `incident_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incident_notes`
--
ALTER TABLE `incident_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `incident_reports`
--
ALTER TABLE `incident_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user_otps`
--
ALTER TABLE `user_otps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `incident_attachments`
--
ALTER TABLE `incident_attachments`
  ADD CONSTRAINT `incident_attachments_ibfk_1` FOREIGN KEY (`incident_id`) REFERENCES `incident_reports` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `incident_notes`
--
ALTER TABLE `incident_notes`
  ADD CONSTRAINT `incident_notes_ibfk_1` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `incident_reports`
--
ALTER TABLE `incident_reports`
  ADD CONSTRAINT `incident_reports_ibfk_1` FOREIGN KEY (`reported_by`) REFERENCES `users` (`username`) ON DELETE CASCADE,
  ADD CONSTRAINT `incident_reports_ibfk_2` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`username`) ON DELETE SET NULL;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`username`) ON DELETE SET NULL;

--
-- Constraints for table `user_otps`
--
ALTER TABLE `user_otps`
  ADD CONSTRAINT `user_otps_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `ev_clean_old_logs` ON SCHEDULE EVERY 1 DAY STARTS '2025-05-28 13:09:10' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    DELETE FROM `logs` 
    WHERE `timestamp` < DATE_SUB(NOW(), 
    INTERVAL (SELECT CAST(setting_value AS SIGNED) FROM system_settings WHERE setting_key = 'log_retention_days') DAY);
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
