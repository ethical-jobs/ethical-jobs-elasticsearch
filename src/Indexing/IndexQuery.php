<?php

namespace EthicalJobs\Elasticsearch\Indexing;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use EthicalJobs\Elasticsearch\Indexable;

/**
 * Queries the database for indexables to index
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class IndexQuery
{
    /**
     * UUID to identify a query and its subQueries
     *
     * @param string
     */
    public $uuid;  

    /**
     * Elastic search client
     *
     * @param \EthicalJobs\Elasticsearch\Indexable
     */
    public $indexable;   

    /**
     * Size of the chunks
     *
     * @var int
     */
    public $chunkSize;   

    /**
     * Number of processes
     *
     * @var int
     */
    public $processCount = 1;      

    /**
     * Chunks (query params that define a chunk)
     *
     * @var Collection
     */
    protected $chunks;            

    /**
     * Constructor
     *
     * @param \EthicalJobs\Elasticsearch\Indexable $indexable
     * @param int $chunkSize
     * @return void
     */
    public function __construct(Indexable $indexable, int $chunkSize)
    {
        $this->uuid = str_random(20);

        $this->indexable = $indexable;

        $this->chunks = new Collection;

        $this->chunkSize = $chunkSize;

        $this->makeChunks();
    }   

    /**
     * Sets the query chunks
     *
     * @var array $chunks
     * @return $this
     */
    public function setChunks(array $chunks): IndexQuery
    {
        $this->chunks = new Collection($chunks);

        return $this;
    }

    /**
     * Returns the query chunks
     *
     * @return \Illuminate\Support\Collection
     */
    public function getChunks(): Collection
    {
        return $this->chunks;
    } 

    /**
     * Sets the number of processes
     *
     * @var int $processCount
     * @return $this
     */
    public function setNumberOfProcesses(int $processCount): IndexQuery
    {
        $this->processCount = $processCount;

        return $this;
    }      

    /**
     * Returns the number of documents in the indexable query
     *
     * @return int
     */
    public function documentCount(): int
    {
        return $this->indexable->getIndexingQuery()->count();
    }    

    /**
     * Returns the number of chunks in the indexable query
     *
     * @return int
     */
    public function chunkCount(): int
    {
        return $this->chunks->count();
    }     

    /**
     * Returns the number of processes
     *
     * @return int
     */
    public function processCount(): int
    {
        return $this->processCount;
    }           

    /**
     * Split query into multiple SubQueries
     *
     * @return Collection
     */
    public function split(): Collection
    {
        $queries = new Collection;

        $this->chunks
            ->split($this->processCount())
            ->each(function($chunks) use($queries) {
                $query = clone $this;
                $query->setChunks($chunks->toArray());
                $queries->push($query);
            });

        return $queries;
    }       

    /**
     * Chunks an indexable's indexing query
     *
     * @param callable $callback
     * @return void
     */
    public function chunk(callable $callback): void
    {
        $index = 0;

        $this->chunks->each(function($params) use($callback, &$index) {
            $nextChunk = $this->indexable
                ->getIndexingQuery()
                ->offset($params['offset'])
                ->limit($params['limit'])
                ->get();
            $callback($nextChunk, $index);
            $index++;
        });
    }     

    /**
     * Divides an indexable into query chunks
     *
     * @return $this
     */
    protected function makeChunks(): IndexQuery
    {
        $numberOfChunks = (int) ceil($this->documentCount() / $this->chunkSize); 

        for ($chunkNum = 1; $chunkNum <= $numberOfChunks; $chunkNum++) {
            $isLastChunk = $chunkNum === $numberOfChunks;
            $this->chunks->push([
                'offset' => ($chunkNum - 1) * $this->chunkSize, // Pagination algorithm
                'limit'  => $isLastChunk ? PHP_INT_MAX : $this->chunkSize, // Encompass all records
            ]);
        }

        return $this;
    }                                                                      
}