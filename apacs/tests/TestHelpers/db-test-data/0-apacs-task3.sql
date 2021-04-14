INSERT INTO `apacs_tasks` (`id`, `name`, `description`, `collection_id`, `primaryEntity_id`) VALUES
    (3,'Begravelser - udvidelser','Begravelser 1912 (lb.nr. 1-3858) - 1940 (lb.nr. 4263-9281)',1,NULL);

INSERT INTO `apacs_pages` (`id`,`volume_id`,`unit_id`,`page_number`,`starbas_id`,`relative_filename_converted`,`image_url`) VALUES
    (114579,null,321,157,74716,'testfile.jpg','http://www.kbhkilder.dk/getfile.php?fileId=114579'),
    (114580,null,321,157,74716,'testfile.jpg','http://www.kbhkilder.dk/getfile.php?fileId=114580');

INSERT INTO `apacs_units` (`id`,`collections_id`,`description`,`pages`) VALUES
    (321, 1, 'test unit 321',1000);

INSERT INTO `apacs_tasks_pages` (`id`,`tasks_id`,`pages_id`,`units_id`,`is_done`,`last_activity`) VALUES
    (32100,3,114579,321,0,'2020-12-31 15:27:36'),
    (32101,3,114580,321,0,'2020-12-31 15:27:36');

INSERT INTO `apacs_tasks_units` (`id`,`tasks_id`,`units_id`,`pages_done`,`columns`,`rows`,`index_active`) VALUES
    (1000,3,321,156,2,4,1);