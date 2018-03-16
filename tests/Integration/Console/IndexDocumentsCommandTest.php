<?php

namespace Tests\Integration\Console;

use Mockery;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Eloquent\Builder;
use EthicalJobs\Elasticsearch\Indexing\Indexer;
use EthicalJobs\Elasticsearch\Index;

class IndexDocumentsCommandTest extends \EthicalJobs\Tests\Elasticsearch\TestCase
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
                ->shouldReceive('indexByQuery')
                ->once()
                ->withArgs(function ($query) use ($indexable) {
                    $this->assertInstanceOf(Builder::class, $query);
                    $this->assertInstanceOf($indexable, $query->getModel());
                    $this->assertEquals(
                        (new $indexable)->getDocumentRelations(),
                        array_keys($query->getEagerLoads())
                    );
                    return true;
                })
                ->andReturn(null);
        }              

        App::instance(Indexer::class, $indexer);

        Artisan::call('ej:es:index');
    }   

    /**
     * @test
     * @group Integration
     */
    public function it_can_index_by_parameters()
    {
        $indexer = Mockery::mock(Indexer::class)
            ->shouldReceive('indexByQuery')
            ->once()
            ->withArgs(function ($query, $chunkSize) {
                $this->assertInstanceOf(
                    \EthicalJobs\Tests\Elasticsearch\Fixtures\Family::class, 
                    $query->getModel()
                );
                $this->assertEquals(133, $chunkSize);
                return true;
            })
            ->getMock(); 

        App::instance(Indexer::class, $indexer);

        Artisan::call('ej:es:index', [
            '--indexables'  => 'EthicalJobs\Tests\Elasticsearch\Fixtures\Family',
            '--chunk-size'   => 133,
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
                ->shouldReceive('queueIndexByQuery')
                ->once()
                ->withArgs(function ($query) use ($indexable) {
                    $this->assertInstanceOf(Builder::class, $query);
                    $this->assertInstanceOf($indexable, $query->getModel());
                    $this->assertEquals(
                        (new $indexable)->getDocumentRelations(),
                        array_keys($query->getEagerLoads())
                    );
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
            ->shouldReceive('queueIndexByQuery')
            ->once()
            ->withArgs(function ($query, $processes, $chunkSize) {
                $this->assertInstanceOf(\EthicalJobs\Tests\Elasticsearch\Fixtures\Family::class, $query->getModel());
                $this->assertEquals(3, $processes);
                $this->assertEquals(133, $chunkSize);
                return true;
            })
            ->andReturn(null)
            ->getMock();         

        App::instance(Indexer::class, $indexer);

        Artisan::call('ej:es:index', [
            '--queue'        => true,
            '--chunk-size'   => 133,            
            '--processes'    => 3,
            '--indexables'   => 'EthicalJobs\Tests\Elasticsearch\Fixtures\Family',
        ]);
    }                  
}
