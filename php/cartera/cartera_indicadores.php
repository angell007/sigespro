<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = "SELECT
COUNT(*) AS Pendientes,
SUM(r.Neto_Factura) AS TOTAL
FROM
(
SELECT 
DATE(MC.Fecha_Movimiento) AS Fecha_Factura,
MC.Documento AS Factura,
(CASE PC.Naturaleza
        WHEN 'C' THEN (SUM(MC.Haber))
        ELSE (SUM(MC.Debe))
    END) AS Total_Compra,
(CASE PC.Naturaleza
	WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
	ELSE (SUM(MC.Debe) - SUM(MC.Haber))
END) AS Neto_Factura,
PC.Naturaleza AS Nat,
MC.Nit,
IF(CONCAT_WS(' ',
			C.Primer_Nombre,
			C.Segundo_Nombre,
			C.Primer_Apellido,
			C.Segundo_Apellido) != '',
	CONCAT_WS(' ',
			C.Primer_Nombre,
			C.Segundo_Nombre,
			C.Primer_Apellido,
			C.Segundo_Apellido),
	C.Razon_Social) AS Nombre_Cliente,
DATE_ADD(DATE(MC.Fecha_Movimiento), INTERVAL IF(C.Condicion_Pago IN (0,1),0,C.Condicion_Pago) DAY) AS Fecha_Vencimiento,
IF(C.Condicion_Pago IN (0,1),0,C.Condicion_Pago) AS Condicion_Pago,

IF(C.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago, DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago, 0), 0) AS Dias_Mora
	
FROM
Movimiento_Contable MC
	INNER JOIN
Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
	INNER JOIN
Cliente C ON C.Id_Cliente = MC.Nit
WHERE
MC.Estado != 'Anulado'
	AND Id_Plan_Cuenta = 57
GROUP BY MC.Id_Plan_Cuenta , MC.Documento, MC.Nit
HAVING Neto_Factura != 0
) r";

$oCon= new consulta();
//$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$facturas = $oCon->getData();
unset($oCon);


$datos['Total']=number_format($facturas['TOTAL'],2,".",",");
$datos['Pendientes']=$facturas['Pendientes'];

echo json_encode($datos);


?>