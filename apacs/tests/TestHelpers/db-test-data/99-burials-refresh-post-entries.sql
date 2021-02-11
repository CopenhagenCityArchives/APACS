/*Refresh tables for UnitTests */

INSERT IGNORE INTO `apacs_posts` (`id`,`pages_id`,`width`,`height`,`x`,`y`,`complete`,`image`,`updated`,`created`) VALUES (10000,145054,0.424170616113740000,0.317523056653490000,0.482227488151660000,0.665349143610010000,1,null,'2016-12-31 15:27:26','2016-12-31 15:27:26');
INSERT IGNORE INTO `apacs_entries` (`id`,`posts_id`,`tasks_id`,`users_id`,`last_update_users_id`,`concrete_entries_id`,`complete`,`updated`,`created`,`test`) VALUES (9331,10000,1,653,NULL,9718,1,'2016-12-31 15:27:26','2016-12-31 15:27:26',9718);
INSERT IGNORE INTO `burial_addresses` (`id`,`streets_id`,`number`,`letter`,`floors_id`,`institutions_id`) VALUES (8544,7782,58,NULL,NULL,NULL);
INSERT IGNORE INTO `burial_burials` (`id`,`cemetaries_id`,`chapels_id`,`parishes_id`,`number`) VALUES (9132,11,13,12,3097);
INSERT IGNORE INTO `burial_persons` (`id`,`firstnames`,`lastname`,`birthname`,`ageYears`,`ageMonth`,`dateOfBirth`,`dateOfDeath`,`deathplaces_id`,`civilstatuses_id`,`birthplaces_id`,`birthplaceOther`,`yearOfBirth`,`addresses_id`,`burials_id`,`adressOutsideCph`,`sex_id`,`comment`,`ageWeeks`,`ageDays`,`ageHours`) VALUES (9718,'Bartoline','Jensen','Svendsen',80,NULL,NULL,'1900-05-15',NULL,3,1,NULL,1820,8544,9132,NULL,2,NULL,NULL,NULL,NULL);
INSERT IGNORE INTO `burial_persons_deathcauses` (`id`,`persons_id`,`deathcauses_id`,`order`) VALUES (12663,9718,15,0);
INSERT IGNORE INTO `burial_persons_positions` (`id`,`persons_id`,`positions_id`,`relationtypes_id`,`workplaces_id`,`order`) VALUES (9518,9718,1718,2,NULL,0);


