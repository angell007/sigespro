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
$tipo = isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : false;

$productos = (array) json_decode($productos, true);

foreach ($productos as $prod) {
    unset($prod['Lotes'][count($prod['Lotes'])-1]);
    if (count($prod['Lotes'])>0){
        foreach ($prod['Lotes'] as $item) {
            if($item['Lote']!='' && $item['Fecha_Vencimiento']!='' && $item['Cantidad_Encontrada']>0 && $item['Cantidad_Encontrada']!=''){
                $oItem = new complex('Producto_Inventario_Fisico_Punto', 'Id_Producto_Inventario_Fisico');
                $oItem->Id_Producto = $prod['Id_Producto'];
                $oItem->Primer_Conteo =(INT)$item['Cantidad_Encontrada'];
                $oItem->Fecha_Primer_Conteo = date('Y-m-d');
                $oItem->Id_Inventario_Fisico_Punto = $id_inventario_fisico;
                $oItem->Lote = $item['Lote'];
                $time = strtotime($item['Fecha_Vencimiento']);
                $fecha = date('Y-m-d',$time);
                $oItem->Fecha_Vencimiento =$fecha ;

                if($tipo=="Si"){
                    $oItem->Id_Inventario_Nuevo=$item['Id_Inventario_Nuevo']=='' ? 0: $item['Id_Inventario_Nuevo'] ;
                    $oItem->Cantidad_Inventario=$item['Cantidad_Inventario']=='' ? 0: $item['Cantidad_Inventario'];
                }

                $oItem->save();
                unset($oItem);
            }
        
        }
    }
   
}

// Cambiar el estado del inventario fisico
$oItem = new complex('Inventario_Fisico_Punto', 'Id_Inventario_Fisico_Punto', $id_inventario_fisico);

if($tipo=="Si"){
    $oItem->Estado = 'Por Confirmar';  
}else{
    $oItem->Estado = 'Segundo Conteo'; 
}

$bandera = $oItem->Id_Inventario_Fisico_Punto;
$oItem->save();
unset($oItem);

if($bandera){
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