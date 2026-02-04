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
header('Content-Disposition: attachment;filename="Reporte_Compras.xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;


$query = "SELECT AR.Id_Acta_Recepcion, AR.Id_Bodega, OCN.Id_Proveedor, P.Nombre , FAR.Factura, FAR.Fecha_Factura,DATE_FORMAT(AR.Fecha_Creacion,'%Y-%m-%d') as Fecha_Acta, AR.Codigo as Acta_Recepcion,
(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si') as Gravada,
(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='No') as Excenta,
((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si')+(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='No')) as Total_Compra,
((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si')+(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='No')+(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si')) as Total_Factura,
((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si')+(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='No')+(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si')) AS Neto_Factura,
(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si') as Iva, AR.Codigo as Codigo_Acta,

(SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 60 LIMIT 1) AS Rte_Fuente,

(SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 63 LIMIT 1) AS Rte_Ica,

AR.Estado

FROM Orden_Compra_Nacional OCN
INNER JOIN Acta_Recepcion AR 
ON AR.Id_Orden_Compra_Nacional = OCN.Id_Orden_Compra_Nacional 
LEFT JOIN Factura_Acta_Recepcion FAR 
ON FAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
INNER JOIN Proveedor P 
ON P.Id_Proveedor = OCN.Id_Proveedor
WHERE DATE_FORMAT(AR.Fecha_Creacion, '%Y-%m-%d') BETWEEN '".$inicio."' AND '".$fin."'".$condicion."

UNION ALL (

	SELECT AR.Id_Acta_Recepcion_Internacional, AR.Id_Bodega, OCN.Id_Proveedor, P.Nombre , FAR.Factura, FAR.Fecha_Factura,DATE_FORMAT(AR.Fecha_Creacion,'%Y-%m-%d') as Fecha_Acta, AR.Codigo as Acta_Recepcion,
(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='Si') as Gravada,
(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='No') as Excenta,
((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='Si')+(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='No')) as Total_Compra,
((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='Si')+(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='No')+(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='Si')) as Total_Factura,
((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='Si')+(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='No')+(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='Si')) AS Neto_Factura,
(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='Si') as Iva, AR.Codigo as Codigo_Acta,

0 AS Rte_Fuente,

0 AS Rte_Ica,

AR.Estado

FROM Orden_Compra_Internacional OCN
INNER JOIN Acta_Recepcion_Internacional AR 
ON AR.Id_Orden_Compra_Internacional = OCN.Id_Orden_Compra_Internacional 
LEFT JOIN Factura_Acta_Recepcion_Internacional FAR 
ON FAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional
INNER JOIN Proveedor P 
ON P.Id_Proveedor = OCN.Id_Proveedor
WHERE DATE_FORMAT(AR.Fecha_Creacion, '%Y-%m-%d') BETWEEN '".$inicio."' AND '".$fin."'".$condicion."
	
)

";

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

$objSheet->getCell('A1')->setValue("Acta Recepcion");
$objSheet->getCell('B1')->setValue("Fecha Recepcion");
$objSheet->getCell('C1')->setValue("NIT");
$objSheet->getCell('D1')->setValue("Proveedor");
$objSheet->getCell('E1')->setValue("Tipo");
$objSheet->getCell('F1')->setValue("Factura");
$objSheet->getCell('G1')->setValue("Fecha Factura");
$objSheet->getCell('H1')->setValue("Valor Execto");
$objSheet->getCell('I1')->setValue("Valor Gravado");
$objSheet->getCell('J1')->setValue("Total Compra");
$objSheet->getCell('K1')->setValue("Iva");
$objSheet->getCell('L1')->setValue("Total Factura");
$objSheet->getCell('M1')->setValue("Rte Fuente 2.5%");
$objSheet->getCell('N1')->setValue("Rte Ica 0.5%");
$objSheet->getCell('O1')->setValue("Neto Factura");

$objSheet->getStyle('A1:O1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:O1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:O1')->getFont()->setBold(true);
$objSheet->getStyle('A1:O1')->getFont()->getColor()->setARGB('FFFFFFFF');


$j=1;
foreach($productos as $disp){ $j++;
	$tipo = $disp['Id_Bodega'] != 0 ? 'Bodega' : 'Punto Dispensación';
	$rte_fuente = $disp['Estado'] != 'Anulada' ? $disp['Rte_Fuente'] != '' ? $disp['Rte_Fuente'] : 0 : 0;
	$rte_ica = $disp['Estado'] != 'Anulada' ? $disp['Rte_Ica'] != '' ? $disp['Rte_Ica'] : 0 : 0;
	$neto = $disp['Estado'] != 'Anulada' ? $disp["Neto_Factura"] - $rte_fuente - $rte_ica : 0;
	$gravada = $disp['Estado'] != 'Anulada' ? $disp["Gravada"] : 0;
	$excenta = $disp['Estado'] != 'Anulada' ? $disp["Excenta"] : 0;
	$total_compra = $disp['Estado'] != 'Anulada' ? $disp["Total_Compra"] : 0;
	$iva = $disp['Estado'] != 'Anulada' ? $disp["Iva"] : 0;
	$total_factura = $disp['Estado'] != 'Anulada' ? $disp["Total_Factura"] : 0;

	$objSheet->getCell('A'.$j)->setValue($disp["Acta_Recepcion"]);
	$objSheet->getCell('B'.$j)->setValue(date('d/m/Y',strtotime($disp["Fecha_Acta"])));
	$objSheet->getCell('C'.$j)->setValue($disp["Id_Proveedor"]);
	$objSheet->getCell('D'.$j)->setValue($disp["Nombre"]);
	$objSheet->getCell('E'.$j)->setValue($tipo);
	$objSheet->getCell('F'.$j)->setValue($disp["Factura"]);
	$objSheet->getCell('G'.$j)->setValue(date("Y-m-d",strtotime($disp["Fecha_Factura"])));
	$objSheet->getCell('H'.$j)->setValue($excenta);
	$objSheet->getCell('I'.$j)->setValue($gravada);
	$objSheet->getCell('J'.$j)->setValue($total_compra);
	$objSheet->getCell('K'.$j)->setValue($iva);
	$objSheet->getCell('L'.$j)->setValue($total_factura);
	$objSheet->getCell('M'.$j)->setValue($rte_fuente);
	$objSheet->getCell('N'.$j)->setValue($rte_ica);
	$objSheet->getCell('O'.$j)->setValue($neto);
	
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
$objSheet->getColumnDimension('M')->setAutoSize(true);
$objSheet->getColumnDimension('N')->setAutoSize(true);
$objSheet->getColumnDimension('O')->setAutoSize(true);
$objSheet->getStyle('A1:O'.$j)->getAlignment()->setWrapText(true);


$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');



?>