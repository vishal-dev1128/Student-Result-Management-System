-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: srms
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
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
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('admin','student','guest') NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_user_type` (`user_type`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,'admin','login','Admin user logged in successfully','127.0.0.1',NULL,'2026-03-16 03:55:01'),(2,1,'admin','database_setup','Database schema upgraded to version 2.0.0','127.0.0.1',NULL,'2026-03-16 03:55:01'),(3,1,'','Admin logged in successfully','','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-16 03:56:10'),(4,1,'','Deleted student ID: 1','','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-16 04:04:38'),(5,1,'','Deleted student ID: 2','','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-16 04:04:40'),(6,1,'','Deleted student ID: 3','','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-16 04:04:41'),(7,1,'','Created student: Ballaleshwar (ID: 4)','','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-16 04:05:32'),(8,1,'','Failed login attempt','','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-16 04:19:03'),(9,1,'','Failed login attempt','','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-16 04:20:16'),(10,1,'','Admin logged in successfully','','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-16 04:20:38'),(11,1,'','Created result for student ID: 4, exam ID: 1','','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-16 04:21:50');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_login`
--

DROP TABLE IF EXISTS `admin_login`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_login` (
  `userid` varchar(30) NOT NULL,
  `password` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_login`
--

LOCK TABLES `admin_login` WRITE;
/*!40000 ALTER TABLE `admin_login` DISABLE KEYS */;
INSERT INTO `admin_login` VALUES ('admin','123');
/*!40000 ALTER TABLE `admin_login` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','staff') DEFAULT 'admin',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `remember_token` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_users`
--

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES (1,'admin','admin@srms.local','$2y$10$Z/NwRxotWgahNKkqxqb9ZO41eBPJHzwiFqARCxe8D9uLnnDKL9EN2','System Administrator','super_admin','active',NULL,'2026-03-16 09:50:38','2026-03-16 03:55:00','2026-03-16 04:25:11');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `class`
--

DROP TABLE IF EXISTS `class`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `class` (
  `name` varchar(30) NOT NULL,
  `id` int(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class`
--

LOCK TABLES `class` WRITE;
/*!40000 ALTER TABLE `class` DISABLE KEYS */;
/*!40000 ALTER TABLE `class` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `section` varchar(10) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_status` (`status`),
  KEY `idx_academic_year` (`academic_year`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classes`
--

LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
INSERT INTO `classes` VALUES (1,'Class 10-A',NULL,'Class 10 Section A','2025-2026','active','2026-03-16 03:55:00','2026-03-16 03:55:00'),(2,'Class 10-B',NULL,'Class 10 Section B','2025-2026','active','2026-03-16 03:55:00','2026-03-16 03:55:00'),(3,'Class 12-Science',NULL,'Class 12 Science Stream','2025-2026','active','2026-03-16 03:55:00','2026-03-16 03:55:00');
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exams`
--

DROP TABLE IF EXISTS `exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_name` varchar(100) NOT NULL,
  `exam_type` enum('midterm','final','unit_test','annual') DEFAULT 'midterm',
  `academic_year` varchar(20) DEFAULT NULL,
  `exam_date` date DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','inactive','scheduled','ongoing','completed','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_academic_year` (`academic_year`),
  KEY `idx_class_id` (`class_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exams`
--

LOCK TABLES `exams` WRITE;
/*!40000 ALTER TABLE `exams` DISABLE KEYS */;
INSERT INTO `exams` VALUES (1,'First Term Examination 2025','midterm','2025-2026','2025-09-01',1,1,'2025-09-01','2025-09-15','active','2026-03-16 03:55:00','2026-03-16 04:21:12'),(2,'Annual Examination 2026','annual','2025-2026','2026-03-01',NULL,0,'2026-03-01','2026-03-20','','2026-03-16 03:55:00','2026-03-16 04:20:51');
/*!40000 ALTER TABLE `exams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faqs`
--

DROP TABLE IF EXISTS `faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faqs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(50) DEFAULT 'general',
  `display_order` int(3) DEFAULT 0,
  `is_published` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_is_published` (`is_published`),
  KEY `idx_display_order` (`display_order`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faqs`
--

LOCK TABLES `faqs` WRITE;
/*!40000 ALTER TABLE `faqs` DISABLE KEYS */;
INSERT INTO `faqs` VALUES (1,'How can I check my results?','You can check your results by entering your class and roll number on the result search page. No login is required for viewing published results.','results',1,1,'2026-03-16 03:55:01','2026-03-16 03:55:01'),(2,'What should I do if I forgot my login password?','Click on the \"Forgot Password\" link on the login page and follow the instructions to reset your password via email.','account',2,1,'2026-03-16 03:55:01','2026-03-16 03:55:01'),(3,'When will the exam results be published?','Results are typically published within 2-3 weeks after the completion of examinations. You will be notified via email and notice board.','results',3,1,'2026-03-16 03:55:01','2026-03-16 03:55:01'),(4,'How can I download my result as PDF?','Once you view your result, you will see a \"Download PDF\" button. Click on it to download your result in PDF format.','results',4,1,'2026-03-16 03:55:01','2026-03-16 03:55:01'),(5,'Who should I contact for result discrepancies?','For any discrepancies in results, please raise a support ticket through the student portal or contact the examination department directly.','support',5,1,'2026-03-16 03:55:01','2026-03-16 03:55:01');
/*!40000 ALTER TABLE `faqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade_settings`
--

DROP TABLE IF EXISTS `grade_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grade_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grade` varchar(10) NOT NULL,
  `min_percentage` decimal(5,2) NOT NULL,
  `max_percentage` decimal(5,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade_settings`
--

LOCK TABLES `grade_settings` WRITE;
/*!40000 ALTER TABLE `grade_settings` DISABLE KEYS */;
INSERT INTO `grade_settings` VALUES (1,'A+',90.00,100.00,'2026-03-16 09:27:02'),(2,'A',80.00,89.99,'2026-03-16 09:27:02'),(3,'B+',70.00,79.99,'2026-03-16 09:27:02'),(4,'B',60.00,69.99,'2026-03-16 09:27:02'),(5,'C',50.00,59.99,'2026-03-16 09:27:02'),(6,'D',40.00,49.99,'2026-03-16 09:27:02'),(7,'F',0.00,39.99,'2026-03-16 09:27:02');
/*!40000 ALTER TABLE `grade_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notices`
--

DROP TABLE IF EXISTS `notices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `target_audience` enum('all','students','staff','specific_class') DEFAULT 'all',
  `class_id` int(11) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `is_published` tinyint(1) DEFAULT 0,
  `publish_date` datetime DEFAULT NULL,
  `expiry_date` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_is_published` (`is_published`),
  KEY `idx_target_audience` (`target_audience`),
  KEY `idx_priority` (`priority`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_class_id` (`class_id`),
  CONSTRAINT `fk_notices_admin` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_notices_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notices`
--

LOCK TABLES `notices` WRITE;
/*!40000 ALTER TABLE `notices` DISABLE KEYS */;
INSERT INTO `notices` VALUES (1,'First Term Results Published','The first term examination results for all classes have been published. Students can now view their results online.','all',NULL,'high',1,'2026-03-16 03:55:00',NULL,1,'2026-03-16 03:55:00','2026-03-16 03:55:00'),(2,'Annual Day Celebration','The annual day celebration will be held on March 15, 2026. All students are requested to participate.','students',NULL,'medium',1,'2026-03-16 03:55:00',NULL,1,'2026-03-16 03:55:00','2026-03-16 03:55:00'),(3,'Fee Payment Reminder','This is a reminder to pay the pending fees before the end of this month to avoid late fees.','all',NULL,'medium',1,'2026-03-16 03:55:00',NULL,1,'2026-03-16 03:55:00','2026-03-16 03:55:00');
/*!40000 ALTER TABLE `notices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `user_type` enum('admin','student') NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_token` (`token`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `result`
--

DROP TABLE IF EXISTS `result`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `result` (
  `name` varchar(30) NOT NULL,
  `rno` int(3) NOT NULL,
  `class` varchar(30) NOT NULL,
  `p1` int(3) NOT NULL,
  `p2` int(3) NOT NULL,
  `p3` int(3) NOT NULL,
  `p4` int(3) NOT NULL,
  `p5` int(3) NOT NULL,
  `marks` int(3) NOT NULL,
  `percentage` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `result`
--

LOCK TABLES `result` WRITE;
/*!40000 ALTER TABLE `result` DISABLE KEYS */;
/*!40000 ALTER TABLE `result` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `result_summary`
--

DROP TABLE IF EXISTS `result_summary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `result_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `total_marks` decimal(7,2) NOT NULL,
  `max_marks` int(5) NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `rank` int(5) DEFAULT NULL,
  `result_status` enum('pass','fail','absent') DEFAULT 'pass',
  `is_published` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_summary` (`student_id`,`exam_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_exam_id` (`exam_id`),
  KEY `idx_is_published` (`is_published`),
  CONSTRAINT `fk_summary_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_summary_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `result_summary`
--

LOCK TABLES `result_summary` WRITE;
/*!40000 ALTER TABLE `result_summary` DISABLE KEYS */;
INSERT INTO `result_summary` VALUES (1,1,1,433.00,500,86.60,'A',NULL,'pass',1,'2026-03-16 03:55:00','2026-03-16 03:55:00'),(2,4,1,5.00,500,1.00,'F',NULL,'fail',0,'2026-03-16 04:21:50','2026-03-16 04:21:50');
/*!40000 ALTER TABLE `result_summary` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `results`
--

DROP TABLE IF EXISTS `results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `total_marks_obtained` decimal(5,2) NOT NULL,
  `total_marks` int(3) NOT NULL DEFAULT 100,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `grade` varchar(5) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_result` (`student_id`,`exam_id`,`subject_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_exam_id` (`exam_id`),
  KEY `idx_subject_id` (`subject_id`),
  KEY `idx_is_published` (`is_published`),
  CONSTRAINT `fk_results_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_results_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_results_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `results`
--

LOCK TABLES `results` WRITE;
/*!40000 ALTER TABLE `results` DISABLE KEYS */;
INSERT INTO `results` VALUES (1,1,1,1,85.00,100,85.00,'A',NULL,1,'2026-03-16 03:55:00','2026-03-16 03:55:00'),(2,1,1,2,78.00,100,78.00,'B+',NULL,1,'2026-03-16 03:55:00','2026-03-16 03:55:00'),(3,1,1,3,92.00,100,92.00,'A+',NULL,1,'2026-03-16 03:55:00','2026-03-16 03:55:00'),(4,1,1,4,88.00,100,88.00,'A',NULL,1,'2026-03-16 03:55:00','2026-03-16 03:55:00'),(5,1,1,5,90.00,100,90.00,'A+',NULL,1,'2026-03-16 03:55:00','2026-03-16 03:55:00'),(6,4,1,5,1.00,100,1.00,'F',NULL,0,'2026-03-16 04:21:50','2026-03-16 04:21:50'),(7,4,1,2,1.00,100,1.00,'F',NULL,0,'2026-03-16 04:21:50','2026-03-16 04:21:50'),(8,4,1,1,1.00,100,1.00,'F',NULL,0,'2026-03-16 04:21:50','2026-03-16 04:21:50'),(9,4,1,3,1.00,100,1.00,'F',NULL,0,'2026-03-16 04:21:50','2026-03-16 04:21:50'),(10,4,1,4,1.00,100,1.00,'F',NULL,0,'2026-03-16 04:21:50','2026-03-16 04:21:50');
/*!40000 ALTER TABLE `results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'institute_name','Student Result Management System','text','Name of the educational institute','2026-03-16 03:55:01'),(2,'institute_email','info@srms.local','text','Official email address','2026-03-16 03:55:01'),(3,'institute_phone','+91-1234567890','text','Contact phone number','2026-03-16 03:55:01'),(4,'institute_address','123 Education Street, City, State - 123456','text','Physical address','2026-03-16 03:55:01'),(5,'institute_logo','','text','Path to institute logo','2026-03-16 03:55:01'),(6,'results_per_page','20','number','Number of results to display per page','2026-03-16 03:55:01'),(7,'session_timeout','3600','number','Session timeout in seconds','2026-03-16 03:55:01'),(8,'enable_email_notifications','1','boolean','Enable/disable email notifications','2026-03-16 03:55:01'),(9,'smtp_host','','text','SMTP server host','2026-03-16 03:55:01'),(10,'smtp_port','587','number','SMTP server port','2026-03-16 03:55:01'),(11,'smtp_username','','text','SMTP username','2026-03-16 03:55:01'),(12,'smtp_password','','text','SMTP password (encrypted)','2026-03-16 03:55:01'),(13,'smtp_encryption','tls','text','SMTP encryption type (tls/ssl)','2026-03-16 03:55:01'),(14,'max_file_upload_size','2097152','number','Maximum file upload size in bytes (2MB)','2026-03-16 03:55:01'),(15,'allowed_photo_extensions','jpg,jpeg,png','text','Allowed photo file extensions','2026-03-16 03:55:01'),(16,'grading_system','percentage','text','Grading system type (percentage/letter)','2026-03-16 03:55:01'),(17,'grade_a_plus_min','90','number','Minimum percentage for A+ grade','2026-03-16 03:55:01'),(18,'grade_a_min','80','number','Minimum percentage for A grade','2026-03-16 03:55:01'),(19,'grade_b_plus_min','70','number','Minimum percentage for B+ grade','2026-03-16 03:55:01'),(20,'grade_b_min','60','number','Minimum percentage for B grade','2026-03-16 03:55:01'),(21,'grade_c_min','50','number','Minimum percentage for C grade','2026-03-16 03:55:01'),(22,'grade_d_min','40','number','Minimum percentage for D grade (Pass)','2026-03-16 03:55:01'),(23,'enable_dark_mode','1','boolean','Enable dark mode feature','2026-03-16 03:55:01'),(24,'maintenance_mode','0','boolean','Enable maintenance mode','2026-03-16 03:55:01');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `roll_number` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `class_id` int(11) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `parent_name` varchar(100) DEFAULT NULL,
  `parent_phone` varchar(20) DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `status` enum('active','inactive','graduated','transferred') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `roll_number` (`roll_number`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_class_id` (`class_id`),
  KEY `idx_status` (`status`),
  KEY `idx_name` (`full_name`),
  CONSTRAINT `fk_students_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,'2025001','Rahul Sharma','rahul.sharma@example.com','9876543210','2010-05-15','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,'male',1,NULL,NULL,'Mr. Sharma','9876543211','2023-04-01','','2026-03-16 03:55:00','2026-03-16 04:04:38'),(2,'2025002','Priya Patel','priya.patel@example.com','9876543212','2010-08-22','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,'female',1,NULL,NULL,'Mrs. Patel','9876543213','2023-04-01','','2026-03-16 03:55:00','2026-03-16 04:04:40'),(3,'2025003','Amit Kumar','amit.kumar@example.com','9876543214','2010-03-10','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,'male',2,NULL,NULL,'Mr. Kumar','9876543215','2023-04-01','','2026-03-16 03:55:00','2026-03-16 04:04:41'),(4,'01','Ballaleshwar','ballaleshwaryalamalle09@gmail.com','9965874125','2005-01-01','$2y$12$RMZ1TtRVFRCUafSRY7bC2.tmjUnlNLneoWKrZWs9Wj2EphEUkuCh.',NULL,'male',1,NULL,'Nothing','','',NULL,'active','2026-03-16 04:05:32','2026-03-16 04:05:32');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `credits` int(2) DEFAULT 0,
  `total_marks` int(3) DEFAULT 100,
  `pass_marks` int(3) DEFAULT 40,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `subject_code` (`subject_code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects`
--

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
INSERT INTO `subjects` VALUES (1,'MATH101','Mathematics',4,100,40,'active','2026-03-16 03:55:00','2026-03-16 03:55:00'),(2,'ENG101','English',3,100,40,'active','2026-03-16 03:55:00','2026-03-16 03:55:00'),(3,'SCI101','Science',4,100,40,'active','2026-03-16 03:55:00','2026-03-16 03:55:00'),(4,'SOC101','Social Studies',3,100,40,'active','2026-03-16 03:55:00','2026-03-16 03:55:00'),(5,'COMP101','Computer Science',3,100,40,'active','2026-03-16 03:55:00','2026-03-16 03:55:00');
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(20) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `category` enum('result','admission','technical','general','complaint') DEFAULT 'general',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `admin_response` text DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_number` (`ticket_number`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_assigned_to` (`assigned_to`),
  CONSTRAINT `fk_tickets_admin` FOREIGN KEY (`assigned_to`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tickets_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_tickets`
--

LOCK TABLES `support_tickets` WRITE;
/*!40000 ALTER TABLE `support_tickets` DISABLE KEYS */;
INSERT INTO `support_tickets` VALUES (1,'TKT-2026-001',1,'Rahul Sharma','rahul.sharma@example.com',NULL,'Result Discrepancy','I noticed a discrepancy in my Mathematics marks. Please review.','result','high','open',NULL,NULL,NULL,'2026-03-16 03:55:01','2026-03-16 03:55:01');
/*!40000 ALTER TABLE `support_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `vw_dashboard_stats`
--

DROP TABLE IF EXISTS `vw_dashboard_stats`;
/*!50001 DROP VIEW IF EXISTS `vw_dashboard_stats`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vw_dashboard_stats` AS SELECT
 1 AS `total_students`,
  1 AS `total_classes`,
  1 AS `completed_exams`,
  1 AS `pending_tickets`,
  1 AS `active_notices` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_student_results`
--

DROP TABLE IF EXISTS `vw_student_results`;
/*!50001 DROP VIEW IF EXISTS `vw_student_results`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vw_student_results` AS SELECT
 1 AS `student_id`,
  1 AS `exam_id`,
  1 AS `roll_number`,
  1 AS `student_name`,
  1 AS `class_name`,
  1 AS `exam_name`,
  1 AS `exam_type`,
  1 AS `exam_date`,
  1 AS `subject_name`,
  1 AS `total_marks_obtained`,
  1 AS `total_marks`,
  1 AS `percentage`,
  1 AS `grade`,
  1 AS `is_published`,
  1 AS `overall_percentage`,
  1 AS `overall_grade`,
  1 AS `result_status` */;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `vw_dashboard_stats`
--

/*!50001 DROP VIEW IF EXISTS `vw_dashboard_stats`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_dashboard_stats` AS select (select count(0) from `students` where `students`.`status` = 'active') AS `total_students`,(select count(0) from `classes` where `classes`.`status` = 'active') AS `total_classes`,(select count(0) from `exams` where `exams`.`status` = 'completed') AS `completed_exams`,(select count(0) from `support_tickets` where `support_tickets`.`status` in ('open','in_progress')) AS `pending_tickets`,(select count(0) from `notices` where `notices`.`is_published` = 1 and (`notices`.`expiry_date` is null or `notices`.`expiry_date` > current_timestamp())) AS `active_notices` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_student_results`
--

/*!50001 DROP VIEW IF EXISTS `vw_student_results`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_student_results` AS select `s`.`id` AS `student_id`,`r`.`exam_id` AS `exam_id`,`s`.`roll_number` AS `roll_number`,`s`.`full_name` AS `student_name`,`c`.`name` AS `class_name`,`e`.`exam_name` AS `exam_name`,`e`.`exam_type` AS `exam_type`,`e`.`exam_date` AS `exam_date`,`sub`.`subject_name` AS `subject_name`,`r`.`total_marks_obtained` AS `total_marks_obtained`,`r`.`total_marks` AS `total_marks`,`r`.`percentage` AS `percentage`,`r`.`grade` AS `grade`,`r`.`is_published` AS `is_published`,`rs`.`percentage` AS `overall_percentage`,`rs`.`grade` AS `overall_grade`,`rs`.`result_status` AS `result_status` from (((((`results` `r` join `students` `s` on(`r`.`student_id` = `s`.`id`)) join `classes` `c` on(`s`.`class_id` = `c`.`id`)) join `exams` `e` on(`r`.`exam_id` = `e`.`id`)) join `subjects` `sub` on(`r`.`subject_id` = `sub`.`id`)) left join `result_summary` `rs` on(`rs`.`student_id` = `s`.`id` and `rs`.`exam_id` = `e`.`id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-16 19:52:59
