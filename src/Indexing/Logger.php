<?php

namespace EthicalJobs\Elasticsearch\Indexing;

use Maknz\Slack\Client;
use Illuminate\Support\Facades\App;
use Symfony\Component\Console\Output\ConsoleOutput;
use EthicalJobs\Elasticsearch\Utilities;

/**
 * Logs indexing operations
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
     * Logs a slack message
     *
     * @param string $message
     * @param array $data
     * @param string $color
     * @return void
     */
    public function message(string $message, array $data = [], string $color = '#86f442'): void
    {
        $this->slack(...func_get_args());

        if (App::runningUnitTests() === false) {
            $this->console(...func_get_args());
        }
    }

    /**
     * Logs a slack message
     *
     * @param string $message
     * @param array $data
     * @param string $color
     * @return void
     */
    protected function slack(string $message, array $data = [], string $color = '#86f442'): void
    {
        $this->slack
            ->attach([
                'fallback'  => '',
                'text'      => '',
                'color'     => $color,
                'fields'    => $this->toFields($data),
            ])
            ->send($message);
    }    

    /**
     * Logs a console message
     *
     * @param string $message
     * @param array $data
     * @param string $color
     * @return void
     */
    protected function console(string $message, array $data = [], string $color = '#86f442'): void
    {
        $this->console->writeln("<info>$message</info>");

        $this->console->writeln($this->encodeArray($data).PHP_EOL);
    }        

    /**
     * Converts a keyed array to attachment fields
     *
     * @param array $data
     * @return array
     */
    protected function toFields(array $data): array
    {
        $fields = [];

        $fields[] = [
            'title' => '',
            'value' => $this->encodeArray($data),
        ];

        return $fields;
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