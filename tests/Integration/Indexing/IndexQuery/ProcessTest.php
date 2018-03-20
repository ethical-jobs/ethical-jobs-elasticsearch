<?php

namespace Tests\Integration\Indexing\IndexQuery;

use Tests\Fixtures\Person;
use EthicalJobs\Elasticsearch\Indexing\IndexQuery;

class ProcessTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_creates_correct_amount_of_sub_queries()
    {
        factory(Person::class, 40)->create();

        $indexQuery = new IndexQuery(new Person, 22); 
        $indexQuery->setNumberOfProcesses(3);
        $queries = $indexQuery->split();

        $this->assertEquals(2, $queries->count());  

        factory(Person::class, 30)->create(); // Create another 30 people

        $indexQuery = new IndexQuery(new Person, 10); 
        $indexQuery->setNumberOfProcesses(3);
        $queries = $indexQuery->split(); 
        
        $this->assertEquals(3, $queries->count());   
    }

    /**
     * @test
     * @group Integration
     */
    public function its_sub_queries_have_all_chunks_of_the_query()
    {
        factory(Person::class, 333)->create();

        $indexQuery = new IndexQuery(new Person, 22); 

        $expected = $indexQuery->getChunks();

        $indexQuery->setNumberOfProcesses(4);

        $collected = collect([]);

        $indexQuery->split()->each(function($query) use(&$collected) {
            $collected = $collected->merge($query->getChunks());
        });

        $this->assertEquals($expected->toArray(), $collected->toArray());  
    }   

    /**
     * @test
     * @group Integration
     */
    public function its_sub_queries_have_the_correct_property_values()
    {
        factory(Person::class, 40)->create();

        $parentQuery = new IndexQuery(new Person, 10); 

        $parentQuery->setNumberOfProcesses(4);

        $parentQuery->split()->each(function($childQuery) use($parentQuery) {
            $this->assertEquals($parentQuery->uuid, $childQuery->uuid);
            $this->assertEquals($parentQuery->indexable, $childQuery->indexable);
            $this->assertEquals($parentQuery->documentCount(), $childQuery->documentCount());
            $this->assertEquals($parentQuery->processCount(), $childQuery->processCount());
        });
    }         
}
