<?php 

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.consulta.php');
include_once('../../class/class.complex.php');
require_once('./helper_lista_precio/funciones_producto_lista.php');

$cum = isset($_REQUEST['cum']) ? $_REQUEST['cum'] : false;
$precio_acual = isset($_REQUEST['precio_acual']) ? $_REQUEST['precio_acual'] : false;
$id = isset($_REQUEST['id_lista_ganancia']) ? $_REQUEST['id_lista_ganancia'] : false;
$funcionario = isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : false;
echo $precio_acual;exit;
try {

    if (getProductoByCum($cum,$id)) {
        throw new Exception('Este cum ya se encuentra asociado a la lista');     
    }

    // $id_producto_lista = insertProducto($cum,$precio_acual,$id);
    guardarActividadProducto($id_producto_lista,$funcionario,0,$precio_acual,'Creacion de producto');

    $oItem=new complex('Producto_Lista_Ganancia','Id_Producto_Lista_Ganancia',$id_producto_lista);

    $res['Producto'] = $oItem->getData();
    unset($oItem);

    echo json_encode($res);
    //code...
} catch (Exception $th) {
    //throw $th;
    header("HTTP/1.0 400 ".$th->getMessage());
    echo json_encode(['message'=>$th->getMessage()]);
}
