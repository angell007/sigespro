<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');

$configuracion = new Configuracion();

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );

$datos = (array) json_decode($datos);
$productos = (array) json_decode($productos , true);


    /*$oItem = new complex('Configuracion','Id_Configuracion',1);
    $nc = $oItem->getData();
    
    $oItem->Remision=$oItem->Remision+1;
    $oItem->save();
    $num_cotizacion=$nc["Remision"];
    unset($oItem);
    
    $cod = "RM".sprintf("%05d", $num_cotizacion); */
    $cod =$configuracion->Consecutivo('Remision');
    $datos['Codigo']=$configuracion->Consecutivo('Remision');
    
    $oItem = new complex($mod,"Id_".$mod);


foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
$id_remision = $oItem->getId();
$resultado = array();
unset($oItem);

//unset($productos[count($productos)-1]);

$link = mysql_connect('localhost', 'corvusla_proh', 'Proh2018') or die('No se pudo conectar: ' . mysql_error());
mysql_select_db('corvusla_proh') or die('No se pudo seleccionar la base de datos');

foreach($productos as $producto){
    //productos es igual al id de la factura que busco
    $query = "UPDATE `Factura_Venta` SET `Id_Remision_Factura_Venta` = ".$id_remision." WHERE `Id_Factura_Venta` = " .$producto;
    $result = mysql_query($query) or die('Consulta fallida: ' . mysql_error());

}

mysql_close($link);


?>		