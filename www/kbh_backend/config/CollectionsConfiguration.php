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
        'info' => 'Politiets mandtaller 1866-1899 er skemaer over personer over 10 år bosat i København, registreret to gange årligt af Københavns Politi.',
        'link' => 'http://www.kbharkiv.dk/wiki',
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
                'gui_hide' => true,
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
                'gui_hide' => true,
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
                'gui_hide' => true,
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
        'info' => 'Et uddrag af kort og tegninger fra Stadsarkivets samlinger',
        'link' => 'http://www.kbharkiv.dk/wiki',
        'short_name' => 'Kort og tegninger',
        'long_name' => 'Københavns Stadsarkivs digitaliserede kort og tegninger',
        'gui_required_fields_text' => 'Vælg en kategori for at fortsætte',
        'image_type' => 'tile',
        'primary_table_name' => 'kortteg_examples',
        //How to link the data level objects to images
        'data_sql' => ' select kortteg_examples.id, CONCAT(\'/collections/kortteg/\',fileName) as imageURL, year, height, width, 
                        description, 
                        av_beskrivelse,
                        if(av_aar_fra is not null AND av_aar_fra != 0, CONCAT(av_aar_fra, " - ", av_aar_til), av_aar) as time_desc 
                        FROM kortteg_examples
                        LEFT JOIN kortteg_tags ON kortteg_examples.tag = kortteg_tags.id
                        left join kortteg_eksemplar eks on kortteg_examples.eksemplar_id = eks.id 
                        left join kortteg_data dat on eks.id = dat.id WHERE :query',
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
                'data_sql' => 'SELECT id, description as text FROM kortteg_tags WHERE is_used = 1',
                'data' => false,
                'hideInMetadataString' => false,
                'required' => true,
                'searchable' => true,
                'required_levels' => false
            ),
            //Beskrivelse, ikke søgebar
            array(
                'order' => 3,
                'gui_name' => 'besk',
                'gui_description' => 'besk',
                'gui_info_link' => false,
                'name' => 'av_beskrivelse',
                'gui_type' => 'preset',
                'data_sql' => "sesf",
                'data' => false,
                'hideInMetadataString' => false,
                'required' => false,
                'searchable' => false,
                'required_levels' => false
            ),
            //Årstal eller spænd, afhængig af data, ikke søgebart
            array(
                'order' => 2,
                'gui_name' => 'time_desc',
                'gui_description' => 'time_desc',
                'gui_info_link' => false,
                'name' => 'time_desc',
                'gui_type' => 'preset',
                'data_sql' => "sc",
                'data' => false,
                'hideInMetadataString' => false,
                'required' => false,
                'searchable' => false,
                'required_levels' => false
            )              
        )
    )    
);

return $collectionsSettings;