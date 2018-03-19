<?php

namespace EthicalJobs\Elasticsearch\Testing;

use Mockery;
use Illuminate\Support\Collection;
use M6Web\Component\ElasticsearchMock\Client as MockClient;
use EthicalJobs\Elasticsearch\IndexableObserver;
use EthicalJobs\Elasticsearch\Indexing\Indexer;

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
    public function getSearchResults(Collection $entities)
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
     * Mocks empty elasticsearch results
     *
     * @return MockClient
     */
    public function getEmptySearchResults()
    {
        $elasticClient = new MockClient;

        $elasticClient->addSearchResult('test-index', 'mock_document_type', []);

        return $elasticClient->search();
    }     

    /**
     * Disables ES indexable observer for testing purposes
     *
     * @return void
     */
    public function withoutElasticsearchObserver(): void
    {
        $this->app->bind(IndexableObserver::class, function(){
            return Mockery::mock(IndexableObserver::class)->shouldIgnoreMissing();
        });  
    }     

    /**
     * Enable ES indexable observer for testing purposes
     *
     * @return void
     */
    public function withElasticsearchObserver(): void
    {
        $this->app->bind(IndexableObserver::class, function(){
            return new IndexableObserver(resolve(Indexer::class));
        });  
    }       
}