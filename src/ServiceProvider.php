<?php

namespace EthicalJobs\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use EthicalJobs\Elasticsearch\Index;
use EthicalJobs\Elasticsearch\Console;
use EthicalJobs\Elasticsearch\IndexSettings;

/**
 * Elasticsearch service provider
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */
class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Config file path
     *
     * @var string
     */
    protected $configPath = __DIR__.'/../config/elasticsearch.php';


    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([$this->configPath => config_path('elasticsearch.php')]);

        $this->configureObservers();

        $this->registerCommands();
    }

     /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom($this->configPath, 'elasticsearch');        

        $this->registerConnectionSingleton();

        $this->registerIndexSingleton();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            Client::class,
            Index::class,
        ];
    }    

    /**
     * Register connection instance
     *
     * @return void
     */
    protected function registerConnectionSingleton(): void
    {
        $this->app->singleton(Client::class, function () {

            $config = array_merge([
                'logPath'   => storage_path().'/logs/elasticsearch-'.php_sapi_name().'.log',
            ], config('elasticsearch'));

            $connection = array_get($config, 'connections.'.$config['defaultConnection']);

            $client = ClientBuilder::create()->setHosts($connection['hosts']);

            if ($connection['logging']) {
                $logger = ClientBuilder::defaultLogger($connection['logPath']);
                $client->setLogger($logger);
            }

            return $client->build();
        });
    }

     /**
     * Register index instance
     *
     * @return void
     */
    protected function registerIndexSingleton(): void
    {
        $this->app->singleton(Index::class, function ($app) {

            $settings = new IndexSettings(
                config('elasticsearch.index'),
                config('elasticsearch.settings'),
                config('elasticsearch.mappings')
            );

            $settings->setIndexables(config('elasticsearch.indexables'));

            return new Index($app[Client::class], $settings);
        });
    }    

    /**
     * Configure indexable observers
     *
     * @return Void
     */
    protected function configureObservers(): void
    {
        $indexables = resolve(Index::class)
            ->getSettings()
            ->getIndexables();

        foreach ($indexables as $indexable) {
            $indexable::observe(Observer::class);
        }
    }    

    /**
     * Register console commands
     *
     * @return Void
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\CreateIndex::class,
                Console\DeleteIndex::class,
                Console\FlushIndex::class,
                Console\IndexDocuments::class,
            ]);
        }
    }      
}