<?php

namespace Tests\Integration\Indexing;

use Mockery;
use Maknz\Slack\Client;
use EthicalJobs\Elasticsearch\Indexing\SlackLogger;

class SlackLoggerTest extends \EthicalJobs\Tests\Elasticsearch\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_can_log_a_message()
    {
        $client = Mockery::mock(Client::class)
            ->shouldReceive('attach')
            ->once()
            ->with([
                'fallback'  => 'Indexing log',
                'text'      => 'Indexing log',
                'color'     => 'yellow',
                'fields'    => [
                    'foo'       => 'bar',
                    'age'       => 34,
                    'height'    => 192,
                ],
            ])
            ->andReturn(Mockery::self())
            ->shouldReceive('send')
            ->with('Hello world!')
            ->andReturn(null)
            ->getMock();

        $logger = new SlackLogger($client);

        $logger->message('Hello world!', [
            'foo'       => 'bar',
            'age'       => 34,
            'height'    => 192,
        ], 'yellow');
    }    
}
