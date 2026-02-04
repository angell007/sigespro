<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../helper/response.php');



$estado = isset (  $_REQUEST['estado'] ) ? $_REQUEST['estado'] : '';
$idDocumento = isset (  $_REQUEST['idDocumento'] ) ? $_REQUEST['idDocumento'] : '';
$Tipo = isset (  $_REQUEST['Tipo'] ) ? $_REQUEST['Tipo'] : '';

if ($estado && $idDocumento) {

    if ($Tipo != 'Auditoria') {
        $oItem = new complex('Doc_Inventario_Fisico','Id_Doc_Inventario_Fisico',$idDocumento);
        $oItem->getData();
        $oItem->Estado = $estado;
        $oItem->save();
        $response['tipo']= 'success';
        $response['title']= 'Cambio de estado exitoso';
        $response['mensaje']= 'Documento actualizado con éxito';
        show($response);
    }

    $oItem = new complex('Doc_Inventario_Auditable','Id_Doc_Inventario_Auditable',$idDocumento);
    $oItem->getData();
    $oItem->Estado = $estado;
    $oItem->save();
    $response['tipo']= 'success';
    $response['title']= 'Cambio de estado exitoso';
    $response['mensaje']= 'Documento actualizado con éxito';
    show($response);
}



