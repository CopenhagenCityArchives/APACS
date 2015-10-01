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
    //ini_set('display_errors',1);
    //error_reporting(E_ALL);
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
        'objects_query' => 'select MAND_files.id, CONCAT(\'/collections/mandtal\',path, fileName) as imageURL, year, month, road_name FROM MAND_files LEFT JOIN MAND_folders ON MAND_folders.id = MAND_files.folder_id WHERE :query AND error_image != 1 ORDER BY year, month, fileName',
        'primary_table_name' => 'MAND_files', 
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
                'data_sql' => 'select distinct month as text, month as id from mand_files where road_name = "%s" AND year = "%s" order by month',
                'data' => false,
                'searchable' => true,
                'required_levels' => array('road_name', 'year'),
                /*'data_sql' => false,
                'gui_type' => 'preset',
                'data' => array(
                    array(
                        'text' => 'MAJ',
                        'id' => 'maj'
                    ),
                    array(
                        'text' => 'NOV',
                        'id' => 'nov'
                    )                            
                ),*/
                'gui_hide_name' => true,
                'required' => false
            ),
            array(
                'order' => 2,
                'gui_name' => 'År',
                'gui_description' => 'Mandtallerne blev ført fra 1866 til 1923',
                'gui_info_link' => 'http://www.kbharkiv.dk/mandtaller',
                'name' => 'year',
                'gui_type' => 'typeahead',
                'data_sql' => 'select distinct year as text, year as id from mand_files WHERE road_name = "%s" order by year',// WHERE :query',//'select id, year as text FROM mand_years',
                'data' => false,
                'gui_hide_name' => true,
                'required' => false,
                'searchable' => true,
                'required_levels' => array('road_name')
            ),
            array(
                'order' => 1,
                'gui_name' => 'Gadenavn',
                'gui_description' => 'Gader, der optræder i mandtallerne',
                'gui_info_link' => 'http://www.kbharkiv.dk/mandtaller',
                'name' => 'road_name',
                'gui_type' => 'typeahead',
                'data_sql' => 'select navn as id, navn as text from MAND_streets WHERE mand_is_used = 1',// navn LIKE \'%s%%\'',
                'data' => false,
                'gui_hide_name' => true,
                'required' => true,
                'searchable' => true,
                'required_levels' => false//array('streetname')
            )
        ),
        'error_intro' => 'Har du opdaget, at et billede er registreret forkert eller ikke passer ind, kan du give os besked. Hvert enkelt billede skal fejlmeldes. Tak for hjælpen.',
        'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
        'error_reports' => array(
            array(
                'id' => 1,
                'name' => 'Gade, årstal eller måned er forkert',
                'sql' => 'UPDATE mand_files SET error_metadata = 1 WHERE id = :itemId LIMIT 1',
                'order' => 1
            ),
            array(
                'id' => 2,
                'name' => 'Viser ikke et mandtal',
                'sql' => 'UPDATE mand_files SET error_image = 1 WHERE id = :itemId LIMIT 1',
                'order' => 2
            )
        )
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
                'required_levels' => false
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
                'required_levels' => false
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
                'required_levels' => false
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
                'required_levels' => false
            )              
        ),
        'error_intro' => 'Har du opdaget en fejl i et kort eller en beskrivelse, kan du give os besked.',
        'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
        'error_reports' => array(
            array(
                'id' => 1,
                'name' => 'Billedet vender forkert',
                'sql' => 'UPDATE kortteg_files SET error_rotation = 1 WHERE id = :itemId LIMIT 1',
                'order' => 1
            ),
            array(
                'id' => 2,
                'name' => 'Beskrivelsen passer ikke',
                'sql' => 'UPDATE kortteg_files SET error_metadata = 1 WHERE id = :itemId LIMIT 1',
                'order' => 2
            )
        )
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
                'required_levels' => false
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
                'required_levels' => false
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
            )              
        ),
        'error_intro' => 'Har du opdaget en fejl på et billede eller i metadata, kan du give os besked.',
        'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
        'error_reports' => array(
            array(
                'id' => 1,
                'name' => 'Billedet kan ikke ses',
                'sql' => 'UPDATE kortteg_files SET error_rotation = 1 WHERE id = :itemId LIMIT 1',
                'order' => 1
            ),
            array(
                'id' => 2,
                'name' => 'Metadata er forkert',
                'sql' => 'UPDATE kortteg_files SET error_description = 1 WHERE id = :itemId LIMIT 1',
                'order' => 2
            )
        )
    ),

    /**
     * Begravelser, registre
     */
    array(
        'id' => 5,
        'test' => true,
        'info' => 'Brug år og nummer fra  det alfabetiske register til at finde begravelsen.',
        'link' => 'http://www.kbharkiv.dk/sog-i-arkivet/kilder-pa-nettet/begravelser',
        'short_name' => 'Begravelsesprotokoller 1861-1940',
        'long_name' => 'Begravelsesprotokoller 1861-1940',
        'gui_required_fields_text' => 'Vælg et år',
        'image_type' => 'image',
        'primary_table_name' => 'begrav_page',
     //   'starbas_field_name' => 'starbas_id',
        //How to link the data level objects to images
        'objects_query' => 'select begrav_page.id, year, nicetitle, begrav_page.starbas_id, CONCAT(\'/getfile.php?fileId=\', begrav_page.id) as imageURL
                        FROM begrav_page
                        LEFT JOIN begrav_volume ON begrav_page.volume_id = begrav_volume.id
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
                'required_levels' => false
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
                'required_levels' => array('year')
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
                'required_levels' => false
            )          
        ),
        'error_intro' => 'Har du opdaget en fejl kan du give os besked.',
        'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
        /*'error_reports' => array(
            array(
                'id' => 1,
                'name' => 'Billedet er ikke en begravelsesprotokol',
                'sql' => 'UPDATE begrav_page SET error_image = 1 WHERE id = :itemId LIMIT 1',
                'order' => 1
            )
        )*/
    ),

    /**
     * Begravelser, protokoller
     */
    array(
        'id' => 6,
        'test' => true,
        'info' => 'Alfabetisk navneregister. Noter år og nummer ved den valgte person.',
        'link' => 'http://www.kbharkiv.dk/sog-i-arkivet/kilder-pa-nettet/begravelser',
        'short_name' => 'Registre til begravelsesprotokoller 1861-1940',
        'long_name' => 'Registre til begravelsesprotokoller 1861-1940',
        'gui_required_fields_text' => 'Vælg et år',
        'image_type' => 'image',
        'primary_table_name' => 'begrav_page',
        //How to link the data level objects to images
        'objects_query' => 'select DISTINCT begrav_page.id, riv_1, sex, begrav_page.starbas_id, nicetitle, CONCAT(\'/getfile.php?fileId=\', begrav_page.id) as imageURL
                        FROM begrav_page
                        LEFT JOIN begrav_volume ON begrav_page.volume_id = begrav_volume.id
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
                'data' => array(
                    array(
                        'text' => 'Mænd',
                        'id' => 'mænd'
                    ),
                    array(
                        'text' => 'Kvinder',
                        'id' => 'kvinder'
                    )                            
                ),
                'gui_hide_name' => true,
                'gui_hide_value' => true,
                'required' => true,
                'searchable' => true,
                'required_levels' => false
            ),            
            //Periode, søgebar
            array(
                'order' => 2,
                'gui_name' => 'Periode',
                'gui_description' => 'Registrets periode',
                'gui_info_link' => false,
                'name' => 'riv_1',
                'gui_type' => 'typeahead',
                'data_sql' => 'SELECT DISTINCT id, riv_1 AS text FROM begrav_volume WHERE is_public = 1 AND sex LIKE \'%s\' ORDER BY text',                
                'data' => false,
                'gui_hide_name' => true,
                'gui_hide_value' => true,
                'required' => true,
                'searchable' => true,
                'required_levels' => array('sex')
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
                'required_levels' => array()
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
                'required_levels' => false
            )          
        ),
        'error_intro' => 'Har du opdaget en fejl kan du give os besked.',
        'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
       /* 'error_reports' => array(
            array(
                'id' => 1,
                'name' => 'Billedet er ikke et register',
                'sql' => 'UPDATE begrav_page SET error_image = 1 WHERE id = :itemId LIMIT 1',
                'order' => 1
            )
        )*/
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
        'primary_table_name' => 'begrav_page',
     //   'starbas_field_name' => 'starbas_id',
        //How to link the data level objects to images
        'objects_query' => 'select begrav_page.id, riv_1, begrav_page.starbas_id, CONCAT(\'/getfile.php?fileId=\', begrav_page.id) as imageURL
                        FROM begrav_page
                        LEFT JOIN begrav_volume ON begrav_page.volume_id = begrav_volume.id
                        WHERE volumetype_id = 4 AND is_public = 1 AND :query',
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
                'data_sql' => 'SELECT DISTINCT id, riv_1 as text FROM begrav_volume where is_public = 1 AND volumetype_id = 4',                
                'data' => false,
                'gui_hide_name' => true,
                'gui_hide_value' => false,
                'required' => true,
                'searchable' => true,
                'required_levels' => false
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
                'required_levels' => false
            )          
        ),
        'error_intro' => 'Har du opdaget en fejl kan du give os besked.',
        'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
        /*'error_reports' => array(
            array(
                'id' => 1,
                'name' => 'Billedet er ikke en begravelsesprotokol',
                'sql' => 'UPDATE begrav_page SET error_image = 1 WHERE id = :itemId LIMIT 1',
                'order' => 1
            )
        )*/
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
        'primary_table_name' => 'begrav_page',
        //How to link the data level objects to images
        'objects_query' => 'select DISTINCT begrav_page.id, riv_1, begrav_page.starbas_id, nicetitle, CONCAT(\'/getfile.php?fileId=\', begrav_page.id) as imageURL
                        FROM begrav_page
                        LEFT JOIN begrav_volume ON begrav_page.volume_id = begrav_volume.id
                        WHERE volumetype_id = 3 AND is_public = 1 AND :query',
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
                'required_levels' => array()
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
                'required_levels' => array('riv_1')
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
                'required_levels' => false
            )          
        ),
        'error_intro' => 'Har du opdaget en fejl kan du give os besked.',
        'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
       /* 'error_reports' => array(
            array(
                'id' => 1,
                'name' => 'Billedet er ikke et register',
                'sql' => 'UPDATE begrav_page SET error_image = 1 WHERE id = :itemId LIMIT 1',
                'order' => 1
            )
        )*/
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
        'primary_table_name' => 'begrav_page',
     //   'starbas_field_name' => 'starbas_id',
        //How to link the data level objects to images
        'objects_query' => 'select begrav_page.id, nicetitle, begrav_page.starbas_id, CONCAT(\'/getfile.php?fileId=\', begrav_page.id) as imageURL
                        FROM begrav_page
                        LEFT JOIN begrav_volume ON begrav_page.volume_id = begrav_volume.id
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
                'required_levels' => false
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
                'required_levels' => false
            )          
        ),
        'error_intro' => 'Har du opdaget en fejl kan du give os besked.',
        'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
        /*'error_reports' => array(
            array(
                'id' => 1,
                'name' => 'Billedet er ikke en begravelsesprotokol',
                'sql' => 'UPDATE begrav_page SET error_image = 1 WHERE id = :itemId LIMIT 1',
                'order' => 1
            )
        )*/
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
        'primary_table_name' => 'begrav_page',
     //   'starbas_field_name' => 'starbas_id',
        //How to link the data level objects to images
        'objects_query' => 'select begrav_page.id, nicetitle, begrav_page.starbas_id, CONCAT(\'/getfile.php?fileId=\', begrav_page.id) as imageURL
                        FROM begrav_page
                        LEFT JOIN begrav_volume ON begrav_page.volume_id = begrav_volume.id
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
                'required_levels' => false
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
                'required_levels' => false
            )          
        ),
        'error_intro' => 'Har du opdaget en fejl kan du give os besked.',
        'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
        /*'error_reports' => array(
            array(
                'id' => 1,
                'name' => 'Billedet er ikke en begravelsesprotokol',
                'sql' => 'UPDATE begrav_page SET error_image = 1 WHERE id = :itemId LIMIT 1',
                'order' => 1
            )
        )*/
    ),

    /**
     * Skoleprotokoller, registre
     */
    array(
        'id' => 11,
        'test' => true,
        'info' => 'Brug år og nummer fra  det alfabetiske register til at finde vielsen.',
        'link' => 'http://www.kbharkiv.dk/sog-i-arkivet/kilder-pa-nettet/begravelser',
        'short_name' => 'Skoleprotokoller for København xxxx - xxxx',
        'long_name' => 'Skoleprotokoller for København xxxx - xxxx',
        'gui_required_fields_text' => 'Vælg et år',
        'image_type' => 'image',
        'primary_table_name' => 'begrav_page',
     //   'starbas_field_name' => 'starbas_id',
        //How to link the data level objects to images
        'objects_query' => 'select begrav_page.id, nicetitle, begrav_page.starbas_id, CONCAT(\'/getfile.php?fileId=\', begrav_page.id) as imageURL
                        FROM begrav_page
                        LEFT JOIN begrav_volume ON begrav_page.volume_id = begrav_volume.id
                        WHERE volumetype_id = 7 AND is_public = 1 AND :query',
        'levels_type' => 'hierarchy',
        'levels' => array(
            //År, søgebar
            array(
                'order' => 1,
                'gui_name' => 'År',
                'gui_description' => 'Periode for protokollen',
                'gui_info_link' => false,
                'name' => 'nicetitle',
                'gui_type' => 'typeahead',
                'data_sql' => 'SELECT DISTINCT id, nicetitle as text FROM begrav_volume where is_public = 1 AND volumetype_id = 7 ORDER BY nicetitle',                
                'data' => false,
                'gui_hide_name' => true,
                'gui_hide_value' => false,
                'required' => true,
                'searchable' => true,
                'required_levels' => false
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
                'required_levels' => false
            )          
        ),
        'error_intro' => 'Har du opdaget en fejl kan du give os besked.',
        'error_confirm' => 'Vi har modtaget fejlen. Tak for dit bidrag.',
        /*'error_reports' => array(
            array(
                'id' => 1,
                'name' => 'Billedet er ikke en begravelsesprotokol',
                'sql' => 'UPDATE begrav_page SET error_image = 1 WHERE id = :itemId LIMIT 1',
                'order' => 1
            )
        )*/
    )    
);

return $collectionsSettings;