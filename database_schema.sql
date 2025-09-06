-- Project Management Database Schema for MySQL
-- Database: agile

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Create database if not exists (for cPanel)
CREATE DATABASE IF NOT EXISTS `agile` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `agile`;

-- --------------------------------------------------------

-- Table structure for table `users`
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `user_role` enum('ADMIN','SCRUM_MASTER','USER') DEFAULT 'USER',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `teams`
CREATE TABLE `teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `teams_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `team_members`
CREATE TABLE `team_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('ADMIN','MEMBER','VIEWER') DEFAULT 'MEMBER',
  `joined_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `team_user_unique` (`team_id`, `user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `team_members_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `team_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `projects`
CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('PLANNING','ACTIVE','ARCHIVED','COMPLETED') DEFAULT 'ACTIVE',
  `created_by` int(11) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `start_date` timestamp NULL DEFAULT NULL,
  `target_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_key` (`key`),
  KEY `created_by` (`created_by`),
  KEY `team_id` (`team_id`),
  CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `work_items`
CREATE TABLE `work_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `external_id` varchar(20) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('EPIC','FEATURE','STORY','TASK','BUG') NOT NULL,
  `status` enum('TODO','IN_PROGRESS','DONE') DEFAULT 'TODO',
  `priority` enum('LOW','MEDIUM','HIGH','CRITICAL') DEFAULT 'MEDIUM',
  `project_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `assignee_id` int(11) DEFAULT NULL,
  `reporter_id` int(11) DEFAULT NULL,
  `estimate` decimal(10,2) DEFAULT NULL,
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `external_id` (`external_id`),
  KEY `project_id` (`project_id`),
  KEY `parent_id` (`parent_id`),
  KEY `assignee_id` (`assignee_id`),
  KEY `reporter_id` (`reporter_id`),
  CONSTRAINT `work_items_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `work_items_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `work_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `work_items_ibfk_3` FOREIGN KEY (`assignee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `work_items_ibfk_4` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `comments`
CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `work_item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `work_item_id` (`work_item_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`work_item_id`) REFERENCES `work_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `work_item_history`
CREATE TABLE `work_item_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `work_item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `field` varchar(50) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `changed_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `work_item_id` (`work_item_id`),
  KEY `user_id` (`user_id`),
  KEY `changed_at` (`changed_at`),
  CONSTRAINT `work_item_history_ibfk_1` FOREIGN KEY (`work_item_id`) REFERENCES `work_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `work_item_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `attachments`
CREATE TABLE `attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `work_item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `work_item_id` (`work_item_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`work_item_id`) REFERENCES `work_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attachments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Insert sample data

-- Sample users for Cybaem Tech
INSERT INTO `users` (`username`, `email`, `full_name`, `password`, `user_role`) VALUES
('admin', 'admin@cybaemtech.com', 'System Administrator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMIN'),
('scrummaster', 'scrum@cybaemtech.com', 'Scrum Master', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'SCRUM_MASTER'),
('manager', 'manager@cybaemtech.com', 'Project Manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'SCRUM_MASTER'),
('developer1', 'dev1@cybaemtech.com', 'Senior Developer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'USER'),
('developer2', 'dev2@cybaemtech.com', 'Frontend Developer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'USER'),
('tester', 'tester@cybaemtech.com', 'QA Engineer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'USER');

-- Sample teams
INSERT INTO `teams` (`name`, `description`, `created_by`) VALUES
('Engineering Team', 'Main development team', 1),
('QA Team', 'Quality assurance team', 1),
('Product Team', 'Product management team', 1);

-- Sample team members
INSERT INTO `team_members` (`team_id`, `user_id`, `role`) VALUES
(1, 1, 'ADMIN'),
(1, 2, 'MEMBER'),
(1, 3, 'MEMBER'),
(1, 4, 'MEMBER'),
(2, 1, 'ADMIN'),
(2, 5, 'MEMBER');

-- Sample projects
INSERT INTO `projects` (`key`, `name`, `description`, `created_by`, `team_id`, `start_date`, `target_date`) VALUES
('PROJ', 'Project Management App', 'A comprehensive project management application', 1, 1, NOW(), DATE_ADD(NOW(), INTERVAL 3 MONTH)),
('ECOM', 'E-commerce Platform', 'Online shopping platform with advanced features', 1, 1, NOW(), DATE_ADD(NOW(), INTERVAL 6 MONTH));

-- Sample work items
INSERT INTO `work_items` (`external_id`, `title`, `description`, `type`, `status`, `priority`, `project_id`, `assignee_id`, `reporter_id`) VALUES
('PROJ-1', 'Setup Project Infrastructure', 'Initialize the project with basic setup and configuration', 'TASK', 'DONE', 'HIGH', 1, 3, 2),
('PROJ-2', 'User Authentication System', 'Implement secure user login and registration', 'FEATURE', 'IN_PROGRESS', 'HIGH', 1, 3, 2),
('PROJ-3', 'Dashboard Design', 'Create responsive dashboard for project overview', 'STORY', 'TODO', 'MEDIUM', 1, 4, 2),
('PROJ-4', 'Fix Login Bug', 'Resolve issue with login form validation', 'BUG', 'TODO', 'HIGH', 1, 3, 5),
('ECOM-1', 'Product Catalog', 'Design and implement product catalog functionality', 'EPIC', 'TODO', 'HIGH', 2, 4, 2),
('ECOM-2', 'Shopping Cart', 'Implement shopping cart with add/remove functionality', 'FEATURE', 'TODO', 'MEDIUM', 2, 4, 2);

COMMIT;

-- Note: Default password for all sample users is 'password'
-- You should change these passwords in production