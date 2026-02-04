<?php 

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.consulta.php');
include_once('../../class/class.complex.php');
require_once('./helper_lista_precio/funciones_producto_lista.php');

$precio_acual = isset($_REQUEST['precio_acual']) ? $_REQUEST['precio_acual'] : false;
$nuevo_precio = isset($_REQUEST['nuevo_precio']) ? $_REQUEST['nuevo_precio'] : false;
$id = isset($_REQUEST['id_producto_lista_ganancia']) ? $_REQUEST['id_producto_lista_ganancia'] : false;
$funcionario = isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : false;


    actualizarPrecioProducto($id,$nuevo_precio);

    guardarActividadProducto($id,$funcionario,$precio_acual,$nuevo_precio,'Actualizacion de precio');

    $oItem=new complex('Producto_Lista_Ganancia','Id_Producto_Lista_Ganancia',$id);

    $res['Producto'] = $oItem->getData();
    unset($oItem);

    echo json_encode($res);
