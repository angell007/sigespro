<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$id_inventario_fisico = isset($_REQUEST['Id_Doc_Inventario_Fisico_Punto']) ? $_REQUEST['Id_Doc_Inventario_Fisico_Punto'] : false;
$productos = isset($_REQUEST['productos']) ? $_REQUEST['productos'] : false;

$productos = (array) json_decode($productos, true);

//validar si el inventario ya esta en
foreach ($productos as $prod) {
  
    $last_lote = $prod['Lotes'][count($prod['Lotes'])-1];
    if ($last_lote['Lote'] == '' || $last_lote['Fecha_Vencimiento'] == '' || $last_lote['Cantidad_Encontrada'] == '') {
        unset($prod['Lotes'][count($prod['Lotes'])-1]);
    }

    foreach ($prod['Lotes'] as $item) {
        
        if ($item['Cantidad_Encontrada'] > 0) {
          
            $oItem = new complex('Producto_Doc_Inventario_Fisico_Punto', 'Id_Producto_Doc_Inventario_Fisico_Punto');
            $oItem->Id_Producto = $prod['Id_Producto'];
            $oItem->Id_Inventario_Nuevo = !isset($item['Id_Inventario_Nuevo']) || $item['Id_Inventario_Nuevo']==''  ? '0' : $item['Id_Inventario_Nuevo'];
            $oItem->Primer_Conteo = $item['Cantidad_Encontrada'];
            $oItem->Fecha_Primer_Conteo = date('Y-m-d');
            $oItem->Cantidad_Inventario =  !isset($item['Cantidad']) || $item['Cantidad']==''  ? '0' : $item['Cantidad'];
            $oItem->Id_Doc_Inventario_Fisico_Punto = $id_inventario_fisico;
            $oItem->Lote = strtoupper($item['Lote']);
            $oItem->Fecha_Vencimiento =  ValidarFecha($prod['Id_Producto'],$item['Fecha_Vencimiento']);
           $oItem->save();
            unset($oItem);
        }
        
    }
}

// Cambiar el estado del inventario fisico
$oItem = new complex('Doc_Inventario_Fisico_Punto', 'Id_Doc_Inventario_Fisico_Punto', $id_inventario_fisico);
$oItem->Estado = 'Primer Conteo';
$oItem->save();
$band = $oItem->getId();
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

function ValidarFecha($id_producto, $fecha){
    $query = 'SELECT IFNULL(Id_Categoria,0) FROM Producto  WHERE Id_Producto='.$id_producto ;

    $oCon= new consulta();
    $oCon->setQuery($query);
    $producto = $oCon->getData();
    unset($oCon);


    if($producto['Id_Categoria']!=0){
        if($producto['Id_Categoria']!=6 && $producto['Id_Categoria']!=1){
            $fecha1=explode(',',$fecha);
            if($fecha1[1]!=2){
                $fecha=$fecha1[0].'-'.$fecha1[1].'-30';
            }else{
                $fecha=$fecha1[0].'-'.$fecha1[1].'-28';
            }
        }
    }
    return $fecha;
}

