<?php

namespace Tests\Integration\Indexing\Logging;

use Mockery;
use Illuminate\Support\Facades\Cache;
use EthicalJobs\Elasticsearch\Indexing\Logging\Logger;
use EthicalJobs\Elasticsearch\Indexing\Logging\Channel;

class LoggerTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_writes_to_all_its_channels()
    {
        $channels = collect();

        for ($i = 0; $i < 10; $i++) {
            $channels->push(
                Mockery::mock(Channel::class)
                    ->shouldReceive('log')
                    ->once()
                    ->withAnyArgs()
                    ->andReturn(null)
                    ->getMock()
            );     
        }       

        $logger = new Logger($channels->toArray());

        $logger->log('Hello world!', ['foo' => 'bar']);
    }  

    /**
     * @test
     * @group Integration
     */
    public function it_writes_message_and_data_to_all_its_channels()
    {
        $channels = collect();

        for ($i = 0; $i < 10; $i++) {
            $channels->push(
                Mockery::mock(Channel::class)
                    ->shouldReceive('log')
                    ->once()
                    ->with('Hello world!', ['foo' => 'bar'])
                    ->andReturn(null)
                    ->getMock()
            );     
        }       

        $logger = new Logger($channels->toArray());

        $logger->log('Hello world!', ['foo' => 'bar']);
    }                                   
}
