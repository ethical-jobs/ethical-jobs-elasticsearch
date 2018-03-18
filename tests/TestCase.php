<?php

namespace Tests;

use Orchestra\Database\ConsoleServiceProvider;
use EthicalJobs\Elasticsearch\Testing\InteractsWithElasticsearch;
use EthicalJobs\Elasticsearch\ServiceProvider;
use Tests\Fixtures;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
	use InteractsWithElasticsearch;

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