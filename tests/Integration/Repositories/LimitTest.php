<?php

namespace Tests\Integration\Storage\Repositories\Elasticsearch;

use Mockery;
use Elasticsearch\Client;
use Tests\Fixtures\RepositoryFactory;
use Tests\Fixtures\Person;

class LimitTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Unit
     */
    public function it_can_add_a_limit()
    {
        $people = factory(Person::class, 10)->create();

        $client = Mockery::mock(Client::class)
            ->shouldReceive('search')
            ->once()
            ->withArgs(function($query) {
                $this->assertEquals(17, array_get($query, 
                    'body.size'
                ));     
                return true;
            })
            ->andReturn($this->getSearchResults($people))
            ->getMock();       

        $repository = RepositoryFactory::build(new Person, $client);     

        $result = $repository
            ->limit(17)
            ->find();

        $this->assertEquals(10, $result->count());        
    }                  
}
