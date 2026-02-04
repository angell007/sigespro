<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$autorizacion = ( isset( $_REQUEST['autorizacion'] ) ? $_REQUEST['autorizacion'] : '' );
$fecha = ( isset( $_REQUEST['fecha'] ) ? $_REQUEST['fecha'] : '' );



if($id!=''&&$autorizacion!=''&&$fecha!=''){
    $oItem = new complex('Producto_Dispensacion','Id_Producto_Dispensacion',$id); 
    $oItem->Numero_Autorizacion=$autorizacion;
    $oItem->Fecha_Autorizacion=$fecha;
    $oItem->Actualizado=1;
    $oItem->save();
    unset($oItem);
}

echo "Exito";



?>