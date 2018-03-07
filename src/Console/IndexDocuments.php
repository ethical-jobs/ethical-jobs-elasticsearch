<?php

namespace EthicalJobs\Elasticsearch\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use EthicalJobs\Elasticsearch\DocumentIndexer;
use EthicalJobs\Elasticsearch\Utilities;
use EthicalJobs\Elasticsearch\Indexable;
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
     * Elastic search index instance
     *
     * @param \EthicalJobs\Elasticsearch\Index 
     */
    private $index;    

    /**
     * Elastic search index service
     *
     * @param \App\Services\Elasticsearch\DocumentIndexer
     */
    private $indexer;

    /**
     * Resources to be indexed
     *
     * @param Array
     */
    private $indexables = [];

    /**
     * Constructor
     *
     * @param \App\Services\Elasticsearch\DocumentIndexer $indexer
     * @param \EthicalJobs\Elasticsearch\Index $index
     * @return void
     */
    public function __construct(Index $index, DocumentIndexer $indexer)
    {
        parent::__construct();

        $this->indexer = $indexer;

        $this->index = $index;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (Cache::has('ej:es:indexing')) {
            return $this->error('Indexing operation currently running.');
        }

        Cache::put('ej:es:indexing', microtime(true), 20);

        foreach ($this->getIndexables() as $indexable) {
            $this->index($indexable);
        }

        $this->info(">> Time elapsed ".(microtime(true)-Cache::get('ej:es:indexing'))." seconds");

        Cache::forget('ej:es:indexing');
    }

    /**
     * Indexes an indexable resource
     *
     * @param  string $indexable
     * @return void
     */
    protected function index(string $indexable): void
    {
        $this->info('Indexing: '.$indexable);

        $query = $this->getIndexableQuery($indexable);

        $this->indexer
            ->setLogging(true)
            ->indexCollection($query);
    }

    /**
     * Returns indexable query
     *
     * @param  string $indexable
     * @return Illuminate\Database\Query\Builder
     */
    protected function getIndexableQuery(string $indexable)
    {
        $instance = new $indexable;

        $relations = $instance->getDocumentRelations();

        $query = $instance->with($relations);

        if (Utilities::isSoftDeletable($indexable)) {
            $query = $instance->withTrashed();
        }

        return $query;    
    }       

    /**
     * Returns indexable entities
     *
     * @return array
     */
    protected function getIndexables(): array
    {
        if ($option = $this->option('indexables')) {
            return is_array($option) ? $option : [$option];
        }
        
        return $this->index->getSettings()->getIndexables();       
    }    
}
