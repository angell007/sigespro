<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');

// include_once('../../../../class/class.querybasedatos.php');
include_once('../../../../class/class.contabilizar.php');

$oItem = new QueryBaseDatos();
$contabilizacion = new Contabilizar(true);

$fecha1 = "2019-01-01";
$fecha2 = "2019-05-28";

// $queryFactura = "SELECT * FROM Nacionalizacion_Parcial N WHERE N.Estado = 'Nacionalizado' AND DATE(A.Fecha_Registro) BETWEEN '$fecha1' AND '$fecha2'";
$queryFactura = "SELECT * FROM Acta_Recepcion_Internacional A WHERE A.Estado = 'Recibida'";

$oItem->SetQuery($queryFactura);
$actas = $oItem->ExecuteQuery('Multiple');
$productos_lotes = [];

foreach ($actas as $acta) {
    $productos = getProductosActas($acta['Id_Acta_Recepcion_Internacional']);

    $productos_lotes[] = ["Producto_Lotes" => $productos];

    $datos_movimiento_contable['Modelo'] = $acta;
    $datos_movimiento_contable['Productos'] = $productos_lotes;
    $datos_movimiento_contable['Id_Registro'] = $acta['Id_Acta_Recepcion_Internacional'];

    $contabilizacion->CrearMovimientoContable('Acta Internacional', $datos_movimiento_contable);

    $productos_lotes = [];
}

echo "Finalizó";


function getProductosActas($id_acta) {
    global $oItem;

    $query = "SELECT * FROM Producto_Acta_Recepcion_Internacional WHERE Id_Acta_Recepcion_Internacional = $id_acta";

    $oItem->SetQuery($query);
    $productos = $oItem->ExecuteQuery('Multiple');

    return $productos;
}


?>