<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$id_inventario_fisico = ( isset( $_REQUEST['Id_Inventario_Fisico_Punto'] ) ? $_REQUEST['Id_Inventario_Fisico_Punto'] : '' );

$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$productos = (array) json_decode($productos, true);
foreach ($productos as $prod) {
    if(count($prod['Lotes'])>1){
        unset($prod['Lotes'][count($prod['Lotes'])-1]);
    
        foreach ($prod['Lotes'] as $item) {
            if($item['Lote']!=''&& $item['Cantidad_Encontrada']!=''&&$item['Fecha_Vencimiento']!='' && $prod['Id_Producto']!='' && $item['Cantidad_Encontrada']!=0){
                if($item['Id_Producto_Inventario_Fisico']){
                    $oItem = new complex('Producto_Inventario_Fisico_Punto', 'Id_Producto_Inventario_Fisico',$item['Id_Producto_Inventario_Fisico'] );
                    $segundo_conteo = number_format((INT) $item['Cantidad_Encontrada'],0,"","");
                    $oItem->Segundo_Conteo = $segundo_conteo;
                    $oItem->Lote = $item['Lote'];
                    $oItem->Fecha_Segundo_Conteo= date('Y-m-d');
                    $time = strtotime($item['Fecha_Vencimiento']);
                    $fecha = date('Y-m-d',$time);
                    $oItem->Fecha_Vencimiento =$fecha ;
                    $oItem->save();
                    unset($oItem);
                }else{
                    $oItem = new complex('Producto_Inventario_Fisico_Punto', 'Id_Producto_Inventario_Fisico');
                    $oItem->Id_Producto = $prod['Id_Producto'];
                    $oItem->Primer_Conteo =0;
                    $segundo_conteo = number_format((INT) $item['Cantidad_Encontrada'],0,"","");
                    $oItem->Segundo_Conteo = $segundo_conteo;
                    $oItem->Fecha_Primer_Conteo = date('Y-m-d');
                    $oItem->Fecha_Segundo_Conteo = date('Y-m-d');
                    $oItem->Id_Inventario_Fisico_Punto = $id_inventario_fisico;
                    $oItem->Lote = $item['Lote'];
                    $time = strtotime($item['Fecha_Vencimiento']);
                    $fecha = date('Y-m-d',$time);
                    $oItem->Fecha_Vencimiento =$fecha ;
                    $oItem->save();
                    unset($oItem);
                }
            }
        }
    }
    
}

// Cambiar el estado del inventario fisico
$oItem = new complex('Inventario_Fisico_Punto', 'Id_Inventario_Fisico_Punto', $id_inventario_fisico);
$oItem->Estado = 'Por Confirmar';
$band = $oItem->Id_Inventario_Fisico_Punto;
$oItem->save();
unset($oItem);


if($band){
    $resultado['titulo'] = "Operación Exitosa";
    $resultado['mensaje'] = "Se ha ajustado correctamente el inventario";
    $resultado['tipo'] = "success";
}else{
    $resultado['titulo'] = "Error";
    $resultado['mensaje'] = "Ha ocurrido un error inesperado. Por favor verifique su conexión a internet.";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);
?>