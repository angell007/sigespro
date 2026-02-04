<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$idRem = isset($_REQUEST['Id_Remision']) ? $_REQUEST['Id_Remision'] : false;
$estado = isset($_REQUEST['Estado']) ? $_REQUEST['Estado'] : false;

if($idRem){
    $res = [];
    if($estado == 'Pendiente' || $estado == 'Anulada' ){
            $oItem = new complex('Remision','Id_Remision',$idRem);
            $oItem->Estado = $estado;
            $oItem->save();
            unset($oItem);
            $res['title'] = 'Opercación exitosa';
            $res['text'] = 'Se ha actualizado la remisión a : '.$estado;
            $res['type'] = 'success';
    }else{
        $res['title'] = 'Error';
        $res['text'] = 'El estado es incorrecto '.$estado;
        $res['type'] = 'error';
    }

    echo json_encode($res);

}