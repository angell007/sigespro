<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['Id_Inventario_Fisico'] ) ? $_REQUEST['Id_Inventario_Fisico'] : '' );
$productos = ( isset( $_REQUEST['Productos'] ) ? $_REQUEST['Productos'] : '' );

$prod = (array) json_decode($productos, true);
//var_dump($prod);


$oItem= new complex("Inventario_Fisico","Id_Inventario_Fisico",$id);
$oItem->Lista_Productos = $productos;
$oItem->save();
unset($oItem);

$resultado["Tipo"]="success";
$resultado["Titulo"]="Agregado Correctamente";
$resultado["Texto"]="Producto Agregado de Manera Exitosa";
            

echo json_encode($resultado);
?>