/*
-- Query: SELECT * FROM politietsregisterblade.PRB_filmrulle WHERE filmrulle_id IN (1, 13, 814, 775)
LIMIT 0, 400

-- Date: 2021-04-06 13:26
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

INSERT INTO `PRB_filmrulle` (`filmrulle_id`,`nummer`,`note`,`station_id`,`registerblade`,`grundregistreringer`,`komplette_registreringer`) VALUES
    (1,'0024','Stinson-Sø, Svendsen (A-V), Sørensen (Aa-X) Thomsen (Aa-C) samt div',1,4536,4527,4536),
    (13,'0025','Vikkelsøe-Vaa, Ut-Ub, Tø-Taa, Thomsen (V-D) samt div. Bemærk filmrullen går baglæns',1,3741,3720,3741),
    (775,'0005','Christensen (Juul - Øjvind); Christiansen; Ebbesen - Eitved.',32,3717,3707,3717),
    (814,'0001','A',11,5621,5598,5621);

/*!40101 SET character_set_client = @saved_cs_client */;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;