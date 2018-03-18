<?php

namespace Tests\Fixtures;

use Elasticsearch\Client;
use ONGR\ElasticsearchDSL\Search;
use Illuminate\Database\Eloquent\Model;
use EthicalJobs\Elasticsearch\Repositories\ElasticsearchRepository;

/**
 * Creates a test ElasticSearch repository instance
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class RepositoryFactory
{
    /**
     * Mocks results of an Elasicsearch "search" api call
     *
     * @param Illuminate\Database\Eloquent\Model $model
     * @param Elasticsearch\Client $client
     * @return MockClient
     */
    public static function build(Model $model, Client $client)
    {
        return new ElasticsearchRepository(
            $model, new Search, $client, 'test-index'
        );
    } 
}