<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use EthicalJobs\Elasticsearch\Indexable;
use EthicalJobs\Elasticsearch\Document;

class Person extends Model implements Indexable
{
    use Document, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 
        'first_name',    
        'last_name',     
        'email',         
    ];    

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
            'first_name'    => ['type' => 'text'],
            'last_name'     => ['type' => 'text'],
            'email'         => ['type' => 'text'],
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