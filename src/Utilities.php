<?php

namespace EthicalJobs\Elasticsearch;

/**
 * Elasticsearch utility class
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class Utilities
{
    /**
     * Validates an Elasicsearch API response
     *
     * @param array $response
     * @return bool
     */
    public static function isResponseValid(array $response): bool
    {
        if (isset($response['errors']) && $response['errors']) {
            return false;
        }

        return true;
    }

    /**
     * Returns response errors
     *
     * @param array $response
     * @return array
     */
    public static function getResponseErrors(array $response): array
    {
        if (! static::isResponseValid($response)) {
            return $response['items'];
        }

        return [];
    }   

    /**
     * Determine if model is soft deletable
     *
     * @param mixed $entity
     * @return bool
     */
    public static function isSoftDeletable($entity): bool
    {
        return in_array(
            \Illuminate\Database\Eloquent\SoftDeletes::class, 
            class_uses($entity)
        );
    }    
}