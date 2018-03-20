<?php

namespace EthicalJobs\Elasticsearch\Indexing;

use Maknz\Slack\Client;
use Illuminate\Support\Facades\App;
use Symfony\Component\Console\Output\ConsoleOutput;
use EthicalJobs\Elasticsearch\Utilities;

/**
 * Logs index queries
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class Logger
{
    /**
     * Slock client
     *
     * @param \Maknz\Slack\Client
     */
    private $slack;

    /**
     * Console output
     *
     * @param \Symfony\Component\Console\Output\ConsoleOutput
     */
    private $console;  

    /**
     * Index query instance
     *
     * @var IndexQuery
     */
    protected $indexQuery;        

    /**
     * Start time in micro seconds
     *
     * @param int
     */
    private $startTime;  

    /**
     * Current chunk count
     *
     * @param int
     */
    private $count;                    

    /**
     * Constructor
     *
     * @param \Maknz\Slack\Client $slack
     * @param \Symfony\Component\Console\Output\ConsoleOutput $console
     * @return void
     */
    public function __construct(Client $slack, ConsoleOutput $console)
    {
        $this->slack = $slack;

        $this->console = $console;
    }

    /**
     * Starts logging an index query
     *
     * @param EthicalJobs\Elasticsearch\Indexing\IndexQuery $indexQuery
     * @return void
     */
    public function start(IndexQuery $indexQuery): void
    {
        $this->startTime = microtime(true);

        $this->indexQuery = $indexQuery;

        $this->count = 0;

        $this->log('Indexing started');
    }

    /**
     * Progresses logging of an index query
     *
     * @return void
     */
    public function progress(): void
    {
        $this->count++;

        if ($this->count % 1000 == 0) { // log every thousand
            $this->log('Indexing progressed');
        }
    }    

    /**
     * Completes logging an index query
     *
     * @return void
     */
    public function finish(): void
    {
        $this->log('Indexing completed');
    }    

    /**
     * Sends a log
     *
     * @param string $message
     * @param array $data
     * @return void
     */
    public function log(string $message, $data = []): void
    {
        if (empty($data)) {
            $data = $this->getStats();
        }

        $this->slack($message, $data);

        if (App::runningUnitTests() === false) {
            $this->console($message, $data);
        }
    }    

    /**
     * Logs a slack message
     *
     * @param string $message
     * @param array $data
     * @return void
     */
    protected function slack(string $message, array $data = []): void
    {
        $this->slack
            ->attach([
                'fallback' => '', 'text' => '', 'color' => 'green',
                'fields'    => [
                    [
                        'title' => '',
                        'value' => '```'.$this->encodeArray($data).'```',
                    ],
                ],
            ])
            ->send($message);
    }    

    /**
     * Logs a console message
     *
     * @param string $message
     * @param array $data
     * @return void
     */
    protected function console(string $message, array $data = []): void
    {
        $this->console->writeln("<info>$message</info>");

        $this->console->writeln($this->encodeArray($data).PHP_EOL);
    }        

    /**
     * Returns stats on the IndexQuery
     *
     * @return array
     */
    protected function getStats(): array
    {
        $time = ceil(microtime(true) - $this->startTime);

        return array_filter([
            'indexable' => get_class($this->indexQuery->indexable),    
            'progress'  => $this->count.'/'.$this->indexQuery->getParam('numberOfChunks').' chunks',
            'duration'  => $time > 60 ? ceil($time / 60).' minutes' : $time.' seconds',
            'process'   => [
                'chunkSize'     => $this->indexQuery->getParam('chunkSize'),
                'proccessNum'   => $this->indexQuery->getParam('currentProcess'),
                'processId'     => getmypid(),
                'hostName'      => gethostname(),
                'environment'   => app()->environment(),
            ],
        ]);
    }

    /**
     * Converts an array to printable json
     *
     * @param array $data
     * @return string
     */
    protected function encodeArray(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }    
}