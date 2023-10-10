-- MySQL dump 10.19  Distrib 10.3.39-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: kanabapi
-- ------------------------------------------------------
-- Server version	10.3.39-MariaDB-0+deb10u1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `activity_types`
--

LOCK TABLES `activity_types` WRITE;
/*!40000 ALTER TABLE `activity_types` DISABLE KEYS */;
INSERT INTO `activity_types` (`id`, `name`, `adventurousness`, `solitariness`, `strenuousness`) VALUES (1,'bicycling',0.50,0.70,0.60),(2,'watersports',0.70,0.30,0.40),(3,'concert',0.50,0.10,0.20),(4,'conferences-and-conventions',0.00,0.00,0.00),(5,'dining',0.10,0.20,0.00),(6,'educational-fun',0.50,0.10,0.20),(7,'endurance-race',0.80,0.10,1.00),(8,'festivals-and-celebrations',0.60,0.10,0.20),(9,'hiking',0.60,0.50,0.60),(10,'museum',0.30,0.30,0.10),(11,'offroading',0.80,0.30,0.40),(12,'rock-climbing',0.60,0.20,0.80),(13,'rodeo',0.60,0.10,0.30),(14,'shopping',0.00,0.10,0.00),(15,'sightseeing',0.30,0.50,0.00),(16,'stargazing',0.10,0.70,0.00),(17,'swimming',0.20,0.20,0.10),(18,'tour',0.40,0.20,0.20),(19,'fishing',0.20,0.90,0.10),(20,'games-and-entertainment',0.40,0.10,0.10),(21,'unknown',0.40,0.30,0.20);
/*!40000 ALTER TABLE `activity_types` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2023-10-10 14:47:39
