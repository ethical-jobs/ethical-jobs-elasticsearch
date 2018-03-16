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
    | Logging
    |--------------------------------------------------------------------------
    |
    | Determines how indexing opeations are logged
    |
    */
   
    'logging' => [
        'slack' => [
            'webhook'   => 'https://hooks.slack.com/...',
            'channel'   => '#elasticsearch',
            'username'  => 'Elasticsearch',
        ]
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
