<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use EthicalJobs\Elasticsearch\Indexable;
use EthicalJobs\Elasticsearch\Document;

class Vehicle extends Model implements Indexable
{
    use Document;

    public function family()
    {
        return $this->belongsTo(Family::class);
    }    

    /**
     * {@inheritdoc}
     */
    public function getDocumentMappings()
    {
        return [
            'family_id'     => ['type' => 'integer'],
            'year'          => ['type' => 'integer'],
            'model'         => ['type' => 'text'],
            'make'          => ['type' => 'text'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentRelations()
    {
        return ['family'];
    }    
}