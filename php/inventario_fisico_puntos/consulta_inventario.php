<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['Id_Inventario_Fisico_Punto'] ) ? $_REQUEST['Id_Inventario_Fisico_Punto'] : '' );

$oItem = new complex("Inventario_Fisico_Punto","Id_Inventario_Fisico_Punto",$id);
$data=$oItem->getData();
unset($oItem);
        
$oItem = new complex("Funcionario","Identificacion_Funcionario",$data["Funcionario_Cuenta"]);
$func_contador = $oItem->getData();
unset($oItem);

$oItem = new complex("Funcionario","Identificacion_Funcionario",$data["Funcionario_Digita"]);
$func_digitador = $oItem->getData();
unset($oItem);

$oItem = new complex("Punto_Dispensacion","Id_Punto_Dispensacion",$data["Id_Punto_Dispensacion"]);
$punto = $oItem->getData();
unset($oItem);

 
$productos =(array) json_decode($data["Lista_Productos"],true);
$datos["Id_Inventario_Fisico_Punto"]=$id;    
$datos["Funcionario_Digita"]=$func_digitador;
$datos["Funcionario_Cuenta"]=$func_contador;
$datos["Punto"] = $punto;
$datos["Inicio"] = $data["Fecha_Inicio"];
$datos["Productos_Conteo"] = $data["Conteo_Productos"];
$datos["Tipo"] = "success";
$datos["Title"] = "Continuamos";
$datos["Text"] = "Continuamos con el Inventario<br> ¡Muchos Exitos!";

$resultado["Datos"]=$datos;
$resultado["Productos"]=$productos;


echo json_encode($resultado);
?>