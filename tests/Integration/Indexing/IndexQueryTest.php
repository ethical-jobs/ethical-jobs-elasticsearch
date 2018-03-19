<?php

namespace Tests\Integration\Indexing;

use Tests\Fixtures\Person;
use Illuminate\Support\Facades\Queue;
use EthicalJobs\Elasticsearch\Indexing\IndexQuery;
use EthicalJobs\Elasticsearch\Indexing\ProcessIndexQuery;

class IndexQueryTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_can_chunk_results()
    {
        factory(Person::class, 1000)->create();

        $indexQuery = new IndexQuery(new Person);

        $indexQuery->makeChunks(100);

        $indexQuery->chunk(function ($chunk, $index) {
            $this->assertEquals(100, $chunk->count());
            $chunk->each(function ($person) {
                $this->assertInstanceOf(Person::class, $person);
            });
        });   

        // Assert params are set
        $this->assertEquals(10, $indexQuery->getParam('numberOfChunks'));
        $this->assertEquals(100, $indexQuery->getParam('chunkSize'));
    }

    /**
     * @test
     * @group Integration
     */
    public function it_captures_every_single_record()
    {
        $expected = factory(Person::class, 339)->create();

        $actual = (new Person)->newCollection();

        $indexQuery = new IndexQuery(new Person);

        $indexQuery->makeChunks(100);

        $indexQuery->chunk(function ($chunk) use($actual) {
            $chunk->each(function ($person) use($actual) {
                $actual->push($person);
            });
        });   

        $this->assertEquals(4, $indexQuery->getChunks()->count());
        $this->assertEquals(339, $actual->count());
        $this->assertEquals($expected->modelKeys(), $actual->modelKeys());
    }    

    /**
     * @test
     * @group Integration
     */
    public function it_can_split_a_query_into_processes()
    {
        Queue::fake();

        factory(Person::class, 1000)->create();

        $indexQuery = new IndexQuery(new Person);

        $indexQuery->split(4, 100);

        Queue::assertPushed(ProcessIndexQuery::class, 4);

        Queue::assertPushed(ProcessIndexQuery::class, function ($event) {
            $this->assertTrue($event->indexQuery->getChunks()->isNotEmpty());
            $event->indexQuery->getChunks()->each(function($chunk) {
                $this->assertTrue(array_has($chunk, ['offset','limit']));
            });
            return true;
        });        

        // Assert params are set
        $this->assertEquals(4, $indexQuery->getParam('numberOfProcesses'));
        $this->assertEquals(10, $indexQuery->getParam('numberOfChunks'));
        $this->assertEquals(100, $indexQuery->getParam('chunkSize'));        
    }    

    /**
     * @test
     * @group Integration
     */
    public function it_can_set_and_get_its_chunks()
    {
        factory(Person::class, 1000)->create();

        $indexQuery = new IndexQuery(new Person);

        $indexQuery->setChunks(collect([
            ['offset' => 0, 'limit' => 100],
            ['offset' => 100, 'limit' => 100],
            ['offset' => 200, 'limit' => 100],
            ['offset' => 300, 'limit' => 100],
        ]));

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
    public function it_can_set_and_get_its_params()
    {
        $indexQuery = new IndexQuery(new Person);

        $indexQuery->setParam('chunkSize', 150);

         $this->assertEquals(150, $indexQuery->getParam('chunkSize'));
    }             
}
