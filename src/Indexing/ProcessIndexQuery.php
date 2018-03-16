<?php

namespace EthicalJobs\Elasticsearch\Indexing;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Processes a queued index query
 *
 * @author  Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class ProcessIndexQuery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Query to be indexed
     * 
     * @var Illuminate\Database\Eloquent\Builder
     */
    public $query;

    /**
     * Chunk size for downstream indexing
     * 
     * @var int
     */
    public $chunkSize;    

    /**
     * Create a new job instance.
     *
     * @param  Illuminate\Database\Eloquent\Builder $query
     * @param int $chunkSize
     * @return void
     */
    public function __construct(Builder $query, int $chunkSize)
    {
        $this->query = $query;

        $this->chunkSize = $chunkSize;
    }

    /**
     * Execute the job.
     *
     * @param  EthicalJobs\Elasticsearch\Indexing\Indexer  $indexer
     * @return void
     */
    public function handle(Indexer $indexer): void
    {
        $indexer->indexByQuery($this->query, $this->chunkSize);
    }
}