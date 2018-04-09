-- phpMyAdmin SQL Dump
-- version 4.7.8
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 09, 2018 at 11:24 PM
-- Server version: 5.7.21
-- PHP Version: 7.2.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `audio`
--
CREATE DATABASE IF NOT EXISTS `audio` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `audio`;

-- --------------------------------------------------------

--
-- Table structure for table `requestreset`
--

DROP TABLE IF EXISTS `requestreset`;
CREATE TABLE `requestreset` (
  `ID` int(11) NOT NULL,
  `Requestsession` int(11) NOT NULL,
  `Targetsession` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `resetplayer`
--

DROP TABLE IF EXISTS `resetplayer`;
CREATE TABLE `resetplayer` (
  `ID` int(11) NOT NULL,
  `Resetsource` int(11) NOT NULL,
  `Resettarget` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `ID` int(11) NOT NULL,
  `Sessionid` varchar(100) NOT NULL,
  `Name` varchar(50) NOT NULL,
  `Contenttype` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `requestreset`
--
ALTER TABLE `requestreset`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `unique_index` (`Requestsession`,`Targetsession`),
  ADD KEY `Targetsession` (`Targetsession`);

--
-- Indexes for table `resetplayer`
--
ALTER TABLE `resetplayer`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `Resetsource` (`Resetsource`,`Resettarget`),
  ADD KEY `Resettarget` (`Resettarget`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `Sessionid` (`Sessionid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `requestreset`
--
ALTER TABLE `requestreset`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2881;

--
-- AUTO_INCREMENT for table `resetplayer`
--
ALTER TABLE `resetplayer`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=220;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `requestreset`
--
ALTER TABLE `requestreset`
  ADD CONSTRAINT `requestreset_ibfk_1` FOREIGN KEY (`Requestsession`) REFERENCES `sessions` (`ID`),
  ADD CONSTRAINT `requestreset_ibfk_2` FOREIGN KEY (`Targetsession`) REFERENCES `sessions` (`ID`);

--
-- Constraints for table `resetplayer`
--
ALTER TABLE `resetplayer`
  ADD CONSTRAINT `resetplayer_ibfk_1` FOREIGN KEY (`Resetsource`) REFERENCES `sessions` (`ID`),
  ADD CONSTRAINT `resetplayer_ibfk_2` FOREIGN KEY (`Resettarget`) REFERENCES `sessions` (`ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
