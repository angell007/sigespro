<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');

// include_once('../../../../class/class.querybasedatos.php');
include_once('../../../../class/class.contabilizar.php');

$oItem = new QueryBaseDatos();
$contabilizacion = new Contabilizar(true);

$fecha1 = "2025-11-01";
$fecha2 = "2025-11-15";

// $queryFactura = "SELECT FV.*, D.Id_Regimen FROM Factura FV INNER JOIN (SELECT DIS.Id_Dispensacion, (SELECT Id_Regimen FROM Paciente WHERE Id_Paciente = DIS.Numero_Documento) AS Id_Regimen FROM Dispensacion DIS WHERE DIS.Estado_Facturacion = 'Facturada' AND (DIS.Tipo IS NULL OR DIS.Tipo != 'Capita') AND DIS.Id_Tipo_Servicio != 7 AND DIS.Estado_Dispensacion != 'Anulada' ) D ON FV.Id_Dispensacion = D.Id_Dispensacion WHERE FV.Estado_Factura NOT IN ('Anulada') AND DATE(FV.Fecha_Documento) BETWEEN '$fecha1' AND '$fecha2'";

//$queryFactura = "SELECT FV.*, D.Id_Regimen FROM Factura FV INNER JOIN (SELECT DIS.Id_Dispensacion, (SELECT Id_Regimen FROM Paciente WHERE Id_Paciente = DIS.Numero_Documento) AS Id_Regimen FROM Dispensacion DIS WHERE (DIS.Tipo IS NULL OR DIS.Tipo != 'Capita') AND DIS.Id_Tipo_Servicio != 7 AND DIS.Estado_Dispensacion != 'Anulada' ) D ON FV.Id_Dispensacion = D.Id_Dispensacion WHERE FV.Estado_Factura NOT IN ('Anulada') AND FV.Id_Factura IN (76168,36136,35499,35418,35196,35153,35095,35035)";

 $queryFactura = "SELECT FV.*, T.Id_Movimiento_Contable , D.Id_Punto_Dispensacion
 FROM Factura FV  
 INNER JOIN Dispensacion D ON D.Id_Dispensacion = FV.Id_Dispensacion 
 LEFT JOIN (
            SELECT T1.Id_Registro_Modulo, T1.Numero_Comprobante, T1.Id_Movimiento_Contable 
            FROM Movimiento_Contable T1 
            WHERE Id_Modulo IN (12,13,14,17,19,20)
            AND Estado = 'Activo' 
            AND DATE(Fecha_Movimiento) >= '".$fecha1."'
            GROUP BY T1.Numero_Comprobante) T ON T.Numero_Comprobante = FV.Codigo
            WHERE FV.Estado_Factura NOT IN ('Anulada') AND DATE(FV.Fecha_Documento) >= '".$fecha1."' AND T.Id_Movimiento_Contable IS NULL
            
 ";
 echo $queryFactura; exit;
 
/*  $queryFactura = "SELECT FV.*, T.Id_Movimiento_Contable , D.Id_Punto_Dispensacion
 FROM Factura FV  
 INNER JOIN Dispensacion D ON D.Id_Dispensacion = FV.Id_Dispensacion
 LEFT JOIN (
            SELECT T1.Id_Registro_Modulo, T1.Numero_Comprobante, T1.Id_Movimiento_Contable 
            FROM Movimiento_Contable T1 
            WHERE Id_Modulo IN (12,13,14,17,19,20)
            AND Estado = 'Activo' 
            AND T1.Numero_Comprobante = 'FENP31533'
            GROUP BY T1.Numero_Comprobante) T ON T.Numero_Comprobante = FV.Codigo
            WHERE FV.Estado_Factura NOT IN ('Anulada') AND FV.Id_Factura = 165249 AND T.Id_Movimiento_Contable IS NULL
 
 ";*/
$oItem->SetQuery($queryFactura);
$facturas = $oItem->ExecuteQuery('Multiple');


$i=0;
foreach ($facturas as $factura) { $i++;
 
    $queryMC = "SELECT * FROM Movimiento_Contable MC WHERE MC.Id_Modulo IN (12,13,14,17,19,20) AND MC.Id_Registro_Modulo=".$factura["Id_Factura"];
    $oItem = new QueryBaseDatos();
    $oItem->SetQuery($queryMC);
    $mc = $oItem->ExecuteQuery('Multiple');
    unset($oItem);
    var_dump($mc);
    echo "<br><br>";
    if($mc){ 
    $query2='UPDATE Movimiento_Contable
    SET Documento = "'.$factura["Codigo"].'", Numero_Comprobante="'.$factura["Codigo"].'", Fecha_Movimiento = "'.$factura["Fecha_Documento"].'"
    WHERE Id_Registro_Modulo='.$factura["Id_Factura"];
    $oCon= new consulta();
    $oCon->setQuery($query2);     
    $oCon->createData();     
    unset($oCon);
    }
    
    echo $i."------";
    echo $factura['Codigo']." - ".$factura["Fecha_Documento"];
    //var_dump($mc);
    echo "------<br><br>";

    $datos_movimiento_contable['Id_Registro'] = $factura['Id_Factura'];
    $datos_movimiento_contable['Nit'] = $factura['Id_Cliente'];
    $datos_movimiento_contable['Id_Regimen'] = $factura['Id_Regimen'];
    $datos_movimiento_contable['Id_Punto_Dispensacion'] = $factura['Id_Punto_Dispensacion'];
    
    $contabilizacion->CrearMovimientoContable('Factura',$datos_movimiento_contable);
  
   //exit;

}

echo "Finalizo";


?>