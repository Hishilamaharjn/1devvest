-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 30, 2025 at 09:53 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `devvest`
--

-- --------------------------------------------------------

--
-- Table structure for table `investments`
--

CREATE TABLE `investments` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `investor_id` int(11) NOT NULL,
  `investor` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `investor`
--

CREATE TABLE `investor` (
  `investor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `investor` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `investor`
--

INSERT INTO `investor` (`investor_id`, `user_id`, `investor`) VALUES
(10, 97, ''),
(11, 99, '');

-- --------------------------------------------------------

--
-- Table structure for table `investor_profile`
--

CREATE TABLE `investor_profile` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `investor` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `investor_projects`
--

CREATE TABLE `investor_projects` (
  `id` int(11) NOT NULL,
  `investor` varchar(255) DEFAULT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `investor_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `invested_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `invested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'pending',
  `refunded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `investor_projects`
--

INSERT INTO `investor_projects` (`id`, `investor`, `project_name`, `investor_id`, `project_id`, `invested_amount`, `invested_at`, `status`, `refunded_at`) VALUES
(44, NULL, NULL, 10, 90, 50000.00, '2025-11-20 02:18:03', 'rejected', '2025-11-20 14:48:17'),
(45, NULL, NULL, 10, 96, 50000.00, '2025-11-23 13:14:02', 'rejected', '2025-11-23 19:00:50'),
(46, NULL, NULL, 10, 95, 60000.00, '2025-11-23 13:14:13', 'approved', NULL),
(47, NULL, NULL, 10, 94, 15000.00, '2025-11-23 13:14:21', 'approved', NULL),
(48, NULL, NULL, 10, 93, 5000.00, '2025-11-23 13:14:30', 'rejected', '2025-11-23 19:00:35'),
(49, NULL, NULL, 11, 96, 50000.00, '2025-11-23 13:18:45', 'approved', '2025-11-23 19:04:53'),
(50, NULL, NULL, 11, 95, 40000.00, '2025-11-23 13:19:05', 'rejected', '2025-11-23 19:05:14'),
(51, NULL, NULL, 11, 94, 10000.00, '2025-11-23 13:19:14', 'approved', NULL),
(52, NULL, NULL, 11, 93, 20000.00, '2025-11-23 13:19:24', 'approved', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `client_name` varchar(100) NOT NULL DEFAULT 'N/A',
  `project_name` varchar(100) NOT NULL DEFAULT 'N/A',
  `user_id` int(11) NOT NULL,
  `investor_id` varchar(255) NOT NULL,
  `investor` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `goal` decimal(12,2) DEFAULT 0.00,
  `collected` decimal(12,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_featured` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `client_id`, `client_name`, `project_name`, `user_id`, `investor_id`, `investor`, `title`, `description`, `goal`, `collected`, `start_date`, `end_date`, `category`, `image`, `file_path`, `status`, `created_at`, `is_featured`) VALUES
(90, 96, 'client', 'AI-Powered Resume Analyzer', 96, '', '', 'AI-Powered Resume Analyzer', 'Users machine learning to analyze resumes and rank candidates based on job requirements.', 150000.00, 0.00, '2025-11-19', '2025-12-04', 'AI / Machine Learning', 'images/1763574102_1763436664_desktop-computer-1834827.webp', NULL, 'pending', '2025-11-19 17:41:42', 0),
(92, 100, 'hishila', 'Smart Task Manager', 100, '', '', 'Smart Task Manager', 'A web app that helps users organize tasks with reminders and daily progress tracking.', 20000.00, 0.00, '2025-11-20', '2025-12-04', 'Mobile App', 'images/1763622848_markus-winkler-cbpHo6fd2v4-unsplash.jpg', NULL, 'rejected', '2025-11-20 07:14:08', 0),
(93, 100, 'hishila', 'Online Voting System', 100, '', '', 'Online Voting System', 'A secure platform where users can cast votes digitally with authentication.', 25000.00, 0.00, '2025-11-21', '2025-12-05', 'Web Development', 'images/1763622996_maxim-tolchinskiy-NBhIaEGgK48-unsplash.jpg', NULL, 'approved', '2025-11-20 07:16:36', 0),
(94, 100, 'hishila', 'E-Commerce Mini Store', 100, '', '', 'E-Commerce Mini Store', 'A simple online shop with cart, checkout, and admin product control.', 30000.00, 0.00, '2025-11-21', '2025-12-18', 'E-Comemerce', 'images/1763623292_shutter-speed-BQ9usyzHx_w-unsplash.jpg', NULL, 'approved', '2025-11-20 07:21:32', 1),
(95, 96, 'client', 'Password Strength Checker', 96, '', '', 'Password Strength Checker', 'Checks password security and suggests improvements.', 100000.00, 0.00, '2025-11-28', '2025-12-31', 'Cybersecurity', 'images/1763623487_1763563438_joshua-koblin-Is9p9NP_JhU-unsplash.jpg', NULL, 'approved', '2025-11-20 07:24:47', 1),
(96, 96, 'client', 'Fake News Classifier', 96, '', '', 'Fake News Classifier', 'Uses NLP to identify fake or misleading news articles.', 100000.00, 0.00, '2025-11-27', '2025-12-06', 'Data Science', 'images/1763623664_myriam-jessier-eveI7MOcSmw-unsplash.jpg', NULL, 'approved', '2025-11-20 07:27:44', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','client','investor') NOT NULL DEFAULT 'client',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `country` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `city` varchar(100) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `country`, `phone`, `status`, `city`, `deleted_at`) VALUES
(87, 'adminuser11', 'admin@devvest.com', '$2y$10$JcDKGtH7u8YiE3W3sN57deP4YQ8j48DEULJK5Y4RrdsmCL/Zs0pUi', 'admin', '2025-11-17 04:36:25', NULL, NULL, 'active', NULL, NULL),
(96, 'client', 'client@gmail.com', '$2y$10$89rY87Tmq89ffRU9bO67iOxlgK/WVwIZHTCEN8POIo7Fo4kf0PJr.', 'client', '2025-11-19 16:12:51', NULL, NULL, 'active', NULL, NULL),
(97, 'investor', 'client@gmail.com', '$2y$10$zOGrvZO.p.y3WppsdUakMeG6cwW5yHye9n41Q51nBheDqHbFjk3xu', 'investor', '2025-11-20 02:17:31', NULL, NULL, 'active', NULL, NULL),
(99, 'ranju', 'ranju@gmail.com', '$2y$10$gjjxyS0vdiyVt9Eu5PdwAOElRflCq7fDKfI48gsLegyzZ3mR8UAvi', 'investor', '2025-11-20 07:09:19', NULL, NULL, 'active', NULL, NULL),
(100, 'hishila', 'hishila@gmail.com', '$2y$10$vI7RJ2oNu2WA8BPvsjPXHO9QaxWK.N9NwdwOX7c5jCEJ3ChdV7.sG', 'client', '2025-11-20 07:10:11', NULL, NULL, 'active', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `investments`
--
ALTER TABLE `investments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_project_invest` (`project_id`),
  ADD KEY `fk_investor_invest` (`investor_id`);

--
-- Indexes for table `investor`
--
ALTER TABLE `investor`
  ADD PRIMARY KEY (`investor_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `investor_profile`
--
ALTER TABLE `investor_profile`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `investor_projects`
--
ALTER TABLE `investor_projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `investor_id` (`investor_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `investments`
--
ALTER TABLE `investments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `investor`
--
ALTER TABLE `investor`
  MODIFY `investor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `investor_profile`
--
ALTER TABLE `investor_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `investor_projects`
--
ALTER TABLE `investor_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `investments`
--
ALTER TABLE `investments`
  ADD CONSTRAINT `fk_investor_invest` FOREIGN KEY (`investor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_project_invest` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `investor`
--
ALTER TABLE `investor`
  ADD CONSTRAINT `investor_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `investor_projects`
--
ALTER TABLE `investor_projects`
  ADD CONSTRAINT `investor_projects_ibfk_1` FOREIGN KEY (`investor_id`) REFERENCES `investor` (`investor_id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
