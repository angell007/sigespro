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

/* require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php'; */

 header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_Ventas.xls"');
header('Cache-Control: max-age=0'); 

// $objPHPExcel = new PHPExcel;

$condicion = '';

if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
	$fecha_inicio = trim(explode(' - ', $_REQUEST['fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $_REQUEST['fechas'])[1]);
	$condicion .= 'AND DATE(D.Fecha_Actual) BETWEEN "'.$fecha_inicio.'" AND "'. $fecha_fin.'"';
}
if (isset($_REQUEST['dep']) && $_REQUEST['dep'] != "") {
	$condicion .= " AND DP.Id_Departamento=$_REQUEST[dep]";
}
if (isset($_REQUEST['pto']) && $_REQUEST['pto'] != "") {
	$condicion .= " AND D.Id_Punto_Dispensacion=$_REQUEST[pto]";
}
if (isset($_REQUEST['func']) && $_REQUEST['func'] != "") {
	$condicion .= " AND D.Identificacion_Funcionario=$_REQUEST[func]";
}
if (isset($_REQUEST['pac']) && $_REQUEST['pac'] != "") {
	$condicion .= " AND D.Id_Paciente=$_REQUEST[pac]";
}

if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
	$condicion .= " AND D.Tipo='$_REQUEST[tipo]'";
}

if (isset($_REQUEST['pend']) && $_REQUEST['pend'] == "No") {
	$condicion .= " AND D.Pendientes=0";
} elseif(isset($_REQUEST['pend']) && $_REQUEST['pend'] == "Si")  {
	$condicion .= " AND D.Pendientes<>0";
}
if (isset($_REQUEST['dis']) && $_REQUEST['dis'] != "") {
	$condicion .= " AND D.Codigo='$_REQUEST[dis]'";
}


$query = 'SELECT
F.Codigo AS Factura,
F.Fecha_Documento AS Fecha_Factura,
F.Id_Cliente AS NIT_Cliente,
C.Nombre AS Nombre_Cliente,
Z.Nombre as Zona_Comercial,

IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
FROM Producto_Factura PF
WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto!=0),0) as Gravada,

IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
FROM Producto_Factura PF
WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto=0),0) as Excenta,

(IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
FROM Producto_Factura PF
WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
FROM Producto_Factura PF
WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto=0),0)) AS Total_Venta,

IFNULL((SELECT CAST(ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2) AS SIGNED)
FROM Producto_Factura PF
WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto!=0),0) as Iva,

IFNULL((SELECT CAST(ROUND(SUM(PF.Cantidad*PF.Descuento),2) AS SIGNED)
FROM Producto_Factura PF
WHERE PF.Id_Factura = F.Id_Factura),0) as Descuento,

((IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
FROM Producto_Factura PF
WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
FROM Producto_Factura PF
WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto=0),0)) + IFNULL((SELECT ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2)
FROM Producto_Factura PF
WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto!=0),0) - IFNULL((SELECT CAST(ROUND(SUM(PF.Cantidad*PF.Descuento),2) AS SIGNED)
FROM Producto_Factura PF
WHERE PF.Id_Factura = F.Id_Factura),0)) AS Neto_Factura,

(SELECT IFNULL(CAST(REPLACE(FORMAT(SUM(I.Costo*PF.Cantidad),2),",","") AS SIGNED),0) FROM Producto_Factura PF INNER JOIN Producto P ON P.Id_Producto = PF.Id_Producto INNER JOIN (SELECT Id_Producto, AVG(Costo) AS Costo FROM Inventario WHERE Id_Bodega != 0 GROUP BY Id_Producto) I ON PF.Id_Producto = I.Id_Producto WHERE PF.Id_Factura = F.Id_Factura AND P.Gravado = "No") AS Costo_Venta_Exenta,

(SELECT IFNULL(CAST(REPLACE(FORMAT(SUM(I.Costo*PF.Cantidad),2),",","") AS SIGNED),0) FROM Producto_Factura PF INNER JOIN Producto P ON P.Id_Producto = PF.Id_Producto INNER JOIN (SELECT Id_Producto, AVG(Costo) AS Costo FROM Inventario WHERE Id_Bodega != 0 GROUP BY Id_Producto) I ON PF.Id_Producto = I.Id_Producto WHERE PF.Id_Factura = F.Id_Factura AND P.Gravado = "Si") AS Costo_Venta_Gravada

FROM Factura F
INNER JOIN Cliente C
ON C.Id_Cliente = F.Id_Cliente
INNER JOIN Zona Z
ON Z.Id_Zona = C.Id_Zona
WHERE YEAR(F.Fecha_Documento) = 2018
ORDER BY Factura';


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$dispensaciones= $oCon->getData();
unset($oCon);


/* 
$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Reporte Dispensacion');
 */

$contenido = '<table border="1"><tr>';


$contenido .= '<td>Factura</td>';
$contenido .= '<td>Fecha_Factura</td>';
$contenido .= '<td>NIT_Cliente</td>';
$contenido .= '<td>Nombre_Cliente</td>';
$contenido .= '<td>Zona_Comercial</td>';
$contenido .= '<td>Gravada</td>';
$contenido .= '<td>Excenta</td>';
$contenido .= '<td>Iva</td>';
$contenido .= '<td>Descuento</td>';
$contenido .= '<td>Total_Venta</td>';
$contenido .= '<td>Neto_Factura</td>';
$contenido .= '<td>Costo_Venta_Excenta</td>';
$contenido .= '<td>Costo_Venta_Gravada</td>';



/* $objSheet->getStyle('A1:AM1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:AM1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:AM1')->getFont()->setBold(true);
$objSheet->getStyle('A1:AM1')->getFont()->getColor()->setARGB('FFFFFFFF'); */


$j=1;
foreach($dispensaciones as $disp){ $j++;

	$contenido .= '<tr>';
	
	$contenido .= '<td>' . $disp["Factura"] . '</td>';
	$contenido .= '<td>' . $disp["Fecha_Factura"] . '</td>';
	$contenido .= '<td>' . $disp["NIT_Cliente"] . '</td>';
	$contenido .= '<td>' . $disp["Nombre_Cliente"] . '</td>';
	$contenido .= '<td>' . $disp["Zona_Comercial"] . '</td>';
	$contenido .= '<td>' . $disp["Gravada"] . '</td>';
	$contenido .= '<td>' . $disp["Excenta"] . '</td>';
	$contenido .= '<td>' . $disp["Iva"] . '</td>';
	$contenido .= '<td>' . $disp["Descuento"] . '</td>';
	$contenido .= '<td>' . $disp["Total_Venta"] . '</td>';
	$contenido .= '<td>' . $disp["Neto_Factura"] . '</td>';
	$contenido .= '<td>' . $disp["Costo_Venta_Exenta"] . '</td>';
	$contenido .= '<td>' . $disp["Costo_Venta_Gravada"] . '</td></tr>';

	
}

$contenido .= '</table>';

/* $objSheet->getColumnDimension('A')->setAutoSize(true);
$objSheet->getColumnDimension('G')->setAutoSize(true);
$objSheet->getColumnDimension('R')->setAutoSize(true);
$objSheet->getStyle('A1:AM'.$j)->getAlignment()->setWrapText(true); */


// $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
// $objWriter->save('php://output');

echo $contenido;

?>