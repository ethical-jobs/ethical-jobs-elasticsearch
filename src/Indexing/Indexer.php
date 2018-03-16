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
     * @param \App\Models\Interfaces\Indexable $indexable
     * @return Array
     */
    public function indexDocument(Indexable $indexable)
    {
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
     * @param \App\Models\Interfaces\Indexable $indexable
     * @return Array
     */
    public function deleteDocument(Indexable $indexable)
    {
        return $this->client->delete([
            'index'     => $this->index->getIndexName(),
            'id'        => $indexable->getDocumentKey(),
            'type'      => $indexable->getDocumentType(),
        ]);
    }    

    /**
     * Indexes indexables from query
     *
     * @param \Illuminate\Support\Collection $query
     * @param int $chunkSize
     * @return void
     */
    public function indexByQuery(Builder $query, int $chunkSize = 300): void
    {
        $start = microtime(true);

        $this->log('Indexing documents', [
            'items'         => $query->count(),
            'totalChunks'   => $totalChunks = ceil($query->count() / $chunkSize),
            'chunkSize'     => $chunkSize,
        ]);

        $progress = new ProgressBar(new ConsoleOutput, $totalChunks);

        $query->latest()->chunk($chunkSize, function ($chunk) use ($progress) {

            $response = $this->bulkRequest($chunk);

            if (Utilities::isResponseValid($response) === false) {
                $this->log('Indexing error', Utilities::getResponseErrors($response), '#f44242');
                throw new IndexingException('Invalid request parameters');
            }

            $progress->advance();
        });

        $this->log('Indexing complete', [
            'elapsed' => microtime(true) - $start.' seconds',
            'items'   => $query->count(),
        ]);

        $progress->finish();
    }

    /**
     * Indexes indexables via a query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $processes
     * @param int $chunkSize
     * @return void
     */
    public function queueIndexByQuery(Builder $query, int $processes = 3, int $chunkSize = 300): void
    {
        $pageSize = ceil($query->count() / $processes);

        $this->log('Queueing documents', [
            'items'     => $query->count(),
            'processes' => $processes,
            'pageSize'  => $pageSize,
            'chunkSize' => $chunkSize,
        ], '#4286f4');        

        while ($processes !== 0) {

            $offset = (int) (($processes - 1) * $pageSize);

            $queryPage = $query
                ->offset($offset)
                ->limit($pageSize);

            ProcessIndexQuery::dispatch($queryPage, $chunkSize);

            $processes--;
        }
    }    

    /**
     * Creates a request from a collection of indexables
     *
     * @param \Illuminate\Support\Collection $collection
     * @param Bool $isDeleteRequest
     * @return Array
     */
    protected function bulkRequest(Collection $collection)
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
    protected function log(string $message = '', array $data = [], string $color = '#86f442'): void
    {
        $processId = app()->environment()."::".gethostname()."::".getmypid();
        
        $this->logger->message("*_".$processId."_* $message", $data, $color);
    }
}