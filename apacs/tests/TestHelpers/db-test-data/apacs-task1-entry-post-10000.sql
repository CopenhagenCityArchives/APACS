/*
-- Query: SELECT * FROM kbharkiv.apacs_entries where posts_id = 10000
LIMIT 0, 1000

-- Date: 2019-02-18 14:36
*/
INSERT INTO `apacs_entries` (`id`,`posts_id`,`tasks_id`,`users_id`,`concrete_entries_id`,`complete`,`last_update`,`test`) VALUES (9331,10000,1,653,9718,1,'2016-12-31 15:27:26',9718);
INSERT INTO `apacs_pages` (`id`,`volume_id`,`unit_id`,`page_number`,`starbas_id`,`filename`,`filename_converted`,`relative_filename`,`relative_filename_converted`,`found`,`image_url`,`md5`,`s3`) VALUES (145054,267,123,519,74716,'13-1057_DK-84_1-1-61_00519.jpg','13-1057_DK-84_Burial-registers-59-Jan-Jun-1900_00519.jpg','begravelsesprotokoller/d3/13-1057_DK-84_0002/13-1057_DK-84_0007/13-1057_DK-84_Burial-registers-59-Jan-Jun-1900_1389832551/13-1057_DK-84_1-1-61_00519.jpg','begravelsesprotokoller/d3/13-1057_DK-84_0002/13-1057_DK-84_0007/13-1057_DK-84_Burial-registers-59-Jan-Jun-1900_1389832551/13-1057_DK-84_Burial-registers-59-Jan-Jun-1900_00519.jpg',NULL,'http://www.kbhkilder.dk/getfile.php?fileId=145054','dcd7274be88ada5f4e1a4ff785ddfff5',0);
INSERT INTO `apacs_posts` (`id`,`pages_id`,`width`,`height`,`x`,`y`,`complete`,`image`,`last_update`) VALUES (10000,145054,0.424170616113740000,0.317523056653490000,0.482227488151660000,0.665349143610010000,1,null,'2016-12-31 15:27:26');
