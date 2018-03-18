<?php

namespace Tests\Integration\Storage\Repositories\Elasticsearch;

use Mockery;
use ArrayObject;
use Elasticsearch\Client;
use Illuminate\Database\Eloquent\Model;
use Tests\Fixtures\RepositoryFactory;
use Tests\Fixtures\Person;

class HydratorTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Unit
     */
    public function it_can_hydrate_results_as_models()
    {
        $people = factory(Person::class, 10)->create();

        $client = Mockery::mock(Client::class)
            ->shouldReceive('search')
            ->once()
            ->withAnyArgs()
            ->andReturn($this->getSearchResults($people))
            ->getMock();       

        $repository = RepositoryFactory::build(new Person, $client);     

        $results = $repository
            ->asModels()
            ->find();

        $results->each(function($result) {
            $this->assertInstanceOf(Model::class, $result);
        });
    }      

    /**
     * @test
     * @group Unit
     */
    public function it_can_hydrate_results_as_models_by_default()
    {
        $people = factory(Person::class, 10)->create();

        $client = Mockery::mock(Client::class)
            ->shouldReceive('search')
            ->once()
            ->withAnyArgs()
            ->andReturn($this->getSearchResults($people))
            ->getMock();       

        $repository = RepositoryFactory::build(new Person, $client);     

        $results = $repository->find();

        $results->each(function($result) {
            $this->assertInstanceOf(Model::class, $result);
        });
    }                    

    /**
     * @test
     * @group Unit
     */
    public function it_can_hydrate_results_as_objects()
    {
        $people = factory(Person::class, 10)->create();

        $client = Mockery::mock(Client::class)
            ->shouldReceive('search')
            ->once()
            ->withAnyArgs()
            ->andReturn($this->getSearchResults($people))
            ->getMock();       

        $repository = RepositoryFactory::build(new Person, $client);     

        $results = $repository
            ->asObjects()
            ->find();

        $results->each(function($result) {
            $this->assertInstanceOf(ArrayObject::class, $result);
        });
    }      

    /**
     * @test
     * @group Unit
     */
    public function it_can_hydrate_results_as_arrays()
    {
        $this->markTestSkipped('must be implemented.');
    }               
}
