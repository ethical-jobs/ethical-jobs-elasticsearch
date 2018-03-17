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
     * Query builder instance
     *
     * @param \Illuminate\Database\Eloquent\Builder
     */
    public $query;    

    /**
     * Query parameters
     *
     * @param array
     */
    protected $params = [
        'chunkSize'         => null,
        'numberOfItems'     => null,
        'numberOfChunks'    => null,
        'numberOfProcesses' => null,
        'itemsPerProcess'   => null,
        'processOffset'     => null,
        'process'           => null,
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

        $this->query = $indexable->getIndexingQuery();
    }    

    /**
     * Set the query chunk size for indexing
     *
     * @param int $chunkSize
     * @return $this
     */
    public function setChunkSize(int $chunkSize): IndexQuery
    {    
        $this->params['chunkSize'] = $chunkSize;

        $this->params['numberOfChunks'] = (int) ceil($this->query->count() / $chunkSize);

        return $this;
    }

    /**
     * Set the number of processes for multi-process indexing
     *
     * @param int $processes
     * @return $this
     */
    public function setNumberOfProcesses(int $processes): IndexQuery
    {    
        $this->params['numberOfProcesses'] = $processes;

        $this->params['itemsPerProcess'] = (int) ceil($this->query->count() / $processes);        

        return $this;
    }    

    /**
     * Splits a query into process subqueries
     *
     * @return Illuminate\Support\Collection
     */
    public function getSubQueries(): Collection
    {
        $subQueries = collect([]);

        for ($process = 1; $process <= $this->params['numberOfProcesses']; $process++) {

            $subQuery = clone $this;
            $subQuery->setParam('processOffset', ($process - 1) * $this->params['itemsPerProcess']);
            $subQuery->setParam('process', $process.'/'.$this->params['numberOfProcesses']);
            $subQueries->push($subQuery);
        }

        return $subQueries;
    } 

    /**
     * Build the underlying query builder instance
     *
     * @return $this
     */
    public function buildQuery(): IndexQuery
    {
        if ($offset = $this->getParam('processOffset')) {
            $this->query->offset($offset);
        }

        if ($limit = $this->getParam('itemsPerProcess')) {
            $this->query->limit($limit);
        }

        $this->params['numberOfItems'] = $this->query->count();

        return $this;
    }    

    /**
     * Chunks the query
     *
     * @param callable $callback
     * @return void
     */
    public function chunk(callable $callback): void
    {
        $this->query->chunk($this->getParam('chunkSize'), $callback);
    }       

    /**
     * Get query a param
     *
     * @param string $param
     * @return mixed
     */
    public function getParam(string $param)
    {
        return $this->params[$param] ?? null;
    }      

    /**
     * Set query a param
     *
     * @param string $param
     * @param mixed $value
     * @return $this
     */
    public function setParam(string $param, $value)
    {
        $this->params[$param] = $value;

        return $this;
    }       

    /**
     * Arrayable interface
     *
     * @return array
     */
    public function toArray(): array
    {
        $arrayed = array_merge([
            'indexable' => get_class($this->indexable),
        ], $this->params);

        return array_filter($arrayed, function ($item) {
            return is_null($item) === false;
        });
    }                                                      
}