<?php

namespace EthicalJobs\Elasticsearch\Hydrators;

use ArrayObject;
use Illuminate\Support\Collection;
use EthicalJobs\Elasticsearch\Hydrator;

/**
 * Hydrates ArrayObjects from elasticsearch results
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */
class ArrayObjectHydrator implements Hydrator
{
    /**
     * Elastic search client
     *
     * @param App\Services\Elasticsearch\Indexable
     */
    protected $indexable;

    /**
     * Hydrates ArrayObjects from elasticsearch results
     *
     * @param arra $response
     * @param App\Services\Elasticsearch\Indexable $indexable
     * @return Illuminate\Support\Collection
     */
    public function hydrateFromResponse(array $response, $indexable)
    {
        $this->indexable = $indexable;

        if (empty($response)) {
            return new Collection;
        }

        $items = $response['hits']['hits'] ?? [];

        $results = [];

        foreach ($items as $hit) {
            $results[] = $this->hydrateRecursive($hit);
        }

        return new Collection($results);
    }

    /**
     * Hydrates a elastic hit
     *
     * @param Array $item
     * @return ArrayObject
     */
    protected function hydrateRecursive(Array $item)
    {
        $relations = $this->indexable->getDocumentRelations();

        $hit = $item['_source'] ?? [];

        $hit['_score'] = $item['_score'] ?? 0;
        $hit['_isDocument'] = true;

        $relationHits = [];

        foreach ($relations as $relation) {
            if (isset($hit[$relation]) && is_array($hit[$relation])) {
                $relationHits[$relation] = $this->hydrate($hit[$relation]);
            }
        }

        $hit = array_merge($hit, $relationHits);

        return new ArrayObject($hit, ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Hydrates a elastic hit
     *
     * @param Array $hit
     * @return ArrayObject
     */
    protected function hydrate(Array $hit)
    {
        return new ArrayObject($hit, ArrayObject::ARRAY_AS_PROPS);
    }
}
