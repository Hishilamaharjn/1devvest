-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 18, 2025 at 03:35 AM
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
(7, 88, '');

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
  `status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `investor_projects`
--

INSERT INTO `investor_projects` (`id`, `investor`, `project_name`, `investor_id`, `project_id`, `invested_amount`, `invested_at`, `status`) VALUES
(29, NULL, NULL, 7, 29, 10000.00, '2025-11-17 05:01:21', 'approved'),
(30, NULL, NULL, 7, 31, 5000.00, '2025-11-17 15:49:32', 'approved'),
(31, NULL, NULL, 7, 31, 1000.00, '2025-11-17 17:22:55', 'Pending'),
(32, NULL, NULL, 7, 31, 500.00, '2025-11-17 17:23:17', 'Pending');

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
(29, 86, 'hishila', 'crowdfunding platform', 86, '', '', 'crowdfunding platform', 'user post their project and investor invest the project', 50000.00, 0.00, '2025-11-17', '2025-12-06', 'Web Development', 'uploads/1763369783_growtika-yGQmjh2uOTg-unsplash.jpg', NULL, 'approved', '2025-11-17 04:40:35', 1),
(30, 86, 'hishila', 'naya', 86, '', '', 'naya', 'naya', 15000.00, 0.00, '2025-11-04', '2025-11-26', 'DevOps', 'uploads/1763371601_240_F_303422103_mnIlqgSPh6aPOX60lIUEfDIbIU9xmbeO.jpg', NULL, 'approved', '2025-11-17 09:26:42', 0),
(31, 89, 'utsav', 'city management', 89, '', '', 'city management', 'it helps to improve city', 40000.00, 0.00, '2025-11-17', '2025-12-03', 'Mobile App', 'uploads/1763394478_50dac42c32c5e201ecf0384e134c6127.jpg', NULL, 'approved', '2025-11-17 15:47:58', 1);

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
  `city` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `country`, `phone`, `status`, `city`) VALUES
(86, 'hishila', 'hishila@gmail.com', '$2y$10$u23By6Mx64mzG73PYeylM.kxNo00.rdSwzRLxB/bSzUuZqqLReh4O', 'client', '2025-11-17 04:25:41', 'nepal', '9865321478', 'active', NULL),
(87, 'adminuser11', 'admin@devvest.com', '$2y$10$JcDKGtH7u8YiE3W3sN57deP4YQ8j48DEULJK5Y4RrdsmCL/Zs0pUi', 'admin', '2025-11-17 04:36:25', NULL, NULL, 'active', NULL),
(88, 'investor', 'investor@gmail.com', '$2y$10$wGn6Atf5MRRwLgMVQ.hHBuvfirwu/hwc0aKSEK0dGqR3cphmqTuR6', 'investor', '2025-11-17 04:41:29', 'nepal', '9865321478', 'active', NULL),
(89, 'utsav', 'utsav@gmail.com', '$2y$10$bAmbWz92jPkHixItb687fODn0awQwSpGJWwH6Oj63fohRXNrnCuxO', 'client', '2025-11-17 15:46:47', 'nepal', '9865321471', 'active', NULL);

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
  MODIFY `investor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `investor_profile`
--
ALTER TABLE `investor_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `investor_projects`
--
ALTER TABLE `investor_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

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
