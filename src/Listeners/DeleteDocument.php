<?php

namespace EthicalJobs\Elasticsearch\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use EthicalJobs\Elasticsearch\DocumentIndexer;

/**
 * Delete elasticsearch indexable
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class DeleteDocument implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * Elastic search index service
     *
     * @param \App\Services\Elasticsearch\DocumentIndexer
     */
    private $indexer;

    /**
     * Constructor
     *
     * @param \App\Services\Elasticsearch\DocumentIndexer $indexer
     * @return void
     */
    public function __construct(DocumentIndexer $indexer)
    {
        $this->indexer = $indexer;
    }

    /**
     * Handle the event.
     *
     * @param Mixed $event
     * @return Void
     */
    public function handle($event)
    {
        $this->indexer->deleteDocument($event->indexable);
    }
}