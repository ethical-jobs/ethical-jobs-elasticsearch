<?php

namespace EthicalJobs\Elasticsearch;

use Illuminate\Database\Eloquent\Model;
use EthicalJobs\Elasticsearch\Events;

/**
 * Updates elastic search from eloquent model events.
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class Observer
{
    /**
     * Listens to the created event
     *
     * @param Illuminate\Database\Eloquent\Model $indexable
     * @return void
     */
    public function created(Model $indexable)
    {
        event(new Events\IndexableCreated($indexable));
    }

    /**
     * Listens to the updated event
     *
     * @param Illuminate\Database\Eloquent\Model $indexable
     * @return void
     */
    public function updated(Model $indexable)
    {
        event(new Events\IndexableUpdated($indexable));
    }

    /**
     * Listen to the restored event.
     *
     * @param Illuminate\Database\Eloquent\Model $indexable
     * @return void
     */
    public function restored(Model $indexable)
    {
        event(new Events\IndexableRestored($indexable));
    }

    /**
     * Listen to the deleted event.
     *
     * @param Illuminate\Database\Eloquent\Model $indexable
     * @return void
     */
    public function deleted(Model $indexable)
    {
        $isSoftDelete = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses($indexable));

        if ($isSoftDelete) {
            event(new Events\IndexableUpdated($indexable));    
        } else {
            event(new Events\IndexableDeleted($indexable));    
        }
    }
}