LOCK TABLES `apacs_tasks` WRITE;
/*!40000 ALTER TABLE `apacs_tasks` DISABLE KEYS */;
INSERT INTO `apacs_tasks` (`id`, `name`, `description`, `collection_id`, `primaryEntity_id`) VALUES (1,'Begravelser','Indtastning af begravelsesprotokoller',1,NULL);
/*!40000 ALTER TABLE `apacs_tasks` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `apacs_datasources` WRITE;
/*!40000 ALTER TABLE `apacs_datasources` DISABLE KEYS */;
INSERT INTO `apacs_datasources` (`id`, `name`, `sql`, `url`, `valueField`, `includeValuesInForm`, `dbTableName`, `isPublicEditable`) VALUES (1,'positions','SELECT id, position FROM (SELECT case WHEN position LIKE \':query%\' THEN 5 + priority WHEN position = \'-\' THEN 9 ELSE priority END AS prio, id, position FROM burial_positions WHERE position LIKE \'%:query%\') a ORDER BY prio DESC, position LIMIT 75',NULL,'position',0,'burial_positions',1),
(2,'deathcauses','SELECT id, deathcause, CASE WHEN deathcause LIKE \":query%\" THEN 5 + priority ELSE priority END as prio FROM burial_deathcauses WHERE deathcause LIKE \"%:query%\" ORDER BY prio DESC, deathcause LIMIT 75;',NULL,'deathcause',0,'burial_deathcauses',1),
(3,'streets','SELECT id, streetAndHood FROM (SELECT case WHEN streetandhood lIKE \':query%\' THEN 5 + priority WHEN streetandhood = \'-\' THEN 9 ELSE priority END AS prio, id, streetandhood FROM burial_streets WHERE streetandhood LIKE \'%:query%\') a ORDER BY prio DESC, streetandhood LIMIT 75',NULL,'streetAndHood',0,'burial_streets',1),
(4,'floors','SELECT id, floor FROM burial_floors WHERE floor LIKE\' %:query%\'',NULL,'floor',1,'burial_floors',0),
(5,'civilstatuses','SELECT id, civilstatus FROM burial_civilstatuses WHERE civilstatus LIKE \'%:query%\'',NULL,'civilstatus',1,'burial_civilstatuses',0),
(6,'chapels','SELECT id, chapel, CASE WHEN chapel LIKE \":query%\"  THEN 5 + priority ELSE priority END as prio FROM burial_chapels WHERE chapel LIKE \"%:query%\"  ORDER BY prio DESC, chapel LIMIT 75;',NULL,'chapel',0,'burial_chapels',1),
(7,'cemetaries','SELECT id, cemetary, CASE WHEN cemetary LIKE \":query%\" THEN 5 + priority ELSE priority END as prio FROM burial_cemetaries WHERE cemetary LIKE \"%:query%\" ORDER BY prio DESC, cemetary LIMIT 75;',NULL,'cemetary',0,'burial_cemetaries',1),
(8,'deathplaces','SELECT id, deathplace FROM (SELECT case WHEN deathplace LIKE \':query%\' THEN 5 + priority WHEN deathplace = \'-\' THEN 9 ELSE priority END AS prio, id, deathplace FROM burial_deathplaces WHERE deathplace LIKE \'%:query%\') a ORDER BY prio DESC, deathplace',NULL,'deathplace',0,'burial_deathplaces',1),
(9,'birthplaces','SELECT id, name FROM burial_birthplaces WHERE name LIKE \'%:query%\'',NULL,'name',0,'burial_birthplaces',0),
(10,'parishes','SELECT id, parish, CASE WHEN parish LIKE \":query%\" AND fromYear<1913 THEN 5 + priority ELSE priority END as prio FROM burial_parishes WHERE parish LIKE \"%:query%\" AND fromYear<1913 ORDER BY prio DESC, parish LIMIT 75;',NULL,'parish',0,'burial_parishes',1),
(11,'relationtype','SELECT id, relationtype FROM burial_relationtypes WHERE relationtype LIKE \"%:query%\"',NULL,'relationtype',1,'burial_relationtypes',0),
(12,'sex','SELECT id, sex FROM burial_persons_sex WHERE sex LIKE \"%:query%\"',NULL,'sex',1,'burial_persons_sex',0),
(13,'institution','SELECT id, institution, CASE WHEN institution LIKE \":query%\" THEN 5 + priority ELSE priority END as prio FROM burial_institutions WHERE institution LIKE \"%:query%\" ORDER BY prio DESC, institution LIMIT 75;',NULL,'institution',0,'burial_institutions',1),
(14,'workplace','SELECT id, workplace, CASE WHEN workplace LIKE \":query%\" THEN 5 + priority ELSE priority END as prio FROM burial_workplaces WHERE workplace LIKE \"%:query%\" ORDER BY prio DESC, workplace LIMIT 75;',NULL,'workplace',0,'burial_workplaces',1);
/*!40000 ALTER TABLE `apacs_datasources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `apacs_steps`
--

LOCK TABLES `apacs_steps` WRITE;
/*!40000 ALTER TABLE `apacs_steps` DISABLE KEYS */;
INSERT INTO `apacs_steps` (`id`, `name`, `description`, `tasks_id`) VALUES (1,'Markering af indtastningsområde','Her skal du markere det område i protokollen, der handler om den person/begravelse, som du vil indtaste.',1),
(2,'Løbenummer, Navn, Køn, og Alder','',1),
(3,'Bopæl','',1),
(4,'Erhverv, Civilstand og Dødsdato','',1),
(5,'Dødsårsag, Dødssted, Fra, Sogn og Kirkegård','',1),
(6,'Markering af indtastningsområde',NULL,2),
(7,'Navn, alder og stilling',NULL,2),
(8,'Bopæl',NULL,2),
(9,'Dødsdato og årsag',NULL,2),
(10,'Kirkegård',NULL,2);
/*!40000 ALTER TABLE `apacs_steps` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `apacs_entities` WRITE;
/*!40000 ALTER TABLE `apacs_entities` DISABLE KEYS */;
INSERT INTO `apacs_entities` (`id`, `task_id`, `name`, `isPrimaryEntity`, `entityKeyName`, `type`, `required`, `countPerEntry`, `guiName`, `primaryTableName`, `includeInSOLR`, `viewOrder`, `parent_id`, `dbTableName`, `fieldRelatingToParent`, `allowNewValues`, `typeOfRelationToParent`, `saveOrderAccordingToParent`) VALUES (1,1,'persons',1,'id','object',1,'1','Person','burial_persons',0,1,NULL,NULL,NULL,1,'connection','after'),
(2,1,'deathcauses',0,'persons_id','array',0,'1','Dødsårsag','burial_persons_deathcauses',0,3,NULL,NULL,NULL,1,'connection','after'),
(3,1,'positions',0,'persons_id','array',0,'1','Erhverv','burial_persons_positions',0,2,NULL,NULL,NULL,1,'connection','after'),
(4,1,'addresses',0,'persons_id','object',0,'1','Adresse','burial_addresses',1,4,NULL,NULL,NULL,1,'connection','after'),
(5,1,'burials',0,'persons_id','object',0,'1','Begravelse','burial_burials',0,5,NULL,NULL,NULL,1,'connection','after'),
(8,2,'persons',NULL,NULL,'object',1,'one','Person','primaryTableName',0,1,NULL,'burial_persons',NULL,1,'border','after'),
(9,2,'addresses',NULL,NULL,'object',0,'one','Adresse','primaryTableName',1,4,8,'burial_addresses','persons_id',1,'connection','after'),
(10,2,'floors',NULL,NULL,'object',0,'one','no name','primaryTableName',0,-1,9,'burial_floors','floors_id',0,'connection','before'),
(11,2,'streets',NULL,NULL,'object',0,'one','no name','primaryTableName',0,-1,9,'burial_streets','streets_id',0,'connection','before'),
(12,2,'hoods',NULL,NULL,'object',0,'one','no name','primaryTableName',1,-1,11,'burial_hoods','hoods_id',0,'connection','before'),
(13,2,'birthplaces',NULL,NULL,'object',0,'one','no name','primaryTableName',0,-1,8,'burial_birthplaces','birthplaces_id',0,'connection','before'),
(14,2,'burials',NULL,NULL,'object',0,'one','Begravelse','primaryTableName',0,5,8,'burial_burials','persons_id',1,'connection','after'),
(15,2,'cemetaries',NULL,NULL,'object',0,'one','no name','primaryTableName',0,-1,14,'burial_cemetaries','cemetaries_id',0,'connection','before'),
(16,2,'chapels',NULL,NULL,'object',0,'one','no name','primaryTableName',0,-1,14,'burial_chapels','chapels_id',0,'connection','before'),
(17,2,'deathplaces',NULL,NULL,'object',0,'one','no name','primaryTableName',0,-1,8,'burial_deathplaces','deathplaces_id',1,'connection','before'),
(18,2,'civilstatuses',NULL,NULL,'object',0,'one','no name','primaryTableName',0,-1,8,'burial_civilstatuses','civilstatuses_id',0,'connection','before'),
(19,2,'persons_deathcauses',NULL,NULL,'array',0,'one','Dødsårsag','primaryTableName',0,4,8,'burial_persons_deathcauses','persons_id',1,'border','after'),
(20,2,'deathcauses',NULL,NULL,'object',1,'one','no name','primaryTableName',0,-1,19,'burial_deathcauses','deathcauses_id',1,'connection','before'),
(21,2,'persons_positions',NULL,NULL,'array',0,'one','Erhverv','primaryTableName',0,2,8,'burial_persons_positions','persons_id',1,'border','after'),
(22,2,'positions',NULL,NULL,'object',0,'one','no name','primaryTableName',1,-1,21,'burial_positions','positions_id',0,'connection','before'),
(23,2,'relationtypes',NULL,NULL,'object',0,'one','no name','primaryTableName',1,-1,21,'burial_relationtypes','relationtypes_id',0,'connection','before'),
(24,2,'parishes',NULL,NULL,'object',0,'one','no name','primaryTableName',1,-1,14,'burial_parishes','parishes_id',0,'connection','before'),
(25,2,'institutions',0,NULL,'object',0,'one','no name','primaryTableName',1,-1,9,'burial_institutions','institutions_id',0,'connection','before'),
(26,2,'workplaces',0,NULL,'object',0,'one','no name','primaryTableName',1,-1,21,'burial_workplaces','workplaces_id',0,'connection','before'),
(27,2,'sex',NULL,NULL,'object',0,'one','no name','primaryTableName',0,-1,8,'burial_persons_sex','sex_id',0,'connection','before');
/*!40000 ALTER TABLE `apacs_entities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `apacs_fields`
--

LOCK TABLES `apacs_fields` WRITE;
/*!40000 ALTER TABLE `apacs_fields` DISABLE KEYS */;
INSERT INTO `apacs_fields` (`id`, `entities_id`, `steps_id`, `datasources_id`, `tableName`, `fieldName`, `hasDecode`, `decodeTable`, `decodeField`, `codeAllowNewValue`, `includeInForm`, `formName`, `formFieldType`, `formFieldOrder`, `defaultValue`, `helpText`, `placeholder`, `isRequired`, `validationRegularExpression`, `validationErrorMessage`, `includeInSOLR`, `SOLRFieldName`, `SOLRFacet`, `SOLRResult`, `name`) VALUES (7,1,2,NULL,'burial_persons','firstnames',0,NULL,NULL,0,1,'Fornavne','string',1,NULL,'Alle for- og mellemnavne.',NULL,1,'/[A-Za-zåøæÅØÆ? \\.]{1,}/','Feltet skal udfyldes. Du kan evt. skrive \'ukendt\' eller \'udøbt\'. Se vejledningen. Erstat bogstaver med ?, hvis de ikke kan læses.',1,'firstnames',0,1,'no_given'),
(9,1,2,NULL,'burial_persons','lastname',0,NULL,NULL,0,1,'Efternavn','string',2,NULL,'Skriv kun et efternavn. Alle andre navne indtastes under fornavne.',NULL,1,'/[A-Za-zåøæÅØÆ? \\.]{1,}/','Feltet skal udfyldes. Erstat bogstaver med ?, hvis de ikke kan læses.',1,'lastname',1,1,'no_given'),
(10,2,5,2,'burial_persons_deathcauses','deathcauses_id',1,'burial_deathcauses','deathcause',0,1,'Dødsårsag','typeahead',180,NULL,'Hvis dødsårsagen ikke matcher en af valgmulighederne på listen, skal du vælge *skal oprettes. Tilføj flere dødsårsager',NULL,0,'/\\w{1,}/','Du skal vælge en dødsårsag fra listen. Hvis ingen af valgmulighederne matcher, så vælg *skal oprettes',1,'deathcauses',0,0,'no_given'),
(11,3,4,1,'burial_persons_positions','positions_id',1,'burial_positions','position',0,1,'Erhverv','typeahead',130,NULL,'Forstået som stilling, ikke civilstand.',NULL,0,'/\\w{1,}/','Du skal vælge et erhverv fra listen. Findes erhvervet ikke på listen, så vælg *skal oprettes.',1,'positions',0,0,'no_given'),
(12,3,4,11,'burial_persons_positions','relationtypes_id',1,'burial_relationtypes','relationtype',0,1,'Relation til erhverv','string',140,'Eget erhverv','Den afdødes relation til erhvervet.',NULL,0,'/\\w{1,}/','Skal udfyldes, hvis erhverv er udfyldt.',0,NULL,0,0,'no_given'),
(13,4,3,3,'burial_addresses','streets_id',1,'burial_streets','streetAndHood',0,1,'Gade','typeahead',70,NULL,'Vælg et gadenavn fra listen. Hvis den ikke findes på listen så vælg *Skal oprettes',NULL,0,'/\\w{1,}/','Du skal vælge en gade fra listen. Hvis gaden ikke er på listen, så vælges *skal oprettes.',1,'streets',1,0,'no_given'),
(14,4,3,NULL,'burial_addresses','number',0,NULL,NULL,0,1,'Gadenummer','number',80,NULL,'Husnummer.',NULL,0,'/^\\d{1,3}$/','Du skal skrive et tal. Bogstavangivelser i forbindelse med husnummeret skrives i feltet nedenfor.',0,NULL,0,0,'no_given'),
(15,4,3,NULL,'burial_addresses','letter',0,NULL,NULL,0,1,'Gadenummerbogstav','string',90,NULL,'Bogstavangivelse i forbindelse med husnummer.',NULL,0,'/^[a-zA-ZæøåÆØÅ]{1}$/','Du skal skrive et bogstav.',0,NULL,0,0,'no_given'),
(16,1,3,NULL,'burial_persons','adressOutsideCph',0,NULL,NULL,0,1,'Resten af Danmark/verden','string',120,NULL,'Alle adresser uden for Københavm Frederiksberg og Gentofte. Skriv hele adressen eller stednavnet som det står. Udfyldes dette felt skal de ovenstående felter til gadenavn mv. ikke udfyldes.',NULL,0,'/\\w{1,}/','Du skal kun udfylde dette felt, hvis adressen ikke er i København, Frederiksberg eller Gentofte.',1,'adressOutsideCph',0,0,'no_given'),
(17,4,3,4,'burial_addresses','floors_id',1,'burial_floors','floor',0,1,'Etage','string',100,NULL,'Er ofte angivet med romertal.',NULL,0,NULL,'Du skal vælge en etage fra listen.',0,NULL,0,0,'no_given'),
(18,1,2,NULL,'burial_persons','ageYears',0,NULL,NULL,0,1,'Alder - år','number',5,NULL,'Alder. Du kan kun skrive hele tal i feltet. Brug månedsfeltet, hvis du vil taste et halvt år.',NULL,0,'/^\\d{1,3}$/','Du kan kun skrive hele tal i feltet. Brug månedsfeltet, hvis du vil taste et halvt år.',1,'ageYears',1,1,'no_given'),
(19,1,2,NULL,'burial_persons','ageMonth',0,NULL,NULL,0,1,'Alder - måneder','string',6,'0','Antal måneder. 3/12 tastes som 3',NULL,0,'/^[0-9]+(\\,[0-9]{1,2})?$/','Du skal skrive antal måneder. Halve måneder tastes også fx 0,5',0,'ageMonth',0,1,'no_given'),
(20,1,4,NULL,'burial_persons','dateOfDeath',0,NULL,NULL,1,1,'Dødsdato','date',170,NULL,'Skrives dd-mm-åååå. Er dag eller måned ikke oplyst skriv i stedet 01, hvor oplysningen mangler, fx 01-01-1899, hvor kun årstallet kendes.',NULL,0,'/^(0?[1-9]|[12][0-9]|3[01])[\\/\\-](0?[1-9]|1[012])[\\/\\-]\\d{4}$/','Du skal bruge formatet dd-mm-åååå. Er dag eller måned ikke oplyst så skriv i stedet 01.',1,'dateOfDeath',1,1,'dateOfDeath'),
(21,1,-1,NULL,'burial_persons','yearOfBirth',0,NULL,NULL,0,0,'Udregnet fødselsår','string',0,NULL,'Træk alderen fra dødsåret. Skrives åååå.',NULL,0,'/\\d{4}/','Du skal skrive fødselsåret. Træk alderen fra dødsåret. Skrives åååå.',1,'yearOfBirth',1,0,'no_given'),
(22,1,4,5,'burial_persons','civilstatuses_id',1,'burial_civilstatuses','civilstatus',0,1,'Civilstand','typeahead',160,NULL,'Enten markeret ved at under- eller overstregning af den fortrykte tekst eller i selve teksten.',NULL,0,'/\\w{1,}/','Du skal vælge civilstand fra listen. Enten markeret ved at under- eller overstregning af den fortryk',1,'civilstatus',0,0,'no_given'),
(23,5,5,6,'burial_burials','chapels_id',1,'burial_chapels','chapel',0,1,'Fra (kapel)','typeahead',190,NULL,'Teksten, der står efter \"Fra\". Vær opmærksom på forkortelser.  Findes det der står ikke på listen ikke vælges *skal oprettes',NULL,0,'/\\w{1,}/','Du skal vælge et sted fra listen. Hvis ingen af valgmulighederne matcher, så vælg *skal oprettes.',1,'chapel',0,1,'no_given'),
(24,5,5,10,'burial_burials','parishes_id',1,'burial_parishes','parish',0,1,'Sogn','typeahead',200,NULL,'Tekst, der står til venstre for \"Til\". Hvis dette område er tomt, springes feltet over.',NULL,0,'/\\w{1,}/','Du skal vælge et sogn fra listen. Findes sognet ikke på listen skal du vælge *skal oprettes.',1,'parish',0,1,'no_given'),
(25,5,5,7,'burial_burials','cemetaries_id',1,'burial_cemetaries','cemetary',0,1,'Til kirkegård','typeahead',210,NULL,'Teksten, der står efter \"Til\". Findes kirkegården ikke vælges *skal oprettes',NULL,0,'/\\w{1,}/','Den indtastede kirkegård står ikke på listen. Vælg *skal oprettes.',1,'cemetary',0,1,'no_given'),
(26,1,5,8,'burial_persons','deathplaces_id',1,'burial_deathplaces','deathplace',0,1,'Dødssted','typeahead',181,NULL,'Vælg dødsstedet fra listen, hvis stedet ikke findes så vælg *skal oprettes.',NULL,0,'/\\w{1,}/','Du skal vælge en værdi fra listen.',1,'deathplace',0,0,'no_given'),
(27,NULL,2,9,'burial_persons','birthplace_id',1,'burial_birthplaces','name',0,1,'Fødested','typeahead',NULL,NULL,'Vælg fødestedet fra listen, hvis stedet ikke findes så indtast det pågældende sted.',NULL,0,'/\\w{1,}/','Du skal vælge en værdi fra listen.',1,NULL,0,1,'no_given'),
(28,1,2,NULL,'burial_persons','birthname',0,NULL,NULL,0,1,'Fødenavn (pigenavn)','string',3,NULL,'Her kan skrives afdøde gifte kvinders eller enkers fødenavn (pigenavn)',NULL,0,'/\\w{1,}/','Du skal  skrive et fødenavn.',1,'birthname',1,0,'no_given'),
(30,8,NULL,NULL,'burial_persons','id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'id'),
(31,8,12,NULL,'burial_persons','firstnames',0,NULL,NULL,1,1,'Fornavne','string',1,NULL,'Alle for- og mellemnavne.',NULL,1,'/\\w{1,}/','Du må kun skrive bogstaver.',1,'firstnames',1,1,'firstnames'),
(32,8,12,NULL,'burial_persons','lastname',0,NULL,NULL,0,1,'Efternavn','string',2,NULL,'Skriv kun et efternavn. Alle andre navne indtastes under fornavne.',NULL,1,'/\\w{1,}/','Du må kun skrive bogstaver.',1,'lastname',1,1,'lastname'),
(33,8,12,NULL,'burial_persons','birthname',0,NULL,NULL,0,1,'Fødenavn (pigenavn)','string',3,NULL,'Her kan skrives afdøde gifte kvinders eller enkers fødenavn (pigenavn)',NULL,0,'/\\w{1,}/','Du skal  skrive et fødenavn.',1,'birthname',0,0,'birthname'),
(34,8,12,NULL,'burial_persons','ageYears',0,NULL,NULL,0,1,'Alder - år','number',5,NULL,'Alder. Du kan kun skrive hele tal i feltet. Brug månedsfeltet, hvis du vil taste et halvt år.',NULL,0,'/^\\d{1,3}$/','Du kan kun skrive hele tal i feltet. Brug månedsfeltet, hvis du vil taste et halvt år.',1,'ageYears',0,1,'ageYears'),
(35,8,12,NULL,'burial_persons','ageMonth',0,NULL,NULL,0,1,'Alder - måneder','string',6,'0','Antal måneder. 3/12 tastes som 3',NULL,0,'/^[0-9]+(\\,[0-9]{1,2})?$/','Du skal skrive antal måneder. Halve måneder tastes også fx 0,5',0,'ageMonth',0,1,'ageMonth'),
(36,8,12,NULL,'burial_persons','dateOfBirth',0,NULL,NULL,1,0,'Fødselsår','date',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,1,'dateOfBirth'),
(37,8,14,NULL,'burial_persons','dateOfDeath',0,NULL,NULL,1,1,'Dødsdato','date',170,NULL,'Skrives dd-mm-åååå. Er dag eller måned ikke oplyst skriv i stedet 01, hvor oplysningen mangler, fx 01-01-1899, hvor kun årstallet kendes.',NULL,0,'/^(0?[1-9]|[12][0-9]|3[01])[\\/\\-](0?[1-9]|1[012])[\\/\\-]\\d{4}$/','Du skal bruge formatet dd-mm-åååå. Er dag eller måned ikke oplyst så skriv i stedet 01.',1,'dateOfDeath',0,0,'dateOfDeath'),
(38,8,0,NULL,'burial_persons','deathplaces_id',0,NULL,NULL,0,0,'Dødssted','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',0,'',0,0,'deathplaces_id'),
(39,8,NULL,NULL,'burial_persons','civilstatuses_id',0,NULL,NULL,1,0,'Civilstand','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'civilstatuses_id'),
(40,8,NULL,NULL,'burial_persons','birthplaces_id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'birthplaces_id'),
(41,8,12,NULL,'burial_persons','birthplaceOther',0,NULL,NULL,0,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'birthplaceOther'),
(42,8,14,NULL,'burial_persons','yearOfBirth',0,NULL,NULL,0,0,'Udregnet fødselsår','string',0,NULL,'Træk alderen fra dødsåret. Skrives åååå.',NULL,0,'/\\d{4}/','Du skal skrive fødselsåret. Træk alderen fra dødsåret. Skrives åååå.',1,'yearOfBirth',0,0,'yearOfBirth'),
(43,8,13,NULL,'burial_persons','adressOutsideCph',0,NULL,NULL,0,1,'Resten af Danmark/verden','string',120,NULL,'Alle adresser uden for Københavm Frederiksberg og Gentofte. Skriv hele adressen eller stednavnet som det står. Udfyldes dette felt skal de ovenstående felter til gadenavn mv. ikke udfyldes.',NULL,0,'/\\w{1,}/','Du skal kun udfylde dette felt, hvis adressen ikke er i København, Frederiksberg eller Gentofte.',1,'adressOutsideCph',0,0,'adressOutsideCph'),
(44,9,NULL,NULL,'burial_addresses','id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'id'),
(45,9,13,3,'burial_addresses','streets_id',1,NULL,NULL,0,1,'Gade','typeahead',70,NULL,'Vælg et gadenavn fra listen. Hvis den ikke findes på listen så vælg *Skal oprettes',NULL,0,'/\\w{1,}/','Du skal vælge en gade fra listen. Hvis gaden ikke er på listen, så vælges *skal oprettes.',0,'streets',1,0,'streets_id'),
(46,9,13,NULL,'burial_addresses','number',0,NULL,NULL,0,1,'Gadenummer','number',80,NULL,'Husnummer.',NULL,0,'/^\\d{1,3}$/','Du skal skrive et tal. Bogstavangivelser i forbindelse med husnummeret skrives i feltet nedenfor.',1,'roadNumber',0,0,'number'),
(47,9,13,NULL,'burial_addresses','letter',0,NULL,NULL,0,1,'Gadenummerbogstav','string',90,NULL,'Bogstavangivelse i forbindelse med husnummer.',NULL,0,'/^[a-zA-Z]{1}$/','Du skal skrive et bogstav.',1,'roadLetter',0,0,'letter'),
(48,9,NULL,NULL,'burial_addresses','floors_id',0,NULL,NULL,1,0,'Etage','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'floors_id'),
(49,9,NULL,NULL,'burial_addresses','persons_id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'persons_id'),
(50,10,NULL,NULL,'burial_floors','id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'id'),
(51,10,13,4,'burial_floors','floor',0,NULL,NULL,0,1,'Etage','string',100,NULL,'Er ofte angivet med romertal.',NULL,0,'/\\w{1,}/','Du skal vælge en etage fra listen.',0,NULL,0,0,'floor'),
(52,11,NULL,NULL,'burial_streets','id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'id'),
(53,11,13,3,'burial_streets','street',0,NULL,NULL,0,0,'Gade','string',NULL,NULL,'Vælg et gadenavn fra listen. Hvis den ikke findes på listen så vælg *Skal oprettes',NULL,0,'/\\w{1,}/','Du skal vælge en gade fra listen. Hvis gaden ikke er på listen, så vælges *skal oprettes.',0,'streets',1,0,'street'),
(54,11,NULL,NULL,'burial_streets','code',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'code'),
(55,11,NULL,NULL,'burial_streets','hoods_id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'hoods_id'),
(56,11,NULL,NULL,'burial_streets','hood',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'hood'),
(57,11,13,3,'burial_streets','streetAndHood',0,NULL,NULL,0,1,'Gade','string',70,NULL,'Vælg et gadenavn fra listen. Hvis den ikke findes på listen så vælg *Skal oprettes',NULL,0,'/\\w{1,}/','Du skal vælge en gade fra listen. Hvis gaden ikke er på listen, så vælges *skal oprettes.',1,'streets',1,0,'streetAndHood'),
(58,12,NULL,NULL,'burial_hoods','id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'id'),
(59,12,NULL,NULL,'burial_hoods','hood',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'hood'),
(60,13,NULL,NULL,'burial_birthplaces','id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'id'),
(61,13,NULL,NULL,'burial_birthplaces','name',0,NULL,NULL,0,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'name'),
(62,14,NULL,NULL,'burial_burials','id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'id'),
(64,14,NULL,NULL,'burial_burials','cemetaries_id',0,NULL,NULL,1,0,'Til kirkegård','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'cemetaries_id'),
(65,14,NULL,NULL,'burial_burials','chapels_id',0,NULL,NULL,1,0,'Fra (kapel)','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'chapels_id'),
(66,14,NULL,NULL,'burial_burials','parishes_id',0,NULL,NULL,1,0,'Sogn','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'parishes_id'),
(67,14,NULL,NULL,'burial_burials','persons_id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'persons_id'),
(68,15,NULL,NULL,'burial_cemetaries','id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'id'),
(69,15,15,7,'burial_cemetaries','cemetary',0,NULL,NULL,0,1,'Til kirkegård','typeahead',210,NULL,'Teksten, der står efter \"Til\". Findes kirkegården ikke vælges *skal oprettes',NULL,0,'/\\w{1,}/','Den indtastede kirkegård står ikke på listen. Vælg *skal oprettes.',1,'cemetary',0,0,'cemetary'),
(70,16,NULL,NULL,'burial_chapels','id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'id'),
(71,16,15,6,'burial_chapels','chapel',0,NULL,NULL,0,1,'Fra (kapel)','typeahead',190,NULL,'Teksten, der står efter \"Fra\". Vær opmærksom på forkortelser.  Findes det der står ikke på listen ikke vælges *skal oprettes',NULL,0,'/\\w{1,}/','Du skal vælge et sted fra listen. Hvis ingen af valgmulighederne matcher, så vælg *skal oprettes.',1,'chapel',0,0,'chapel'),
(72,17,NULL,8,'burial_deathplaces','id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'id'),
(73,0,5,NULL,'burial_deathplaces','deathplace',0,NULL,NULL,0,0,'Dødssted','typeahead',NULL,NULL,'Vælg dødsstedet fra listen, hvis stedet ikke findes så indtast det pågældende sted.',NULL,0,'/\\w{1,}/','Du skal vælge en værdi fra listen.',1,'deathPlace',0,0,'deathplace'),
(74,18,NULL,NULL,'burial_civilstatuses','id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'id'),
(75,18,14,5,'burial_civilstatuses','civilstatus',0,NULL,NULL,1,1,'Civilstand','typeahead',160,NULL,'Enten markeret ved at under- eller overstregning af den fortrykte tekst eller i selve teksten.',NULL,0,'/\\w{1,}/','Du skal vælge civilstand fra listen. Enten markeret ved at under- eller overstregning af den fortryk',1,'civilstatus',0,0,'civilstatus'),
(76,19,NULL,NULL,'burial_persons_deathcauses','id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'id'),
(77,19,NULL,NULL,'burial_persons_deathcauses','persons_id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'persons_id'),
(78,19,NULL,NULL,'burial_persons_deathcauses','deathcauses_id',0,NULL,NULL,1,0,'Dødsårsag','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'deathcauses_id'),
(79,20,NULL,NULL,'burial_deathcauses','id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'id'),
(80,20,15,2,'burial_deathcauses','deathcause',0,NULL,NULL,0,1,'Dødsårsag','typeahead',180,NULL,'Hvis dødsårsagen ikke matcher en af valgmulighederne på listen, skal du vælge *skal oprettes. Tilføj flere dødsårsager',NULL,0,'/\\w{1,}/','Du skal vælge en dødsårsag fra listen. Hvis ingen af valgmulighederne matcher, så vælg *skal oprette',1,'deathcauses',0,0,'deathcause'),
(81,21,NULL,NULL,'burial_persons_positions','id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'id'),
(82,21,NULL,NULL,'burial_persons_positions','persons_id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'persons_id'),
(83,21,NULL,NULL,'burial_persons_positions','positions_id',0,NULL,NULL,1,0,'Erhverv','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'positions_id'),
(84,21,NULL,NULL,'burial_persons_positions','relationtypes_id',0,NULL,NULL,1,0,'Relation til erhverv','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'relationtypes_id'),
(85,22,NULL,NULL,'burial_positions','id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'id'),
(86,22,14,1,'burial_positions','position',0,NULL,NULL,0,1,'Erhverv','typeahead',130,NULL,'Forstået som stilling, ikke civilstand.',NULL,0,'/\\w{1,}/','Du skal vælge et erhverv fra listen. Findes erhvervet ikke på listen, så vælg *skal oprettes.',1,'positions',0,0,'position'),
(87,23,NULL,NULL,'burial_relationtypes','id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'id'),
(88,23,14,11,'burial_relationtypes','relationtype',0,NULL,NULL,0,1,'Relation til erhverv','string',140,'Eget erhverv','Den afdødes relation til erhvervet.',NULL,0,'/\\w{1,}/','Skal udfyldes, hvis erhverv er udfyldt.',0,NULL,0,0,'relationtype'),
(89,24,NULL,NULL,'burial_parishes','id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'id'),
(90,24,15,10,'burial_parishes','parish',0,NULL,NULL,0,1,'Sogn','typeahead',200,NULL,'Tekst, der står til venstre for \"Til\". Hvis dette område er tomt, springes feltet over.',NULL,0,'/\\w{1,}/','Du skal vælge et sogn fra listen. Findes sognet ikke på listen skal du vælge *skal oprettes.',1,'parish',0,0,'parish'),
(91,24,NULL,NULL,'burial_parishes','fromYear',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,0,'fromYear'),
(92,1,2,12,'burial_persons','sex_id',1,'burial_persons_sex','sex',0,1,'Køn','typeahead',4,NULL,'Angiv om personen er mand, kvinde eller vælg ukendt, hvis det ikke kan sluttes ud fra navn og evt. erhverv',NULL,0,'/\\w{1,}/','Du skal vælge køn fra listen.',1,'sex',0,1,'sex'),
(93,27,12,NULL,'burial_persons_sex','sex',0,NULL,NULL,0,1,'Køn','typeahead',80,NULL,'Personens køn',NULL,0,'/\\w{1,}/','Du må kun skrive bogstaver.',1,'sex',0,0,'sex'),
(94,5,2,NULL,'burial_burials','number',0,NULL,NULL,1,1,'Løbenummer','number',0,NULL,'Begravelsensnummer. Det står som det første. Indtast kun tal.',NULL,0,'/^\\d{1,5}$/','Du må kun skrive tal.',1,'record_number',0,1,'number'),
(95,14,12,NULL,'burial_burials','number',0,NULL,NULL,1,1,'Løbenummer','number',0,NULL,'Begravelsensnummer. Det står som det første. Indtast kun tal.',NULL,0,'/^\\d{1,5}$/','Du må kun skrive tal.',1,'record_number',0,1,'number'),
(96,4,3,13,'burial_addresses','institutions_id',1,'burial_institutions','institution',0,1,'Institution/sted','typeahead',110,NULL,'Skal udfyldes hvis adressen er en institution og ikke et gadenavn.',NULL,0,'/\\w{1,}/','Du skal vælge en institution eller sted fra listen.',1,'institutions',0,1,'institution'),
(97,9,NULL,NULL,'burial_addresses','institutions_id',0,NULL,NULL,0,0,'Institution/sted','string',NULL,NULL,'Skal udfyldes hvis adressen er en institution og ikke et gadenavn.',NULL,0,'/\\w{1,}/','Du skal vælge en institution eller sted fra listen.',0,NULL,0,0,'institutions_id'),
(98,25,3,13,'burial_institutions','institution',0,NULL,NULL,0,1,'Institution/sted','typeahead',110,NULL,'Skal udfyldes hvis adressen er en institution og ikke et gadenavn.',NULL,0,'/\\w{1,}/','Du skal vælge en institution eller sted fra listen.',1,'institutions',0,0,'institution'),
(99,25,NULL,NULL,'burial_institutions','id',0,NULL,NULL,1,0,'formName','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/','Feltet skal udfyldes',0,NULL,0,0,'id'),
(100,1,5,NULL,'burial_persons','comment',0,NULL,NULL,0,1,'Kommentar','string',220,NULL,'Kommentar må kun tilføjes i overensstemmelse med indtastningsvejledningen.',NULL,0,'/\\w{1,}/','Du må kun skrive bogstaver.',1,'comments',0,0,'comment'),
(101,8,15,NULL,'burial_persons','comment',0,NULL,NULL,0,1,'Kommentar','string',220,NULL,'Kommentar må kun tilføjes i overensstemmelse med indtastningsvejledningen.',NULL,0,'/\\w{1,}/','Du må kun skrive bogstaver.',1,'comments',0,0,'comment'),
(102,3,4,14,'burial_persons_positions','workplaces_id',1,'burial_workplaces','workplace',0,1,'Arbejdssted','typeahead',150,NULL,'Evt. arbejdssted eller organisation.',NULL,0,'/\\w{1,}/','Kan udfyldes, hvis erhverv er udfyldt.',1,'workplace',0,0,'workplace'),
(103,21,NULL,NULL,'burial_persons_positions','workplaces_id',0,NULL,NULL,0,0,'Arbejdssted','typeahead',150,NULL,NULL,NULL,0,'/\\w{1,}/','Kan udfyldes, hvis erhverv er udfyldt.',0,NULL,0,0,'workplaces_id'),
(104,26,NULL,14,'burial_workplaces','workplace',0,NULL,NULL,0,1,'Arbejdssted','typeahead',150,NULL,'Evt. arbejdssted eller organisation.',NULL,0,'/\\w{1,}/','Kan udfyldes, hvis erhverv er udfyldt.',1,'workplace',0,0,'workplace'),
(106,27,12,NULL,'burial_persons_sex','id',0,NULL,NULL,0,0,'Nyt felt','string',NULL,NULL,NULL,NULL,0,'/\\w{1,}/',NULL,NULL,NULL,0,0,'id'),
(107,1,2,NULL,'burial_persons','ageWeeks',0,NULL,NULL,0,1,'Alder - uger','string',61,'0','Antal uger',NULL,0,'/^[0-9]+(\\,[0-9]{1,2})?$/','Du skal skrive antal uger. Halve uger tastes også fx 0,5',0,'ageWeeks',0,1,'ageWeeks'),
(108,8,12,NULL,'burial_persons','ageWeeks',0,NULL,NULL,0,1,'Alder - uger','string',61,'0','Antal uger',NULL,0,'/^[0-9]+(\\,[0-9]{1,2})?$/','Du skal skrive antal uger. Halve uger tastes også fx 0,5',0,'ageWeeks',0,1,'ageWeeks'),
(109,1,2,NULL,'burial_persons','ageDays',0,NULL,NULL,0,1,'Alder - dage','string',62,'0','Antal dage',NULL,0,'/^[0-9]+(\\,[0-9]{1,2})?$/','Du skal skrive antal dage. Halve dage taste fx 0,5',0,'ageDays',0,1,'ageDays'),
(110,8,12,NULL,'burial_persons','ageDays',0,NULL,NULL,0,1,'Alder - dage','string',62,'0','Antal dage',NULL,0,'/^[0-9]+(\\,[0-9]{1,2})?$/','Du skal skrive antal dage. Halve dage taste fx 0,5',0,'ageDays',0,1,'ageDays'),
(111,1,2,NULL,'burial_persons','ageHours',0,NULL,NULL,0,1,'Alder - timer','string',63,'0','Antal timer',NULL,0,'/^\\d{1,3}$/','Du skal skrive en hel time.',0,'ageHours',0,1,'ageHours'),
(112,8,12,NULL,'burial_persons','ageHours',0,NULL,NULL,0,1,'Alder - timer','string',63,'0','Antal timer',NULL,0,'/^\\d{1,3}$/','Du skal skrive en hel time.',0,'ageHours',0,1,'ageHours'),
(113,1,2,NULL,'burial_persons','freetext_store',0,NULL,NULL,0,0,'Fritekst','string',1,NULL,'Fritekstsøgning',NULL,0,'/\\w{1,}/','',0,'freetext_store',0,0,'no_given');
/*!40000 ALTER TABLE `apacs_fields` ENABLE KEYS */;

UNLOCK TABLES;

