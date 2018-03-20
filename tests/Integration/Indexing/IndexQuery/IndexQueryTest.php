<?php

namespace Tests\Integration\Indexing\IndexQuery;

use Tests\Fixtures\Person;
use EthicalJobs\Elasticsearch\Indexing\IndexQuery;

class IndexQueryTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_sets_a_uuid_when_created()
    {
        $indexQuery = new IndexQuery(new Person, 50);

        $this->assertTrue(is_string($indexQuery->uuid));
        $this->assertEquals(20, strlen($indexQuery->uuid));
    }

    /**
     * @test
     * @group Integration
     */
    public function it_sets_its_indexable()
    {
        $indexable = new Person;

        $indexQuery = new IndexQuery($indexable, 50);

        $this->assertEquals($indexable, $indexQuery->indexable);
    }    

    /**
     * @test
     * @group Integration
     */
    public function it_sets_chunks_to_an_empty_collection()
    {
        $indexQuery = new IndexQuery(new Person, 50);

        $this->assertEquals(collect([]), $indexQuery->getChunks());
    }    

    /**
     * @test
     * @group Integration
     */
    public function it_has_correct_initial_counts()
    {
        factory(Person::class, 15)->create();

        $indexQuery = new IndexQuery(new Person, 5);

        $this->assertEquals(1, $indexQuery->processCount());
        $this->assertEquals(3, $indexQuery->chunkCount());
        $this->assertEquals(15, $indexQuery->documentCount());
    }   

    /**
     * @test
     * @group Integration
     */
    public function it_can_set_and_get_its_chunks()
    {
        $indexQuery = new IndexQuery(new Person, 50);

        $indexQuery->setChunks([
            ['offset' => 0, 'limit' => 100],
            ['offset' => 100, 'limit' => 100],
            ['offset' => 200, 'limit' => 100],
            ['offset' => 300, 'limit' => 100],
        ]);

         $this->assertEquals(collect([
            ['offset' => 0, 'limit' => 100],
            ['offset' => 100, 'limit' => 100],
            ['offset' => 200, 'limit' => 100],
            ['offset' => 300, 'limit' => 100],
        ]), $indexQuery->getChunks());
    }     

    /**
     * @test
     * @group Integration
     */
    public function it_can_return_the_current_document_count()
    {
        factory(Person::class, 34)->create();

        $indexQuery = new IndexQuery(new Person, 50);

        factory(Person::class, 21)->create();

         $this->assertEquals(55, $indexQuery->documentCount());
    }                   
}
