<?php
// AGmedia Custom
define('OC_ENV', [
    'env'                    => 'local',
    //
    'free_shipping_amount'   => 500,
    'default_shipping_price' => 39,
    'service'                => [
        // test_url http://luceedapi-test.tomsoft.hr:3676/datasnap/rest/
        // live_url http://luceedapi.tomsoft.hr:3675/datasnap/rest/
        'base_url' => 'http://sechip.dyndns.org:8889/datasnap/rest/',
        'username' => 'webshop',
        'password' => 'test.bJ8tn63Q',
    ],
    'import'                 => [
        'default_category'        => 0,
        'default_action_category' => 264,
        'default_language'        => 2, // HR
        'default_tax_class'       => 1, // PDV
        'default_stock_empty'     => 5,
        'default_stock_full'      => 7,
        'default_attribute_group' => 4,
        'default_store_id'        => 0,
        'image_path'              => 'catalog/products/',
        'image_placeholder'       => 'catalog/products/no-image.jpg',
        'category'                => [
            'excluded' => ['000000', '409400', '900001', '100000', '100001', '100002',
                           '900411', '9004DJ', '900DJ', '9004SM', '9004ML', '9004MD', '9004SI', '9004BE', '9004AK', '9004BV', '9004MA', '06', '9004FF', '9004DA',
                           '900400', '230300', '250363', '9004MV', '9004DM', '9004ST', '9004RI', '9004MP', '9004IV', '9004MM', '9004ZG', '9004MZ', '9004RL',
                           '9004OS', '9004GI', '9004DP', '9004DM1', '9004DM2', '9004MS', '9004KK', '9004ZD', '9004DB', '9004IJ', '9004TR', '9004FJ', '9004MR', '9004PU', '900600']
        ],
        'warehouse'               => [
            'included'          => ['001', '002', '003', '004', '006', '005', '007', '011', '012', '101'],
            'default'           => ['101', '001'],
            'availability_view' => ['001', '002', '003', '004', '006', '005', '007', '011', '012'],
            'stores'            => ['002', '003', '004', '006', '005', '007', '011', '012'],
            'json'              => DIR_STORAGE . 'upload/assets/skladista.json'
        ],
        'payments' => [
            'included' => [
                'VIRMAN MP',
                'GLS POUZEĆE',
                'MAESTRO',
                'MAESTRO RATE',
                'MASTERCARD',
                'MASTERCARD RATE',
                'VISA',
                'VISA RATE'
            ],
            'json' => DIR_STORAGE . 'upload/assets/placanja.json'
        ],
        'product'                 => [
            'chunk' => 100,
        ],
        'orders' => [
            'from_date' => '01.08.2021'
        ]
    ],
    'luceed'                 => [
        'with_tax'              => 'D',
        'default_warehouse_uid' => 'P02', // Šifra skladišta iz Luceed-a.
        'stock_warehouse_uid'   => 'P04', // Primarna šifra skladišta za provjeru količina.
        'status_uid'            => '01',
        'payment'               => [
            'cod'           => 'GLS POUZEĆE',
            'bank_transfer' => 'VIRMAN MP',
            'card_default'  => ''
        ],
        'shipping_article_uid'  => 'USL-19',
        'date'                  => 'd.m.Y',
        'datetime'              => 'd.m.Y H:i:s',
    ],
    //
    'mail' => [
        'cod' => [
            0 => [
                '02' => [
                    24 => 10,
                    72 => 11
                ],
                '05' => [
                    24 => 10,
                    72 => 11
                ],
                '11' => [
                    168 => 6
                ]
            ],
            7 => [
                'from' => '01',
                'to' => '02'
            ],
            3 => [
                'from' => '02',
                'to' => '05'
            ],
            4 => [
                'from' => '05',
                'to' => '11'
            ]
        ],
        'bank_transfer' => [
            0 => [
                '12' => [
                    24 => 5,
                    48 => 8
                ],
                '02' => [
                    24 => 10,
                    72 => 11
                ],
                '05' => [
                    24 => 10,
                    72 => 11
                ],
                '11' => [
                    168 => 6
                ]
            ],
            2 => [
                'from' => '01',
                'to' => '12'
            ],
            9 => [
                'from' => '12',
                'to' => '02'
            ],
            3 => [
                'from' => '02',
                'to' => '05'
            ],
            4 => [
                'from' => '05',
                'to' => '11'
            ]
        ],
        'wspay' => [
            0 => [
                '02' => [
                    24 => 10,
                    72 => 11
                ],
                '05' => [
                    24 => 10,
                    72 => 11
                ],
                '11' => [
                    168 => 6
                ]
            ],
            1 => [
                'from' => '01',
                'to' => '02'
            ],
            3 => [
                'from' => '02',
                'to' => '05'
            ],
            4 => [
                'from' => '05',
                'to' => '11'
            ]
        ],
    ],
]);