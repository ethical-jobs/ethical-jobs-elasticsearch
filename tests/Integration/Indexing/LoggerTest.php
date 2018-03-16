<?php

namespace Tests\Integration\Indexing;

use Mockery;
use Maknz\Slack\Client;
use Illuminate\Support\Facades\App;
use Symfony\Component\Console\Output\ConsoleOutput;
use EthicalJobs\Elasticsearch\Indexing\Logger;

class LoggerTest extends \EthicalJobs\Tests\Elasticsearch\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_can_log_a_message()
    {
        $slack = Mockery::mock(Client::class)
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

        $console = Mockery::mock(ConsoleOutput::class);

        $logger = new Logger($slack, $console);

        $logger->message('Hello world!', [
            'foo'       => 'bar',
            'age'       => 34,
            'height'    => 192,
        ], 'yellow');
    } 

    /**
     * @test
     * @group Integration
     */
    public function it_can_log_a_message_to_console_when_not_testing()
    {
        $data = [
            'foo'       => 'bar',
            'age'       => 34,
            'height'    => 192,
        ];

        App::shouldReceive('runningUnitTests')
            ->once()
            ->withNoArgs()
            ->andReturn(false);

        $slack = Mockery::mock(Client::class)
            ->shouldReceive('attach')
            ->once()
            ->withAnyArgs()
            ->andReturn(Mockery::self())
            ->shouldReceive('send')
            ->withAnyArgs()
            ->andReturn(null)
            ->getMock();

        $console = Mockery::mock(ConsoleOutput::class)
            ->shouldReceive('writeln')
            ->once()
            ->with('<comment>Hello world!</comment>')
            ->andReturn(null)
            ->shouldReceive('writeln')
            ->once()
            ->with('<info>'.json_encode($data, JSON_PRETTY_PRINT).'</info>')
            ->andReturn(null)            
            ->getMock();

        $logger = new Logger($slack, $console);

        $logger->message('Hello world!', $data);
    }        
}
