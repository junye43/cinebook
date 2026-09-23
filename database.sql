-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 23, 2026 at 08:32 PM
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
-- Database: `cinebook_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `movie`
--

CREATE TABLE `movie` (
  `Movie_ID` int(11) NOT NULL,
  `Location` varchar(150) DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `Showtime` time DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Title` varchar(150) NOT NULL,
  `Poster` varchar(255) DEFAULT '',
  `Backdrop` varchar(255) NOT NULL,
  `Genre` varchar(80) DEFAULT NULL,
  `Duration_Min` int(11) DEFAULT NULL,
  `Certificate` varchar(10) DEFAULT 'PG',
  `Stars` tinyint(4) DEFAULT 4,
  `Status` varchar(20) DEFAULT 'showing',
  `Cinema_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `movie`
--

INSERT INTO `movie` (`Movie_ID`, `Location`, `Date`, `Showtime`, `Description`, `Title`, `Poster`, `Backdrop`, `Genre`, `Duration_Min`, `Certificate`, `Stars`, `Status`, `Cinema_ID`) VALUES
(1, 'Hall 1', '2026-09-20', '14:30:00', 'A brilliant neurosurgeon is drawn into the world of the mystic arts on a journey of physical and spiritual healing, confronting a danger that threatens the entire multiverse.', 'Stellar Horizon', 'stellarhorizon.png', 'stellarhorizonposter.png', 'Sci-Fi', 128, 'PG13', 5, 'showing', 1),
(2, 'Hall 2', '2026-09-20', '18:00:00', 'A heartwarming story of friendship and courage set on a remote coast, following a keeper who guards more than just the light.', 'The Last Lighthouse', 'thelastlighthouse.png', 'thelastlighthouseposter.png', 'Drama', 105, 'PG', 4, 'showing', 1),
(3, 'Hall 1', '2026-09-21', '20:15:00', 'Non-stop action as an elite team races against time to stop a global threat hidden in plain sight.', 'Midnight Protocol', 'midnightprotocol.png', 'midnightprotocolposter.png', 'Action', 115, 'NC16', 3, 'showing', 2),
(4, 'Hall 3', '2026-09-21', '16:00:00', 'A laugh-out-loud comedy about a chaotic family weekend that spirals hilariously out of control.', 'Weekend Warriors', 'weekendwarriors.png', 'weekendwarriorsposter.png', 'Comedy', 98, 'PG', 4, 'showing', 2),
(5, 'Hall 2', '2026-09-22', '19:30:00', 'A gripping thriller where nothing is as it seems and every clue leads deeper into the dark.', 'Silent Echo', 'silentecho.png', 'silentechoposter.png', 'Action', 122, 'M18', 4, 'showing', 1),
(6, 'Hall 4', '2026-09-22', '15:15:00', 'An animated adventure across enchanted lands, full of heart, wonder and unlikely heroes.', 'Painted Skies', 'paintedskies.png', 'paintedskiesposter.png', 'Comedy', 92, 'PG', 5, 'showing', 2),
(7, 'Hall 1', '2026-10-10', '20:00:00', 'A kingdom rises and a hero is forged in this sweeping epic of loyalty, betrayal and destiny.', 'Crown of Ash', 'crownofash.png', '', 'Drama', 134, 'PG13', 4, 'coming', 1),
(8, 'Hall 2', '2026-10-18', '21:00:00', 'When the city sleeps, one detective uncovers a conspiracy that reaches the highest towers.', 'Neon Alibi', 'neonalibi.png', '', 'Action', 118, 'NC16', 4, 'coming', 2),
(9, 'Hall 3', '2026-11-01', '17:30:00', 'A tiny hero with a big heart proves that size is never a limit when courage leads the way.', 'Pocket Dynamo', 'pocketdynamo.png', '', 'Comedy', 101, 'PG', 3, 'coming', 1),
(10, 'Hall 4', '2026-11-14', '19:45:00', 'Two rivals, one prize, and a race across the stars that will decide the fate of a galaxy.', 'Orbit Run', 'orbitrun.png', '', 'Sci-Fi', 126, 'PG13', 5, 'coming', 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `movie`
--
ALTER TABLE `movie`
  ADD PRIMARY KEY (`Movie_ID`),
  ADD KEY `Cinema_ID` (`Cinema_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `movie`
--
ALTER TABLE `movie`
  MODIFY `Movie_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `movie`
--
ALTER TABLE `movie`
  ADD CONSTRAINT `movie_ibfk_1` FOREIGN KEY (`Cinema_ID`) REFERENCES `cinema` (`Cinema_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
