/*
-- Query: SELECT * FROM kbharkiv.apacs_entries where posts_id = 10000
LIMIT 0, 1000

-- Date: 2019-02-18 14:36
*/
INSERT INTO `apacs_collections` (`id`,`name`) VALUES (1,'collection 1 name');
INSERT INTO `apacs_entries` (`id`,`posts_id`,`tasks_id`,`users_id`,`last_update_users_id`,`concrete_entries_id`,`complete`,`updated`,`created`,`test`) VALUES (9331,10000,1,653,NULL,9718,1,'2016-12-31 15:27:26','2016-12-31 15:27:26',9718);
INSERT INTO `apacs_pages` (`id`,`volume_id`,`unit_id`,`page_number`,`starbas_id`,`filename`,`filename_converted`,`relative_filename`,`relative_filename_converted`,`found`,`image_url`,`md5`,`s3`,`s3_bucket`,`s3_key`) VALUES (145054,267,123,519,74716,'testfile.jpg','testfile.jpg','testfile.jpg','testfile.jpg',NULL,'http://www.kbhkilder.dk/getfile.php?fileId=145054','dcd7274be88ada5f4e1a4ff785ddfff5',0,'kbhkilder','10000'),(145055,267,123,520,74716,'testfile.jpg','testfile.jpg','testfile.jpg','testfile.jpg',NULL,'http://www.kbhkilder.dk/getfile.php?fileId=145054','dcd7274be88ada5f4e1a4ff785ddfff5',0,'kbhkilder','10000');
INSERT INTO `apacs_posts` (`id`,`pages_id`,`width`,`height`,`x`,`y`,`complete`,`image`,`updated`,`created`) VALUES (10000,145054,0.424170616113740000,0.317523056653490000,0.482227488151660000,0.665349143610010000,1,null,'2016-12-31 15:27:26','2016-12-31 15:27:26');
INSERT INTO `apacs_tasks_pages` (`id`,`tasks_id`,`pages_id`,`units_id`,`is_done`,`last_activity`) VALUES (72410,1,145054,123,1,'2016-12-31 15:27:36'),(72411,1,145055,123,0,'2016-12-31 15:27:36');
INSERT INTO `apacs_units` (`id`,`collections_id`,`description`,`pages`) VALUES (123, 1, 'test unit 123',1000);
INSERT INTO `apacs_units` (`id`,`collections_id`,`description`,`pages`) VALUES (1, 1, 'test unit 1',1000);
INSERT INTO `apacs_units` (`id`,`collections_id`,`description`,`pages`) VALUES (2, 1, 'test unit 2',1000);
INSERT INTO `apacs_tasks_units` (`id`,`tasks_id`,`units_id`,`pages_done`,`columns`,`rows`,`index_active`) VALUES (124,1,123,678,2,3,1);
INSERT INTO `apacs_users` (`id`,`username`,`auth0_user_id`) VALUES (1,'testuser',NULL),(653,'testuser1',NULL),(619,'testuser2',NULL),(2348, 'test@test.com','auth0|5ea72fe6c10da20c1a8c88f3');