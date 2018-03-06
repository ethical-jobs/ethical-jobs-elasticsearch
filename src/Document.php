<?php

namespace EthicalJobs\Elasticsearch;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use EthicalJobs\Foundation\Utils\Timestamp;

/**
 * Indexable trait for eloquent models
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

trait Document
{
    /**
     * Is indexed in elastic search.
     *
     * @var bool
     */
    public $isDocument = false;

    /**
     * Hit score after querying Elasticsearch.
     *
     * @var null|int
     */
    public $documentScore = null;

    /**
     * {@inheritdoc}
     */
    public function getDocumentKey()
    {
        return $this->getKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentType()
    {
        return $this->getTable();
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentBody()
    {
        return $this->attributesToArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentMappings()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentRelations()
    {
        return [];
    }

   /**
     * Returns an instance of the indexable relation
     *
     * @param String $relation
     * @return \App\Models\Interfaces\Indexable
     */
    public function getDocumentRelation($relation)
    {
        if ($this->isIndexableRelation($relation)) {

            $relation = $this->$relation()->getModel();

            return new $relation;
        }
    }

    /**
     * Is attribute an indexable relation
     *
     * @param String $attribute
     * @return Boolean
     */
    public function isIndexableRelation($attribute)
    {
        if (! in_array($attribute, $this->getDocumentRelations())) {
            return false;
        }

        if (! method_exists($this, $attribute)) {
            return false;
        }

        if (! App::make(Index::class)->isIndexable($this->$attribute()->getModel())) {
            return false;
        }

        return true;
    }

    /**
     * Builds a tree of the document and its relations
     *
     * @return Array
     */
    public function getDocumentTree()
    {
        $body = $this->getDocumentBody();

        // Convert date params
        foreach ($this->getDates() as $dateField) {
            if (isset($this->$dateField)) {
                $body[$dateField] = Timestamp::parse($this->$dateField)->toIso8601String();
            }
        }

        // Fill relations
        foreach ($this->getDocumentRelations() as $relationKey) {
            if ($this->$relationKey !== null) {
                if ($this->isIndexableRelation($relationKey)) {
                    if ($this->$relationKey instanceof Collection) {
                        $body[$relationKey] = $this->$relationKey->map(function ($indexable) {
                            return $indexable->getDocumentBody();
                        })->toArray();
                    } else if ($this->$relationKey instanceof Indexable) {
                        $body[$relationKey] = $this->$relationKey->getDocumentBody();
                    }
                } else if ($this->relationLoaded($relationKey)) {
                    if (method_exists($this->$relationKey, 'attributesToArray')) {
                        $body[$relationKey] = $this->$relationKey->attributesToArray();
                    } else if (method_exists($this->$relationKey, 'toArray')) {
                        $body[$relationKey] = $this->$relationKey->toArray();
                    }
                }
            }
        }

        return $body;
    }
}
