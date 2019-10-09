
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
-- Table structure for table `apacs_datasources`
--

DROP TABLE IF EXISTS `apacs_datasources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `apacs_datasources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(25) COLLATE utf8_danish_ci DEFAULT NULL,
  `sql` text COLLATE utf8_danish_ci,
  `url` varchar(255) COLLATE utf8_danish_ci DEFAULT NULL,
  `valueField` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  `includeValuesInForm` tinyint(1) NOT NULL DEFAULT '0',
  `dbTableName` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  `isPublicEditable` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `apacs_steps`
--

DROP TABLE IF EXISTS `apacs_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `apacs_steps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(50) COLLATE utf8_danish_ci DEFAULT NULL,
  `description` text COLLATE utf8_danish_ci,
  `tasks_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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

DROP TABLE IF EXISTS `apacs_entries`;
CREATE TABLE `apacs_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `posts_id` int(11) NOT NULL,
  `tasks_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  `concrete_entries_id` int(11) DEFAULT NULL,
  `complete` tinyint(1) NOT NULL DEFAULT '0',
  `last_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `test` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `posts_id` (`posts_id`),
  KEY `tasks_id` (`tasks_id`),
  KEY `users_id` (`users_id`),
  KEY `concrete_entries_id` (`concrete_entries_id`)
) ENGINE=InnoDB AUTO_INCREMENT=231705 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

DROP TABLE IF EXISTS `apacs_posts`;
CREATE TABLE `apacs_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pages_id` int(11) DEFAULT NULL,
  `width` decimal(21,18) DEFAULT NULL,
  `height` decimal(21,18) DEFAULT NULL,
  `x` decimal(21,18) DEFAULT NULL,
  `y` decimal(21,18) DEFAULT NULL,
  `complete` tinyint(1) NOT NULL DEFAULT '0',
  `image` blob,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_posts.pages_id_to_pages.id_idx` (`pages_id`)
) ENGINE=InnoDB AUTO_INCREMENT=237867 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

DROP TABLE IF EXISTS `apacs_pages`;
CREATE TABLE `apacs_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `volume_id` int(11) DEFAULT NULL,
  `unit_id` int(11) NOT NULL DEFAULT '0',
  `page_number` int(11) NOT NULL DEFAULT '1',
  `starbas_id` int(11) DEFAULT NULL,
  `filename` varchar(255) COLLATE utf8_danish_ci DEFAULT NULL,
  `filename_converted` varchar(255) COLLATE utf8_danish_ci DEFAULT NULL,
  `relative_filename` varchar(255) COLLATE utf8_danish_ci DEFAULT NULL,
  `relative_filename_converted` varchar(255) COLLATE utf8_danish_ci DEFAULT NULL,
  `found` varchar(255) COLLATE utf8_danish_ci DEFAULT NULL,
  `image_url` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `md5` char(50) COLLATE utf8_danish_ci DEFAULT NULL,
  `s3` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `volume_id` (`volume_id`),
  KEY `unit_id` (`unit_id`),
  KEY `starbas_id` (`starbas_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1605091 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

DROP TABLE IF EXISTS `apacs_units`;
CREATE TABLE `apacs_units` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Collection_ids over 100 stemmer overens med starbas'' bestillingsenhedsid.For collection_ids under 100, er id''erne under starbas id, og her er der ikke overensstemmelse.',
  `collections_id` int(11) DEFAULT NULL,
  `pages` int(11) NOT NULL DEFAULT '0',
  `starbas_id` int(11) unsigned DEFAULT NULL,
  `description` char(100) COLLATE utf8_danish_ci NOT NULL,
  `former_id` int(11) DEFAULT NULL,
  `level1_value` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `level1_order` int(11) DEFAULT NULL,
  `level2_value` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `level2_order` int(11) DEFAULT NULL,
  `level3_value` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `level3_order` int(11) DEFAULT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_public` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_units.collections_id_to_collections.id_idx` (`collections_id`),
  KEY `level_value` (`level1_value`,`level2_value`,`level3_value`),
  KEY `level_order` (`level1_order`,`level2_order`,`level3_order`),
  CONSTRAINT `FK_units.collections_id_to_collections.id` FOREIGN KEY (`collections_id`) REFERENCES `apacs_collections` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1274698 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci COMMENT='Used to make distinct queries for values faster';

DROP TABLE IF EXISTS `apacs_collections`;
CREATE TABLE `apacs_collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(250) COLLATE utf8_danish_ci NOT NULL,
  `description` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `url` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `concreteImagesTableName` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `info` text COLLATE utf8_danish_ci,
  `status` int(11) NOT NULL DEFAULT '0',
  `num_of_filters` int(11) DEFAULT '0',
  `level1_name` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
  `level1_info` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `level1_example_value` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
  `level2_name` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
  `level2_info` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `level2_example_value` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
  `level3_name` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
  `level3_info` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `level3_example_value` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
  `link` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `link_text` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `link_mouse_over` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_public` timestamp NULL DEFAULT NULL,
  `image_url` char(100) COLLATE utf8_danish_ci DEFAULT 'http://www.kbhkilder.dk',
  `solr_base_query` text COLLATE utf8_danish_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

DROP TABLE IF EXISTS `apacs_tasks`;
CREATE TABLE `apacs_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_danish_ci NOT NULL,
  `description` varchar(250) COLLATE utf8_danish_ci NOT NULL,
  `collection_id` int(11) NOT NULL,
  `primaryEntity_id` varchar(45) COLLATE utf8_danish_ci DEFAULT NULL,
  `serializedConf` longblob,
  PRIMARY KEY (`id`),
  KEY `FK_tasks.collection_id_to_collections.id_idx` (`collection_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

DROP TABLE IF EXISTS `apacs_tasks_units`;
CREATE TABLE `apacs_tasks_units` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tasks_id` int(11) NOT NULL,
  `units_id` int(11) NOT NULL,
  `pages_done` int(11) NOT NULL DEFAULT '0',
  `columns` int(11) NOT NULL DEFAULT '1',
  `rows` int(11) NOT NULL DEFAULT '1',
  `index_active` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `FK_tasks_units.task_id_to_tasks.id_idx` (`tasks_id`),
  KEY `FK_tasks_units.unit_id_idx` (`units_id`),
  CONSTRAINT `FK_tasks_units.task_id_to_tasks.id` FOREIGN KEY (`tasks_id`) REFERENCES `apacs_tasks` (`id`),
  CONSTRAINT `FK_tasks_units.unit_id` FOREIGN KEY (`units_id`) REFERENCES `apacs_units` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=416 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

DROP TABLE IF EXISTS `apacs_tasks_pages`;
CREATE TABLE `apacs_tasks_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tasks_id` int(11) NOT NULL,
  `pages_id` int(11) NOT NULL,
  `units_id` int(11) DEFAULT NULL,
  `is_done` tinyint(1) NOT NULL DEFAULT '0',
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_task_pages.task_id_to_task.id_idx` (`tasks_id`),
  KEY `FK_tasks_pages.pages_id_to_pages.id_idx` (`pages_id`),
  CONSTRAINT `FK_task_pages.task_id_to_task.id` FOREIGN KEY (`tasks_id`) REFERENCES `apacs_tasks` (`id`),
  CONSTRAINT `FK_tasks_pages.pages_id_to_pages.id` FOREIGN KEY (`pages_id`) REFERENCES `apacs_pages` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=132076 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;


DROP TABLE IF EXISTS `apacs_users`;
CREATE TABLE `apacs_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(45) COLLATE utf8_danish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1613 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

DROP TABLE IF EXISTS `apacs_errorreports`;
CREATE TABLE `apacs_errorreports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tasks_id` int(11) DEFAULT NULL,
  `posts_id` int(11) NOT NULL,
  `pages_id` int(11) DEFAULT NULL,
  `entities_id` int(11) DEFAULT NULL,
  `entity_name` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `field_name` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `entity_position` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `users_id` int(11) DEFAULT NULL,
  `reporting_users_id` int(11) DEFAULT NULL,
  `comment` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `concrete_entries_id` int(11) DEFAULT NULL,
  `original_value` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `last_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `toSuperUser` tinyint(1) DEFAULT '0',
  `superUserTime` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `field_id` int(11) DEFAULT NULL,
  `entry_created_by` char(150) COLLATE utf8_danish_ci DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_reason` char(50) COLLATE utf8_danish_ci DEFAULT NULL,
  `entries_id` int(11) DEFAULT NULL,
  `collection_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `apacs_pages_idx` (`pages_id`)
) ENGINE=InnoDB AUTO_INCREMENT=110030 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `apacs_superusers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tasks_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `users_id` (`users_id`),
  KEY `tasks_id` (`tasks_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `apacs_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` char(60) COLLATE utf8_danish_ci DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` char(150) COLLATE utf8_danish_ci DEFAULT NULL,
  `expires` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `token` (`token`),
  KEY `expires` (`expires`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `apacs_exceptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` char(100) COLLATE utf8_danish_ci NOT NULL,
  `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `details` text COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `time` (`time`)
) ENGINE=InnoDB AUTO_INCREMENT=4601 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `apacs_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `datasource_id` int(11) DEFAULT NULL,
  `collections_id` int(11),
  `tasks_id` int(11),
  `units_id` int(11),
  `pages_id` int(11),
  `posts_id` int(11),
  `event_type` char(45) NOT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `backup` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tmestamp` (`timestamp`),
  KEY `apacs_pages_idx` (`pages_id`)
) ENGINE=InnoDB AUTO_INCREMENT=509682 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `apacs_datalist_events`;
CREATE TABLE `apacs_datalist_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `datasource_id` int(11) NOT NULL,
  `event_type` char(45) NOT NULL,
  `old_value` char(45) DEFAULT NULL,
  `new_value` char(45) NOT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tmestamp` (`timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=509682 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;