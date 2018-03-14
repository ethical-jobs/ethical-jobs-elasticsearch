<?php

namespace EthicalJobs\Elasticsearch\Testing;

use Mockery;
use M6Web\Component\ElasticsearchMock\Client as MockClient;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Collection;
use EthicalJobs\Elasticsearch\DocumentIndexer;

/**
 * Mocks the elasticsearch client
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

trait InteractsWithElasticsearch
{
    /**
     * Mocks results of an Elasicsearch "search" api call
     *
     * @param Illuminate\Support\Collection $entities
     * @return MockClient
     */
    public function getMockSearchResults(Collection $entities)
    {
        $elasticClient = new MockClient;

        $documents = $entities->map(function ($indexable) {
            return $indexable->getDocumentTree();
        })->toArray();

        $elasticClient->addSearchResult(
            config('elasticsearch.index'),
            $entities->first()->getDocumentType(),
            $documents
        );

        return $elasticClient->search();
    } 

    /**
     * Disables ES indexable observer for testing purposes
     *
     * @return void
     */
    public function withoutElasticsearchObserver(): void
    {
        $indexer = Mockery::mock(DocumentIndexer::class)->shouldIgnoreMissing();

        App::instance(DocumentIndexer::class, $indexer);
    }     
}