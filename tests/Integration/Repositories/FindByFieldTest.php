<?php

namespace Tests\Integration\Storage\Repositories\Elasticsearch;

use Mockery;
use Elasticsearch\Client;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Query\TermLevel;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use Tests\Fixtures\ElasticsearchRepository;
use Tests\Fixtures\Person;

class FindByFieldTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Unit
     */
    public function it_can_find_by_a_field()
    {
        $people = factory(Person::class, 20)->create();

        $searchResults = $this->getMockSearchResults($people);

        $client = Mockery::mock(Client::class)
            ->shouldReceive('search')
            ->once()
            ->with([
                'index' => 'testing-index',
                'type'  => 'people',
                'body'  => [],                
            ])
            ->andReturn($searchResults)
            ->getMock();            

        $result = (new ElasticsearchRepository($client))
            ->findByField('first_name', 'Andrew');
    }    

    // /**
    //  * @test
    //  * @group Unit
    //  */
    // public function it_throws_http_404_exception_when_no_model_found()
    // {
    //     $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

    //     $expected = new MockModel;

    //     $query = Mockery::mock(Builder::class)
    //          ->shouldReceive('where')
    //          ->once()
    //          ->with('first_name', 'Andrew')
    //          ->andReturn(Mockery::self())
    //          ->shouldReceive('get')
    //          ->once()
    //          ->withNoArgs()
    //          ->andReturn(null)
    //          ->getMock();

    //     (new DatabaseRepository)
    //         ->setQuery($query)
    //         ->findByField('first_name', 'Andrew');
    // }         
}
