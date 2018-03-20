<?php

namespace Tests\Integration\Indexing\Logging;

use Mockery;
use Illuminate\Support\Facades\App;
use Symfony\Component\Console\Output\ConsoleOutput;
use EthicalJobs\Elasticsearch\Indexing\Logging\ConsoleChannel;

class ConsoleChannelTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_writes_a_message_line()
    {
        App::shouldReceive('runningUnitTests')
            ->once()
            ->withNoArgs()
            ->andReturn(false);

        $console = Mockery::mock(ConsoleOutput::class)
            ->shouldReceive('writeln')
            ->once()
            ->with('<info>Hello world!</info>')
            ->andReturn(null)
            ->shouldReceive('writeln')
            ->once()
            ->withAnyArgs()
            ->andReturn(null)
            ->getMock();            

        $channel = new ConsoleChannel($console);

        $channel->log('Hello world!', []);
    }   

    /**
     * @test
     * @group Integration
     */
    public function it_writes_an_encoded_data_line()
    {
        $data = ['foo' => 'bar', 'bar' => 123];

        App::shouldReceive('runningUnitTests')
            ->once()
            ->withNoArgs()
            ->andReturn(false);

        $console = Mockery::mock(ConsoleOutput::class)
            ->shouldReceive('writeln')
            ->once()
            ->withAnyArgs()
            ->andReturn(null)
            ->shouldReceive('writeln')
            ->once()
            ->with(json_encode($data, JSON_PRETTY_PRINT).PHP_EOL)
            ->andReturn(null)
            ->getMock();            

        $channel = new ConsoleChannel($console);

        $channel->log('', $data);
    }       

    /**
     * @test
     * @group Integration
     */
    public function it_wrotes_nothing_when_running_tests()
    {
        $data = ['foo' => 'bar', 'bar' => 123];
        
        App::shouldReceive('runningUnitTests')
            ->once()
            ->withNoArgs()
            ->andReturn(true);

        $console = Mockery::mock(ConsoleOutput::class)
            ->shouldReceive('writeln')
            ->never()
            ->getMock();

        $channel = new ConsoleChannel($console);

        $channel->log('', $data);
    }                              
}
