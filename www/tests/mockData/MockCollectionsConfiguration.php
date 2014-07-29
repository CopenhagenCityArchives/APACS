<?php

$collectionsSettings = array(
    array(
        'id' => 1,
        'info' => array(
            'generel_information' => 'Politiets mandtaller indeholder registreringer af alle københavnere fra 1866 til 1923.',
            'link' => 'http://www.kbharkiv.dk/wiki',
            'name' => 'Politiets mandtal for København 1866-1923',
            'short_name' => 'mandtaller'
        ),        
        'config' => array(
            'name' => 'Politiets registerblade',
            'dataLevel' => array(
                'name' => 'registerblade',
                //How to get instances of data level objects. Not sure if this is even necessary. When do you need to get data objects without images...?
                'data_sql' => 'select * from RRB_registerblade WHERE :query',
                //Not sure if this is necessary...
                'primaryField' => 'registerblad_id',
                //How to link the data level objects to images
                'image_sql' => 'select image from PRB_images LEFT JOIN PRB_registerblade ON PRB_registerblade.registerblad_id = PRB_images.object_id WHERE :query'
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
                        'data_sql' => 'SELECT id, filmrulle_navn from PRB_filmrulle WHERE station_id = :query',
                        'data' => false,
                        'required' => false
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
            'generel_information' => 'Politiets mandtaller indeholder registreringer af alle københavnere fra 1866 til 1923.',
            'link' => 'http://www.kbharkiv.dk/wiki',
            'name' => 'Politiets mandtal for København 1866-1923',
            'short_name' => 'mandtaller'
        ),        
        'config' => array(            
            'name' => 'Mandtaller',
            'dataLevel' => array(
                'name' => 'mandtalssider',
                //How to get instances of data level objects
                'data_sql' => 'select * from MAND_mandtaller WHERE :query',
                //Not sure if this is necessary...
                'primaryField' => 'registerblad_id',
                //How to link the data level objects to images
                'image_sql' => 'select image from MAND_images LEFT JOIN MAND_mandtaller ON MAND_taller.id = PRB_images.object_id WHERE :query'
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
                                'name' => 'Maj',
                                'id' => 'maj'
                            ),
                            array(
                                'name' => 'November',
                                'id' => 'nov'
                            )                            
                        )
                    ),
                    array(
                        'order' => 1,
                        'gui_name' => 'År',
                        'gui_description' => 'Mandtallerne blev ført fra 1862 til 1923',
                        'gui_info_link' => 'http://www.kbharkiv.dk/mandtaller',
                        'name' => 'year',
                        'type' => 'typeahead',
                        'data_sql' => 'select id, year from MAND_year WHERE year = :query',
                        'data' => false                       
                    ),
                    array(
                        'order' => 3,
                        'gui_name' => 'Gadenavn',
                        'gui_description' => 'Mandtallerne blev ført fra 1862 til 1923',
                        'gui_info_link' => 'http://www.kbharkiv.dk/mandtaller',
                        'name' => 'streetname',
                        'type' => 'typeahead',
                        'data_sql' => 'select id, gadenavn from MAND_gader WHERE gadenavn LIKE \':query%\'',
                        'data' => false                       
                    )
                )
            )
        )
    )        
);

return $collectionsSettings;