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
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$peso = ( isset( $_REQUEST['peso'] ) ? $_REQUEST['peso'] : '' );

if(empty($peso) || $peso=='undefined'){
    $peso=0;
}
$productos = (array) json_decode($productos , true); 
//$peso = (array) json_decode($peso , true); 
//var_dump($_REQUEST);
$oItem = new complex($mod,'Id_Remision',$id);
$oItem->Estado_Alistamiento=2;
$oItem->Fin_Fase2=date("Y-m-d H:i:s");
$oItem->Estado="Alistada";
$oItem->Peso_Remision=$peso;
$oItem->save();
unset($oItem);
    
/*foreach($remision as $index=>$value) {
    $oItem->$index=$value;
}*/

$oItem = new complex($mod,'Id_Remision',$id);
$remision = $oItem->getData();
unset($oItem);

//Guardar actividad de la remision 
$oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
$oItem->Id_Remision=$id;
$oItem->Identificacion_Funcionario=$funcionario;
$oItem->Detalles="Se realizo la Fase 2 de Alistamiento de la Remision ".$remision["Codigo"];
$oItem->Estado="Fase 2";
$oItem->Fecha=date("Y-m-d H:i:s");
$oItem->save();
unset($oItem);

foreach($productos as $producto){


//Descontar del inventario

    $oItem = new complex('Inventario','Id_Inventario', $producto["Id_Inventario"]);
    $inv=$oItem->getData();
    $apartada=number_format($inv["Cantidad_Apartada"],0,"","");
    $cantidad=number_format($inv["Cantidad"],0,"","");
    $actual = number_format($producto["Cantidad"],0,"","");

    $fin = $apartada - $actual;
    $final = $cantidad - $actual;
    if($fin<0){
        $fin=0;
    }
    if($final<0){
        $final=0;
    }
    $oItem->Cantidad_Apartada=number_format($fin,0,"","");
    $oItem->Cantidad=number_format($final,0,"","");
    $oItem->save();
    unset($oItem);

}
$resultado['mensaje'] = "Se ha guardado correctamente la Fase 2 de la Remision con codigo: ". $remision['Codigo'];
$resultado['tipo'] = "success";
echo json_encode($resultado);

?>	