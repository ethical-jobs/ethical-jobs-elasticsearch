<?php

namespace EthicalJobs\Tests\Elasticsearch\Integration\Elasticsearch;

use Mockery;
use Elasticsearch\Client;
use EthicalJobs\Elasticsearch\DocumentIndexer;
use EthicalJobs\Tests\Elasticsearch\Fixtures;
use EthicalJobs\Elasticsearch\Index;

class DocumentIndexerTest extends \EthicalJobs\Tests\Elasticsearch\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_can_set_class_params()
    {
        $client = Mockery::mock(Client::class);

        $index = app()->make(Index::class);

        $indexer = new DocumentIndexer($client, $index);

        $this->assertInstanceOf(DocumentIndexer::class, $indexer->setClient($client));

        $this->assertInstanceOf(DocumentIndexer::class, $indexer->setChunkSize(5));

        $this->assertInstanceOf(DocumentIndexer::class, $indexer->setLogging(true));
    }

    /**
     * @test
     * @group Integration
     */
    public function it_can_index_a_single_entity()
    {
        $person = factory(Fixtures\Person::class)->create();

        $client = Mockery::mock(Client::class)
            ->shouldReceive('index')
            ->once()
            ->with([
                'index' => 'testing',
                'id'    => 1,
                'type'  => 'people',
                'body'  => $person->getDocumentTree(),
            ])
            ->andReturn('success')
            ->getMock();

        $index = app()->make(Index::class);

        $indexer = new DocumentIndexer($client, $index);            

        $result = $indexer->indexDocument($person);

        $this->assertEquals('success', $result);
    }

    /**
     * @test
     * @group Integration
     */
    public function it_can_delete_a_single_entity()
    {
        $person = factory(Fixtures\Person::class)->create();

        $client = Mockery::mock(Client::class)
            ->shouldReceive('delete')
            ->with([
                'index' => 'testing',
                'id'    => 1,
                'type'  => 'people',
            ])
            ->andReturn('deleted')
            ->getMock();

        $index = app()->make(Index::class);

        $indexer = new DocumentIndexer($client, $index); 

        $result = $indexer->deleteDocument($person);

        $this->assertEquals('deleted', $result);
    }

    /**
     * @test
     * @group Integration
     */
    public function it_can_index_a_collection_from_a_query()
    {
        $families = factory(Fixtures\Family::class, 6)->create();

        $query = Fixtures\Family::query();

        $client = Mockery::mock(Client::class)
            ->shouldReceive('bulk')
            ->once()
            ->withArgs(function($params) use ($families) {
                $this->assertEquals('testing', array_get($params, 'body.0.index._index'));
                $this->assertEquals('families', array_get($params, 'body.0.index._type'));
                $this->assertEquals($families[0]->surname, array_get($params, 'body.1.surname'));
                // -- Next family
                $this->assertEquals('testing', array_get($params, 'body.0.index._index'));
                $this->assertEquals('families', array_get($params, 'body.0.index._type'));                
                $this->assertEquals($families[1]->surname, array_get($params, 'body.3.surname'));
                return true;
            })
            ->andReturn(['success'])
            ->shouldReceive('bulk')
            ->once()
            ->withArgs(function($params) use ($families) {
                $this->assertEquals('testing', array_get($params, 'body.0.index._index'));
                $this->assertEquals('families', array_get($params, 'body.0.index._type'));
                $this->assertEquals($families[2]->surname, array_get($params, 'body.1.surname'));
                // -- Next family
                $this->assertEquals('testing', array_get($params, 'body.0.index._index'));
                $this->assertEquals('families', array_get($params, 'body.0.index._type'));                
                $this->assertEquals($families[3]->surname, array_get($params, 'body.3.surname'));
                return true;
            })
            ->andReturn(['success'])
            ->shouldReceive('bulk')
            ->once()
            ->withArgs(function($params) use ($families) {
                $this->assertEquals('testing', array_get($params, 'body.0.index._index'));
                $this->assertEquals('families', array_get($params, 'body.0.index._type'));
                $this->assertEquals($families[4]->surname, array_get($params, 'body.1.surname'));
                // -- Next family
                $this->assertEquals('testing', array_get($params, 'body.0.index._index'));
                $this->assertEquals('families', array_get($params, 'body.0.index._type'));                
                $this->assertEquals($families[5]->surname, array_get($params, 'body.3.surname'));
                return true;
            })
            ->andReturn(['success'])                        
            ->getMock();

        $index = app()->make(Index::class);

        $indexer = new DocumentIndexer($client, $index); 

        $indexer
            ->setChunkSize(2)
            ->indexCollection($query);
    }    
}
