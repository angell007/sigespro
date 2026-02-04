<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$categoria = ( isset( $_REQUEST['id_categoria'] ) ? $_REQUEST['id_categoria'] : '' );
$producto = ( isset( $_REQUEST['id_producto'] ) ? $_REQUEST['id_producto'] : '' );

$oItem = new complex('Producto', 'Id_Producto', $producto);
$oItem->Id_Categoria = $categoria;
$Id_Producto = $oItem->Id_Producto;
$oItem->save();
unset($oItem);

if($Id_Producto){
 $resultado['mensaje'] = "Se ha actualizado correctamente la categoria del producto";
    $resultado['tipo'] = "success";
    $resultado['titulo'] = "Operacion Exitosa";
}else{
   $resultado['mensaje'] = "Ha ocurrido un error inesperado. Por favor vuelva a intentarlo";
    $resultado['tipo'] = "error";
    $resultado['titulo'] = "Error";
}



echo json_encode($resultado);

?>