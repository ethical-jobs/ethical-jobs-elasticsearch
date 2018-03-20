<?php

namespace EthicalJobs\Elasticsearch\Indexing\Logging;

use Illuminate\Support\Facades\Cache;

/**
 * Stores distributed logging data
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class Store
{
    /**
     * Store key
     *
     * @param string
     */
    const KEY = 'es:idx:log:';                 

    /**
     * Sets a single item into the store
     * 
     * @param string $uuid  [description]
     * @param string $key   [description]
     * @param void
     */
    public static function set(string $uuid, string $key, $value): void
    {
        static::merge($uuid, [
            $key => $value,
        ]);
    }

    /**
     * Merges values into the store
     * 
     * @param  string $uuid [description]
     * @param  array  $data [description]
     * @return void
     */
    public static function merge(string $uuid, array $data): void
    {
        $current = static::all($uuid);

        $merged = array_merge($current, $data);

        Cache::put(static::key($uuid), json_encode($merged), 300);
    }    

    /**
     * Gets a singel value from the store
     * 
     * @param  string $uuid [description]
     * @param  string $key  [description]
     * @return mixed
     */
    public static function get(string $uuid, string $key)
    {
        return array_get(static::all($uuid), $key);
    }

    /**
     * Returns all values from the store
     * 
     * @param  string $uuid [description]
     * @param  array $defaults [description]
     * @return array
     */
    public static function all(string $uuid, array $defaults = []): array
    {
        $raw = Cache::get(static::key($uuid), '');

        $decoded = json_decode($raw, true) ?? [];

        return array_merge($defaults, $decoded);
    }

    /**
     * Increments a value by += {incrementer} and returns it
     * 
     * @param  string $uuid        [description]
     * @param  string $key         [description]
     * @param  int    $incrementer [description]
     * @return int
     */
    public static function increment(string $uuid, string $key, int $incrementer): int
    {
        $current = static::get($uuid, $key);

        $updated = $current + $incrementer;

        static::merge($uuid, [
            $key => $updated,
        ]);

        return $updated;
    }

    /**
     * Builds a unique store key
     * 
     * @param  string $uuid [description]
     * @return string
     */
    protected static function key(string $uuid): string
    {
        return self::KEY.$uuid;
    }    
}