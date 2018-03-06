<?php

namespace EthicalJobs\Elasticsearch\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;

/**
 * Indexable entity was created event
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class IndexableCreated
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
