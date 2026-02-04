<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
header("Cache-Control: no-cache");

include_once('../../class/PHPExcel/IOFactory.php');
include_once('../../class/class.consulta.php');
require_once('./helper_lista_productos/funciones_lista_productos.php');


$id_lista_precio = isset($_REQUEST['Id_Lista_Precio']) ? $_REQUEST['Id_Lista_Precio'] : false;
$funcionario = isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : false;
$idproveedor = isset($_REQUEST['Id_Proveedor']) ? $_REQUEST['Id_Proveedor'] : false;

if(isset($_FILES['excelFile'])){ 
    try {   
        //code...
        $excelObject = PHPExcel_IOFactory::load($_FILES['excelFile']['tmp_name']);
        $getShet = $excelObject->getActiveSheet()->toArray(null);
    
        validarProductos($getShet);
        foreach ($getShet as $key => $value) {

            $producto = getProductoByCum($idproveedor,$value[0]);

            if ($producto) {
                actualizarPrecioProducto($producto['Id_Lista_Precio'],$value[1]);
                guardarActividadProducto($producto['Id_Lista_Precio'],$funcionario,$producto['Precio'],$value[1], 'Actualizacion de precio MASIVO');
            }else{
               $id_producto_lista= insertProducto($idproveedor,$value[0],$value[1]);
                guardarActividadProducto($idproveedor,$funcionario,0,$value[1],'Creacion de producto MASIVO');
            }
        }
        echo json_encode(['message'=>'Actualización exitosa']);
    } catch (Exception $th) {
        header("HTTP/1.0 400 ".$th->getMessage());
        echo json_encode(['message'=>$th->getMessage()]); 
    }
}else{
    header("HTTP/1.0 400 No se envio el archivo");
}

function validarProductos($productos){
    foreach ($productos as $key => $value) {
        if(!$value[0] ||$value[0]=='' ||!$value[1] ||$value[1]=='' 
        || $value[1]<0  ){
            throw new Exception('Error de los datos en la fila '.$key+=1);
        }
    }
}




