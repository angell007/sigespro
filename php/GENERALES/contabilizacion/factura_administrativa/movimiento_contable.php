<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');

 include_once('../../../class/class.querybasedatos.php');
include_once('../../../class/class.contabilizar.php');

$oItem = new QueryBaseDatos();
$contabilizacion = new Contabilizar(true);

$queryFactura = "SELECT F.*, T.Id_Movimiento_Contable 
FROM Factura_Administrativa F 
LEFT JOIN
    ( SELECT T1.Id_Registro_Modulo, T1.Numero_Comprobante, T1.Id_Movimiento_Contable 
    FROM Movimiento_Contable T1 
    WHERE Id_Modulo IN (35) AND Estado = 'Activo'
    GROUP BY T1.Numero_Comprobante) T ON T.Numero_Comprobante = F.Codigo 
    WHERE F.Estado_Factura NOT IN ('Anulada') AND T.Id_Movimiento_Contable IS NULL";

$oItem->SetQuery($queryFactura);
$facturas = $oItem->ExecuteQuery('Multiple');

/* echo "<pre>";
var_dump($facturas);
echo "</pre>";
exit; */

$i=0;


foreach ($facturas as $factura) { $i++;
 

    $datos_movimiento_contable['Id_Registro'] = $factura['Id_Factura_Administrativa']; 
    $datos_movimiento_contable['Nit'] = $factura['Id_Cliente']; 
 /*    $datos_movimiento_contable['Productos'] = $productos; */
    $contabilizar = new Contabilizar();
    $contabilizar->CrearMovimientoContable('Factura Administrativa', $datos_movimiento_contable);
    unset($contabilizar);

    echo $i."------";
    echo $factura['Codigo'];
    echo "------<br><br>";

}

echo "Finalizó";


?>