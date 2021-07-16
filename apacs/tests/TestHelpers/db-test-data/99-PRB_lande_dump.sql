/*
-- Query: SELECT DISTINCT
	l.*
FROM PRB_lande l
LEFT JOIN politietsregisterblade.PRB_foedested fs ON fs.land_id = l.id
LEFT JOIN PRB_person p ON p.foedested_id = fs.foedested_id
LEFT JOIN PRB_registerblad r ON p.registerblad_id = r.registerblad_id
WHERE r.filmrulle_id IN (1, 13, 814, 775)
-- Date: 2021-03-31 10:53
*/

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

INSERT INTO `PRB_lande` (`id`,`land`) VALUES
	(190,'USA'),
	(185,'Tyskland'),
	(166,'Sverige'),
	(130,'Norge'),
	(201,'England'),
	(143,'Rusland'),
	(55,'Finland'),
	(77,'Island'),
	(57,'Frankrig'),
	(15,'Belgien'),
	(165,'Schweiz'),
	(81,'Japan'),
	(139,'Polen'),
	(202,'Færøerne'),
	(79,'Italien'),
	(180,'Tjekkiet'),
	(94,'Letland'),
	(200,'Østrig'),
	(50,'Estland'),
	(160,'Spanien'),
	(71,'Holland'),
	(76,'Irland'),
	(188,'Ungarn'),
	(7,'Argentina'),
	(72,'Indien'),
	(10,'Australien'),
	(86,'Kina');

/*!40101 SET character_set_client = @saved_cs_client */;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;