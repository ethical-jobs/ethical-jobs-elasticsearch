<?php

namespace EthicalJobs\Elasticsearch\Indexing\Logging;

/**
 * Log output channel
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

interface Channel
{                 
    /**
     * Sends a log to the channel
     *
     * @param string $message
     * @param array $data
     * @return void
     */
    public function log(string $message, array $data): void;   
}