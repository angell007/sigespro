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

$objPHPExcel = new PHPExcel;

$query = 'SELECT AR.Id_Proveedor, P.Nombre FROM Factura_Acta_Recepcion FAR INNER JOIN Producto_Acta_Recepcion PAR ON FAR.Id_Acta_Recepcion = PAR.Id_Acta_Recepcion AND FAR.Factura = PAR.Factura INNER JOIN Acta_Recepcion AR ON AR.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion INNER JOIN Proveedor P ON AR.Id_Proveedor = P.Id_Proveedor GROUP BY AR.Id_Proveedor';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
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
	FAR.Factura, FAR.Fecha_Factura, AR.Codigo AS Acta_Recepcion, (SELECT Codigo FROM Orden_Compra_Nacional WHERE Id_Orden_Compra_Nacional = AR.Id_Orden_Compra_Nacional) AS Orden_Compra,
	
	(SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0) as Gravado,
	
	(SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto=0) as Excento,
	
	(SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio*(PAR2.Impuesto/100))),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0)  as Iva,
	
	((SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto=0)+ (SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0)) AS Total_Factura,
	
	((SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto=0)+ (SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0) + (SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio*(PAR2.Impuesto/100))),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0)) as Neto_Factura
	 
	FROM
	Factura_Acta_Recepcion FAR
	INNER JOIN Acta_Recepcion AR
	ON FAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
	INNER JOIN Proveedor P
	ON AR.Id_Proveedor = P.Id_Proveedor
	INNER JOIN Producto_Acta_Recepcion PAR
	ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
	WHERE AR.Id_Proveedor = $prov[Id_Proveedor] AND FAR.Estado = 'Pendiente'
	GROUP BY FAR.Id_Acta_Recepcion, FAR.Factura ORDER BY FAR.Fecha_Factura DESC";

	$oCon= new consulta();
	$oCon->setTipo('Multiple');
	$oCon->setQuery($q);
	$facturas_prov= $oCon->getData();
	unset($oCon);

	$objSheet->getCell('A'.$j)->setValue($prov["Id_Proveedor"]);
	$objSheet->getCell('B'.$j)->setValue($prov["Nombre"]);
	$j++;

	$objSheet->getCell('B'.$j)->setValue('Factura');
	$objSheet->getCell('C'.$j)->setValue('Fecha Factura');
	$objSheet->getCell('D'.$j)->setValue('Acta Recepción');
	$objSheet->getCell('E'.$j)->setValue('Orden Compra');
	$objSheet->getCell('F'.$j)->setValue('Gravado');
	$objSheet->getCell('G'.$j)->setValue('Excento');
	$objSheet->getCell('H'.$j)->setValue('Iva');
	$objSheet->getCell('I'.$j)->setValue('Total Factura');
	$objSheet->getCell('J'.$j)->setValue('Neto Factura');
	
	$objSheet->getStyle('A'.$j.':J'.$j)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objSheet->getStyle('A'.$j.':J'.$j)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
	$objSheet->getStyle('A'.$j.':J'.$j)->getFont()->setBold(true);
	$objSheet->getStyle('A'.$j.':J'.$j)->getFont()->getColor()->setARGB('FFFFFFFF');


	foreach ($facturas_prov as $value) {$j++;
		$objSheet->getCell('B'.$j)->setValue($value["Factura"]);
		$objSheet->getCell('C'.$j)->setValue($value["Fecha_Factura"]);
		$objSheet->getCell('D'.$j)->setValue($value["Acta_Recepcion"]);
		$objSheet->getCell('E'.$j)->setValue($value["Orden_Compra"]);
		$objSheet->getCell('F'.$j)->setValue($value["Gravado"]);
		$objSheet->getStyle('F'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getCell('G'.$j)->setValue($value["Excento"]);
		$objSheet->getStyle('G'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getCell('H'.$j)->setValue($value["Iva"]);
		$objSheet->getStyle('H'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getCell('I'.$j)->setValue($value["Total_Factura"]);
		$objSheet->getStyle('I'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getCell('J'.$j)->setValue($value["Neto_Factura"]);
		$objSheet->getStyle('J'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	}
	
}

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

?>