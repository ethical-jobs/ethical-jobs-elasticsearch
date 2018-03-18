<?php

namespace EthicalJobs\Elasticsearch\Indexing;

use Elasticsearch\Client;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
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
        $this->log('Indexing document', [
            'index'     => $this->index->getIndexName(),
            'id'        => $indexable->getDocumentKey(),
            'type'      => $indexable->getDocumentType(),
            'body'      => $indexable->getDocumentTree(),
        ]);

        return $this->client->index([
            'index'     => $this->index->getIndexName(),
            'id'        => $indexable->getDocumentKey(),
            'type'      => $indexable->getDocumentType(),
            'body'      => $indexable->getDocumentTree(),
        ]);
    }

    /**
     * Deletes a indexable instance
     *
     * @param \EthicalJobs\Elasticsearch\Indexabl $indexable
     * @return Array
     */
    public function deleteDocument(Indexable $indexable)
    {
        $this->log('Deleteing document', [
            'index'     => $this->index->getIndexName(),
            'id'        => $indexable->getDocumentKey(),
            'type'      => $indexable->getDocumentType(),
            'body'      => $indexable->getDocumentTree(),
        ]);

        return $this->client->delete([
            'index'     => $this->index->getIndexName(),
            'id'        => $indexable->getDocumentKey(),
            'type'      => $indexable->getDocumentType(),
        ]);
    }    

    /**
     * Indexes all items of an indexable
     *
     * @param \EthicalJobs\Elasticsearch\Indexing\IndexQuery $indexQuery
     * @return void
     */
    public function indexQuery(IndexQuery $indexQuery): void
    {
        $start = microtime(true);

        $indexQuery->buildQuery();

        $this->log('Indexing by query', $indexQuery->toArray());

        $progress = new ProgressBar(new ConsoleOutput, $indexQuery->getParam('numberOfChunks'));

        $indexQuery->chunk(function($chunk) use($progress) {

            $response = $this->bulkRequest($chunk);

            if (Utilities::isResponseValid($response) === false) {
                $this->log('Indexing error', Utilities::getResponseErrors($response));
                throw new IndexingException('Invalid request parameters');
            }

            $progress->advance();
        });

        $this->log('Indexing complete', array_merge([
            'timeSpentIndexing' => microtime(true) - $start.' seconds',
        ], $indexQuery->toArray()));

        $progress->finish();
    }

    /**
     * Queues and indexable for indexing
     *
     * @param \EthicalJobs\Elasticsearch\Indexing\IndexQuery $indexQuery
     * @return void
     */
    public function queueQuery(IndexQuery $indexQuery): void
    {
        $indexQuery->buildQuery();
        
        $this->log('Queueing indexing processes', $indexQuery->toArray());     

        foreach ($indexQuery->getSubQueries() as $subQuery) {
            ProcessIndexQuery::dispatch($indexQuery);
        }
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

    /**
     * Logs activity
     *
     * @param string $message
     * @param array $data
     * @param string $color
     * @return Void
     */
    protected function log(string $message = '', array $data = []): void
    {
        $processId = app()->environment()."::".gethostname()."::".getmypid();
        
        $this->logger->message("$processId - $message", $data);
    }
}