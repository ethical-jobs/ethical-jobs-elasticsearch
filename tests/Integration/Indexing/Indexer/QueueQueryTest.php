<?php

namespace Tests\Integration\Indexing\Indexer;

use Mockery;
use Elasticsearch\Client;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Queue;
use EthicalJobs\Elasticsearch\Indexing\Indexer;
use EthicalJobs\Elasticsearch\Indexing\Logging\Logger;
use EthicalJobs\Elasticsearch\Indexing\IndexQuery;
use EthicalJobs\Elasticsearch\Indexing\ProcessIndexQuery;
use Tests\Fixtures\Person;

class QueueQueryTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_sends_correct_log_events()
    {
        Queue::fake();

        $people = factory(Person::class, 200)->create();

        $indexQuery = new IndexQuery(new Person, 25);

        $indexQuery->setNumberOfProcesses(4);

        $client = Mockery::mock(Client::class)->shouldIgnoreMissing();

        $logger = Mockery::mock(Logger::class)
            ->shouldReceive('start')
            ->once()
            ->with($indexQuery)
            ->andReturn(Mockery::self())                 
            ->getMock();           

        App::instance(Client::class, $client);

        $indexer = new Indexer($client, $logger, 'test-index');

        $indexer->queueQuery($indexQuery); 		
    } 	  

    /**
     * @test
     * @group Integration
     */
    public function it_dispatches_correct_amount_of_queue_jobs()
    {
        Queue::fake();

        $people = factory(Person::class, 200)->create();

        $indexQuery = new IndexQuery(new Person, 25);

        $indexQuery->setNumberOfProcesses(4);

        $client = Mockery::mock(Client::class)->shouldIgnoreMissing();

        $logger = Mockery::mock(Logger::class)->shouldIgnoreMissing();           

        App::instance(Client::class, $client);

        $indexer = new Indexer($client, $logger, 'test-index');

        $indexer->queueQuery($indexQuery);      

        Queue::assertPushed(ProcessIndexQuery::class, 1);
    }         

    /**
     * @test
     * @group Integration
     */
    public function it_indexes_all_documents_in_the_query()
    {
        $people = factory(Person::class, 200)->create();

        $indexQuery = new IndexQuery(new Person, 25);

        $indexQuery->setNumberOfProcesses(4);

        $indexedIds = collect();
        $indexedNames = collect();

        $client = Mockery::mock(Client::class)
            ->shouldReceive('bulk')
            ->times($indexQuery->chunkCount())
            ->withArgs(function($params) use(&$indexedIds, &$indexedNames) {
                $ids = array_filter(array_pluck($params['body'], 'id'));
                $indexedIds = $indexedIds->merge($ids);
                $names = array_filter(array_pluck($params['body'], 'first_name'));
                $indexedNames = $indexedNames->merge($names);
                return true;
            })
            ->andReturn($this->getSearchResults($people))
            ->getMock();            

        $logger = Mockery::mock(Logger::class)->shouldIgnoreMissing();           

        App::instance(Client::class, $client);
        App::instance(Logger::class, $logger);

        $indexer = new Indexer($client, $logger, 'test-index');

        $indexer->queueQuery($indexQuery);      

        $this->assertEquals($people->modelKeys(), $indexedIds->toArray());
        $this->assertEquals($people->pluck('first_name')->toArray(), $indexedNames->toArray());
    }                
}
