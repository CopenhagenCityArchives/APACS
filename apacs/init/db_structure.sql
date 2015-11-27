CREATE TABLE `apacs_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pages_id` int(11) NOT NULL,
  `collections_id` int(11) NOT NULL,
  `tasks_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  `type` varchar(45) COLLATE utf8_danish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `apacs_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collections_id` int(11) NOT NULL,
  `units_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `apacs_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  `description` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

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
  `tasks_id` int(11) NOT NULL,
  `units_id` int(11) NOT NULL,
  `pages_done` int(11) NOT NULL DEFAULT '0',
  `layout` varchar(10) COLLATE utf8_danish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `apacs_tasks_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `tasks_id` int(11) NOT NULL,
  `isSuperuser` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `apacs_units` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collection_id` int(11) DEFAULT NULL,
  `pages` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;
