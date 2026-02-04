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
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : false );
$resultado = [];

if ($id) {
    $id = json_decode($id,true);

    $oItem = new complex('Proceso_Disciplinario','Id_Proceso_Disciplinario',$id);
    $oItem->Estado = "Cerrado";
    $oItem->save();
    unset($oItem);

    $resultado['codigo'] = "success";
    $resultado['titulo'] = "Exito!";
    $resultado['mensaje'] = "El proceso se ha cerrado exitosamente.";
} else {
    $resultado['codigo'] = "error";
    $resultado['titulo'] = "Oops!";
    $resultado['mensaje'] = "Ha ocurrido un error inesperado.";
}

echo json_encode($resultado);
?> 