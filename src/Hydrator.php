<?php

namespace EthicalJobs\Elasticsearch;

/**
 * Document Hydrator Interface
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

interface Hydrator
{
    /**
     * Hydrates entities from elasticsearch response
     *
     * @param array $response
     * @param \App\Services\Elasticsearch\Indexable $indexable
     * @return \Illuminate\Support\Collection
     */
    public function hydrateFromResponse(array $response, $indexable);
}
