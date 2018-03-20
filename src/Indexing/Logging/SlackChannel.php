<?php

namespace EthicalJobs\Elasticsearch\Indexing\Logging;

use Maknz\Slack\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\App;

/**
 * Slack logging channel
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class SlackChannel
{
    /**
     * Slack client
     *
     * @param \Maknz\Slack\Client
     */
    private $client;                 

    /**
     * Constructor
     *
     * @param \Maknz\Slack\Client $client
     * @return void
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function log(string $message, array $data): void
    {
        $color = App::environment() === 'production' ? '#42f44b' : '';

        $encoded = '```'.$this->encodeArray($data).'```';

        $this->client
            ->attach([
                'color'  => $color,
                'fields' => [
                    ['title' => '', 'value' => $encoded],
                ],
            ])
            ->send($message);
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