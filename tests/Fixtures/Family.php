<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use EthicalJobs\Elasticsearch\Indexable;
use EthicalJobs\Elasticsearch\Document;

class Family extends Model implements Indexable
{
    use Document;

    public function vehicles()
    {
        return $this->hasOne(Vehicle::class);
    }

    public function members()
    {
        return $this->hasOne(Person::class);
    }    

    /**
     * {@inheritdoc}
     */
    public function getDocumentMappings()
    {
        return [
            'surname'   => ['type' => 'text'],
            'vehicles'  => ['type' => 'object'],                   
            'members'   => ['type' => 'object'],            
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentRelations()
    {
        return ['vehicles','members'];
    }    
}