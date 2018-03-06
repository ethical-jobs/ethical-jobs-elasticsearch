<?php

namespace EthicalJobs\Elasticsearch\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;

/**
 * Indexable entity was updated event
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class IndexableUpdated
{
    use SerializesModels;

    /**
     * Indexable entity
     *
     * @param Illuminate\Database\Eloquent\Model
     */
    public $indexable;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Model $indexable)
    {
        $this->indexable = $indexable;
    }
}
