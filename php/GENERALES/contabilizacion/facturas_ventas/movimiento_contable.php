<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');

// include_once('../../../../class/class.querybasedatos.php');
include_once('../../../../class/class.contabilizar.php');

$oItem = new QueryBaseDatos();
$contabilizacion = new Contabilizar(true);

//$fecha1 = "2019-05-01";
//$fecha2 = "2019-05-09";

$fecha1 = "2020-07-22";
$fecha2 = "2019-05-09";
// $queryFactura = "SELECT * FROM Factura_Venta FV WHERE FV.Estado NOT IN ('Anulada') AND DATE(FV.Fecha_Documento) BETWEEN '$fecha1' AND '$fecha2'";
/*$queryFactura = "SELECT * FROM Factura_Venta FV WHERE FV.Estado NOT IN ('Anulada') AND FV.Id_Cliente IN (900341526,900470642)";*/
//$queryFactura = "SELECT * FROM Factura_Venta FV WHERE Id_Factura_Venta=1697";

$queryFactura = "SELECT FV.*, T.Id_Movimiento_Contable
 FROM Factura_Venta FV 
 LEFT JOIN (
            SELECT T1.Id_Registro_Modulo, T1.Numero_Comprobante, T1.Id_Movimiento_Contable 
            FROM Movimiento_Contable T1 
            WHERE Id_Modulo IN (2) 
            AND Estado = 'Activo' 
            AND DATE(Fecha_Movimiento) BETWEEN '2020-07-22' AND '2020-08-20' 
            GROUP BY T1.Numero_Comprobante) T ON T.Numero_Comprobante = FV.Codigo
 WHERE FV.Estado NOT IN ('Anulada') AND DATE(FV.Fecha_Documento) BETWEEN '2020-07-22' AND '2020-08-20' AND T.Id_Movimiento_Contable IS NULL";



$oItem->SetQuery($queryFactura);
$facturas = $oItem->ExecuteQuery('Multiple');


//$oItem->SetQuery($queryFactura);
//$facturas = $oItem->ExecuteQuery('Multiple');

 //echo "<pre>";
//var_dump($facturas);
//echo json_encode($facturas);
//echo "</pre>";




$i=0;
foreach ($facturas as $factura) { $i++;
    $query = "SELECT * FROM Producto_Factura_Venta WHERE Id_Factura_Venta = $factura[Id_Factura_Venta]";
    $oItem = new QueryBaseDatos();
    $oItem->SetQuery($query);
    $resultado = $oItem->ExecuteQuery('Multiple');
    unset($oItem);
    //---------------------
    
    
    $queryMC = "SELECT * FROM Movimiento_Contable MC WHERE MC.Id_Modulo IN (2) AND MC.Id_Registro_Modulo=".$factura["Id_Factura_Venta"];
    $oItem = new QueryBaseDatos();
    $oItem->SetQuery($queryMC);
    $mc = $oItem->ExecuteQuery('Multiple');
    unset($oItem);
    
    if($mc){ 
   
        $query2='UPDATE Movimiento_Contable
            SET Documento = "'.$factura["Codigo"].'", Numero_Comprobante="'.$factura["Codigo"].'", Fecha_Movimiento = "'.$factura["Fecha_Documento"].'"
            WHERE Id_Registro_Modulo='.$factura["Id_Factura_Venta"];
        $oCon= new consulta();
        $oCon->setQuery($query2); 
        
        $oCon->createData();     
        unset($oCon);
        
        echo "<br> Actualizado---";
        echo json_encode($mc);
        echo "<br> <br>";
    }
    
    
    //----------------------
    
    
    echo $i."------";
    echo $factura['Codigo']." - "; //.$factura["Id_Movimiento_Contable"];
    echo "------<br><br>";
    
    $datos_movimiento_contable['Id_Registro'] = $factura['Id_Factura_Venta'];
    $datos_movimiento_contable['Nit'] = $factura['Id_Cliente'] ? $factura['Id_Cliente']  : $factura['Id_Cliente2']  ;
    $datos_movimiento_contable['Productos'] = $resultado;

   
   $contabilizacion->CrearMovimientoContable('Factura Venta',$datos_movimiento_contable);
}

echo "Finalizó";


?>