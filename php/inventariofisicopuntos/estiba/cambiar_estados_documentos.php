<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');


$estado = isset (  $_REQUEST['estado'] ) ? $_REQUEST['estado'] : '';
$idDocumento = isset (  $_REQUEST['idDocumento'] ) ? $_REQUEST['idDocumento'] : '';

if ($estado && $idDocumento) {
    # code...

    $oItem = new complex('Doc_Inventario_Fisico_Punto','Id_Doc_Inventario_Fisico_Punto',$idDocumento);
    $oItem->getData();
    $oItem->Estado = $estado;
    $oItem->save();

    $response['tipo']= 'success';
    $response['title']= 'Cambio de estado exitoso';
    $response['mensaje']= 'Documento actualizado con éxito';
    
    echo json_encode($response);
}



