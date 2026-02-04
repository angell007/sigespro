<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$soportes = ( isset( $_REQUEST['soporte'] ) ? $_REQUEST['soporte'] : '' );

$id_factura = $datos["Id_Factura"];
$id_factura_actualizada = $datos["Id_Factura_Actualizada"];

if($id_factura_actualizada==''){
    
    $oItem = new Lista('Z_Factura_Actualizada');  
    $oItem->setRestrict('Id_Factura','=',$id_factura);
    $fact=$oItem->getList();
    unset($oItem);
    
    if($fact[0]["Id_Factura_Actualizada"]!=""){
        $id_factura_actualizada = $fact[0]["Id_Factura_Actualizada"];
    }
}

foreach($soportes as $soporte){
    $oItem = new complex('Soporte_Auditoria','Id_Soporte_Auditoria',$soporte["Id_Soporte_Auditoria"]);  
    $oItem->Paginas=$soporte["Paginas"];
    $oItem->save();
    unset($oItem);  
}

if($id_factura_actualizada==''){
    $oItem = new complex('Z_Factura_Actualizada','Id_Factura_Actualizada'); 
    unset($datos["Id_Factura_Actualizada"]);
    foreach($datos as $index=>$value) {
        $oItem->$index=$value;
    }
    $oItem->Estado="Si";
    $oItem->save();
    unset($oItem);
}


echo "Exito";



?>

