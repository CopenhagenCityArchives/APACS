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

LOCK TABLES `apacs_datasources` WRITE;
/*!40000 ALTER TABLE `apacs_datasources` DISABLE KEYS */;
INSERT INTO `apacs_datasources` (`id`, `name`, `sql`, `url`, `valueField`, `includeValuesInForm`, `dbTableName`, `isPublicEditable`) VALUES (1,'positions','SELECT id, position FROM (SELECT case WHEN position LIKE \':query%\' THEN 5 + priority WHEN position = \'-\' THEN 9 ELSE priority END AS prio, id, position FROM burial_positions WHERE position LIKE \'%:query%\') a ORDER BY prio DESC, position LIMIT 75',NULL,'position',0,'burial_positions',1),(2,'deathcauses','SELECT id, deathcause, CASE WHEN deathcause LIKE \":query%\" THEN 5 + priority ELSE priority END as prio FROM burial_deathcauses WHERE deathcause LIKE \"%:query%\" ORDER BY prio DESC, deathcause LIMIT 75;',NULL,'deathcause',0,'burial_deathcauses',1),(3,'streets','SELECT id, streetAndHood FROM (SELECT case WHEN streetandhood lIKE \':query%\' THEN 5 + priority WHEN streetandhood = \'-\' THEN 9 ELSE priority END AS prio, id, streetandhood FROM burial_streets WHERE streetandhood LIKE \'%:query%\') a ORDER BY prio DESC, streetandhood LIMIT 75',NULL,'streetAndHood',0,'burial_streets',1),(4,'floors','SELECT id, floor FROM burial_floors WHERE floor LIKE\' %:query%\'',NULL,'floor',1,'burial_floors',0),(5,'civilstatuses','SELECT id, civilstatus FROM burial_civilstatuses WHERE civilstatus LIKE \'%:query%\'',NULL,'civilstatus',1,'burial_civilstatuses',0),(6,'chapels','SELECT id, chapel, CASE WHEN chapel LIKE \":query%\"  THEN 5 + priority ELSE priority END as prio FROM burial_chapels WHERE chapel LIKE \"%:query%\"  ORDER BY prio DESC, chapel LIMIT 75;',NULL,'chapel',0,'burial_chapels',1),(7,'cemetaries','SELECT id, cemetary, CASE WHEN cemetary LIKE \":query%\" THEN 5 + priority ELSE priority END as prio FROM burial_cemetaries WHERE cemetary LIKE \"%:query%\" ORDER BY prio DESC, cemetary LIMIT 75;',NULL,'cemetary',0,'burial_cemetaries',1),(8,'deathplaces','SELECT id, deathplace FROM (SELECT case WHEN deathplace LIKE \':query%\' THEN 5 + priority WHEN deathplace = \'-\' THEN 9 ELSE priority END AS prio, id, deathplace FROM burial_deathplaces WHERE deathplace LIKE \'%:query%\') a ORDER BY prio DESC, deathplace',NULL,'deathplace',0,'burial_deathplaces',1),(9,'birthplaces','SELECT id, name FROM burial_birthplaces WHERE name LIKE \'%:query%\'',NULL,'name',0,'burial_birthplaces',0),(10,'parishes','SELECT id, parish, CASE WHEN parish LIKE \":query%\" AND fromYear<1913 THEN 5 + priority ELSE priority END as prio FROM burial_parishes WHERE parish LIKE \"%:query%\" AND fromYear<1913 ORDER BY prio DESC, parish LIMIT 75;',NULL,'parish',0,'burial_parishes',1),(11,'relationtype','SELECT id, relationtype FROM burial_relationtypes WHERE relationtype LIKE \"%:query%\"',NULL,'relationtype',1,'burial_relationtypes',0),(12,'sex','SELECT id, sex FROM burial_persons_sex WHERE sex LIKE \"%:query%\"',NULL,'sex',1,'burial_persons_sex',0),(13,'institution','SELECT id, institution, CASE WHEN institution LIKE \":query%\" THEN 5 + priority ELSE priority END as prio FROM burial_institutions WHERE institution LIKE \"%:query%\" ORDER BY prio DESC, institution LIMIT 75;',NULL,'institution',0,'burial_institutions',1),(14,'workplace','SELECT id, workplace, CASE WHEN workplace LIKE \":query%\" THEN 5 + priority ELSE priority END as prio FROM burial_workplaces WHERE workplace LIKE \"%:query%\" ORDER BY prio DESC, workplace LIMIT 75;',NULL,'workplace',0,'burial_workplaces',1),(15,NULL,NULL,NULL,'',0,'',0);
/*!40000 ALTER TABLE `apacs_datasources` ENABLE KEYS */;
UNLOCK TABLES;