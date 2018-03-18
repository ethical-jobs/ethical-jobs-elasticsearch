<?php

namespace Tests\Integration\Storage\Repositories\Elasticsearch;

use Mockery;
use Elasticsearch\Client;
use Tests\Fixtures\RepositoryFactory;
use Tests\Fixtures\Person;

class WhereInTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Unit
     */
    public function it_can_find_by_a_whereIn_terms_query()
    {
        $people = factory(Person::class, 10)->create();

        $client = Mockery::mock(Client::class)
            ->shouldReceive('search')
            ->once()
            ->withArgs(function($query) {
                // dd($query);
                $this->assertEquals([34,65,14,21], array_get($query, 
                    'body.query.bool.filter.0.terms.age'
                ));
                return true;
            })
            ->andReturn($this->getSearchResults($people))
            ->getMock();       

        $repository = RepositoryFactory::build(new Person, $client);     

        $result = $repository
            ->whereIn('age', [34,65,14,21])
            ->find();

        $this->assertEquals(10, $result->count());        
    }                  
}
