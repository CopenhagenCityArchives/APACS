INSERT IGNORE INTO `apacs_users` (`id`,`username`,`auth0_user_id`) VALUES (653,'testuser1',NULL),(619,'testuser2',NULL),(2348, 'test@test.com','auth0|5ea72fe6c10da20c1a8c88f3');
INSERT INTO `apacs_posts` (`id`,`pages_id`,`width`,`height`,`x`,`y`,`complete`,`image`,`updated`,`created`) VALUES
    (20000,55511,0.95,0.05,0.025, 0.025,1,null,'2020-12-31 15:27:26','2020-12-31 15:25:26');
INSERT INTO `apacs_entries` (`id`,`posts_id`,`tasks_id`,`users_id`,`last_update_users_id`,`concrete_entries_id`,`complete`,`updated`,`created`,`test`) VALUES
    (21000,20000,4,653,NULL,6,1,'2016-12-31 15:27:26','2016-12-31 15:26:26',6);

INSERT INTO `resolutions_complaint_verbs` VALUES (6,'Verbum');
INSERT INTO `resolutions_complaint_subject_categories` VALUES (6,'Emnekategori');
INSERT INTO `resolutions_attachment_types` VALUES (6,'asd');
INSERT INTO `resolutions_complaints` VALUES (6,6,6,6,6,'','');
INSERT INTO `resolutions_transcriptions` VALUES (6,6,'Transkriberet tekst',6,1);
INSERT INTO `resolutions_transcription_types` VALUES (6,'Transkriptionen');
INSERT INTO `resolutions_person_sexes` VALUES (2,'Mand');
INSERT INTO `resolutions_references` VALUES (2,'Henvfrem','Henvtilb',2,NULL,6,1);
INSERT INTO `resolutions_complaint_purposes` VALUES (6,'Formål med klagen');
INSERT INTO `resolutions_reference_types` VALUES (2,'Type');
INSERT INTO `resolutions_cases` VALUES (6,6,'1777-05-17','Asd',6,0);
INSERT INTO `resolutions_complaint_subjects` VALUES (6,'Emne');
INSERT INTO `resolutions_case_types` VALUES (6,'hey');
INSERT INTO `resolutions_person_relations` VALUES (2,'Klager');
INSERT INTO `resolutions_persons` VALUES (2,'Jens Nielsen',2,2,2,6,1);
INSERT INTO `resolutions_person_roles` VALUES (2,'Rolle');
INSERT INTO `resolutions_attachments` VALUES (6,6,'Asd',NULL,6,1);