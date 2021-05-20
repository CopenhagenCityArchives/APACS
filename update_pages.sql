UPDATE apacs_pages, apacs_units 
SET
	apacs_pages.s3_bucket = 'kbhkilder',
	apacs_pages.s3_key = CONCAT('collections/', relative_filename_converted)
WHERE 
	apacs_units.id = apacs_pages.unit_id 
	AND apacs_units.collection_id NOT IN (148, 149);

UPDATE apacs_pages, apacs_units
SET
	apacs_pages.s3_bucket = 'frbkilder',
	apacs_pages.s3_key = relative_filename_converted
WHERE
	apacs_units.id = apacs_pages.unit_id 
	AND apacs_units.collection_id IN (148, 149);
