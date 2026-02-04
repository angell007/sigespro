<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');

// include_once('../../../../class/class.querybasedatos.php');
include_once('../../../../class/class.contabilizar_pai.php');

$oItem = new QueryBaseDatos();
$contabilizacion = new ContabilizarPai(true);

$fecha1 = "2019-01-01";
$fecha2 = "2019-05-28";

// $queryFactura = "SELECT * FROM Nacionalizacion_Parcial N WHERE N.Estado = 'Nacionalizado' AND DATE(A.Fecha_Registro) BETWEEN '$fecha1' AND '$fecha2'";
$queryFactura = "SELECT * FROM Nacionalizacion_Parcial N WHERE N.Estado = 'Acomodada' AND Id_Nacionalizacion_Parcial IN (63)";

$oItem->SetQuery($queryFactura);
$parciales = $oItem->ExecuteQuery('Multiple');
//echo json_encode($parciales);exit;

foreach ($parciales as $parcial) {
    $productos = getProductosParciales($parcial['Id_Nacionalizacion_Parcial']);
    $gastos = getGastosParcial($parcial['Id_Nacionalizacion_Parcial']);
    $datos_movimiento_contable['Modelo'] = $parcial;
    $datos_movimiento_contable['Productos'] = $productos;
    $datos_movimiento_contable['Otros_Gastos'] = $gastos;
    $datos_movimiento_contable['Porcentaje_Flete_Internacional'] = $productos[0]['Porcentaje_Flete'];
    $datos_movimiento_contable['Porcentaje_Seguro_Internacional'] = $productos[0]['Porcentaje_Seguro'];
    $datos_movimiento_contable['Tasa_Dolar_Parcial'] = $parcial['Tasa_Cambio'];
    $datos_movimiento_contable['Id_Registro'] = $parcial['Id_Nacionalizacion_Parcial'];
    $contabilizacion->CrearMovimientoContable('Parcial Acta Internacional', $datos_movimiento_contable);
}

echo "Finalizó";



function getProductosParciales($id_parcial) {
    global $oItem;
    $query = "SELECT PNP.*, Precio AS Precio_Dolares, Precio_Unitario_Pesos AS FOT_Pesos, Total_Flete AS Subtotal_Flete, Total_Seguro AS Subtotal_Seguro, Total_Flete_Nacional AS Subtotal_Flete_Nacional, Total_Licencia AS Subtotal_Licencia, Total_Licencia AS Subtotal_Licencia, Total_Arancel AS Valor_Arancel, IF(P.Gravado='Si',19,0) AS Gravado FROM Producto_Nacionalizacion_Parcial PNP INNER JOIN Producto P ON P.Id_Producto = PNP.Id_Producto WHERE Id_Nacionalizacion_Parcial = $id_parcial";

    $oItem->SetQuery($query);
    $productos = $oItem->ExecuteQuery('Multiple');

    return $productos;
}

function getGastosParcial($id_parcial) {
    global $oItem;
    $query = "SELECT * FROM Nacionalizacion_Parcial_Otro_Gasto WHERE Id_Nacionalizacion_Parcial = $id_parcial";

    $oItem->SetQuery($query);
    $gastos = $oItem->ExecuteQuery('Multiple');

    return $gastos;
}

?>