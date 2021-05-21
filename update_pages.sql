# Opdater s3_key baseret på image_url for filer der allerede ligger i s3
update apacs_pages set s3_key = SUBSTRING_INDEX(image_url, "/", -1) where s3=1;
# Opdater s3_key for filer der ikke tidligere lå s3
update apacs_pages set s3_key = CONCAT('collections/', relative_filename_converted)  where s3=0;
# Opdater s3_bucket baseret på archive_id
UPDATE apacs_pages, apacs_units SET apacs_pages.s3_bucket = 'frbkilder' WHERE apacs_units.id = apacs_pages.unit_id AND apacs_units.collection_id IN (148, 149);
UPDATE apacs_pages, apacs_units SET apacs_pages.s3_bucket = 'kbhkilder' WHERE apacs_units.id = apacs_pages.unit_id AND apacs_units.collection_id NOT IN (148, 149);

# Husk at lave stats.loadTime om til DECIMAL(10,5)