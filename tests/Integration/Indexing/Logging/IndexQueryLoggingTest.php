<?php

namespace Tests\Integration\Indexing\Logging;

use Mockery;
use EthicalJobs\Elasticsearch\Indexing\IndexQuery;
use EthicalJobs\Elasticsearch\Indexing\Logging\Logger;
use EthicalJobs\Elasticsearch\Indexing\Logging\Channel;
use EthicalJobs\Elasticsearch\Indexing\Logging\Store;
use Tests\Fixtures\Person;

class IndexQueryLoggingTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_sets_initial_store_values_when_starting()
    {
        factory(Person::class, 40)->create();

        $indexQuery = new IndexQuery(new Person, 10);

        $indexQuery->setNumberOfProcesses(2);

        $channels = [
            Mockery::mock(Channel::class)->shouldIgnoreMissing()
        ];         

        $logger = new Logger($channels);

        $logger->start($indexQuery);

        $expected = Store::all($indexQuery->uuid);

        $this->assertTrue(is_numeric($expected['duration']));

        $this->assertEquals(array_except($expected, 'duration'), [
            'documents:indexed'     => 0,
            'documents:total'       => 40,
            'processes:completed'   => 0,
            'processes:total'       => 2,            
        ]);
    }  

    /**
     * @test
     * @group Integration
     */
    public function it_logs_an_event_when_starting()
    {
        factory(Person::class, 40)->create();

        $indexQuery = new IndexQuery(new Person, 10);

        $indexQuery->setNumberOfProcesses(2);

        $channels = [
            Mockery::mock(Channel::class)
                ->shouldReceive('log')
                ->once()
                ->with('Indexing started', [
                    'environment'   => 'testing',
                    'uuid'          => $indexQuery->uuid,
                    'indexable'     => get_class($indexQuery->indexable),
                    'duration'      => '0m 1s',
                    'indexing'      => '0% (0/40) indexed',
                    'processes'     => '0/2 completed',
                    'processIds'    => [],
                ])
                ->andReturn(null)
                ->getMock()
        ];         

        $logger = new Logger($channels);

        $logger->start($indexQuery);
    }   

    /**
     * @test
     * @group Integration
     */
    public function it_can_add_processes_by_join_command()
    {
        factory(Person::class, 100)->create();

        $indexQuery = new IndexQuery(new Person, 5);

        $indexQuery->setNumberOfProcesses(4);

        $channels = [
            Mockery::mock(Channel::class)->shouldIgnoreMissing()
        ];         

        $logger = new Logger($channels);

        $logger->start($indexQuery);

        $indexQuery->split()->each(function($query) use($logger) {
            $logger->join($query);
        });

        $store = Store::all($indexQuery->uuid);

        $this->assertEquals(4, count(explode(',',$store['processes:ids'])));
    }      

    /**
     * @test
     * @group Integration
     */
    public function it_can_progress_query_logging_documents()
    {
        factory(Person::class, 100)->create();

        $indexQuery = new IndexQuery(new Person, 5);

        $indexQuery->setNumberOfProcesses(4);

        $channel = Mockery::mock(Channel::class)
            ->shouldReceive('log')
            ->once()
            ->withAnyArgs()
            ->andReturn(null);

        $channel
            ->shouldReceive('log')
            ->once()
            ->withArgs(function ($message, $params) {
                $this->assertEquals('15% (15/100) indexed', $params['indexing']);
                return true;                    
            })
            ->andReturn(null)
            ->shouldReceive('log')
            ->once()
            ->withArgs(function ($message, $params) {
                $this->assertEquals('37% (37/100) indexed', $params['indexing']);
                return true;                    
            })
            ->andReturn(null)
            ->shouldReceive('log')
            ->once()
            ->withArgs(function ($message, $params) {
                $this->assertEquals('70% (70/100) indexed', $params['indexing']);
                return true;                    
            })
            ->andReturn(null);                                        

        $logger = new Logger([$channel->getMock()]);

        $logger->start($indexQuery);

        $logger
            ->progress($indexQuery, 15)
            ->progress($indexQuery, 22)
            ->progress($indexQuery, 33);
    }       

    /**
     * @test
     * @group Integration
     */
    public function it_can_complete_a_query_logging_process()
    {
        factory(Person::class, 100)->create();

        $indexQuery = new IndexQuery(new Person, 5);

        $indexQuery->setNumberOfProcesses(4);

        $channel = Mockery::mock(Channel::class)->shouldIgnoreMissing();                                       

        $logger = new Logger([$channel]);

        $logger
            ->start($indexQuery)
            ->progress($indexQuery, 15)
            ->complete($indexQuery);

        $expected = Store::all($indexQuery->uuid);

        $this->assertEquals(1, $expected['processes:completed']);
    }   

    /**
     * @test
     * @group Integration
     */
    public function it_completes_a_logging_session_when_all_processes_have_completed()
    {
        factory(Person::class, 100)->create();

        $indexQuery = new IndexQuery(new Person, 5);

        $indexQuery->setNumberOfProcesses(4);

        $channel = Mockery::mock(Channel::class)
            ->shouldReceive('log')->times(2)->withAnyArgs()->andReturn(null)
            ->shouldReceive('log')
            ->once()
            ->withArgs(function($message, $params) {
                $this->assertEquals('Indexing completed', $message);
                $this->assertEquals('4/4 completed', $params['processes']);
                return true;
            })
            ->andReturn(null)
            ->getMock();

        $logger = new Logger([$channel]);

        $logger
            ->start($indexQuery)
            ->progress($indexQuery, 15)
            ->complete($indexQuery)
            ->complete($indexQuery)
            ->complete($indexQuery)
            ->complete($indexQuery);
    }             
}
