<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set("America/Bogota");
$inicio = ( isset( $_REQUEST['inicio'] ) ? $_REQUEST['inicio'] : '' );
$fin = ( isset( $_REQUEST['fin'] ) ? $_REQUEST['fin'] : '' );
$proveedor = ( isset( $_REQUEST['proveedor'] ) ? $_REQUEST['proveedor'] : false );

$condicion='';

if($proveedor){
    $condicion=' AND OCN.Id_Proveedor='.$proveedor;
}



require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_Proveedor.xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;

$query = 'SELECT  (SELECT GROUP_CONCAT(F.Factura SEPARATOR " / ")
FROM Factura_Acta_Recepcion F WHERE F.Id_Acta_Recepcion=AR.Id_Acta_Recepcion GROUP BY F.Id_Acta_Recepcion) 
AS Factura,	IFNULL(B.Nombre,BN.Nombre) as Nombre_Bodega, (SELECT P.Nombre FROM Proveedor P WHERE P.Id_Proveedor=AR.Id_Proveedor)
AS Proveedor, 
(SELECT PAR.Codigo_Compra FROM Producto_Acta_Recepcion PAR WHERE PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion GROUP BY AR.Id_Acta_Recepcion)
AS Codigo_Compra, AR.Codigo as Codigo_Acta, DATE_FORMAT(AR.Fecha_Creacion,"%d/%m/%Y") AS Fecha
FROM Orden_Compra_Nacional OCN 
LEFT JOIN Acta_Recepcion AR
ON OCN.Id_Orden_Compra_Nacional=AR.Id_Orden_Compra_Nacional
LEFT JOIN Bodega B
ON AR.Id_Bodega=B.Id_Bodega

LEFT JOIN Bodega_Nuevo BN
ON BN.Id_Bodega_Nuevo = AR.Id_Bodega_Nuevo

WHERE OCN.Estado!="Anulada" AND DATE(AR.Fecha_Creacion) BETWEEN "'.$inicio.'" AND "'.$fin.'"'.$condicion.' ORDER BY Codigo_Acta DESC';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos= $oCon->getData();
unset($oCon);


$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Reporte Proveedores');

$objSheet->getCell('A1')->setValue("Proveedor");
$objSheet->getCell('B1')->setValue("Bodega");
$objSheet->getCell('C1')->setValue("Orden Compra");
$objSheet->getCell('D1')->setValue("Fecha Acta");
$objSheet->getCell('E1')->setValue("Factura");
$objSheet->getCell('F1')->setValue("Acta Recepción");

$objSheet->getStyle('A1:F1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:F1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:F1')->getFont()->setBold(true);
$objSheet->getStyle('A1:F1')->getFont()->getColor()->setARGB('FFFFFFFF');


$j=1;
foreach($productos as $disp){ $j++;
	$objSheet->getCell('A'.$j)->setValue($disp["Proveedor"]);
	$objSheet->getCell('B'.$j)->setValue($disp["Nombre_Bodega"]);
	$objSheet->getCell('C'.$j)->setValue($disp["Codigo_Compra"]);
	$objSheet->getCell('D'.$j)->setValue($disp["Fecha"]);
	$objSheet->getCell('E'.$j)->setValue($disp["Factura"]);
	$objSheet->getCell('F'.$j)->setValue($disp["Codigo_Acta"]);
	
}
$objSheet->getColumnDimension('A')->setAutoSize(true);
$objSheet->getColumnDimension('B')->setAutoSize(true);
$objSheet->getColumnDimension('C')->setAutoSize(true);
$objSheet->getColumnDimension('D')->setAutoSize(true);
$objSheet->getColumnDimension('E')->setAutoSize(true);
$objSheet->getColumnDimension('F')->setAutoSize(true);
$objSheet->getStyle('A1:F'.$j)->getAlignment()->setWrapText(true);


$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');



?>