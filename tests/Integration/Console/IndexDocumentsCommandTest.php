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
    public function it_performs_normal_query_indexing_without_processes_param()
    {
        factory(Fixtures\Family::class, 50)->create();
        factory(Fixtures\Person::class, 50)->create();
        factory(Fixtures\Vehicle::class, 50)->create();

        $client = Mockery::mock(Client::class);

        $indexer = Mockery::mock(Indexer::class)
            ->shouldNotReceive('queueIndex')
            ->shouldReceive('indexQuery')
            ->times(3)
            ->withAnyArgs()
            ->andReturn(null)
            ->getMock();

        App::instance(Client::class, $client);

        App::instance(Indexer::class, $indexer);

        Artisan::call('ej:es:index', [
            '--chunk-size' => 25,   
        ]);
    }

    /**
     * @test
     * @group Integration
     */
    public function it_queues_index_queries_when_queue_param_present()
    {
        factory(Fixtures\Family::class, 50)->create();
        factory(Fixtures\Person::class, 50)->create();
        factory(Fixtures\Vehicle::class, 50)->create();

        $client = Mockery::mock(Client::class);

        $indexer = Mockery::mock(Indexer::class)
            ->shouldNotReceive('indexQuery')
            ->shouldReceive('queueQuery')
            ->times(3)
            ->withAnyArgs()
            ->andReturn(null)
            ->getMock();

        App::instance(Client::class, $client);

        App::instance(Indexer::class, $indexer);

        Artisan::call('ej:es:index', [
            '--chunk-size'  => 25,   
            '--queue'       => true,
        ]);
    }    

    /**
     * @test
     * @group Integration
     */
    public function it_can_specify_indexables_to_index()
    {
        factory(Fixtures\Family::class, 20)->create();

        $indexer = Mockery::mock(Indexer::class)
            ->shouldReceive('indexQuery')
            ->once()
            ->withArgs(function ($indexQuery) {
                $this->assertInstanceOf(\Tests\Fixtures\Family::class, $indexQuery->indexable);
                return true;
            })
            ->andReturn(null)
            ->getMock();         

        App::instance(Indexer::class, $indexer);

        Artisan::call('ej:es:index', [
            '--chunk-size'   => 133,            
            '--indexables'   => 'Tests\Fixtures\Family',
        ]);
    }                  
}
