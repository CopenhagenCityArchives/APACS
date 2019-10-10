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


DROP TABLE IF EXISTS `burial_chapels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `burial_chapels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chapel` char(100) DEFAULT NULL,
  `priority` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `chapel_UNIQUE` (`chapel`)
) ENGINE=InnoDB AUTO_INCREMENT=325 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;