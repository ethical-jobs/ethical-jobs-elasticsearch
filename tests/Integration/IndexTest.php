<?php

namespace Tests\Integration;

use Mockery;
use Elasticsearch\Client;
use EthicalJobs\Elasticsearch\Index;
use EthicalJobs\Elasticsearch\Indexable;
use Tests\Fixtures;

class IndexTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_can_set_its_client_param()
    {
        $mockClient = Mockery::mock(Client::class);

        $index = app()->make(Index::class);

        $this->assertInstanceOf(Index::class, $index->setClient($mockClient));
    }

    /**
     * @test
     * @group Integration
     */
    public function it_can_return_an_array_of_indexables()
    {
        $index = app()->make(Index::class);

        foreach ($index->getSettings()->getIndexables() as $indexable) {

            $interfaces = class_implements($indexable);

            $this->assertTrue(array_key_exists(Indexable::class, $interfaces));
        }
    }

    /**
     * @test
     * @group Integration
     */
    public function it_can_check_if_a_model_instance_is_indexable()
    {
        $index = app()->make(Index::class);

        $this->assertTrue($index->isIndexable(new Fixtures\Vehicle));
    }

    /**
     * @test
     * @group Integration
     */
    public function it_returns_the_index_name()
    {
        $index = app()->make(Index::class);

        $this->assertEquals(config('elasticsearch.index'), $index->getIndexName());
    }

    /**
     * @test
     * @group Integration
     */
    public function it_returns_the_index_settings()
    {
        $index = app()->make(Index::class);

        $this->assertTrue(is_array($index->getSettings()->settings));
    }

    /**
     * @test
     * @group Integration
     */
    public function it_returns_the_index_mappings()
    {
        $index = app()->make(Index::class);

        $indexMappings = $index->getIndexMappings();

        foreach ($index->getSettings()->getIndexables() as $class) {

            $indexable = new $class;

            $indexableMapKeys = array_keys($indexable->getDocumentMappings());

            $expected = $indexMappings[$indexable->getDocumentType()]['properties'];

            $this->assertTrue(array_has($expected, $indexableMapKeys));
        }
    }

    /**
     * @test
     * @group Integration
     */
    public function it_can_create_the_index()
    {
        $index = app()->make(Index::class);

        $mockClient = Mockery::mock(Client::class)
            ->shouldReceive('indices')
            ->withNoArgs()
            ->andReturn(Mockery::self())
            ->shouldReceive('exists')
            ->with([
                'index' => $index->getIndexName()
            ])
            ->andReturn(false)
            ->shouldReceive('indices')
            ->withNoArgs()
            ->andReturn(Mockery::self())
            ->shouldReceive('create')
            ->with([
                'index' => 'testing',
                'body'  => [
                    'settings' => $index->getSettings()->settings,
                    'mappings' => $index->getIndexMappings(),
                ],
            ])
            ->andReturn('success')
            ->getMock();

        $result = $index
            ->setClient($mockClient)
            ->create();

        $this->assertEquals('success', $result);
    }

    /**
     * @test
     * @group Integration
     */
    public function it_throws_exception_when_creating_an_already_existing_index()
    {
        $this->expectException(\Exception::class);

        $index = app()->make(Index::class);

        $mockClient = Mockery::mock(Client::class)
            ->shouldReceive('indices')
            ->withNoArgs()
            ->andReturn(Mockery::self())
            ->shouldReceive('exists')
            ->with([
                'index' => $index->getIndexName(),
            ])
            ->andReturn(true)
            ->getMock();

        $index
            ->setClient($mockClient)
            ->create();
    }

    /**
     * @test
     * @group Integration
     */
    public function it_can_delete_the_index()
    {
        $index = app()->make(Index::class);

        $mockClient = Mockery::mock(Client::class)
            ->shouldReceive('indices')->withNoArgs()->andReturn(Mockery::self())
            ->shouldReceive('delete')->with(['index' => $index->getIndexName()])->andReturn(true)
            ->getMock();

        $result = $index
            ->setClient($mockClient)
            ->delete();

        $this->assertTrue($result);
    }

    /**
     * @test
     * @group Integration
     */
    public function it_throws_exception_when_deleting_a_non_existing_index()
    {
        $this->expectException(\Exception::class);

        $index = app()->make(Index::class);

        $mockClient = Mockery::mock(Client::class)
            ->shouldReceive('indices')
            ->withNoArgs()
            ->andReturn(Mockery::self())
            ->shouldReceive('exists')
            ->with([
                'index' => $index->getIndexName(),
            ])
            ->andReturn(false)
            ->getMock();

        $index
            ->setClient($mockClient)
            ->delete();
    }

    /**
     * @test
     * @group Integration
     */
    public function it_can_check_if_the_index_exits()
    {
        $index = app()->make(Index::class);

        $mockClient = Mockery::mock(Client::class)
            ->shouldReceive('indices')
            ->withNoArgs()
            ->andReturn(Mockery::self())
            ->shouldReceive('exists')
            ->with([
                'index' => $index->getIndexName()
            ])
            ->andReturn(true)
            ->getMock();

        $result = $index
            ->setClient($mockClient)
            ->exists();

        $this->assertTrue($result);
    }
}
