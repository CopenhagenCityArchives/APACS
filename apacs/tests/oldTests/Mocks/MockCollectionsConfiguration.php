<?php
namespace Mocks;


class MockedCollectionsSettings
{
    public function getCollection(){
        $collectionsSettings = [
            [
                'id' => 1,
                'generel_information' => 'Politiets mandtaller indeholder registreringer af alle københavnere fra 1866 til 1923.',
                'link' => 'http://www.kbharkiv.dk/wiki',
                'name' => 'Politiets mandtal for København 1866-1923',
                'short_name' => 'mandtaller',       
                'name' => 'Politiets registerblade',
                //How to get instances of data level objects. Not sure if this is even necessary. When do you need to get data objects without images...?
                'objects_query' => 'select * from PRB_registerblade WHERE :query',
                'primary_table_name' => 'PRB_registerblade',
                'levels_type' => 'hierarchy',
                'levels' => [
                    [
                        'order' => 2,
                        'gui_name' => 'Filmruller',
                        'gui_description' => 'Der er mellem 20 og 50 filmruller pr. station. Opdelingen skyldes begrænsningen i antallet af billeder på en gammeldags fotofilm',
                        'gui_info_link' => 'http://www.kbharkiv.dk/wiki/registerbladenes-filmruller',
                        'name' => 'roll',
                        'gui_type' => 'getallbyfilter',
                        'data_sql' => 'SELECT id, filmrulle_navn from PRB_filmrulle WHERE station_id = :query',
                        'data' => false,
                        'required' => false
                    ],
                    [
                        'order' => 1,
                        'gui_name' => 'Stationer',
                        'gui_description' => 'Der findes seks stationer baseret på politidistrikternes inddeling, og to baseret på alfabetisk sortering',
                        'gui_info_link' => 'http://www.kbharkiv.dk/registerblade/om-stationerne',
                        'name' => 'station',
                        'gui_type' => 'preset',
                        'data_sql' => false,
                        'data' => [
                            [
                                'name' => 'Station 1',
                                'id' => '26'
                            ],
                            [
                                'name' => 'Station 2',
                                'id' => '29'
                            ]
                        ],
                        'required' => true
                    ]                  
                ]
            ]        
        ];

        return $collectionsSettings;
    }
}


