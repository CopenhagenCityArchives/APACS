CREATE TABLE `apacs_collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(50) COLLATE utf8_danish_ci NOT NULL,
  `description` char(250) COLLATE utf8_danish_ci NOT NULL,
  `url` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `apacs_entities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `countPerEntry` char(3) COLLATE utf8_danish_ci NOT NULL DEFAULT 'one',
  `dbTableName` char(50) COLLATE utf8_danish_ci NOT NULL,
  `isMarkable` tinyint(1) NOT NULL DEFAULT '0',
  `guiName` char(50) COLLATE utf8_danish_ci NOT NULL,
  `task_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `apacs_entities_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `field_id` int(11) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `field_group_number` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `apacs_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `collection_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `apacs_entries_entities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_id` int(11) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `concrete_entry_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `apacs_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(50) COLLATE utf8_danish_ci NOT NULL,
  `formType` char(50) COLLATE utf8_danish_ci NOT NULL,
  `defaultValue` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
  `placeholder` char(100) COLLATE utf8_danish_ci NOT NULL,
  `helpText` char(150) COLLATE utf8_danish_ci DEFAULT NULL,
  `dbFieldName` char(50) COLLATE utf8_danish_ci NOT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `validationRegularExpression` char(100) COLLATE utf8_danish_ci NOT NULL,
  `validationErrorMessage` char(100) COLLATE utf8_danish_ci NOT NULL,
  `normalizationTable` char(100) COLLATE utf8_danish_ci DEFAULT 'null',
  `normalizationField` char(100) COLLATE utf8_danish_ci DEFAULT 'null',
  `normalizationPrimaryKey` char(100) COLLATE utf8_danish_ci DEFAULT 'null',
  `normalizationAllowNewValue` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `apacs_filterlevels` (
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

CREATE TABLE `apacs_filters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `short_name` char(100) COLLATE utf8_danish_ci NOT NULL,
  `long_name` char(250) COLLATE utf8_danish_ci NOT NULL,
  `gui_required_fields_text` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
  `objects_query` text COLLATE utf8_danish_ci NOT NULL,
  `primary_table_name` char(50) COLLATE utf8_danish_ci NOT NULL,
  `starbas_field_name` char(50) COLLATE utf8_danish_ci DEFAULT NULL,
  `levels_type` char(50) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `apacs_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collection_id` int(11) NOT NULL,
  `concrete_page_id` int(11) NOT NULL,
  `concrete_unit_id` int(11) NOT NULL,
  `tablename` char(50) COLLATE utf8_danish_ci NOT NULL,
  `unit_id` int(11) NOT NULL DEFAULT '1',
  `page_number` int(11) NOT NULL,
  `image_url` char(250) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=458746 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `apacs_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_danish_ci NOT NULL,
  `description` varchar(250) COLLATE utf8_danish_ci NOT NULL,
  `collection_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `apacs_tasks_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tasks_id` int(11) NOT NULL,
  `pages_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `is_done` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `apacs_tasks_units` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `pages_done` int(11) NOT NULL DEFAULT '0',
  `layout` varchar(10) COLLATE utf8_danish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `apacs_tasks_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `tasks_id` int(11) NOT NULL,
  `isSuperuser` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `apacs_units` (
  `id` int(11) NOT NULL,
  `collection_id` int(11) DEFAULT NULL,
  `pages` int(11) NOT NULL DEFAULT '0',
  `tablename` char(50) COLLATE utf8_danish_ci NOT NULL,
  `concrete_unit_id` int(11) unsigned DEFAULT NULL,
  `description` char(100) COLLATE utf8_danish_ci NOT NULL,
  `index_active` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;
