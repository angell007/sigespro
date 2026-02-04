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
header('Content-Disposition: attachment;filename="Reporte_Rotacion.xls"');
header('Cache-Control: max-age=0'); 

// $objPHPExcel = new PHPExcel;
$tipo = isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : ''; 
$condicion = '';

if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
	$fecha_inicio = trim(explode(' - ', $_REQUEST['fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $_REQUEST['fechas'])[1]);
	$condicion .= ' AND DATE(D.Fecha_Actual) BETWEEN "'.$fecha_inicio.'" AND "'. $fecha_fin.'"';
	$condicionbodega .= ' AND DATE(R.Fecha) BETWEEN "'.$fecha_inicio.'" AND "'. $fecha_fin.'"';
}
if (isset($_REQUEST['dep']) && $_REQUEST['dep'] != "") {
	$condicion .= " AND DP.Id_Departamento=$_REQUEST[dep]";
}
if (isset($_REQUEST['pto']) && $_REQUEST['pto'] != "") {
	$condicion .= " AND D.Id_Punto_Dispensacion=$_REQUEST[pto]";
}
if (isset($_REQUEST['bod']) && $_REQUEST['bod'] != "") {
	$condicionbodega .= " AND R.Id_Origen=$_REQUEST[bod] AND R.Tipo_Origen='Bodega'";
}

if($tipo=='Punto'){
    $query = 'SELECT P.Codigo_Cum, P.Nombre_Comercial, 
CONCAT_WS(" ",P.Principio_Activo,P.Presentacion,P.Concentracion,P.Cantidad,P.Unidad_Medida) as Nombre, 
P.Embalaje, P.Laboratorio_Generico, P.Laboratorio_Comercial, SUM(PD.Cantidad_Formulada) AS Cantidad_Rotada, 
PDI.Nombre  as Punto_Dispensacion
FROM Producto_Dispensacion PD
INNER JOIN Dispensacion D
ON PD.Id_Dispensacion = D.Id_Dispensacion
INNER JOIN Producto P
On P.Id_Producto = PD.Id_Producto
INNER JOIN Punto_Dispensacion PDI
ON PDI.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
INNER JOIN Departamento DP
ON PDI.Departamento=DP.Id_Departamento
WHERE D.Estado != "Anulada"
'.$condicion.' GROUP BY PD.Id_Producto ORDER BY Cantidad_Rotada DESC ';

}elseif ($tipo=='Bodega') {
    $query = 'SELECT P.Codigo_Cum, P.Nombre_Comercial, 
    CONCAT_WS(" ",P.Principio_Activo,P.Presentacion,P.Concentracion,P.Cantidad,P.Unidad_Medida) as Nombre, 
    P.Embalaje, P.Laboratorio_Generico, P.Laboratorio_Comercial, SUM(PD.Cantidad) AS Cantidad_Rotada
    FROM Producto_Remision PD
    INNER JOIN Remision R
    ON PD.Id_Remision = R.Id_Remision
    INNER JOIN Producto P
    On P.Id_Producto = PD.Id_Producto   
    WHERE R.Estado != "Anulada"
    '.$condicionbodega.' GROUP BY PD.Id_Producto  ORDER BY Cantidad_Rotada DESC ';

}

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


$contenido .= '<td>Nombre Comercial </td>';
$contenido .= '<td>Nombre</td>';
$contenido .= '<td>Embalaje</td>';
$contenido .= '<td>Cum</td>';
$contenido .= '<td>Laboratorio_Generico</td>';
$contenido .= '<td>Laboratorio_Comercial</td>';
$contenido .= '<td>Cantidad Rotada</td>';
$contenido .= '</tr>';


/* $objSheet->getStyle('A1:AM1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:AM1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:AM1')->getFont()->setBold(true);
$objSheet->getStyle('A1:AM1')->getFont()->getColor()->setARGB('FFFFFFFF'); */

$dis = '';
$j=1;
foreach($dispensaciones as $disp){ $j++;

	$contenido .= '<tr>';
	$contenido .= '<td>' . $disp["Nombre_Comercial"] . '</td>';
	$contenido .= '<td>' . $disp["Nombre"] . '</td>';
	$contenido .= '<td>' . $disp["Embalaje"] . '</td>';
	$contenido .= '<td>' . $disp["Codigo_Cum"] . '</td>';
	$contenido .= '<td>' . $disp["Laboratorio_Generico"] . '</td>';
	$contenido .= '<td>' . $disp["Laboratorio_Comercial"] . '</td>';
	$contenido .= '<td>' . $disp["Cantidad_Rotada"] . '</td></tr>';

	
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