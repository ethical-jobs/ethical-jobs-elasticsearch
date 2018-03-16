<?php

namespace Tests\Integration\Hydrators;

use ArrayObject;
use Illuminate\Support\Collection;
use EthicalJobs\Elasticsearch\Hydrators\ArrayObjectHydrator;
use EthicalJobs\Tests\Elasticsearch\Fixtures;

class ArrayObjectHydratorTest extends \EthicalJobs\Tests\Elasticsearch\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_returns_a_collection_of_array_objects()
    {
        $vehicles = factory(Fixtures\Vehicle::class, 5)->create();

        $response = $this->getMockSearchResults($vehicles);

        $collection = (new ArrayObjectHydrator)
            ->hydrateFromResponse($response, new Fixtures\Vehicle);

        $this->assertInstanceOf(Collection::class, $collection);

        $collection->each(function ($entity) {
            $this->assertInstanceOf(ArrayObject::class, $entity);
        });
    }

    /**
     * @test
     * @group Integration
     */
    public function it_sets_a_score_property_on_models()
    {
        $vehicles = factory(Fixtures\Vehicle::class, 5)->create();

        $response = $this->getMockSearchResults($vehicles);

        $collection = (new ArrayObjectHydrator)
            ->hydrateFromResponse($response, new Fixtures\Vehicle);

        $collection->each(function ($entity) {
            $this->assertEquals(1, $entity->_score);
        });
    }

    /**
     * @test
     * @group Integration
     */
    public function it_sets_a_isDocument_property_on_models()
    {
        $vehicles = factory(Fixtures\Vehicle::class, 5)->create();

        $response = $this->getMockSearchResults($vehicles);

        $collection = (new ArrayObjectHydrator)
            ->hydrateFromResponse($response, new Fixtures\Vehicle);

        $collection->each(function ($entity) {
            $this->assertTrue($entity->_isDocument);
        });
    }

    /**
     * @test
     * @group Integration
     */
    public function it_returns_empty_collection_when_there_are_no_results()
    {
        $response = [];

        $collection = (new ArrayObjectHydrator)
            ->hydrateFromResponse($response, new Fixtures\Vehicle);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $collection);

        $this->assertEquals(0, $collection->count());
    }

    /**
     * @test
     * @group Integration
     * @group skipped
     */
    public function it_builds_document_relations()
    {
        $expectedRelations = ['members', 'vehicles'];

        $documentRelations = (new Fixtures\Family)->getDocumentRelations();

        $families = factory(Fixtures\Family::class, 2)
            ->create()
            ->each(function ($family) {
                factory(Fixtures\Person::class, rand(2, 6))->create(['family_id' => $family->id]);
                factory(Fixtures\Vehicle::class, rand(1, 2))->create(['family_id' => $family->id]);
            });

        $families->load($documentRelations);

        $response = $this->getMockSearchResults($families);

        $collection = (new ArrayObjectHydrator)
            ->hydrateFromResponse($response, new Fixtures\Family);

        // Check that document relations are built
        foreach ($collection as $family) {
            foreach ($expectedRelations as $relation) {
                $this->assertTrue($family->$relation->id ? true : false);
            }
        }
    }
}
