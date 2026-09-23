-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 22, 2026 at 07:34 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kingdomite`
--

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(1000) NOT NULL,
  `slug` varchar(1000) NOT NULL,
  `image` text NOT NULL,
  `date` timestamp NOT NULL,
  `location` varchar(1000) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `content` varchar(1000) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `slug`, `image`, `date`, `location`, `description`, `content`, `created_at`) VALUES
(1, 'Oil & Wine Summit', 'oil-wine-summit', 'kci_image/img37cc.jpg', '2026-01-08 08:23:21', 'Kingdomite Church International', 'A powerful gathering of worship, prayer, teaching and fellowship designed to draw hearts closer to God.', 'Join us for six days of worship, prayer, and teaching, Day & Night. This summit is designed to draw hearts closer to God and build meaningful connections with fellow believers.', '2026-09-22 08:24:24'),
(2, 'June Conference', 'june-conference', 'kci_image/img36cc.jpg', '2026-05-31 23:00:00', 'Kingdomite Church International', 'A season of teaching, connection and spiritual growth for individuals, families and the wider church community.', 'Our annual June Conference brings together believers for a season of teaching, connection and spiritual growth. Expect powerful messages, worship, and fellowship.', '2026-09-22 08:26:14'),
(3, 'Embers of Glory', 'embers-of-glory', 'kci_image/img39.jpg', '2026-09-01 08:18:46', 'Kingdomite Church International', 'A special atmosphere of worship, prayer and renewed passion for the presence and purpose of God.', 'Embers of Glory is a special worship event designed to renew our passion for Gods presence. Come ready for an atmosphere of worship and prayer.', '2026-09-22 08:25:34');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
