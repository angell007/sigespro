<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$condicion = '';
$data = isset($_REQUEST['data']) && $_REQUEST['data'] != '' ? $_REQUEST['data'] : false;
$extensions = array("jpg","png","jpeg","pdf");

$archivo = isset($_FILES['archivo']) && $_FILES['archivo'] != '' ? $_FILES['archivo'] : false;
$data = json_decode($data, true);

include_once('./helper.remision_orden.php');



exit;

$cabecera = $data['cabecera'];
$productos = $data['productos'];

$file_path = __DIR__.'/../../../ARCHIVOS/ORDEN_COMPRA/SOPORTES';

$cabecera['Archivo_Compra_Cliente'] = '';
try {
    
    if($archivo){
        validarArchivo();
        $temp_archivo = $archivo['tmp_name'];
        if ( !file_exists($file_path) ) {
            mkdir($file_path, 0777, true);
        }
         $nombre_archivo= generarNombre($archivo);
         move_uploaded_file($temp_archivo, $file_path . '/' . $nombre_archivo);
       
         $cabecera['Archivo_Compra_Cliente'] =   $nombre_archivo;
     }

    $compra = validarInventario($productos);
   // print_r($compra);exit;
    $idOrden = guardarCabecera($cabecera);  
    guardarPreCompra($compra,$idOrden);
    guardarProductos($idOrden,$productos);
    echo json_encode(['text'=>'guardado con éxito','type'=>"success",'title'=>"Operación Exitosa"]);
} catch (\Throwable $th) {
    echo json_encode(['text'=>$th->getMessage(),'type'=>"error",'title'=>"Ha ocurrido un error"] );
}

function guardarRemision($cabecera, $productos){
    
}


function validarInventario(&$productos)
{
    global $productos;
    $compra = [];
    foreach ($productos as $key => $prod) {
        $query = ' SELECT SUM( I.Cantidad - I.Cantidad_Apartada - I.Cantidad_Seleccionada) AS Cantidad_Disponible 
                    FROM  Inventario_Nuevo I
                    INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba  
                    WHERE ( E.Id_Bodega_Nuevo IS NOT NULL AND E.Id_Bodega_Nuevo) AND I.Id_Producto = ' . $prod['Id_Producto'] . '
                    GROUP BY I.Id_Producto
                    ';
        $oCon = new consulta();
        $oCon->setQuery($query);
        $inventario = $oCon->getData();
        unset($oCon);

        if (!$inventario) {
            $inventario = [];
            $inventario['Cantidad_Disponible'] = 0;
        }

        if ($prod['Cantidad'] > $inventario['Cantidad_Disponible']) {
            $productos[$key]['Cantidad_Compra']   = $prod['Cantidad'] - $inventario['Cantidad_Disponible'];

            if (array_key_exists($prod['Proveedor'], $compra)) {
                array_push($compra[$prod['Proveedor']]['Productos'], $productos[$key]);
            } else {
                $compra[$prod['Proveedor']] = [];
                $compra[$prod['Proveedor']]['Productos'][] = $productos[$key];
            }
        }else{
            $productos[$key]['Cantidad_Compra']  = 0;
        }
    }
    return $compra;
}

function guardarCabecera($cabecera)
{
    $oItem = new complex('Orden_Pedido', 'Id_Orden_Pedido');
    $oItem->Id_Cliente = $cabecera['cliente']['Id_Cliente'];
    $oItem->Id_Agentes_Cliente = $cabecera['agente']['Id_Agentes_Cliente'];
    $oItem->Fecha_Probable_Entrega = $cabecera['fecha_probable_entrega'];
    $oItem->Identificacion_Funcionario = $cabecera['Identificacion_Funcionario'];
    $oItem->Observaciones = $cabecera['observaciones'];
    $oItem->Archivo_Compra_Cliente = $cabecera['Archivo_Compra_Cliente'];
    $oItem->Orden_Compra_Cliente = $cabecera['ordenCompra'];
    $oItem->Estado = 'Activa';
    $oItem->save();
    return $oItem->getId();
}


function guardarProductos($idOrden,$productos){
    foreach ($productos as $producto) {
        # code...
        $query = 'INSERT INTO  Producto_Orden_Pedido ( Id_Orden_Pedido, Id_Producto, Cantidad , Precio_Orden , Impuesto, Precio, Costo, Id_Proveedor, Cantidad_Compra ) VALUES(
            '.$idOrden.',
             '.$producto['Id_Producto'].' , 
             '.$producto['Cantidad'].' ,  
             '.number_format($producto['Precio_Orden'],2,".","").' ,
             '.$producto['Impuesto'].' ,
             '.number_format($producto['Precio'],2,".","").'  ,
             '.number_format($producto['Costo'],2,".","").' ,
             '.$producto['Proveedor'].' ,
             '.$producto['Cantidad_Compra'].' 
        )';
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->createData();
        unset($oCon);
    }
}

function validarInventario(&$productos)
{
    global $productos;
    $compra = [];
    foreach ($productos as $key => $prod) {
        $query = ' SELECT SUM( I.Cantidad - I.Cantidad_Apartada - I.Cantidad_Seleccionada) AS Cantidad_Disponible 
                    FROM  Inventario_Nuevo I
                    INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba  
                    WHERE ( E.Id_Bodega_Nuevo IS NOT NULL AND E.Id_Bodega_Nuevo) AND I.Id_Producto = ' . $prod['Id_Producto'] . '
                    GROUP BY I.Id_Producto
                    ';
        $oCon = new consulta();
        $oCon->setQuery($query);
        $inventario = $oCon->getData();
        unset($oCon);

        if (!$inventario) {
            $inventario = [];
            $inventario['Cantidad_Disponible'] = 0;
        }

        if ($prod['Cantidad'] > $inventario['Cantidad_Disponible']) {
            $productos[$key]['Cantidad_Compra']   = $prod['Cantidad'] - $inventario['Cantidad_Disponible'];

            if (array_key_exists($prod['Proveedor'], $compra)) {
                array_push($compra[$prod['Proveedor']]['Productos'], $productos[$key]);
            } else {
                $compra[$prod['Proveedor']] = [];
                $compra[$prod['Proveedor']]['Productos'][] = $productos[$key];
            }
        }else{
            $productos[$key]['Cantidad_Compra']  = 0;
        }
    }
    return $compra;
}


function validarArchivo(){
    global $archivo ,$extensions;
    $archivoExtension = getExtension($archivo);

    $valido = in_array($archivoExtension, $extensions);
   
    if ( !$valido ) {
        throw new Exception("Error, El tipo de archivo no es permitido");
    }
}


function generarNombre($archivo){

    $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';
    $name = substr(str_shuffle($permitted_chars), 0, 30);
    $archivoExtension = getExtension($archivo);
    $name.= '.'.$archivoExtension;
    
    return $name;
}

function getExtension($archivo){
    $archivoExtension = pathinfo($archivo['name'],PATHINFO_EXTENSION);
    return $archivoExtension;
}

function guardarPreCompra( $proveedores, $idOrden ){
    global $cabecera;
    foreach ($proveedores as $key => $prov) {
        $oItem= new complex("Pre_Compra","Id_Pre_Compra");
        $oItem->Identificacion_Funcionario= $cabecera['Identificacion_Funcionario'];
        $oItem->Id_Proveedor = $key;
        $oItem->Tipo = 'Orden_Pedido';
        $oItem->Id_Orden_Pedido =  $idOrden;
        $oItem->save();
        $id_pre_compra= $oItem->getId();
        unset($oItem);
        foreach ($prov["Productos"] as $item) {
            if( isset($item["Id_Producto"]) && $item["Id_Producto"]!='' ){
                $oItem= new complex("Producto_Pre_Compra","Id_Producto_Pre_Compra");
                $oItem->Id_Pre_Compra=$id_pre_compra;
                $oItem->Id_Producto = $item["Id_Producto"];
                if($item["Cantidad"]==''){
                    $oItem->Cantidad = 0;
                }else{
                    $oItem->Cantidad = $item["Cantidad"];
                }
                if($item["Costo"]==''){
                    $oItem->Costo = 0;
                }else{
                    $oItem->Costo = $item["Costo"];
                }

                $oItem->save();
                unset($oItem);
            }

        }
        
        

    }

}