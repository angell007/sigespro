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

$queryFactura = "SELECT * FROM Ajuste_Individual A WHERE A.Estado != 'Anulada' AND DATE(A.Fecha) BETWEEN '$fecha1' AND '$fecha2'";

$oItem->SetQuery($queryFactura);
$ajustes = $oItem->ExecuteQuery('Multiple');

/* echo "<pre>";
var_dump($ajustes);
echo "</pre>";
exit; */

foreach ($ajustes as $ajuste) {
    $query = "SELECT * FROM Producto_Ajuste_Individual WHERE Id_Ajuste_Individual = $ajuste[Id_Ajuste_Individual]";

    $oItem->SetQuery($query);
    $resultado = $oItem->ExecuteQuery('Multiple');

    // var_dump($resultado);

    $datos_movimiento_contable['Id_Registro'] = $ajuste['Id_Ajuste_Individual'];
    $datos_movimiento_contable['Nit'] = 804016084;
    $datos_movimiento_contable['Tipo'] = $ajuste['Tipo'];
    $datos_movimiento_contable['Clase_Ajuste'] = $ajuste['Id_Clase_Ajuste_Individual'];
    $datos_movimiento_contable['Productos'] = $resultado;
    
    // var_dump($datos_movimiento_contable);

    $contabilizacion->CrearMovimientoContable('Ajuste Individual',$datos_movimiento_contable);
}

echo "Finalizó";


?>