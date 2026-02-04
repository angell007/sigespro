<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.configuracion.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$datos = (array) json_decode($datos,true);
foreach($datos as $dato){

    $oItem=new complex('Reporte_Inventario', 'Id_Reporte_Inventario', $dato['Id_Reporte_Inventario']);
    $oItem->Cantidad_Final=$dato['Cantidad_Ingresada'];    
    $oItem->save();
    unset($oItem);

}
$resultado['mensaje'] = "Reconteo Guardado Exitosamente!";
$resultado['tipo'] = "success";
echo json_encode($resultado);

?>