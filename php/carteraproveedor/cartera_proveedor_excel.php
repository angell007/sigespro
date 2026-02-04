<?php
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
header('Content-Disposition: attachment;filename="Cartera_Proveedor.xls"');
header('Cache-Control: max-age=0');

$condicion = '';
$condicion2 = '';
$condicion3 = '';
$condicion_fechas = '';
$condicion_fechas2 = '';
$condicion_fechas3 = '';

if (isset($_REQUEST['Fechas']) && $_REQUEST['Fechas'] != "" && $_REQUEST['Fechas'] != "undefined") {
	$fecha_inicio = trim(explode(' - ', $_REQUEST['Fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $_REQUEST['Fechas'])[1]);
	$condicion .= " AND (DATE(AR.Fecha_Creacion) BETWEEN '2019-01-01' AND '$fecha_fin')";
	$condicion2 .= " AND (DATE(FP.Fecha_Factura) BETWEEN '$fecha_inicio' AND '$fecha_fin')";
	$condicion3 .= " AND (DATE(FP.Fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin')";
	$condicion_fechas .= " AND (DATE(MC.Fecha_Movimiento) BETWEEN '$fecha_inicio' AND '$fecha_fin')";
	$condicion_fechas2 .= " AND (DATE(FP.Fecha_Factura) BETWEEN '$fecha_inicio' AND '$fecha_fin')";
	$condicion_fechas3 .= " AND (DATE(F.Fecha) BETWEEN '2019-01-01' AND '$fecha_fin')";
}

/** SE COLOCA ESTATICO LA FECHA 2019-01-01 YA QUE EN EL SIGESPRO SE DEBE MOSTRAR FACTURAS DESDE ENERO. */

if (isset($_REQUEST['proveedor']) && $_REQUEST['proveedor'] != '') {
	$condicion .= " AND AR.Id_Proveedor = $_REQUEST[proveedor]";
	$condicion2 .= " AND FP.Nit_Proveedor = $_REQUEST[proveedor]";
	$condicion3 .= " AND FP.Id_Proveedor = $_REQUEST[proveedor]";
}

$objPHPExcel = new PHPExcel;

$query = '
SELECT
r.*
FROM
(
	(SELECT AR.Id_Proveedor, 
P.Nombre 
FROM Factura_Acta_Recepcion FAR 
INNER JOIN Producto_Acta_Recepcion PAR 
ON FAR.Id_Acta_Recepcion = PAR.Id_Acta_Recepcion AND FAR.Factura = PAR.Factura 
INNER JOIN Acta_Recepcion AR 
ON AR.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion 
INNER JOIN Proveedor P 
ON AR.Id_Proveedor = P.Id_Proveedor
WHERE FAR.Estado  = "Pendiente" 
'.$condicion.' GROUP BY AR.Id_Proveedor) 
UNION ALL 
(
	SELECT
	FP.Id_Proveedor AS Id_Proveedor,
	P.Nombre
	FROM
	Devolucion_Compra FP
	INNER JOIN Proveedor P 
	ON FP.Id_Proveedor = P.Id_Proveedor
	'.$condicion3.'
	GROUP BY FP.Id_Proveedor
)
/*UNION ALL 
(
	SELECT
	FP.Nit_Proveedor AS Id_Proveedor,
	P.Nombre
	FROM
	Facturas_Proveedor_Mantis FP
	INNER JOIN Proveedor P 
	ON FP.Nit_Proveedor = P.Id_Proveedor
	WHERE FP.Estado = "Pendiente" 
	'.$condicion2.'
	GROUP BY FP.Nit_Proveedor
)*/
) r
GROUP BY r.Id_Proveedor
';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$proveedores= $oCon->getData();
unset($oCon);

$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Cartera Proveedor');

$facturas_prov = [];

$j=0;
foreach($proveedores as $prov){ $j++;

	$q = "SELECT 
	DATE(MC.Fecha_Movimiento) AS Fecha_Recepcion,
    (SELECT DATE(Fecha_Factura) FROM Factura_Acta_Recepcion WHERE Factura = MC.Documento AND Id_Acta_Recepcion = MC.Id_Registro_Modulo) AS Fecha_Factura,
    (SELECT Codigo FROM Acta_Recepcion WHERE Id_Acta_Recepcion = MC.Id_Registro_Modulo) AS Acta,
    MC.Documento AS Factura,	
    (CASE PC.Naturaleza
        WHEN 'C' THEN (SUM(MC.Haber))
        ELSE (SUM(MC.Debe))
    END) AS Valor_Factura,
    (CASE PC.Naturaleza
        WHEN 'C' THEN (SUM(MC.Debe))
        ELSE (SUM(MC.Haber))
    END) AS Valor_Abono,
    (CASE PC.Naturaleza
        WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
        ELSE (SUM(MC.Debe) - SUM(MC.Haber))
    END) AS Valor_Saldo,
    PC.Naturaleza AS Nat,
	(SELECT IFNULL(SUM(Cantidad*Precio),0) FROM Producto_Acta_Recepcion WHERE Factura = MC.Documento AND Impuesto != 0) AS Gravado,
	(SELECT IFNULL(SUM(Cantidad*Precio),0) FROM Producto_Acta_Recepcion WHERE Factura = MC.Documento AND Impuesto = 0) AS Excento,
	(SELECT IFNULL(SUM(Cantidad*Precio*(Impuesto/100)),0) FROM Producto_Acta_Recepcion WHERE Factura = MC.Documento AND Impuesto != 0) AS Iva,
	(SELECT (SUM(Haber)-SUM(Debe)) FROM Movimiento_Contable WHERE Id_Modulo = 15 AND Documento = MC.Documento AND Id_Plan_Cuenta = 320) AS Rte_Fuente,
	(SELECT (SUM(Haber)-SUM(Debe)) FROM Movimiento_Contable WHERE Id_Modulo = 15 AND Documento = MC.Documento AND Id_Plan_Cuenta = 328) AS Rte_Ica
    FROM
    Movimiento_Contable MC
        INNER JOIN
    Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
    WHERE
    MC.Nit = $prov[Id_Proveedor] AND MC.Estado != 'Anulado'
        AND MC.Id_Plan_Cuenta = 272
		$condicion_fechas
	GROUP BY MC.Id_Plan_Cuenta, MC.Documento HAVING Valor_Saldo != 0 AND Acta != '' ORDER BY MC.Fecha_Movimiento";
	

	$oCon= new consulta();
	$oCon->setQuery($q);
	$oCon->setTipo('Multiple');
	$facturas_prov= $oCon->getData();
	unset($oCon);

	$objSheet->getCell('A'.$j)->setValue($prov["Id_Proveedor"]);
	$objSheet->getCell('B'.$j)->setValue($prov["Nombre"]);
	$j++;

	$objSheet->getCell('B'.$j)->setValue('Factura');
	$objSheet->getCell('C'.$j)->setValue('Fecha Factura');
	$objSheet->getCell('D'.$j)->setValue('Acta Recepción');
	$objSheet->getCell('E'.$j)->setValue('Fecha Recepción');
	$objSheet->getCell('F'.$j)->setValue('Gravado');
	$objSheet->getCell('G'.$j)->setValue('Excento');
	$objSheet->getCell('H'.$j)->setValue('Iva');
	$objSheet->getCell('I'.$j)->setValue('Rte Fuente 2.5%');
	$objSheet->getCell('J'.$j)->setValue('Rte Ica 0.5%');
	$objSheet->getCell('K'.$j)->setValue('Total Factura');
	$objSheet->getCell('L'.$j)->setValue('Abono');
	$objSheet->getCell('M'.$j)->setValue('Neto Factura');
	
	$objSheet->getStyle('A'.$j.':M'.$j)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objSheet->getStyle('A'.$j.':M'.$j)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
	$objSheet->getStyle('A'.$j.':M'.$j)->getFont()->setBold(true);
	$objSheet->getStyle('A'.$j.':M'.$j)->getFont()->getColor()->setARGB('FFFFFFFF');


	foreach ($facturas_prov as $value) {$j++;
		$total_factura = $value["Total_Factura"]+$value["Iva"]-$value["Rte_Fuente"]-$value["Rte_Ica"];
		$neto = $total_factura - $value["Abono"];
		$objSheet->getCell('B'.$j)->setValue($value["Factura"]);
		$objSheet->getCell('C'.$j)->setValue(fecha($value["Fecha_Factura"]));
		$objSheet->getCell('D'.$j)->setValue($value['Acta']);
		$objSheet->getCell('E'.$j)->setValue(fecha($value['Fecha_Recepcion']));
		$objSheet->getCell('F'.$j)->setValue($value["Gravado"]);
		$objSheet->getStyle('F'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getCell('G'.$j)->setValue($value["Excento"]);
		$objSheet->getStyle('G'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getCell('H'.$j)->setValue($value["Iva"]);
		$objSheet->getStyle('H'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getCell('I'.$j)->setValue($value["Rte_Fuente"]);
		$objSheet->getStyle('I'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getCell('J'.$j)->setValue($value["Rte_Ica"]);
		$objSheet->getStyle('J'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getCell('K'.$j)->setValue($value['Valor_Factura']);
		$objSheet->getStyle('K'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getCell('L'.$j)->setValue($value["Valor_Abono"]);
		$objSheet->getStyle('L'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getCell('M'.$j)->setValue($value['Valor_Saldo']);
		$objSheet->getStyle('M'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	}
	
}

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

function fecha($date) {
	return date('d/m/Y', strtotime($date));
}

?>