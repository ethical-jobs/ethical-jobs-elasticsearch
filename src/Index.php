<?php

namespace EthicalJobs\Elasticsearch;

use Elasticsearch\Client;
use Illuminate\Database\Eloquent\Model;
use EthicalJobs\Elasticsearch\Indexable;

/**
 * Elasticsearch index manager
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class Index
{
    /**
     * Index settings
     *
     * @param \EthicalJobs\Foundation\IndexSettings
     */
    private $settings = [];

    /**
     * Elastic search client
     *
     * @param \Elasticsearch\Client
     */
    private $elasticsearch;    

    /**
     * Constructor
     *
     * @param \Elasticsearch\Client $elasticsearch
     * @param \EthicalJobs\Foundation\IndexSettings $settings
     * @return void
     */
    public function __construct(Client $elasticsearch, IndexSettings $settings)
    {
        $this->setClient($elasticsearch);

        $this->settings = $settings;
    }

    /**
     * Sets the elasticsearch client
     *
     * @param \Elasticsearch\Client $elasticsearch
     * @return $this
     */
    public function setClient(Client $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;

        return $this;
    }

    /**
     * Returns the index settings
     *
     * @return Array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Truth test if entity is indaxable
     *
     * @param \Illuminate\Database\Eloquent\Model $entity
     * @return Bool
     */
    public function isIndexable(Model $entity)
    {
        if (in_array(get_class($entity), $this->getSettings()->getIndexables())) {
            return true;
        }

        return false;
    }

    /**
     * Returns the index name
     *
     * @return string
     */
    public function getIndexName(): string
    {
        return $this->getSettings()->name;
    }

    /**
     * Returns the mappings for the index
     *
     * @return Array
     */
    public function getIndexMappings()
    {
        $indexMappings = [];

        foreach ($this->getSettings()->getIndexables() as $class) {

            $indexable = new $class;

            $typeName = $indexable->getDocumentType();

            foreach ($indexable->getDocumentMappings() as $attribute => $map) {

                if ($indexable->isIndexableRelation($attribute)) {
                    $map['properties'] = $indexable
                        ->getDocumentRelation($attribute)
                        ->getDocumentMappings();
                }

                $indexMappings[$typeName]['properties'][$attribute] = $map;
            }
        }

        return array_merge($this->getSettings()->mappings, $indexMappings);
    }

    /**
     * Create the index
     *
     * @return Void
     */
    public function create()
    {
        if ($this->exists()) {
            throw new \Exception('Index already exists.');
        }

        return $this->elasticsearch->indices()->create([
            'index' => $this->getIndexName(),
            'body'  => [
                'settings'  => $this->getSettings()->settings,
                'mappings'  => $this->getSettings()->mappings,
            ],
        ]);
    }

    /**
     * Delete the index
     *
     * @return Boolean
     */
    public function delete()
    {
        return $this->elasticsearch->indices()->delete([
            'index' => $this->getIndexName(),
        ]);
    }

    /**
     * Determine if the index exists
     *
     * @return Bool
     */
    public function exists()
    {
        return $this->elasticsearch->indices()->exists([
            'index' => $this->getIndexName(),
        ]);
    }
}