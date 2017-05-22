<?php
/**
 * The uber configuration
 * Contains the following:
 *
 * Datalevels
 * The data level is the last level of metadata before the actual image files. The data levels can as such be seen as a image collector.
 * If the collection doesn't exists of smaller collections of images, the relation between the images and the data level is one-to-one.
 * TODO: This may be reduced to a single SQL connecting the data objects and the images.
 *
 * Metadatalevels
 * The metadatalevels contains the levels of metadata concerning the sources. Each source is expected to have each level of metadata.
 * The metadata levels can be arranged as either hierarchial or flat.
 * In the first type, each level most be filled before the next (ordered by the 'order' value)
 * In the second type the levels are independent of each other, so the values in one level does not depend on values in other levels
 *
 * Each filter contains list of possible values (id and name). These values can be received from the server in three different ways (controlled by the 'type' value):
 * preset: The possible values are included in the level in the 'data' array (which is otherwise false)
 * getallbyfilter: The possible values are loaded at once from the server (in hierarchial metadata levels they are load based on the selected values of the higher ranking filters
 * typeahead: The values are loaded as the user types in the field
 *
 */

/*
 * New structure
 */

$collectionsSettings = array(

	/**
	 * Politiets mandtal
	 */
	array(
		'id' => 2,
		'test' => false,
		'info' => 'Københavns befolkning over 10 år er registreret i mandtallerne. Vælg gade, år og måned, hvorefter du kan bladre dig frem til husnummer',
		'link' => 'http://www.kbharkiv.dk/sog-i-arkivet/kilder-pa-nettet/politiets-mandtaller',
		'short_name' => 'Politiets Mandtal',
		'long_name' => 'Politiets Mandtal for København 1866 - 1923',
		'gui_required_fields_text' => 'Vælg minimum gade og år',
		//How to link the data level objects to images
		'objects_query' => 'select files.id, CONCAT(\'/collections/mandtal\',path) as imageURL, year, month, name FROM files LEFT JOIN volumes ON files.volume_id = volumes.id LEFT JOIN streets on volumes.street_id = streets.id WHERE :query ORDER BY year, month, files.id',
		'primary_table_name' => 'files',
		'starbas_field_name' => false,
		'levels_type' => 'hierarchy',
		'levels' => array(
			array(
				'order' => 3,
				'gui_name' => 'Måned',
				'gui_description' => 'Mandtallerne blev ført to gange årligt',
				'gui_info_link' => false,
				'name' => 'month',
				'gui_type' => 'typeahead',
				//'data_sql' => 'SELECT DISTINCT month as text, month as id FROM files LEFT JOIN volumes ON files.volume_id = volumes.id LEFT JOIN error_file ON error_file.file_id = files.id LEFT JOIN streets ON volumes.street_id = streets.id WHERE error_file.file_id IS NULL AND streets.name = "%s" AND volumes.year = "%s" order by month',
				'data_sql' => 'SELECT DISTINCT volumes.month as text, volumes.month as id FROM volumes LEFT JOIN streets ON streets.id = volumes.street_id LEFT JOIN files ON files.volume_id = volumes.id LEFT JOIN error_file ON error_file.file_id = files.id WHERE streets.name = "%s" AND volumes.year = "%s" GROUP BY volumes.id HAVING SUM(error_file.file_id IS NOT NULL) = 0 AND SUM(files.id) <> 0 ORDER BY volumes.month',
				'data' => false,
				'searchable' => true,
				'required_levels' => array('name', 'year'),
				'gui_hide_name' => true,
				'required' => false,
			),
			array(
				'order' => 2,
				'gui_name' => 'År',
				'gui_description' => 'Mandtallerne blev ført fra 1866 til 1923',
				'gui_info_link' => 'http://www.kbharkiv.dk/mandtaller',
				'name' => 'year',
				'gui_type' => 'typeahead',
				//'data_sql' => 'SELECT DISTINCT year as text, year as id FROM files LEFT JOIN volumes ON files.volume_id = volumes.id LEFT JOIN error_file ON error_file.file_id = files.id LEFT JOIN streets ON volumes.street_id = streets.id WHERE error_file.file_id IS NULL AND streets.name = "%s" ORDER BY year', // WHERE :query',//'select id, year as text FROM mand_years',
				'data_sql' => 'SELECT DISTINCT volumes.year as text, volumes.year as id FROM volumes LEFT JOIN streets ON streets.id = volumes.street_id LEFT JOIN files ON files.volume_id = volumes.id LEFT JOIN error_file ON error_file.file_id = files.id WHERE streets.name = "%s" GROUP BY volumes.id HAVING SUM(error_file.file_id IS NOT NULL) = 0 AND SUM(files.id) <> 0 ORDER BY volumes.year',
				'data' => false,
				'gui_hide_name' => true,
				'required' => false,
				'searchable' => true,
				'required_levels' => array('name'),
			),
			array(
				'order' => 1,
				'gui_name' => 'Gadenavn',
				'gui_description' => 'Gader, der optræder i mandtallerne',
				'gui_info_link' => 'http://www.kbharkiv.dk/mandtaller',
				'name' => 'name',
				'gui_type' => 'typeahead',
				//'data_sql' => 'SELECT DISTINCT streets.id AS id, streets.name as text FROM files LEFT JOIN volumes ON files.volume_id = volumes.id LEFT JOIN error_file ON error_file.file_id = files.id JOIN streets ON volumes.street_id = streets.id WHERE volumes.id IS NOT NULL AND error_file.error_id IS NULL AND volumes.id IS NOT NULL AND streets.id IS NOT NULL ORDER BY text', // navn LIKE \'%s%%\'',
				'data_sql' => 'SELECT DISTINCT streets.name as text, streets.name as id FROM streets INNER JOIN volumes ON streets.id = volumes.street_id INNER JOIN files ON files.volume_id = volumes.id LEFT JOIN error_file ON error_file.file_id = files.id WHERE error_file.file_id IS NULL ORDER BY streets.name',
				'data' => false,
				'gui_hide_name' => true,
				'required' => true,
				'searchable' => true,
				'required_levels' => false, //array('streetname')
			),
		),
		'error_intro' => 'Har du opdaget, at et billede er registreret forkert eller ikke passer ind, kan du give os besked. Når du fejlmelder et billede, fejlmelder du hele mandtallet. Tak for hjælpen.',
		'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
		'error_reports' => array(
			array(
				'id' => 1,
				'name' => 'Gade, årstal eller måned er forkert',
				'sql' => 'INSERT IGNORE INTO error_file (file_id, error_id) VALUES (:itemId, 9)',
				'order' => 1,
			),
			array(
				'id' => 2,
				'name' => 'Viser ikke et mandtal',
				'sql' => 'INSERT IGNORE INTO error_file (file_id, error_id) VALUES (:itemId, 8)',
				'order' => 2,
			),
		),
	),

	/**
	 * Kort og tegninger
	 */
	array(
		'id' => 3,
		'test' => false,
		'info' => 'Udvalgte kort og tegninger fra Stadsarkivets samling.',
		'link' => 'http://www.kbharkiv.dk/sog-i-arkivet/kilder-pa-nettet/kort-og-tegninger',
		'short_name' => 'Kort og tegninger',
		'long_name' => 'Københavns Stadsarkivs digitaliserede kort og tegninger',
		'gui_required_fields_text' => 'Vælg en kategori for at fortsætte',
		'image_type' => 'tile',
		'primary_table_name' => 'kortteg_files',
		'starbas_field_name' => 'av_stam_id',
		//How to link the data level objects to images
		'objects_query' => 'select kortteg_files.id, CONCAT(\'/collections/kortteg/\',fileName) as imageURL, year, height, width,
                        description,
                        av_beskrivelse,
                        av_stam_id,
                        if(av_aar_fra is not null AND av_aar_fra != 0, CONCAT(av_aar_fra, " - ", av_aar_til), av_aar) as time_desc
                        FROM kortteg_files
                        LEFT JOIN kortteg_new_tags ON kortteg_files.new_tag_id = kortteg_new_tags.id
                        LEFT JOIN kortteg_starbas_data on kortteg_files.eksemplar_id = kortteg_starbas_data.eks_id
                        WHERE :query',
		'levels_type' => 'flat',
		'levels' => array(
			//Kategori, søgebar
			array(
				'order' => 1,
				'gui_name' => 'Kategori',
				'gui_description' => 'Kort og tegninger er kategoriseret efter indhold',
				'gui_info_link' => false,
				'name' => 'description',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT id, description as text FROM kortteg_new_tags WHERE is_used = 1',
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => false,
				'required' => false,
				'searchable' => true,
				'required_levels' => false,
			),
			//Beskrivelse, ikke søgebar
			array(
				'order' => 3,
				'gui_name' => 'besk',
				'gui_description' => '',
				'gui_info_link' => false,
				'name' => 'av_beskrivelse',
				'gui_type' => 'preset',
				'data_sql' => "sesf",
				'data' => false,
				'gui_hide_name' => true,
				'required' => false,
				'searchable' => false,
				'required_levels' => false,
			),
			//Starbas-reference, ikke søgebar
			array(
				'order' => 4,
				'gui_name' => '',
				'gui_description' => '',
				'gui_info_link' => false,
				'name' => 'av_stam_id',
				'gui_type' => 'preset',
				'data_sql' => "sesf",
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => true,
				'required' => false,
				'searchable' => false,
				'required_levels' => false,
			),
			//Årstal eller spænd, afhængig af data, ikke søgebart
			array(
				'order' => 2,
				'gui_name' => 'time_desc',
				'gui_description' => '',
				'gui_info_link' => false,
				'name' => 'time_desc',
				'gui_type' => 'preset',
				'data_sql' => "sc",
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => false,
				'required' => false,
				'searchable' => false,
				'required_levels' => false,
			),
		),
		'error_intro' => 'Har du opdaget en fejl i et kort eller en beskrivelse, kan du give os besked.',
		'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
		'error_reports' => array(
			array(
				'id' => 1,
				'name' => 'Billedet vender forkert',
				'sql' => 'UPDATE kortteg_files SET error_rotation = 1 WHERE id = :itemId LIMIT 1',
				'order' => 1,
			),
			array(
				'id' => 2,
				'name' => 'Beskrivelsen passer ikke',
				'sql' => 'UPDATE kortteg_files SET error_metadata = 1 WHERE id = :itemId LIMIT 1',
				'order' => 2,
			),
		),
	),

	/**
	 * Skoleprotokoller, protokoller
	 */
	array(
		'id' => 4,
		'test' => true,
		'info' => 'Skoleprotokoller fra københavnske skoler',
		'link' => false,
		'short_name' => 'Skoleprotokoller',
		'long_name' => 'Skoleprotokoller fra københavnske skoler',
		'gui_required_fields_text' => 'Vælg en skole for at fortsætte',
		'primary_table_name' => 'kortteg_files',
		//How to link the data level objects to images
		// 'objects_query' => 'select tblSkoleKilde.id, CONCAT(\'/collections/kortteg/\',fileName) as imageURL from tblskoleprotokol_images WHERE :query',
		'objects_query' => 'SELECT SkoleKildeOpslagId as id, Navn, Kildenavn, kilde.AarstalTil, kilde.AarstalFra, skole.SkoleId as skoleid, skole.navn as skole, opslag.Kildeid as kildeid, kilde.SkoleKildeID as SkoleKildeId, CONCAT(\'/collections/skoleprotokoller/\',FuldFilNavn) as imageURL
                        FROM tblSkoleKildeOpslag as opslag
                        LEFT JOIN tblSkoleKilde as kilde ON opslag.kildeid = kilde.SkoleKildeId
                        LEFT JOIN tblSkole as skole ON kilde.SkoleId = skole.SkoleId
                        WHERE :query',
		'levels_type' => 'hierarchy',
		'levels' => array(
			//Skole id
			array(
				'order' => 1,
				'gui_name' => 'Skole',
				'gui_description' => 'Protokollerne er arrangeret efter skole',
				'gui_info_link' => false,
				'name' => 'skoleid',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT SkoleId as id, Navn as text FROM tblSkole',
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => false,
				'required' => false,
				'searchable' => false,
				'required_levels' => false,
			),
			//Skolenavn, søgebar
			array(
				'order' => 2,
				'gui_name' => 'Skole',
				'gui_description' => 'Protokollerne er arrangeret efter skole',
				'gui_info_link' => false,
				'name' => 'skole',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT SkoleId as id, Navn as text FROM tblSkole',
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => false,
				'required' => false,
				'searchable' => true,
				'required_levels' => false,
			),
			//Protokol id, søgebar
			array(
				'order' => 3,
				'gui_name' => 'Protokol',
				'gui_description' => 'Hver skole har et antal protokoller',
				'gui_info_link' => false,
				'name' => 'SkoleKildeId',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT SkoleKildeId as id, CONCAT(Kildenavn, " ", AarstalFra, "-", AarstalTil) as text FROM tblSkoleKilde WHERE register = 0 AND SkoleId = "%d"',
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => false,
				'required' => true,
				'searchable' => true,
				'required_levels' => array('skole'),
			),
			//Protokol navn, søgebar
			array(
				'order' => 4,
				'gui_name' => 'Protokol',
				'gui_description' => 'Hver skole har et antal protokoller',
				'gui_info_link' => false,
				'name' => 'Kildenavn',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT SkoleKildeId as id, CONCAT(Kildenavn, " ", AarstalFra, "-", AarstalTil) as text FROM tblSkoleKilde WHERE register = 0 AND SkoleId = "%d"',
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => false,
				'required' => false,
				'searchable' => false,
				'required_levels' => array('skole'),
			),
		),
		'error_intro' => 'Har du opdaget en fejl på et billede eller i metadata, kan du give os besked.',
		'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
		'error_reports' => array(
			array(
				'id' => 1,
				'name' => 'Billedet kan ikke ses',
				'sql' => 'UPDATE kortteg_files SET error_rotation = 1 WHERE id = :itemId LIMIT 1',
				'order' => 1,
			),
			array(
				'id' => 2,
				'name' => 'Metadata er forkert',
				'sql' => 'UPDATE kortteg_files SET error_description = 1 WHERE id = :itemId LIMIT 1',
				'order' => 2,
			),
		),
	),

	/**
	 * Begravelser, protokoller
	 */
	array(
		'id' => 5,
		'test' => true,
		'info' => 'Brug år og nummer fra  det alfabetiske register til at finde begravelsen.',
		'link' => 'http://www.kbharkiv.dk/sog-i-arkivet/kilder-pa-nettet/begravelser',
		'short_name' => 'Begravelsesprotokoller 1861-1942',
		'long_name' => 'Begravelsesprotokoller 1861-1942',
		'gui_required_fields_text' => 'Vælg et år',
		'image_type' => 'image',
		'primary_table_name' => 'apacs_pages',
		//   'starbas_field_name' => 'starbas_id',
		//How to link the data level objects to images
		'objects_query' => 'select apacs_pages.id, year, nicetitle, apacs_pages.starbas_id, CONCAT(\'/getfile.php?fileId=\', apacs_pages.id) as imageURL
                        FROM apacs_pages
                        LEFT JOIN begrav_volume ON apacs_pages.volume_id = begrav_volume.id
                        LEFT JOIN begrav_volume_years ON begrav_volume.id = begrav_volume_years.volume_id
                        WHERE volumetype_id = 1 AND is_public = 1 AND :query',
		'levels_type' => 'hierarchy',
		'levels' => array(
			//År, søgebar
			array(
				'order' => 1,
				'gui_name' => 'År',
				'gui_description' => 'Året for protokollen',
				'gui_info_link' => false,
				'name' => 'year',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT DISTINCT id, text FROM begrav_help_years where is_public = 1',
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => true,
				'required' => true,
				'searchable' => true,
				'required_levels' => false,
			),
			//Periode, søgebar
			array(
				'order' => 2,
				'gui_name' => 'Periode',
				'gui_description' => 'Hvert år er inddelt i flere perioder',
				'gui_info_link' => false,
				'name' => 'nicetitle',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT begrav_volume.id as id, nicetitle as text FROM begrav_volume LEFT JOIN begrav_volume_years ON begrav_volume.id = begrav_volume_years.volume_id WHERE volumetype_id = 1 AND is_public = 1 AND year = %d ORDER BY volume_sort' /* volumetype_id = 1 AND %d <= year_to AND %d >= year_from"*/,
				'data' => false,
				'gui_hide_name' => true,
				'required' => false,
				'searchable' => true,
				'required_levels' => array('year'),
			),
			//Starbas-reference, ikke søgebar
			array(
				'order' => 3,
				'gui_name' => '',
				'gui_description' => '',
				'gui_info_link' => false,
				'name' => 'starbas_id',
				'gui_type' => 'preset',
				'data_sql' => "sesf",
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => true,
				'required' => false,
				'searchable' => false,
				'required_levels' => false,
			),
		),
		'error_intro' => 'Har du opdaget en fejl kan du give os besked.',
		'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
		/*'error_reports' => array(
			            array(
			                'id' => 1,
			                'name' => 'Billedet er ikke en begravelsesprotokol',
			                'sql' => 'UPDATE apacs_pages SET error_image = 1 WHERE id = :itemId LIMIT 1',
			                'order' => 1
			            )
		*/
	),

	/**
	 * Begravelser, registre
	 */
	array(
		'id' => 6,
		'test' => true,
		'info' => 'Alfabetisk navneregister. Noter år og nummer ved den valgte person.',
		'link' => 'http://www.kbharkiv.dk/sog-i-arkivet/kilder-pa-nettet/begravelser',
		'short_name' => 'Registre til begravelsesprotokoller 1861-1942',
		'long_name' => 'Registre til begravelsesprotokoller 1861-1942',
		'gui_required_fields_text' => 'Vælg et år',
		'image_type' => 'image',
		'primary_table_name' => 'apacs_pages',
		//How to link the data level objects to images
		'objects_query' => 'SELECT DISTINCT apacs_pages.id, riv_1, sex, apacs_pages.starbas_id, nicetitle, CONCAT(\'/getfile.php?fileId=\', apacs_pages.id) as imageURL
                        FROM apacs_pages
                        LEFT JOIN begrav_volume ON apacs_pages.volume_id = begrav_volume.id
                        WHERE volumetype_id = 2 AND is_public = 1 AND :query',
		'levels_type' => 'hierarchy',
		'levels' => array(
			//Periode, søgebar
			array(
				'order' => 1,
				'gui_name' => 'Køn',
				'gui_description' => 'Hvert år er opdelt efter køn',
				'gui_info_link' => false,
				'name' => 'sex',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT DISTINCT sex as text, LOWER(sex) as id FROM begrav_volume WHERE volumetype_id = 2 AND is_public = 1',
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => true,
				'required' => true,
				'searchable' => true,
				'required_levels' => false,
			),
			//Periode, søgebar
			array(
				'order' => 2,
				'gui_name' => 'Periode',
				'gui_description' => 'Registrets periode',
				'gui_info_link' => false,
				'name' => 'riv_1',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT DISTINCT id, riv_1 AS text FROM begrav_volume WHERE is_public = 1 AND volumetype_id = 2 AND sex LIKE \'%s\' ORDER BY text',
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => true,
				'required' => true,
				'searchable' => true,
				'required_levels' => array('sex'),
			),
			//Aggregeret titel, ikke søgebar
			array(
				'order' => 3,
				'gui_name' => 'beskrivelse',
				'gui_description' => 'Hvert år er inddelt i flere perioder',
				'gui_info_link' => false,
				'name' => 'nicetitle',
				'gui_type' => 'typeahead',
				'data_sql' => "*",
				'data' => false,
				'gui_hide_name' => true,
				'required' => false,
				'searchable' => false,
				'required_levels' => array(),
			),
			//Starbas-reference, ikke søgebar
			array(
				'order' => 4,
				'gui_name' => '',
				'gui_description' => '',
				'gui_info_link' => false,
				'name' => 'starbas_id',
				'gui_type' => 'preset',
				'data_sql' => "sesf",
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => true,
				'required' => false,
				'searchable' => false,
				'required_levels' => false,
			),
		),
		'error_intro' => 'Har du opdaget en fejl kan du give os besked.',
		'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
		/* 'error_reports' => array(
			            array(
			                'id' => 1,
			                'name' => 'Billedet er ikke et register',
			                'sql' => 'UPDATE apacs_pages SET error_image = 1 WHERE id = :itemId LIMIT 1',
			                'order' => 1
			            )
		*/
	),

	/**
	 * Lysninger, registre
	 */
	array(
		'id' => 7,
		'test' => true,
		'info' => 'Alfabetisk navneregister. Noter nummeret, der står ved den valgte person.',
		'link' => 'http://www.kbharkiv.dk/sog-i-arkivet/kilder-pa-nettet/agteskab/lysninger-provelse-af-aegteskab',
		'short_name' => 'Registre til lysninger',
		'long_name' => 'Registre for lysningsjournaler for København 1923 - 1965',
		'gui_required_fields_text' => 'Vælg et år',
		'image_type' => 'image',
		'primary_table_name' => 'apacs_pages',
		//   'starbas_field_name' => 'starbas_id',
		//How to link the data level objects to images
		'objects_query' => 'select apacs_pages.id, riv_1, apacs_pages.starbas_id, CONCAT(\'/getfile.php?fileId=\', apacs_pages.id) as imageURL
                        FROM apacs_pages
                        LEFT JOIN begrav_volume ON apacs_pages.volume_id = begrav_volume.id
                        WHERE volumetype_id = 4 AND is_public = 1 AND :query ORDER BY apacs_page.page_number',
		'levels_type' => 'hierarchy',
		'levels' => array(
			//År, søgebar
			array(
				'order' => 1,
				'gui_name' => 'År',
				'gui_description' => 'Registre opdelt efter år',
				'gui_info_link' => false,
				'name' => 'riv_1',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT DISTINCT id, riv_1 as text FROM begrav_volume where is_public = 1 AND volumetype_id = 4 order by text',
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => false,
				'required' => true,
				'searchable' => true,
				'required_levels' => false,
			),
			//Starbas-reference, ikke søgebar
			array(
				'order' => 2,
				'gui_name' => '',
				'gui_description' => '',
				'gui_info_link' => false,
				'name' => 'starbas_id',
				'gui_type' => 'preset',
				'data_sql' => "sesf",
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => true,
				'required' => false,
				'searchable' => false,
				'required_levels' => false,
			),
		),
		'error_intro' => 'Har du opdaget en fejl kan du give os besked.',
		'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
		/*'error_reports' => array(
			            array(
			                'id' => 1,
			                'name' => 'Billedet er ikke en begravelsesprotokol',
			                'sql' => 'UPDATE apacs_pages SET error_image = 1 WHERE id = :itemId LIMIT 1',
			                'order' => 1
			            )
		*/
	),

	/**
	 * Lysninger, protokoller
	 */
	array(
		'id' => 8,
		'test' => true,
		'info' => 'Brug nummeret fra registret til at vælge den rigtige protokol',
		'link' => ' http://www.kbharkiv.dk/sog-i-arkivet/kilder-pa-nettet/agteskab/lysninger-provelse-af-aegteskab',
		'short_name' => 'Lysningsprotokoller',
		'long_name' => 'Lysningsjournaler for København 1923 - 1965',
		'gui_required_fields_text' => 'Vælg et år',
		'image_type' => 'image',
		'primary_table_name' => 'apacs_pages',
		//How to link the data level objects to images
		'objects_query' => 'select DISTINCT apacs_pages.id, riv_1, apacs_pages.starbas_id, nicetitle, CONCAT(\'/getfile.php?fileId=\', apacs_pages.id) as imageURL
                        FROM apacs_pages
                        LEFT JOIN begrav_volume ON apacs_pages.volume_id = begrav_volume.id
                        WHERE volumetype_id = 3 AND is_public = 1 AND :query ORDER BY page_number',
		'levels_type' => 'hierarchy',
		'levels' => array(
			//År, søgebar
			array(
				'order' => 1,
				'gui_name' => 'År',
				'gui_description' => 'Protokollens år',
				'gui_info_link' => false,
				'name' => 'riv_1',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT DISTINCT riv_1 as id, riv_1 AS text FROM begrav_volume WHERE is_public = 1 AND volumetype_id = 3 ORDER BY text',
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => true,
				'required' => true,
				'searchable' => true,
				'required_levels' => array(),
			),
			//Periode, søgebar
			array(
				'order' => 2,
				'gui_name' => 'Løbenummer',
				'gui_description' => 'År og løbenummer',
				'gui_info_link' => false,
				'name' => 'nicetitle',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT DISTINCT id, nicetitle AS text FROM begrav_volume WHERE is_public = 1 AND volumetype_id = 3 AND riv_1 = %s ORDER BY number_from',
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => false,
				'required' => false,
				'searchable' => true,
				'required_levels' => array('riv_1'),
			),
			array(
				'order' => 4,
				'gui_name' => '',
				'gui_description' => '',
				'gui_info_link' => false,
				'name' => 'starbas_id',
				'gui_type' => 'preset',
				'data_sql' => "sesf",
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => true,
				'required' => false,
				'searchable' => false,
				'required_levels' => false,
			),
		),
		'error_intro' => 'Har du opdaget en fejl kan du give os besked.',
		'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
		/* 'error_reports' => array(
			            array(
			                'id' => 1,
			                'name' => 'Billedet er ikke et register',
			                'sql' => 'UPDATE apacs_pages SET error_image = 1 WHERE id = :itemId LIMIT 1',
			                'order' => 1
			            )
		*/
	),

	/**
	 * Borgerlige vielser, registre
	 */
	array(
		'id' => 9,
		'test' => true,
		'info' => 'Alfabetisk navneregister. Noter nummeret, der står ved den valgte person.',
		'link' => 'http://www.kbharkiv.dk/sog-i-arkivet/kilder-pa-nettet/agteskab/borgerlige-vielser',
		'short_name' => 'Registre til borgerlige vielser 1851 - 1922',
		'long_name' => 'Registre til borgerlige vielser 1851 - 1922',
		'gui_required_fields_text' => 'Vælg et år',
		'image_type' => 'image',
		'primary_table_name' => 'apacs_pages',
		//   'starbas_field_name' => 'starbas_id',
		//How to link the data level objects to images
		'objects_query' => 'select apacs_pages.id, nicetitle, apacs_pages.starbas_id, CONCAT(\'/getfile.php?fileId=\', apacs_pages.id) as imageURL
                        FROM apacs_pages
                        LEFT JOIN begrav_volume ON apacs_pages.volume_id = begrav_volume.id
                        WHERE volumetype_id = 6 AND is_public = 1 AND :query',
		'levels_type' => 'hierarchy',
		'levels' => array(
			//År, søgebar
			array(
				'order' => 1,
				'gui_name' => 'År',
				'gui_description' => 'Registrets periode',
				'gui_info_link' => false,
				'name' => 'nicetitle',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT DISTINCT id, nicetitle as text FROM begrav_volume where is_public = 1 AND volumetype_id = 6 ORDER BY nicetitle',
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => false,
				'required' => true,
				'searchable' => true,
				'required_levels' => false,
			),
			//Starbas-reference, ikke søgebar
			array(
				'order' => 2,
				'gui_name' => '',
				'gui_description' => '',
				'gui_info_link' => false,
				'name' => 'starbas_id',
				'gui_type' => 'preset',
				'data_sql' => "sesf",
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => true,
				'required' => false,
				'searchable' => false,
				'required_levels' => false,
			),
		),
		'error_intro' => 'Har du opdaget en fejl kan du give os besked.',
		'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
		/*'error_reports' => array(
			            array(
			                'id' => 1,
			                'name' => 'Billedet er ikke en begravelsesprotokol',
			                'sql' => 'UPDATE apacs_pages SET error_image = 1 WHERE id = :itemId LIMIT 1',
			                'order' => 1
			            )
		*/
	),

	/**
	 * Borgerlige vielser, protokoller
	 */
	array(
		'id' => 10,
		'test' => true,
		'info' => 'Brug nummeret fra registret til at vælge den rigtige protokol',
		'link' => 'http://www.kbharkiv.dk/sog-i-arkivet/kilder-pa-nettet/agteskab/borgerlige-vielser',
		'short_name' => 'Protokoller med borgerlige vielser 1851-1922',
		'long_name' => 'Protokoller med borgerlige vielser 1851-1922',
		'gui_required_fields_text' => 'Vælg et år',
		'image_type' => 'image',
		'primary_table_name' => 'apacs_pages',
		//   'starbas_field_name' => 'starbas_id',
		//How to link the data level objects to images
		'objects_query' => 'select apacs_pages.id, nicetitle, apacs_pages.starbas_id, CONCAT(\'/getfile.php?fileId=\', apacs_pages.id) as imageURL
                        FROM apacs_pages
                        LEFT JOIN begrav_volume ON apacs_pages.volume_id = begrav_volume.id
                        WHERE volumetype_id = 5 AND is_public = 1 AND :query',
		'levels_type' => 'hierarchy',
		'levels' => array(
			//År, søgebar
			array(
				'order' => 1,
				'gui_name' => 'År og løbenummer',
				'gui_description' => 'Protokollens periode',
				'gui_info_link' => false,
				'name' => 'nicetitle',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT DISTINCT id, nicetitle as text FROM begrav_volume where is_public = 1 AND volumetype_id = 5 ORDER BY nicetitle',
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => false,
				'required' => true,
				'searchable' => true,
				'required_levels' => false,
			),
			//Starbas-reference, ikke søgebar
			array(
				'order' => 2,
				'gui_name' => '',
				'gui_description' => '',
				'gui_info_link' => false,
				'name' => 'starbas_id',
				'gui_type' => 'preset',
				'data_sql' => "sesf",
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => true,
				'required' => false,
				'searchable' => false,
				'required_levels' => false,
			),
		),
		'error_intro' => 'Har du opdaget en fejl kan du give os besked.',
		'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
		/*'error_reports' => array(
			            array(
			                'id' => 1,
			                'name' => 'Billedet er ikke en begravelsesprotokol',
			                'sql' => 'UPDATE apacs_pages SET error_image = 1 WHERE id = :itemId LIMIT 1',
			                'order' => 1
			            )
		*/
	),

	/**
	 * Skoleprotokoller, registre
	 */
	array(
		'id' => 11,
		'test' => true,
		'info' => 'Brug år og nummer fra det alfabetiske navneregister til at finde eleven i protokollen.',
		'link' => 'http://www.kbharkiv.dk/sog-i-arkivet/kilder-pa-nettet/skoler',
		'short_name' => 'Skoleprotokoller og registre 1753-1937',
		'long_name' => 'Skoleprotokoller og registre 1753-1937',
		'gui_required_fields_text' => 'Vælg en skole',
		'image_type' => 'image',
		'primary_table_name' => 'apacs_pages',
		//   'starbas_field_name' => 'starbas_id',
		//How to link the data level objects to images
		'objects_query' => 'select apacs_pages.id, creator_name, nicetitle, apacs_pages.starbas_id, CONCAT(\'/getfile.php?fileId=\', apacs_pages.id) as imageURL
                        FROM apacs_pages
                        LEFT JOIN begrav_volume ON apacs_pages.volume_id = begrav_volume.id
                        WHERE volumetype_id = 7 AND is_public = 1 AND :query',
		'levels_type' => 'hierarchy',
		'levels' => array(
			//Skole, søgebar
			array(
				'order' => 1,
				'gui_name' => 'Skole',
				'gui_description' => 'Alfabetisk liste over skoler',
				'gui_info_link' => false,
				'name' => 'creator_name',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT distinct (creator_name) as text, (creator_name) as id FROM begrav_volume where is_public = 1 AND volumetype_id = 7 ORDER BY creator_name',
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => false,
				'required' => true,
				'searchable' => true,
				'required_levels' => false,
			),
			//Protokol, søgebar
			array(
				'order' => 2,
				'gui_name' => 'Elevprotokol eller register',
				'gui_description' => 'Vælg register eller elevprotokol',
				'gui_info_link' => false,
				'name' => 'nicetitle',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT DISTINCT id, nicetitle AS text FROM begrav_volume WHERE is_public = 1 AND volumetype_id = 7 AND creator_name = "%s" ORDER BY nicetitle',
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => false,
				'required' => false,
				'searchable' => true,
				'required_levels' => array('creator_name'),
			),
			//Starbas-reference, ikke søgebar
			array(
				'order' => 2,
				'gui_name' => '',
				'gui_description' => '',
				'gui_info_link' => false,
				'name' => 'starbas_id',
				'gui_type' => 'preset',
				'data_sql' => "sesf",
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => true,
				'required' => false,
				'searchable' => false,
				'required_levels' => false,
			),
		),
		'error_intro' => 'Har du opdaget en fejl kan du give os besked.',
		'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
		/*'error_reports' => array(
			            array(
			                'id' => 1,
			                'name' => 'Billedet er ikke en begravelsesprotokol',
			                'sql' => 'UPDATE apacs_pages SET error_image = 1 WHERE id = :itemId LIMIT 1',
			                'order' => 1
			            )
		*/
	),

	/**
	 * Borgerskabsprotokoller
	 */
	array(
		'id' => 12,
		'test' => true,
		'info' => 'Brug år og nummer fra registret til at finde den rigtige protokol.',
		'link' => 'http://www.kbharkiv.dk/sog-i-arkivet/kilder-pa-nettet/borgerskab',
		'short_name' => 'Borgerskabsprotokoller 1860-1932',
		'long_name' => 'Borgerskabsprotokoller 1860-1932',
		'gui_required_fields_text' => 'Vælg en protokol',
		'image_type' => 'image',
		'primary_table_name' => 'apacs_pages',
		//   'starbas_field_name' => 'starbas_id',
		//How to link the data level objects to images
		'objects_query' => 'select apacs_pages.id, creator_name, nicetitle, apacs_pages.starbas_id, CONCAT(\'/getfile.php?fileId=\', apacs_pages.id) as imageURL
                        FROM apacs_pages
                        LEFT JOIN begrav_volume ON apacs_pages.volume_id = begrav_volume.id
                        WHERE volumetype_id = 8 AND is_public = 1 AND :query',
		'levels_type' => 'hierarchy',
		'levels' => array(
			//Protokol, søgebar
			array(
				'order' => 1,
				'gui_name' => 'Datointerval',
				'gui_description' => 'Protokollens periode',
				'gui_info_link' => false,
				'name' => 'nicetitle',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT DISTINCT id, nicetitle AS text FROM begrav_volume WHERE is_public = 1 AND volumetype_id = 8 ORDER BY nicetitle',
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => false,
				'required' => false,
				'searchable' => true,
				'required_levels' => false,
			),
			//Starbas-reference, ikke søgebar
			array(
				'order' => 2,
				'gui_name' => '',
				'gui_description' => '',
				'gui_info_link' => false,
				'name' => 'starbas_id',
				'gui_type' => 'preset',
				'data_sql' => "sesf",
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => true,
				'required' => false,
				'searchable' => false,
				'required_levels' => false,
			),
		),
		'error_intro' => 'Har du opdaget en fejl kan du give os besked.',
		'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
		/*'error_reports' => array(
			            array(
			                'id' => 1,
			                'name' => 'Billedet er ikke en begravelsesprotokol',
			                'sql' => 'UPDATE apacs_pages SET error_image = 1 WHERE id = :itemId LIMIT 1',
			                'order' => 1
			            )
		*/
	),

	/**
	 * Register til borgerskabsprotokoller
	 */
	array(
		'id' => 13,
		'test' => true,
		'info' => 'Alfabetisk navneregister inddelt efter år. Noter år og dato.',
		'link' => 'http://www.kbharkiv.dk/sog-i-arkivet/kilder-pa-nettet/borgerskab',
		'short_name' => 'Registre til borgerskabsprotokoller 1860-1932',
		'long_name' => 'Registre til borgerskabsprotokoller 1860-1932',
		'gui_required_fields_text' => 'Vælg en protokol',
		'image_type' => 'image',
		'primary_table_name' => 'apacs_pages',
		//   'starbas_field_name' => 'starbas_id',
		//How to link the data level objects to images
		'objects_query' => 'select apacs_pages.id, creator_name, nicetitle, apacs_pages.starbas_id, CONCAT(\'/getfile.php?fileId=\', apacs_pages.id) as imageURL
                        FROM apacs_pages
                        LEFT JOIN begrav_volume ON apacs_pages.volume_id = begrav_volume.id
                        WHERE volumetype_id = 9 AND is_public = 1 AND :query',
		'levels_type' => 'hierarchy',
		'levels' => array(
			//Protokol, søgebar
			array(
				'order' => 1,
				'gui_name' => 'År',
				'gui_description' => 'Alfabetisk navneregister inddelt efter år',
				'gui_info_link' => false,
				'name' => 'nicetitle',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT DISTINCT id, nicetitle AS text FROM begrav_volume WHERE is_public = 1 AND volumetype_id = 9 ORDER BY nicetitle',
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => false,
				'required' => false,
				'searchable' => true,
				'required_levels' => false,
			),
			//Starbas-reference, ikke søgebar
			array(
				'order' => 2,
				'gui_name' => '',
				'gui_description' => '',
				'gui_info_link' => false,
				'name' => 'starbas_id',
				'gui_type' => 'preset',
				'data_sql' => "sesf",
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => true,
				'required' => false,
				'searchable' => false,
				'required_levels' => false,
			),
		),
		'error_intro' => 'Har du opdaget en fejl kan du give os besked.',
		'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
		/*'error_reports' => array(
			            array(
			                'id' => 1,
			                'name' => 'Billedet er ikke en begravelsesprotokol',
			                'sql' => 'UPDATE apacs_pages SET error_image = 1 WHERE id = :itemId LIMIT 1',
			                'order' => 1
			            )
		*/
	),

	/**
	 * Register til borgerskabsprotokoller
	 */
	array(
		'id' => 14,
		'test' => true,
		'info' => 'Alfabetisk navneregister findes forrest i hvert bind. Start med at finde manden eller konens navn i registret og brug nummeret ud fra navnet til at finde oplysningen om separationen/skilsmissen i selve protokollen.',
		'link' => 'http://www.kbharkiv.dk/sog-i-arkivet/kilder-pa-nettet/separation',
		'short_name' => 'Referat- og resolutionsprotokoller 1806-1857',
		'long_name' => 'Referat- og resolutionsprotokoller 1806-1857',
		'gui_required_fields_text' => 'Vælg en protokol',
		'image_type' => 'image',
		'primary_table_name' => 'apacs_pages',
		//   'starbas_field_name' => 'starbas_id',
		//How to link the data level objects to images
		'objects_query' => 'select apacs_pages.id, creator_name, nicetitle, apacs_pages.starbas_id, CONCAT(\'/getfile.php?fileId=\', apacs_pages.id) as imageURL
                        FROM apacs_pages
                        LEFT JOIN begrav_volume ON apacs_pages.volume_id = begrav_volume.id
                        WHERE volumetype_id = 10 AND is_public = 1 AND :query',
		'levels_type' => 'hierarchy',
		'levels' => array(
			//Protokol, søgebar
			array(
				'order' => 1,
				'gui_name' => 'År',
				'gui_description' => 'Der er navneregister bagerst i hvert bind.',
				'gui_info_link' => false,
				'name' => 'nicetitle',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT DISTINCT id, nicetitle AS text FROM begrav_volume WHERE is_public = 1 AND volumetype_id = 10 ORDER BY nicetitle',
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => false,
				'required' => false,
				'searchable' => true,
				'required_levels' => false,
			),
			//Starbas-reference, ikke søgebar
			array(
				'order' => 2,
				'gui_name' => '',
				'gui_description' => '',
				'gui_info_link' => false,
				'name' => 'starbas_id',
				'gui_type' => 'preset',
				'data_sql' => "sesf",
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => true,
				'required' => false,
				'searchable' => false,
				'required_levels' => false,
			),
		),
		'error_intro' => 'Har du opdaget en fejl kan du give os besked.',
		'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
		/*'error_reports' => array(
			            array(
			                'id' => 1,
			                'name' => 'Billedet er ikke en begravelsesprotokol',
			                'sql' => 'UPDATE apacs_pages SET error_image = 1 WHERE id = :itemId LIMIT 1',
			                'order' => 1
			            )
		*/
	),

	/**
	 * Begravelser fra Assistens, protokoller
	 */
	array(
		'id' => 15,
		'test' => true,
		'info' => 'Brug år og nummer fra det alfabetiske register til at finde begravelsen.',
		'link' => 'http://www.kbharkiv.dk/sog-i-arkivet/kilder-pa-nettet/begravelser',
		'short_name' => 'Begravelsesprotokoller fra Assistens Kirkegård, 1805-1862',
		'long_name' => 'Begravelsesprotokoller fra Assistens Kirkegård, 1805-1862',
		'gui_required_fields_text' => 'Vælg et år',
		'image_type' => 'image',
		'primary_table_name' => 'apacs_pages',
		//   'starbas_field_name' => 'starbas_id',
		//How to link the data level objects to images
		'objects_query' => 'SELECT apacs_pages.id, year, nicetitle, apacs_pages.starbas_id, CONCAT(\'/getfile.php?fileId=\', apacs_pages.id) as imageURL
                        FROM apacs_pages
                        LEFT JOIN begrav_volume ON apacs_pages.volume_id = begrav_volume.id
                        LEFT JOIN begrav_volume_years ON begrav_volume.id = begrav_volume_years.volume_id
                        WHERE volumetype_id = 11 AND is_public = 1 AND :query',
		'levels_type' => 'hierarchy',
		'levels' => array(
			//År, søgebar
			array(
				'order' => 1,
				'gui_name' => 'År og løbenummer',
				'gui_description' => 'Protokollens periode og løbenummer',
				'gui_info_link' => false,
				'name' => 'nicetitle',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT DISTINCT id, nicetitle as text FROM begrav_volume where volumetype_id = 11 AND is_public = 1 ORDER BY nicetitle',
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => true,
				'required' => true,
				'searchable' => true,
				'required_levels' => false,
			),
			//Starbas-reference, ikke søgebar
			array(
				'order' => 2,
				'gui_name' => '',
				'gui_description' => '',
				'gui_info_link' => false,
				'name' => 'starbas_id',
				'gui_type' => 'preset',
				'data_sql' => "sesf",
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => true,
				'required' => false,
				'searchable' => false,
				'required_levels' => false,
			),
		),
		'error_intro' => 'Har du opdaget en fejl kan du give os besked.',
		'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
		/*'error_reports' => array(
			            array(
			                'id' => 1,
			                'name' => 'Billedet er ikke en begravelsesprotokol',
			                'sql' => 'UPDATE apacs_pages SET error_image = 1 WHERE id = :itemId LIMIT 1',
			                'order' => 1
			            )
		*/
	),

	/**
	 * Begravelser fra Assistens, registre
	 */
	array(
		'id' => 16,
		'test' => true,
		'info' => 'Alfabetisk navneregister. Noter år og nummer ved den valgte person.',
		'link' => 'http://www.kbharkiv.dk/sog-i-arkivet/kilder-pa-nettet/begravelser',
		'short_name' => 'Registre til begravelsesprotokoller fra Assistens Kirkegård, 1805-1860',
		'long_name' => 'Registre til begravelsemvsprotokoller fra Assistens Kirkegård, 1805-1860',
		'gui_required_fields_text' => 'Vælg et år',
		'image_type' => 'image',
		'primary_table_name' => 'apacs_pages',
		//How to link the data level objects to images
		'objects_query' => 'SELECT DISTINCT apacs_pages.id, riv_1, sex, apacs_pages.starbas_id, nicetitle, CONCAT(\'/getfile.php?fileId=\', apacs_pages.id) as imageURL
                        FROM apacs_pages
                        LEFT JOIN begrav_volume ON apacs_pages.volume_id = begrav_volume.id
                        WHERE volumetype_id = 12 AND is_public = 1 AND :query',
		'levels_type' => 'hierarchy',
		'levels' => array(
			//Køn, søgebar
			array(
				'order' => 1,
				'gui_name' => 'Køn',
				'gui_description' => 'Hvert år er opdelt efter køn',
				'gui_info_link' => false,
				'name' => 'sex',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT DISTINCT sex as text, LOWER(sex) as id FROM begrav_volume WHERE volumetype_id = 12 AND is_public = 1 ORDER BY text',
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => true,
				'required' => true,
				'searchable' => true,
				'required_levels' => false,
			),
			//Periode, søgebar
			array(
				'order' => 2,
				'gui_name' => 'Periode',
				'gui_description' => 'Registrets periode',
				'gui_info_link' => false,
				'name' => 'nicetitle',
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT DISTINCT id, nicetitle AS text FROM begrav_volume WHERE is_public = 1 AND volumetype_id = 12 AND LOWER(sex) LIKE \'%s\' ORDER BY text',
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => true,
				'required' => true,
				'searchable' => true,
				'required_levels' => array('sex'),
			),
			//Starbas-reference, ikke søgebar
			array(
				'order' => 3,
				'gui_name' => '',
				'gui_description' => '',
				'gui_info_link' => false,
				'name' => 'starbas_id',
				'gui_type' => 'preset',
				'data_sql' => "sesf",
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => true,
				'required' => false,
				'searchable' => false,
				'required_levels' => false,
			),
		),
		'error_intro' => 'Har du opdaget en fejl kan du give os besked.',
		'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
		/* 'error_reports' => array(
			            array(
			                'id' => 1,
			                'name' => 'Billedet er ikke et register',
			                'sql' => 'UPDATE apacs_pages SET error_image = 1 WHERE id = :itemId LIMIT 1',
			                'order' => 1
			            )
		*/
	),
);

return $collectionsSettings;
