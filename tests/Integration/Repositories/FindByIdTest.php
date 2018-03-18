<?php

namespace Tests\Integration\Storage\Repositories\Elasticsearch;

use Mockery;
use Elasticsearch\Client;
use Tests\Fixtures\RepositoryFactory;
use Tests\Fixtures\Person;

class FindByIdTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Unit
     */
    public function it_can_find_by_id()
    {
        $people = factory(Person::class, 1)->create();

        $searchResults = $this->getSearchResults($people);

        $client = Mockery::mock(Client::class)
            ->shouldReceive('search')
            ->once()
            ->withArgs(function($query) use ($people) {
                $this->assertEquals($people->first()->id, array_get($query, 
                    'body.query.bool.filter.0.term.id'
                ));
                return true;
            })
            ->andReturn($searchResults)
            ->getMock();            

        $repository = RepositoryFactory::build(new Person, $client);

        $result = $repository->findById($people->first()->id);

        $this->assertEquals($result->id, $people->first()->id);
        $this->assertInstanceOf(Person::class, $result);
    }  

    /**
     * @test
     * @group Unit
     */
    public function it_throws_http_404_exception_when_no_model_found()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $client = Mockery::mock(Client::class)
            ->shouldReceive('search')
            ->once()
            ->withAnyArgs()
            ->andReturn($this->getEmptySearchResults())
            ->getMock();            

        $repository = RepositoryFactory::build(new Person, $client);

        $repository->findByField('first_name', 'Andrew');
    }         
}
