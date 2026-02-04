<?php
//header('Content-Type: application/json');

require_once("/home/sigespro/public_html/config/start.inc_cron.php");
include_once("/home/sigespro/public_html/class/class.lista.php");
include_once("/home/sigespro/public_html/class/class.complex.php");
include_once("/home/sigespro/public_html/class/class.consulta.php");
require_once('/home/sigespro/public_html/class/class.configuracion.php');
date_default_timezone_set('America/Bogota'); 

$oItem = new complex("Configuracion","id",1);
$config = $oItem->getData();
unset($oItem);

$fecha_mes_actual = date('Y-m-30');
$fecha_mes_venc = strtotime('+3 months', strtotime($fecha_mes_actual));
$fecha_mes_venc = date('Y-m-d', $fecha_mes_venc);

$bodegas = getAllBodegas();

foreach ($bodegas as $bodega) {
    ## OBTENER EL INVENTARIO DE CADA BODEGA PARA REALIZAR EL TRASLADO

    $inventarios = getInventario($bodega['Id_Bodega']);

    $datos = armarDatosRem($bodega);

    guardarMovimiento($datos,$inventarios);
    
}

function armarDatosRem($bodega)
{
    
    
    $datos = [
        "Id_Bodega_Origen" => $bodega['Id_Bodega'],
        "Id_Bodega_Destino" => 9,
        "Observaciones" => "Movimiento automatico para la bodega de vencimientos.",
        "Estado" =>"Pendiente",
        "Fecha" => date("Y-m-d H:i:s")
      
    ];

    return $datos;

}

function armarProd($inventarios, $id_movimiento)
{
    $productos = [];
    
    if (count($inventarios) > 0) {
        foreach ($inventarios as $inv) {
            $cantidad = $inv['Cantidad']-$inv['Cantidad_Apartada']-$inv['Cantidad_Seleccionada'];
            $prod = [
                "Id_Movimiento_Vencimiento" => $id_movimiento,
                "Id_Inventario" => $inv['Id_Inventario'],
                "Lote" => $inv['Lote'],
                "Fecha_Vencimiento" => $inv['Fecha_Vencimiento'],
                "Cantidad" => number_format($cantidad,0,"",""),
                "Id_Producto" => $inv['Id_Producto']                
            ];

            $productos[] = $prod;
        }
    }

    return $productos;
}

function guardarMovimiento($datos,$inventarios)
{
    $oItem = new complex('Movimiento_Vencimiento','Id_Movimiento_Vencimiento');
    if (count($datos) > 0 && count($inventarios) > 0) {
        foreach ($datos as $index => $value) {
            $oItem->$index = $value;
        }
        $oItem->save();
        $id_movimiento = $oItem->getId();
    }
    unset($oItem);

    //generarQr($id_movimiento);

    $productos = armarProd($inventarios,$id_movimiento);
    
    if (count($productos) > 0) {
        foreach ($productos as $producto) {
            $oItem = new complex('Producto_Movimiento_Vencimiento','Id_Producto_Movimiento_Vencimiento');
            foreach ($producto as $index => $value) {
                $oItem->$index = $value;
            }
            $oItem->save();
            unset($oItem);
        }

        updateToVencimiento($productos); // ACTUALIZAR A BODEGA VENCIMIENTOS
    }

    return;
}

function updateToVencimiento($productos)
{
    foreach ($productos as $producto) {
        $cantidad_prod = $producto['Cantidad'];
        $oItem = new complex('Inventario','Id_Inventario',$producto['Id_Inventario']);
        $inventario = $oItem->getData();
        $cantidad_actual = $oItem->Cantidad - $oItem->Cantidad_Apartada - $oItem->Cantidad_Seleccionada;
        $cantidad_final = $cantidad_actual - $cantidad_prod;
        if($cantidad_final<0){
            $cantidad_final=0;
        }
        $oItem->Cantidad = number_format($cantidad_final,0,"","");
        $oItem->save();
        unset($oItem);
        
        $invVencimiento = existeInventarioEnVencimiento($inventario);

        if ($invVencimiento) {
            $oItem = new complex('Inventario','Id_Inventario', $invVencimiento['Id_Inventario']);
            $cantidad_actual = $oItem->Cantidad;
            $cantidad_final = $cantidad_actual + $cantidad_prod;
            $oItem->Cantidad = number_format($cantidad_final,0,"","");
            $oItem->save();
            unset($oItem);
        } else {
            ## HACER EL REGISTRO

            ## UTILIZANDO EL ARRAY PARA HACER UNA "COPIA" DE LOS REGISTROS ANTERIORES, PERO SE MODIFICAN ALGUNOS CAMPOS.
            $inventario['Id_Bodega'] = 9; // BODEGA VENCIMIENTOS
            $inventario['Cantidad'] = number_format($cantidad_prod,0,"","");
            $inventario['Cantidad_Apartada'] = "0";
            $inventario['Cantidad_Seleccionada'] = "0";
            $inventario['Fecha_Carga'] = date('Y-m-d H:i:s');
            $inventario['Identificacion_Funcionario'] = 1095815196; // IDENTIFICACION DE JHON BACAREO.
            unset($inventario['Id_Inventario']);

            $oItem = new complex('Inventario','Id_Inventario');
            foreach ($inventario as $index => $value) {
                $oItem->$index = $value;
            }
            $oItem->save();
            unset($oItem);
        }

    }

    return true;
}

function existeInventarioEnVencimiento($inventario)
{
    $existe = false;

    $query = "SELECT Id_Inventario FROM Inventario WHERE Id_Producto = $inventario[Id_Producto] AND Lote = '$inventario[Lote]' AND Id_Bodega = 9";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);

    if ($resultado) { // SI DEVUELVE DATOS ES PORQUE SÍ EXISTE ESE REGISTRO EN LA BODEGA DE VENCIMIENTOS.
        $existe = $resultado;
    }

    return $existe;
}

function getInventario($id_bodega)
{
    global $fecha_mes_actual;
    global $fecha_mes_venc;
    
    $query = "SELECT I.*, P.Nombre_Comercial, IF(P.Gravado='Si',19,0) AS Impuesto FROM Inventario I INNER JOIN (SELECT Id_Producto, Nombre_Comercial, Gravado FROM Producto) P ON I.Id_Producto = P.Id_Producto WHERE I.Id_Bodega = $id_bodega AND (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) > 0 AND (I.Fecha_Vencimiento BETWEEN '$fecha_mes_actual' AND '$fecha_mes_venc')";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $inventarios = $oCon->getData();
    unset($oCon);

    return $inventarios;
}


function getAllBodegas()
{
    $query = "SELECT Id_Bodega, Nombre FROM Bodega WHERE Id_Bodega NOT IN (3,4,5,9)"; // Obtener todas las Bodegas excepto ZONA FRANCA, BODEGA CALI, CONTROLADOS y VENCIMIENTOS.

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $bodegas = $oCon->getData();
    unset($oCon);

    return $bodegas;

}

function generarQr($id_remision)
{
    /* AQUI GENERA QR */
    $qr = generarqr('remision',$id_remision,'/IMAGENES/QR/');
    $oItem = new complex("Remision","Id_Remision",$id_remision);
    $oItem->Codigo_Qr=$qr;
    $oItem->save();
    unset($oItem);

    return; 
}

?>