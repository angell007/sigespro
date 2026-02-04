<?php 

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.consulta.php');
include_once('../../class/class.complex.php');
require_once('./helper_lista_precio/funciones_producto_lista.php');

$id_producto = isset($_REQUEST['id_producto_lista']) ? $_REQUEST['id_producto_lista'] : false;
$nuevo_estado = isset($_REQUEST['nuevo_estado']) ? $_REQUEST['nuevo_estado'] : false;
$funcionario = isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : false;
try {
    //code...
    cambiarEstado($id_producto,$nuevo_estado,$funcionario);
} catch (\Throwable $th) {
    //throw $th;
    echo json_encode(['Error'=>$th,'Mensaje'=>'Ha ocurrido un error inesperado']);
}   

    
