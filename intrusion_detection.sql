-- phpMyAdmin SQL Dump
-- Intrusion Detection System Database
-- Encoding: UTF-8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `intrusion_detection`
--
DROP DATABASE IF EXISTS `intrusion_detection`;
CREATE DATABASE IF NOT EXISTS `intrusion_detection` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `intrusion_detection`;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `username` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `verified` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `login_attempts` INT DEFAULT 0,
    `last_failed_login` DATETIME DEFAULT NULL,
    `password_changed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_username` (`username`),
    UNIQUE KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `user_otps`
--
DROP TABLE IF EXISTS `user_otps`;
CREATE TABLE `user_otps` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `otp` VARCHAR(6) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NOT NULL,
    `used` TINYINT(1) DEFAULT 0,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `incidents`
--
DROP TABLE IF EXISTS `incidents`;
CREATE TABLE `incidents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `location` VARCHAR(255) NOT NULL,
    `date_time` DATETIME NOT NULL,
    `severity` ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL,
    `reported_by` VARCHAR(100) NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'New',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `incident_notes`
--
DROP TABLE IF EXISTS `incident_notes`;
CREATE TABLE `incident_notes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `incident_id` INT NOT NULL,
    `note` TEXT NOT NULL,
    `added_by` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`incident_id`) REFERENCES `incidents`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `login_attempts`
--
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `ip_address` VARCHAR(45) NOT NULL,
    `username` VARCHAR(255) DEFAULT NULL,
    `attempts` INT NOT NULL DEFAULT 0,
    `last_attempt` INT NOT NULL,
    `blocked_until` DATETIME DEFAULT NULL,
    UNIQUE KEY `idx_ip` (`ip_address`),
    INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `logs`
--
DROP TABLE IF EXISTS `logs`;
CREATE TABLE `logs` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `source_ip` VARCHAR(45) NOT NULL,
    `destination_ip` VARCHAR(45) NOT NULL,
    `protocol` VARCHAR(50) NOT NULL,
    `alert` TEXT NOT NULL,
    `severity` ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
    INDEX `idx_timestamp` (`timestamp`),
    INDEX `idx_severity` (`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `system_settings`
--
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT NOT NULL,
    `setting_description` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`username`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Default system settings
--
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_description`) VALUES
('smtp_host', 'smtp.gmail.com', 'SMTP server hostname'),
('smtp_port', '587', 'SMTP server port'),
('smtp_username', '', 'SMTP authentication username'),
('smtp_from', 'noreply@yourdomain.com', 'From email address for system notifications'),
('max_login_attempts', '3', 'Maximum number of failed login attempts before lockout'),
('lockout_time', '30', 'Account lockout duration in minutes'),
('password_expiry_days', '90', 'Number of days before password expires'),
('session_timeout', '30', 'Session timeout in minutes'),
('min_password_length', '8', 'Minimum password length'),
('require_special_chars', '1', 'Require special characters in password'),
('maintenance_mode', '0', 'System maintenance mode'),
('log_retention_days', '90', 'Number of days to keep logs')
ON DUPLICATE KEY UPDATE 
    `setting_value` = VALUES(`setting_value`),
    `setting_description` = VALUES(`setting_description`);

--
-- Default admin user (password: admin123)
--
INSERT INTO `users` (`username`, `password`, `email`, `role`, `verified`) VALUES
('admin', '$2y$10$aMdtEmQAgdYl48DRGCPTg.MDT02wneJOOCLJbMPQCqhE/ccla0pWG', 'admin@example.com', 'admin', 1);

--
-- Create maintenance triggers
--
DELIMITER //

-- Clean up old logs
CREATE EVENT `ev_clean_old_logs`
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
    DELETE FROM `logs` 
    WHERE `timestamp` < DATE_SUB(NOW(), 
    INTERVAL (SELECT CAST(setting_value AS SIGNED) FROM system_settings WHERE setting_key = 'log_retention_days') DAY);
END//

DELIMITER ;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;