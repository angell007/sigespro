<?php
ini_set('memory_limit', '2048M');
set_time_limit(0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set("America/Bogota");

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Estado de Cartera Proveedores.xls"');
header('Cache-Control: max-age=0'); 

$objPHPExcel = new PHPExcel;

$condicion = '';
$condicion2 = '';

if (isset($_REQUEST['Fechas']) && $_REQUEST['Fechas'] != "" && $_REQUEST['Fechas'] != "undefined") {
	$fecha = $_REQUEST['Fechas'];
	$condicion .= " AND (DATE(AR.Fecha_Creacion) BETWEEN '2019-01-01' AND '$fecha')";
	// $condicion .= " AND (DATE(AR.Fecha_Creacion) <= '$fecha')";
	$condicion2 .= " AND (DATE(FP.Fecha_Factura) <= '$fecha')";
}

if (isset($_REQUEST['proveedor']) && $_REQUEST['proveedor'] != '') {
	$condicion .= " AND AR.Id_Proveedor = $_REQUEST[proveedor]";
	$condicion2 .= " AND FP.Nit_Proveedor = $_REQUEST[proveedor]";
}


$query = '
SELECT
r.Id_Proveedor,
r.Nombre,
SUM(r.Saldo) AS Saldo,
SUM(r.Sin_Vencer) AS Sin_Vencer,
SUM(r.first_thirty_days) AS first_thirty_days,
SUM(r.thirtyone_sixty) AS thirtyone_sixty,
SUM(r.sixtyone_ninety) AS sixtyone_ninety,
SUM(r.ninetyone_onehundeight) AS ninetyone_onehundeight,
SUM(r.onehundeight_threehundsix) AS onehundeight_threehundsix,
SUM(r.mayor_threehundsix) AS mayor_threehundsix
FROM
(
(SELECT
P.Id_Proveedor, P.Nombre, (SELECT IFNULL(ROUND(SUM(IF(PAR.Impuesto!=0, (PAR.Subtotal*(1+(PAR.Impuesto/100))), PAR.Subtotal)),2),0) FROM Producto_Acta_Recepcion PAR INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion INNER JOIN Factura_Acta_Recepcion FAR ON PAR.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND FAR.Factura = PAR.Factura WHERE FAR.Estado = "Pendiente" AND AR.Id_Proveedor = P.Id_Proveedor '.$condicion.') AS Saldo,

(SELECT IFNULL(ROUND(SUM(IF(PAR.Impuesto!=0, (PAR.Subtotal*(1+(PAR.Impuesto/100))), PAR.Subtotal)),2),0) FROM Producto_Acta_Recepcion PAR INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion INNER JOIN Factura_Acta_Recepcion FAR ON PAR.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND FAR.Factura = PAR.Factura WHERE FAR.Estado = "Pendiente" AND AR.Id_Proveedor = P.Id_Proveedor AND IFNULL(IF(P.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(FAR.Fecha_Factura)) > P.Condicion_Pago, DATEDIFF(CURDATE(), DATE(FAR.Fecha_Factura)) - P.Condicion_Pago, 0), 0),0) = 0 '.$condicion.') AS Sin_Vencer,

(SELECT IFNULL(ROUND(SUM(IF(PAR.Impuesto!=0, (PAR.Subtotal*(1+(PAR.Impuesto/100))), PAR.Subtotal)),2),0) FROM Producto_Acta_Recepcion PAR INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion INNER JOIN Factura_Acta_Recepcion FAR ON PAR.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND FAR.Factura = PAR.Factura WHERE FAR.Estado = "Pendiente" AND AR.Id_Proveedor = P.Id_Proveedor AND IFNULL(IF(P.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(FAR.Fecha_Factura)) > P.Condicion_Pago, DATEDIFF(CURDATE(), DATE(FAR.Fecha_Factura)) - P.Condicion_Pago, 0), 0),0) BETWEEN 1 AND 30 '.$condicion.') AS first_thirty_days,

(SELECT IFNULL(ROUND(SUM(IF(PAR.Impuesto!=0, (PAR.Subtotal*(1+(PAR.Impuesto/100))), PAR.Subtotal)),2),0) FROM Producto_Acta_Recepcion PAR INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion INNER JOIN Factura_Acta_Recepcion FAR ON PAR.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND FAR.Factura = PAR.Factura WHERE FAR.Estado = "Pendiente" AND AR.Id_Proveedor = P.Id_Proveedor AND IFNULL(IF(P.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(FAR.Fecha_Factura)) > P.Condicion_Pago, DATEDIFF(CURDATE(), DATE(FAR.Fecha_Factura)) - P.Condicion_Pago, 0), 0),0) BETWEEN 31 AND 60 '.$condicion.') AS thirtyone_sixty,

(SELECT IFNULL(ROUND(SUM(IF(PAR.Impuesto!=0, (PAR.Subtotal*(1+(PAR.Impuesto/100))), PAR.Subtotal)),2),0) FROM Producto_Acta_Recepcion PAR INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion INNER JOIN Factura_Acta_Recepcion FAR ON PAR.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND FAR.Factura = PAR.Factura WHERE FAR.Estado = "Pendiente" AND AR.Id_Proveedor = P.Id_Proveedor AND IFNULL(IF(P.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(FAR.Fecha_Factura)) > P.Condicion_Pago, DATEDIFF(CURDATE(), DATE(FAR.Fecha_Factura)) - P.Condicion_Pago, 0), 0),0) BETWEEN 61 AND 90 '.$condicion.') AS sixtyone_ninety,

(SELECT IFNULL(ROUND(SUM(IF(PAR.Impuesto!=0, (PAR.Subtotal*(1+(PAR.Impuesto/100))), PAR.Subtotal)),2),0) FROM Producto_Acta_Recepcion PAR INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion INNER JOIN Factura_Acta_Recepcion FAR ON PAR.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND FAR.Factura = PAR.Factura WHERE FAR.Estado = "Pendiente" AND AR.Id_Proveedor = P.Id_Proveedor AND IFNULL(IF(P.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(FAR.Fecha_Factura)) > P.Condicion_Pago, DATEDIFF(CURDATE(), DATE(FAR.Fecha_Factura)) - P.Condicion_Pago, 0), 0),0) BETWEEN 91 AND 180 '.$condicion.') AS ninetyone_onehundeight,

(SELECT IFNULL(ROUND(SUM(IF(PAR.Impuesto!=0, (PAR.Subtotal*(1+(PAR.Impuesto/100))), PAR.Subtotal)),2),0) FROM Producto_Acta_Recepcion PAR INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion INNER JOIN Factura_Acta_Recepcion FAR ON PAR.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND FAR.Factura = PAR.Factura WHERE FAR.Estado = "Pendiente" AND AR.Id_Proveedor = P.Id_Proveedor AND IFNULL(IF(P.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(FAR.Fecha_Factura)) > P.Condicion_Pago, DATEDIFF(CURDATE(), DATE(FAR.Fecha_Factura)) - P.Condicion_Pago, 0), 0),0) BETWEEN 181 AND 360 '.$condicion.') AS onehundeight_threehundsix,

(SELECT IFNULL(ROUND(SUM(IF(PAR.Impuesto!=0, (PAR.Subtotal*(1+(PAR.Impuesto/100))), PAR.Subtotal)),2),0) FROM Producto_Acta_Recepcion PAR INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion INNER JOIN Factura_Acta_Recepcion FAR ON PAR.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND FAR.Factura = PAR.Factura WHERE FAR.Estado = "Pendiente" AND AR.Id_Proveedor = P.Id_Proveedor AND IFNULL(IF(P.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(FAR.Fecha_Factura)) > P.Condicion_Pago, DATEDIFF(CURDATE(), DATE(FAR.Fecha_Factura)) - P.Condicion_Pago, 0), 0),0) > 360 '.$condicion.') AS mayor_threehundsix
FROM
Proveedor P
INNER JOIN Acta_Recepcion AR
ON P.Id_Proveedor = AR.Id_Proveedor
INNER JOIN Factura_Acta_Recepcion FAR
ON AR.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion
WHERE FAR.Estado = "Pendiente"
'.$condicion.'
GROUP BY P.Id_Proveedor)
/*UNION ALL (
	SELECT
	P.Id_Proveedor,
	P.Nombre,
	SUM(FP.Saldo) AS Saldo,
	(SELECT IFNULL(SUM(FP2.Saldo),0) FROM Facturas_Proveedor_Mantis FP2 WHERE FP2.Estado = "Pendiente" AND FP2.Nit_Proveedor = P.Id_Proveedor AND IFNULL(IF(P.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(FP2.Fecha_Factura)) > P.Condicion_Pago, DATEDIFF(CURDATE(), DATE(FP2.Fecha_Factura)) - P.Condicion_Pago, 0), 0),0) = 0 '.$condicion2.') AS Sin_Vencer,

	(SELECT IFNULL(SUM(FP2.Saldo),0) FROM Facturas_Proveedor_Mantis FP2 WHERE FP2.Estado = "Pendiente" AND FP2.Nit_Proveedor = P.Id_Proveedor AND IFNULL(IF(P.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(FP2.Fecha_Factura)) > P.Condicion_Pago, DATEDIFF(CURDATE(), DATE(FP2.Fecha_Factura)) - P.Condicion_Pago, 0), 0),0) BETWEEN 1 AND 30 '.$condicion2.') AS first_thirty_days,

	(SELECT IFNULL(SUM(FP2.Saldo),0) FROM Facturas_Proveedor_Mantis FP2 WHERE FP2.Estado = "Pendiente" AND FP2.Nit_Proveedor = P.Id_Proveedor AND IFNULL(IF(P.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(FP2.Fecha_Factura)) > P.Condicion_Pago, DATEDIFF(CURDATE(), DATE(FP2.Fecha_Factura)) - P.Condicion_Pago, 0), 0),0) BETWEEN 31 AND 60 '.$condicion2.') AS thirtyone_sixty,

	(SELECT IFNULL(SUM(FP2.Saldo),0) FROM Facturas_Proveedor_Mantis FP2 WHERE FP2.Estado = "Pendiente" AND FP2.Nit_Proveedor = P.Id_Proveedor AND IFNULL(IF(P.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(FP2.Fecha_Factura)) > P.Condicion_Pago, DATEDIFF(CURDATE(), DATE(FP2.Fecha_Factura)) - P.Condicion_Pago, 0), 0),0) BETWEEN 61 AND 90 '.$condicion2.') AS sixtyone_ninety,

	(SELECT IFNULL(SUM(FP2.Saldo),0) FROM Facturas_Proveedor_Mantis FP2 WHERE FP2.Estado = "Pendiente" AND FP2.Nit_Proveedor = P.Id_Proveedor AND IFNULL(IF(P.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(FP2.Fecha_Factura)) > P.Condicion_Pago, DATEDIFF(CURDATE(), DATE(FP2.Fecha_Factura)) - P.Condicion_Pago, 0), 0),0) BETWEEN 91 AND 180 '.$condicion2.') AS ninetyone_onehundeight,

	(SELECT IFNULL(SUM(FP2.Saldo),0) FROM Facturas_Proveedor_Mantis FP2 WHERE FP2.Estado = "Pendiente" AND FP2.Nit_Proveedor = P.Id_Proveedor AND IFNULL(IF(P.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(FP2.Fecha_Factura)) > P.Condicion_Pago, DATEDIFF(CURDATE(), DATE(FP2.Fecha_Factura)) - P.Condicion_Pago, 0), 0),0) BETWEEN 181 AND 360 '.$condicion2.') AS onehundeight_threehundsix,

	(SELECT IFNULL(SUM(FP2.Saldo),0) FROM Facturas_Proveedor_Mantis FP2 WHERE FP2.Estado = "Pendiente" AND FP2.Nit_Proveedor = P.Id_Proveedor AND IFNULL(IF(P.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(FP2.Fecha_Factura)) > P.Condicion_Pago, DATEDIFF(CURDATE(), DATE(FP2.Fecha_Factura)) - P.Condicion_Pago, 0), 0),0) > 360 '.$condicion2.') AS mayor_threehundsix
	FROM
	Proveedor P
	INNER JOIN Facturas_Proveedor_Mantis FP
	ON P.Id_Proveedor = FP.Nit_Proveedor
	WHERE FP.Estado = "Pendiente"
	'.$condicion2.'
	GROUP BY P.Id_Proveedor
)*/
) r
GROUP BY r.Id_Proveedor
';


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$edades= $oCon->getData();
unset($oCon);

$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Reporte Dispensacion');

$objSheet->getCell('A1')->setValue("NIT PROVEEDOR");
$objSheet->getCell('B1')->setValue("RAZON SOCIAL");
$objSheet->getCell('C1')->setValue("SALDO");
$objSheet->getCell('D1')->setValue("SIN VENCER");
$objSheet->getCell('E1')->setValue("1 - 30");
$objSheet->getCell('F1')->setValue("31 - 60");
$objSheet->getCell('G1')->setValue("61 - 90");
$objSheet->getCell('H1')->setValue("91 - 180");
$objSheet->getCell('I1')->setValue("181 - 360");
$objSheet->getCell('J1')->setValue("MAYOR DE 360");
$objSheet->getStyle('A1:J1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:J1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:J1')->getFont()->setBold(true);
$objSheet->getStyle('A1:J1')->getFont()->getColor()->setARGB('FFFFFFFF');


$j=1;
foreach($edades as $value){ $j++;
	$objSheet->getCell('A'.$j)->setValue($value["Id_Proveedor"]);
	$objSheet->getCell('B'.$j)->setValue($value["Nombre"]);
	$objSheet->getCell('C'.$j)->setValue($value["Saldo"]);
	$objSheet->getCell('D'.$j)->setValue($value["Sin_Vencer"]);
	$objSheet->getCell('E'.$j)->setValue($value["first_thirty_days"]);
	$objSheet->getCell('F'.$j)->setValue($value["thirtyone_sixty"]);
	$objSheet->getCell('G'.$j)->setValue($value["sixtyone_ninety"]);
	$objSheet->getCell('H'.$j)->setValue($value["ninetyone_onehundeight"]);
	$objSheet->getCell('I'.$j)->setValue($value["onehundeight_threehundsix"]);
	$objSheet->getCell('J'.$j)->setValue($value["mayor_threehundsix"]);
}
$objSheet->getColumnDimension('A')->setAutoSize(true);
$objSheet->getColumnDimension('B')->setAutoSize(true);
$objSheet->getColumnDimension('C')->setAutoSize(true);
$objSheet->getColumnDimension('D')->setAutoSize(true);
$objSheet->getColumnDimension('E')->setAutoSize(true);
$objSheet->getColumnDimension('F')->setAutoSize(true);
$objSheet->getColumnDimension('G')->setAutoSize(true);
$objSheet->getColumnDimension('H')->setAutoSize(true);
$objSheet->getColumnDimension('I')->setAutoSize(true);
$objSheet->getColumnDimension('J')->setAutoSize(true);
$objSheet->getStyle('A1:J'.$j)->getAlignment()->setWrapText(true);


$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');



?>