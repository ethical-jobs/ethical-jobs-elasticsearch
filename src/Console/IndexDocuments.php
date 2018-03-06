<?php

namespace EthicalJobs\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use EthicalJobs\Elasticsearch\DocumentIndexer;
use EthicalJobs\Elasticsearch\Index;

/**
 * Indexes indexable entities in Elasticsearch
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class IndexDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ej:es:index {--indexables=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Indexes indexables into Elasticsearch';

    /**
     * Elastic search index service
     *
     * @param \App\Services\Elasticsearch\DocumentIndexer
     */
    private $indexer;

    /**
     * Elastic search index instance
     *
     * @param \EthicalJobs\Elasticsearch\Index 
     */
    private $index;    

    /**
     * Resources to be indexed
     *
     * @param Array
     */
    private $indexables = ['jobs','organisations','invoices'];

    /**
     * Constructor
     *
     * @param \App\Services\Elasticsearch\DocumentIndexer $indexer
     * @param \EthicalJobs\Elasticsearch\Index $index
     * @return void
     */
    public function __construct(Index $index, DocumentIndexer $indexer)
    {
        $this->indexer = $indexer;

        $this->indexer = $index;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $resources = $this->option('resource') ? $this->option('resource') : $this->resources;

        if (Cache::has('ej:es:indexing')) {
            return $this->error('Indexing operation currently running.');
        }

        Cache::put('ej:es:indexing', microtime(true), 20);

        foreach ($resources as $resource) {
            $this->indexResource($resource);
        }

        $this->info(">> Time elapsed ".(microtime(true)-Cache::get('ej:es:indexing'))." seconds");

        Cache::forget('ej:es:indexing');
    }

    /**
     * Indexes resources
     *
     * @return Void
     */
    protected function indexResource(string $resource)
    {
        switch ($resource) {
            case 'jobs':
                return $this->indexJobs();
            case 'organisations':
                return $this->indexOrganisations();
            case 'invoices':
                return $this->indexInvoices();
        }
    }

    /**
     * Returns jobs for indexing
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    protected function indexJobs()
    {
        $this->info('Fetching jobs.');

        $query = Models\Job::where('status', '!=', 'DRAFT')
            ->withTrashed()
            ->with((new Models\Job)->relations);

        $this->index($query);
    }

    /**
     * Returns organisations for indexing
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    protected function indexOrganisations()
    {
        $this->info('Fetching organisations.');

        $query = Models\Organisation::withTrashed()
            ->with((new Models\Organisation)->relations);

        $this->index($query);
    }

    /**
     * Returns invoices for indexing
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    protected function indexInvoices()
    {
        $this->info('Fetching invoices.');

        $query = Models\Invoice::withTrashed()
            ->with((new Models\Invoice)->relations);

        $this->index($query);
    }

    /**
     * Indexes a collection
     *
     * @return void
     */
    protected function index($query)
    {
       $this->info('Indexing.');

        $this->indexer
            ->setLogging(true)
            ->indexCollection($query);
    }

    /**
     * Indexes a collection
     *
     * @return void
     */
    protected function getIndexables($query)
    {
       $this->info('Indexing.');

        $this->indexer
            ->setLogging(true)
            ->indexCollection($query);
    }    
}
