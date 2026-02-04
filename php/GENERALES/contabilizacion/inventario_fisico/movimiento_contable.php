<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');

// include_once('../../../../class/class.querybasedatos.php');
include_once('../../../../class/class.contabilizar.php');

$oItem = new QueryBaseDatos();
$contabilizacion = new Contabilizar(true);

$fecha1 = "2019-06-01";
$fecha2 = "2019-06-28";

$queryFactura = "SELECT * FROM Inventario_Fisico I WHERE Estado = 'Terminado' AND DATE(I.Fecha_Fin) BETWEEN '$fecha1' AND '$fecha2'";

$oItem->SetQuery($queryFactura);
$inventarios = $oItem->ExecuteQuery('Multiple');

/* echo "<pre>";
var_dump($inventarios);
echo "</pre>";
exit; */

foreach ($inventarios as $inv) {
    $query = "SELECT PI.*, P.Gravado FROM Producto_Inventario_Fisico PI INNER JOIN Producto P ON PI.Id_Producto = P.Id_Producto WHERE PI.Id_Inventario_Fisico = $inv[Id_Inventario_Fisico]";

    $oItem->SetQuery($query);
    $resultado = $oItem->ExecuteQuery('Multiple');

    foreach ($resultado as $i => $value) {
        $resultado[$i]['Cantidad_Final'] = $value['Segundo_Conteo'];
    }

    $datos_movimiento_contable['Id_Registro'] = $inv['Id_Inventario_Fisico'];
    $datos_movimiento_contable['Nit'] = 804016084;
    $datos_movimiento_contable['Productos'] = $resultado;
    
    // var_dump($datos_movimiento_contable);

    $contabilizacion->CrearMovimientoContable('Inventario Fisico',$datos_movimiento_contable);
}

echo "Finalizó";


?>