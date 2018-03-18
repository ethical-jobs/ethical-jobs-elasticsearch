<?php

namespace Tests\Integration\Storage\Repositories\Elasticsearch;

use Mockery;
use Elasticsearch\Client;
use Tests\Fixtures\RepositoryFactory;
use Tests\Fixtures\Person;

class WhereTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Unit
     */
    public function it_can_find_by_range_operators()
    {
        $people = factory(Person::class, 10)->create();

        foreach (['<=','>=','<','>'] as $operator) {

            $client = Mockery::mock(Client::class)
                ->shouldReceive('search')
                ->once()
                ->withArgs(function($query) use ($operator) {
                    $this->assertEquals([$operator => 65], array_get($query, 
                        'body.query.bool.filter.0.range.age'
                    ));
                    return true;
                })
                ->andReturn($this->getSearchResults($people))
                ->getMock();       

            $repository = RepositoryFactory::build(new Person, $client);     
    
            $result = $repository
                ->where('age', $operator, 65)
                ->find();

            $this->assertEquals(10, $result->count());        
        }
    }  

    /**
     * @test
     * @group Unit
     */
    public function it_can_find_by_wildcard_operator()
    {
        $people = factory(Person::class, 10)->create();

        $client = Mockery::mock(Client::class)
            ->shouldReceive('search')
            ->once()
            ->withArgs(function($query) {
                $this->assertEquals('Andre* McL*an', array_get($query, 
                    'body.query.bool.filter.0.wildcard.name.value'
                ));
                return true;
            })
            ->andReturn($this->getSearchResults($people))
            ->getMock();       

        $repository = RepositoryFactory::build(new Person, $client);     

        $result = $repository
            ->where('name', 'like', 'Andre% McL%an')
            ->find();

        $this->assertEquals(10, $result->count());        
    }    

    /**
     * @test
     * @group Unit
     */
    public function it_can_find_by_not_equals_operator()
    {
        $people = factory(Person::class, 10)->create();

        $client = Mockery::mock(Client::class)
            ->shouldReceive('search')
            ->once()
            ->withArgs(function($query) {
                $this->assertEquals(34, array_get($query, 
                    'body.query.bool.must_not.0.term.age'
                ));
                return true;
            })
            ->andReturn($this->getSearchResults($people))
            ->getMock();       

        $repository = RepositoryFactory::build(new Person, $client);     

        $result = $repository
            ->where('age', '!=', 34)
            ->find();

        $this->assertEquals(10, $result->count());        
    }       

    /**
     * @test
     * @group Unit
     */
    public function it_can_find_by_equals_operator()
    {
        $people = factory(Person::class, 10)->create();

        $client = Mockery::mock(Client::class)
            ->shouldReceive('search')
            ->once()
            ->withArgs(function($query) {
                $this->assertEquals(34, array_get($query, 
                    'body.query.bool.filter.0.term.age'
                ));
                return true;
            })
            ->andReturn($this->getSearchResults($people))
            ->getMock();       

        $repository = RepositoryFactory::build(new Person, $client);     

        $result = $repository
            ->where('age', '=', 34)
            ->find();

        $this->assertEquals(10, $result->count());        
    }  

    /**
     * @test
     * @group Unit
     */
    public function it_can_find_by_equals_operator_by_default()
    {
        $people = factory(Person::class, 10)->create();

        $client = Mockery::mock(Client::class)
            ->shouldReceive('search')
            ->once()
            ->withArgs(function($query) {
                $this->assertEquals(37, array_get($query, 
                    'body.query.bool.filter.0.term.age'
                ));
                return true;
            })
            ->andReturn($this->getSearchResults($people))
            ->getMock();       

        $repository = RepositoryFactory::build(new Person, $client);     

        $result = $repository
            ->where('age', 37)
            ->find();

        $this->assertEquals(10, $result->count());        
    }                 
}
