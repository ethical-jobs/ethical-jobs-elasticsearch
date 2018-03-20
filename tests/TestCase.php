<?php

namespace Tests;

use Orchestra\Database\ConsoleServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use EthicalJobs\Elasticsearch\Testing\InteractsWithElasticsearch;
use EthicalJobs\Foundation\Testing\ExtendsAssertions;
use EthicalJobs\Elasticsearch\ServiceProvider;
use Tests\Fixtures;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
	use InteractsWithElasticsearch, ExtendsAssertions, RefreshDatabase;

	/**
	 * Setup the test environment.
     *
     * @return void
     */
	protected function setUp(): void
	{
	    parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');        

	    $this->withFactories(__DIR__.'/../database/factories');

	    $this->withoutElasticsearchObserver();
	}	

	/**
	 * Define environment setup.
	 *
	 * @param  \Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function getEnvironmentSetUp($app)
	{
	    $app['config']->set('elasticsearch.index', 'testing');

	    $app['config']->set('elasticsearch.indexables', [
	        Fixtures\Person::class,
	        Fixtures\Family::class,
	        Fixtures\Vehicle::class,
	    ]);
	}	

	/**
	 * Inject package service provider
	 * 
	 * @param  Application $app
	 * @return Array
	 */
	protected function getPackageProviders($app)
	{
	    return [
	    	ServiceProvider::class,
	    	ConsoleServiceProvider::class,
	   	];
	}

	/**
	 * Inject package facade aliases
	 * 
	 * @param  Application $app
	 * @return Array
	 */
	protected function getPackageAliases($app)
	{
	    return [];
	}	
}