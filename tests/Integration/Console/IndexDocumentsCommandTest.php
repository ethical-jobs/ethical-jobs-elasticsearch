<?php

namespace Tests\Integration\Console;

use Mockery;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use EthicalJobs\Elasticsearch\Indexing\IndexQuery;
use EthicalJobs\Elasticsearch\Indexing\Indexer;
use EthicalJobs\Elasticsearch\Index;

class IndexDocumentsCommandTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_indexes_all_indexables_by_default()
    {
        $expectedIndexables = App::make(Index::class)
            ->getSettings()
            ->getIndexables();

        $indexer = Mockery::mock(Indexer::class);

        foreach ($expectedIndexables as $indexable) {
            $indexer
                ->shouldReceive('indexQuery')
                ->once()
                ->withArgs(function ($indexQuery) use ($indexable) {
                    $this->assertInstanceOf(IndexQuery::class, $indexQuery);
                    $this->assertInstanceOf($indexable, $indexQuery->indexable);
                    return true;
                })
                ->andReturn(null);
        }              

        App::instance(Indexer::class, $indexer);

        Artisan::call('ej:es:index', [
            '--quiet' => true,
        ]);
    }   

    /**
     * @test
     * @group Integration
     */
    public function it_queues_all_indexables_by_default()
    {
        $expectedIndexables = App::make(Index::class)
            ->getSettings()
            ->getIndexables();

        $indexer = Mockery::mock(Indexer::class);

        foreach ($expectedIndexables as $indexable) {
            $indexer
                ->shouldReceive('queueQuery')
                ->once()
                ->withArgs(function ($indexQuery) use ($indexable) {
                    $this->assertInstanceOf(IndexQuery::class, $indexQuery);
                    $this->assertInstanceOf($indexable, $indexQuery->indexable);
                    return true;
                })
                ->andReturn(null);
        }              

        App::instance(Indexer::class, $indexer);

        Artisan::call('ej:es:index', [
            '--queue' => true,
        ]);
    }   

    /**
     * @test
     * @group Integration
     */
    public function it_can_queue_by_parameters()
    {
        $indexer = Mockery::mock(Indexer::class)
            ->shouldReceive('queueQuery')
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
            '--queue'        => true,
            '--chunk-size'   => 133,            
            '--processes'    => 3,
            '--indexables'   => 'Tests\Fixtures\Family',
        ]);
    }                  
}
