<?php

namespace EthicalJobs\Elasticsearch\Indexing\Logging;

use Maknz\Slack\Client;
use Symfony\Component\Console\Output\ConsoleOutput;
use EthicalJobs\Elasticsearch\Indexing\IndexQuery;

/**
 * Logs index queries
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class Logger
{
    /**
     * Log output channels
     *
     * @param array
     */
    private $channels = [];     

    /**
     * Cache store key
     *
     * @param const
     */
    const STORE_KEY = 'es:idx:log:';                

    /**
     * Constructor
     *
     * @param array $channels
     * @return void
     */
    public function __construct(array $channels)
    {
        $this->channels = $channels;
    }

    /**
     * Starts logging an index query
     *
     * @param EthicalJobs\Elasticsearch\Indexing\IndexQuery $indexQuery
     * @return $this
     */
    public function start(IndexQuery $indexQuery): Logger
    {
        Store::merge($indexQuery->uuid, [
            'duration'              => microtime(true),
            'documents:indexed'     => 0,
            'documents:total'       => $indexQuery->documentCount(),
            'processes:completed'   => 0,
            'processes:total'       => $indexQuery->processCount(),
        ]);

        $this->logIndexQuery('Indexing started', $indexQuery);

        return $this;
    }

    /**
     * Joins a logging session of an index query
     *
     * @param EthicalJobs\Elasticsearch\Indexing\IndexQuery $indexQuery
     * @return $this
     */
    public function join(IndexQuery $indexQuery): Logger
    {
        $processId = gethostname().'::'.getmypid();

        $current = Store::get($indexQuery->uuid, 'processes:ids');

        $store = empty($current) ? $processId : $current.','.$processId;

        Store::merge($indexQuery->uuid, [
            'processes:ids' => $store
        ]);

        return $this;
    }        

    /**
     * Progresses logging of an index query
     *
     * @param EthicalJobs\Elasticsearch\Indexing\IndexQuery $indexQuery
     * @var int $incrementer
     * @return $this
     */
    public function progress(IndexQuery $indexQuery, int $incrementer): Logger
    {
        Store::increment($indexQuery->uuid, 'documents:indexed', $incrementer);

        $this->logIndexQuery('Indexing documents', $indexQuery);

        return $this;
    }    

    /**
     * Completes logging an index query
     *
     * @param EthicalJobs\Elasticsearch\Indexing\IndexQuery $indexQuery
     * @return $this
     */
    public function complete(IndexQuery $indexQuery): Logger
    {
        Store::increment($indexQuery->uuid, 'processes:completed', 1);

        if (Store::get($indexQuery->uuid,'processes:completed') === Store::get($indexQuery->uuid,'processes:total')) {
            $this->logIndexQuery('Indexing completed', $indexQuery);
        }

        return $this;
    }    

    /**
     * Sends a log
     *
     * @param string $message
     * @param array $data
     * @return void
     */
    public function log(string $message, array $data): void
    {
        foreach ($this->channels as $channel) {
            $channel->log($message, $data);
        }
    }     

    /**
     * Logs an index query
     *
     * @param string $message
     * @var \EthicalJobs\Elasticsearch\Indexing\IndexQuery $indexQuery
     * @return void
     */
    protected function logIndexQuery(string $message, IndexQuery $indexQuery): void
    {
        $items = Store::all($indexQuery->uuid, [
            'duration'              => microtime(true),
            'documents:indexed'     => 0,
            'documents:total'       => $indexQuery->documentCount(),
            'processes:completed'   => 0,
            'processes:total'       => $indexQuery->processCount(),
            'processes:ids'         => '',
        ]);

        $seconds = ceil(microtime(true) - $items['duration']);
        $minutes = $seconds > 60 ? str_pad(ceil($seconds / 60), 2, '0', STR_PAD_LEFT) : 00;
        $time = $minutes.'m '.$seconds.'s';

        $percentage = ($items['documents:indexed'] / $items['documents:total']) * 100;
        $progress = $percentage.'% ('.$items['documents:indexed'].'/'.$items['documents:total'].') indexed';

        $percentage = ($items['processes:completed'] / $items['processes:total']) * 100;
        $processes = $items['processes:completed'].'/'.$items['processes:total'].' completed';

        $processIds = empty($items['processes:ids']) ? [] : explode(',', $items['processes:ids']);

        $this->log($message, [
            'environment'   => env('EJ_ENV') ?? app()->environment(),
            'uuid'          => $indexQuery->uuid,
            'indexable'     => get_class($indexQuery->indexable),
            'duration'      => $time,
            'indexing'      => $progress,
            'processes'     => $processes,
            'processIds'    => $processIds,
        ]);
    }
}