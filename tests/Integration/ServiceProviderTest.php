<?php

namespace EthicalJobs\Tests\Elasticsearch\Integration;

use Elasticsearch\Client;
use Illuminate\Support\Facades\Event;
use EthicalJobs\Elasticsearch\Index;

class ServiceProviderTest extends \EthicalJobs\Tests\Elasticsearch\TestCase
{
    /**
     * @test
     * @group Unit
     */
    public function it_loads_es_service_provider()
    {
        $providers = $this->app->getLoadedProviders();

        $this->assertTrue($providers[\EthicalJobs\Elasticsearch\ServiceProvider::class]);
    }  

    /**
     * @test
     * @group Unit
     */
    public function it_loads_package_config()
    {
        $this->assertTrue(array_has(config('elasticsearch'), [
            'defaultConnection',
            'connections.default.hosts',
            'index',
            'settings',
            'mappings',
            'indexables',
        ]));
    }          

    /**
     * @test
     * @group Unit
     */
    public function it_registers_client_instance()
    {
        $client = $this->app->make(Client::class);

        $this->assertInstanceOf(Client::class, $client);
    }     

    /**
     * @test
     * @group Unit
     */
    public function it_registers_index_instance()
    {
        $index = $this->app->make(Index::class);

        $this->assertInstanceOf(Index::class, $index);
        $this->assertEquals('testing', $index->getIndexName());
        $this->assertEquals(config('elasticsearch.settings'), $index->getSettings()->settings);
        $this->assertEquals(config('elasticsearch.mappings'), $index->getSettings()->mappings);
    }          

    /**
     * @test
     * @group Unit
     */
    public function it_observes_indexables()
    {
        $index = $this->app->make(Index::class);

        $indexables = $index->getSettings()->getIndexables();

        $this->assertTrue(count($indexables) > 0);

        foreach ($indexables as $indexable) {
            $this->assertEquals(1, count(Event::getListeners("eloquent.created: $indexable")));
            $this->assertEquals(1, count(Event::getListeners("eloquent.updated: $indexable")));
            $this->assertEquals(1, count(Event::getListeners("eloquent.restored: $indexable")));
            $this->assertEquals(1, count(Event::getListeners("eloquent.deleted: $indexable")));
        }
    }                 
}
