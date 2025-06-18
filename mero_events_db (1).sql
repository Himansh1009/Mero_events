-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 18, 2025 at 04:44 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mero_events_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`) VALUES
(3, 'Site Admin', 'admin@mero.com', '$2y$10$hJxLsVjH5ELBcRcCv7cx..RkE6Yd71yxJ.N5HxxmuwS90.NR.eKGW');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `event_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `organizer_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `total_tickets` int(11) DEFAULT 0,
  `tickets_booked` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `event_date`, `event_time`, `location`, `category`, `organizer_id`, `status`, `total_tickets`, `tickets_booked`) VALUES
(6, 'MUN', 'Its a model united nations program', '2025-06-18', '00:43:00', 'Tandi', 'Education', 1, 'approved', 0, 0),
(7, 'hackathon', 'woowl', '2025-06-23', '10:50:00', 'Tandi', 'Tech', 2, 'approved', 0, 0),
(8, 'fiesta', 'enjoy', '2025-06-17', '11:57:00', 'Tandi', 'Tech', 3, 'approved', 0, 0),
(9, 'wow', 'j;ajga', '2025-06-21', '08:18:00', 'fjaj', 'Tech', 3, 'approved', 0, 0),
(10, 'Video editing cohort', 'Anyone can learn the basics of video editing in as little as two days, as well as more advanced skills in several additional days, provided they have the assistance of instructors who have decades of experience in the film, television, and digital media industries.\r\n\r\nThe speed at which you can learn video editing will be influenced by your familiarity with other applications that are similar to professional-grade video editing applications like Adobe Premiere Pro or Final Cut. If you’re already confident working in post-production and photo editing applications like After Effects and Photoshop, you will be able to learn the concepts and techniques of video editing more easily. Those with no prior experience should anticipate needing more time to get comfortable navigating the interface and creating projects in video editing applications. For complete beginners, it’s helpful to find introductory video editing courses that can guide you through the basics.\r\n\r\nLearning how to edit video may take slightly longer for those who want to learn advanced techniques, such as multicam editing, animation, and color correction. If your goal is to learn basic video editing skills, it will only take a few days of training before you are ready to start creating projects on your own. However, if you’re aiming to master editing techniques used by industry professionals, you will need several days of advanced training and additional time to practice what you’ve learned.', '2025-06-25', '08:43:00', 'Tandi', 'Tech', 4, 'approved', 0, 0),
(11, 'Dance', 'this will be organised in college', '2025-06-26', '11:20:00', 'Boston international college', 'Community', 3, 'rejected', 0, 0),
(12, 'Pannel discussion', 'Woow very informative', '2025-06-20', '10:46:00', 'bharatpur', 'Education', 3, 'approved', 5, 5),
(13, 'CMUN', 'its a mun', '2025-06-21', '07:40:00', 'Tandi', 'Education', 1, 'approved', 5, 3);

-- --------------------------------------------------------

--
-- Table structure for table `organizers`
--

CREATE TABLE `organizers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `id_proof` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_approved` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organizers`
--

INSERT INTO `organizers` (`id`, `name`, `email`, `password`, `id_proof`, `created_at`, `is_approved`) VALUES
(1, 'Saheshna sapkota', 'saheshna@gmail.com', '$2y$10$ZQK//TLdh9hJOg7iNt75ou633uH2FsXN6Zb11vyaUJmc4gK81V29a', 'uploads/id_proof_68459638624f44.85235223.jpg', '2025-06-08 13:55:04', 1),
(2, 'Himanshu Bharti', 'hanshubharti10@gmail.com', '$2y$10$iF0gRWuFnYfEWOfTKQ36kOi5XXFxhpEGASscOF9pAJX5kI1ttHV.S', 'uploads/id_proof_6845c2abd42c02.45892709.png', '2025-06-08 17:04:43', 1),
(3, 'Rasina Mahato', 'rasina@gmail.com', '$2y$10$itPvHDQ/aB6cRUKcbEZBXuUEuW7imORnBDJBCQDA3iNXVvR5AUg.S', 'uploads/id_proof_68465f209262d0.43788149.jpeg', '2025-06-09 04:12:16', 1),
(4, 'Ayush Dahal', 'aayush@gmail.com', '$2y$10$4zykF/BGBzeBZYD.ZISdEewbznninLzJnAW9SNpIoTwIABXsltXLm', 'uploads/id_proof_68502fe9c585d8.74673703.png', '2025-06-16 14:53:29', 1);

-- --------------------------------------------------------

--
-- Table structure for table `ticket_bookings`
--

CREATE TABLE `ticket_bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `tickets_booked` int(11) NOT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ticket_bookings`
--

INSERT INTO `ticket_bookings` (`id`, `user_id`, `event_id`, `tickets_booked`, `booking_date`) VALUES
(1, 2, 12, 1, '2025-06-18 08:34:56'),
(2, 5, 12, 3, '2025-06-18 08:37:57'),
(3, 6, 12, 1, '2025-06-18 08:39:43'),
(4, 1, 13, 3, '2025-06-18 10:56:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 'Rasina Mahato', 'rasina@gmail.com', '$2y$10$gTi0sv.2y.xD5BbdYSuQXOSgshrGLzmY9SLhrZ3HvhOcpM5Oksk1.', '2025-06-08 17:06:19'),
(2, 'anmol gaire', 'anmol@gmail.com', '$2y$10$fq1bT/vz4qA7PQgARmgyXOK1Fv2cFCDzx2/Aiparajj5.BwXY8XNy', '2025-06-09 06:14:16'),
(3, 'Rasina Mahato', 'rasina123@gmail.com', '$2y$10$AlZnEwzdA8dCvnP7tUhQKeVBH96CZmFKH3OLFsHfJhAbzYMK0H4WO', '2025-06-09 06:16:41'),
(4, 'Harsh', 'harsh@gmail.com', '$2y$10$1hwl5fac2Vjqlno6gNI.4eYMVvjOeUBrIiqauIuSYFAR/y06yxCbC', '2025-06-17 17:04:37'),
(5, 'Puja Bhuja', 'puja@gmail.com', '$2y$10$eSy0SS22ShdGrqET1troSuBI6LNXm2ESpbUDOXEyQ/EwLuaU7ijju', '2025-06-18 08:37:42'),
(6, 'Norah Shrestha', 'norah@gmail.com', '$2y$10$D59NJHU36.EcMkweAgr86u0Rs9OMIkfqr2ooh01SMC1sWS0lFZrUG', '2025-06-18 08:39:11'),
(7, 'Sadhana Adhikari', 'sadhana@gmail.com', '$2y$10$SSih.fsDyRB/Q/rZGQAvaurH/4Bk/veSZMllZ.3ISU8eAyndfhpPO', '2025-06-18 10:52:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `organizer_id` (`organizer_id`);

--
-- Indexes for table `organizers`
--
ALTER TABLE `organizers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `ticket_bookings`
--
ALTER TABLE `ticket_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `organizers`
--
ALTER TABLE `organizers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ticket_bookings`
--
ALTER TABLE `ticket_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`organizer_id`) REFERENCES `organizers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ticket_bookings`
--
ALTER TABLE `ticket_bookings`
  ADD CONSTRAINT `ticket_bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `ticket_bookings_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
