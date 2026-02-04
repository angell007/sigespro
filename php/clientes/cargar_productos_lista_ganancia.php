<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
header("Cache-Control: no-cache");

include_once('../../class/PHPExcel/IOFactory.php');
include_once('../../class/class.consulta.php');
#include_once('../../class/class.complex.php');
require_once('./helper_lista_precio/funciones_producto_lista.php');

$id_lista_ganancia = isset($_REQUEST['id_lista_ganancia']) ? $_REQUEST['id_lista_ganancia'] : false;
$funcionario = isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : false;

if(isset($_FILES['excelFile'])){ 
    try {   
        //code...
        $excelObject = PHPExcel_IOFactory::load($_FILES['excelFile']['tmp_name']);
        $getShet = $excelObject->getActiveSheet()->toArray(null);
    
        validarProductos($getShet);
        foreach ($getShet as $key => $value) {

            $producto = getProductoByCum($value[0],$id_lista_ganancia);

            if ($producto) {
                actualizarPrecioProducto($producto['Id_Producto_Lista_Ganancia'],$value[1]);
                guardarActividadProducto($producto['Id_Producto_Lista_Ganancia'],$funcionario,$producto['Precio'],$value[1], 'Actualizacion de precio MASIVO');
               
            }else{
               $id_producto_lista= insertProducto($value[0],$value[1],$id_lista_ganancia);
                guardarActividadProducto($id_producto_lista,$funcionario,0,$value[1],'Creacion de producto MASIVO');
            }
        }
       #header("Proceso Realizado satisfactoriamente",true,200);
        echo json_encode(['message'=>'Actualización exitosa']);
    } catch (Exception $th) {
        //throw $th;
        header("HTTP/1.0 400 ".$th->getMessage());
        echo json_encode(['message'=>$th->getMessage()]); 
    }
}else{
    #return 'No se envió archuvo';
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




