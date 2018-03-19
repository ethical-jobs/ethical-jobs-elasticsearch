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
     * Elastic search client
     *
     * @param \EthicalJobs\Elasticsearch\Indexable
     */
    public $indexable;   

    /**
     * Chunks (query params that define a chunk)
     *
     * @var Collection
     */
    protected $chunks;      

    /**
     * Index query params
     *
     * @var array
     */
    protected $params = [
        'numberOfProcesses' => null,
        'currentProcess'    => null,
        'numberOfChunks'    => null,
        'chunkSize'         => null,
    ];       

    /**
     * Constructor
     *
     * @param \EthicalJobs\Elasticsearch\Indexable $indexable
     * @return void
     */
    public function __construct(Indexable $indexable)
    {
        $this->indexable = $indexable;

        $this->chunks = new Collection;
    }     

    /**
     * Split query into processes
     *
     * @param int $numberOfProcesses
     * @param int $chunkSize
     * @return void
     */
    public function split(int $numberOfProcesses, int $chunkSize): void
    {
        $this->setParam('numberOfProcesses', $numberOfProcesses);

        $this->makeChunks($chunkSize);

        $counter = 1;

        $this->chunks
            ->split($numberOfProcesses)
            ->each(function ($processChunks) use(&$counter, $numberOfProcesses) {
                $subQuery = clone $this;
                $subQuery
                    ->setChunks($processChunks)
                    ->setParam('currentProcess', "$counter/$numberOfProcesses");
                ProcessIndexQuery::dispatch($subQuery);
                $counter++;
            });
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
     * @param int $chunkSize
     * @return $this
     */
    public function makeChunks(int $chunkSize): IndexQuery
    {
        $count = $this->indexable->getIndexingQuery()->count();
        $numberOfChunks = (int) ceil($count / $chunkSize); 

        for ($chunkNum = 1; $chunkNum <= $numberOfChunks; $chunkNum++) {
            $isLastChunk = $chunkNum === $numberOfChunks;
            $this->chunks->push([
                'offset' => ($chunkNum - 1) * $chunkSize, // Pagination algorithm
                'limit'  => $isLastChunk ? PHP_INT_MAX : $chunkSize, // Encompass all records
            ]);
        }

        $this->setParam('numberOfChunks', $this->chunks->count());
        $this->setParam('chunkSize', $chunkSize);

        return $this;
    }       

    /**
     * Sets the query chunks
     *
     * @param Collection $chunks
     * @return IndexQuery
     */
    public function setChunks(Collection $chunks): IndexQuery
    {
        $this->chunks = $chunks;

        return $this;
    }       

    /**
     * Returns the query chunks
     *
     * @return Collection
     */
    public function getChunks(): Collection
    {
        return $this->chunks;
    }     

    /**
     * Sets a query param
     *
     * @param string $name
     * @param mixed $value
     * @return IndexQuery
     */
    public function setParam(string $name, $value): IndexQuery
    {
        $this->params[$name] = $value;

        return $this;
    }       

    /**
     * Returns a query param
     *
     * @var string $paramPath
     * @return mixed
     */
    public function getParam(string $paramPath)
    {
        return array_get($this->params, $paramPath);
    }                                                                 
}