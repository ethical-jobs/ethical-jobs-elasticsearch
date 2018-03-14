<?php

namespace EthicalJobs\Tests\Elasticsearch\Integration\Console;

use Mockery;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;
use EthicalJobs\Elasticsearch\DocumentIndexer;
use EthicalJobs\Elasticsearch\Index;

class IndexDocumentsCommandTest extends \EthicalJobs\Tests\Elasticsearch\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_does_nothing_when_cache_locked()
    {
        Cache::shouldReceive('has')
            ->once()
            ->with('ej:es:indexing')
            ->andReturn(true);

        Cache::shouldNotReceive('put');

        Artisan::call('ej:es:index');
    }

    /**
     * @test
     * @group Integration
     */
    public function it_can_cache_lock_the_command()
    {
        $index = Mockery::mock(Index::class)
            ->shouldReceive('getSettings')
            ->once()
            ->withNoArgs()
            ->andReturn(Mockery::self())
            ->shouldReceive('getIndexables')
            ->once()
            ->withNoArgs()
            ->andReturn([])            
            ->getMock();

        App::instance(Index::class, $index);

        Cache::shouldReceive('has')
            ->once()
            ->with('ej:es:indexing')
            ->andReturn(false);

        Cache::shouldReceive('put')
            ->once()            
            ->withArgs(function ($key, $value, $minutes) {
                $this->assertEquals('ej:es:indexing', $key);
                $this->assertTrue(is_float($value));
                $this->assertEquals(20, $minutes);
                return true;
            });    

        Cache::shouldReceive('get')
            ->once()            
            ->with('ej:es:indexing');      

        Cache::shouldReceive('forget')
            ->once()            
            ->with('ej:es:indexing');                                        

        Artisan::call('ej:es:index');
    }   

    /**
     * @test
     * @group Integration
     */
    public function it_can_index_indexables()
    {
        $expectedIndexables = App::make(Index::class)
            ->getSettings()
            ->getIndexables();

        $indexer = Mockery::mock(DocumentIndexer::class);

        foreach ($expectedIndexables as $indexable) {
            $indexer
                ->shouldReceive('setLogging')
                ->once()
                ->with(true)
                ->andReturn(Mockery::self())   
                ->shouldReceive('indexCollection')
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

        App::instance(DocumentIndexer::class, $indexer);

        Artisan::call('ej:es:index');
    }   

    /**
     * @test
     * @group Integration
     */
    public function it_can_index_by_parameters()
    {
        $indexer = Mockery::mock(DocumentIndexer::class)
            ->shouldReceive('setLogging')
            ->once()
            ->with(true)
            ->andReturn(Mockery::self())   
            ->shouldReceive('indexCollection')
            ->once()
            ->withArgs(function ($query) {
                $this->assertInstanceOf(
                    \EthicalJobs\Tests\Elasticsearch\Fixtures\Family::class, 
                    $query->getModel()
                );
                return true;
            })
            ->getMock(); 

        App::instance(DocumentIndexer::class, $indexer);

        Artisan::call('ej:es:index', [
            '--indexables' => 'EthicalJobs\Tests\Elasticsearch\Fixtures\Family',
        ]);
    }       
}
