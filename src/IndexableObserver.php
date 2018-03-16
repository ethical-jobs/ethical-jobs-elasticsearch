<?php

namespace EthicalJobs\Elasticsearch;

use Illuminate\Database\Eloquent\Model;
use EthicalJobs\Elasticsearch\Events;
use EthicalJobs\Elasticsearch\Utilities;
use EthicalJobs\Elasticsearch\Indexing\Indexer;

/**
 * Updates elasticsearch from eloquent model events.
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class IndexableObserver
{
    /**
     * Elastic search index service
     *
     * @param \EthicalJobs\Elasticsearch\Indexing\Indexer
     */
    private $indexer;

    /**
     * Constructor
     *
     * @param \EthicalJobs\Elasticsearch\Indexing\Indexer $indexer
     * @return void
     */
    public function __construct(Indexer $indexer)
    {
        $this->indexer = $indexer;
    }

    /**
     * Listens to the created event
     *
     * @param Illuminate\Database\Eloquent\Model $indexable
     * @return void
     */
    public function created(Model $indexable)
    {
        $this->indexer->indexDocument($indexable); 
    }

    /**
     * Listens to the updated event
     *
     * @param Illuminate\Database\Eloquent\Model $indexable
     * @return void
     */
    public function updated(Model $indexable)
    {
        $this->indexer->indexDocument($indexable); 
    }

    /**
     * Listen to the deleting event.
     *
     * @param Illuminate\Database\Eloquent\Model $indexable
     * @return void
     */
    public function deleted(Model $indexable)
    {
        if (Utilities::isSoftDeletable($indexable)) { 
            $this->indexer->indexDocument($indexable); 
        } else {
            $this->indexer->deleteDocument($indexable);   
        }
    }

    /**
     * Listen to the restored event.
     *
     * @param Illuminate\Database\Eloquent\Model $indexable
     * @return void
     */
    public function restored(Model $indexable)
    {
        $this->indexer->indexDocument($indexable); 
    }
}