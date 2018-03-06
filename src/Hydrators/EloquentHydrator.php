<?php

namespace EthicalJobs\Elasticsearch\Hydrators;

use Carbon\Carbon;
use ReflectionMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use EthicalJobs\Elasticsearch\Hydrator;

/**
 * Hydrates eloquent models from elasticsearch results
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class EloquentHydrator implements Hydrator
{
    /**
     * Hydrates eloquent models from elasticsearch response
     *
     * @param array $response
     * @param App\Models\Interfaces\Indexable $indexable
     * @return Illuminate\Support\Collection
     */
    public function hydrateFromResponse(array $response, $indexable)
    {
        $indexables = [];

        $hits = $response['hits']['hits'] ?? null;

        if (! $hits || count($hits) < 1) {
            return $this->toCollection([]);
        }

        foreach ($hits as $hit) {
            $indexables[] = $this->hydrateEntityRecursive($indexable, $hit['_source'], $hit['_score']);
        }

        return $this->toCollection($indexables);
    }

    /**
     * Hydrates an eloquent model from an array of attributes
     *
     * @param Illuminate\Database\Eloquent\Model $entity
     * @param array $attributes
     * @param Integer $score
     * @return Illuminate\Database\Eloquent\Model
     */
    protected function hydrateEntityRecursive(Model $entity, array $attributes, $score = null)
    {
        $instance = $this->hydrateEntity($entity, $attributes, $score);

        $instance = $this->hydrateRelations($instance);

        return $instance;
    }

    /**
     * Hydrates an eloquent model from an array of attributes
     *
     * @param Illuminate\Database\Eloquent\Model $entity
     * @param array $attributes
     * @param Integer $score
     * @return Illuminate\Database\Eloquent\Model
     */
    protected function hydrateEntity(Model $entity, array $attributes, $score = null)
    {
        $instance = $entity->newInstance([], true);

        $instance->setRawAttributes((array) $attributes, true);

        $instance = $this->parseDateFields($instance);

        $instance->_isDocument = true;

        $instance->_score = $score;

        return $instance;
    }

    /**
     * Parses date attributes
     *
     * @param \Illuminate\Database\Eloquent\Model $entity
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function parseDateFields(Model $entity)
    {
        foreach ($entity->getDates() as $dateAttribute) {
            if ($date = $entity->getOriginal($dateAttribute)) {
                $entity->setAttribute($dateAttribute, Carbon::parse($date));
            }
        }

        return $entity;
    }

    /**
     * Hydrates the relations of an indexable
     *
     * @param \Illuminate\Database\Eloquent\Model $entity
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function hydrateRelations(Model $entity)
    {

        $attributes = $entity->getAttributes();

        foreach ($attributes as $attribute => $value) {

            if ($value) {

                if (! in_array($attribute, $entity->getDocumentRelations())) {
                    continue;
                }

                $reflection_method = new ReflectionMethod($entity, $attribute);

                if ($reflection_method->class != Model::class) {

                    $relation = $entity->$attribute();

                    if ($relation instanceof Relation) {

                        // Check if the document array is supposed to be single or collection
                        if (is_null($value) === true || count($value) == count($value, COUNT_RECURSIVE)) {
                            $value = [$value];
                        }

                        $models = [];

                        foreach ($value as $relationValue) {
                            if ($relationValue) {
                                $models[] = $this->hydrateEntity($relation->getModel(), $relationValue);
                            }
                        }

                        $models = $this->toCollection($models);

                        // Unset attribute before match relation
                        unset($entity[$attribute]);
                        $relation->match([$entity], $models, $attribute);

                    }
                }
            }
        }

        return $entity;
    }

    /**
     * Converts array to collection
     *
     * @param array $entities
     * @return Illuminate\Database\Eloquent\Collection
     */
    protected function toCollection(array $entities)
    {
        return Collection::make($entities);
    }
}
