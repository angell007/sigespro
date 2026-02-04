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


$query = "SELECT 
DATE_FORMAT(MC.Fecha_Movimiento, '%d/%m/%Y') AS Fecha,
MC.Documento AS Factura,
(CASE PC.Naturaleza
	WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
	ELSE (SUM(MC.Debe) - SUM(MC.Haber))
END) AS Valor_Saldo,
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
IF(IFNULL(IF(C.Condicion_Pago > 1,
				IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
					DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
					0),
				0),
			0) = 0,
	(CASE PC.Naturaleza
		WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
		ELSE (SUM(MC.Debe) - SUM(MC.Haber))
	END),
	0) AS Sin_Vencer,
	
	IF(IFNULL(IF(C.Condicion_Pago > 1,
				IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
					DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
					0),
				0),
			0) BETWEEN 1 AND 30, (CASE PC.Naturaleza
		WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
		ELSE (SUM(MC.Debe) - SUM(MC.Haber))
	END), 0) AS first_thirty_days,
	
	IF(IFNULL(IF(C.Condicion_Pago > 1,
				IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
					DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
					0),
				0),
			0) BETWEEN 31 AND 60, (CASE PC.Naturaleza
		WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
		ELSE (SUM(MC.Debe) - SUM(MC.Haber))
	END), 0) AS thirtyone_sixty,
	
	IF(IFNULL(IF(C.Condicion_Pago > 1,
				IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
					DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
					0),
				0),
			0) BETWEEN 61 AND 90, (CASE PC.Naturaleza
		WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
		ELSE (SUM(MC.Debe) - SUM(MC.Haber))
	END), 0) AS sixtyone_ninety,
	
	IF(IFNULL(IF(C.Condicion_Pago > 1,
				IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
					DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
					0),
				0),
			0) BETWEEN 91 AND 180, (CASE PC.Naturaleza
		WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
		ELSE (SUM(MC.Debe) - SUM(MC.Haber))
	END), 0) AS ninetyone_onehundeight,
	
	IF(IFNULL(IF(C.Condicion_Pago > 1,
				IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
					DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
					0),
				0),
			0) BETWEEN 181 AND 360, (CASE PC.Naturaleza
		WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
		ELSE (SUM(MC.Debe) - SUM(MC.Haber))
	END), 0) AS onehundeight_threehundsix,
	
	IF(IFNULL(IF(C.Condicion_Pago > 1,
				IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
					DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
					0),
				0),
			0) > 360, (CASE PC.Naturaleza
		WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
		ELSE (SUM(MC.Debe) - SUM(MC.Haber))
	END), 0) AS mayor_threehundsix
	
FROM
Movimiento_Contable MC
	INNER JOIN
Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
	INNER JOIN
Cliente C ON C.Id_Cliente = MC.Nit
WHERE
MC.Estado != 'Anulado'
	AND Id_Plan_Cuenta = 57
	$condiciones
GROUP BY MC.Id_Plan_Cuenta , MC.Documento, MC.Nit
HAVING Valor_Saldo != 0
ORDER BY MC.Fecha_Movimiento";
	
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$edades= $oCon->getData();
unset($oCon);


$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Reporte Edades Cartera Clientes');

$objSheet->getCell('A1')->setValue("NIT CLIENTE");
$objSheet->getCell('B1')->setValue("RAZON SOCIAL");
$objSheet->getCell('C1')->setValue("FACTURA");
$objSheet->getCell('D1')->setValue("FECHA FACTURA");
$objSheet->getCell('E1')->setValue("SALDO");
$objSheet->getCell('F1')->setValue("SIN VENCER");
$objSheet->getCell('G1')->setValue("1 - 30");
$objSheet->getCell('H1')->setValue("31 - 60");
$objSheet->getCell('I1')->setValue("61 - 90");
$objSheet->getCell('J1')->setValue("91 - 180");
$objSheet->getCell('K1')->setValue("181 - 360");
$objSheet->getCell('L1')->setValue("MAYOR DE 360");
$objSheet->getStyle('A1:L1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:L1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:L1')->getFont()->setBold(true);
$objSheet->getStyle('A1:L1')->getFont()->getColor()->setARGB('FFFFFFFF');


$j=1;
foreach($edades as $value){ $j++;
	$objSheet->getCell('A'.$j)->setValue($value["Nit"]);
	$objSheet->getCell('B'.$j)->setValue($value["Nombre_Cliente"]);
	$objSheet->getCell('C'.$j)->setValue($value["Factura"]);
	$objSheet->getCell('D'.$j)->setValue($value["Fecha"]);
	$objSheet->getCell('E'.$j)->setValue($value["Valor_Saldo"]);
	$objSheet->getCell('F'.$j)->setValue($value["Sin_Vencer"]);
	$objSheet->getCell('G'.$j)->setValue($value["first_thirty_days"]);
	$objSheet->getCell('H'.$j)->setValue($value["thirtyone_sixty"]);
	$objSheet->getCell('I'.$j)->setValue($value["sixtyone_ninety"]);
	$objSheet->getCell('H'.$j)->setValue($value["ninetyone_onehundeight"]);
	$objSheet->getCell('K'.$j)->setValue($value["onehundeight_threehundsix"]);
	$objSheet->getCell('L'.$j)->setValue($value["mayor_threehundsix"]);
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
$objSheet->getColumnDimension('K')->setAutoSize(true);
$objSheet->getColumnDimension('L')->setAutoSize(true);
$objSheet->getStyle('A1:L'.$j)->getAlignment()->setWrapText(true);


$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

function SetCondiciones(){

    $condicion = '';

    if (isset($_REQUEST['cliente']) && $_REQUEST['cliente']) {
        $condicion .= " AND MC.Nit = $_REQUEST[cliente]";
    }

    if (isset($_REQUEST['fechas']) && $_REQUEST['fechas']) {        
        $fecha = $_REQUEST['fechas'];
		$condicion .= " AND (DATE(MC.Fecha_Movimiento) <= '$fecha')";
    }

    return $condicion;
}


?>