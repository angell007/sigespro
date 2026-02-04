<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['Id_Inventario_Fisico_Punto'] ) ? $_REQUEST['Id_Inventario_Fisico_Punto'] : '' );
$productos = ( isset( $_REQUEST['Productos'] ) ? $_REQUEST['Productos'] : '' );

$prod = (array) json_decode($productos, true);
//var_dump($prod);


$oItem= new complex("Inventario_Fisico_Punto","Id_Inventario_Fisico_Punto",$id);
//var_dump($oItem);
$oItem->Lista_Productos = $productos;
//var_dump($oItem);
try{
    $oItem->save();
    unset($oItem);
    $resultado["Tipo"]="succes";
    $resultado["Titulo"]="Agregado Correctamente";
    $resultado["Texto"]="Producto Agregado de Manera Exitosa";
}catch (Exception $e) {
    $resultado["Tipo"]="error";
    $resultado["Titulo"]="Error de Registro";
    $resultado["Texto"]="Producto No Agregado: ". $e->getMessage();
}
            

echo json_encode($resultado);
?>