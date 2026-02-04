<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');

// include_once('../../../../class/class.querybasedatos.php');
include_once('../../../../class/class.contabilizar.php');

$oItem = new QueryBaseDatos();
$contabilizacion = new Contabilizar(true);

$fecha1 = "2019-09-01";
$fecha2 = "2019-09-30";

// $queryFactura = "SELECT * FROM Nota_Credito NC WHERE NC.Estado = 'Aprobada' AND DATE(NC.Fecha) BETWEEN '$fecha1' AND '$fecha2'";
$queryFactura = "SELECT * FROM Nota_Credito NC WHERE Id_Nota_Credito = 193";

$oItem->SetQuery($queryFactura);
$notas = $oItem->ExecuteQuery('Multiple');

/* echo "<pre>";
var_dump($notas);
echo "</pre>";
exit; */

foreach ($notas as $nota) {
    $query = "SELECT * FROM Producto_Nota_Credito WHERE Id_Nota_Credito = $nota[Id_Nota_Credito]";

    $oItem->SetQuery($query);
    $resultado = $oItem->ExecuteQuery('Multiple');

    $datos_movimiento_contable['Id_Registro'] = $nota['Id_Nota_Credito'];
    $datos_movimiento_contable['Nit'] = $nota['Id_Cliente'];
    $datos_movimiento_contable['Productos'] = $resultado;

    $contabilizacion->CrearMovimientoContable('Nota Credito',$datos_movimiento_contable);
}

echo "Finalizó";


?>