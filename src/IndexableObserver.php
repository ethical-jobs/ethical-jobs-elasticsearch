<?php

namespace EthicalJobs\Elasticsearch;

use Illuminate\Support\Facades\Log;
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
        $this->indexDocument($indexable); 
    }

    /**
     * Listens to the updated event
     *
     * @param Illuminate\Database\Eloquent\Model $indexable
     * @return void
     */
    public function updated(Model $indexable)
    {
        $this->indexDocument($indexable); 
    }

    /**
     * Listen to the deleting event.
     *
     * @param Illuminate\Database\Eloquent\Model $indexable
     * @return void
     */
    public function deleted(Model $indexable)
    {
        if (Utilities::isSoftDeletable($indexable) && !$indexable->isForceDeleting()) {
            $this->indexDocument($indexable); 
        } else {
            $this->deleteDocument($indexable);   
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
        $this->indexDocument($indexable); 
    }

    /**
     * Executes index action. Swallow and log.
     *
     * @param Illuminate\Database\Eloquent\Model $indexable
     * @return void
     */
    protected function indexDocument(Model $indexable)
    {
        try {
            $this->indexer->indexDocument($indexable); 
        } catch (\Exception $exception) {
            Log::critical("ej:es:indexing - : ".$exception->getMessage(), $indexable->toArray());
        }
    }    

    /**
     * Executes delete action. Swallow and log.
     *
     * @param Illuminate\Database\Eloquent\Model $indexable
     * @return void
     */
    protected function deleteDocument(Model $indexable)
    {
        try {
            $this->indexer->deleteDocument($indexable); 
        } catch (\Exception $exception) {
            Log::critical("ej:es:deleting - : ".$exception->getMessage(), $indexable->toArray());
        }
    }        
}