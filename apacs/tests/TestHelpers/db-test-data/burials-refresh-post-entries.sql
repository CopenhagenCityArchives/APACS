/*Refresh tables for UnitTests */

INSERT INTO `apacs_posts` (`id`,`pages_id`,`width`,`height`,`x`,`y`,`complete`,`image`,`last_update`) VALUES (10000,145054,0.424170616113740000,0.317523056653490000,0.482227488151660000,0.665349143610010000,1,null,'2016-12-31 15:27:26');
INSERT INTO `apacs_entries` (`id`,`posts_id`,`tasks_id`,`users_id`,`concrete_entries_id`,`complete`,`last_update`,`test`) VALUES (9331,10000,1,653,9718,1,'2016-12-31 15:27:26',9718);
INSERT INTO `burial_persons` (`id`,`firstnames`,`lastname`,`birthname`,`ageYears`,`ageMonth`,`dateOfBirth`,`dateOfDeath`,`deathplaces_id`,`civilstatuses_id`,`birthplaces_id`,`birthplaceOther`,`yearOfBirth`,`adressOutsideCph`,`sex_id`,`comment`,`ageWeeks`,`ageDays`,`ageHours`) VALUES (9718,'Bartoline','Jensen','Svendsen',80,NULL,NULL,'1900-05-15',NULL,3,1,NULL,1820,NULL,2,NULL,NULL,NULL,NULL);
INSERT INTO `burial_addresses` (`id`,`streets_id`,`number`,`letter`,`floors_id`,`persons_id`,`institutions_id`) VALUES (8544,7782,58,NULL,NULL,9718,NULL);
INSERT INTO `burial_burials` (`id`,`cemetaries_id`,`chapels_id`,`parishes_id`,`persons_id`,`number`) VALUES (9132,11,13,12,9718,3097);
INSERT INTO `burial_persons_deathcauses` (`id`,`persons_id`,`deathcauses_id`,`order`) VALUES (12663,9718,15,0);
INSERT INTO `burial_persons_positions` (`id`,`persons_id`,`positions_id`,`relationtypes_id`,`workplaces_id`,`order`) VALUES (9518,9718,1718,2,NULL,0);


