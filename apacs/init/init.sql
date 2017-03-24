CREATE TABLE `apacs_collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(250) COLLATE utf8_danish_ci NOT NULL,
  `info` text COLLATE utf8_danish_ci NOT NULL,
  `level1_name` char(100) COLLATE utf8_danish_ci NOT NULL,
  `level1_info` char(250) COLLATE utf8_danish_ci NOT NULL,
  `level2_name` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
  `level2_info` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `level3_name` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
  `level3_info` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `link` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `link_text` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `link_mouse_over` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_public` timestamp NULL DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;


CREATE TABLE IF NOT EXISTS `apacs_datasources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(25) COLLATE utf8_danish_ci DEFAULT NULL,
  `sql` char(255) COLLATE utf8_danish_ci DEFAULT NULL,
  `values` char(255) COLLATE utf8_danish_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8_danish_ci DEFAULT NULL,
  `valueField` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE IF NOT EXISTS `apacs_entities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `name` char(20) COLLATE utf8_danish_ci NOT NULL,
  `isPrimaryEntity` tinyint(1) DEFAULT '0',
  `entityKeyName` char(45) COLLATE utf8_danish_ci DEFAULT NULL,
  `type` char(6) COLLATE utf8_danish_ci DEFAULT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `countPerEntry` char(3) COLLATE utf8_danish_ci NOT NULL DEFAULT 'one',
  `guiName` char(50) COLLATE utf8_danish_ci NOT NULL,
  `primaryTableName` char(250) COLLATE utf8_danish_ci NOT NULL,
  `includeInSOLR` tinyint(1) NOT NULL DEFAULT '0',
  `viewOrder` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE IF NOT EXISTS `apacs_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `posts_id` int(11) NOT NULL,
  `tasks_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  `concrete_entries_id` varchar(45) COLLATE utf8_danish_ci DEFAULT NULL,
  `complete` tinyint(1) NOT NULL DEFAULT '0',
  `last_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE IF NOT EXISTS `apacs_errorreports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tasks_id` int(11) NOT NULL,
  `posts_id` int(11) NOT NULL,
  `pages_id` int(11) NOT NULL,
  `entity_name` char(250) COLLATE utf8_danish_ci NOT NULL,
  `field_name` char(250) COLLATE utf8_danish_ci NOT NULL,
  `users_id` int(11) NOT NULL,
  `reporting_users_id` int(11) DEFAULT NULL,
  `comment` char(250) COLLATE utf8_danish_ci NOT NULL,
  `concrete_entries_id` int(11) DEFAULT NULL,
  `original_value` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  `last_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `toSuperUser` tinyint(1) DEFAULT '0',
  `superUserTime` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `apacs_errorreportscol` varchar(45) COLLATE utf8_danish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE IF NOT EXISTS `apacs_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `collections_id` int(11) NOT NULL,
  `tasks_id` int(11) NOT NULL,
  `units_id` int(11) NOT NULL,
  `pages_id` int(11) NOT NULL,
  `posts_id` int(11) NOT NULL,
  `event_type` varchar(45) NOT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `apacs_fields` (
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
  `helpText` char(150) COLLATE utf8_danish_ci DEFAULT NULL,
  `placeholder` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
  `isRequired` tinyint(1) NOT NULL DEFAULT '0',
  `validationRegularExpression` char(100) COLLATE utf8_danish_ci DEFAULT '/\\w{1,}/',
  `validationErrorMessage` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
  `includeInSOLR` tinyint(1) DEFAULT '1',
  `SOLRFieldName` char(45) COLLATE utf8_danish_ci DEFAULT NULL,
  `SOLRFacet` tinyint(1) NOT NULL DEFAULT '0',
  `SOLRResult` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE IF NOT EXISTS `apacs_filterlevels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filter_id` int(11) NOT NULL,
  `name` char(50) COLLATE utf8_danish_ci NOT NULL,
  `gui_name` char(50) COLLATE utf8_danish_ci NOT NULL,
  `gui_hide_name` tinyint(1) NOT NULL DEFAULT '0',
  `gui_type` char(50) COLLATE utf8_danish_ci NOT NULL,
  `data_sql` char(250) COLLATE utf8_danish_ci NOT NULL,
  `data` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
  `searchable` tinyint(1) NOT NULL DEFAULT '1',
  `required_levels` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `order` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE IF NOT EXISTS `apacs_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unit_id` int(11) NOT NULL DEFAULT '1',
  `page_number` int(11) NOT NULL DEFAULT '0',
  `image_url` char(250) COLLATE utf8_danish_ci NOT NULL,
  `former_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=131071 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE IF NOT EXISTS `apacs_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pages_id` int(11) DEFAULT NULL,
  `width` decimal(21,18) DEFAULT NULL,
  `height` decimal(21,18) DEFAULT NULL,
  `x` decimal(21,18) DEFAULT NULL,
  `y` decimal(21,18) DEFAULT NULL,
  `complete` tinyint(1) NOT NULL DEFAULT '0',
  `image` blob,
  `last_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=247 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE IF NOT EXISTS `apacs_steps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(50) COLLATE utf8_danish_ci DEFAULT NULL,
  `description` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
  `tasks_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE IF NOT EXISTS `apacs_superusers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tasks_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE IF NOT EXISTS `apacs_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_danish_ci NOT NULL,
  `description` varchar(250) COLLATE utf8_danish_ci NOT NULL,
  `collection_id` int(11) NOT NULL,
  `primaryEntity_id` varchar(45) COLLATE utf8_danish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE IF NOT EXISTS `apacs_tasks_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tasks_id` int(11) NOT NULL,
  `pages_id` int(11) NOT NULL,
  `is_done` tinyint(1) NOT NULL DEFAULT '0',
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=131071 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE IF NOT EXISTS `apacs_tasks_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `posts_id` int(11) DEFAULT NULL,
  `tasks_id` int(11) DEFAULT NULL,
  `is_done` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE IF NOT EXISTS `apacs_tasks_units` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tasks_id` int(11) NOT NULL,
  `units_id` int(11) NOT NULL,
  `pages_done` int(11) NOT NULL DEFAULT '0',
  `columns` int(11) NOT NULL DEFAULT '1',
  `rows` int(11) NOT NULL DEFAULT '1',
  `index_active` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=257 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE IF NOT EXISTS `apacs_units` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collections_id` int(11) DEFAULT NULL,
  `pages` int(11) NOT NULL DEFAULT '0',
  `starbas_id` int(11) unsigned DEFAULT NULL,
  `description` char(250) COLLATE utf8_danish_ci NOT NULL,
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
  KEY `level_value` (`level1_value`,`level2_value`,`level3_value`) COMMENT 'Used to make distinct queries for values faster',
  KEY `level_order` (`level1_order`,`level2_order`,`level3_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE IF NOT EXISTS `apacs_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(45) COLLATE utf8_danish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=608 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;
