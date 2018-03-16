<?php

namespace Tests\Integration\Indexing;

use Mockery;
use Maknz\Slack\Client;
use Illuminate\Support\Facades\App;
use EthicalJobs\Elasticsearch\Indexing\Logger;

class LoggerTest extends \EthicalJobs\Tests\Elasticsearch\TestCase
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
                'fallback'  => '',
                'text'      => '',
                'color'     => 'yellow',
                'fields'    => [
                    ['title' => 'foo','value' => 'bar'],
                    ['title' => 'age','value' => 34],
                    ['title' => 'height','value' => 192],
                ],
            ])
            ->andReturn(Mockery::self())
            ->shouldReceive('send')
            ->with('Hello world!')
            ->andReturn(null)
            ->getMock();

        $logger = new Logger($client);

        $logger->message('Hello world!', [
            'foo'       => 'bar',
            'age'       => 34,
            'height'    => 192,
        ], 'yellow');
    }    
}
