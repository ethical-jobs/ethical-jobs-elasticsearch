<?php

namespace Tests\Integration\Indexing\IndexQuery;

use Tests\Fixtures\Person;
use EthicalJobs\Elasticsearch\Indexing\IndexQuery;

class ChunkingTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_creates_correct_amount_of_chunks()
    {
        factory(Person::class, 137)->create();

        $indexQuery = new IndexQuery(new Person, 51);

        $this->assertEquals(3, $indexQuery->chunkCount());  
    }

    /**
     * @test
     * @group Integration
     */
    public function its_chunks_have_correct_query_parameters()
    {
        $expected = factory(Person::class, 50)->create();

        $indexQuery = new IndexQuery(new Person, 5);

        $indexQuery->getChunks()->each(function ($chunk) {
            $this->assertTrue(is_numeric($chunk['offset']));
            $this->assertTrue(is_numeric($chunk['limit']));
        });   
    }    

    /**
     * @test
     * @group Integration
     */
    public function it_passes_index_to_the_chunking_callback()
    {
        $expected = factory(Person::class, 50)->create();

        $indexQuery = new IndexQuery(new Person, 5);

        $counter = 0;

        $indexQuery->chunk(function($chunk, $index) use(&$counter) {
            $this->assertEquals($counter, $index);
            $counter++;
        });   
    }              

    /**
     * @test
     * @group Integration
     */
    public function it_captures_every_single_document()
    {
        $expected = factory(Person::class, 339)->create();
        $collected = (new Person)->newCollection();

        $indexQuery = new IndexQuery(new Person, 50);

        $indexQuery->chunk(function ($chunk) use($collected) {
            $chunk->each(function ($person) use($collected) {
                $collected->push($person);
            });
        });   

        $this->assertEquals(339, $collected->count());
        $this->assertEquals($expected->modelKeys(), $collected->modelKeys());
    }    

    /**
     * @test
     * @group Integration
     */
    public function it_queries_the_correct_indexable()
    {
        factory(Person::class, 50)->create();

        $indexQuery = new IndexQuery(new Person, 25);

        $indexQuery->chunk(function ($chunk) {
            $chunk->each(function ($person) {
                $this->assertInstanceOf(Person::class, $person);
            });
        });   
    }           
}
