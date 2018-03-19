<?php

namespace Tests\Integration\Indexing;

use Mockery;
use Maknz\Slack\Client;
use Illuminate\Support\Facades\App;
use Symfony\Component\Console\Output\ConsoleOutput;
use EthicalJobs\Elasticsearch\Indexing\IndexQuery;
use EthicalJobs\Elasticsearch\Indexing\Logger;
use Tests\Fixtures\Person;

class LoggerTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_starts_logging_indexing_query()
    {
        factory(Person::class, 1000)->create();

        $indexQuery = new IndexQuery(new Person);

        $indexQuery->makeChunks(100);

        $slack = Mockery::mock(Client::class)
            ->shouldReceive('attach')
            ->once()
            ->withArgs(function ($params) {
                $this->assertTrue(array_has($params, [
                    'fallback','text','color','fields.0.title'
                ]));
                $decoded = json_decode(str_replace('`', '', $params['fields'][0]['value']));
                $this->assertEquals(Person::class, $decoded->indexable);
                $this->assertEquals('0/10', $decoded->progress);
                $this->assertEquals(100, $decoded->process->chunkSize);
                $this->assertEquals(null, $decoded->process->proccessNum);
                $this->assertTrue(is_numeric($decoded->process->processId));
                $this->assertTrue(is_string($decoded->process->hostName));
                $this->assertEquals('testing', $decoded->process->environment);
                return true;
            })
            ->andReturn(Mockery::self())
            ->shouldReceive('send')
            ->with('Indexing started')
            ->andReturn(null)
            ->getMock();

        $console = Mockery::mock(ConsoleOutput::class)
            ->shouldIgnoreMissing();
        
        $logger = new Logger($slack, $console);

        $logger->start($indexQuery);       
    }    

    /**
     * @test
     * @group Integration
     */
    public function it_can_progress_logging()
    {
        factory(Person::class, 1000)->create();

        $indexQuery = new IndexQuery(new Person);

        $indexQuery->makeChunks(100);

        $slack = Mockery::mock(Client::class)
            ->shouldReceive('attach')
            ->once()
            ->withAnyArgs()
            ->andReturn(Mockery::self())
            ->shouldReceive('send')
            ->with('Indexing started') 
            ->andReturn(null)      

            ->shouldReceive('attach')
            ->once()
            ->withArgs(function ($params) {
                $decoded = json_decode(str_replace('`', '', $params['fields'][0]['value']));
                $this->assertEquals('15/10', $decoded->progress);
                return true;
            })  
            ->andReturn(Mockery::self())
            ->shouldReceive('send')
            ->with('Indexing progressed') 
            ->andReturn(null)                              
            ->getMock();

        $console = Mockery::mock(ConsoleOutput::class)
            ->shouldIgnoreMissing();
        
        $logger = new Logger($slack, $console);

        $logger->start($indexQuery);       

        $logger->progress(15); 
    }        

    /**
     * @test
     * @group Integration
     */
    public function it_can_finish_logging()
    {
        factory(Person::class, 1000)->create();

        $indexQuery = new IndexQuery(new Person);

        $indexQuery->makeChunks(100);

        $slack = Mockery::mock(Client::class)
            ->shouldReceive('attach')
            ->once()
            ->withAnyArgs()
            ->andReturn(Mockery::self())
            ->shouldReceive('send')
            ->with('Indexing started') 
            ->andReturn(null)      
            ->shouldReceive('attach')
            ->once()
            ->withAnyArgs()  
            ->andReturn(Mockery::self())
            ->shouldReceive('send')
            ->with('Indexing progressed') 
            ->andReturn(null)  
            ->shouldReceive('attach')
            ->once()
            ->withAnyArgs()  
            ->andReturn(Mockery::self())
            ->shouldReceive('send')
            ->with('Indexing completed') 
            ->andReturn(null)                                           
            ->getMock();

        $console = Mockery::mock(ConsoleOutput::class)
            ->shouldIgnoreMissing();
        
        $logger = new Logger($slack, $console);

        $logger->start($indexQuery);       

        $logger->progress(15); 

        $logger->finish(); 
    }             
}
