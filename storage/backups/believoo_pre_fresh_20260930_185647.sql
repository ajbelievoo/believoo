/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.10-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: believoo
-- ------------------------------------------------------
-- Server version	10.11.10-MariaDB-log

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
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
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
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
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
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
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
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
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
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
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
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
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
INSERT INTO `sessions` VALUES
('5wzsLgYpBw31Im5pVuAiFkaEKxP5je7VbzoFrPUG',NULL,'162.159.102.31','Mozilla/5.0 (compatible; DotBot/1.2; +https://opensiteexplorer.org/dotbot; help@moz.com)','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRzRZdWJneVp5VDBWaDF2eXFVNE5BVXA2WEZNRWVDVjlkcEdxVUJleSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTM6Imh0dHBzOi8vc3VwcG9ydC5iZWxpZXZvby5jb20vc2VydmljZXMvMjZzY2FsZWFtZDA1LXY0Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1790790943),
('62sQxnRSIKELLsRh6REP79jeo6kXD1hgDk3UDiFQ',NULL,'104.23.187.94','Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoiM09vWnJaOTJSbVFXdGdsdTg2aVRiQ0R1VG44N0RFV1B6dmFDZDF3TCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzI6Imh0dHBzOi8vc3VwcG9ydC5iZWxpZXZvby5jb20vdnBzIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1790791421),
('7h19DbzpDHRROU7IAr1FTCNwFbhKTwKEdYKREELW',NULL,'162.158.108.129','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Mobile Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZEFXeGlyc0hDOGc0YW50ME9XVk9McGg3MFdLTXR3Z1Q1a0c4eTBNSCI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo1OToiaHR0cHM6Ly9zdXBwb3J0LmJlbGlldm9vLmNvbS9jaGVja291dC9ob3N0aW5nLXBlcmZvcm1hbmNlLTEiO31zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozNDoiaHR0cHM6Ly9zdXBwb3J0LmJlbGlldm9vLmNvbS9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1790793276),
('8rhRFouXEhMciXA9u3DcBYjVdUOV2DihtJB31X2a',NULL,'172.70.208.153','curl/7.81.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiSG5vbUFkZnJqR05KWDhQN25jV3FEc1RRQ3ZraGVKYmIyR3kxa2VvTiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vYmMuYmVsaWV2b28uY29tL2xvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1790791551),
('8YQCLmzqPI4jrYCgPANwIq1spvDbIMAbH8HessJr',NULL,'104.22.31.108','Mozilla/5.0 (compatible; DotBot/1.2; +https://opensiteexplorer.org/dotbot; help@moz.com)','YTo0OntzOjY6Il90b2tlbiI7czo0MDoid3Q4b2RuTGVMaE14THZLRW02UEN2c1h0bDV0MzJlazZneXEwcW54ZiI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo0ODoiaHR0cHM6Ly9iZWxpZXZvby5jb20vY2hlY2tvdXQvMjZhZHYwNS12Mi9kZWZhdWx0Ijt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDg6Imh0dHBzOi8vYmVsaWV2b28uY29tL2NoZWNrb3V0LzI2YWR2MDUtdjIvZGVmYXVsdCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1790793438),
('ahclh3Fbue8RDsYz4fGexg8F4BwPrjI2ZN7iaz3c',NULL,'172.71.22.22','Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm) Chrome/116.0.1938.76 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQWJGMjRaaHN2QWlNcUxZOERDdVJTQUZnNHdtNzlOdXNlVEZNTzJFdiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vd3d3LmJlbGlldm9vLmNvbS9ibG9nIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1790790919),
('AJNcS2wypfCOB1TLT17sdHeD7xDW1tjspQnZkEp1',NULL,'172.70.142.53','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Mobile Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSEhOU09lMXZkWWE1S1dQMWdqR2FyZ2dKRVJab2I1WHk3bVk1RHhpVCI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo1NjoiaHR0cHM6Ly9zdXBwb3J0LmJlbGlldm9vLmNvbS9jaGVja291dC8yNmFkdjAzLXY3L2RlZmF1bHQiO31zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozNDoiaHR0cHM6Ly9zdXBwb3J0LmJlbGlldm9vLmNvbS9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1790791539),
('bjfvvr7s88ij5BTAyUPv8uqmrPkPkoZty5xYE69U',NULL,'172.64.198.122','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiVzdua2twS29sZ1I0bkp4UUo2Q0tOampVV0FZREtBUUlZVWJXTTN1byI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vYmVsaWV2b28uY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1790791289),
('edipHZKsXcyZmKguArO9hHwHP35HbEqhC0jeOfY4',NULL,'162.158.22.78','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','YToyOntzOjY6Il90b2tlbiI7czo0MDoiWU1acE5rVnk1ZFZyNmF1cVpyZnN0cVRneVppZjRuS2RXMThzWHZDcCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1790791709),
('eLaCihEnREuhyq9pG2kvPV5Ix4kDwrUyNkQ7griR',NULL,'104.23.172.115','Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoidkRWME05OHFLcmpjV0hGaDVsbGhpNDlpTUQ2MDhLeER3RG4xdXBrUSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjQ6Imh0dHBzOi8vd3d3LmJlbGlldm9vLmNvbSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1790791472),
('HXW1vRiXGR1JgGx7SgwUvw5xEOKOeYCw6bLlcZ2B',NULL,'104.23.248.92','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.6167.57 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoid0RzajBRZ1I1SzRSVll5SXh2eEtBckt0NHFSTVRSTzJoN21uTlp2SyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vYmVsaWV2b28uY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1790793131),
('IjxAGG978PrykptELOtmOR00nxVEUR2rdSAKshuL',NULL,'172.71.124.52','curl/7.81.0','YToyOntzOjY6Il90b2tlbiI7czo0MDoieUYyVjdiTzMzM3pxdWQ4M0FCb3pIZGdVWXBHeTRxMXVsR254eEZvbSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1790791546),
('k9cK4UT7hABd7jGloxj05T7qmAVXNkd2MhCXpY8E',NULL,'104.22.31.108','Mozilla/5.0 (compatible; DotBot/1.2; +https://opensiteexplorer.org/dotbot; help@moz.com)','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSkc5SjloNTg1Tk9XMkFnSDFTWmY3VlA5dWRKQ2NMamJRb0NoMlRBSiI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo0NToiaHR0cHM6Ly9iZWxpZXZvby5jb20vY2hlY2tvdXQvMjZzY2FsZWFtZDA5LXYzIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDU6Imh0dHBzOi8vYmVsaWV2b28uY29tL2NoZWNrb3V0LzI2c2NhbGVhbWQwOS12MyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1790790927),
('l6yz02aNcdQta6p2dNEi8Smpz7iUNwcmcrcgCL84',NULL,'172.68.225.20','Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiRm1OekhmNGhsSnRWbXlRVHFkdVcxYWtCTU1PVUFSVTlLamp6R1I0cCI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo0NToiaHR0cHM6Ly9zdXBwb3J0LmJlbGlldm9vLmNvbS9jbGllbnQvZGFzaGJvYXJkIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzQ6Imh0dHBzOi8vc3VwcG9ydC5iZWxpZXZvby5jb20vbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1790790685),
('MAjh0Al87wtfQqe8r5krAcfwXBOw1F6klMB3apYu',NULL,'162.158.88.85','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Mobile Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiQjNlaFJpem9TcmxYR0VyQmhyeEhLRGVuUXF0OXFyaFVjbFl0elBPRSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo1NzoiaHR0cHM6Ly9zdXBwb3J0LmJlbGlldm9vLmNvbS9jaGVja291dC8yNGhjaS1pMi12Mi9kZWZhdWx0Ijt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzQ6Imh0dHBzOi8vc3VwcG9ydC5iZWxpZXZvby5jb20vbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1790793143),
('MjV92znuhC8BOE1vWps60vKUJK9HfBtx4TsOwX10',NULL,'172.71.198.140','Go-http-client/2.0','YToyOntzOjY6Il90b2tlbiI7czo0MDoiZnN2anBqb0FSMjI2ajVOZ1dHbmxnaGJDeXJxS1p4eFl4dW56MDlYZSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1790792744),
('ota51dQaHUbtj92GfsFX1l8n1MAr94SaMfF1yE1z',NULL,'172.71.182.222','Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRnNVbHpzQU1sZngxQkVMV2dtaGQ1S3ZiSzhBaWVCN2pFd0lnQmhCVSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDE6Imh0dHBzOi8vc3VwcG9ydC5iZWxpZXZvby5jb20vc2VydmljZXMvdnBzIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1790793150),
('PXAoEH0pmH3ceRxYPbpc8BoQUKSx0nGZ7bZ76gsZ',NULL,'104.22.17.153','Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoic0wwYjBsc2Z1Q1pIc1Z4REUwYmpsRkZWM2FzZldXN3Z0Q09xRXlKZSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MTg4OiJodHRwczovL3N1cHBvcnQuYmVsaWV2b28uY29tL2RvbWFpbnMvcmVnaXN0ZXI/ZG9tYWluPSUyNCU3QmVuY29kZVVSSUNvbXBvbmVudCUyOGRvbWFpbiUyOSU3RCZwcmljZT0lMjQlN0JwcmljaW5nLnByaWNlJTdEJnByb3ZpZGVyPSUyNCU3QmVuY29kZVVSSUNvbXBvbmVudCUyOGJlc3RPcHRpb24ucHJvdmlkZXJfY29kZSUyOSU3RCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1790791962),
('r6BhfRdB7cBCjcPlrUGfKNQiKjPa8uNlokGjIh40',NULL,'172.68.164.55','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSlhWQU84bGJJTjk5MVpLd0pmZmRNQm5MM2psSU8zZW9IckpndER0TCI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo2MToiaHR0cHM6Ly9zdXBwb3J0LmJlbGlldm9vLmNvbS9jaGVja291dC8yNnNjYWxlYW1kMDEtdjQvZGVmYXVsdCI7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjM0OiJodHRwczovL3N1cHBvcnQuYmVsaWV2b28uY29tL2xvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1790792322),
('rhFcT4eKomjSRSRlLuFpeTtAYT3fZsOh5lboVo2x',NULL,'162.158.88.158','curl/7.81.0','YToyOntzOjY6Il90b2tlbiI7czo0MDoiaEFld2U2ODRRUG1NdzFEN2lMMXVyWmkzVmhQakRrSmhCMVBjU1cwayI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1790791543),
('SGSuPMgivHUMnhX84vMI5bUGUwuJVdbxCQUSKuOo',NULL,'104.22.31.24','Mozilla/5.0 (compatible; DotBot/1.2; +https://opensiteexplorer.org/dotbot; help@moz.com)','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiRFlZb1RNaEZmV29aY1pFWExGTG5qUGN6bHFFWVc0amw1R0dHMXExSyI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo2NDoiaHR0cHM6Ly9zdXBwb3J0LmJlbGlldm9vLmNvbS9jaGVja291dC92cHMtZWxpdGUtOC0xNi0xNjAvZGVmYXVsdCI7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjY0OiJodHRwczovL3N1cHBvcnQuYmVsaWV2b28uY29tL2NoZWNrb3V0L3Zwcy1lbGl0ZS04LTE2LTE2MC9kZWZhdWx0Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1790793475),
('snMP2Tkh3WhEdMmUJ5zZOqVt4ufIZOj500AMMIsA',NULL,'162.159.102.31','Mozilla/5.0 (compatible; DotBot/1.2; +https://opensiteexplorer.org/dotbot; help@moz.com)','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMHgzVGRISldkTGhWSTZBdDk0UnR6NTVkVUxtM0p5NHFBaWVzeHJ0UyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTI6Imh0dHBzOi8vc3VwcG9ydC5iZWxpZXZvby5jb20vc2VydmljZXMvMjRhZHZzdG9yMDEtdjMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1790793418),
('Ti5jcbD97zMhnzo7Bp5dTez17SCsC37VTZhDrlgL',NULL,'162.159.102.152','Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)','YTozOntzOjY6Il90b2tlbiI7czo0MDoidGt1bE0wTWlOS1FmZ1JGMjhrN29SMFVsNlFDQWVwajFab3FIUnpKRSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzU6Imh0dHBzOi8vYmMuYmVsaWV2b28uY29tL3NpdGVtYXAueG1sIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1790793357),
('TPVfI9gSFDdRLWmG9e6LudP8zzA9W1jHTpjKeugP',NULL,'162.159.102.83','Mozilla/5.0 (compatible; DotBot/1.2; +https://opensiteexplorer.org/dotbot; help@moz.com)','YTo0OntzOjY6Il90b2tlbiI7czo0MDoibHhWcFZ3bmo4RXdOeHVzZ0p6SnJYN0ZKb1VxeXNGWXlhQzNkRnl0NyI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo0NzoiaHR0cHM6Ly9iZWxpZXZvby5jb20vY2hlY2tvdXQvdnBzLWVsaXRlLTgtOC0zMjAiO31zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo0NzoiaHR0cHM6Ly9iZWxpZXZvby5jb20vY2hlY2tvdXQvdnBzLWVsaXRlLTgtOC0zMjAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1790793388),
('ucR42BqitOkaKht9yXoqhmOXJaB0LsNs1egby0Kd',NULL,'172.71.152.44','curl/7.81.0','YToyOntzOjY6Il90b2tlbiI7czo0MDoieXdJV3lDZTgzYTdvdXFwNkM4ZkxNRFZkWjF3S3Bxem85bUNrYkZ3cSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1790791545),
('VSJUxLYfAjluyZsBGmeIobxA4UDG8PtaFxQfqADd',NULL,'172.70.160.246','Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiSkw4WTVJWWpFSExwb3F1UG5hTVR4ZmVsOWR0aGNzdWE5anRPMFJHQyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHBzOi8vc3VwcG9ydC5iZWxpZXZvby5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1790792219),
('YQhVJQe54X85ZzG6wgd7hDqZ7tKqnHlYWGh7BoRq',NULL,'172.68.19.228','Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoiazN1UkxTUVFIZGNPTzVXSXFRRXlLRzZIZmdXS3VaRGlxaVk0UVhMViI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NzE6Imh0dHBzOi8vc3VwcG9ydC5iZWxpZXZvby5jb20vbG9naW4/cmVkaXJlY3Q9JTI0JTdCZW5jb2RlVVJJQ29tcG9uZW50JTI4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1790792576);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vm_migrations`
--

DROP TABLE IF EXISTS `vm_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vm_migrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `proxmox_vm_id` bigint(20) unsigned NOT NULL,
  `source_node` varchar(255) NOT NULL,
  `target_node` varchar(255) NOT NULL,
  `vmid` int(11) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `progress_percent` int(11) NOT NULL DEFAULT 0,
  `status_message` varchar(255) DEFAULT NULL,
  `proxmox_upid` varchar(255) DEFAULT NULL,
  `task_status` varchar(255) DEFAULT NULL,
  `is_live_migration` tinyint(1) NOT NULL DEFAULT 1,
  `total_bytes_transferred` bigint(20) DEFAULT NULL,
  `bytes_remaining` bigint(20) DEFAULT NULL,
  `migration_type` varchar(255) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT NULL,
  `error_log` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vm_migrations_user_id_foreign` (`user_id`),
  CONSTRAINT `vm_migrations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vm_migrations`
--

LOCK TABLES `vm_migrations` WRITE;
/*!40000 ALTER TABLE `vm_migrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `vm_migrations` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-30 18:56:47
