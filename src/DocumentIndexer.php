<?php

namespace EthicalJobs\Elasticsearch;

use Elasticsearch\Client;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use EthicalJobs\Elasticsearch\Indexable;

/**
 * Indexes documents in elasticsearch
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class DocumentIndexer
{
    /**
     * Elastic search client
     *
     * @param \Elasticsearch\Client
     */
    private $elasticsearch;

    /**
     * Elastic search index
     *
     * @param \App\Services\Elasticsearch\Index
     */
    private $index;

    /**
     * Size of collection request chunks
     *
     * @param Integer
     */
    private $chunkSize;

    /**
     * Log output to console
     *
     * @param Boolean
     */
    private $log = false;

    /**
     * Constructor
     *
     * @param \Elasticsearch\Client $elasticsearch
     * @param \App\Services\Elasticsearch\Index $index
     * @param Integer $chunkSize
     * @return void
     */
    public function __construct(Client $elasticsearch, Index $index, $chunkSize = 450)
    {
        \DB::disableQueryLog();

        $this->setClient($elasticsearch);

        $this->setChunkSize($chunkSize);

        $this->index = $index;
    }

    /**
     * Sets the elasticsearch client
     *
     * @param \Elasticsearch\Client $elasticsearch
     * @return $this
     */
    public function setClient(Client $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;

        return $this;
    }

    /**
     * Sets the chunksize
     *
     * @param Integer $chunkSize
     * @return $this
     */
    public function setChunkSize($chunkSize)
    {
        $this->chunkSize = $chunkSize;

        return $this;
    }

    /**
     * Sets the chunksize
     *
     * @param Boolean $logOutput
     * @return $this
     */
    public function setLogging($logOutput)
    {
        $this->log = (boolean) $logOutput;

        return $this;
    }

    /**
     * Indexes a indexable instance
     *
     * @param \App\Models\Interfaces\Indexable $indexable
     * @return Array
     */
    public function indexDocument(Indexable $indexable)
    {
        $params = $this->buildRequest($indexable);

        return $this->elasticsearch->index($params);
    }

    /**
     * Removes a document from the index
     *
     * @param \App\Models\Interfaces\Indexable $indexable
     * @return Array
     */
    public function deleteDocument(Indexable $indexable)
    {
        $params = $this->buildRequest($indexable, true);

        return $this->elasticsearch->delete($params);
    }

    /**
     * Indexes a collection of indexables
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return Array
     */
    public function indexCollection(Builder $query)
    {
        return $this->bulkRequest($query, false);
    }

    /**
     * Removes a collection of indexables from the index
     *
     * @param \Illuminate\Support\Collection $collection
     * @return Array
     */
    public function deleteCollection(Collection $collection)
    {
        return $this->bulkRequest($collection, true);
    }

    /**
     * Runs an operation on a collection
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Boolean $isDeleteRequest
     * @return Array
     */
    protected function bulkRequest(Builder $query, $isDeleteRequest = false)
    {
        $counter = 1;

        $total = $query->count();

        $totalChunks = ceil($total / $this->chunkSize);

        $this->log("Discovered {$query->count()} items.");

        $this->log("Hacking into {$totalChunks} chunks of {$this->chunkSize} items.");

        $query->latest()->chunk($this->chunkSize, function ($chunk) use (&$counter, $totalChunks, $isDeleteRequest) {

            $this->log("Indexing chunk {$counter}/{$totalChunks}");

            $params = $this->buildBulkRequest($chunk, $isDeleteRequest);

            $response = $this->elasticsearch->bulk($params);

            if (! Utilities::isResponseValid($response)) {
                $this->log(Utilities::getResponseErrors($response));
                throw new \Exception('Invalid request parameters');
            }

            $counter++;
        });

        return [];
    }

    /**
     * Creates a request an indexable
     *
     * @param \App\Models\Interfaces\Indexable $indexable
     * @param Bool $isDeleteRequest
     * @return Array
     */
    protected function buildRequest(Indexable $indexable, $isDeleteRequest = false)
    {
        $params = [
            'index'     => $this->index->getIndexName(),
            'id'        => $indexable->getDocumentKey(),
            'type'      => $indexable->getDocumentType(),
        ];

        if (! $isDeleteRequest) {
            $params['body'] = $indexable->getDocumentTree();
        }

        return $params;
    }

    /**
     * Creates a request from a collection of indexables
     *
     * @param \Illuminate\Support\Collection $collection
     * @param Bool $isDeleteRequest
     * @return Array
     */
    protected function buildBulkRequest(Collection $collection, $isDeleteRequest = false)
    {
        $actionKey = $isDeleteRequest ? 'delete' : 'index';

        $params = [];

        foreach ($collection as $indexable) {

            $params['body'][] = [
                $actionKey => [
                    '_index' => $this->index->getIndexName(),
                    '_id'    => $indexable->getDocumentKey(),
                    '_type'  => $indexable->getDocumentType(),
                ],
            ];

            if (! $isDeleteRequest) {
                $params['body'][] = $indexable->getDocumentTree();
            }
        }

        return $params;
    }

    /**
     * Logs activity to the console
     *
     * @param Mixed $data
     * @return Void
     */
    protected function log($data)
    {
        if ($this->log) {
            dump($data);
        }
    }
}