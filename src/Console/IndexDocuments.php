<?php

namespace EthicalJobs\Elasticsearch\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use EthicalJobs\Elasticsearch\Indexing\IndexQuery;
use EthicalJobs\Elasticsearch\Indexing\Indexer;
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
    protected $signature = 'ej:es:index
                            {--chunk-size=250 : How many documents to index at once}
                            {--processes=2 : How many queue processes per indexable}
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
        $indexQuery = new IndexQuery(new $indexable);

        $chunkSize = $this->option('chunk-size');

        if ($processes = $this->option('processes')) {

            $indexQuery->split($processes, $chunkSize);
        } else {

            $indexQuery->makeChunks($chunkSize);

            $this->indexer->indexQuery($indexQuery);
        }
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
