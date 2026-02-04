<?php

/**
 * Clase de cache reutilizable para endpoints PHP
 * Basada en ReportCache pero adaptada para uso general
 */
class Cache
{
  private $dir;
  private $ttlSeconds;
  private static $defaultDir = null;

  public function __construct($dir = null, $ttlSeconds = 300)
  {
    if ($dir === null) {
      $dir = self::getDefaultCacheDir();
    }
    $this->dir = $dir;
    $this->ttlSeconds = (int)$ttlSeconds;
    $this->ensureDir();
  }

  /**
   * Obtiene el directorio de cache por defecto
   */
  private static function getDefaultCacheDir()
  {
    if (self::$defaultDir === null) {
      $baseDir = __DIR__ . '/../storage/cache';
      if (!is_dir($baseDir)) {
        @mkdir($baseDir, 0777, true);
      }
      self::$defaultDir = $baseDir;
    }
    return self::$defaultDir;
  }

  /**
   * Genera una clave de cache basada en un prefijo y parámetros
   */
  public function keyForParams($prefix, $params = [])
  {
    // Ordena los parámetros para que el mismo conjunto genere la misma clave
    ksort($params);
    $paramsString = serialize($params);
    return $prefix . '_' . hash('sha256', $paramsString);
  }

  /**
   * Genera una clave de cache basada en una query SQL
   */
  public function keyForQuery($prefix, $query)
  {
    return $prefix . '_' . hash('sha256', $query);
  }

  /**
   * Obtiene datos del cache
   * @param string $key Clave del cache
   * @param int|null $ttlOverride TTL personalizado (en segundos), null usa el TTL por defecto
   * @return mixed|null Datos cacheados o null si no existe o expiró
   */
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

  /**
   * Guarda datos en el cache
   * @param string $key Clave del cache
   * @param mixed $data Datos a guardar
   */
  public function set($key, $data)
  {
    $path = $this->pathForKey($key);
    $payload = serialize($data);
    @file_put_contents($path, $payload, LOCK_EX);
  }

  /**
   * Elimina una entrada del cache
   * @param string $key Clave del cache
   */
  public function delete($key)
  {
    $path = $this->pathForKey($key);
    if (is_file($path)) {
      @unlink($path);
    }
  }

  /**
   * Limpia todo el cache del directorio
   */
  public function clear()
  {
    $files = glob($this->dir . '/*.cache');
    foreach ($files as $file) {
      if (is_file($file)) {
        @unlink($file);
      }
    }
  }

  /**
   * Limpia cache expirado
   */
  public function clearExpired()
  {
    $files = glob($this->dir . '/*.cache');
    $now = time();
    foreach ($files as $file) {
      if (is_file($file)) {
        $age = $now - filemtime($file);
        if ($age > $this->ttlSeconds) {
          @unlink($file);
        }
      }
    }
  }

  /**
   * Asegura que el directorio de cache existe
   */
  private function ensureDir()
  {
    if (is_dir($this->dir)) {
      return;
    }
    @mkdir($this->dir, 0777, true);
  }

  /**
   * Obtiene la ruta completa del archivo de cache para una clave
   */
  private function pathForKey($key)
  {
    // Sanitiza la clave para evitar problemas con nombres de archivo
    $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
    return rtrim($this->dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeKey . '.cache';
  }
}
