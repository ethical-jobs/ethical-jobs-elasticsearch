<?php

namespace Tests\Integration\Indexing\Indexer;

use Mockery;
use Elasticsearch\Client;
use EthicalJobs\Elasticsearch\Indexing\Indexer;
use EthicalJobs\Elasticsearch\Indexing\Logging\Logger;
use Tests\Fixtures\Person;

class DeleteDocumentTest extends \Tests\TestCase
{
    /**
     * @test
     * @group Integration
     */
    public function it_indexes_the_document_and_returns_the_response()
    {
 		$logger = Mockery::mock(Logger::class)->shouldIgnoreMissing();

 		$indexName = 'test-index';

 		$person = factory(Person::class)->create();

 		$params = [
            'index' => $indexName,
            'id'    => $person->getDocumentKey(),
            'type'  => $person->getDocumentType(),
 		];

 		$client = Mockery::mock(Client::class)
 			->shouldReceive('delete')
 			->once()
 			->with($params)
 			->andReturn(['hits' => 1])
 			->getMock();

 		$indexer = new Indexer($client, $logger, $indexName);

 		$response = $indexer->deleteDocument($person);

 		$this->assertEquals(['hits' => 1], $response);
    } 	    
}
