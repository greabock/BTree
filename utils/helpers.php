<?php

function data_get($target, $key, $default = null)
{
    if (is_null($key)) return $target;
    foreach (explode('.', $key) as $segment) {
        $target = match (true) {
            is_array($target) => array_key_exists($segment, $target) ? $target[$segment] : value($default),
            is_object($target) => $target->{$segment} ?? value($default),
            default => value($default),
        };
    }

    return $target;
}

function value($value)
{
    return $value instanceof Closure ? $value() : $value;
}

function human_readable_bytes(int $bytes): string
{
    $size = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.2f %s", $bytes / (1024 ** $factor), $size[$factor]);

}

function runtime($now, $start): float|int
{
    return ($now['ru_utime.tv_sec'] * 1000 + intval($now['ru_utime.tv_usec'] / 1000))
        - ($start['ru_utime.tv_sec'] * 1000 + intval($start['ru_utime.tv_usec'] / 1000));
}