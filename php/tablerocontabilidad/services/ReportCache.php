<?php

class ReportCache
{
    private $dir;
    private $ttlSeconds;

    public function __construct($dir, $ttlSeconds)
    {
        $this->dir = $dir;
        $this->ttlSeconds = (int)$ttlSeconds;
        $this->ensureDir();
    }

    public function keyForQuery($prefix, $query)
    {
        return $prefix . '_' . hash('sha256', $query);
    }

    public function get($key, $ttlOverride = null)
    {
        $path = $this->pathForKey($key);
        if (!is_file($path)) {
            return null;
        }
        $ttl = $ttlOverride === null ? $this->ttlSeconds : (int)$ttlOverride;
        if ($ttl > 0 && (filemtime($path) + $ttl) < time()) {
            @unlink($path);
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }
        $data = @unserialize($raw);
        return $data === false ? null : $data;
    }

    public function set($key, $data)
    {
        $path = $this->pathForKey($key);
        $payload = serialize($data);
        @file_put_contents($path, $payload, LOCK_EX);
    }

    private function ensureDir()
    {
        if (is_dir($this->dir)) {
            return;
        }
        @mkdir($this->dir, 0777, true);
    }

    private function pathForKey($key)
    {
        return rtrim($this->dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $key . '.cache';
    }
}
