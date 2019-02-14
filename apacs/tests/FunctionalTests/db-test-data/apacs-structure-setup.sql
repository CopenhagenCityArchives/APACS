
-- MySQL dump 10.13  Distrib 5.7.12, for Win32 (AMD64)
--
-- Host: phhw-140602.cust.powerhosting.dk    Database: kbharkiv
-- ------------------------------------------------------
-- Server version	5.6.23-72.1-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `apacs_entities`
--

DROP TABLE IF EXISTS `apacs_entities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `apacs_entities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `name` char(100) COLLATE utf8_danish_ci NOT NULL,
  `isPrimaryEntity` tinyint(1) DEFAULT '0',
  `entityKeyName` char(45) COLLATE utf8_danish_ci DEFAULT NULL,
  `type` char(6) COLLATE utf8_danish_ci DEFAULT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `countPerEntry` char(3) COLLATE utf8_danish_ci NOT NULL DEFAULT 'one',
  `guiName` char(50) COLLATE utf8_danish_ci NOT NULL,
  `primaryTableName` char(250) COLLATE utf8_danish_ci NOT NULL,
  `includeInSOLR` tinyint(1) NOT NULL DEFAULT '0',
  `viewOrder` int(11) NOT NULL DEFAULT '0',
  `parent_id` int(11) DEFAULT NULL,
  `dbTableName` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `fieldRelatingToParent` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `allowNewValues` tinyint(1) NOT NULL DEFAULT '1',
  `typeOfRelationToParent` char(12) COLLATE utf8_danish_ci DEFAULT 'connection',
  `saveOrderAccordingToParent` char(10) COLLATE utf8_danish_ci NOT NULL DEFAULT 'after',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `apacs_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `apacs_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) DEFAULT NULL COMMENT 'FK til entities',
  `steps_id` int(11) DEFAULT NULL,
  `datasources_id` int(11) DEFAULT NULL,
  `tableName` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  `fieldName` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  `hasDecode` tinyint(1) NOT NULL DEFAULT '0',
  `decodeTable` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
  `decodeField` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
  `codeAllowNewValue` tinyint(1) NOT NULL DEFAULT '0',
  `includeInForm` tinyint(1) NOT NULL DEFAULT '1',
  `formName` char(50) COLLATE utf8_danish_ci NOT NULL DEFAULT 'Nyt felt',
  `formFieldType` char(45) COLLATE utf8_danish_ci DEFAULT 'string',
  `formFieldOrder` int(11) DEFAULT NULL,
  `defaultValue` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
  `helpText` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `placeholder` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
  `isRequired` tinyint(1) NOT NULL DEFAULT '0',
  `validationRegularExpression` char(100) COLLATE utf8_danish_ci DEFAULT '/\\w{1,}/',
  `validationErrorMessage` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `includeInSOLR` tinyint(1) DEFAULT '1',
  `SOLRFieldName` char(45) COLLATE utf8_danish_ci DEFAULT NULL,
  `SOLRFacet` tinyint(1) NOT NULL DEFAULT '0',
  `SOLRResult` tinyint(1) NOT NULL DEFAULT '1',
  `name` char(250) COLLATE utf8_danish_ci NOT NULL DEFAULT 'no_given',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;
