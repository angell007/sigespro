<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');

// include_once('../../../../class/class.querybasedatos.php');


include_once('../../../../class/class.contabilizar.php');
$oItem = new QueryBaseDatos();
$contabilizacion = new Contabilizar(true);

$fecha1 = "2020-10-15";
$fecha2 = "2020-10-15";


$queryFactura = "SELECT FV.* , T.Id_Movimiento_Contable
 FROM Factura_Capita FV  
 LEFT JOIN (
            SELECT T1.Id_Registro_Modulo, T1.Numero_Comprobante, T1.Id_Movimiento_Contable 
            FROM Movimiento_Contable T1 
            WHERE Id_Modulo IN (3)
            AND Estado = 'Activo' 
            AND DATE(Fecha_Movimiento) BETWEEN '".$fecha1." 00:00:00' AND '".$fecha2." 23:59:59' 
            GROUP BY T1.Numero_Comprobante) T ON T.Numero_Comprobante = FV.Codigo

WHERE FV.Estado_Factura NOT IN ('Anulada') AND FV.Fecha_Documento BETWEEN '".$fecha1." 00:00:00' AND '".$fecha2." 23:59:59' AND T.Id_Movimiento_Contable IS NULL
 
 ";

$oItem->SetQuery($queryFactura);
$facturas = $oItem->ExecuteQuery('Multiple');

foreach ($facturas as $factura) {
	
  
  	

    $queryFactura = "SELECT 
    		(Cantidad * Precio) as Subtotal 
            FROM Descripcion_Factura_Capita
            WHERE Id_Factura_Capita = $factura[Id_Factura_Capita] ";

    $oItem->SetQuery($queryFactura);
    $Subtotal = $oItem->ExecuteQuery('Multiple');
 
	$Subtotal = $Subtotal[0]['Subtotal'];
   
  
    echo $factura["Codigo"]."<br>";
    $datos_movimiento_contable['Id_Registro'] = $factura['Id_Factura_Capita'];
    $datos_movimiento_contable['Id_Departamento'] = $factura['Id_Departamento'];
    $datos_movimiento_contable['Cuota'] = $factura['Cuota_Moderadora'];
    $datos_movimiento_contable['Subtotal'] = $Subtotal;
    $datos_movimiento_contable['Nit'] = $factura['Id_Cliente'];
	 
  	//$contabilizacion = new Contabilizar(true);

   //$contabilizacion->CrearMovimientoContable('Factura Capita',$datos_movimiento_contable);
}

echo "Finalizó";


?>