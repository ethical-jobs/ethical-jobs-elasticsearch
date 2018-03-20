<?php

namespace EthicalJobs\Elasticsearch\Indexing\Logging;

use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Support\Facades\App;

/**
 * Console logging channel
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class ConsoleChannel
{
    /**
     * Console client
     *
     * @param \Symfony\Component\Console\Output\ConsoleOutput 
     */
    private $console;                 

    /**
     * Constructor
     *
     * @param \Symfony\Component\Console\Output\ConsoleOutput $console
     * @return void
     */
    public function __construct(ConsoleOutput $console)
    {
        $this->console = $console;
    }

    /**
     * {@inheritdoc}
     */
    public function log(string $message, array $data): void
    {
        if (App::runningUnitTests() === false) {
            $this->console->writeln("<info>$message</info>");
            $this->console->writeln($this->encodeArray($data).PHP_EOL);
        }
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