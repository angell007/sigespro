<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../../class/class.querybasedatos.php');
require_once('../../../../class/class.contabilizar.php');
require_once('../../../../class/class.complex.php');

$oItem = new QueryBaseDatos();

$queryFactura = "SELECT D.*,13678 as acta  FROM Devolucion_Compra D WHERE D.Id_Devolucion_Compra IN (602)";

$oItem->SetQuery($queryFactura);
$actas = $oItem->ExecuteQuery('Multiple');

// echo json_encode($actas); 
    
// exit;

foreach ($actas as $cont => $acta) {
    
    $contabilizacion = new Contabilizar(true);
    $idDevolucionCompra = $acta['Id_Devolucion_Compra'];

    $datos = new complex('Devolucion_Compra','Id_Devolucion_Compra', $idDevolucionCompra );
    $datos = $datos->getData();
    $datos['acta'] = $acta['acta'];
    
    $queryProds = "SELECT PAC.*, PD.*
					
			FROM Producto_Devolucion_Compra PD
			INNER JOIN Producto_Acta_Recepcion PAC ON PAC.Id_Producto = PD.Id_Producto and PAC.Id_Acta_Recepcion =  $acta[acta]
			 WHERE PD.Id_Devolucion_Compra= $idDevolucionCompra";
    $oItem->SetQuery($queryProds);
    $productos = $oItem->ExecuteQuery('Multiple');
    


    $datos_movimientos['Id_Registro'] = $idDevolucionCompra;
    $datos_movimientos['datos'] = $datos;
    $datos_movimientos['productos'] = $productos;
    
    // echo json_encode($datos_movimientos); exit;
    $contabilizacion->CrearMovimientoContable('Devolucion Acta', $datos_movimientos);

    echo "$acta[Codigo] <br>";
}

