-- MySQL dump 10.13  Distrib 8.4.9, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: bengkel_paten
-- ------------------------------------------------------
-- Server version	8.4.9
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `event` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changes` json DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `activity_logs_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `activity_logs_event_index` (`event`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendances`
--

DROP TABLE IF EXISTS `attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `clock_in` time DEFAULT NULL,
  `clock_out` time DEFAULT NULL,
  `clock_in_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clock_out_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('present','late','absent','leave','sick','off') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'present',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendances_user_id_date_unique` (`user_id`,`date`),
  KEY `attendances_branch_id_foreign` (`branch_id`),
  CONSTRAINT `attendances_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendances`
--

LOCK TABLES `attendances` WRITE;
/*!40000 ALTER TABLE `attendances` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehicle_plate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehicle_brand` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehicle_model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `booking_at` datetime NOT NULL,
  `complaint` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','confirmed','in_progress','done','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `service_id` bigint unsigned DEFAULT NULL,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bookings_customer_id_foreign` (`customer_id`),
  KEY `bookings_service_id_foreign` (`service_id`),
  KEY `bookings_branch_id_booking_at_index` (`branch_id`,`booking_at`),
  KEY `bookings_status_index` (`status`),
  CONSTRAINT `bookings_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (2,NULL,NULL,'Public Booking','08121234567897','pb6a0cbd251ca9f@t.id','PB 9956','Honda','Brio','2026-05-20 19:42:29','AC tidak dingin','pending',NULL,NULL,'2026-05-19 12:42:29','2026-05-19 12:42:29');
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,'Bengkel Paten â€” Pusat Semarang','PST','Jl. Siliwangi No. 88, Semarang','0241234567','pusat@bengkelpaten.id',1,'2026-05-02 07:53:02','2026-05-02 07:53:02',NULL),(2,'Bengkel Paten â€” Cabang Ungaran','UNG','Jl. Diponegoro No. 12, Ungaran','0241234568','ungaran@bengkelpaten.id',1,'2026-05-02 07:53:02','2026-05-02 07:53:02',NULL),(3,'Bengkel Paten â€” Cabang Kendal','KDL','Jl. Pemuda No. 5, Kendal','0241234569','kendal@bengkelpaten.id',1,'2026-05-02 07:53:02','2026-05-02 07:53:02',NULL);
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_hours`
--

DROP TABLE IF EXISTS `business_hours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_hours` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `day_of_week` int NOT NULL,
  `open_time` time DEFAULT NULL,
  `close_time` time DEFAULT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_hours_branch_id_index` (`branch_id`),
  CONSTRAINT `business_hours_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_hours`
--

LOCK TABLES `business_hours` WRITE;
/*!40000 ALTER TABLE `business_hours` DISABLE KEYS */;
INSERT INTO `business_hours` VALUES (1,1,0,NULL,NULL,1,'2026-05-19 10:43:32','2026-05-19 10:43:32'),(2,1,1,'08:00:00','17:00:00',0,'2026-05-19 10:43:32','2026-05-19 10:43:32'),(3,1,2,'08:00:00','17:00:00',0,'2026-05-19 10:43:32','2026-05-19 10:43:32'),(4,1,3,'08:00:00','17:00:00',0,'2026-05-19 10:43:32','2026-05-19 10:43:32'),(5,1,4,'08:00:00','17:00:00',0,'2026-05-19 10:43:32','2026-05-19 10:43:32'),(6,1,5,'08:00:00','17:00:00',0,'2026-05-19 10:43:32','2026-05-19 10:43:32'),(7,1,6,'08:00:00','17:00:00',0,'2026-05-19 10:43:32','2026-05-19 10:43:32');
/*!40000 ALTER TABLE `business_hours` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('bengkel-paten-cache-spatie.permission.cache','a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:85:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:10:\"technician\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:13:\"customer.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:15:\"customer.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:13:\"customer.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:15:\"customer.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:12:\"vehicle.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:14:\"vehicle.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:12:\"vehicle.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:14:\"vehicle.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:12:\"service.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:14:\"service.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:12:\"service.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:14:\"service.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:12:\"jobcard.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:14:\"jobcard.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:12:\"jobcard.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:14:\"jobcard.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:12:\"invoice.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:14:\"invoice.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:12:\"invoice.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:14:\"invoice.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:12:\"payment.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:14:\"payment.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:12:\"payment.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:14:\"payment.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:12:\"product.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:14:\"product.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:27;a:4:{s:1:\"a\";i:28;s:1:\"b\";s:12:\"product.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:14:\"product.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:29;a:4:{s:1:\"a\";i:30;s:1:\"b\";s:13:\"purchase.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:30;a:4:{s:1:\"a\";i:31;s:1:\"b\";s:15:\"purchase.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:31;a:4:{s:1:\"a\";i:32;s:1:\"b\";s:13:\"purchase.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:32;a:4:{s:1:\"a\";i:33;s:1:\"b\";s:15:\"purchase.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:33;a:4:{s:1:\"a\";i:34;s:1:\"b\";s:9:\"sale.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:34;a:4:{s:1:\"a\";i:35;s:1:\"b\";s:11:\"sale.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:35;a:4:{s:1:\"a\";i:36;s:1:\"b\";s:9:\"sale.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:36;a:4:{s:1:\"a\";i:37;s:1:\"b\";s:11:\"sale.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:37;a:4:{s:1:\"a\";i:38;s:1:\"b\";s:8:\"pos.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:38;a:4:{s:1:\"a\";i:39;s:1:\"b\";s:10:\"pos.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:39;a:4:{s:1:\"a\";i:40;s:1:\"b\";s:8:\"pos.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:40;a:4:{s:1:\"a\";i:41;s:1:\"b\";s:10:\"pos.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:41;a:4:{s:1:\"a\";i:42;s:1:\"b\";s:11:\"income.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:42;a:4:{s:1:\"a\";i:43;s:1:\"b\";s:13:\"income.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:43;a:4:{s:1:\"a\";i:44;s:1:\"b\";s:11:\"income.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:44;a:4:{s:1:\"a\";i:45;s:1:\"b\";s:13:\"income.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:45;a:4:{s:1:\"a\";i:46;s:1:\"b\";s:12:\"expense.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:46;a:4:{s:1:\"a\";i:47;s:1:\"b\";s:14:\"expense.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:47;a:4:{s:1:\"a\";i:48;s:1:\"b\";s:12:\"expense.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:48;a:4:{s:1:\"a\";i:49;s:1:\"b\";s:14:\"expense.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:49;a:4:{s:1:\"a\";i:50;s:1:\"b\";s:11:\"report.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:50;a:4:{s:1:\"a\";i:51;s:1:\"b\";s:13:\"report.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:51;a:4:{s:1:\"a\";i:52;s:1:\"b\";s:11:\"report.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:52;a:4:{s:1:\"a\";i:53;s:1:\"b\";s:13:\"report.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:53;a:4:{s:1:\"a\";i:54;s:1:\"b\";s:12:\"voucher.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:54;a:4:{s:1:\"a\";i:55;s:1:\"b\";s:14:\"voucher.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:55;a:4:{s:1:\"a\";i:56;s:1:\"b\";s:12:\"voucher.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:56;a:4:{s:1:\"a\";i:57;s:1:\"b\";s:14:\"voucher.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:57;a:4:{s:1:\"a\";i:58;s:1:\"b\";s:12:\"loyalty.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:58;a:4:{s:1:\"a\";i:59;s:1:\"b\";s:14:\"loyalty.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:59;a:4:{s:1:\"a\";i:60;s:1:\"b\";s:12:\"loyalty.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:60;a:4:{s:1:\"a\";i:61;s:1:\"b\";s:14:\"loyalty.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:61;a:4:{s:1:\"a\";i:62;s:1:\"b\";s:12:\"booking.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:62;a:4:{s:1:\"a\";i:63;s:1:\"b\";s:14:\"booking.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:63;a:4:{s:1:\"a\";i:64;s:1:\"b\";s:12:\"booking.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:64;a:4:{s:1:\"a\";i:65;s:1:\"b\";s:14:\"booking.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:65;a:4:{s:1:\"a\";i:66;s:1:\"b\";s:15:\"commission.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:66;a:4:{s:1:\"a\";i:67;s:1:\"b\";s:17:\"commission.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:67;a:4:{s:1:\"a\";i:68;s:1:\"b\";s:15:\"commission.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:68;a:4:{s:1:\"a\";i:69;s:1:\"b\";s:17:\"commission.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:69;a:4:{s:1:\"a\";i:70;s:1:\"b\";s:11:\"branch.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:70;a:4:{s:1:\"a\";i:71;s:1:\"b\";s:13:\"branch.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:71;a:4:{s:1:\"a\";i:72;s:1:\"b\";s:11:\"branch.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:72;a:4:{s:1:\"a\";i:73;s:1:\"b\";s:13:\"branch.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:73;a:4:{s:1:\"a\";i:74;s:1:\"b\";s:12:\"setting.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:74;a:4:{s:1:\"a\";i:75;s:1:\"b\";s:14:\"setting.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:75;a:4:{s:1:\"a\";i:76;s:1:\"b\";s:12:\"setting.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:76;a:4:{s:1:\"a\";i:77;s:1:\"b\";s:14:\"setting.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:77;a:4:{s:1:\"a\";i:78;s:1:\"b\";s:9:\"user.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:78;a:4:{s:1:\"a\";i:79;s:1:\"b\";s:11:\"user.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:79;a:4:{s:1:\"a\";i:80;s:1:\"b\";s:9:\"user.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:80;a:4:{s:1:\"a\";i:81;s:1:\"b\";s:11:\"user.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:81;a:4:{s:1:\"a\";i:82;s:1:\"b\";s:9:\"role.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:82;a:4:{s:1:\"a\";i:83;s:1:\"b\";s:11:\"role.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:83;a:4:{s:1:\"a\";i:84;s:1:\"b\";s:9:\"role.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:84;a:4:{s:1:\"a\";i:85;s:1:\"b\";s:11:\"role.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}}s:5:\"roles\";a:2:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:11:\"super_admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:5:\"admin\";s:1:\"c\";s:3:\"web\";}}}',1779304701);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checkout_categories`
--

DROP TABLE IF EXISTS `checkout_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `checkout_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checkout_categories`
--

LOCK TABLES `checkout_categories` WRITE;
/*!40000 ALTER TABLE `checkout_categories` DISABLE KEYS */;
INSERT INTO `checkout_categories` VALUES (1,'Oli & Cairan','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(2,'Kondisi Mesin','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(3,'Rem & Kaki-kaki','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(4,'Kelistrikan','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(5,'Body & Kabin','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(6,'Test Drive','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL);
/*!40000 ALTER TABLE `checkout_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checkout_results`
--

DROP TABLE IF EXISTS `checkout_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `checkout_results` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint unsigned NOT NULL,
  `checkout_category_id` bigint unsigned NOT NULL,
  `result` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `checkout_results_service_id_index` (`service_id`),
  KEY `checkout_results_checkout_category_id_index` (`checkout_category_id`),
  CONSTRAINT `checkout_results_checkout_category_id_foreign` FOREIGN KEY (`checkout_category_id`) REFERENCES `checkout_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `checkout_results_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checkout_results`
--

LOCK TABLES `checkout_results` WRITE;
/*!40000 ALTER TABLE `checkout_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `checkout_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cities`
--

DROP TABLE IF EXISTS `cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `state_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cities_state_id_index` (`state_id`),
  CONSTRAINT `cities_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cities`
--

LOCK TABLES `cities` WRITE;
/*!40000 ALTER TABLE `cities` DISABLE KEYS */;
/*!40000 ALTER TABLE `cities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `colors`
--

DROP TABLE IF EXISTS `colors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `colors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hex_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `colors`
--

LOCK TABLES `colors` WRITE;
/*!40000 ALTER TABLE `colors` DISABLE KEYS */;
INSERT INTO `colors` VALUES (1,'Putih','#7e9170',1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(2,'Hitam','#92db93',1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(3,'Silver / Abu','#c4aeee',1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(4,'Merah','#436366',1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(5,'Biru','#3921c2',1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(6,'Kuning','#38f6fe',1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(7,'Hijau','#46cdbf',1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(8,'Orange','#69b44e',1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL);
/*!40000 ALTER TABLE `colors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `countries`
--

LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `currencies`
--

DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `symbol` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exchange_rate` decimal(19,4) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currencies`
--

LOCK TABLES `currencies` WRITE;
/*!40000 ALTER TABLE `currencies` DISABLE KEYS */;
/*!40000 ALTER TABLE `currencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `custom_field_values`
--

DROP TABLE IF EXISTS `custom_field_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_field_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `custom_field_id` bigint unsigned NOT NULL,
  `entity_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` bigint unsigned NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `custom_field_values_custom_field_id_index` (`custom_field_id`),
  KEY `custom_field_values_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  CONSTRAINT `custom_field_values_custom_field_id_foreign` FOREIGN KEY (`custom_field_id`) REFERENCES `custom_fields` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custom_field_values`
--

LOCK TABLES `custom_field_values` WRITE;
/*!40000 ALTER TABLE `custom_field_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `custom_field_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `custom_fields`
--

DROP TABLE IF EXISTS `custom_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_fields` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` json DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custom_fields`
--

LOCK TABLES `custom_fields` WRITE;
/*!40000 ALTER TABLE `custom_fields` DISABLE KEYS */;
INSERT INTO `custom_fields` VALUES (1,'customers','Tanggal Lahir','date',NULL,0,1,1,'2026-05-19 10:43:32','2026-05-19 10:43:32',NULL),(2,'customers','Sumber Customer','select','\"[\\\"Google\\\",\\\"Sosmed\\\",\\\"Teman\\\",\\\"Spanduk\\\"]\"',0,2,1,'2026-05-19 10:43:32','2026-05-19 10:43:32',NULL),(3,'vehicles','No STNK','text',NULL,0,1,1,'2026-05-19 10:43:32','2026-05-19 10:43:32',NULL),(4,'vehicles','Tanggal STNK Berakhir','date',NULL,0,2,1,'2026-05-19 10:43:32','2026-05-19 10:43:32',NULL),(5,'services','Keluhan Tambahan','textarea',NULL,0,1,1,'2026-05-19 10:43:32','2026-05-19 10:43:32',NULL);
/*!40000 ALTER TABLE `custom_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `portal_password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `loyalty_points` int NOT NULL DEFAULT '0',
  `membership_tier` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bronze',
  `portal_last_login` timestamp NULL DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `referral_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referred_by_customer_id` bigint unsigned DEFAULT NULL,
  `referral_count` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_referral_code_unique` (`referral_code`),
  KEY `customers_branch_id_index` (`branch_id`),
  KEY `customers_referred_by_customer_id_foreign` (`referred_by_customer_id`),
  CONSTRAINT `customers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customers_referred_by_customer_id_foreign` FOREIGN KEY (`referred_by_customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,NULL,'Hendra Wijaya','08121234567890',NULL,'customer1@example.com',NULL,NULL,NULL,'Jl. Demo No. 1, Semarang',NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0,'bronze',NULL,NULL,NULL,NULL,0),(2,NULL,'Dewi Lestari','08121234567891',NULL,'customer2@example.com',NULL,NULL,NULL,'Jl. Demo No. 2, Semarang',NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0,'bronze',NULL,NULL,NULL,NULL,0),(3,NULL,'Rudi Hartono','08121234567892',NULL,'customer3@example.com',NULL,'CV Maju Bersama',NULL,'Jl. Demo No. 3, Semarang',NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0,'bronze',NULL,NULL,NULL,NULL,0),(4,NULL,'Rina Kusuma','08121234567893',NULL,'customer4@example.com',NULL,NULL,NULL,'Jl. Demo No. 4, Semarang',NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0,'bronze',NULL,NULL,NULL,NULL,0),(5,NULL,'Wahyu Nugroho','08121234567894',NULL,'customer5@example.com',NULL,'PT Sinar Abadi',NULL,'Jl. Demo No. 5, Semarang',NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0,'bronze',NULL,NULL,NULL,NULL,0),(8,NULL,'Walk-in Customer','000000000',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-05-19 11:44:42','2026-05-19 11:44:42',NULL,0,'bronze',NULL,NULL,NULL,NULL,0),(16,NULL,'UAT POST 6a0cbd22e2219','08121234567895',NULL,'uatpost6a0cbd22e222b@t.id',NULL,NULL,NULL,'Jl. Test',NULL,'2026-05-19 12:42:27','2026-05-19 12:42:27',NULL,0,'bronze',NULL,NULL,NULL,NULL,0);
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `to` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_logs`
--

LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipment`
--

DROP TABLE IF EXISTS `equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(15,2) DEFAULT NULL,
  `status` enum('active','maintenance','broken','retired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `next_maintenance_date` date DEFAULT NULL,
  `maintenance_interval_days` int NOT NULL DEFAULT '90',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `equipment_branch_id_foreign` (`branch_id`),
  CONSTRAINT `equipment_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment`
--

LOCK TABLES `equipment` WRITE;
/*!40000 ALTER TABLE `equipment` DISABLE KEYS */;
/*!40000 ALTER TABLE `equipment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipment_maintenance_logs`
--

DROP TABLE IF EXISTS `equipment_maintenance_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment_maintenance_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `equipment_id` bigint unsigned NOT NULL,
  `maintenance_date` date NOT NULL,
  `performed_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cost` decimal(15,2) NOT NULL DEFAULT '0.00',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `equipment_maintenance_logs_equipment_id_foreign` (`equipment_id`),
  CONSTRAINT `equipment_maintenance_logs_equipment_id_foreign` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment_maintenance_logs`
--

LOCK TABLES `equipment_maintenance_logs` WRITE;
/*!40000 ALTER TABLE `equipment_maintenance_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `equipment_maintenance_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_history_records`
--

DROP TABLE IF EXISTS `expense_history_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_history_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `expense_id` bigint unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expense_history_records_expense_id_index` (`expense_id`),
  CONSTRAINT `expense_history_records_expense_id_foreign` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_history_records`
--

LOCK TABLES `expense_history_records` WRITE;
/*!40000 ALTER TABLE `expense_history_records` DISABLE KEYS */;
INSERT INTO `expense_history_records` VALUES (1,1,4500000.00,'Gaji Karyawan April 2026','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(2,2,850000.00,'Tagihan Listrik PLN April','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(3,3,3500000.00,'Sewa Gedung Bengkel April','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(4,4,2750000.00,'Pembelian Oli & Spare Parts','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(5,5,250000.00,'Internet & Telepon','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(6,6,85000.00,'PDAM / Air','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(7,7,650000.00,'Peralatan Servis Baru','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(8,8,125000.00,'Sabun & Perlengkapan Kebersihan','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL);
/*!40000 ALTER TABLE `expense_history_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `expense_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_created_by_index` (`created_by`),
  KEY `expenses_branch_id_index` (`branch_id`),
  CONSTRAINT `expenses_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `expenses_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
INSERT INTO `expenses` VALUES (1,'2026-04-01',4500000.00,'Gaji Karyawan April 2026',NULL,1,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(2,'2026-04-05',850000.00,'Tagihan Listrik PLN April',NULL,1,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(3,'2026-04-05',3500000.00,'Sewa Gedung Bengkel April',NULL,1,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(4,'2026-04-08',2750000.00,'Pembelian Oli & Spare Parts',NULL,1,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(5,'2026-04-10',250000.00,'Internet & Telepon',NULL,1,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(6,'2026-04-10',85000.00,'PDAM / Air',NULL,1,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(7,'2026-04-15',650000.00,'Peralatan Servis Baru',NULL,1,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(8,'2026-04-20',125000.00,'Sabun & Perlengkapan Kebersihan',NULL,1,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(12,'2026-05-19',50000.00,'Test Expense','desc',1,NULL,'2026-05-19 12:42:28','2026-05-19 12:42:28',NULL);
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fuel_types`
--

DROP TABLE IF EXISTS `fuel_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fuel_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fuel_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fuel_types_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fuel_types`
--

LOCK TABLES `fuel_types` WRITE;
/*!40000 ALTER TABLE `fuel_types` DISABLE KEYS */;
INSERT INTO `fuel_types` VALUES (1,'Bensin','bensin',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(2,'Solar / Diesel','solar-diesel',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(3,'Pertamax','pertamax',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(4,'Pertalite','pertalite',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(5,'Listrik (EV)','listrik-ev',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(6,'Hybrid','hybrid',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL);
/*!40000 ALTER TABLE `fuel_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gate_passes`
--

DROP TABLE IF EXISTS `gate_passes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gate_passes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `gate_pass_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `service_id` bigint unsigned DEFAULT NULL,
  `entry_date` datetime DEFAULT NULL,
  `exit_date` datetime DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `driver_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `driver_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gate_passes_gate_pass_no_index` (`gate_pass_no`),
  KEY `gate_passes_vehicle_id_index` (`vehicle_id`),
  KEY `gate_passes_customer_id_index` (`customer_id`),
  KEY `gate_passes_service_id_index` (`service_id`),
  KEY `gate_passes_created_by_index` (`created_by`),
  KEY `gate_passes_branch_id_index` (`branch_id`),
  KEY `gate_passes_status_index` (`status`),
  KEY `gate_passes_entry_date_index` (`entry_date`),
  CONSTRAINT `gate_passes_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gate_passes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gate_passes_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gate_passes_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gate_passes_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gate_passes`
--

LOCK TABLES `gate_passes` WRITE;
/*!40000 ALTER TABLE `gate_passes` DISABLE KEYS */;
INSERT INTO `gate_passes` VALUES (3,'GP-SEED-1',1,1,2,'2026-05-19 19:40:04',NULL,'in',NULL,NULL,NULL,1,1,'2026-05-19 12:40:04','2026-05-19 12:40:04',NULL);
/*!40000 ALTER TABLE `gate_passes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holidays`
--

DROP TABLE IF EXISTS `holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `holidays` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `holidays_branch_id_index` (`branch_id`),
  CONSTRAINT `holidays_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holidays`
--

LOCK TABLES `holidays` WRITE;
/*!40000 ALTER TABLE `holidays` DISABLE KEYS */;
INSERT INTO `holidays` VALUES (1,1,'Tahun Baru','2026-01-01',1,'2026-05-19 10:43:32','2026-05-19 10:43:32',NULL),(2,1,'Tahun Baru Imlek','2026-02-17',1,'2026-05-19 10:43:32','2026-05-19 10:43:32',NULL),(3,1,'Hari Raya Idul Fitri','2026-03-21',1,'2026-05-19 10:43:32','2026-05-19 10:43:32',NULL),(4,1,'Hari Buruh','2026-05-01',1,'2026-05-19 10:43:32','2026-05-19 10:43:32',NULL),(5,1,'Hari Kemerdekaan RI','2026-08-17',1,'2026-05-19 10:43:32','2026-05-19 10:43:32',NULL);
/*!40000 ALTER TABLE `holidays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `income_history_records`
--

DROP TABLE IF EXISTS `income_history_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `income_history_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `income_id` bigint unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `income_history_records_income_id_index` (`income_id`),
  CONSTRAINT `income_history_records_income_id_foreign` FOREIGN KEY (`income_id`) REFERENCES `incomes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `income_history_records`
--

LOCK TABLES `income_history_records` WRITE;
/*!40000 ALTER TABLE `income_history_records` DISABLE KEYS */;
INSERT INTO `income_history_records` VALUES (1,1,185000.00,'Servis Berkala Avanza','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(2,2,75000.00,'Servis Berkala Xenia','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(3,3,235000.00,'Ganti Kampas Rem Fortuner','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(4,4,125000.00,'Servis CVT Honda Beat','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(5,5,700000.00,'Servis Berkala NMAX + Aki','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(6,6,350000.00,'Tune Up Brio Satya','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(7,7,85000.00,'Ganti Kampas Rem GSX-R150','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(8,8,450000.00,'Servis AC Pajero Sport','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(9,9,110000.00,'Ganti Oli Honda Beat','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(10,10,285000.00,'Servis Berkala NMAX 20rb','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(11,11,195000.00,'Ganti V-Belt Avanza','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL);
/*!40000 ALTER TABLE `income_history_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `incomes`
--

DROP TABLE IF EXISTS `incomes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `incomes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `payment_method_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `income_date` date NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incomes_customer_id_index` (`customer_id`),
  KEY `incomes_payment_method_id_index` (`payment_method_id`),
  KEY `incomes_created_by_index` (`created_by`),
  KEY `incomes_branch_id_index` (`branch_id`),
  CONSTRAINT `incomes_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `incomes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `incomes_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `incomes_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `incomes`
--

LOCK TABLES `incomes` WRITE;
/*!40000 ALTER TABLE `incomes` DISABLE KEYS */;
INSERT INTO `incomes` VALUES (1,'INV-2026-001',1,2,185000.00,'2026-04-01','Servis Berkala Avanza',NULL,1,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(2,'INV-2026-002',2,3,75000.00,'2026-04-03','Servis Berkala Xenia',NULL,1,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(3,'INV-2026-003',3,2,235000.00,'2026-04-05','Ganti Kampas Rem Fortuner',NULL,1,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(4,'INV-2026-004',4,2,125000.00,'2026-04-07','Servis CVT Honda Beat',NULL,1,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(5,'INV-2026-005',5,3,700000.00,'2026-04-10','Servis Berkala NMAX + Aki',NULL,1,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(6,'INV-2026-006',1,1,350000.00,'2026-04-12','Tune Up Brio Satya',NULL,1,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(7,'INV-2026-007',2,2,85000.00,'2026-04-15','Ganti Kampas Rem GSX-R150',NULL,1,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(8,'INV-2026-008',3,2,450000.00,'2026-04-17','Servis AC Pajero Sport',NULL,1,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(9,'INV-2026-009',4,1,110000.00,'2026-04-20','Ganti Oli Honda Beat',NULL,1,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(10,'INV-2026-010',5,1,285000.00,'2026-04-22','Servis Berkala NMAX 20rb',NULL,1,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(11,'INV-2026-011',1,2,195000.00,'2026-04-25','Ganti V-Belt Avanza',NULL,1,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(15,NULL,NULL,NULL,100000.00,'2026-05-19','Test Income','desc',1,NULL,'2026-05-19 12:42:28','2026-05-19 12:42:28',NULL);
/*!40000 ALTER TABLE `incomes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inspection_points_library`
--

DROP TABLE IF EXISTS `inspection_points_library`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inspection_points_library` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `observation_type_id` bigint unsigned DEFAULT NULL,
  `point` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inspection_points_library_observation_type_id_index` (`observation_type_id`),
  KEY `inspection_points_library_sort_order_index` (`sort_order`),
  CONSTRAINT `inspection_points_library_observation_type_id_foreign` FOREIGN KEY (`observation_type_id`) REFERENCES `observation_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inspection_points_library`
--

LOCK TABLES `inspection_points_library` WRITE;
/*!40000 ALTER TABLE `inspection_points_library` DISABLE KEYS */;
INSERT INTO `inspection_points_library` VALUES (1,1,'Body - Panel depan','eksterior',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(2,1,'Body - Panel samping','eksterior',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(3,1,'Body - Panel belakang','eksterior',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(4,1,'Kaca - Retak/Baret','eksterior',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(5,1,'Ban - Keausan','eksterior',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(6,1,'Spion - Kondisi','eksterior',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(7,2,'Dashboard - Indikator','interior',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(8,2,'Setir - Kondisi','interior',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(9,2,'Jok - Robek/Lecet','interior',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(10,2,'AC - Suhu & Tiupan','interior',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(11,2,'Audio - Fungsi','interior',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(12,3,'Oli - Level','mesin',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(13,3,'Oli - Kebocoran','mesin',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(14,3,'Coolant - Level','mesin',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(15,3,'Filter Udara - Kotor','mesin',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(16,3,'Busi - Kondisi','mesin',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(17,3,'Belt - Retak/Aus','mesin',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(18,3,'Suara Mesin - Normal/Tidak','mesin',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(19,4,'Shock - Kebocoran','kaki-kaki',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(20,4,'Ball Joint - Obak','kaki-kaki',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(21,4,'Karet Boot - Robek','kaki-kaki',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(22,4,'Knalpot - Bocor/Berisik','kaki-kaki',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(23,5,'Lampu Depan - Low Beam','kelistrikan',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(24,5,'Lampu Depan - High Beam','kelistrikan',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(25,5,'Lampu Sein - Semua','kelistrikan',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(26,5,'Lampu Rem','kelistrikan',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(27,5,'Aki - Tegangan','kelistrikan',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(28,5,'Klason - Volume','kelistrikan',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(29,6,'Akselerasi - Responsif','test-drive',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(30,6,'Rem - Pakem & Lurus','test-drive',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(31,6,'Transmisi - Perpindahan Gigi','test-drive',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(32,6,'Suspensi - Bunyi & Getaran','test-drive',0,1,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL);
/*!40000 ALTER TABLE `inspection_points_library` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_invoice_id_index` (`invoice_id`),
  KEY `invoice_items_product_id_index` (`product_id`),
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_items`
--

LOCK TABLES `invoice_items` WRITE;
/*!40000 ALTER TABLE `invoice_items` DISABLE KEYS */;
INSERT INTO `invoice_items` VALUES (1,17,12,'Busi NGK Iridium (pcs)',1.00,85000.00,85000.00,'2026-05-19 12:15:22','2026-05-19 12:15:22',NULL),(2,17,13,'Busi Denso Iridium (pcs)',1.00,90000.00,90000.00,'2026-05-19 12:15:22','2026-05-19 12:15:22',NULL),(3,17,18,'Aki GS Astra MF 35Ah (Mobil)',1.00,650000.00,650000.00,'2026-05-19 12:15:22','2026-05-19 12:15:22',NULL),(4,17,29,'Air Radiator / Coolant (1L)',1.00,35000.00,35000.00,'2026-05-19 12:15:22','2026-05-19 12:15:22',NULL);
/*!40000 ALTER TABLE `invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `service_id` bigint unsigned DEFAULT NULL,
  `sale_id` bigint unsigned DEFAULT NULL,
  `pos_session_id` bigint unsigned DEFAULT NULL,
  `payment_method_id` bigint unsigned DEFAULT NULL,
  `payment_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT NULL,
  `discount` decimal(15,2) DEFAULT NULL,
  `tax_amount` decimal(15,2) DEFAULT NULL,
  `grand_total` decimal(15,2) DEFAULT NULL,
  `paid_amount` decimal(15,2) DEFAULT NULL,
  `amount_received` decimal(15,2) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `invoice_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `branch_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoices_customer_id_index` (`customer_id`),
  KEY `invoices_service_id_index` (`service_id`),
  KEY `invoices_payment_method_id_index` (`payment_method_id`),
  KEY `invoices_created_by_index` (`created_by`),
  KEY `invoices_branch_id_index` (`branch_id`),
  KEY `invoices_pos_session_id_foreign` (`pos_session_id`),
  KEY `invoices_sale_id_foreign` (`sale_id`),
  CONSTRAINT `invoices_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_pos_session_id_foreign` FOREIGN KEY (`pos_session_id`) REFERENCES `pos_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (1,'INV-2026-001',1,2,NULL,NULL,2,'2',185000.00,NULL,NULL,185000.00,185000.00,185000.00,'2026-04-01','service',1,NULL,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(2,'INV-2026-002',2,3,NULL,NULL,3,'2',75000.00,NULL,NULL,75000.00,75000.00,75000.00,'2026-04-03','service',1,NULL,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(3,'INV-2026-003',3,4,NULL,NULL,2,'2',235000.00,NULL,NULL,235000.00,235000.00,235000.00,'2026-04-05','service',1,NULL,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(4,'INV-2026-004',4,5,NULL,NULL,2,'2',125000.00,NULL,NULL,125000.00,125000.00,125000.00,'2026-04-07','service',1,NULL,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(5,'INV-2026-005',5,6,NULL,NULL,3,'2',700000.00,NULL,NULL,700000.00,700000.00,700000.00,'2026-04-10','service',1,NULL,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(6,'INV-2026-006',1,7,NULL,NULL,1,'2',350000.00,NULL,NULL,350000.00,350000.00,350000.00,'2026-04-12','service',1,NULL,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(7,'INV-2026-007',2,8,NULL,NULL,2,'2',85000.00,NULL,NULL,85000.00,85000.00,85000.00,'2026-04-15','service',1,NULL,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(8,'INV-2026-008',3,9,NULL,NULL,2,'2',450000.00,NULL,NULL,450000.00,450000.00,450000.00,'2026-04-17','service',1,NULL,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(9,'INV-2026-009',4,10,NULL,NULL,1,'2',110000.00,NULL,NULL,110000.00,110000.00,110000.00,'2026-04-20','service',1,NULL,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(10,'INV-2026-010',5,11,NULL,NULL,1,'2',285000.00,NULL,NULL,285000.00,285000.00,285000.00,'2026-04-22','service',1,NULL,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(11,'INV-2026-011',1,12,NULL,NULL,2,'2',195000.00,NULL,NULL,195000.00,195000.00,195000.00,'2026-04-25','service',1,NULL,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(12,'INV-2026-012',2,13,NULL,NULL,1,'0',300000.00,NULL,NULL,300000.00,0.00,0.00,'2026-04-28','service',1,NULL,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(13,'INV-2026-013',3,14,NULL,NULL,2,'0',680000.00,NULL,NULL,680000.00,0.00,0.00,'2026-04-29','service',1,NULL,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(14,'INV-2026-014',4,15,NULL,NULL,1,'0',95000.00,NULL,NULL,95000.00,0.00,0.00,'2026-04-30','service',1,NULL,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(15,'INV-2026-015',5,16,NULL,NULL,1,'0',120000.00,NULL,NULL,120000.00,0.00,0.00,'2026-04-30','service',1,NULL,NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(16,'TEST-1779216282',8,NULL,NULL,NULL,NULL,'2',100000.00,0.00,0.00,100000.00,100000.00,100000.00,'2026-05-19','pos',1,NULL,NULL,'2026-05-19 11:44:42','2026-05-19 11:44:42','2026-05-19 11:44:42'),(17,'POS-20260519-0001',8,NULL,NULL,NULL,4,'2',860000.00,0.00,0.00,860000.00,860000.00,860000.00,'2026-05-19','pos',1,NULL,NULL,'2026-05-19 12:15:22','2026-05-19 12:15:22',NULL);
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ip_whitelists`
--

DROP TABLE IF EXISTS `ip_whitelists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ip_whitelists` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ip_whitelists_ip_index` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ip_whitelists`
--

LOCK TABLES `ip_whitelists` WRITE;
/*!40000 ALTER TABLE `ip_whitelists` DISABLE KEYS */;
/*!40000 ALTER TABLE `ip_whitelists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobcard_details`
--

DROP TABLE IF EXISTS `jobcard_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobcard_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint unsigned NOT NULL,
  `jobcard_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `vehicle_id` bigint unsigned NOT NULL,
  `odometer_in` int DEFAULT NULL,
  `odometer_out` int DEFAULT NULL,
  `in_date` datetime DEFAULT NULL,
  `out_date` datetime DEFAULT NULL,
  `next_service_date` date DEFAULT NULL,
  `next_service_km` int DEFAULT NULL,
  `done_status` int DEFAULT NULL,
  `reminder_sent` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jobcard_details_service_id_index` (`service_id`),
  KEY `jobcard_details_customer_id_index` (`customer_id`),
  KEY `jobcard_details_vehicle_id_index` (`vehicle_id`),
  CONSTRAINT `jobcard_details_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `jobcard_details_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `jobcard_details_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobcard_details`
--

LOCK TABLES `jobcard_details` WRITE;
/*!40000 ALTER TABLE `jobcard_details` DISABLE KEYS */;
INSERT INTO `jobcard_details` VALUES (1,2,'BP-0001',1,1,65000,65292,'2026-04-01 08:00:00','2026-04-01 09:00:00','2026-06-30',75000,1,0,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(2,3,'BP-0002',2,2,48000,48712,'2026-04-03 09:00:00','2026-04-03 12:00:00','2026-07-02',58000,1,0,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(3,4,'BP-0003',3,3,82000,82730,'2026-04-05 10:00:00','2026-04-05 11:00:00','2026-07-04',92000,1,0,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(4,5,'BP-0004',4,4,12000,12519,'2026-04-07 08:30:00','2026-04-07 10:30:00','2026-07-06',22000,1,0,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(5,6,'BP-0005',5,5,8500,9319,'2026-04-10 09:00:00','2026-04-10 12:00:00','2026-07-09',18500,1,0,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(6,7,'BP-0006',1,6,38000,38929,'2026-04-12 10:00:00','2026-04-12 12:00:00','2026-07-11',48000,1,0,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(7,8,'BP-0007',2,8,5200,5950,'2026-04-15 11:00:00','2026-04-15 12:00:00','2026-07-14',15200,1,0,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(8,9,'BP-0008',3,7,95000,95855,'2026-04-17 08:00:00','2026-04-17 09:00:00','2026-07-16',105000,1,0,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(9,10,'BP-0009',4,4,12000,12885,'2026-04-20 09:30:00','2026-04-20 13:30:00','2026-07-19',22000,1,0,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(10,11,'BP-0010',5,5,8500,9178,'2026-04-22 10:00:00','2026-04-22 11:00:00','2026-07-21',18500,1,0,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(11,12,'BP-0011',1,1,65000,65397,'2026-04-25 08:00:00','2026-04-25 12:00:00','2026-07-24',75000,1,0,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(12,13,'BP-0012',2,2,48000,NULL,'2026-04-28 09:00:00',NULL,'2026-07-27',58000,0,0,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(13,14,'BP-0013',3,3,82000,NULL,'2026-04-29 10:00:00',NULL,'2026-07-28',92000,0,0,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(14,15,'BP-0014',4,4,12000,NULL,'2026-04-30 08:30:00',NULL,'2026-07-29',22000,0,0,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(15,16,'BP-0015',5,5,8500,NULL,'2026-04-30 09:00:00',NULL,'2026-07-29',18500,0,0,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL);
/*!40000 ALTER TABLE `jobcard_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loyalty_transactions`
--

DROP TABLE IF EXISTS `loyalty_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `reference_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `points` int NOT NULL,
  `type` enum('earn','redeem','adjust','expire') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loyalty_transactions_customer_id_foreign` (`customer_id`),
  KEY `loyalty_transactions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  CONSTRAINT `loyalty_transactions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loyalty_transactions`
--

LOCK TABLES `loyalty_transactions` WRITE;
/*!40000 ALTER TABLE `loyalty_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `loyalty_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=129 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2019_09_15_000010_create_tenants_table',1),(5,'2019_09_15_000020_create_domains_table',1),(65,'2026_05_02_044318_create_permission_tables',1),(66,'2026_05_02_044321_create_personal_access_tokens_table',1),(67,'2026_05_02_110000_add_tenant_fields_to_users_table',1),(68,'2026_05_02_100001_create_branches_table',2),(69,'2026_05_02_100002_create_checkout_categories_table',2),(70,'2026_05_02_100003_create_colors_table',2),(71,'2026_05_02_100004_create_countries_table',2),(72,'2026_05_02_100005_create_currencies_table',2),(73,'2026_05_02_100006_create_custom_fields_table',2),(74,'2026_05_02_100007_create_email_logs_table',2),(75,'2026_05_02_100008_create_fuel_types_table',2),(76,'2026_05_02_100009_create_notification_templates_table',2),(77,'2026_05_02_100010_create_observation_types_table',2),(78,'2026_05_02_100011_create_payment_methods_table',2),(79,'2026_05_02_100012_create_product_types_table',2),(80,'2026_05_02_100013_create_product_units_table',2),(81,'2026_05_02_100014_create_repair_categories_table',2),(82,'2026_05_02_100015_create_settings_table',2),(83,'2026_05_02_100016_create_suppliers_table',2),(84,'2026_05_02_100017_create_tax_rates_table',2),(85,'2026_05_02_100018_create_vehicle_types_table',2),(86,'2026_05_02_111001_create_business_hours_table',2),(87,'2026_05_02_111002_create_customers_table',2),(88,'2026_05_02_111003_create_holidays_table',2),(89,'2026_05_02_111004_create_inspection_points_library_table',2),(90,'2026_05_02_111005_create_observation_points_table',2),(91,'2026_05_02_111006_create_states_table',2),(92,'2026_05_02_111007_create_vehicle_brands_table',2),(93,'2026_05_02_121001_create_cities_table',2),(94,'2026_05_02_121002_create_vehicles_table',2),(95,'2026_05_02_131001_create_products_table',2),(96,'2026_05_02_131002_create_vehicle_images_table',2),(97,'2026_05_02_141001_create_custom_field_values_table',2),(98,'2026_05_02_141002_create_services_table',2),(99,'2026_05_02_141003_create_stock_histories_table',2),(100,'2026_05_02_141004_create_stock_records_table',2),(101,'2026_05_02_151001_create_checkout_results_table',2),(102,'2026_05_02_151002_create_invoices_table',2),(103,'2026_05_02_151003_create_jobcard_details_table',2),(104,'2026_05_02_151004_create_service_images_table',2),(105,'2026_05_02_151005_create_service_observation_points_table',2),(106,'2026_05_02_151006_create_service_taxes_table',2),(107,'2026_05_02_151007_create_service_technicians_table',2),(108,'2026_05_02_161001_create_invoice_items_table',2),(109,'2026_05_02_161002_create_payment_records_table',2),(110,'2026_05_02_161003_create_purchases_table',2),(111,'2026_05_02_171001_create_purchase_history_records_table',2),(112,'2026_05_02_171002_create_purchase_items_table',2),(113,'2026_05_02_171003_create_sales_table',2),(114,'2026_05_02_181001_create_expenses_table',2),(115,'2026_05_02_181002_create_gate_passes_table',2),(116,'2026_05_02_181003_create_incomes_table',2),(117,'2026_05_02_181004_create_notes_table',2),(118,'2026_05_02_181005_create_reminders_table',2),(119,'2026_05_02_191001_create_expense_history_records_table',2),(120,'2026_05_02_191002_create_income_history_records_table',2),(121,'2026_05_02_191003_create_washbays_table',2),(122,'2026_05_02_200000_add_global_id_to_users_table',3),(123,'2026_05_02_210000_add_sale_id_to_invoices',4),(124,'2026_05_20_300001_fix_notes_and_reminders_columns',5),(125,'2026_05_20_300002_invoice_items_nullable_product',6),(126,'2026_05_20_300003_fix_nullable_columns',7),(127,'2026_05_20_300004_more_nullable_fixes',8),(128,'2026_05_20_300005_add_sales_columns',9);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `notable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notable_id` bigint unsigned NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notes_notable_type_notable_id_index` (`notable_type`,`notable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notes`
--

LOCK TABLES `notes` WRITE;
/*!40000 ALTER TABLE `notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_templates`
--

DROP TABLE IF EXISTS `notification_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_templates_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_templates`
--

LOCK TABLES `notification_templates` WRITE;
/*!40000 ALTER TABLE `notification_templates` DISABLE KEYS */;
INSERT INTO `notification_templates` VALUES (2,'Seed Tpl','seed-tpl','whatsapp',NULL,'Hi {customer_name}',1,'2026-05-19 12:40:04','2026-05-19 12:40:04',NULL),(3,'POST Tpl 6a0cbd2475c0a','post-tpl-6a0cbd2475c0e','whatsapp',NULL,'Hi {customer_name}',0,'2026-05-19 12:42:28','2026-05-19 12:42:28',NULL);
/*!40000 ALTER TABLE `notification_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `observation_points`
--

DROP TABLE IF EXISTS `observation_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `observation_points` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `observation_type_id` bigint unsigned NOT NULL,
  `observation_point` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `observation_points_observation_type_id_index` (`observation_type_id`),
  CONSTRAINT `observation_points_observation_type_id_foreign` FOREIGN KEY (`observation_type_id`) REFERENCES `observation_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `observation_points`
--

LOCK TABLES `observation_points` WRITE;
/*!40000 ALTER TABLE `observation_points` DISABLE KEYS */;
INSERT INTO `observation_points` VALUES (1,1,'Body / Cat (lecet, penyok, karat)','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(2,1,'Kaca depan (retak, baret)','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(3,1,'Kaca samping & belakang','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(4,1,'Spion kiri & kanan','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(5,1,'Wiper depan & belakang','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(6,1,'Ban & velg (aus, tekanan)','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(7,1,'Pintu & engsel','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(8,2,'Dashboard / panel instrumen','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(9,2,'Setir & klakson','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(10,2,'Sabuk pengaman','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(11,2,'Jok & sandaran','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(12,2,'AC & pemanas','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(13,2,'Audio / head unit','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(14,2,'Power window & central lock','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(15,3,'Oli mesin (level, warna)','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(16,3,'Filter oli','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(17,3,'Air radiator / coolant','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(18,3,'Filter udara','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(19,3,'Busi / koil','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(20,3,'Belt / fan belt','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(21,3,'Engine mounting','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(22,3,'Suara mesin (getaran, knocking)','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(23,4,'Shock absorber (kebocoran)','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(24,4,'Ball joint & tie rod','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(25,4,'Rubber boot / karet boot','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(26,4,'Knalpot & mounting','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(27,4,'Gardan / differential','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(28,4,'Drive shaft / axle','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(29,4,'Sistem rem (selang, master)','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(30,5,'Lampu depan (low & high beam)','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(31,5,'Lampu sein depan & belakang','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(32,5,'Lampu rem','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(33,5,'Lampu mundur','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(34,5,'Lampu hazard','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(35,5,'Lampu kabut / fog lamp','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(36,5,'Aki & terminal','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(37,5,'Klason','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(38,6,'Akselerasi / tarikan mesin','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(39,6,'Rem (pakem, bunyi, getar)','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(40,6,'Transmisi (halus, selip)','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(41,6,'Stir / setir (steady, oblak)','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(42,6,'Suara tidak normal saat jalan','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(43,6,'Suspensi & kenyamanan','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL);
/*!40000 ALTER TABLE `observation_points` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `observation_types`
--

DROP TABLE IF EXISTS `observation_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `observation_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `observation_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `observation_types`
--

LOCK TABLES `observation_types` WRITE;
/*!40000 ALTER TABLE `observation_types` DISABLE KEYS */;
INSERT INTO `observation_types` VALUES (1,'Pemeriksaan Eksterior','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(2,'Pemeriksaan Interior','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(3,'Pemeriksaan Mesin','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(4,'Pemeriksaan Kolong / Kaki-Kaki','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(5,'Pemeriksaan Lampu & Kelistrikan','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL),(6,'Test Drive','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL);
/*!40000 ALTER TABLE `observation_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_gateways`
--

DROP TABLE IF EXISTS `payment_gateways`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_gateways` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_format` enum('redirect','embed','qr','manual_transfer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `base_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `merchant_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_key_encrypted` text COLLATE utf8mb4_unicode_ci,
  `secret_key_encrypted` text COLLATE utf8mb4_unicode_ci,
  `extra_headers` json DEFAULT NULL,
  `extra_config` json DEFAULT NULL,
  `callback_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supported_methods` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `sandbox_mode` tinyint(1) NOT NULL DEFAULT '1',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_gateways`
--

LOCK TABLES `payment_gateways` WRITE;
/*!40000 ALTER TABLE `payment_gateways` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_gateways` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_links`
--

DROP TABLE IF EXISTS `payment_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_links` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `payment_gateway_id` bigint unsigned DEFAULT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` enum('pending','paid','expired','cancelled','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qr_string` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway_response` json DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_links_token_unique` (`token`),
  KEY `payment_links_invoice_id_foreign` (`invoice_id`),
  KEY `payment_links_payment_gateway_id_foreign` (`payment_gateway_id`),
  KEY `payment_links_status_expires_at_index` (`status`,`expires_at`),
  CONSTRAINT `payment_links_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_links_payment_gateway_id_foreign` FOREIGN KEY (`payment_gateway_id`) REFERENCES `payment_gateways` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_links`
--

LOCK TABLES `payment_links` WRITE;
/*!40000 ALTER TABLE `payment_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_methods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_methods_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_methods`
--

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
INSERT INTO `payment_methods` VALUES (1,'Cash / Tunai','cash-tunai',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(2,'Transfer Bank','transfer-bank',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(3,'QRIS','qris',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(4,'GoPay','gopay',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(5,'OVO','ovo',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(6,'Dana','dana',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(7,'Kartu Debit','kartu-debit',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(8,'Kartu Kredit','kartu-kredit',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(9,'Piutang / Kredit','piutang-kredit',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(10,'POST PM 6a0cbd24d08b4','post-pm-6a0cbd24d08b4',NULL,1,'2026-05-19 12:42:28','2026-05-19 12:42:28',NULL);
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_records`
--

DROP TABLE IF EXISTS `payment_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `payment_method_id` bigint unsigned NOT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `reference_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_records_invoice_id_index` (`invoice_id`),
  KEY `payment_records_payment_method_id_index` (`payment_method_id`),
  KEY `payment_records_created_by_index` (`created_by`),
  CONSTRAINT `payment_records_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_records_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_records_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_records`
--

LOCK TABLES `payment_records` WRITE;
/*!40000 ALTER TABLE `payment_records` DISABLE KEYS */;
INSERT INTO `payment_records` VALUES (1,17,4,860000.00,'2026-05-19 19:15:22','POS-20260519-0001','POS payment',NULL,'2026-05-19 12:15:22','2026-05-19 12:15:22',NULL);
/*!40000 ALTER TABLE `payment_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'technician','web','2026-05-19 08:01:23','2026-05-19 08:01:23'),(2,'customer.view','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(3,'customer.create','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(4,'customer.edit','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(5,'customer.delete','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(6,'vehicle.view','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(7,'vehicle.create','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(8,'vehicle.edit','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(9,'vehicle.delete','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(10,'service.view','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(11,'service.create','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(12,'service.edit','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(13,'service.delete','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(14,'jobcard.view','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(15,'jobcard.create','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(16,'jobcard.edit','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(17,'jobcard.delete','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(18,'invoice.view','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(19,'invoice.create','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(20,'invoice.edit','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(21,'invoice.delete','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(22,'payment.view','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(23,'payment.create','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(24,'payment.edit','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(25,'payment.delete','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(26,'product.view','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(27,'product.create','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(28,'product.edit','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(29,'product.delete','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(30,'purchase.view','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(31,'purchase.create','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(32,'purchase.edit','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(33,'purchase.delete','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(34,'sale.view','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(35,'sale.create','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(36,'sale.edit','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(37,'sale.delete','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(38,'pos.view','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(39,'pos.create','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(40,'pos.edit','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(41,'pos.delete','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(42,'income.view','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(43,'income.create','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(44,'income.edit','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(45,'income.delete','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(46,'expense.view','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(47,'expense.create','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(48,'expense.edit','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(49,'expense.delete','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(50,'report.view','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(51,'report.create','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(52,'report.edit','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(53,'report.delete','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(54,'voucher.view','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(55,'voucher.create','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(56,'voucher.edit','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(57,'voucher.delete','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(58,'loyalty.view','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(59,'loyalty.create','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(60,'loyalty.edit','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(61,'loyalty.delete','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(62,'booking.view','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(63,'booking.create','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(64,'booking.edit','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(65,'booking.delete','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(66,'commission.view','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(67,'commission.create','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(68,'commission.edit','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(69,'commission.delete','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(70,'branch.view','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(71,'branch.create','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(72,'branch.edit','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(73,'branch.delete','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(74,'setting.view','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(75,'setting.create','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(76,'setting.edit','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(77,'setting.delete','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(78,'user.view','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(79,'user.create','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(80,'user.edit','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(81,'user.delete','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(82,'role.view','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(83,'role.create','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(84,'role.edit','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(85,'role.delete','web','2026-05-19 11:40:30','2026-05-19 11:40:30');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `petty_cash_transactions`
--

DROP TABLE IF EXISTS `petty_cash_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `petty_cash_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `type` enum('in','out','replenish') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `petty_cash_transactions_created_by_foreign` (`created_by`),
  KEY `petty_cash_transactions_branch_id_date_index` (`branch_id`,`date`),
  CONSTRAINT `petty_cash_transactions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `petty_cash_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `petty_cash_transactions`
--

LOCK TABLES `petty_cash_transactions` WRITE;
/*!40000 ALTER TABLE `petty_cash_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `petty_cash_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pos_sessions`
--

DROP TABLE IF EXISTS `pos_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `opened_at` timestamp NOT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `closing_balance` decimal(15,2) DEFAULT NULL,
  `expected_balance` decimal(15,2) DEFAULT NULL,
  `difference` decimal(15,2) DEFAULT NULL,
  `status` enum('open','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_sessions_branch_id_foreign` (`branch_id`),
  CONSTRAINT `pos_sessions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pos_sessions`
--

LOCK TABLES `pos_sessions` WRITE;
/*!40000 ALTER TABLE `pos_sessions` DISABLE KEYS */;
INSERT INTO `pos_sessions` VALUES (1,NULL,1,'2026-05-19 11:36:56','2026-05-19 13:00:47',1000000.00,NULL,NULL,NULL,'closed',NULL,'2026-05-19 11:36:56','2026-05-19 13:00:47',NULL);
/*!40000 ALTER TABLE `pos_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_types`
--

DROP TABLE IF EXISTS `product_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_types_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_types`
--

LOCK TABLES `product_types` WRITE;
/*!40000 ALTER TABLE `product_types` DISABLE KEYS */;
INSERT INTO `product_types` VALUES (1,'Oli & Pelumas','oli-pelumas',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(2,'Filter','filter',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(3,'Sistem Pengapian','sistem-pengapian',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(4,'Sistem Rem','sistem-rem',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(5,'Baterai / Aki','baterai-aki',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(6,'Cairan','cairan',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(7,'Ban & Velg','ban-velg',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(8,'Komponen Mesin','komponen-mesin',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(9,'Kelistrikan','kelistrikan',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(10,'Aksesoris','aksesoris',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(11,'Parts CVT','parts-cvt',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(12,'Rantai & Sproket','rantai-sproket',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL);
/*!40000 ALTER TABLE `product_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_units`
--

DROP TABLE IF EXISTS `product_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abbreviation` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_units`
--

LOCK TABLES `product_units` WRITE;
/*!40000 ALTER TABLE `product_units` DISABLE KEYS */;
INSERT INTO `product_units` VALUES (1,'Pcs','Pcs',1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(2,'Liter','Liter',1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(3,'Set','Set',1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(4,'Pasang','Pasang',1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(5,'Botol','Botol',1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(6,'Gram','Gram',1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(7,'Meter','Meter',1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(8,'Roll','Roll',1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL);
/*!40000 ALTER TABLE `product_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_type_id` bigint unsigned NOT NULL,
  `unit_id` bigint unsigned NOT NULL,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `price` decimal(15,2) NOT NULL,
  `cost_price` decimal(15,2) DEFAULT NULL,
  `warranty` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'warranty period',
  `description` text COLLATE utf8mb4_unicode_ci,
  `branch_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `warranty_months` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_product_no_unique` (`product_no`),
  UNIQUE KEY `products_code_unique` (`code`),
  KEY `products_product_type_id_index` (`product_type_id`),
  KEY `products_unit_id_index` (`unit_id`),
  KEY `products_supplier_id_index` (`supplier_id`),
  KEY `products_name_index` (`name`),
  KEY `products_branch_id_index` (`branch_id`),
  CONSTRAINT `products_product_type_id_foreign` FOREIGN KEY (`product_type_id`) REFERENCES `product_types` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `products_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `products_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `product_units` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'PRD-001','OLI-001','Oli Mesin Shell Helix HX5 10W-40 (1L)',1,2,1,65000.00,55000.00,'6 bulan',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(2,'PRD-002','OLI-002','Oli Mesin Castrol GTX 10W-40 (1L)',1,2,1,60000.00,50000.00,'6 bulan',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(3,'PRD-003','OLI-003','Oli Gardan Matic Honda (0.8L)',1,2,1,45000.00,38000.00,'3 bulan',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(4,'PRD-004','OLI-004','Oli Mesin Yamalube 10W-40 (0.8L)',1,2,1,55000.00,48000.00,'6 bulan',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(5,'PRD-005','OLI-005','Oli Transmisi ATF Dexron III (1L)',1,2,1,85000.00,70000.00,'1 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(6,'PRD-006','FLT-001','Filter Oli Honda Beat/Vario',2,1,1,35000.00,28000.00,NULL,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(7,'PRD-007','FLT-002','Filter Oli Toyota Avanza',2,1,1,45000.00,37000.00,NULL,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(8,'PRD-008','FLT-003','Filter Udara Honda Beat',2,1,1,40000.00,32000.00,'1 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(9,'PRD-009','FLT-004','Filter Udara Toyota Avanza',2,1,1,55000.00,45000.00,'1 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(10,'PRD-010','FLT-005','Filter Kabin Toyota Avanza',2,1,1,75000.00,60000.00,'1 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(11,'PRD-011','ZAP-001','Busi NGK Standar (pcs)',3,1,1,25000.00,18000.00,'1 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(12,'PRD-012','ZAP-002','Busi NGK Iridium (pcs)',3,1,1,85000.00,70000.00,'2 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(13,'PRD-013','ZAP-003','Busi Denso Iridium (pcs)',3,1,1,90000.00,75000.00,'2 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(14,'PRD-014','BRK-001','Kampas Rem Depan Honda Beat (pasang)',4,4,1,85000.00,65000.00,'1 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(15,'PRD-015','BRK-002','Kampas Rem Depan Toyota Avanza (set)',4,3,1,185000.00,150000.00,'1 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(16,'PRD-016','BRK-003','Kampas Rem Belakang Toyota Avanza (set)',4,3,1,165000.00,135000.00,'1 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(17,'PRD-017','BRK-004','Kampas Rem Tromol Honda Beat (pasang)',4,4,1,75000.00,60000.00,'1 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(18,'PRD-018','AKI-001','Aki GS Astra MF 35Ah (Mobil)',5,1,1,650000.00,550000.00,'1 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(19,'PRD-019','AKI-002','Aki GS Astra MF 45Ah (Mobil)',5,1,1,750000.00,650000.00,'1 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(20,'PRD-020','AKI-003','Aki Motor Yuasa MF 5Ah',5,1,1,195000.00,165000.00,'1 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(21,'PRD-021','CVT-001','V-Belt Honda Beat / Vario',11,1,1,145000.00,120000.00,'1 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(22,'PRD-022','CVT-002','Roller Honda Beat 12g (set)',11,1,1,55000.00,45000.00,'1 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(23,'PRD-023','CVT-003','Kampas Ganda Honda Beat',11,1,1,120000.00,100000.00,'1 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(24,'PRD-024','CVT-004','V-Belt Yamaha NMAX / Aerox',11,1,1,185000.00,155000.00,'1 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(25,'PRD-025','RNT-001','Rantai + Sproket Honda Beat (set)',12,3,1,185000.00,155000.00,'1 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(26,'PRD-026','RNT-002','Rantai + Sproket Yamaha Jupiter (set)',12,3,1,195000.00,165000.00,'1 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(27,'PRD-027','CAI-001','Air Aki / Akuades (1L)',6,2,1,8000.00,5000.00,NULL,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(28,'PRD-028','CAI-002','Minyak Rem DOT 3 (200ml)',6,5,1,25000.00,18000.00,'1 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(29,'PRD-029','CAI-003','Air Radiator / Coolant (1L)',6,2,1,35000.00,28000.00,'2 tahun',NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL,0),(35,'PRD-202605-0001','PST-6a0cbd23a03ca','POST Test Product',1,1,NULL,50000.00,30000.00,NULL,'desc',NULL,'2026-05-19 12:42:27','2026-05-19 12:42:27',NULL,0);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_history_records`
--

DROP TABLE IF EXISTS `purchase_history_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_history_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `changed_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_history_records_purchase_id_index` (`purchase_id`),
  KEY `purchase_history_records_status_index` (`status`),
  CONSTRAINT `purchase_history_records_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_history_records`
--

LOCK TABLES `purchase_history_records` WRITE;
/*!40000 ALTER TABLE `purchase_history_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_history_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_items`
--

DROP TABLE IF EXISTS `purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_items_purchase_id_index` (`purchase_id`),
  KEY `purchase_items_product_id_index` (`product_id`),
  CONSTRAINT `purchase_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_items_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_items`
--

LOCK TABLES `purchase_items` WRITE;
/*!40000 ALTER TABLE `purchase_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchases`
--

DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_id` bigint unsigned NOT NULL,
  `purchase_date` date NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchases_supplier_id_index` (`supplier_id`),
  KEY `purchases_created_by_index` (`created_by`),
  KEY `purchases_branch_id_index` (`branch_id`),
  KEY `purchases_purchase_no_index` (`purchase_no`),
  KEY `purchases_status_index` (`status`),
  KEY `purchases_purchase_date_index` (`purchase_date`),
  CONSTRAINT `purchases_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchases_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchases_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchases`
--

LOCK TABLES `purchases` WRITE;
/*!40000 ALTER TABLE `purchases` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recalls`
--

DROP TABLE IF EXISTS `recalls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recalls` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned DEFAULT NULL,
  `vehicle_brand_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `issue_date` date NOT NULL,
  `severity` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recalls_product_id_foreign` (`product_id`),
  KEY `recalls_vehicle_brand_id_foreign` (`vehicle_brand_id`),
  CONSTRAINT `recalls_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recalls_vehicle_brand_id_foreign` FOREIGN KEY (`vehicle_brand_id`) REFERENCES `vehicle_brands` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recalls`
--

LOCK TABLES `recalls` WRITE;
/*!40000 ALTER TABLE `recalls` DISABLE KEYS */;
/*!40000 ALTER TABLE `recalls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reminders`
--

DROP TABLE IF EXISTS `reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reminders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `vehicle_id` bigint unsigned NOT NULL,
  `service_id` bigint unsigned NOT NULL,
  `reminder_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reminder_date` date NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `sent` tinyint(1) NOT NULL DEFAULT '0',
  `sent_at` datetime DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reminders_customer_id_index` (`customer_id`),
  KEY `reminders_vehicle_id_index` (`vehicle_id`),
  KEY `reminders_service_id_index` (`service_id`),
  KEY `reminders_branch_id_index` (`branch_id`),
  CONSTRAINT `reminders_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reminders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reminders_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reminders_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reminders`
--

LOCK TABLES `reminders` WRITE;
/*!40000 ALTER TABLE `reminders` DISABLE KEYS */;
INSERT INTO `reminders` VALUES (2,1,1,2,'service_followup','2026-06-18',NULL,0,NULL,1,1,'2026-05-19 12:40:04','2026-05-19 12:40:04',NULL);
/*!40000 ALTER TABLE `reminders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repair_categories`
--

DROP TABLE IF EXISTS `repair_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `repair_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `repair_category_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `repair_categories_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_categories`
--

LOCK TABLES `repair_categories` WRITE;
/*!40000 ALTER TABLE `repair_categories` DISABLE KEYS */;
INSERT INTO `repair_categories` VALUES (1,'Servis Berkala','servis-berkala',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(2,'Kendaraan Rusak/Mogok','kendaraan-rusak',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(3,'Repeat Job','repeat-job',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(4,'Customer Menunggu','customer-menunggu',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(5,'Klaim Garansi','klaim-garansi',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL);
/*!40000 ALTER TABLE `repair_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `reviewer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` int NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `admin_reply` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reviews_service_id_foreign` (`service_id`),
  KEY `reviews_customer_id_foreign` (`customer_id`),
  KEY `reviews_branch_id_foreign` (`branch_id`),
  KEY `reviews_is_published_rating_index` (`is_published`,`rating`),
  CONSTRAINT `reviews_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reviews_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reviews_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1),(11,1),(12,1),(13,1),(14,1),(15,1),(16,1),(17,1),(18,1),(19,1),(20,1),(21,1),(22,1),(23,1),(24,1),(25,1),(26,1),(27,1),(28,1),(29,1),(30,1),(31,1),(32,1),(33,1),(34,1),(35,1),(36,1),(37,1),(38,1),(39,1),(40,1),(41,1),(42,1),(43,1),(44,1),(45,1),(46,1),(47,1),(48,1),(49,1),(50,1),(51,1),(52,1),(53,1),(54,1),(55,1),(56,1),(57,1),(58,1),(59,1),(60,1),(61,1),(62,1),(63,1),(64,1),(65,1),(66,1),(67,1),(68,1),(69,1),(70,1),(71,1),(72,1),(73,1),(74,1),(75,1),(76,1),(77,1),(78,1),(79,1),(80,1),(81,1),(82,1),(83,1),(84,1),(85,1),(1,2),(2,2),(3,2),(4,2),(5,2),(6,2),(7,2),(8,2),(9,2),(10,2),(11,2),(12,2),(13,2),(14,2),(15,2),(16,2),(17,2),(18,2),(19,2),(20,2),(21,2),(22,2),(23,2),(24,2),(25,2),(26,2),(27,2),(28,2),(29,2),(30,2),(31,2),(32,2),(33,2),(34,2),(35,2),(36,2),(37,2),(38,2),(39,2),(40,2),(41,2),(42,2),(43,2),(44,2),(45,2),(46,2),(47,2),(48,2),(49,2),(50,2),(51,2),(52,2),(53,2),(54,2),(55,2),(56,2),(57,2),(58,2),(59,2),(60,2),(61,2),(62,2),(63,2),(64,2),(65,2),(66,2),(67,2),(68,2),(69,2),(70,2),(71,2),(72,2),(73,2),(74,2),(75,2),(76,2),(77,2),(78,2),(79,2),(80,2),(81,2);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'super_admin','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(2,'admin','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(3,'manager','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(4,'technician','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(5,'cashier','web','2026-05-19 11:40:30','2026-05-19 11:40:30'),(6,'viewer','web','2026-05-19 11:40:30','2026-05-19 11:40:30');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salaries`
--

DROP TABLE IF EXISTS `salaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salaries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `period_year` int NOT NULL,
  `period_month` int NOT NULL,
  `base_salary` decimal(15,2) NOT NULL DEFAULT '0.00',
  `commission_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `allowance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `deduction` decimal(15,2) NOT NULL DEFAULT '0.00',
  `net_salary` decimal(15,2) NOT NULL DEFAULT '0.00',
  `days_present` int NOT NULL DEFAULT '0',
  `days_absent` int NOT NULL DEFAULT '0',
  `status` enum('draft','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `paid_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `salaries_user_id_period_year_period_month_unique` (`user_id`,`period_year`,`period_month`),
  CONSTRAINT `salaries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salaries`
--

LOCK TABLES `salaries` WRITE;
/*!40000 ALTER TABLE `salaries` DISABLE KEYS */;
/*!40000 ALTER TABLE `salaries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sales_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `vehicle_id` bigint unsigned DEFAULT NULL,
  `sale_date` date NOT NULL,
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `down_payment` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `grand_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `salesperson_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_salesperson_id_foreign` (`salesperson_id`),
  KEY `sales_sales_no_index` (`sales_no`),
  KEY `sales_customer_id_index` (`customer_id`),
  KEY `sales_vehicle_id_index` (`vehicle_id`),
  KEY `sales_created_by_index` (`created_by`),
  KEY `sales_branch_id_index` (`branch_id`),
  KEY `sales_sale_date_index` (`sale_date`),
  CONSTRAINT `sales_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_salesperson_id_foreign` FOREIGN KEY (`salesperson_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_images`
--

DROP TABLE IF EXISTS `service_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_images` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint unsigned NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caption` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_images_service_id_index` (`service_id`),
  CONSTRAINT `service_images_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_images`
--

LOCK TABLES `service_images` WRITE;
/*!40000 ALTER TABLE `service_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_observation_points`
--

DROP TABLE IF EXISTS `service_observation_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_observation_points` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint unsigned NOT NULL,
  `observation_point_id` bigint unsigned NOT NULL,
  `checked` tinyint(1) NOT NULL DEFAULT '0',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_observation_points_service_id_index` (`service_id`),
  KEY `service_observation_points_observation_point_id_index` (`observation_point_id`),
  CONSTRAINT `service_observation_points_observation_point_id_foreign` FOREIGN KEY (`observation_point_id`) REFERENCES `observation_points` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_observation_points_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_observation_points`
--

LOCK TABLES `service_observation_points` WRITE;
/*!40000 ALTER TABLE `service_observation_points` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_observation_points` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_taxes`
--

DROP TABLE IF EXISTS `service_taxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_taxes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint unsigned NOT NULL,
  `tax_rate_id` bigint unsigned NOT NULL,
  `tax_amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_taxes_service_id_index` (`service_id`),
  KEY `service_taxes_tax_rate_id_index` (`tax_rate_id`),
  CONSTRAINT `service_taxes_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_taxes_tax_rate_id_foreign` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_taxes`
--

LOCK TABLES `service_taxes` WRITE;
/*!40000 ALTER TABLE `service_taxes` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_taxes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_technicians`
--

DROP TABLE IF EXISTS `service_technicians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_technicians` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `role` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'lead',
  `commission_pct` decimal(5,2) NOT NULL DEFAULT '10.00',
  `commission_amt` decimal(15,2) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `paid_by` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_technicians_service_id_user_id_unique` (`service_id`,`user_id`),
  KEY `service_technicians_service_id_index` (`service_id`),
  KEY `service_technicians_user_id_index` (`user_id`),
  CONSTRAINT `service_technicians_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_technicians_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_technicians`
--

LOCK TABLES `service_technicians` WRITE;
/*!40000 ALTER TABLE `service_technicians` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_technicians` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `vehicle_id` bigint unsigned NOT NULL,
  `repair_category_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `service_date` datetime DEFAULT NULL,
  `charge` decimal(15,2) DEFAULT NULL,
  `done_status` int NOT NULL DEFAULT '0',
  `assign_to` bigint unsigned DEFAULT NULL,
  `mot_status` tinyint(1) NOT NULL DEFAULT '0',
  `is_quotation` tinyint(1) NOT NULL DEFAULT '0',
  `is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `job_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `tracking_token` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estimate_token` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estimate_approved_at` timestamp NULL DEFAULT NULL,
  `estimate_approved_by_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estimate_rejection_reason` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `services_tracking_token_unique` (`tracking_token`),
  UNIQUE KEY `services_estimate_token_unique` (`estimate_token`),
  KEY `services_customer_id_index` (`customer_id`),
  KEY `services_vehicle_id_index` (`vehicle_id`),
  KEY `services_repair_category_id_index` (`repair_category_id`),
  KEY `services_assign_to_index` (`assign_to`),
  KEY `services_created_by_index` (`created_by`),
  KEY `services_branch_id_index` (`branch_id`),
  CONSTRAINT `services_assign_to_foreign` FOREIGN KEY (`assign_to`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `services_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `services_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `services_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `services_repair_category_id_foreign` FOREIGN KEY (`repair_category_id`) REFERENCES `repair_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `services_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (2,1,1,1,'Servis Berkala 10.000 km Avanza','Ganti oli Shell HX5, filter oli, cek rem & ban','2026-04-01 08:00:00',185000.00,2,NULL,0,0,1,1,NULL,'BP-0001','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL,'0a754a945ef29ac22e480ad9344864c1',NULL,NULL,NULL,NULL),(3,2,2,1,'Servis Berkala Xenia','Ganti oli mesin, cek kaki-kaki, cek tekanan ban','2026-04-03 09:00:00',75000.00,2,NULL,0,0,1,1,NULL,'BP-0002','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL,'5ba4d2dcd146f1fa8c42f4ec478af629',NULL,NULL,NULL,NULL),(4,3,3,2,'Ganti Kampas Rem + Busi Fortuner','Ganti kampas rem depan belakang, ganti 4 busi NGK','2026-04-05 10:00:00',235000.00,2,NULL,0,0,1,1,NULL,'BP-0003','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL,'73f0a1e868f6ea499900af916fb83892',NULL,NULL,NULL,NULL),(5,4,4,1,'Servis CVT Honda Beat','Bersih CVT, ganti roller & kampas ganda Honda Beat','2026-04-07 08:30:00',125000.00,2,NULL,0,0,1,1,NULL,'BP-0004','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL,'660c77604d48725d883abd3ba8f8ad12',NULL,NULL,NULL,NULL),(6,5,5,1,'Servis Berkala + Ganti Aki NMAX','Ganti oli Yamalube, filter, ganti aki Yuasa NMAX','2026-04-10 09:00:00',700000.00,2,NULL,0,0,1,1,NULL,'BP-0005','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL,'8acc4ea77ee63df0725d8274c8b91266',NULL,NULL,NULL,NULL),(7,1,6,3,'Tune Up Brio Satya','Bersih throttle body, ganti filter udara & busi NGK','2026-04-12 10:00:00',350000.00,2,NULL,0,0,1,1,NULL,'BP-0006','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL,'628ed0fc14f8a8eb4899405e28da17c3',NULL,NULL,NULL,NULL),(8,2,8,2,'Ganti Kampas Rem GSX-R150','Ganti kampas rem depan & belakang Suzuki GSX-R150','2026-04-15 11:00:00',85000.00,2,NULL,0,0,1,1,NULL,'BP-0007','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL,'cc5527795adde190e9fa716a8908071e',NULL,NULL,NULL,NULL),(9,3,7,2,'Servis AC Pajero Sport','Isi freon R134a, bersih kondensor, ganti filter kabin','2026-04-17 08:00:00',450000.00,2,NULL,0,0,1,1,NULL,'BP-0008','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL,'17b104f3660e2c5fd97c30c3c962abde',NULL,NULL,NULL,NULL),(10,4,4,1,'Ganti Oli + Filter Honda Beat','Ganti oli Shell HX5 & filter oli Honda Beat','2026-04-20 09:30:00',110000.00,2,NULL,0,0,1,1,NULL,'BP-0009','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL,'51bce056a1b232a2ed7c24d7ea45bd19',NULL,NULL,NULL,NULL),(11,5,5,1,'Servis Berkala NMAX 20.000 km','Ganti oli Yamalube, filter, busi iridium, cek rantai','2026-04-22 10:00:00',285000.00,2,NULL,0,0,1,1,NULL,'BP-0010','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL,'159bdc8a6fa711e1fb236ab45c8421a0',NULL,NULL,NULL,NULL),(12,1,1,2,'Ganti V-Belt CVT Avanza','Ganti V-Belt & roller Toyota Avanza di 65.000 km','2026-04-25 08:00:00',195000.00,2,NULL,0,0,1,1,NULL,'BP-0011','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL,'f3ae5777dc209beb03cf7cb21e05c574',NULL,NULL,NULL,NULL),(13,2,2,1,'Spooring & Balancing Xenia','Spooring balancing 4 roda, cek ball joint & tie rod','2026-04-28 09:00:00',300000.00,1,NULL,0,0,0,1,NULL,'BP-0012','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL,'d19dce58ac7580958c2fc92d1b850d9e',NULL,NULL,NULL,NULL),(14,3,3,2,'Ganti Aki Toyota Fortuner','Ganti aki GS MF 45Ah Toyota Fortuner','2026-04-29 10:00:00',680000.00,1,NULL,0,0,0,1,NULL,'BP-0013','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL,'4e7ca079a4e4b6a9cf52fe2bbcc45c7b',NULL,NULL,NULL,NULL),(15,4,4,1,'Servis Berkala Honda Beat 16rb km','Ganti oli, filter, bersih karbu Honda Beat','2026-04-30 08:30:00',95000.00,1,NULL,0,0,0,1,NULL,'BP-0014','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL,'a186d353aae137d9a30833dd745c4cfd',NULL,NULL,NULL,NULL),(16,5,5,1,'Tune Up NMAX + Busi Iridium','Tune up lengkap NMAX 155, ganti busi Denso iridium','2026-04-30 09:00:00',120000.00,1,NULL,0,0,0,1,NULL,'BP-0015','2026-05-02 07:55:43','2026-05-02 07:55:43',NULL,'b6a85d1fe6894fbbc7306df0b3030fe1',NULL,NULL,NULL,NULL),(22,16,15,1,'POST Test Service','desc','2026-05-19 00:00:00',250000.00,1,NULL,0,0,0,1,NULL,'BP-20260519-001','2026-05-19 12:42:27','2026-05-19 12:42:27',NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('11rsv0yvTZAehb1uAxdIPUy2cwNKv1Cjll5rvqYP',NULL,'127.0.0.1','curl/8.19.0','eyJfdG9rZW4iOiJrd3pqQmE0UlpKN2IwUGliNnJ6bkRHamlIeFhCeUZJQzB4N0VZbEtZIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTI0XC9sb2dpbiIsInJvdXRlIjoibG9naW4ifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1779219454),('3H68m05GaLzzyEcLNDR45Mae1tmpoadAjhVmRMQP',NULL,'127.0.0.1','curl/8.19.0','eyJfdG9rZW4iOiIzalBXZ2ZaejZaOHdjd0VGWWN2Ynl0SXczOUE4emNUWXNBMG5hNzg4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4NzY1Iiwicm91dGUiOiJkYXNoYm9hcmQifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1779218621),('5yXoKgcSUy7J4NAytweplQuTiFTJTOOjFZovXULj',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJUWUNmQ044bHRyZG1YZW04eThNNHVrbzNYczgweTZzOFhVS0EyOWwwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4NzY1XC9hY3Rpdml0eS1sb2dzIiwicm91dGUiOiJhY3Rpdml0eS1sb2dzLmluZGV4In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjF9',1779218382),('6a2FFm4jWTgANUWr3DLc34uZ7j9W5EojBmnuZF2I',NULL,'127.0.0.1','curl/8.19.0','eyJfdG9rZW4iOiJicEZ4MmxNZXdKVEFwV21rY1UxRHU5ajN6ZW83VlprUVRWVFE0aEZRIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTI0Iiwicm91dGUiOiJkYXNoYm9hcmQifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1779219454),('8ligzuHoZyOSXMcUiI6bW1F6EHLQ6bS8TX3tDsAB',1,'127.0.0.1','','eyJfdG9rZW4iOiJSNEo1eDBiblVyeXNqQ1A1cXoxeGZkN1VmNnE4NlphUFRiTWNKNEVIIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTI0XC93YXNoYmF5c1wvMVwvZWRpdCIsInJvdXRlIjoid2FzaGJheXMuZWRpdCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxfQ==',1779219641),('A5VfQ5fOReEn3WliThhIy2xD1k3evhAIVhXCxcc7',NULL,'127.0.0.1','curl/8.19.0','eyJfdG9rZW4iOiJFT2RjR3hORjUzNWZBOXRyN0ZZM2dBOFZMS2ZRZ2FITVI2MDZxamJ5IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTI0Iiwicm91dGUiOiJkYXNoYm9hcmQifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1779219742),('dHFHQXT9rWEerWnTfwuhMKXba3fVtqFJvtsJJvd1',1,'127.0.0.1','','eyJfdG9rZW4iOiIzMEpxcWd4M2NaSlNkM216SkNJZnBObjJsNnR4ZU9QMkFab0tBZDFzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTI0XC9wdXJjaGFzZXNcL2NyZWF0ZSIsInJvdXRlIjoicHVyY2hhc2VzLmNyZWF0ZSJ9LCJfZmxhc2giOnsib2xkIjpbInN1Y2Nlc3MiXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJzdWNjZXNzIjoiUHVyY2hhc2Ugb3JkZXIgYmVyaGFzaWwgZGlidWF0LiJ9',1779220571),('eGs3KVqWYUlXLCw6fmnbxWlRhAFhPSIxhaRCAsDG',1,'127.0.0.1','curl/8.19.0','eyJfdG9rZW4iOiJRQTZkdE9QVXVZaVBsOFZJcXA0QjJaN1JmcDBhRGNnc1VKdWpkQ0NBIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTI0XC9nYXRlLXBhc3Nlc1wvM1wvZWRpdCIsInJvdXRlIjoiZ2F0ZS1wYXNzZXMuZWRpdCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxfQ==',1779219652),('FlnWO0vTYOZNCsHD6gOxms8qR3kSzu7vm4hN7o0X',1,'127.0.0.1','','eyJfdG9rZW4iOiJLcGxCTm82QXk3YlN0WXUxbG5peXJyWlJRWEY0TmpURkRqa2liQjl4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTI0XC93YXNoYmF5c1wvY3JlYXRlIiwicm91dGUiOiJ3YXNoYmF5cy5jcmVhdGUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MX0=',1779219529),('FTsLLP9L1ZqRPc0abJTyAwUZQER3WSzWuBD0wv9R',1,'127.0.0.1','','eyJfdG9rZW4iOiJZaDFCenVIajlSMEJ4cURESU95ZFRWNHo2NGVHQWhDcldnTnFNeFFLIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTI0XC9wdXJjaGFzZXNcL2NyZWF0ZSIsInJvdXRlIjoicHVyY2hhc2VzLmNyZWF0ZSJ9LCJfZmxhc2giOnsib2xkIjpbImVycm9yIl0sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MSwiZXJyb3IiOiJIYW55YSBwdXJjaGFzZSBvcmRlciBkZW5nYW4gc3RhdHVzIERyYWZ0IHlhbmcgZGFwYXQgZGloYXB1cy4ifQ==',1779220667),('h9qJh20PccGTgT1kh2arAMFZKa7pNsYz0EjECDYf',1,'127.0.0.1','','eyJfdG9rZW4iOiJGWFNDVGFjaERXUmkxUjB4U1hhRGljRXFhdTg5MXI5VEtHMzFra3JPIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTI0XC93YXNoYmF5c1wvMVwvZWRpdCIsInJvdXRlIjoid2FzaGJheXMuZWRpdCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxfQ==',1779219678),('IsCt5UYLPresTPPMeXA4DLan96okLkuFZlxpkcTy',NULL,'127.0.0.1','curl/8.19.0','eyJfdG9rZW4iOiJldjd2bjZLTEh4NFdPcDdkME5Za29nZVFObXZHRUZDRWhVVXhOUlJxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTI0Iiwicm91dGUiOiJkYXNoYm9hcmQifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1779220480),('JP7q5DdHVdxZVmdimKwSnojw6VuhlRfPqezDU379',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJ0NlhLbTNYSHQ3bTE4MEU4MHo4VklGd2h1cVBSVzFmOUlWMHVsSThhIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2JlbmdrZWwudGVzdCIsInJvdXRlIjoiZGFzaGJvYXJkIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1779334318),('NvJ0IE4cPCz8bBYgH5x5e4vrirCDhcU1MKKk7fWc',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJnUkdSMmtiY0ZkUXRiUVFScHhJdlVuQThSVDlkbjM0ejJhQkNXWElKIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4NzY1Iiwicm91dGUiOiJkYXNoYm9hcmQifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1779218622),('o6dqdoN6DgViJnmrjxrvHeALOx8bA8HsoxKwgBdI',1,'127.0.0.1','','eyJfdG9rZW4iOiI5WXZnZHpITTNORmdGaDFERGpZMEpZbm0yWWh6TnhtU0NhUGU3WklJIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTI0XC9wdXJjaGFzZXNcL2NyZWF0ZSIsInJvdXRlIjoicHVyY2hhc2VzLmNyZWF0ZSJ9LCJfZmxhc2giOnsib2xkIjpbInN1Y2Nlc3MiXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJzdWNjZXNzIjoiUHVyY2hhc2Ugb3JkZXIgYmVyaGFzaWwgZGlidWF0LiJ9',1779220616),('oYSqrSb2XQWLrVvRwXdL3T8AxdnOwHw8hRGT1OFH',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJ6SjlTazhtOEpTQjhQRjRWWkducFNWS2pzN1kzblNuNTI1Y3RhdkNsIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvYmVuZ2tlbC50ZXN0Iiwicm91dGUiOiJkYXNoYm9hcmQifX0=',1779221943),('qRqmAlqDcNnYfphsMRGpDyk3lD4dTvZkgEAGiTzU',1,'127.0.0.1','','eyJfdG9rZW4iOiJ3eDl0SmV0MEp3clpCc0ZSZkhKdWJlM3diRVM3QnB4c2Fwa2Y5b25KIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTI0XC9zYWxlc1wvY3JlYXRlIiwicm91dGUiOiJzYWxlcy5jcmVhdGUifSwiX2ZsYXNoIjp7Im9sZCI6WyJzdWNjZXNzIl0sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MSwic3VjY2VzcyI6IlBlbmp1YWxhbiBiZXJoYXNpbCBkaWhhcHVzLiJ9',1779220804),('QxQ5ETaDfInIIJxUlfRyPiLsX5C99NL8AoXrmJQF',NULL,'127.0.0.1','curl/8.19.0','eyJfdG9rZW4iOiJMcjZXeXBqczJXS1ZiYTlaYnBtZzNRYVBiT1FrVlJZeEhuYlNxVWF4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4NzY1XC9sb2dpbiIsInJvdXRlIjoibG9naW4ifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1779218273),('qz0559sNhuTif2qHxXH6OSmiXUvObzpshs7fy0Vu',1,'127.0.0.1','','eyJfdG9rZW4iOiJKb25XNUZZbkFac1RnNTRWZ2wyZEJGOGNsQlpNa1hGRFRsWkRVdHBmIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTI0XC9wdXJjaGFzZXNcLzYiLCJyb3V0ZSI6InB1cmNoYXNlcy5zaG93In0sIl9mbGFzaCI6eyJvbGQiOlsic3VjY2VzcyJdLCJuZXciOltdfSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjEsInN1Y2Nlc3MiOiJTdG9rIGJlcmhhc2lsIGRpdGFtYmFoa2FuLiBTdGF0dXMgUE8gZGl1YmFoIG1lbmphZGkgRGl0ZXJpbWEuIn0=',1779220512),('Vw3O2rZWUc2VbPYiebB5rHeLNLy5wUtldJDfhLiK',NULL,'127.0.0.1','curl/8.19.0','eyJfdG9rZW4iOiJhR2pXT1J5eTBGWXl6OGVxczFheUNBWmVWVjcxc3RKM2tGamtLQjhuIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTI0Iiwicm91dGUiOiJkYXNoYm9hcmQifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1779220796),('wBc4EZTFRyGzDQP2Mdx2nVQBTUsKRJlAFJbtkFXl',1,'127.0.0.1','','eyJfdG9rZW4iOiJ1TVV5VFdFRUxad0dzQ0s2bUwxY3R5b09LaGZMMXpQbjhsQUtxU2pmIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTI0XC9wb3NcL3Nlc3Npb25zXC8zXC9jbG9zZSIsInJvdXRlIjoicG9zLmNsb3NlRm9ybSJ9LCJfZmxhc2giOnsib2xkIjpbInN1Y2Nlc3MiXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJzdWNjZXNzIjoiU2VzaSBrYXNpciBkaXR1dHVwLiJ9',1779220848),('wuDq46Jw9E3kn8NoD0wwso7gj6IVquIb7tBk32Ce',NULL,'127.0.0.1','curl/8.19.0','eyJfdG9rZW4iOiJIRlVQOWZsTkNvV21CckZORGZoaEIyOGhEVEZNQkxISGNaYzlrOGVzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTIzIiwicm91dGUiOiJkYXNoYm9hcmQifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1779218804),('X2Fbp3fZ9vRLYkAzIFoGHqqumQ7MGLzuB7hGOS6h',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJkVGxqQUNaZ0tkMkpMOFltR0FhUklpVFA1ZklhUFBTV3NPSjRtOU96IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2JlbmdrZWwudGVzdFwvZG9jc1wvbGljZW5zZS1wYWlyaW5nIiwicm91dGUiOiJkb2NzLnNob3cifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1779334363),('xltHFcsiJmh4mqtHEtd27jEnQwqZ0tFcIuWCnKTI',1,'127.0.0.1','','eyJfdG9rZW4iOiJacXdmUXl3UmtPcnJCWW42UHlVd3hCaHRyWHZGbkpOV0lTWmhYamMxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTI0XC9ib29raW5nIiwicm91dGUiOiJwdWJsaWMuYm9va2luZyJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxfQ==',1779219749),('yS6yHKqAqpQ8JzACpw5lcDfiOIroXZYwAbOgP68l',1,'127.0.0.1','','eyJfdG9rZW4iOiJlRXh1blFPNDNlYW9xRERVblFXT1RxZHNSdTN6aVJXNWFOeGRsTUJGIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTI0XC93YXNoYmF5c1wvMVwvZWRpdCIsInJvdXRlIjoid2FzaGJheXMuZWRpdCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxfQ==',1779219587);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `group` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'system_name','Bengkel Paten','general','2026-05-02 07:53:02','2026-05-02 07:53:02',NULL),(2,'address','Jl. Siliwangi No. 88, Semarang','general','2026-05-02 07:53:02','2026-05-02 07:53:02',NULL),(3,'phone','0241234567','general','2026-05-02 07:53:02','2026-05-02 07:53:02',NULL),(4,'email','info@bengkelpaten.id','general','2026-05-02 07:53:02','2026-05-02 07:53:02',NULL),(5,'invoice_prefix','INV','invoice','2026-05-02 07:53:02','2026-05-02 07:53:02',NULL),(6,'currency','IDR','general','2026-05-02 07:53:02','2026-05-02 07:53:02',NULL);
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `states`
--

DROP TABLE IF EXISTS `states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `states` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `states_country_id_index` (`country_id`),
  CONSTRAINT `states_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `states`
--

LOCK TABLES `states` WRITE;
/*!40000 ALTER TABLE `states` DISABLE KEYS */;
/*!40000 ALTER TABLE `states` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_histories`
--

DROP TABLE IF EXISTS `stock_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `quantity_change` int NOT NULL,
  `previous_stock` int NOT NULL,
  `new_stock` int NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_histories_product_id_index` (`product_id`),
  KEY `stock_histories_type_index` (`type`),
  KEY `stock_histories_user_id_index` (`user_id`),
  KEY `stock_histories_created_at_index` (`created_at`),
  KEY `stock_histories_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  CONSTRAINT `stock_histories_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_histories`
--

LOCK TABLES `stock_histories` WRITE;
/*!40000 ALTER TABLE `stock_histories` DISABLE KEYS */;
INSERT INTO `stock_histories` VALUES (1,12,-1,40,39,'pos','App\\Models\\Invoice',17,'POS sale POS-20260519-0001',1,'2026-05-19 12:15:22','2026-05-19 12:15:22'),(2,13,-1,30,29,'pos','App\\Models\\Invoice',17,'POS sale POS-20260519-0001',1,'2026-05-19 12:15:22','2026-05-19 12:15:22'),(3,18,-1,8,7,'pos','App\\Models\\Invoice',17,'POS sale POS-20260519-0001',1,'2026-05-19 12:15:22','2026-05-19 12:15:22'),(4,29,-1,20,19,'pos','App\\Models\\Invoice',17,'POS sale POS-20260519-0001',1,'2026-05-19 12:15:22','2026-05-19 12:15:22'),(7,1,5,50,55,'purchase','App\\Models\\Purchase',6,'PO #PO-20260519-0001',1,'2026-05-19 12:55:12','2026-05-19 12:55:12'),(8,1,1,55,56,'purchase','App\\Models\\Purchase',10,'PO #PO-20260519-0002',1,'2026-05-19 12:57:46','2026-05-19 12:57:46');
/*!40000 ALTER TABLE `stock_histories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_records`
--

DROP TABLE IF EXISTS `stock_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `minimum_stock` int NOT NULL DEFAULT '0',
  `rack_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_records_product_id_index` (`product_id`),
  KEY `stock_records_supplier_id_index` (`supplier_id`),
  KEY `stock_records_quantity_index` (`quantity`),
  KEY `stock_records_branch_id_index` (`branch_id`),
  CONSTRAINT `stock_records_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_records_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_records`
--

LOCK TABLES `stock_records` WRITE;
/*!40000 ALTER TABLE `stock_records` DISABLE KEYS */;
INSERT INTO `stock_records` VALUES (1,1,1,56,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-19 13:00:48',NULL),(2,2,1,40,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(3,3,1,30,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(4,4,1,35,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(5,5,1,20,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(6,6,1,40,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(7,7,1,30,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(8,8,1,25,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(9,9,1,20,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(10,10,1,15,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(11,11,1,60,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(12,12,1,39,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-19 12:15:22',NULL),(13,13,1,29,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-19 12:15:22',NULL),(14,14,1,20,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(15,15,1,15,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(16,16,1,15,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(17,17,1,20,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(18,18,1,7,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-19 12:15:22',NULL),(19,19,1,6,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(20,20,1,12,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(21,21,1,15,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(22,22,1,20,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(23,23,1,15,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(24,24,1,12,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(25,25,1,10,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(26,26,1,8,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(27,27,1,50,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(28,28,1,25,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(29,29,1,19,0,NULL,NULL,'2026-05-02 07:54:02','2026-05-19 12:15:22',NULL),(35,35,NULL,0,0,NULL,NULL,'2026-05-19 12:42:27','2026-05-19 12:42:27',NULL);
/*!40000 ALTER TABLE `stock_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subcontractor_jobs`
--

DROP TABLE IF EXISTS `subcontractor_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subcontractor_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subcontractor_id` bigint unsigned NOT NULL,
  `service_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cost` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('assigned','in_progress','done','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'assigned',
  `assigned_date` date NOT NULL,
  `completed_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subcontractor_jobs_subcontractor_id_foreign` (`subcontractor_id`),
  KEY `subcontractor_jobs_service_id_foreign` (`service_id`),
  CONSTRAINT `subcontractor_jobs_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subcontractor_jobs_subcontractor_id_foreign` FOREIGN KEY (`subcontractor_id`) REFERENCES `subcontractors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subcontractor_jobs`
--

LOCK TABLES `subcontractor_jobs` WRITE;
/*!40000 ALTER TABLE `subcontractor_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `subcontractor_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subcontractors`
--

DROP TABLE IF EXISTS `subcontractors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subcontractors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialty` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subcontractors_branch_id_foreign` (`branch_id`),
  CONSTRAINT `subcontractors_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subcontractors`
--

LOCK TABLES `subcontractors` WRITE;
/*!40000 ALTER TABLE `subcontractors` DISABLE KEYS */;
/*!40000 ALTER TABLE `subcontractors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `suppliers_name_index` (`name`),
  KEY `suppliers_phone_index` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'PT Indoparts Jaya','supplier1@example.com','0241234567',NULL,NULL,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(7,'POST Test Supplier','sup6a0cbd23c8f40@t.id','08121234567896','addr',NULL,NULL,NULL,'2026-05-19 12:42:27','2026-05-19 12:42:27',NULL);
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_rates`
--

DROP TABLE IF EXISTS `tax_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `taxname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tax` decimal(5,2) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_rates`
--

LOCK TABLES `tax_rates` WRITE;
/*!40000 ALTER TABLE `tax_rates` DISABLE KEYS */;
INSERT INTO `tax_rates` VALUES (1,'PPN 11%',11.00,NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(2,'Tanpa Pajak (0%)',0.00,NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL);
/*!40000 ALTER TABLE `tax_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `global_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tenant_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_tenant_admin` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `two_factor_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_verified_at` timestamp NULL DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `base_salary` decimal(15,2) NOT NULL DEFAULT '0.00',
  `position` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_global_id_unique` (`global_id`),
  KEY `users_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `users_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'63773025-83ab-4104-8a58-2051211696cc','Admin Bengkel','admin@bengkelpaten.id',NULL,NULL,NULL,0,1,NULL,'$2y$12$cxNN.VBvTJw7VmpnTNWA/OLcOfLPCN/cFPTFahdAqm2E27MkbB5/u',NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL,NULL,0,0.00,NULL,NULL),(2,'0cad42a8-30c5-4799-8cc8-a2db4a4b6a51','Mekanik 1','mekanik@bengkelpaten.id',NULL,NULL,NULL,0,1,NULL,'$2y$12$.qR/IYysdZpzidkUGdYmp.e5lU18p3albgkTctsTxHh0spt29mCJm',NULL,'2026-05-02 07:55:43','2026-05-02 07:55:43',NULL,NULL,0,0.00,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_brands`
--

DROP TABLE IF EXISTS `vehicle_brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_brands` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_type_id` bigint unsigned NOT NULL,
  `vehicle_brand` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicle_brands_vehicle_type_id_index` (`vehicle_type_id`),
  CONSTRAINT `vehicle_brands_vehicle_type_id_foreign` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_brands`
--

LOCK TABLES `vehicle_brands` WRITE;
/*!40000 ALTER TABLE `vehicle_brands` DISABLE KEYS */;
INSERT INTO `vehicle_brands` VALUES (1,1,'Toyota','2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(2,1,'Honda','2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(3,1,'Daihatsu','2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(4,1,'Mitsubishi','2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(5,1,'Suzuki','2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(6,1,'Nissan','2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(7,2,'Honda','2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(8,2,'Yamaha','2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(9,2,'Suzuki','2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(10,2,'Kawasaki','2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(11,2,'TVS','2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(12,3,'Hino','2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(13,3,'Isuzu','2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(14,3,'Mitsubishi','2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(15,4,'Mercedes','2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(16,5,'Toyota','2026-05-02 07:54:02','2026-05-02 07:54:02',NULL);
/*!40000 ALTER TABLE `vehicle_brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_images`
--

DROP TABLE IF EXISTS `vehicle_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_images` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_id` bigint unsigned NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caption` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicle_images_vehicle_id_index` (`vehicle_id`),
  CONSTRAINT `vehicle_images_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_images`
--

LOCK TABLES `vehicle_images` WRITE;
/*!40000 ALTER TABLE `vehicle_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `vehicle_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_types`
--

DROP TABLE IF EXISTS `vehicle_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicle_types_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_types`
--

LOCK TABLES `vehicle_types` WRITE;
/*!40000 ALTER TABLE `vehicle_types` DISABLE KEYS */;
INSERT INTO `vehicle_types` VALUES (1,'Mobil','mobil',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(2,'Motor','motor',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(3,'Truk','truk',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(4,'Bus','bus',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(5,'Pick Up','pick-up',NULL,1,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL);
/*!40000 ALTER TABLE `vehicle_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `vehicle_type_id` bigint unsigned DEFAULT NULL,
  `vehicle_brand_id` bigint unsigned DEFAULT NULL,
  `fuel_type_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `number_plate` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chassis_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `engine_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_year` int DEFAULT NULL,
  `color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `odometer` int DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicles_customer_id_index` (`customer_id`),
  KEY `vehicles_vehicle_type_id_index` (`vehicle_type_id`),
  KEY `vehicles_vehicle_brand_id_index` (`vehicle_brand_id`),
  KEY `vehicles_fuel_type_id_index` (`fuel_type_id`),
  KEY `vehicles_branch_id_index` (`branch_id`),
  CONSTRAINT `vehicles_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vehicles_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vehicles_fuel_type_id_foreign` FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vehicles_vehicle_brand_id_foreign` FOREIGN KEY (`vehicle_brand_id`) REFERENCES `vehicle_brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vehicles_vehicle_type_id_foreign` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
INSERT INTO `vehicles` VALUES (1,1,1,1,4,NULL,'H 1234 AB',NULL,NULL,'Avanza',2019,NULL,65000,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(2,2,1,3,4,NULL,'H 5678 BC',NULL,NULL,'Xenia',2020,NULL,48000,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(3,3,1,1,2,NULL,'H 9999 CD',NULL,NULL,'Fortuner',2018,NULL,82000,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(4,4,2,7,1,NULL,'H 2468 EF',NULL,NULL,'Beat Street',2021,NULL,12000,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(5,5,2,8,1,NULL,'H 1357 GH',NULL,NULL,'NMAX 155',2022,NULL,8500,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(6,1,1,2,4,NULL,'H 3579 IJ',NULL,NULL,'Brio Satya',2020,NULL,38000,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(7,3,1,4,2,NULL,'H 8642 KL',NULL,NULL,'Pajero Sport',2017,NULL,95000,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(8,2,2,9,1,NULL,'H 7531 MN',NULL,NULL,'GSX-R150',2023,NULL,5200,NULL,NULL,'2026-05-02 07:54:02','2026-05-02 07:54:02',NULL),(15,16,1,1,1,NULL,'POST 4800',NULL,NULL,'Test Model',2022,'red',1000,NULL,NULL,'2026-05-19 12:42:27','2026-05-19 12:42:27',NULL);
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `voucher_usages`
--

DROP TABLE IF EXISTS `voucher_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `voucher_usages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `voucher_id` bigint unsigned NOT NULL,
  `invoice_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `discount_applied` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `voucher_usages_voucher_id_foreign` (`voucher_id`),
  KEY `voucher_usages_invoice_id_foreign` (`invoice_id`),
  KEY `voucher_usages_customer_id_foreign` (`customer_id`),
  CONSTRAINT `voucher_usages_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `voucher_usages_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `voucher_usages_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `voucher_usages`
--

LOCK TABLES `voucher_usages` WRITE;
/*!40000 ALTER TABLE `voucher_usages` DISABLE KEYS */;
/*!40000 ALTER TABLE `voucher_usages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vouchers`
--

DROP TABLE IF EXISTS `vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vouchers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('percent','fixed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(15,2) NOT NULL,
  `min_purchase` decimal(15,2) NOT NULL DEFAULT '0.00',
  `max_discount` decimal(15,2) DEFAULT NULL,
  `usage_limit` int DEFAULT NULL,
  `used_count` int NOT NULL DEFAULT '0',
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vouchers_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vouchers`
--

LOCK TABLES `vouchers` WRITE;
/*!40000 ALTER TABLE `vouchers` DISABLE KEYS */;
INSERT INTO `vouchers` VALUES (2,'SEED50','Seed promo','percent',10.00,0.00,NULL,100,0,'2026-05-19','2026-06-18',1,NULL,'2026-05-19 12:40:04','2026-05-19 12:40:04',NULL),(3,'POST701','POST Voucher','percent',10.00,0.00,100000.00,100,0,'2026-05-19','2026-06-18',1,NULL,'2026-05-19 12:42:28','2026-05-19 12:42:28',NULL);
/*!40000 ALTER TABLE `vouchers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warranty_claims`
--

DROP TABLE IF EXISTS `warranty_claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warranty_claims` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_item_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `claim_date` date NOT NULL,
  `complaint` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('submitted','approved','rejected','resolved') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'submitted',
  `resolution` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warranty_claims_invoice_item_id_foreign` (`invoice_item_id`),
  KEY `warranty_claims_customer_id_foreign` (`customer_id`),
  CONSTRAINT `warranty_claims_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warranty_claims_invoice_item_id_foreign` FOREIGN KEY (`invoice_item_id`) REFERENCES `invoice_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warranty_claims`
--

LOCK TABLES `warranty_claims` WRITE;
/*!40000 ALTER TABLE `warranty_claims` DISABLE KEYS */;
/*!40000 ALTER TABLE `warranty_claims` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `washbays`
--

DROP TABLE IF EXISTS `washbays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `washbays` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `current_service_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `washbays_branch_id_index` (`branch_id`),
  KEY `washbays_current_service_id_index` (`current_service_id`),
  CONSTRAINT `washbays_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `washbays_current_service_id_foreign` FOREIGN KEY (`current_service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `washbays`
--

LOCK TABLES `washbays` WRITE;
/*!40000 ALTER TABLE `washbays` DISABLE KEYS */;
INSERT INTO `washbays` VALUES (1,'Bay 1 - Mobil',1,'available',NULL,'2026-05-19 10:43:02','2026-05-19 10:43:02',NULL),(2,'Bay 2 - Mobil',2,'occupied',NULL,'2026-05-19 10:43:02','2026-05-19 10:43:02',NULL),(3,'Bay 3 - Motor',3,'available',NULL,'2026-05-19 10:43:02','2026-05-19 10:43:02',NULL),(4,'Bay 4 - Motor',1,'available',NULL,'2026-05-19 10:43:02','2026-05-19 10:43:02',NULL),(5,'Cuci & Detail',2,'maintenance',NULL,'2026-05-19 10:43:02','2026-05-19 10:43:02',NULL),(6,'Tune Up Bay',3,'available',NULL,'2026-05-19 10:43:02','2026-05-19 10:43:02',NULL);
/*!40000 ALTER TABLE `washbays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'bengkel_paten'
--

--
-- Dumping routines for database 'bengkel_paten'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-21 11:11:29

