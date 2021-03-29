
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

INSERT IGNORE INTO `apacs_users` (`id`,`username`,`auth0_user_id`) VALUES (653,'testuser1',NULL),(619,'testuser2',NULL),(2348, 'test@test.com','auth0|5ea72fe6c10da20c1a8c88f3');
INSERT INTO `apacs_posts` (`id`,`pages_id`,`width`,`height`,`x`,`y`,`complete`,`image`,`updated`,`created`) VALUES
    (20000,14711,0.45,0.25,0.525,0.05,1,null,'2020-12-31 15:27:26','2020-12-31 15:25:26');
INSERT INTO `apacs_entries` (`id`,`posts_id`,`tasks_id`,`users_id`,`last_update_users_id`,`concrete_entries_id`,`complete`,`updated`,`created`,`test`) VALUES
    (21000,20000,4,653,NULL,100,1,'2016-12-31 15:27:26','2016-12-31 15:26:26',6);

INSERT INTO `resolutions_complaint_verbs` VALUES (1,'Klager'), (2, 'Foredrager');
INSERT INTO `resolutions_complaint_subjects` VALUES (1,10,1,2,1);
INSERT INTO `resolutions_complaint_subject_names` VALUES (1,'Dokumentation');
INSERT INTO `resolutions_complaint_subject_categories` VALUES (1,'Byrum'), (2, 'Lav'), (3, 'Sociale relationer');
INSERT INTO `resolutions_complaint_purposes` VALUES (1,'Genoprettelse af orden');
INSERT INTO `resolutions_complaints` VALUES (10,2,1,1,0);
INSERT INTO `resolutions_transcriptions` VALUES (1,2,'Jens Nielsen, konsulent, brokker sig over mangel på dokumentation af APACS.',100,1);
INSERT INTO `resolutions_transcription_types` VALUES (1,'Resolution'), (2,'Henvendelse');
INSERT INTO `resolutions_person_sexes` VALUES (1,'Kvinde'), (2,'Mand'), (3,'Uoplyst'), (4,'Andet');
INSERT INTO `resolutions_references` VALUES (2,'Næste protokol',NULL,2,NULL,100,1);
INSERT INTO `resolutions_reference_types` VALUES (1, 'Klagen'), (2,'Resolutionen'), (3, 'Andet');
INSERT INTO `resolutions_cases` VALUES (100,1,'1777-05-17','Side 1','1 side','153',10,0);
INSERT INTO `resolutions_case_types` VALUES (1,'Klage'), (2,'Ikke-klage');
INSERT INTO `resolutions_person_relations` VALUES (1,'Familie'), (2,'Nabo'), (3,'Ægtefælle'), (4,'Kollega');
INSERT INTO `resolutions_persons` VALUES (2,'Jens Nielsen',2,4,1,100,1);
INSERT INTO `resolutions_person_roles` VALUES (1,'Klager'), (2, 'Påklager'), (3, 'Vidne'), (4, 'Magistransansatte'), (5, 'Andet');
INSERT INTO `resolutions_attachment_types` VALUES (1,'Bilag'), (2, 'Kopibog'), (3, 'Kollegiebrev');
INSERT INTO `resolutions_attachments` VALUES (1,2,'Ligegyldig vedhæftning',NULL,100,1);
INSERT INTO `resolutions_person_occupation_categories` VALUES (1,'Tjeneste'), (2, 'Håndværk'), (3, 'Administration'), (4, 'Handel');
INSERT INTO `resolutions_person_occupation_relations` VALUES (1,'Eget erhverv'), (2, 'Ægtefælles erhverv');
INSERT INTO `resolutions_place_types` VALUES (1, 'Trappe'), (2, 'Baggård'), (3, 'På gaden');

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;