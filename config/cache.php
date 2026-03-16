<?php

// Simple file-based cache for expensive DB aggregations.
// The GROUP BY queries that power charts run on every page load — as the events
// table grows this gets expensive. 60 seconds is fine for analytics data
// since nobody needs real-time accuracy on a chart showing daily trends.

define('CACHE_DIR', __DIR__ . '/../cache/');
define('CACHE_TTL', 60); // seconds

function cacheGet(string $key): mixed {
    $file = CACHE_DIR . md5($key) . '.cache';
    if (!file_exists($file)) return null;
    if (time() - filemtime($file) > CACHE_TTL) {
        unlink($file);
        return null;
    }
    return json_decode(file_get_contents($file), true);
}

function cacheSet(string $key, mixed $value): void {
    $file = CACHE_DIR . md5($key) . '.cache';
    file_put_contents($file, json_encode($value), LOCK_EX);
}

function cached(string $key, callable $fn): mixed {
    $val = cacheGet($key);
    if ($val !== null) return $val;
    $val = $fn();
    cacheSet($key, $val);
    return $val;
}
