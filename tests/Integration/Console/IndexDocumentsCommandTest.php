<?php

namespace Tests\Integration\Console;

use Mockery;
use Elasticsearch\Client;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use EthicalJobs\Elasticsearch\Indexing\Indexer;
use EthicalJobs\Elasticsearch\Indexing\IndexQuery;
use EthicalJobs\Elasticsearch\Index;
use Tests\Fixtures;

class IndexDocumentsCommandTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_indexes_all_indexables_by_default()
    {
        $families = factory(Fixtures\Family::class, 50)->create();
        factory(Fixtures\Person::class, 50)->create();
        factory(Fixtures\Vehicle::class, 50)->create();

        $expectedIndexables = App::make(Index::class)
            ->getSettings()
            ->getIndexables();

        $elasticsearch = Mockery::mock(Client::class);

        foreach ($expectedIndexables as $indexable) {
            $elasticsearch
                ->shouldReceive('bulk')
                ->times(5)
                ->withAnyArgs()
                ->andReturn($this->getSearchResults($families));
        }              

        App::instance(Client::class, $elasticsearch);

        Artisan::call('ej:es:index', [
            '--chunk-size' => 12,   
        ]);
    }

    /**
     * @test
     * @group Integration
     */
    public function it_can_split_and_queue_indexing_into_processes()
    {
        $families = factory(Fixtures\Family::class, 100)->create();
        factory(Fixtures\Person::class, 100)->create();
        factory(Fixtures\Vehicle::class, 100)->create();

        $elasticsearch = Mockery::mock(Client::class)
            ->shouldReceive('bulk')
            ->times(30)
            ->withAnyArgs()
            ->andReturn($this->getSearchResults($families))
            ->getMock();         

        App::instance(Client::class, $elasticsearch);

        Artisan::call('ej:es:index', [
            '--chunk-size'   => 10,            
            '--processes'    => 4,
        ]);
    }     

    /**
     * @test
     * @group Integration
     */
    public function it_can_queue_by_parameters()
    {
        factory(Fixtures\Family::class, 20)->create();

        $indexer = Mockery::mock(Indexer::class)
            ->shouldReceive('indexQuery')
            ->once()
            ->withArgs(function ($indexQuery) {
                $this->assertEquals(133, $indexQuery->getParam('chunkSize'));
                $this->assertEquals(3, $indexQuery->getParam('numberOfProcesses'));
                $this->assertInstanceOf(\Tests\Fixtures\Family::class, $indexQuery->indexable);
                return true;
            })
            ->andReturn(null)
            ->getMock();         

        App::instance(Indexer::class, $indexer);

        Artisan::call('ej:es:index', [
            '--chunk-size'   => 133,            
            '--processes'    => 3,
            '--indexables'   => 'Tests\Fixtures\Family',
        ]);
    }                  
}
