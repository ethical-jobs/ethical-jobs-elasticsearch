<?php

namespace EthicalJobs\Elasticsearch\Repositories;

use Traversable;
use Elasticsearch\Client;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use ONGR\ElasticsearchDSL\Query\TermLevel;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use EthicalJobs\Foundation\Storage\Repository;
use EthicalJobs\Elasticsearch\Hydrators;

/**
 * Abstract elasticsearch repository
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class ElasticsearchRepository implements Repository
{
    /**
     * Elasticsearch client
     * 
     * @var 
     */
    protected $client;

    /**
     * Name of the working Elasticsearch index
     * 
     * @var string
     */    
    protected $indexName;
    
    /**
     * Eloquent model 
     * 
     * @var Illuminate\Database\Eloquent\Model
     */    
    protected $model;
    
    /**
     * Elasticsearch query DSL
     * 
     * @var ONGR\ElasticsearchDSL\Search
     */    
    protected $search;

    /**
     * Object constructor
     *
     * @param Illuminate\Database\Eloquent\Model $model
     * @param ONGR\ElasticsearchDSL\Search $search
     * @param Elasticsearch\Client $client
     * @param string $indexName
     * @return void
     */
    public function __construct(Model $model, Search $search, Client $client, string $indexName)
    {
        $this->model = $model;

        $this->client = $client;

        $this->indexName = $indexName;

        $this->search = $search;

        $this->asModels();
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {    
        return $this->search;
    }

    /**
     * {@inheritdoc}
     */
    public function setQuery($search)
    {    
        $this->search = $search;

        return $this;
    }        

    /**
     * {@inheritdoc}
     */
    public function findById($id): Model
    {
        $query = new TermLevel\TermQuery('id', $id);

        $this->search->addQuery($query, BoolQuery::FILTER);        

        return $this->asModels()->find()->first();
    }  

    /**
     * {@inheritdoc}
     */
    public function findByField(string $field, $value): Model
    {
        $query = new TermLevel\TermQuery($field, $value);

        $this->search->addQuery($query, BoolQuery::FILTER);        

        return $this->asModels()->find()->first();
    }     

    /**
     * {@inheritdoc}
     */
    public function where(string $field, $operator, $value = null): Repository
    {
        switch ($operator) {
            case '<=':
            case '>=':
            case '<':
            case '>':
                $query = new TermLevel\RangeQuery($field, [$operator => $value]);
                $bool = BoolQuery::FILTER;
                break;
            case 'like':
                $query = new TermLevel\WildcardQuery($field, str_replace('%', '*', $value));
                $bool = BoolQuery::FILTER;
                break;    
            case '!=':
                $query = new TermLevel\TermQuery($field, $value);
                $bool = BoolQuery::MUST_NOT;
                break; 
            case '=':
            default:
                $query = new TermLevel\TermQuery($field, $value ? $value : $operator);
                $bool = BoolQuery::FILTER;
                break;                                             
        }

        $this->search->addQuery($query, $bool); 

        return $this;
    }  

    /**
     * {@inheritdoc}
     */
    public function whereIn(string $field, array $values): Repository
    {
        $query = new TermLevel\TermsQuery($field, $values);

        $this->search->addQuery($query, BoolQuery::FILTER);        

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function orderBy(string $field, $direction = 'asc'): Repository
    {
        $this->search->addSort(new FieldSort($field, $direction));

        $this->search->addSort(new FieldSort('_score', $direction));  

        return $this;
    }               

    /**
     * {@inheritdoc}
     */
    public function limit(int $limit): Repository
    {
        $this->search->setSize($limit);

        return $this;
    }   

    /**
     * {@inheritdoc}
     */  
    public function asModels(): Repository
    {
        $this->hydrator = Hydrators\EloquentHydrator::class;

        return $this;
    }    

    /**
     * {@inheritdoc}
     */ 
    public function asObjects(): Repository
    {
        $this->hydrator = Hydrators\ArrayObjectHydrator::class;

        return $this;
    }    
    
    /**
     * {@inheritdoc}
     */ 
    public function asArrays(): Repository
    {
        $this->hydrator = Hydrators\ArrayHydrator::class;

        return $this;
    }                      

    /**
     * {@inheritdoc}
     */
    public function find(): Traversable
    {
        $response = $this->client->search([
            'index' => $this->indexName,
            'type'  => $this->model->getDocumentType(),
            'body'  => $this->search->toArray(),
        ]);

        if ($response['hits']['total'] < 1) {
            throw new NotFoundHttpException;
        }

        return (new $this->hydrator)->hydrateFromResponse($response, $this->model);    
    }   
}