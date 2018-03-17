<?php

namespace Tests\Integration;

use EthicalJobs\Elasticsearch\IndexSettings;
use Tests\Fixtures;

class IndexSettingsTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_can_set_its_name_settings_and_mappings()
    {
        $settings = new IndexSettings('anderws-index', ['host' => 'localhost:9200'], ['dogs' => '123']);

        $this->assertEquals('anderws-index', $settings->name);
        $this->assertEquals(['host' => 'localhost:9200'], $settings->settings);
        $this->assertEquals(['dogs' => '123'], $settings->mappings);
    }

    /**
     * @test
     * @group Integration
     */
    public function it_can_get_and_set_its_indexable_models()
    {
        $settings = new IndexSettings('anderws-index', ['host' => 'localhost:9200'], ['dogs' => '123']);

        $settings->setIndexables([
            Fixtures\Person::class,
            Fixtures\Family::class,
        ]);

        $this->assertEquals($settings->getIndexables(), [
            Fixtures\Person::class,
            Fixtures\Family::class,
        ]);
    }    
}
