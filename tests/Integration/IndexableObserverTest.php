<?php

namespace Tests\Integration;

use Mockery;
use Illuminate\Support\Facades\App;
use Tests\Fixtures;
use EthicalJobs\Elasticsearch\Indexing\Indexer;

class IndexableObserverTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_indexes_created_indexables()
    {
        $indexer = Mockery::mock(Indexer::class)
            ->shouldReceive('indexDocument')
            ->once()
            ->withArgs(function($person) {
                $this->assertEquals('Andrew', $person->first_name);
                $this->assertEquals('McLagan', $person->last_name);
                return true;
            })
            ->getMock();

        App::instance(Indexer::class, $indexer);

        factory(Fixtures\Person::class)->create([
            'first_name'    => 'Andrew',
            'last_name'     => 'McLagan',
        ]);
    } 

    /**
     * @test
     * @group Integration
     */
    public function it_indexes_updated_indexables()
    {
        $indexer = Mockery::mock(Indexer::class)
            ->shouldReceive('indexDocument')
            ->once()
            ->withAnyArgs()
            ->andReturn(null)
            ->shouldReceive('indexDocument')
            ->once()
            ->withArgs(function($person) {
                $this->assertEquals('Werdna', $person->first_name);
                $this->assertEquals('NagaLcM', $person->last_name);
                return true;
            })
            ->andReturn(null)            
            ->getMock();

        App::instance(Indexer::class, $indexer);

        factory(Fixtures\Person::class)
            ->create([
                'first_name'    => 'Andrew',
                'last_name'     => 'McLagan',
            ])
            ->update([
                'first_name'    => 'Werdna',
                'last_name'     => 'NagaLcM',
            ]);
    }     

    /**
     * @test
     * @group Integration
     */
    public function it_indexes_soft_deleted_indexables()
    {
        $indexer = Mockery::mock(Indexer::class)
            ->shouldReceive('indexDocument')
            ->once()
            ->withAnyArgs()
            ->andReturn(null)
            ->shouldReceive('indexDocument')
            ->once()
            ->withArgs(function($person) {
                $this->assertFalse(is_null($person->deleted_at));
                return true;
            })
            ->andReturn(null)            
            ->getMock();

        App::instance(Indexer::class, $indexer);

        $person = factory(Fixtures\Person::class)->create([
            'first_name'    => 'Andrew',
            'last_name'     => 'McLagan',
        ]);

        $person->delete();
    }   

    /**
     * @test
     * @group Integration
     */
    public function it_deletes_non_soft_deleted_indexables()
    {
        $indexer = Mockery::mock(Indexer::class)
            ->shouldReceive('indexDocument')
            ->once()
            ->withAnyArgs()
            ->andReturn(null)
            ->shouldReceive('deleteDocument')
            ->once()
            ->withArgs(function($family) {
                $this->assertEquals('McLagan', $family->surname);
                return true;
            })
            ->andReturn(null)            
            ->getMock();

        App::instance(Indexer::class, $indexer);

        $family = factory(Fixtures\Family::class)->create([
            'surname' => 'McLagan',
        ]);

        $family->delete();
    }       

    /**
     * @test
     * @group Integration
     */
    public function it_indexes_restored_indexables()
    {
        $indexer = Mockery::mock(Indexer::class)
            ->shouldReceive('indexDocument')
            ->times(3)
            ->withArgs(function($person) {
                $this->assertEquals('Andrew', $person->first_name);
                $this->assertEquals('McLagan', $person->last_name);
                return true;
            })
            ->andReturn(null)        
            ->getMock();

        App::instance(Indexer::class, $indexer);

        $person = factory(Fixtures\Person::class)->create([
            'first_name'    => 'Andrew',
            'last_name'     => 'McLagan',
        ]);

        $person->delete();

        $person->restore();
    }                     
}
