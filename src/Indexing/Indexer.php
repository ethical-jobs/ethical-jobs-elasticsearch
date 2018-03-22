<?php

namespace EthicalJobs\Elasticsearch\Indexing;

use Elasticsearch\Client;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use EthicalJobs\Elasticsearch\Exceptions\IndexingException;
use EthicalJobs\Elasticsearch\Indexing\Logging\Logger;
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
     * Elastic search index name
     *
     * @param string
     */
    private $indexName;

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
     * @param \EthicalJobs\Elasticsearch\Indexing\Logging\Logger $logger
     * @param string $indexName
     * @return void
     */
    public function __construct(Client $client, Logger $logger, string $indexName)
    {
        \DB::disableQueryLog();

        $this->client = $client;

        $this->logger = $logger;

        $this->indexName = $indexName;
    }

    /**
     * Indexes a indexable instance
     *
     * @param \EthicalJobs\Elasticsearch\Indexabl $indexable
     * @return array
     */
    public function indexDocument(Indexable $indexable): array
    {
        $params = [
            'index'     => $this->indexName,
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
     * @return array
     */
    public function deleteDocument(Indexable $indexable): array
    {
        $params = [
            'index'     => $this->indexName,
            'id'        => $indexable->getDocumentKey(),
            'type'      => $indexable->getDocumentType(),
        ];

        $this->logger->log('Deleting document', $params);

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
        $this->logger->join($indexQuery);

        $indexQuery->chunk(function($chunk, $index) use($indexQuery) {

            $response = $this->bulkRequest($chunk);

            if (Utilities::isResponseValid($response) === false) {
                $this->logger->log('Indexing error', Utilities::getResponseErrors($response));
                throw new IndexingException('Invalid request parameters');
            }
        });

        $this->logger->complete($indexQuery);
    } 

    /**
     * Queues index queries into seperate processes
     *
     * @param \EthicalJobs\Elasticsearch\Indexing\IndexQuery $indexQuery
     * @return void
     */
    public function queueQuery(IndexQuery $indexQuery): void
    {
        $this->logger->start($indexQuery);

        ProcessIndexQuery::dispatch($indexQuery);
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
                    '_index' => $this->indexName,
                    '_id'    => $indexable->getDocumentKey(),
                    '_type'  => $indexable->getDocumentType(),
                ],
            ];

            $params['body'][] = $indexable->getDocumentTree();
        }

        return $this->client->bulk($params);
    }
}