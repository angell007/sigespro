<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');


$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');
$estado = isset($_REQUEST['estado']) ? $_REQUEST['estado'] : '';
$observaciones = isset($_REQUEST['observaciones']) ? $_REQUEST['observaciones'] : '';

$oItem = new complex('Dispensacion_Mipres', "Id_Dispensacion_Mipres", $id);
$oItem->Estado_Callcenter = $estado;
$oItem->Observaciones_Callcenter =  $observaciones;
if ($estado == 'Contactado') {
    $oItem->Fecha_Contacto = date('Y-m-d H:i:s');
}
$oItem->save();
unset($oItem);

$resultado['mensaje'] = "Se ha Guardado Correctamente!";
$resultado['tipo'] = "success";

echo json_encode($resultado);
