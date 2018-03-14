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
   
	'settings' => [],

    /*
    |--------------------------------------------------------------------------
    | Index mappings and defaults
    |--------------------------------------------------------------------------
    |
    | Set any predefined or default index document mappings
    |
    */

	'mappings'	=> [],

    /*
    |--------------------------------------------------------------------------
    | Indexing
    |--------------------------------------------------------------------------
    |
    | Indexing settings dictate how document indexing is performed
    |
    */
   
    'indexing' => [
        'chunk-size' => 300,
    ]

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
