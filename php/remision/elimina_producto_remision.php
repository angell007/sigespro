<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$rem = ( isset( $_REQUEST['rem'] ) ? $_REQUEST['rem'] : '' );
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$nombre = ( isset( $_REQUEST['Nombre'] ) ? $_REQUEST['Nombre'] : '' );

$datos = (array)json_decode($datos);

foreach($datos as $dato){
    $dato = (array)$dato;
    $oItem = new complex("Inventario","Id_Inventario",(INT)$dato["Id_Inventario"]);
    $actual = $oItem->getData();
    
    $act = number_format($actual["Cantidad_Apartada"],0,"","");
    $num = number_format($dato["Cantidad"],0,"","");
    $fin = $act - $num;
    $oItem->Cantidad_Apartada =  number_format($fin,0,"","");
    $oItem->save();
    unset($oItem);
    
    $oItem = new complex("Producto_Remision","Id_Producto_Remision",(INT)$dato["Id_Producto_Remision"]);
    $oItem->delete();
    unset($oItem);

}

$oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
$oItem->Id_Remision=$rem;
$oItem->Identificacion_Funcionario=$funcionario;
$oItem->Detalles="Se elimino este producto de la remision: ".$nombre;
$oItem->Fecha=date("Y-m-d H:i:s");
$oItem->Estado ='Edicion';
$oItem->save();
unset($oItem);

$resultado["Respuesta"]="ok";
echo json_encode($resultado);

?>