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


INSERT INTO `apacs_datasources` (`id`, `name`, `sql`, `url`, `valueField`, `includeValuesInForm`, `dbTableName`, `isPublicEditable`) VALUES
  ('37', 'prb_vej', 'SELECT id, navn, CASE WHEN navn LIKE \":query%\" THEN 5 ELSE 0 END as prio FROM PRB_vej ORDER BY prio DESC, navn LIMIT 75;', NULL, 'navn', '0', 'PRB_vej', '0');

INSERT INTO `apacs_tasks` (`id`, `name`, `description`, `collection_id`, `primaryEntity_id`) VALUES
    (5, 'Politiets Registerblade', 'Rettelse af Politiets Registerblade', 76, NULL);

INSERT INTO `apacs_collections` (`id`,`name`) VALUES
  (76,'resolutions collection name');

-- INSERT INTO `apacs_units` (`id`,`collections_id`,`description`,`pages`) VALUES
--   (7611, 76, 'station 4 (Amager) - Stinson-Sø, Svendsen (A-V), Sørensen (Aa-X) Thomsen (Aa-C)', 2),
--   (76113, 76, 'station 4 (Amager) - Vikkelsøe-Vaa, Ut-Ub, Tø-Taa, Thomsen (V-D)', 2),
--   (7632775, 76, 'station 8 - Christensen (Juul - Øjvind); Christiansen; Ebbesen - Eitved.', 2),
--   (7611814, 76, 'Dødeblade (indeholder afdøde i perioden) - A', 2);

CREATE TABLE `PRB_adresse` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `registerblad_id` mediumint(9) NOT NULL,
  `vej_id` mediumint(9) DEFAULT NULL,
  `adresse_dag` tinyint(2) DEFAULT NULL,
  `adresse_maaned` tinyint(2) DEFAULT NULL,
  `adresse_aar` smallint(4) DEFAULT NULL,
  `vejnummer` char(10) DEFAULT NULL,
  `vejnummerbogstav` char(3) DEFAULT NULL,
  `etage` char(10) NOT NULL,
  `sideangivelse` char(3) NOT NULL,
  `sted` char(90) DEFAULT NULL,
  `tjenesteLogerendeHos` char(255) NOT NULL,
  `fra_note` char(100) NOT NULL,
  `til_note` char(100) NOT NULL,
  `frameldt` tinyint(1) NOT NULL DEFAULT '0',
  `adresse_dato` int(11) DEFAULT NULL,
  `opgang` char(50) NOT NULL,
  `koordinat_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `registerblad_id` (`registerblad_id`),
  KEY `vej_id` (`vej_id`),
  KEY `adresse_dato` (`adresse_dato`),
  KEY `koordinat_id` (`koordinat_id`),
  KEY `sted` (`sted`),
  KEY `etage` (`etage`),
  KEY `sideangivelse` (`sideangivelse`)
) ENGINE=InnoDB AUTO_INCREMENT=4960214 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `PRB_person` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `registerblad_id` mediumint(9) NOT NULL,
  `fornavne` char(80) NOT NULL,
  `efternavn` char(50) NOT NULL,
  `koen` tinyint(1) DEFAULT '3' COMMENT '1 = mand, 2 = kvinde og 3 = ukendt',
  `foedselsdag` tinyint(2) DEFAULT NULL,
  `foedselsmaaned` tinyint(2) DEFAULT NULL,
  `foedselsaar` smallint(4) DEFAULT NULL,
  `foedested_id` mediumint(9) DEFAULT NULL,
  `pigenavn` char(50) NOT NULL,
  `gift` tinyint(1) NOT NULL,
  `afdoed_dag` tinyint(2) DEFAULT NULL,
  `afdoed_maaned` tinyint(2) DEFAULT NULL,
  `afdoed_aar` smallint(4) DEFAULT NULL,
  `person_type` tinyint(4) NOT NULL,
  `foedselsdato` int(11) DEFAULT NULL,
  `afdoed_dato` int(11) DEFAULT NULL,
  `last_changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `civilstatus` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `foedested_id` (`foedested_id`),
  KEY `registerblad_id` (`registerblad_id`),
  KEY `foedselsdag` (`foedselsdag`),
  KEY `foedselsmaaned` (`foedselsmaaned`),
  KEY `foedselsaar` (`foedselsaar`),
  KEY `fornavne` (`fornavne`),
  KEY `efternavn` (`efternavn`),
  KEY `person_type` (`person_type`),
  KEY `pigenavn` (`pigenavn`,`afdoed_dag`,`afdoed_maaned`,`afdoed_aar`),
  KEY `foedselsdato` (`foedselsdato`),
  KEY `afdoed_dato` (`afdoed_dato`),
  KEY `koen` (`koen`)
) ENGINE=InnoDB AUTO_INCREMENT=2103679 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `PRB_person_stilling` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `person_id` mediumint(9) NOT NULL,
  `stilling_id` mediumint(9) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `person_id` (`person_id`),
  KEY `stilling_id` (`stilling_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2212947 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `PRB_persontype` (
  `id` tinyint(4) NOT NULL,
  `beskrivelse` char(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `PRB_personrelationer` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `person1_id` mediumint(9) NOT NULL,
  `person2_id` mediumint(9) NOT NULL,
  `relationstype` tinyint(2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=775566 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci COMMENT='Relationstyper: 2) Ægtefæller 3) Forældre-barn';

CREATE TABLE `PRB_stilling` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `stilling` char(70) NOT NULL,
  `kontrolleret_stilling_id` int(11) DEFAULT NULL,
  `indtastet_stilling` char(70) DEFAULT NULL COMMENT 'Tildelt værdien fra stilling 28-02-2018 før normalisering',
  PRIMARY KEY (`id`),
  KEY `stilling` (`stilling`),
  KEY `kontrolleret_stilling_id` (`kontrolleret_stilling_id`)
) ENGINE=InnoDB AUTO_INCREMENT=103432 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `PRB_kontrolleret_stilling` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kontrolleret_stilling` varchar(100) DEFAULT NULL,
  `kontrolleret_stilling_eng` varchar(100) DEFAULT NULL,
  `ISCO_major_group` char(5) DEFAULT NULL,
  `ISCO_submajor_group` char(5) DEFAULT NULL,
  `ISCO_minor_group` char(5) DEFAULT NULL,
  `ISCO_unit` char(5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kontrolleret_stilling_UNIQUE` (`kontrolleret_stilling`),
  KEY `kontrolleret_stilling` (`kontrolleret_stilling`)
) ENGINE=InnoDB AUTO_INCREMENT=3623 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci COMMENT='Tabel med kontrollerede stillingsbetegnelser på baggrund af liste fra Mikkel Thelle';

CREATE TABLE `PRB_lande` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `land` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `land_UNIQUE` (`land`)
) ENGINE=InnoDB AUTO_INCREMENT=205 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `PRB_foedested` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `foedested` char(100) NOT NULL,
  `foedested_indtastet` char(100) NOT NULL,
  `autoriserede_stednavne_id` int(11) DEFAULT NULL,
  `land_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `foedested_UNIQUE` (`foedested`),
  KEY `foedested` (`foedested`)
) ENGINE=InnoDB AUTO_INCREMENT=224577 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE `PRB_vej` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `navn` char(65) DEFAULT NULL,
  `burial_streets_id` int(11) DEFAULT NULL,
  `burial_streets_streetAndHood` char(65) DEFAULT NULL,
  `burial_institutions_id` int(11) DEFAULT NULL,
  `burial_institutions_institution` char(65) DEFAULT NULL,
  `burial_hoods_id` int(11) DEFAULT NULL,
  `helgas_kommentar` text COLLATE utf8_danish_ci,
  PRIMARY KEY (`id`),
  KEY `navn` (`navn`),
  KEY `street_id` (`burial_streets_id`),
  KEY `institution_id` (`burial_institutions_id`),
  KEY `hood_id` (`burial_hoods_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3278 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `PRB_registerblad` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `station_id` mediumint(9) NOT NULL,
  `filmrulle_id` mediumint(9) NOT NULL,
  `udfyldelse_dag` tinyint(4) DEFAULT NULL,
  `udfyldelse_maaned` tinyint(4) DEFAULT NULL,
  `udfyldelse_aar` smallint(6) DEFAULT NULL,
  `registreringsstatus` tinyint(4) NOT NULL DEFAULT '0',
  `filnavn` char(10) NOT NULL,
  `filnavn2` char(10) NOT NULL,
  `saerlige_bemaerkninger` char(250) NOT NULL,
  `udfyldelse_dato` int(11) DEFAULT NULL,
  `fler_opl` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Felt til at markere om der findes opl fra efter 1924',
  `approved` tinyint(1) NOT NULL DEFAULT '0',
  `complete` int(11) NOT NULL DEFAULT '0',
  `complete_time` timestamp,
  PRIMARY KEY (`id`),
  KEY `filmrulle_id` (`filmrulle_id`),
  KEY `station_id` (`station_id`),
  KEY `registreringsstatus` (`registreringsstatus`),
  KEY `udfyldelse_dag` (`udfyldelse_dag`),
  KEY `udfyldelse_maaned` (`udfyldelse_maaned`),
  KEY `udfyldelse_aar` (`udfyldelse_aar`),
  KEY `FlerOpl` (`fler_opl`)
) ENGINE=InnoDB AUTO_INCREMENT=3468260 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `PRB_station` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `nummer` tinyint(4) NOT NULL,
  `navn` char(50) NOT NULL,
  `beskrivelse` char(60) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `nummer` (`nummer`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE `PRB_filmrulle` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `nummer` char(6) NOT NULL,
  `note` char(120) NOT NULL,
  `station_id` mediumint(9) NOT NULL,
  `registerblade` mediumint(9) NOT NULL DEFAULT '0',
  `grundregistreringer` mediumint(9) NOT NULL DEFAULT '0',
  `komplette_registreringer` mediumint(9) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `station_id` (`station_id`)
) ENGINE=InnoDB AUTO_INCREMENT=853 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;


/*!40101 SET character_set_client = @saved_cs_client */;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;