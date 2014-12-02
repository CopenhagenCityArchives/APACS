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
    array(
        'id' => 2,
        'test' => false,
        'info' => 'Politiets mandtaller 1866-1923 består af skemaer, hvor Københavns befolkning over 10 år er registreret. Vælg gade, år og måned, hvorefter du kan bladre i mandtallerne. <p><a href="http://www.kbharkiv.dk/wiki/Politiets_mandtaller" target="_blank">Mere om oplysningerne i Politiets mandtaller</a></p>',
        'video_link' => 'ZdQfxegC06E',
        'short_name' => 'Politiets Mandtal',
        'long_name' => 'Politiets Mandtal for København 1866 - 1923',  
        'gui_required_fields_text' => 'Vælg minimum gade og år',
        //How to link the data level objects to images
        'data_sql' => 'select MAND_files.id, CONCAT(\'/collections/mandtal\',path, fileName) as imageURL, year, month, road_name FROM MAND_files LEFT JOIN MAND_folders ON MAND_folders.id = MAND_files.folder_id WHERE :query ORDER BY year, month, fileName',
        'primary_table_name' => 'MAND_files', 
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
        'error_intro' => 'Har du opdaget at et billede er registreret forkert eller billedet ikke passer ind, kan du give os besked. Hvis det er hele vejen, der er registreret forkert, behøver du kun at rapportere et af billederne.',
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
                'name' => 'Billedet er ikke et mandtal',
                'sql' => 'UPDATE mand_files SET error_image = 1 WHERE id = :itemId LIMIT 1',
                'order' => 2
            )
        )
    ),
    array(
        'id' => 3,
        'test' => true,
        'info' => 'Et uddrag af kort og tegninger fra Stadsarkivets samlinger',
        'link' => 'http://www.kbharkiv.dk/wiki',
        'short_name' => 'Kort og tegninger',
        'long_name' => 'Københavns Stadsarkivs digitaliserede kort og tegninger',
        'gui_required_fields_text' => 'Vælg en kategori for at fortsætte',
        'image_type' => 'tile',
        'primary_table_name' => 'kortteg_files',
        'starbas_field_name' => 'av_stam_id',
        //How to link the data level objects to images
        'data_sql' => 'select kortteg_files.id, CONCAT(\'/collections/kortteg/\',fileName) as imageURL, year, height, width, 
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
                'gui_hide_value' => true,
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
                'name' => 'Kortet vender forkert',
                'sql' => 'UPDATE kortteg_data SET error_ratation = 1 WHERE id = :itemId LIMIT 1',
                'order' => 1
            ),
            array(
                'id' => 2,
                'name' => 'Beskrivelsen passer ikke',
                'sql' => 'UPDATE kortteg_data SET error_description = 1 WHERE id = :itemId LIMIT 1',
                'order' => 2
            )
        )
    )    
);

return $collectionsSettings;