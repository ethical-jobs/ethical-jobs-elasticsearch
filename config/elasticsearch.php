<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Connection settings
    |--------------------------------------------------------------------------
    |
    | Configure the conenction to elasticsearch service
    |
    */    

    'defaultConnection' => 'default',

    'connections' => [
        'default' => [
            'hosts'             => [ env('ES_HOST', 'localhost:9200') ],
            'sslVerification'   => null,
            'logging'           => false,
        ],
    ],    

    /*
    |--------------------------------------------------------------------------
    | Index name
    |--------------------------------------------------------------------------
    |
    | Name of the primary Elasticsearch index
    | at present the package only supports a single index
    |
    */
   
	'index'	=> 'my-index',

    /*
    |--------------------------------------------------------------------------
    | Index settings
    |--------------------------------------------------------------------------
    |
    | The index settings initialised at creation time
    |
    */
   
	'settings' => [
            'analyzer' => [
                'html_analyzer' => [
                    'type'          => 'custom',
                    'tokenizer'     => 'standard',
                    'filter'        => ['lowercase'],
                    'char_filter'   => ['html_strip'],
                ],
            ],
            'normalizer' => [
                'standard_lowercase' => [
                    'type'          => 'custom',
                    'char_filter'   => [],
                    'filter'        => ['lowercase'],
                ],
            ],
	],

    /*
    |--------------------------------------------------------------------------
    | Index mappings and defaults
    |--------------------------------------------------------------------------
    |
    | Set any predefined or default index document mappings
    |
    */

	'mappings'	=> [
        '_default_' => [
            'properties' => [
                'id'            => ['type' => 'integer'],
                'created_at'    => ['type' => 'date' ],
                'updated_at'    => ['type' => 'date' ],
                'deleted_at'    => ['type' => 'date' ],
            ],
        ],
	],

    /*
    |--------------------------------------------------------------------------
    | Indexable models
    |--------------------------------------------------------------------------
    |
    | An array of indexable models, must implment the Indexable interface
    |
    */    
   
   'indexables' => [
        // App\User::class,
   ],
];
