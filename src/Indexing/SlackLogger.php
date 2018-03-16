<?php

namespace EthicalJobs\Elasticsearch\Indexing;

use Maknz\Slack\Client;

/**
 * Logs indexing operations into slack
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class SlackLogger
{
    /**
     * Slock client
     *
     * @param \Maknz\Slack\Client
     */
    private $client;

    /**
     * Constructor
     *
     * @param \Maknz\Slack\Client $client
     * @param array $settings
     * @return void
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
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
        $this->client
            ->attach([
                'fallback'  => 'Indexing log',
                'text'      => 'Indexing log',
                'color'     => $color,
                'fields'    => $data,
            ])
            ->send($message);        
    }  
}