<?php

namespace Tests\Integration\Indexing\Logging;

use Mockery;
use Maknz\Slack\Client;
use Illuminate\Support\Facades\App;
use EthicalJobs\Elasticsearch\Indexing\Logging\SlackChannel;

class SlackChannelTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_attaches_a_field_to_the_message()
    {
        $markdown = '```';

        $encoded = $markdown.json_encode(['foo' => 'bar', 'bar' => 'foo'], JSON_PRETTY_PRINT).$markdown;

        $expected = [
            'color'     => '',
            'fields'    => [
                ['title' => '','value' => $encoded],
            ],         
        ];

        $client = Mockery::mock(Client::class)
            ->shouldReceive('attach')
            ->once()
            ->with($expected)
            ->andReturn(Mockery::self())
            ->shouldReceive('send')
            ->withAnyArgs()
            ->andReturn(null)
            ->getMock();

        $channel = new SlackChannel($client);

        $channel->log('foo', ['foo' => 'bar', 'bar' => 'foo']);
    }   

    /**
     * @test
     * @group Integration
     */
    public function it_has_a_color_for_production()
    {
        App::shouldReceive('environment')
            ->once()
            ->withNoArgs()
            ->andReturn('production');

        $client = Mockery::mock(Client::class)
            ->shouldReceive('attach')
            ->once()
            ->withArgs(function ($params) {
                return '#42f44b' === $params['color'];
            })
            ->andReturn(Mockery::self())
            ->shouldReceive('send')
            ->withAnyArgs()
            ->andReturn(null)
            ->getMock();

        $channel = new SlackChannel($client);

        $channel->log('foo', ['foo' => 'bar', 'bar' => 'foo']);
    }  

    /**
     * @test
     * @group Integration
     */
    public function it_sends_a_message_with_the_data()
    {
        $client = Mockery::mock(Client::class)
            ->shouldReceive('attach')
            ->once()
            ->withAnyArgs()
            ->andReturn(Mockery::self())
            ->shouldReceive('send')
            ->with('Hello world!')
            ->andReturn(null)
            ->getMock();

        $channel = new SlackChannel($client);

        $channel->log('Hello world!', []);
    }                        
}
