-- MySQL dump 10.17  Distrib 10.3.15-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: tdems
-- ------------------------------------------------------
-- Server version	10.3.15-MariaDB

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
-- Table structure for table `asset`
--

DROP TABLE IF EXISTS `asset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset` (
  `asset_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `equip_barcode` varchar(20) NOT NULL COMMENT '설비바코드',
  `rack_location` varchar(100) DEFAULT NULL COMMENT '랙 위치',
  `mounted_location` varchar(100) DEFAULT NULL COMMENT '장착 위치',
  `hostname` varchar(255) DEFAULT NULL COMMENT '호스트명',
  `ip` varchar(255) DEFAULT NULL COMMENT '장비 IP',
  `asset_type` varchar(50) NOT NULL DEFAULT '' COMMENT '장비 종류',
  `ma` varchar(1) DEFAULT NULL COMMENT 'MA',
  `status` varchar(10) DEFAULT NULL COMMENT '상태',
  `purpose` varchar(50) DEFAULT NULL COMMENT '용도',
  `purpose_detail` varchar(100) DEFAULT NULL COMMENT '상세용도',
  `facility_status` varchar(50) DEFAULT NULL COMMENT '설비상태',
  `own_team` varchar(100) DEFAULT '' COMMENT '자산보유팀',
  `standard_service` varchar(100) DEFAULT '' COMMENT '표준서비스',
  `unit_service` varchar(100) DEFAULT '' COMMENT '단위서비스',
  `manufacturer` varchar(100) DEFAULT NULL COMMENT '장비 제조사',
  `model_name` varchar(150) DEFAULT NULL COMMENT '장비 모델명',
  `serial_number` varchar(150) DEFAULT NULL COMMENT '장비 시리얼',
  `receipt_ym` varchar(7) DEFAULT NULL COMMENT '입고시기',
  `os` varchar(100) NOT NULL DEFAULT '' COMMENT 'OS 정보',
  `cpu_type` varchar(100) NOT NULL DEFAULT '' COMMENT 'CPU 종류',
  `cpu_qty` int(11) NOT NULL DEFAULT 0 COMMENT 'CPU 수량',
  `cpu_core` int(11) NOT NULL DEFAULT 0 COMMENT 'CPU 코어',
  `swap_size` varchar(100) NOT NULL DEFAULT '' COMMENT 'SWAP',
  `created_ip` varchar(45) DEFAULT NULL COMMENT '최초 등록자 IP',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '등록일',
  `updated_ip` varchar(45) DEFAULT NULL COMMENT '최종 수정자 IP',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일',
  `del_yn` char(1) NOT NULL DEFAULT 'N' COMMENT '삭제 구분 (Y:삭제)',
  `deleted_at` datetime DEFAULT NULL COMMENT '삭제일',
  `deleted_reason` varchar(255) DEFAULT NULL COMMENT '삭제 사유',
  `asset_history` longtext DEFAULT NULL COMMENT '자산 이력',
  `hostname_active` varchar(255) GENERATED ALWAYS AS (case when `del_yn` = 'N' then `hostname` else NULL end) STORED,
  `equip_barcode_active` varchar(255) GENERATED ALWAYS AS (case when `del_yn` = 'N' then `equip_barcode` else NULL end) STORED,
  PRIMARY KEY (`asset_id`),
  UNIQUE KEY `uq_asset_equip_barcode` (`equip_barcode`),
  UNIQUE KEY `uq_asset_hostname_active` (`hostname_active`),
  UNIQUE KEY `uq_asset_barcode_active` (`equip_barcode_active`),
  KEY `idx_asset_del_yn` (`del_yn`),
  KEY `idx_asset_deleted_at` (`deleted_at`),
  KEY `idx_asset_maker` (`manufacturer`),
  KEY `idx_asset_model` (`model_name`),
  KEY `idx_asset_serial` (`serial_number`),
  KEY `idx_asset_rack_mounted` (`rack_location`,`mounted_location`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset`
--

LOCK TABLES `asset` WRITE;
/*!40000 ALTER TABLE `asset` DISABLE KEYS */;
INSERT INTO `asset` VALUES (1,'K918543300000001','AF03','ALL','kcube_dm','172.21.10.102','서버','O','ON','DB','무선분석(품질) 메인 DB 서버','운용','OSS데이터혁신팀','지능형네트워크통합관제','지능형네트워크통합관제','IBM','E870(9119-MME)','0284B4B47','2015-10','AIX 6.1','PowerPC_POWER8 4190MHz',14,1,'16','172.21.100.28','2025-08-19 22:45:40','172.21.100.28','2025-08-19 15:35:58','N',NULL,NULL,'[2015년 10월 8일]\r\n입고(드림인텍 길이훈 이사), IT부문 → OSS사업팀 자산이관 예정','kcube_dm','K918543300000001');
/*!40000 ALTER TABLE `asset` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_hdd`
--

DROP TABLE IF EXISTS `asset_hdd`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_hdd` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equip_barcode` varchar(20) NOT NULL,
  `capacity` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_asset_hdd_asset` (`equip_barcode`),
  CONSTRAINT `fk_asset_hdd_asset` FOREIGN KEY (`equip_barcode`) REFERENCES `asset` (`equip_barcode`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_hdd`
--

LOCK TABLES `asset_hdd` WRITE;
/*!40000 ALTER TABLE `asset_hdd` DISABLE KEYS */;
INSERT INTO `asset_hdd` VALUES (9,'K918543300000001','146GB',4);
/*!40000 ALTER TABLE `asset_hdd` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_memory`
--

DROP TABLE IF EXISTS `asset_memory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_memory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equip_barcode` varchar(20) NOT NULL,
  `capacity` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_asset_memory_asset` (`equip_barcode`),
  CONSTRAINT `fk_asset_memory_asset` FOREIGN KEY (`equip_barcode`) REFERENCES `asset` (`equip_barcode`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_memory`
--

LOCK TABLES `asset_memory` WRITE;
/*!40000 ALTER TABLE `asset_memory` DISABLE KEYS */;
INSERT INTO `asset_memory` VALUES (7,'K918543300000001','16',16);
/*!40000 ALTER TABLE `asset_memory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_ssd`
--

DROP TABLE IF EXISTS `asset_ssd`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_ssd` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equip_barcode` varchar(20) NOT NULL,
  `capacity` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_asset_ssd_asset` (`equip_barcode`),
  CONSTRAINT `fk_asset_ssd_asset` FOREIGN KEY (`equip_barcode`) REFERENCES `asset` (`equip_barcode`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_ssd`
--

LOCK TABLES `asset_ssd` WRITE;
/*!40000 ALTER TABLE `asset_ssd` DISABLE KEYS */;
/*!40000 ALTER TABLE `asset_ssd` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-20  1:01:22
