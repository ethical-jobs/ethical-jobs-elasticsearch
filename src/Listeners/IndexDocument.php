<?php

namespace EthicalJobs\Elasticsearch\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use EthicalJobs\Elasticsearch\DocumentIndexer;

/**
 * Indexes a document
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class IndexDocument implements ShouldQueue
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
        $this->indexer->indexDocument($event->indexable);
    }
}