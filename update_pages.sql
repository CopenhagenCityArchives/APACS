# Opdater s3_key baseret på image_url for filer der allerede ligger i s3
update apacs_pages set s3_key = SUBSTRING_INDEX(image_url, "/", -1) where s3=1;
# Opdater s3_key for filer der ikke tidligere lå s3
update apacs_pages set s3_key = CONCAT('collections/', relative_filename_converted)  where s3=0;
# Opdater s3_bucket baseret på archive_id
UPDATE apacs_pages, apacs_units SET apacs_pages.s3_bucket = 'frbkilder' WHERE apacs_units.id = apacs_pages.unit_id AND apacs_units.collections_id IN (148, 149);
UPDATE apacs_pages, apacs_units SET apacs_pages.s3_bucket = 'kbhkilder' WHERE apacs_units.id = apacs_pages.unit_id AND apacs_units.collections_id NOT IN (148, 149);

# Husk at lave stats.loadTime om til DECIMAL(10,5)
ALTER TABLE `apacs`.`stats` 
CHANGE COLUMN `loadTime` `loadTime` DECIMAL(10,5) NULL DEFAULT NULL ;

#Husk at tilføje index på s3_key
ALTER TABLE `apacs`.`apacs_pages` 
CHANGE COLUMN `s3_key` `s3_key` CHAR(255) NULL DEFAULT NULL ,
ADD INDEX `s3_key` (`s3_key` ASC) VISIBLE;
;


