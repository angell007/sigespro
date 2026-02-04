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
header('Content-Disposition: attachment;filename="Estado de Cartera Clientes.xls"');
header('Cache-Control: max-age=0'); 

$objPHPExcel = new PHPExcel;

$condiciones = SetCondiciones();


$query = '
SELECT
C.Id_Cliente, C.Nombre, SUM(R.Saldo) AS Saldo, SUM(R.Sin_Vencer) AS Sin_Vencer, SUM(R.first_thirty_days) AS first_thirty_days, SUM(R.thirtyone_sixty) AS thirtyone_sixty, SUM(R.sixtyone_ninety) AS sixtyone_ninety, SUM(R.ninetyone_onehundeight) AS ninetyone_onehundeight,
SUM(R.onehundeight_threehundsix) AS onehundeight_threehundsix, SUM(R.mayor_threehundsix) AS mayor_threehundsix
FROM
(
	(SELECT F.Id_Cliente
	, (SELECT SUM(IF(PFV.Impuesto!=0, (PFV.Subtotal*(1+PFV.Impuesto/100)), PFV.Subtotal)) FROM Producto_Factura_Venta PFV INNER JOIN Factura_Venta F2 ON PFV.Id_Factura_Venta = F2.Id_Factura_Venta WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado = "Pendiente" '.$condiciones["condicion"].') AS Saldo,

	(SELECT IFNULL(SUM(IF(PFV.Impuesto!=0, (PFV.Subtotal*(1+PFV.Impuesto/100)), PFV.Subtotal)),0) FROM Producto_Factura_Venta PFV INNER JOIN Factura_Venta F2 ON PFV.Id_Factura_Venta = F2.Id_Factura_Venta WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado = "Pendiente" AND IFNULL(IF(F2.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) > F2.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) - F2.Condicion_Pago, 0), 0),0) = 0'.$condiciones["condicion"].') AS Sin_Vencer,

	(SELECT IFNULL(SUM(IF(PFV.Impuesto!=0, (PFV.Subtotal*(1+PFV.Impuesto/100)), PFV.Subtotal)),0) FROM Producto_Factura_Venta PFV INNER JOIN Factura_Venta F2 ON PFV.Id_Factura_Venta = F2.Id_Factura_Venta WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado = "Pendiente" AND IFNULL(IF(F2.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) > F2.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) - F2.Condicion_Pago, 0), 0),0) BETWEEN 1 AND 30 '.$condiciones["condicion"].') AS first_thirty_days,

	(SELECT IFNULL(SUM(IF(PFV.Impuesto!=0, (PFV.Subtotal*(1+PFV.Impuesto/100)), PFV.Subtotal)),0) FROM Producto_Factura_Venta PFV INNER JOIN Factura_Venta F2 ON PFV.Id_Factura_Venta = F2.Id_Factura_Venta WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado = "Pendiente" AND IFNULL(IF(F2.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) > F2.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) - F2.Condicion_Pago, 0), 0),0) BETWEEN 31 AND 60 '.$condiciones["condicion"].') AS thirtyone_sixty,

	(SELECT IFNULL(SUM(IF(PFV.Impuesto!=0, (PFV.Subtotal*(1+PFV.Impuesto/100)), PFV.Subtotal)),0) FROM Producto_Factura_Venta PFV INNER JOIN Factura_Venta F2 ON PFV.Id_Factura_Venta = F2.Id_Factura_Venta WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado = "Pendiente" AND IFNULL(IF(F2.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) > F2.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) - F2.Condicion_Pago, 0), 0),0) BETWEEN 61 AND 90 '.$condiciones["condicion"].') AS sixtyone_ninety,

	(SELECT IFNULL(SUM(IF(PFV.Impuesto!=0, (PFV.Subtotal*(1+PFV.Impuesto/100)), PFV.Subtotal)),0) FROM Producto_Factura_Venta PFV INNER JOIN Factura_Venta F2 ON PFV.Id_Factura_Venta = F2.Id_Factura_Venta WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado = "Pendiente" AND IFNULL(IF(F2.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) > F2.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) - F2.Condicion_Pago, 0), 0),0) BETWEEN 91 AND 180 '.$condiciones["condicion"].') AS ninetyone_onehundeight,

	(SELECT IFNULL(SUM(IF(PFV.Impuesto!=0, (PFV.Subtotal*(1+PFV.Impuesto/100)), PFV.Subtotal)),0) FROM Producto_Factura_Venta PFV INNER JOIN Factura_Venta F2 ON PFV.Id_Factura_Venta = F2.Id_Factura_Venta WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado = "Pendiente" AND IFNULL(IF(F2.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) > F2.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) - F2.Condicion_Pago, 0), 0),0) BETWEEN 181 AND 360 '.$condiciones["condicion"].') AS onehundeight_threehundsix,

	(SELECT IFNULL(SUM(IF(PFV.Impuesto!=0, (PFV.Subtotal*(1+PFV.Impuesto/100)), PFV.Subtotal)),0) FROM Producto_Factura_Venta PFV INNER JOIN Factura_Venta F2 ON PFV.Id_Factura_Venta = F2.Id_Factura_Venta WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado = "Pendiente" AND IFNULL(IF(F2.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) > F2.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) - F2.Condicion_Pago, 0), 0),0) > 360 '.$condiciones["condicion"].') mayor_threehundsix

	FROM Factura_Venta F
	WHERE F.Estado="Pendiente" '.$condiciones["condicion3"].'  GROUP BY F.Id_Cliente)
	UNION(
	SELECT F.Id_Cliente
	, (SELECT ROUND(SUM(IF(PFV.Impuesto!=0, (PFV.Subtotal*(1+PFV.Impuesto/100)), PFV.Subtotal)),2) FROM Producto_Factura PFV INNER JOIN Factura F2 ON PFV.Id_Factura = F2.Id_Factura WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado_Factura = "Sin Cancelar" '.$condiciones["condicion"].') AS Saldo,

	(SELECT ROUND(IFNULL(SUM(IF(PFV.Impuesto!=0, (PFV.Subtotal*(1+PFV.Impuesto/100)), PFV.Subtotal)),0),2) FROM Producto_Factura PFV INNER JOIN Factura F2 ON PFV.Id_Factura = F2.Id_Factura INNER JOIN Cliente C ON F2.Id_Cliente = C.Id_Cliente WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado_Factura = "Sin Cancelar" AND IFNULL(IF(C.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) > C.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) - C.Condicion_Pago, 0), 0),0) = 0 '.$condiciones["condicion"].') AS Sin_Vencer,

	(SELECT IFNULL(SUM(IF(PFV.Impuesto!=0, (PFV.Subtotal*(1+PFV.Impuesto/100)), PFV.Subtotal)),0) FROM Producto_Factura PFV INNER JOIN Factura F2 ON PFV.Id_Factura = F2.Id_Factura INNER JOIN Cliente C ON F2.Id_Cliente = C.Id_Cliente WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado_Factura = "Sin Cancelar" AND IFNULL(IF(C.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) > C.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) - C.Condicion_Pago, 0), 0),0) BETWEEN 1 AND 30 '.$condiciones["condicion"].') AS first_thirty_days,

	(SELECT IFNULL(SUM(IF(PFV.Impuesto!=0, (PFV.Subtotal*(1+PFV.Impuesto/100)), PFV.Subtotal)),0) FROM Producto_Factura PFV INNER JOIN Factura F2 ON PFV.Id_Factura = F2.Id_Factura INNER JOIN Cliente C ON F2.Id_Cliente = C.Id_Cliente WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado_Factura = "Sin Cancelar" AND IFNULL(IF(C.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) > C.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) - C.Condicion_Pago, 0), 0),0) BETWEEN 31 AND 60 '.$condiciones["condicion"].') AS thirtyone_sixty,

	(SELECT IFNULL(SUM(IF(PFV.Impuesto!=0, (PFV.Subtotal*(1+PFV.Impuesto/100)), PFV.Subtotal)),0) FROM Producto_Factura PFV INNER JOIN Factura F2 ON PFV.Id_Factura = F2.Id_Factura INNER JOIN Cliente C ON F2.Id_Cliente = C.Id_Cliente WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado_Factura = "Sin Cancelar" AND IFNULL(IF(C.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) > C.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) - C.Condicion_Pago, 0), 0),0) BETWEEN 61 AND 90 '.$condiciones["condicion"].') AS sixtyone_ninety,

	(SELECT IFNULL(SUM(IF(PFV.Impuesto!=0, (PFV.Subtotal*(1+PFV.Impuesto/100)), PFV.Subtotal)),0) FROM Producto_Factura PFV INNER JOIN Factura F2 ON PFV.Id_Factura = F2.Id_Factura INNER JOIN Cliente C ON F2.Id_Cliente = C.Id_Cliente WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado_Factura = "Sin Cancelar" AND IFNULL(IF(C.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) > C.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) - C.Condicion_Pago, 0), 0),0) BETWEEN 91 AND 180 '.$condiciones["condicion"].') AS ninetyone_onehundeight,

	(SELECT IFNULL(SUM(IF(PFV.Impuesto!=0, (PFV.Subtotal*(1+PFV.Impuesto/100)), PFV.Subtotal)),0) FROM Producto_Factura PFV INNER JOIN Factura F2 ON PFV.Id_Factura = F2.Id_Factura INNER JOIN Cliente C ON F2.Id_Cliente = C.Id_Cliente WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado_Factura = "Sin Cancelar" AND IFNULL(IF(C.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) > C.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) - C.Condicion_Pago, 0), 0),0) BETWEEN 181 AND 360 '.$condiciones["condicion"].') AS onehundeight_threehundsix,

	(SELECT IFNULL(SUM(IF(PFV.Impuesto!=0, (PFV.Subtotal*(1+PFV.Impuesto/100)), PFV.Subtotal)),0) FROM Producto_Factura PFV INNER JOIN Factura F2 ON PFV.Id_Factura = F2.Id_Factura INNER JOIN Cliente C ON F2.Id_Cliente = C.Id_Cliente WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado_Factura = "Sin Cancelar" AND IFNULL(IF(C.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) > C.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) - C.Condicion_Pago, 0), 0),0) > 360 '.$condiciones["condicion"].') mayor_threehundsix

	FROM Factura F
	WHERE F.Estado_Factura="Sin Cancelar" '.$condiciones["condicion3"].' GROUP BY F.Id_Cliente
	)
	UNION
	(
	SELECT F.Id_Cliente
	, (SELECT ROUND(SUM(PFV.Total - F2.Cuota_Moderadora),2) FROM Descripcion_Factura_Capita PFV INNER JOIN Factura_Capita F2 ON PFV.Id_Factura_Capita = F2.Id_Factura_Capita WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado_Factura = "Sin Cancelar" '.$condiciones["condicion"].') AS Saldo,

	(SELECT ROUND(SUM(PFV.Total - F2.Cuota_Moderadora),2) FROM Descripcion_Factura_Capita PFV INNER JOIN Factura_Capita F2 ON PFV.Id_Factura_Capita = F2.Id_Factura_Capita INNER JOIN Cliente C ON F2.Id_Cliente = C.Id_Cliente WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado_Factura = "Sin Cancelar" AND IFNULL(IF(C.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) > C.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) - C.Condicion_Pago, 0), 0),0) = 0 '.$condiciones["condicion"].') AS Sin_Vencer,

	(SELECT IFNULL(ROUND(SUM(PFV.Total - F2.Cuota_Moderadora),2),0) FROM Descripcion_Factura_Capita PFV INNER JOIN Factura_Capita F2 ON PFV.Id_Factura_Capita = F2.Id_Factura_Capita INNER JOIN Cliente C ON F2.Id_Cliente = C.Id_Cliente WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado_Factura = "Sin Cancelar" AND IFNULL(IF(C.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) > C.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) - C.Condicion_Pago, 0), 0),0) BETWEEN 1 AND 30 '.$condiciones["condicion"].') AS first_thirty_days,

	(SELECT IFNULL(ROUND(SUM(PFV.Total - F2.Cuota_Moderadora),2),0) FROM Descripcion_Factura_Capita PFV INNER JOIN Factura_Capita F2 ON PFV.Id_Factura_Capita = F2.Id_Factura_Capita INNER JOIN Cliente C ON F2.Id_Cliente = C.Id_Cliente WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado_Factura = "Sin Cancelar" AND IFNULL(IF(C.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) > C.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) - C.Condicion_Pago, 0), 0),0) BETWEEN 31 AND 60 '.$condiciones["condicion"].') AS thirtyone_sixty,

	(SELECT IFNULL(ROUND(SUM(PFV.Total - F2.Cuota_Moderadora),2),0) FROM Descripcion_Factura_Capita PFV INNER JOIN Factura_Capita F2 ON PFV.Id_Factura_Capita = F2.Id_Factura_Capita INNER JOIN Cliente C ON F2.Id_Cliente = C.Id_Cliente WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado_Factura = "Sin Cancelar" AND IFNULL(IF(C.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) > C.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) - C.Condicion_Pago, 0), 0),0) BETWEEN 61 AND 90 '.$condiciones["condicion"].') AS sixtyone_ninety,

	(SELECT IFNULL(ROUND(SUM(PFV.Total - F2.Cuota_Moderadora),2),0) FROM Descripcion_Factura_Capita PFV INNER JOIN Factura_Capita F2 ON PFV.Id_Factura_Capita = F2.Id_Factura_Capita INNER JOIN Cliente C ON F2.Id_Cliente = C.Id_Cliente WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado_Factura = "Sin Cancelar" AND IFNULL(IF(C.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) > C.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) - C.Condicion_Pago, 0), 0),0) BETWEEN 91 AND 180 '.$condiciones["condicion"].') AS ninetyone_onehundeight,

	(SELECT IFNULL(ROUND(SUM(PFV.Total - F2.Cuota_Moderadora),2),0) FROM Descripcion_Factura_Capita PFV INNER JOIN Factura_Capita F2 ON PFV.Id_Factura_Capita = F2.Id_Factura_Capita INNER JOIN Cliente C ON F2.Id_Cliente = C.Id_Cliente WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado_Factura = "Sin Cancelar" AND IFNULL(IF(C.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) > C.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) - C.Condicion_Pago, 0), 0),0) BETWEEN 181 AND 360 '.$condiciones["condicion"].') AS onehundeight_threehundsix,

	(SELECT IFNULL(ROUND(SUM(PFV.Total - F2.Cuota_Moderadora),2),0) FROM Descripcion_Factura_Capita PFV INNER JOIN Factura_Capita F2 ON PFV.Id_Factura_Capita = F2.Id_Factura_Capita INNER JOIN Cliente C ON F2.Id_Cliente = C.Id_Cliente WHERE F2.Id_Cliente = F.Id_Cliente AND F2.Estado_Factura = "Sin Cancelar" AND IFNULL(IF(C.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) > C.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F2.Fecha_Documento)) - C.Condicion_Pago, 0), 0),0) > 360 '.$condiciones["condicion"].') mayor_threehundsix

	FROM Factura_Capita F
	WHERE F.Estado_Factura="Sin Cancelar" '.$condiciones["condicion3"].' GROUP BY F.Id_Cliente
	)
) R
INNER JOIN Cliente C
ON R.Id_Cliente = C.Id_Cliente
GROUP BY R.Id_Cliente
ORDER BY C.Nombre
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

$objSheet->getCell('A1')->setValue("NIT CLIENTE");
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
	$objSheet->getCell('A'.$j)->setValue($value["Id_Cliente"]);
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

function SetCondiciones(){

    $condicion = '';
    $condicion2 = ''; 
    $condicion3 = ''; 
    $condiciones = array('condicion' => '', 'condicion2' => '', 'condicion3' => '');

    if (isset($_REQUEST['cliente']) && $_REQUEST['cliente']) {
        $condicion .= " AND F2.Id_Cliente = $_REQUEST[cliente]";
        $condicion3 .= " AND F.Id_Cliente = $_REQUEST[cliente]";
		$condicion2 .= " AND FP.Nit_Proveedor = $_REQUEST[cliente]";
    }

    if (isset($_REQUEST['fechas']) && $_REQUEST['fechas']) {        
        $fecha = $_REQUEST['fechas'];
		$condicion .= " AND (DATE(F2.Fecha_Documento) BETWEEN '2019-01-01' AND '$fecha')";
		$condicion3 .= " AND (DATE(F.Fecha_Documento) BETWEEN '2019-01-01' AND '$fecha')";
		$condicion2 .= " AND (DATE(FP.Fecha_Factura) <= '$fecha')";
    }

    $condiciones['condicion'] = $condicion;
    $condiciones['condicion2'] = $condicion2;
    $condiciones['condicion3'] = $condicion3;
    return $condiciones;
}


?>