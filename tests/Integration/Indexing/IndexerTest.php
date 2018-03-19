<?php

namespace Tests\Integration\Indexing;

use Mockery;
use Elasticsearch\Client;
use Illuminate\Support\Facades\Queue;
use EthicalJobs\Elasticsearch\Indexing\ProcessIndexQuery;
use EthicalJobs\Elasticsearch\Indexing\IndexQuery;
use EthicalJobs\Elasticsearch\Indexing\Indexer;
use EthicalJobs\Elasticsearch\Indexing\Logger;
use EthicalJobs\Elasticsearch\Index;
use Tests\Fixtures\Person;

class IndexerTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_can_index_a_single_entity()
    {
        $person = factory(Person::class)->create();

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
    public function it_can_index_by_indexQuery()
    {
        factory(Person::class, 1000)->create();

        $indexQuery = (new IndexQuery(new Person))
            ->makeChunks(50);

        $client = Mockery::mock(Client::class)
            ->shouldReceive('bulk')
            ->times(20)
            ->withArgs(function($params) {
                $this->assertEquals(100, count($params['body'])); // Two times the chunksize
                $this->assertEquals('testing', array_get($params, 'body.0.index._index'));
                $this->assertEquals('people', array_get($params, 'body.0.index._type'));
                $this->assertTrue(array_has(array_get($params, 'body.1'), [
                    'id', 'family_id', 'first_name', 'last_name', 
                    'email', 'created_at', 'updated_at', 'deleted_at',
                ]));
                return true;
            })       
            ->andReturn([])
            ->getMock();
            
        $index = app()->make(Index::class);

        $logger = Mockery::mock(Logger::class)->shouldIgnoreMissing();

        $indexer = new Indexer($client, $index, $logger);            

        $indexer->indexQuery($indexQuery);
    }    
}
