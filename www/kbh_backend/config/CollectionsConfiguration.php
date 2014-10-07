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
$collectionsSettings = array(
    array(
        'id' => 1,
        'info' => array(
            'info' => 'Politiets mandtaller indeholder registreringer af alle københavnere fra 1866 til 1923.',
            'link' => 'http://www.kbharkiv.dk/wiki',
            'name' => 'Politiets mandtal for København 1866-1923',
            'short_name' => 'Mandtaller'
        ),
        'config' => array(
            'name' => 'Politiets registerblade',
            'dataLevel' => array(
                'name' => 'registerblade',
                //How to get the object and the related images
                'data_sql' => 'select image from PRB_images LEFT JOIN PRB_registerblade ON PRB_registerblade.registerblad_id = PRB_images.object_id WHERE :query',
            ),
            'metadataLevels' => array(
                'type' => 'hierarchy',
                'levels' => array(
                    array(
                        'order' => 2,
                        'gui_name' => 'Filmruller',
                        'gui_description' => 'Der er mellem 20 og 50 filmruller pr. station. Opdelingen skyldes begrænsningen i antallet af billeder på en gammeldags fotofilm',
                        'gui_info_link' => 'http://www.kbharkiv.dk/wiki/registerbladenes-filmruller',
                        'name' => 'roll',
                        'type' => 'getallbyfilter',
                        'data_sql' => 'SELECT id, filmrulle_navn from PRB_filmrulle WHERE station_id = %d',
                        'required_levels' => array('station'),
                        'data' => false,
                        'required' => true
                    ),
                    array(
                        'order' => 1,
                        'gui_name' => 'Stationer',
                        'gui_description' => 'Der findes seks stationer baseret på politidistrikternes inddeling, og to baseret på alfabetisk sortering',
                        'gui_info_link' => 'http://www.kbharkiv.dk/registerblade/om-stationerne',
                        'name' => 'station',
                        'type' => 'preset',
                        'data_sql' => false,
                        'data' => array(
                            array(
                                'name' => 'Station 1',
                                'id' => '26'
                            ),
                            array(
                                'name' => 'Station 2',
                                'id' => '29'
                            )
                        ),
                        'required' => true
                    )                  
                )
            )
        )
    ),
    array(
        'id' => 2,
        'info' => array(
            'info' => 'Politiets Mandtal indeholder informationer om beboere i København for perioden 1866 til 1923',
            'link' => 'http://www.kbharkiv.dk/wiki',
            'short_name' => 'Politiets Mandtal, København',
            'long_name' => 'Politiets Mandtal for København 1866 - 1923'
        ),        
        'config' => array(            
            'name' => 'Mandtaller',
            'dataLevel' => array(
                'name' => 'MAND_mandtaller',
                //How to link the data level objects to images
                'data_sql' => 'select MAND_mandtaller.id, image, year, month, streetname FROM MAND_images LEFT JOIN MAND_mandtaller ON MAND_mandtaller.id = MAND_images.object_id WHERE :query'
            ),            
            'metadataLevels' => array(
                'type' => 'flat',
                'levels' => array(
                    array(
                        'order' => 2,
                        'gui_name' => 'Måned',
                        'gui_description' => 'Folketællingerne blev ajourført to gange om året, maj og november',
                        'gui_info_link' => false,
                        'name' => 'month',
                        'type' => 'preset',
                        'data_sql' => false,
                        'data' => array(
                            array(
                                'text' => 'Maj',
                                'id' => 'maj'
                            ),
                            array(
                                'text' => 'November',
                                'id' => 'nov'
                            )                            
                        ),
                        'hideInMetadataString' => true,
                        'required' => false
                    ),
                    array(
                        'order' => 1,
                        'gui_name' => 'År',
                        'gui_description' => 'Mandtallerne blev ført fra 1862 til 1923',
                        'gui_info_link' => 'http://www.kbharkiv.dk/mandtaller',
                        'name' => 'year',
                        'type' => 'preset',
                        'data_sql' => false,
                        'data' => array(
                            array(
                                'text' => '1866',
                                'id' => '1866'
                            ),
                            array(
                                'text' => '1867',
                                'id' => '1867'
                            )                            
                        ),
                        'hideInMetadataString' => true,
                        'required' => true
                    ),
                    array(
                        'order' => 3,
                        'gui_name' => 'Gadenavn',
                        'gui_description' => 'Mandtallerne blev ført fra 1862 til 1923',
                        'gui_info_link' => 'http://www.kbharkiv.dk/mandtaller',
                        'name' => 'streetname',
                        'type' => 'typeahead',
                        'data_sql' => 'select id, gadenavn from MAND_gader WHERE gadenavn LIKE \':query%\'',
                        'data' => false,
                        'hideInMetadataString' => true,
                        'required' => true
                    )
                )
            )
        )
    ),
    array(
        'id' => 3,
        'info' => array(
            'info' => 'Digitaliserede kort er et udsnit af Københavns Stadsarkivs kort- og tegningsamling, som er blevet digitaliseret i 2014',
            'link' => 'http://www.kbharkiv.dk/wiki',
            'short_name' => 'Digitaliserede kort og tegninger',
            'long_name' => 'Københavns Stadsarkivs digitaliserede kort og tegninger'
        ),        
        'config' => array(            
            'name' => 'Digitaliserede kort',
            'dataLevel' => array(
                'name' => 'kort',
                //How to link the data level objects to images
                'data_sql' => 'SELECT av_beskrivelse, av_sted, IF(av_aar IS NULL OR av_aar = 0, CONCAT(av_aar_fra,\'-\', av_aar_til), av_aar) as aar FROM KORTTEG_eksemplar LEFT JOIN KORTTEG_data ON av_stam_id = KORTTEG_data.id WHERE :query'
            ),            
            'metadataLevels' => array(
                'type' => 'flat',
                'levels' => array(
                    array(
                        'order' => 1,
                        'gui_name' => 'Detaljer',
                        'gui_description' => 'Fritekstsøgning i kortinformationer',
                        'gui_info_link' => false,
                        'name' => 'av_beskrivelse',
                        'type' => 'getallbyfilter',
                        'data_sql' => 'SELECT * FROM KORTTEG_eksemplar LEFT JOIN KORTTEG_data ON av_stam_id = KORTTEG_data.id WHERE :query',
                        'data' => false,
                        'hideInMetadataString' => true,
                        'required' => false,
                        'required_levels' => array()
                    )
                )
            )
        )
    )            
);*/

/*
 * New structure
 */

$collectionsSettings = array(
    /*array(
        'id' => 1,
        'description' => 'Politiets registerblade indeholder registreringer af alle københavnere fra 1890 til 1923.',
        'link' => 'http://www.kbharkiv.dk/wiki',
        'name' => 'Politiets registerblade for København 1890-1923',
        'short_name' => 'Polle',
        'levels_type' => 'hierarki',
        'object_sql' => 'select image from PRB_images LEFT JOIN PRB_registerblade ON PRB_registerblade.registerblad_id = PRB_images.object_id WHERE :query',
        'levels' => array(
            array(
                'order' => 2,
                'gui_name' => 'Filmruller',
                'gui_description' => 'Der er mellem 20 og 50 filmruller pr. station. Opdelingen skyldes begrænsningen i antallet af billeder på en gammeldags fotofilm',
                'gui_info_link' => 'http://www.kbharkiv.dk/wiki/registerbladenes-filmruller',
                'name' => 'roll',
                'gui_type' => 'getallbyfilter',
                'data_sql' => 'SELECT id, filmrulle_navn from PRB_filmrulle WHERE station_id = %d',
                'required_levels' => array('station'),
                'data' => false,
                'required' => true
            ),
            array(
                'order' => 1,
                'gui_name' => 'Stationer',
                'gui_description' => 'Der findes seks stationer baseret på politidistrikternes inddeling, og to baseret på alfabetisk sortering',
                'gui_info_link' => 'http://www.kbharkiv.dk/registerblade/om-stationerne',
                'name' => 'station',
                'gui_type' => 'preset',
                'data_sql' => false,
                'data' => array(
                    array(
                        'name' => 'Station 1',
                        'id' => '26'
                    ),
                    array(
                        'name' => 'Station 2',
                        'id' => '29'
                    )
                ),
                'required' => true
            )                  
        )
    ),*/
    array(
        'id' => 2,
        'info' => 'Politiets mandtaller 1866-1899 er skemaer over personer over 10 år bosat i København, registreret to gange årligt af Københavns Politi.',
        'link' => 'http://www.kbharkiv.dk/wiki',
        'short_name' => 'Politiets Mandtal',
        'long_name' => 'Politiets Mandtal for København 1866 - 1923',  
        'gui_required_fields_text' => 'Udfyld som minimum vej og år',
        //How to link the data level objects to images
        'data_sql' => 'select MAND_files.id, CONCAT(\'/collections/mandtal\',path, fileName) as imageURL, year, month, road_name FROM MAND_files LEFT JOIN MAND_folders ON MAND_folders.id = MAND_files.folder_id WHERE :query',
        'primary_table_name' => 'MAND_files', 
        'levels_type' => 'flat',
        'levels' => array(
            array(
                'order' => 3,
                'gui_name' => 'Måned',
                'gui_description' => 'Folketællingerne blev ajourført to gange om året, maj og november',
                'gui_info_link' => false,
                'name' => 'month',
                'gui_type' => 'preset',
                'data_sql' => false,
                'data' => array(
                    array(
                        'text' => 'MAJ',
                        'id' => 'maj'
                    ),
                    array(
                        'text' => 'NOV',
                        'id' => 'nov'
                    )                            
                ),
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
                'data_sql' => 'select id, year as text FROM mand_years',
                'data' => false,
                'gui_hide' => true,
                'required' => false
            ),
            array(
                'order' => 1,
                'gui_name' => 'Gadenavn',
                'gui_description' => 'Mandtallerne blev ført fra 1862 til 1923',
                'gui_info_link' => 'http://www.kbharkiv.dk/mandtaller',
                'name' => 'road_name',
                'gui_type' => 'typeahead',
                'data_sql' => 'select navn as id, navn as text from MAND_streets WHERE mand_is_used = 1',// navn LIKE \'%s%%\'',
                'data' => false,
                'gui_hide' => true,
                'required' => true,
                'required_levels' => false//array('streetname')
            )
        )
    )/*,
    array(
        'id' => 3,
        'info' => 'Digitaliserede kort er et udsnit af Københavns Stadsarkivs kort- og tegningsamling, som er blevet digitaliseret i 2014',
        'link' => 'http://www.kbharkiv.dk/wiki',
        'short_name' => 'Digitaliserede kort og tegninger',
        'long_name' => 'Københavns Stadsarkivs digitaliserede kort og tegninger',    
        //How to link the data level objects to images
        'data_sql' => 'SELECT av_beskrivelse, av_sted, IF(av_aar IS NULL OR av_aar = 0, CONCAT(av_aar_fra,\'-\', av_aar_til), av_aar) as aar FROM KORTTEG_eksemplar LEFT JOIN KORTTEG_data ON av_stam_id = KORTTEG_data.id WHERE :query',        
        'levels_type' => 'flat',
        'levels' => array(
            array(
                'order' => 1,
                'gui_name' => 'Detaljer',
                'gui_description' => 'Fritekstsøgning i kortinformationer',
                'gui_info_link' => false,
                'name' => 'av_beskrivelse',
                'type' => 'getallbyfilter',
                'data_sql' => 'SELECT * FROM KORTTEG_eksemplar LEFT JOIN KORTTEG_data ON av_stam_id = KORTTEG_data.id WHERE :query',
                'data' => false,
                'hideInMetadataString' => true,
                'required' => false,
                'required_levels' => array()
            )
        )
    )  */     
    ,array(
        'id' => 3,
        'info' => 'Test af visning af digitaliserede kort i forskellige størrelser',
        'link' => 'http://www.kbharkiv.dk/wiki',
        'short_name' => 'Kort og tegninger',
        'long_name' => 'Københavns Stadsarkivs digitaliserede kort og tegninger',
        'gui_required_fields_text' => 'År skal udfyldes, men det er bare et dummy-felt',
        'image_type' => 'tile',
        'primary_table_name' => 'kortteg_examples',
        //How to link the data level objects to images
        'data_sql' => 'select kortteg_examples.id, CONCAT(\'/collections/kortteg/\',fileName) as imageURL, year, height, width FROM kortteg_examples WHERE :query',
        'levels_type' => 'flat',
        'levels' => array(
            array(
                'order' => 1,
                'gui_name' => 'År',
                'gui_description' => 'Kortets år',
                'gui_info_link' => false,
                'name' => 'year',
                'type' => 'getallbyfilter',
                'data_sql' => false,
                'data' => array(
                    array(
                        'id'=>1900,
                        'text'=>1900
                    )
                ),
                'hideInMetadataString' => false,
                'required' => true,
                'searchable' => true,
                'required_levels' => array()
            ),
            array(
                'order' => 2,
                'name' => 'width',
                'type' => 'preset',
                'data_sql' => false,
                'data' => array('id'=>1),
                'technical_info' => true
            ),
            array(
                'order' => 3,
                'name' => 'height',
                'type' => 'preset',
                'data_sql' => false,
                'data' => array('id'=>1),
                'technical_info' => true
            )            
        )
    )    
);

return $collectionsSettings;