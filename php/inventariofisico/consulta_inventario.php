<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['Id_Inventario_Fisico'] ) ? $_REQUEST['Id_Inventario_Fisico'] : '' );


$oItem = new complex("Inventario_Fisico","Id_Inventario_Fisico",$id);
$data=$oItem->getData();
unset($oItem);
        
$oItem = new complex("Funcionario","Identificacion_Funcionario",$data["Funcionario_Cuenta"]);
$func_contador = $oItem->getData();
unset($oItem);

$oItem = new complex("Funcionario","Identificacion_Funcionario",$data["Funcionario_Digita"]);
$func_digitador = $oItem->getData();
unset($oItem);

$oItem = new complex("Bodega","Id_Bodega",$data["Bodega"]);
$bodega = $oItem->getData();
unset($oItem);
if($data["Categoria"]!="Todas"){
    $oItem = new complex("Categoria","Id_Categoria",$data["Categoria"]);
    $categoria = $oItem->getData();
    unset($oItem);
}else{
    $categoria["Nombre"]="Todas";
}
   
$productos = (array) json_decode($data["Lista_Productos"] , true);

$datos["Id_Inventario_Fisico"]=$id;    
$datos["Funcionario_Digita"]=$func_digitador;
$datos["Funcionario_Cuenta"]=$func_contador;
$datos["Bodega"] = $bodega;
$datos["Categoria"]=$categoria;
$datos["Letras"]=$data["Letras"];
$datos["Inicio"] = $data["Fecha_Inicio"];
$datos["Productos_Conteo"] = $data["Conteo_Productos"];
$datos["Tipo_Inventario"] = $data["Tipo_Inventario"];
$datos["Tipo"] = "success";
$datos["Title"] = "Continuamos";
$datos["Text"] = "Continuamos con el Inventario<br> ¡Muchos Exitos!";

$resultado["Datos"]=$datos;
$resultado["Productos"]=$productos;


echo json_encode($resultado);
?>