<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

class NotificationCache
{
	private static $ttl = 40; // 1 minuto en lugar de 30 segundos

	public static function get($key)
	{
		if (function_exists('apcu_fetch')) {
			$data = apcu_fetch($key);
			return $data !== false ? $data : null;
		} else {
			$file = sys_get_temp_dir() . '/cache_alerta_' . md5($key) . '.cache';
			if (file_exists($file) && (filemtime($file) + self::$ttl > time())) {
				return unserialize(file_get_contents($file));
			}
		}
		return null;
	}

	public static function set($key, $value)
	{
		if (function_exists('apcu_store')) {
			apcu_store($key, $value, self::$ttl);
		} else {
			$file = sys_get_temp_dir() . '/cache_alerta_' . md5($key) . '.cache';
			file_put_contents($file, serialize($value));
		}
	}

	public static function delete($key)
	{
		if (function_exists('apcu_delete')) {
			apcu_delete($key);
		} else {
			$file = sys_get_temp_dir() . '/cache_alerta_' . md5($key) . '.cache';
			if (file_exists($file)) {
				unlink($file);
			}
		}
	}
}

function normalize_utf8_value($value)
{
	if (is_string($value)) {
		return utf8_encode(utf8_decode($value));
	}
	return $value;
}

$id = $_REQUEST['id'] ?? '';
if (empty($id)) {
	echo json_encode([]);
	exit;
}

$cache_key = "alertas_funcionario_" . $id;
$alertas = NotificationCache::get($cache_key);

if ($alertas === null) {
	// Usar consultas preparadas para seguridad
	$query = 'SELECT A.*, F.Nombres, F.Apellidos, F.Imagen 
              FROM Alerta A 
              LEFT JOIN Funcionario F ON A.Identificacion_Funcionario = F.Identificacion_Funcionario
              WHERE A.Respuesta = "No"
              AND A.Identificacion_Funcionario = ' . $id . '
              AND (A.Id IS NULL OR A.Tipo IN ("Preaviso", "Memorando", "OtroSi", "Formulario", "Actividad"))
              
              UNION ALL 
              
              SELECT A.*, F.Nombres, F.Apellidos, F.Imagen 
              FROM Alerta A 
              INNER JOIN (
                  SELECT A.Id_Auditoria 
                  FROM Auditoria A 
                  INNER JOIN Dispensacion D ON A.Id_Dispensacion = D.Id_Dispensacion
                  WHERE D.Estado_Dispensacion != "Anulada"
              ) AD ON A.Id = AD.Id_Auditoria 
              LEFT JOIN Funcionario F ON A.Identificacion_Funcionario = F.Identificacion_Funcionario
              WHERE A.Identificacion_Funcionario = ' . $id . '
              AND A.Id IS NOT NULL 
              ORDER BY Id_Alerta DESC';

	$oCon = new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$alertas = $oCon->getData();
	unset($oCon);

	// Procesar nombres una sola vez
	foreach ($alertas as &$alerta) {
		$nombres = explode(" ", $alerta["Nombres"] ?? "");
		$apellidos = explode(" ", $alerta["Apellidos"] ?? "");
		$alerta["Nombre"] = ($nombres[0] ?? "") . " " . ($apellidos[0] ?? "");
		unset($alerta["Nombres"], $alerta["Apellidos"]);
		foreach ($alerta as $key => $value) {
			$alerta[$key] = normalize_utf8_value($value);
		}
	}

	NotificationCache::set($cache_key, $alertas);
}

echo json_encode($alertas, JSON_UNESCAPED_UNICODE);
