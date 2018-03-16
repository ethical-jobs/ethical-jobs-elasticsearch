<?php

namespace Tests\Integration\Indexing;

use Mockery;
use Elasticsearch\Client;
use Illuminate\Support\Facades\Queue;
use EthicalJobs\Elasticsearch\Indexing\ProcessIndexQuery;
use EthicalJobs\Elasticsearch\Indexing\Logger;
use EthicalJobs\Elasticsearch\Indexing\Indexer;
use EthicalJobs\Tests\Elasticsearch\Fixtures;
use EthicalJobs\Elasticsearch\Index;

class IndexerTest extends \EthicalJobs\Tests\Elasticsearch\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_can_index_a_single_entity()
    {
        $person = factory(Fixtures\Person::class)->create();

        $client = Mockery::mock(Client::class)
            ->shouldReceive('index')
            ->once()
            ->with([
                'index' => 'testing',
                'id'    => 1,
                'type'  => 'people',
                'body'  => $person->getDocumentTree(),
            ])
            ->andReturn('success')
            ->getMock();

        $index = app()->make(Index::class);

        $logger = Mockery::mock(Logger::class)->shouldIgnoreMissing();

        $indexer = new Indexer($client, $index, $logger);            

        $result = $indexer->indexDocument($person);

        $this->assertEquals('success', $result);
    }

    /**
     * @test
     * @group Integration
     */
    public function it_can_index_by_query()
    {
        $families = factory(Fixtures\Family::class, 6)->create();

        $query = Fixtures\Family::query();

        $client = Mockery::mock(Client::class)
            ->shouldReceive('bulk')
            ->once()
            ->withArgs(function($params) use ($families) {
                $this->assertEquals('testing', array_get($params, 'body.0.index._index'));
                $this->assertEquals('families', array_get($params, 'body.0.index._type'));
                $this->assertEquals($families[0]->surname, array_get($params, 'body.1.surname'));
                // -- Next family
                $this->assertEquals('testing', array_get($params, 'body.0.index._index'));
                $this->assertEquals('families', array_get($params, 'body.0.index._type'));                
                $this->assertEquals($families[1]->surname, array_get($params, 'body.3.surname'));
                return true;
            })
            ->andReturn(['success'])
            ->shouldReceive('bulk')
            ->once()
            ->withArgs(function($params) use ($families) {
                $this->assertEquals('testing', array_get($params, 'body.0.index._index'));
                $this->assertEquals('families', array_get($params, 'body.0.index._type'));
                $this->assertEquals($families[2]->surname, array_get($params, 'body.1.surname'));
                // -- Next family
                $this->assertEquals('testing', array_get($params, 'body.0.index._index'));
                $this->assertEquals('families', array_get($params, 'body.0.index._type'));                
                $this->assertEquals($families[3]->surname, array_get($params, 'body.3.surname'));
                return true;
            })
            ->andReturn(['success'])
            ->shouldReceive('bulk')
            ->once()
            ->withArgs(function($params) use ($families) {
                $this->assertEquals('testing', array_get($params, 'body.0.index._index'));
                $this->assertEquals('families', array_get($params, 'body.0.index._type'));
                $this->assertEquals($families[4]->surname, array_get($params, 'body.1.surname'));
                // -- Next family
                $this->assertEquals('testing', array_get($params, 'body.0.index._index'));
                $this->assertEquals('families', array_get($params, 'body.0.index._type'));                
                $this->assertEquals($families[5]->surname, array_get($params, 'body.3.surname'));
                return true;
            })
            ->andReturn(['success'])                        
            ->getMock();

        $index = app()->make(Index::class);

        $logger = Mockery::mock(Logger::class)->shouldIgnoreMissing();

        $indexer = new Indexer($client, $index, $logger);            

        $indexer->indexByQuery($query, 2);
    }    

    /**
     * @test
     * @group Integration
     */
    public function it_can_multi_process_indexing()
    {
        Queue::fake();

        factory(Fixtures\Family::class, 1000)->create();

        $query = Fixtures\Family::query();

        $indexer = Mockery::mock(Indexer::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('log')
            ->once()
            ->withAnyArgs()
            ->andReturn(null)
            ->getMock();

        $indexer->queueIndexByQuery($query, 5, 155);

        Queue::assertPushed(ProcessIndexQuery::class, 5);        

        Queue::assertPushed(ProcessIndexQuery::class, function ($event) {
            $this->assertEquals(200, $event->query->get()->count());
            $this->assertEquals(155, $event->chunkSize);
            return true;
        });        
    }        
}
