<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');

$datos = isset($_REQUEST['datos']) ? $_REQUEST['datos'] : false;
$resultado = [];

if ($datos) {
    $datos = json_decode($datos, true);
    $idBorrador = false;
    $oItem = null;

    if (isset($datos['Id_Borrador_Contabilidad']) && $datos['Id_Borrador_Contabilidad'] != '') {
        $oItem = new complex('Borrador_Contabilidad','Id_Borrador_Contabilidad',$datos['Id_Borrador_Contabilidad']);
    } else {
        $oItem = new complex('Borrador_Contabilidad','Id_Borrador_Contabilidad');
    }

    foreach ($datos as $index => $value) {
        if ($index != 'Id_Borrador_Contabilidad') {
            $valor = $index == 'Datos' ? json_encode(limpiarStr($value)) : $value;
            $oItem->$index = $valor;
        }
    }
    $oItem->save();

    $idBorrador = (isset($datos['Id_Borrador_Contabilidad']) && $datos['Id_Borrador_Contabilidad'] != '') ? $datos['Id_Borrador_Contabilidad'] : $oItem->getId();
    unset($oItem);

    if ($idBorrador) {
        $resultado['Id_Borrador'] = $idBorrador;
        $resultado['status'] = 202;
    } else {
        $resultado['status'] = 500;
    }
} else {
    $resultado['status'] = 500;
}

echo json_encode($resultado);

function limpiarStr($str) {
    $search = ["\t","\r","\n"];
    $replace = [" "," "," "];

    return str_replace($search,$replace,$str);
}

          
?>