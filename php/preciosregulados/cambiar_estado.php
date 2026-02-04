<?php 

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.consulta.php');
include_once('../../class/class.complex.php');
require_once('./helper_lista_precio_ganancia/funciones_lista_precio_ganancia.php');

$id_producto = isset($_REQUEST['id_precio_regulado']) ? $_REQUEST['id_precio_regulado'] : false;
$nuevo_estado = isset($_REQUEST['nuevo_estado']) ? $_REQUEST['nuevo_estado'] : false;
$funcionario = isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : false;
try {
    cambiarEstado($id_producto,$nuevo_estado,$funcionario);
} catch (\Throwable $th) {
    echo json_encode(['Error'=>$th,'Mensaje'=>'Ha ocurrido un error inesperado']);
}   
