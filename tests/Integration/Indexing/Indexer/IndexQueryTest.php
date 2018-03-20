<?php

namespace Tests\Integration\Indexing\Indexer;

use Mockery;
use Elasticsearch\Client;
use EthicalJobs\Elasticsearch\Indexing\Indexer;
use EthicalJobs\Elasticsearch\Indexing\Logging\Logger;
use EthicalJobs\Elasticsearch\Indexing\IndexQuery;
use Tests\Fixtures\Person;

class IndexQueryTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_sends_correct_log_events()
    {
        $people = factory(Person::class, 100)->create();

        $indexQuery = new IndexQuery(new Person, 25);

        $client = Mockery::mock(Client::class)
            ->shouldReceive('bulk')
            ->zeroOrMoreTimes()
            ->withAnyArgs()
            ->andReturn($this->getSearchResults($people))
            ->getMock();

        $logger = Mockery::mock(Logger::class)
            ->shouldReceive('join')
            ->once()
            ->with($indexQuery)
            ->andReturn(Mockery::self())
            ->shouldReceive('progress')
            ->times(4)
            ->with($indexQuery, 25)
            ->andReturn(Mockery::self())
            ->shouldReceive('complete')
            ->once()
            ->with($indexQuery)
            ->andReturn(Mockery::self())                   
            ->getMock();            

        $indexer = new Indexer($client, $logger, 'test-index');

        $indexer->indexQuery($indexQuery); 		
    } 	 

    /**
     * @test
     * @group Integration
     */
    public function it_logs_and_throws_an_exception_on_invalid_response()
    {
        $this->expectException(\EthicalJobs\Elasticsearch\Exceptions\IndexingException::class);

        $people = factory(Person::class, 100)->create();

        $indexQuery = new IndexQuery(new Person, 25);

        $client = Mockery::mock(Client::class)
            ->shouldReceive('bulk')
            ->zeroOrMoreTimes()
            ->withAnyArgs()
            ->andReturn([
                'errors' => true,
                'items'  => ['foo' => 'bar', 'bar' => 'foo'],
            ])
            ->getMock();

        $logger = Mockery::mock(Logger::class)
            ->shouldReceive('join')
            ->once()
            ->withAnyArgs()
            ->andReturn(Mockery::self())
            ->shouldReceive('log')
            ->once()
            ->with('Indexing error', ['foo' => 'bar', 'bar' => 'foo'])
            ->andReturn(Mockery::self())                  
            ->getMock();            

        $indexer = new Indexer($client, $logger, 'test-index');

        $indexer->indexQuery($indexQuery);      
    }     

    /**
     * @test
     * @group Integration
     */
    public function it_indexes_all_documents_in_the_query()
    {
        $people = factory(Person::class, 100)->create();

        $indexQuery = new IndexQuery(new Person, 25);

        $indexedIds = collect();

        $client = Mockery::mock(Client::class)
            ->shouldReceive('bulk')
            ->times(4)
            ->withArgs(function($params) use(&$indexedIds) {
                $ids = array_filter(array_pluck($params['body'], 'id'));
                $indexedIds = $indexedIds->merge($ids);
                return true;
            })
            ->andReturn($this->getSearchResults($people))
            ->getMock();

        $logger = Mockery::mock(Logger::class)->shouldIgnoreMissing();            

        $indexer = new Indexer($client, $logger, 'test-index');

        $indexer->indexQuery($indexQuery);   

        $this->assertEquals($people->modelKeys(), $indexedIds->toArray());
        $this->assertEquals($people->count(), $indexedIds->count());
    }    

    /**
     * @test
     * @group Integration
     */
    public function it_indexes_with_correct_index_params()
    {
        $people = factory(Person::class, 100)->create();

        $indexQuery = new IndexQuery(new Person, 25);

        $client = Mockery::mock(Client::class)
            ->shouldReceive('bulk')
            ->times(4)
            ->withArgs(function($params) use($people) {
                foreach ($params['body'] as $param) {
                    if ($settings = array_get($param, 'index')) {
                        $this->assertEquals('test-index', $settings['_index']);
                        $this->assertEquals('people', $settings['_type']);
                        $this->assertTrue($people->contains($settings['_id']));
                    }
                }
                return true;
            })
            ->andReturn($this->getSearchResults($people))
            ->getMock();

        $logger = Mockery::mock(Logger::class)->shouldIgnoreMissing();            

        $indexer = new Indexer($client, $logger, 'test-index');

        $indexer->indexQuery($indexQuery);   
    }            
}
