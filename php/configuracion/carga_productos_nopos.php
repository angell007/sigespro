<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
header("Cache-Control: no-cache");

include_once('../../class/PHPExcel/IOFactory.php');
include_once('../../class/class.consulta.php');
require_once('./helper_cum/funciones_cum.php');

$Id_Lista_Producto_Nopos = isset($_REQUEST['Id_Lista_Producto_Nopos']) ? $_REQUEST['Id_Lista_Producto_Nopos'] : false;
$Cum = isset($_REQUEST['Cum']) ? $_REQUEST['Cum'] : false;

if(isset($_FILES['excelFile'])){
    try {
        $excelObject = PHPExcel_IOFactory::load($_FILES['excelFile']['tmp_name']);
        $getShet = $excelObject->getActiveSheet()->toArray(null);
        validarProductos($getShet);  
        foreach($getShet as $key => $value) {
            if($value[0] == ''){
                break;  
            }
            $productonopos = getProductonoposByCum($Id_Lista_Producto_Nopos, $value[0]);
            if($productonopos) {
                actualizarProductoNoPos($productonopos['Id_Producto_NoPos'],$value, "Actualización exitosa");
            }else{
                $productoNoPos= validarExistencia($value);
                if($productoNoPos) {
                    guardarProductoNoPos($Id_Lista_Producto_Nopos, $value);
                }    
            }
        }
     echo json_encode(['message'=>'Proceso Exitoso']);
    } catch (Exception $th) {
        header("HTTP/1.0 400 ".$th->getMessage());
        echo json_encode(['message'=>$th->getMessage()]);
    }
}else{
    header("HTTP/1.0 400 No se envio el archivo");
}
