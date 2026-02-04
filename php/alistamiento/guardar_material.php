<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$funcionario=( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );


$productos = (array) json_decode($productos , true); 
$oItem = new complex($mod,'Id_Remision',$id);
$oItem->Fin_Fase1=date("Y-m-d H:i:s");
/* $oItem->Estado_Alistamiento=2; // CAMBIO PEDIDO POR JHON BACAREO - 20/12/2019
$oItem->Estado="Alistada"; */
$oItem->Estado_Alistamiento=1;
$oItem->Fin_Fase1=date("Y-m-d H:i:s");
    
/*foreach($remision as $index=>$value) {
    $oItem->$index=$value;
}*/
$oItem->save();
unset($oItem);

$oItem = new complex($mod,'Id_Remision',$id);
$remision = $oItem->getData();
unset($oItem);

/* foreach($productos as $producto){ // CAMBIO PEDIDO POR JHON BACAREO - 20/12/2019
    
    
    
    //Descontar del inventario
    
    $oItem = new complex('Inventario','Id_Inventario', $producto["Id_Inventario"]);
    $inv=$oItem->getData();
    $apartada=number_format($inv["Cantidad_Apartada"],0,"","");
    $cantidad=number_format($inv["Cantidad"],0,"","");
    $actual = number_format($producto["Cantidad"],0,"","");

    $fin = $apartada - $actual;
    if($fin<0){
        $fin=0;
    }
    $final = $cantidad - $actual;
    if($final<0){
        $final=0;
    }
    $oItem->Cantidad_Apartada=number_format($fin,0,"","");
    $oItem->Cantidad=number_format($final,0,"","");
    $oItem->save();
    unset($oItem);
    
} */

//Guardar actividad de la remision 
$oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
$oItem->Id_Remision=$id;
$oItem->Identificacion_Funcionario=$funcionario;
// $oItem->Detalles="Se realizo la Fase Unica de Alistamiento de la Remision ".$remision["Codigo"].".";
$oItem->Detalles="Se realizo la Fase 1 de Alistamiento de la Remision ".$remision["Codigo"];
$oItem->Estado="Alistamiento";
$oItem->Fecha=date("Y-m-d H:i:s");
$oItem->save();
unset($oItem);

$resultado['mensaje'] = "Se ha guardado correctamente la Fase Unica de alistaiento de la Remision con codigo: ". $remision['Codigo'];
$resultado['tipo'] = "success";
echo json_encode($resultado);

?>	




