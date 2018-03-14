<?php

namespace EthicalJobs\Elasticsearch;

use Illuminate\Support\Facades\Event;
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
        $this->publishes([
            $this->configPath => config_path('elasticsearch.php')
        ], 'config');

        $this->bootObservers();

        $this->bootCommands();
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

        $this->registerDocumentIndexer();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            Index::class,
            Client::class,
            DocumentIndexer::class,
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
     * Register document indexer
     *
     * @return void
     */
    protected function registerDocumentIndexer(): void
    {
        $this->app->bind(DocumentIndexer::class, function ($app) {
            return new DocumentIndexer(
                $app[Client::class],
                $app[Index::class],
                config('elasticsearch.indexing.chunk-size', null)
            );
        });
    }       

    /**
     * Configure indexable observers
     *
     * @return Void
     */
    protected function bootObservers(): void
    {
        $indexables = resolve(Index::class)
            ->getSettings()
            ->getIndexables();

        foreach ($indexables as $indexable) {
            $indexable::observe(IndexableObserver::class);
        }
    }        

    /**
     * Register console commands
     *
     * @return Void
     */
    protected function bootCommands(): void
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