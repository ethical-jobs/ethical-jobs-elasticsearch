<?php

namespace EthicalJobs\Elasticsearch\Exceptions;

use Elasticsearch\Common\Exceptions\ElasticsearchException;

/**
 * IndexingException
 *
 * @category Elasticsearch
 * @author   Andrew McLagan <andrew@ethicaljobs.com.au>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache2
 * @link     http://elastic.co
 */
class IndexingException extends \Exception implements ElasticsearchException
{
}
