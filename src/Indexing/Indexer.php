<?php

namespace EthicalJobs\Elasticsearch\Indexing;

use Elasticsearch\Client;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use EthicalJobs\Elasticsearch\Exceptions\IndexingException;
use EthicalJobs\Elasticsearch\Indexable;
use EthicalJobs\Elasticsearch\Utilities;
use EthicalJobs\Elasticsearch\Index;

/**
 * Indexes documents in elasticsearch
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class Indexer
{
    /**
     * Elastic search client
     *
     * @param \Elasticsearch\Client
     */
    private $client;

    /**
     * Elastic search index
     *
     * @param \EthicalJobs\Elasticsearch\Index
     */
    private $index;

    /**
     * Slack logging instance
     *
     * @param \EthicalJobs\Elasticsearch\Indexing\Logger
     */
    private $logger;    

    /**
     * Constructor
     *
     * @param \Elasticsearch\Client $client
     * @param \EthicalJobs\Elasticsearch\Index $index
     * @param \EthicalJobs\Elasticsearch\Indexing\Logger $logger
     * @return void
     */
    public function __construct(Client $client, Index $index, Logger $logger)
    {
        \DB::disableQueryLog();

        $this->client = $client;

        $this->index = $index;

        $this->logger = $logger;
    }

    /**
     * Indexes a indexable instance
     *
     * @param \EthicalJobs\Elasticsearch\Indexabl $indexable
     * @return Array
     */
    public function indexDocument(Indexable $indexable)
    {
        $params = [
            'index'     => $this->index->getIndexName(),
            'id'        => $indexable->getDocumentKey(),
            'type'      => $indexable->getDocumentType(),
            'body'      => $indexable->getDocumentTree(),
        ];

        $this->logger->log('Indexing document', $params);

        return $this->client->index($params);
    }

    /**
     * Deletes a indexable instance
     *
     * @param \EthicalJobs\Elasticsearch\Indexabl $indexable
     * @return Array
     */
    public function deleteDocument(Indexable $indexable)
    {
        $params = [
            'index'     => $this->index->getIndexName(),
            'id'        => $indexable->getDocumentKey(),
            'type'      => $indexable->getDocumentType(),
        ];

        $this->logger->log('Deleteing document', $params);

        return $this->client->delete($params);
    }    

    /**
     * Indexes all items of an indexable
     *
     * @param \EthicalJobs\Elasticsearch\Indexing\IndexQuery $indexQuery
     * @return void
     */
    public function indexQuery(IndexQuery $indexQuery): void
    {
        $this->logger->start($indexQuery);

        $indexQuery->chunk(function($chunk, $index) {

            $response = $this->bulkRequest($chunk);

            if (Utilities::isResponseValid($response) === false) {
                $this->logger->log('Indexing error', Utilities::getResponseErrors($response));
                throw new IndexingException('Invalid request parameters');
            }

            $this->logger->progress();
        });

        $this->logger->finish();
    } 

    /**
     * Creates a request from a collection of indexables
     *
     * @param \Illuminate\Support\Collection $collection
     * @param Bool $isDeleteRequest
     * @return array
     */
    protected function bulkRequest(Collection $collection): array
    {
        $params = [];

        foreach ($collection as $indexable) {

            $params['body'][] = [
                'index' => [
                    '_index' => $this->index->getIndexName(),
                    '_id'    => $indexable->getDocumentKey(),
                    '_type'  => $indexable->getDocumentType(),
                ],
            ];

            $params['body'][] = $indexable->getDocumentTree();
        }

        return $this->client->bulk($params);
    }
}