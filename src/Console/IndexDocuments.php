<?php

namespace EthicalJobs\Elasticsearch\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use EthicalJobs\Elasticsearch\Indexing\IndexQuery;
use EthicalJobs\Elasticsearch\Indexing\Indexer;
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
    protected $signature = 'ej:es:index
                            {--chunk-size=250 : How many documents to index at once}
                            {--queue : Process the indexing of each indexable in a seperate process}
                            {--indexables=* : An array of indexables to index (none == all)}';

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
     * @param \App\Services\Elasticsearch\Indexing\Indexer
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
     * @param \App\Services\Elasticsearch\Indexing\Indexer $indexer
     * @param \EthicalJobs\Elasticsearch\Index $index
     * @return void
     */
    public function __construct(Index $index, Indexer $indexer)
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
        foreach ($this->getIndexables() as $indexable) {
            $this->index($indexable);
        }
    }

    /**
     * Indexes an indexable resource
     *
     * @param  string $indexable
     * @return void
     */
    protected function index(string $indexable): void
    {
        if (Cache::get('es:es:indexing')) {
            throw new \Exception('Indexing operation already running.');
        }

        Cache::put('es:es:indexing', true, 60);

        $indexQuery = new IndexQuery(new $indexable, $this->option('chunk-size'));

        if ($this->option('queue')) {
            $this->indexer->queueQuery($indexQuery);
        } else {
            $this->indexer->indexQuery($indexQuery);
        }

        Cache::forget('es:es:indexing');
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
