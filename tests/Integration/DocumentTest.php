<?php

namespace Tests\Integration;

use EthicalJobs\Elasticsearch\Index;

class DocumentTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function documents_can_return_their_keys()
    {
        $index = app()->make(Index::class);

        foreach ($index->getSettings()->getIndexables() as $class) {
            $indexable = new $class;
            $this->assertEquals($indexable->id, $indexable->getDocumentKey());
        }
    }

    /**
     * @test
     * @group Integration
     */
    public function documents_can_return_their_body()
    {
        $index = app()->make(Index::class);

        foreach ($index->getSettings()->getIndexables() as $class) {
            $indexable = new $class;
            $this->assertTrue(is_array($indexable->getDocumentBody()));
        }
    }

    /**
     * @test
     * @group Integration
     */
    public function documents_can_return_their_type()
    {
        $index = app()->make(Index::class);

        foreach ($index->getSettings()->getIndexables() as $class) {
            $indexable = new $class;
            $this->assertEquals($indexable->getTable(), $indexable->getDocumentType());
        }
    }

    /**
     * @test
     * @group Integration
     */
    public function documents_can_return_their_mappings()
    {
        $index = app()->make(Index::class);

        foreach ($index->getSettings()->getIndexables() as $class) {
            foreach ((new $class)->getDocumentMappings() as $mapping) {
                $this->assertTrue(array_has($mapping, 'type'));
            }
        }
    }

    /**
     * @test
     * @group Integration
     */
    public function documents_can_return_their_relations()
    {
        $index = app()->make(Index::class);

        foreach ($index->getSettings()->getIndexables() as $class) {
            $indexable = new $class;
            $this->assertTrue(is_array($indexable->getDocumentRelations()));
        }
    }
}
