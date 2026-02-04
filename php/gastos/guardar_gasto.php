<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

$datos = isset($_REQUEST['datos']) ? $_REQUEST['datos'] : false;
$gastos = isset($_REQUEST['gastos']) ? $_REQUEST['gastos'] : false;
$anticipos = isset($_REQUEST['anticipos']) ? $_REQUEST['anticipos'] : false;

$configuracion = new Configuracion();

$response = [];




if ($datos && $gastos && $anticipos) {
    $datos = json_decode($datos, true);
    $gastos = json_decode($gastos, true);

    $datos['Codigo'] = GetCodigo();
    $datos['Id_Punto_Dispensacion'] = 3;
    $datos['Anticipos'] = $anticipos;


    $id_gasto = guardarGastoCabecera($datos);

    if ($id_gasto) {
        guardarGastoItems($id_gasto, $gastos);
        guardarActividadGasto($id_gasto, $datos);

        $response['mensaje'] = "Se ha guardado satisfactoriamente el gasto.";
        $response['titulo'] = "Exito!";
        $response['tipo'] = "success";
    } else {
        $response['mensaje'] = "Ha ocurrido un error inesperado al intentar guardar.";
        $response['titulo'] = "Oops!";
        $response['tipo'] = "error";
    }
} else {
    $response['mensaje'] = "Ha ocurrido un error inesperado al intentar guardar.";
    $response['titulo'] = "Oops!";
    $response['tipo'] = "error";
}

echo json_encode($response);

function GetCodigo(){
    global $configuracion;
    $codigo=$configuracion->Consecutivo('Gasto_Punto');
    sleep(2); // Esperar 2 segundo antes de hacer la validación.
    $query = "SELECT Id_Gasto_Punto FROM Gasto_Punto WHERE Codigo = '$codigo'";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $res = $oCon->getData();
    unset($oCon);
    if($res['Id_Gasto_Punto']){
       $codigo=$configuracion->Consecutivo('Gasto_Punto');
    }
    return $codigo;
}

function guardarGastoCabecera($datos) {
    $oItem = new complex('Gasto_Punto','Id_Gasto_Punto');
    foreach ($datos as $index => $value) {
        $oItem->$index = $value;
    }
    $oItem->save();
    $id_gasto = $oItem->getId();
    unset($oItem);

    /* AQUI GENERA QR */
    $qr = generarqr('gasto_punto',$id_gasto,'IMAGENES/QR/');
    $oItem = new complex("Gasto_Punto","Id_Gasto_Punto",$id_gasto);
    $oItem->Codigo_Qr=$qr;
    $oItem->save();
    unset($oItem);
    /* HASTA AQUI GENERA QR */

    return $id_gasto;
}

function guardarGastoItems($id_gasto, $gastos) {
    foreach ($gastos as $i => $gasto) {
        $oItem = new complex('Item_Gasto_Punto', 'Id_Item_Gasto_Punto');
        foreach ($gasto as $index => $value) {
            $oItem->$index = $value;
        }
        $oItem->Id_Gasto_Punto = $id_gasto;
        $oItem->save();
    }
    unset($oItem);

    return;
}

function guardarActividadGasto($id_gasto, $datos) {
    $oItem = new complex('Actividad_Gasto_Punto','Id_Actividad_Gasto_Punto');
    $oItem->Id_Gasto_Punto = $id_gasto;
    $oItem->Identificacion_Funcionario = $datos['Identificacion_Funcionario'];
    $oItem->Detalles = "Se ha generado el documento de Gasto: $datos[Codigo]";
    $oItem->Estado = "Creacion";
    $oItem->save();

    return;
}
?>