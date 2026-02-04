<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = "SELECT 
DATE(MC.Fecha_Movimiento) AS Fecha_Factura,
MC.Documento AS Factura,
(SELECT Archivo_Factura FROM Factura_Acta_Recepcion WHERE Factura = MC.Documento LIMIT 1) AS Archivo_Factura,
(SELECT IFNULL(SUM(PAR.Precio*PAR.Cantidad),0) FROM Producto_Acta_Recepcion PAR  iNNER jOIN Acta_Recepcion AR on AR.Id_Acta_Recepcion=PAR.Id_Acta_Recepcion WHERE PAR.Impuesto = 0 AND PAR.Factura = MC.Documento and AR.Estado!='Anulada') AS Exenta,
(SELECT IFNULL(SUM(PAR.Precio*PAR.Cantidad),0) FROM Producto_Acta_Recepcion  PAR iNNER jOIN Acta_Recepcion AR on AR.Id_Acta_Recepcion=PAR.Id_Acta_Recepcion WHERE PAR.Impuesto != 0 AND PAR.Factura = MC.Documento and AR.Estado!='Anulada') AS Gravada,
(SELECT IFNULL(SUM(PAR.Precio*PAR.Cantidad*(Impuesto/100)),0) FROM Producto_Acta_Recepcion PAR  iNNER jOIN Acta_Recepcion AR on AR.Id_Acta_Recepcion=PAR.Id_Acta_Recepcion WHERE PAR.Impuesto != 0 AND PAR.Factura = MC.Documento and AR.Estado!='Anulada') AS Iva,
(CASE PC.Naturaleza
        WHEN 'C' THEN (SUM(MC.Haber))
        ELSE (SUM(MC.Debe))
    END) AS Total_Compra,
(CASE PC.Naturaleza
	WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
	ELSE (SUM(MC.Debe) - SUM(MC.Haber))
END) AS Neto_Factura,
PC.Naturaleza AS Nat,
DATE_ADD(DATE(MC.Fecha_Movimiento), INTERVAL IF(C.Condicion_Pago IN (0,1),0,C.Condicion_Pago) DAY) AS Fecha_Vencimiento,
IF(C.Condicion_Pago IN (0,1),0,C.Condicion_Pago) AS Condicion_Pago,

-- IF(C.Condicion_Pago > 1,
	IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago, 
		DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
	 0) 
-- 0) 
AS Dias_Mora
FROM
Movimiento_Contable MC
	INNER JOIN
Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
	INNER JOIN
Proveedor C ON C.Id_Proveedor = MC.Nit
WHERE
MC.Estado != 'Anulado'
	AND Id_Plan_Cuenta = 272
	AND MC.Nit = $id
GROUP BY MC.Id_Plan_Cuenta , MC.Documento
HAVING Neto_Factura != 0
ORDER BY MC.Fecha_Movimiento DESC
";

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

$i=-1;
foreach ($resultado  as $value) {$i++;
   $resultado[$i]['RetencionesFacturas']=[];
}

$query='SELECT * FROM Proveedor WHERE Id_Proveedor='.$id;
$oCon= new consulta();
$oCon->setQuery($query);
$proveedor = $oCon->getData();
unset($oCon);

$datos['Facturas']=$resultado;
$datos['Proveedor']=$proveedor;


echo json_encode($datos);


?>