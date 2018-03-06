<?php

namespace EthicalJobs\Elasticsearch;

use Elasticsearch\Client;
use Illuminate\Database\Eloquent\Model;
use App\Models\Interfaces\Indexable;
use App\Models;

/**
 * Elasticsearch index settings
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class IndexSettings
{
    /**
     * Index name
     *
     * @param String
     */
    public $name = '';

    /**
     * Default index settings
     *
     * @param Array
     */
    public $settings = [];

    /**
     * Default index mappings
     *
     * @param Array
     */
    public $mappings = [];  

    /**
     * Indexable entities
     *
     * @param Array
     */
    protected $indexables = [];      

    /**
     * Constructor
     *
     * @param  string $name
     * @param  array $settings
     * @param  array $mappings
     * @return void
     */
    public function __construct(string $name, array $settings, array $mappings)
    {
        $this->name = $name;

        $this->settings = $settings;

        $this->mappings = $mappings;
    }

    /**
     * Sets the indexable entities
     *
     * @return Array
     */
    public function setIndexables(array $indexables)
    {
        $this->indexables = $indexables;

        return $this;
    }

    /**
     * Returns the indexable entities
     *
     * @return Array
     */
    public function getIndexables(): array
    {
        return $this->indexables;
    }    
}