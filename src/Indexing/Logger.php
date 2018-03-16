<?php

namespace EthicalJobs\Elasticsearch\Indexing;

use Maknz\Slack\Client;
use Illuminate\Support\Facades\App;
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
        $this->client
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
        dump($message);
        dump($data);
        dump("\n");
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

        if (Utilities::isAssocArray($data)) {
            foreach ($data as $key => $value) {
                $fields[] = [
                    'title' => $key,
                    'value' => $value,
                ];
            }
        } else {
            $fields[] = [
                'title' => 'json',
                'value' => json_encode($data, JSON_PRETTY_PRINT),
            ];
        }

        return $fields;
    }
}