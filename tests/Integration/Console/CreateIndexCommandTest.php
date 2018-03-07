<?php

namespace EthicalJobs\Tests\Elasticsearch\Integration\Console;

use Mockery;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use EthicalJobs\Elasticsearch\Index;

class CreateIndexCommandTest extends \EthicalJobs\Tests\Elasticsearch\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_create_an_index()
    {
        $index = Mockery::mock(Index::class)
            ->shouldReceive('create')
            ->once()
            ->withNoArgs()
            ->getMock();

        App::instance(Index::class, $index);

        Artisan::call('ej:es:index-create');
    }
}
