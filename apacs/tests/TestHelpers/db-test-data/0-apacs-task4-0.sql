
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

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
INSERT INTO `apacs_tasks` (`id`, `name`, `description`, `collection_id`, `primaryEntity_id`) VALUES
    (4, 'Magistratens Resolutioner', 'Indtastning af magistratens resolutioner', 555, NULL);

INSERT INTO `apacs_collections` (`id`,`name`) VALUES (555,'resolutions collection name');

INSERT INTO `apacs_units` (`id`,`collections_id`,`description`,`pages`) VALUES (5551, 555, 'test unit for resolutions', 2);
INSERT INTO `apacs_pages` (`id`,`volume_id`,`unit_id`,`page_number`,`starbas_id`,`filename`,`filename_converted`,`relative_filename`,`relative_filename_converted`,`found`,`image_url`,`md5`,`s3`) VALUES 
    (55511,NULL,5551,1,12345,'resolution_1.jpg','resolution_1.jpg','resolution_1.jpg','resolution_1.jpg',NULL,'https://api-beta.kbharkiv.dk/test-assets/resolution_1.jpg','dcd7274be88ada5f4e1a4ff785ddfff5',0),
    (55512,NULL,5551,2,12345,'resolution_2.jpg','resolution_2.jpg','resolution_2.jpg','resolution_2.jpg',NULL,'https://api-beta.kbharkiv.dk/test-assets/resolution_2.jpg','dcd7274be88ada5f4e1a4ff785ddfff5',0);
INSERT INTO `apacs_tasks_pages` (`id`,`tasks_id`,`pages_id`,`units_id`,`is_done`,`last_activity`) VALUES
    (55511,4,55511,5551,0,NULL),
    (55512,4,55512,5551,0,NULL);

INSERT INTO `apacs_tasks_units` (`id`,`tasks_id`,`units_id`,`pages_done`,`columns`,`rows`,`index_active`) VALUES (5551,4,5551,0,1,10,1);

/* Jens's user */
INSERT INTO `apacs_users` (`id`, `username`)  VALUES (2385, 'jensfeodor@gmail.com');

/* Data sources */
INSERT INTO `apacs_datasources` (`id`, `name`, `sql`, `url`, `valueField`, `includeValuesInForm`, `dbTableName`, `isPublicEditable`) VALUES
  ('15', 'case_types', 'SELECT id, case_type, CASE WHEN case_type LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM resolutions_case_types ORDER BY prio DESC, case_type LIMIT 75;', NULL, 'case_type', '0', 'resolutions_case_types', '1'),
  ('16', 'transcription_types', 'SELECT id, transcription_type, CASE WHEN transcription_type LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM resolutions_transcription_types ORDER BY prio DESC, transcription_type LIMIT 75;', NULL, 'transcription_type', '0', 'resolutions_transcription_types', '1'),
  ('17', 'attachment_types', 'SELECT id, attachment_type, CASE WHEN attachment_type LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM resolutions_attachment_types ORDER BY prio DESC, attachment_type LIMIT 75;', NULL, 'attachment_type', '0', 'resolutions_attachment_types', '1'),
  ('18', 'reference_types', 'SELECT id, reference_type, CASE WHEN reference_type LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM resolutions_reference_types ORDER BY prio DESC, reference_type LIMIT 75;', NULL, 'reference_type', '0', 'resolutions_reference_types', '1'),
  ('19', 'resolutions_units', 'SELECT id, description, CASE WHEN collections_id = 555 AND description LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM apacs_units ORDER BY prio DESC, description LIMIT 75;', NULL, 'description', '0', 'description', '1'),
  ('20', 'person_sexes', 'SELECT id, sex, CASE WHEN sex LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM resolutions_person_sexes ORDER BY prio DESC, sex LIMIT 75;', NULL, 'sex', '0', 'sex', '1'),
  ('21', 'person_relations', 'SELECT id, relation, CASE WHEN relation LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM resolutions_person_relations ORDER BY prio DESC, relation LIMIT 75;', NULL, 'relation', '0', 'relation', '1'),
  ('22', 'person_roles', 'SELECT id, role, CASE WHEN role LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM resolutions_person_roles ORDER BY prio DESC, role LIMIT 75;', NULL, 'role', '0', 'role', '1'),
  ('23', 'complaint_verbs', 'SELECT id, verb, CASE WHEN verb LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM resolutions_complaint_verbs ORDER BY prio DESC, verb LIMIT 75;', NULL, 'verb', '0', 'verb', '1'),
  ('24', 'complaint_subjects', 'SELECT id, subject, CASE WHEN subject LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM resolutions_complaint_subjects ORDER BY prio DESC, subject LIMIT 75;', NULL, 'subject', '0', 'subject', '1'),
  ('25', 'complaint_subject_cats', 'SELECT id, subject_category, CASE WHEN subject_category LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM resolutions_complaint_subject_categories ORDER BY prio DESC, subject_category LIMIT 75;', NULL, 'subject_category', '0', 'subject_category', '1'),
  ('26', 'complaint_purposes', 'SELECT id, purpose, CASE WHEN purpose LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM resolutions_complaint_purposes ORDER BY prio DESC, purpose LIMIT 75;', NULL, 'purpose', '0', 'purpose', '1'),
  ('27', 'occupation_types', 'SELECT id, occupation_type, CASE WHEN occupation_type LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM resolutions_person_occupation_types ORDER BY prio DESC, occupation_type LIMIT 75;', NULL, 'occupation_type', '0', 'occupation_type', '1'),
  ('28', 'occupation_relations', 'SELECT id, relation, CASE WHEN relation LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM resolutions_person_occupation_relations ORDER BY prio DESC, relation LIMIT 75;', NULL, 'relation', '0', 'relation', '1'),
  ('29', 'occupation_categories', 'SELECT id, category, CASE WHEN category LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM resolutions_person_occupation_categories ORDER BY prio DESC, category LIMIT 75;', NULL, 'category', '0', 'category', '1'),
  ('30', 'magistrate_actions', 'SELECT id, magistrate_action, CASE WHEN magistrate_action LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM resolutions_resolution_magistrate_actions ORDER BY prio DESC, magistrate_action LIMIT 75;', NULL, 'magistrate_action', '0', 'magistrate_action', '1'),
  ('31', 'party_reactions', 'SELECT id, party_reaction, CASE WHEN party_reaction LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM resolutions_resolution_party_reactions ORDER BY prio DESC, party_reaction LIMIT 75;', NULL, 'party_reaction', '0', 'party_reaction', '1'),
  ('32', 'resolution_types', 'SELECT id, resolution_type, CASE WHEN resolution_type LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM resolutions_resolution_types ORDER BY prio DESC, resolution_type LIMIT 75;', NULL, 'resolution_type', '0', 'resolution_type', '1'),
  ('33', 'comment_types', 'SELECT id, comment_type, CASE WHEN comment_type LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM resolutions_comment_types ORDER BY prio DESC, comment_type LIMIT 75;', NULL, 'comment_type', '0', 'comment_type', '1'),
  ('34', 'resolution_hoods', 'SELECT id, neighbourhood, CASE WHEN neighbourhood LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM resolutions_place_neighbourhoods ORDER BY prio DESC, neighbourhood LIMIT 75;', NULL, 'neighbourhood', '0', 'neighbourhood', '1'),
  ('35', 'place_type', 'SELECT id, place_type, CASE WHEN place_type LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM resolutions_place_types ORDER BY prio DESC, place_type LIMIT 75;', NULL, 'place_type', '0', 'place_type', '1');


/* data structure */
CREATE TABLE `resolutions_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_types_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `start_page` varchar(45) COLLATE utf8_danish_ci DEFAULT NULL,
  `complaints_id` int(11) NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attachment_types_id` int(11) NOT NULL,
  `reference` varchar(45) COLLATE utf8_danish_ci DEFAULT NULL,
  `starbas_id` int(11) DEFAULT NULL,
  `cases_id` int(11) NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_attachment_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attachment_type` varchar(32) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;


CREATE TABLE `resolutions_case_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_type` varchar(32) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `case_type_UNIQUE` (`case_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comment` text COLLATE utf8_danish_ci NOT NULL,
  `comment_types_id` int(11) NOT NULL,
  `cases_id` int(11) NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_comment_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comment_type` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_complaint_purposes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purpose` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_complaints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `complaint_verbs_id` int(11) NOT NULL,
  `complaint_subjects_id` int(11) NOT NULL,
  `complaint_subject_categories_id` int(11) NOT NULL,
  `complaint_purposes_id` int(11) NOT NULL,
  `witnesses` bit(1) NOT NULL,
  `attachments_mentioned` bit(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_complaint_subject_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_category` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_complaint_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_complaint_verbs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `verb` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_person_occupation_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_person_occupation_relations` (
  `id` int(11) NOT NULL,
  `relation` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_person_occupations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `persons_id` int(11) NOT NULL,
  `person_occupation_relations_id` int(11) NOT NULL,
  `person_occupation_types_id` int(11) NOT NULL,
  `person_occupation_categories_id` int(11) NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_person_occupation_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `occupation_type` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_person_relations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `relation` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_person_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_persons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(96) COLLATE utf8_danish_ci NOT NULL,
  `person_sexes_id` int(11) NOT NULL,
  `person_relations_id` int(11) NOT NULL,
  `person_roles_id` int(11) NOT NULL,
  `cases_id` int(11) NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_person_sexes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sex` varchar(10) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_place_neighbourhoods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `neighbourhood` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_places` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `place` varchar(256) COLLATE utf8_danish_ci NOT NULL,
  `place_neighbourhoods_id` int(11) NOT NULL,
  `cases_id` int(11) NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_places_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `places_id` int(11) NOT NULL,
  `place_types_id` int(11) NOT NULL,
  `order` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_place_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `place_type` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_references` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `forward` varchar(45) COLLATE utf8_danish_ci DEFAULT NULL,
  `backward` varchar(45) COLLATE utf8_danish_ci DEFAULT NULL,
  `reference_types_id` int(11) NOT NULL,
  `referenced_unit_id` int(11) DEFAULT NULL,
  `cases_id` int(11) NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_reference_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference_type` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_type_UNIQUE` (`reference_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_resolutions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resolution_magistrate_actions_id` int(11) NOT NULL,
  `resolution_party_reactions_id` int(11) NOT NULL,
  `resolution_types_id` int(11) NOT NULL,
  `case_reopened` bit(1) NOT NULL,
  `date` date NOT NULL,
  `cases_id` int(11) NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_resolution_magistrate_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `magistrate_action` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_resolution_party_reactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `party_reaction` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_resolution_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resolution_type` varchar(45) COLLATE utf8_danish_ci  NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_transcriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transcription_types_id` int(11) DEFAULT NULL,
  `transcription` text COLLATE utf8_danish_ci NOT NULL,
  `cases_id` int(11) NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `resolutions_transcription_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transcription_type` varchar(45) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transcription_type_UNIQUE` (`transcription_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;