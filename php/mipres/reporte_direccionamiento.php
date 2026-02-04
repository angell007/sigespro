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

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_Dispensacion.csv"');
header('Cache-Control: max-age=0'); 


$condicion = '';

if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
	$fecha_inicio = trim(explode(' - ', $_REQUEST['fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $_REQUEST['fechas'])[1]);
	$condicion .= 'WHERE  DATE(DM.Fecha_Direccionamiento) BETWEEN "'.$fecha_inicio.'" AND "'. $fecha_fin.'" ';
}
if (isset($_REQUEST['dep']) && $_REQUEST['dep'] != "" &&  $_REQUEST['dep'] != '0') {
	$condicion .= " AND DP.Id_Departamento=$_REQUEST[dep]";
}
if (isset($_REQUEST['pto']) && $_REQUEST['pto'] != "" &&  $_REQUEST['pto'] != '0') {
	$condicion .= " AND D.Id_Punto_Dispensacion=$_REQUEST[pto]";
}

if (isset($_REQUEST['nit']) && $_REQUEST['nit'] != "") {
	$condicion .= " AND PA.Nit LIKE '%$_REQUEST[nit]%'";
}
if (isset($_REQUEST['estado']) && $_REQUEST['estado'] != "") {
	$condicion .= " AND DM.Estado LIKE '%$_REQUEST[estado]%'";
}



$query = "
SELECT
Eps.Nombre AS EPS,
PDM.IDDireccionamiento AS Id_Direccionamiento,
PDM.NoPrescripcion AS No_Prescripcion,
PDM.Tipo_Tecnologia AS Tipo_Tecnologia,
PDM.CodSerTecAEntregar AS Cum_Direccionado,
PDIS.Cum AS Cum_Entregado,
PDIS.Costo AS Costo_Unitario,
PA.Id_Paciente AS Id_Paciente,
PA.Paciente as Nombre_Paciente,
PA.Regimen AS Regimen_Paciente,
D.Codigo AS Dispensacion,
PDIS.Numero_Autorizacion AS Autorizacion,
DM.Numero_Entrega AS Numero_Entrega,
DM.Fecha_Direccionamiento AS Fecha_Direccionamiento,
DM.Fecha_Maxima_Entrega AS Fecha_Maxima_Entrega,
D.Fecha_Actual AS Fecha_Radicacion,
PDM.Fecha_Programacion,
PDM.Fecha_Entrega,
PDM.IdProgramacion,
PDM.IdEntrega,
PDM.IdReporteEntrega,
PDM.Valor_Reportado,
DM.Estado,
P.Nombre_Comercial,
CONCAT_WS(' ',P.Principio_Activo, P.Presentacion, P.Concentracion, P.Cantidad, P.Unidad_Medida) AS Nombre_Generico,
P.Embalaje,
PDM.Cantidad AS Cantidad_Formulada,
PDIS.Cantidad_Entregada AS Cantidad_Entregada,
PD.Nombre AS Punto_Dispensacion

FROM Producto_Dispensacion_Mipres PDM
INNER JOIN Dispensacion_Mipres DM ON DM.Id_Dispensacion_Mipres = PDM.Id_Dispensacion_Mipres
INNER JOIN Producto P ON P.Id_Producto = PDM.Id_Producto
INNER JOIN (SELECT Id_Paciente, CONCAT_WS(' ', Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido) AS Paciente, IF(Id_Regimen = 1, 'Contributivo', 'Subsidiado') AS Regimen,
Nit FROM Paciente) PA ON PA.Id_Paciente = DM.Id_Paciente
INNER JOIN Eps ON Eps.Nit = PA.Nit
INNER JOIN (SELECT DE.Id_Departamento, DE.Nombre, MU.Codigo FROM Municipio MU INNER JOIN  Departamento DE ON MU.Id_Departamento=DE.Id_Departamento) DP ON DM.Codigo_Municipio=DP.Codigo
LEFT JOIN  (SELECT * FROM Dispensacion  WHERE Estado_Dispensacion <> 'Anulada' ) D ON D.Id_Dispensacion_Mipres =  DM.Id_Dispensacion_Mipres
LEFT JOIN Punto_Dispensacion PD ON PD.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion  
LEFT JOIN (SELECT PDD.Numero_Autorizacion, PDD.Id_Dispensacion, PDD.Id_Producto, PDD.Cum, PDD.Cantidad_Entregada, PDD.Cantidad_Formulada, PDD.Costo FROM Producto_Dispensacion PDD ) PDIS ON PDIS.Id_Dispensacion=D.Id_Dispensacion AND PDIS.Id_Producto=PDM.Id_Producto AND PDIS.Cantidad_Formulada=PDM.Cantidad  
".$condicion;
 

//echo $query;
//exit;
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
/*
$contenido = '<table border="1"><tr>';

$contenido .= '<td>Id_Direccionamiento</td>';
$contenido .= '<td>No_Prescripcion</td>';
$contenido .= '<td>Tipo_Tecnologia</td>';
$contenido .= '<td>Cum_Direccionado</td>';
$contenido .= '<td>Cum_Entregado</td>';
$contenido .= '<td>Id_Paciente</td>';
$contenido .= '<td>Nombre_Paciente</td>';
$contenido .= '<td>Regimen_Paciente</td>';
$contenido .= '<td>Dispensacion</td>';
$contenido .= '<td>Numero_Entrega</td>';
$contenido .= '<td>Fecha_Direccionamiento</td>';
$contenido .= '<td>Fecha_Maxima_Entrega</td>';
$contenido .= '<td>Fecha_Radicacion</td>';
$contenido .= '<td>Fecha_Programacion</td>';
$contenido .= '<td>Fecha_Entrega</td>';
$contenido .= '<td>Estado</td>';
$contenido .= '<td>Nombre_Comercial</td>';
$contenido .= '<td>Nombre_Generico</td>';
$contenido .= '<td>Embalaje</td>';
$contenido .= '<td>Cantidad_Formulada</td>';
$contenido .= '<td>Cantidad_Entregada</td>';

$contenido .= '</tr>';
*/
$contenido .= 'EPS;';
$contenido .= 'Id_Direccionamiento;';
$contenido .= 'No_Prescripcion;';
$contenido .= 'Tipo_Tecnologia;';
$contenido .= 'Cum_Direccionado;';
$contenido .= 'Cum_Entregado;';
$contenido .= 'Costo_Unitario;';
$contenido .= 'Id_Paciente;';
$contenido .= 'Nombre_Paciente;';
$contenido .= 'Regimen_Paciente;';
$contenido .= 'Dispensacion;';
$contenido .= 'Autorizacion;';
$contenido .= 'Numero_Entrega;';
$contenido .= 'Fecha_Direccionamiento;';
$contenido .= 'Fecha_Maxima_Entrega;';
$contenido .= 'Fecha_Radicacion;';
$contenido .= 'Fecha_Programacion;';
$contenido .= 'Fecha_Entrega;';
$contenido .= 'Estado;';
$contenido .= 'Nombre_Comercial;';
$contenido .= 'Nombre_Generico;';
$contenido .= 'Embalaje;';
$contenido .= 'Cantidad_Formulada;';
$contenido .= 'IdProgramacion;';
$contenido .= 'IdEntrega;';
$contenido .= 'IdReporteEntrega;';
$contenido .='Valor_Reportado;';
$contenido .= 'Cantidad_Entregada;';
$contenido .= 'Punto_Dispensacion;'. PHP_EOL;


/* $objSheet->getStyle('A1:AM1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:AM1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:AM1')->getFont()->setBold(true);
$objSheet->getStyle('A1:AM1')->getFont()->getColor()->setARGB('FFFFFFFF'); */

$dis = '';
$j=1;
foreach($dispensaciones as $disp){ $j++;
    /*
	$contenido .= '<tr>';
	$contenido .= '<td>' . $disp["Id_Direccionamiento"] . '</td>';
    $contenido .= '<td>' . $disp["No_Prescripcion"] . '</td>';
    $contenido .= '<td>' . $disp["Tipo_Tecnologia"] . '</td>';
    $contenido .= '<td>' . $disp["Cum_Direccionado"] . '</td>';
    $contenido .= '<td>' . $disp["Cum_Entregado"] . '</td>';
    $contenido .= '<td>' . $disp["Id_Paciente"] . '</td>';
    $contenido .= '<td>' . $disp["Nombre_Paciente"] . '</td>';
    $contenido .= '<td>' . $disp["Regimen_Paciente"] . '</td>';
    $contenido .= '<td>' . $disp["Dispensacion"] . '</td>';
    $contenido .= '<td>' . $disp["Numero_Entrega"] . '</td>';
    $contenido .= '<td>' . $disp["Fecha_Direccionamiento"] . '</td>';
    $contenido .= '<td>' . $disp["Fecha_Maxima_Entrega"] . '</td>';
    $contenido .= '<td>' . $disp["Fecha_Radicacion"] . '</td>';
    $contenido .= '<td>' . $disp["Fecha_Programacion"] . '</td>';
    $contenido .= '<td>' . $disp["Fecha_Entrega"] . '</td>';
    $contenido .= '<td>' . $disp["Estado"] . '</td>';
    $contenido .= '<td>' . $disp["Nombre_Comercial"] . '</td>';
    $contenido .= '<td>' . $disp["Nombre_Generico"] . '</td>';
    $contenido .= '<td>' . $disp["Embalaje"] . '</td>';
    $contenido .= '<td>' . $disp["Cantidad_Formulada"] . '</td>';
    $contenido .= '<td>' . $disp["Cantidad_Entregada"] . '</td>';
    $contenido .= '</tr>';
    */
    $contenido .= $disp["EPS"] . ';';
	$contenido .= $disp["Id_Direccionamiento"] . ';';
    $contenido .= 'P '.(string)$disp["No_Prescripcion"] . ';';
    $contenido .= $disp["Tipo_Tecnologia"] . ';';
    $contenido .= $disp["Cum_Direccionado"] . ';';
    $contenido .= $disp["Cum_Entregado"] . ';';
    $contenido .= $disp["Costo_Unitario"] . ';';
    $contenido .= $disp["Id_Paciente"] . ';';
    $contenido .= $disp["Nombre_Paciente"] . ';';
    $contenido .= $disp["Regimen_Paciente"] . ';';
    $contenido .= $disp["Dispensacion"] . ';';
    $contenido .= $disp["Autorizacion"] . ';';
    $contenido .= $disp["Numero_Entrega"] . ';';
    $contenido .= $disp["Fecha_Direccionamiento"] . ';';
    $contenido .= $disp["Fecha_Maxima_Entrega"] . ';';
    $contenido .= $disp["Fecha_Radicacion"] . ';';
    $contenido .= $disp["Fecha_Programacion"] . ';';
    $contenido .= $disp["Fecha_Entrega"] . ';';
    $contenido .= $disp["Estado"] . ';';
    $contenido .= $disp["Nombre_Comercial"] . ';';
    $contenido .= $disp["Nombre_Generico"] . ';';
    $contenido .= $disp["Embalaje"] . ';';
    $contenido .= $disp["Cantidad_Formulada"] . ';';
    $contenido .= $disp["IdProgramacion"] . ';'; 
    $contenido .= $disp["IdEntrega"] . ';'; '';
    $contenido .= $disp["IdReporteEntrega"] . ';';
    $contenido .= $disp["Valor_Reportado"] . ';';
    $contenido .= $disp["Cantidad_Entregada"].';';
    $contenido .= $disp["Punto_Dispensacion"].';'. PHP_EOL;
}

// $contenido .= '</table>';

/* $objSheet->getColumnDimension('A')->setAutoSize(true);
$objSheet->getColumnDimension('G')->setAutoSize(true);
$objSheet->getColumnDimension('R')->setAutoSize(true);
$objSheet->getStyle('A1:AM'.$j)->getAlignment()->setWrapText(true); */


// $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
// $objWriter->save('php://output');

echo $contenido;

?>