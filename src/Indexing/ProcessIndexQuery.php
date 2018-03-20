<?php

namespace EthicalJobs\Elasticsearch\Indexing;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
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
     * Indexing query instance
     * 
     * @var EthicalJobs\Elasticsearch\Indexing\IndexQuery
     */
    public $indexQuery;  

    /**
     * Create a new job instance.
     *
     * @param EthicalJobs\Elasticsearch\Indexing\IndexQuery $indexQuery
     * @return void
     */
    public function __construct(IndexQuery $indexQuery)
    {
        $this->indexQuery = $indexQuery;
    }

    /**
     * Execute the job.
     *
     * @param  EthicalJobs\Elasticsearch\Indexing\Indexer  $indexer
     * @return void
     */
    public function handle(Indexer $indexer): void
    {
        $indexer->indexQuery($this->indexQuery);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags()
    {
        return [
            'es', 
            'es:indexing', 
            'es:indexing:'.$this->indexQuery->uuid, 
            'es:indexing:indexable:'.get_class($this->indexQuery->indexable), 
        ];
    }    
}