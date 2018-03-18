<?php

namespace Tests\Integration\Indexing;

use Mockery;
use Tests\Fixtures\Person;
use Illuminate\Support\Collection;
use EthicalJobs\Elasticsearch\Indexing\IndexQuery;

class IndexQueryTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_can_construct_an_index_query()
    {
        $indexable = new Person;

        $indexQuery = new IndexQuery($indexable);

        $this->assertEquals($indexable->getIndexingQuery(), $indexQuery->query);
        $this->assertEquals($indexable, $indexQuery->indexable);
    }

    /**
     * @test
     * @group Integration
     */
    public function it_can_set_its_chunkSize()
    {
        factory(Person::class, 500)->create();

        $indexQuery = new IndexQuery(new Person);

        $indexQuery->setChunkSize(133);

        $this->assertEquals(133, $indexQuery->getParam('chunkSize'));
        $this->assertEquals(4, $indexQuery->getParam('numberOfChunks'));
    }    

    /**
     * @test
     * @group Integration
     */
    public function it_can_set_its_number_of_processes()
    {
        factory(Person::class, 500)->create();

        $indexQuery = new IndexQuery(new Person);

        $indexQuery->setNumberOfProcesses(8);

        $this->assertEquals(8, $indexQuery->getParam('numberOfProcesses'));
        $this->assertEquals(63, $indexQuery->getParam('itemsPerProcess'));
    }    

    /**
     * @test
     * @group Integration
     */
    public function it_can_split_queries_by_process()
    {
        factory(Person::class, 500)->create();

        $indexQuery = new IndexQuery(new Person);

        $subQueries = $indexQuery
            ->setChunkSize(50)
            ->setNumberOfProcesses(4)
            ->getSubQueries();

        $this->assertInstanceOf(Collection::class, $subQueries);
        $this->assertEquals(4, $subQueries->count());

        $this->assertEquals($subQueries[0]->toArray(), [
            'indexable'         => 'Tests\Fixtures\Person',
            'chunkSize'         => 50,
            'numberOfChunks'    => 10,
            'numberOfProcesses' => 4,
            'itemsPerProcess'   => 125,
            'processOffset'     => 0,
            'process'           => '1/4',            
        ]);  

        $this->assertEquals($subQueries[1]->toArray(), [
            'indexable'         => 'Tests\Fixtures\Person',
            'chunkSize'         => 50,
            'numberOfChunks'    => 10,
            'numberOfProcesses' => 4,
            'itemsPerProcess'   => 125,
            'processOffset'     => 125,
            'process'           => '2/4',            
        ]);  

        $this->assertEquals($subQueries[2]->toArray(), [
            'indexable'         => 'Tests\Fixtures\Person',
            'chunkSize'         => 50,
            'numberOfChunks'    => 10,
            'numberOfProcesses' => 4,
            'itemsPerProcess'   => 125,
            'processOffset'     => 250,
            'process'           => '3/4',            
        ]);  

        $this->assertEquals($subQueries[3]->toArray(), [
            'indexable'         => 'Tests\Fixtures\Person',
            'chunkSize'         => 50,
            'numberOfChunks'    => 10,
            'numberOfProcesses' => 4,
            'itemsPerProcess'   => 125,
            'processOffset'     => 375,
            'process'           => '4/4',            
        ]);                          
    }       

    /**
     * @test
     * @group Integration
     */
    public function it_can_build_the_query()
    {      
        factory(Person::class, 500)->create();

        $indexQuery = new IndexQuery(new Person);

        $subQueries = $indexQuery
            ->setChunkSize(50)
            ->setNumberOfProcesses(4)
            ->getSubQueries();

        $query = $subQueries->last();
        
        $query->buildQuery();

        $this->assertEquals(
            'select * from "people" order by "created_at" desc limit 125 offset 375', 
            $query->query->toSql()
        );
    }

    /**
     * @test
     * @group Integration
     */
    public function it_can_chunk_results()
    {      
        factory(Person::class, 500)->create();

        $indexQuery = new IndexQuery(new Person);

        $indexQuery
            ->setChunkSize(50)
            ->chunk(function ($chunk) {
                $this->assertEquals(50, $chunk->count());
            });
    }  

    /**
     * @test
     * @group Integration
     */
    public function it_is_serializable()
    {      
        factory(Person::class, 500)->create();

        $indexQuery = new IndexQuery(new Person);

        $indexQuery
            ->setChunkSize(50)
            ->setNumberOfProcesses(4)
            ->buildQuery();   
            
        $serialized = serialize($indexQuery);     
        $unserialized = unserialize($serialized);

        $this->assertEquals($indexQuery->toArray(), $unserialized->toArray());
    }        
}
