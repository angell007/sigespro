<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set("America/Bogota");


$condicion = '';

if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
	$fecha_inicio = trim(explode(' - ', $_REQUEST['fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $_REQUEST['fechas'])[1]);
	$condicion .= 'AND DATE(N.Fecha_registro) BETWEEN "'.$fecha_inicio.'" AND "'. $fecha_fin.'"';
}

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_No_Conforme.xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;

$query='SELECT PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, PRD.Laboratorio_Generico, PNC.Cantidad, IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida),CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Nombre_Producto, PNC.Observaciones AS Observaciones , PRD.Id_Producto, (SELECT PR.Precio FROM Producto_Remision PR WHERE PR.Id_Producto_Remision=PNC.Id_Producto_Remision) as Costo, (SELECT PD.Nombre FROM Remision R INNER JOIN Punto_Dispensacion PD  ON R.Id_Destino=PD.Id_Punto_Dispensacion WHERE R.Id_Remision=PNC.Id_Remision) as Punto, (SELECT R.Codigo FROM Remision R WHERE R.Id_Remision=PNC.Id_Remision ) as REM, (SELECT C.Nombre FROM Causal_No_Conforme C WHERE C.Id_Causal_No_Conforme=PNC.Id_Causal_No_Conforme) as Motivo,N.Codigo,
CONCAT(F.Nombres," ", F.Apellidos) as Nombre_Funcionario, R.Nombre_Destino as Punto, R.Codigo as Remision,  DATE(N.Fecha_registro) as Fecha, N.Estado,DATE(R.Fecha) as Fecha_Remision, 
R.Inicio_Fase1, R.Fin_Fase1, (SELECT  CONCAT(Nombres, " ", Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=R.Fase_1) as Funcionario_Fase_1, R.Inicio_Fase2, R.Fin_Fase2, (SELECT  CONCAT(Nombres, " ", Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=R.Fase_2) as Funcionario_Fase_2,(SELECT  CONCAT(Nombres, " ", Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=R.Identificacion_Funcionario) as Funcionario_Crea, IFNULL((SELECT Cantidad FROM Producto_Remision WHERE Id_Producto_Remision=PNC.Id_Producto_Remision),0) as Cantidad_Enviada, IFNULL((SELECT Cantidad FROM Producto_Acta_Recepcion_Remision WHERE Id_Producto_Remision=PNC.Id_Producto_Remision),0) as Cantidad_Recibida, PRD.Codigo_Cum
FROM Producto_No_Conforme_Remision PNC  
INNER JOIN Producto PRD  ON PNC.Id_Producto=PRD.Id_Producto
INNER JOIN No_Conforme N ON PNC.Id_No_Conforme=N.Id_No_Conforme
INNER JOIN Funcionario F
On N.Persona_Reporta=F.Identificacion_Funcionario
INNER JOIN Remision R 
ON N.Id_Remision=R.Id_Remision WHERE N.Tipo = "Remision" '.$condicion;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos = $oCon->getData();
unset($oCon);

$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Reporte Pendientes Punto');

$objSheet->getCell('A1')->setValue("Codigo No Conforme");
$objSheet->getCell('B1')->setValue("Fecha");
$objSheet->getCell('C1')->setValue("Funcionario");
$objSheet->getCell('D1')->setValue("Punto");
$objSheet->getCell('E1')->setValue("Nombre Comercial");
$objSheet->getCell('F1')->setValue("Nombre");
$objSheet->getCell('G1')->setValue("Laboratorio Comercial");
$objSheet->getCell('H1')->setValue("Codigo Cum");
$objSheet->getCell('I1')->setValue("Cantidad");
$objSheet->getCell('J1')->setValue("Cantidad Enviada");
$objSheet->getCell('K1')->setValue("Cantidad Recibida");
$objSheet->getCell('L1')->setValue("Costo");
$objSheet->getCell('M1')->setValue("Motivo");
$objSheet->getCell('N1')->setValue("Remision");
$objSheet->getCell('O1')->setValue("Observaciones");
$objSheet->getCell('P1')->setValue("Funcionario Crea");
$objSheet->getCell('Q1')->setValue("Funcionario Fase 1");
$objSheet->getCell('R1')->setValue("Funcionario Fase 2");
$objSheet->getCell('S1')->setValue("Fecha Remision");
$objSheet->getCell('T1')->setValue("Estado");

$objSheet->getStyle('A1:T1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:T1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:T1')->getFont()->setBold(true);
$objSheet->getStyle('A1:T1')->getFont()->getColor()->setARGB('FFFFFFFF');


$j=1;
foreach($productos as $disp){ $j++;
	$objSheet->getCell('A'.$j)->setValue($disp["Codigo"]);
	$objSheet->getCell('B'.$j)->setValue($disp["Fecha"]);
	$objSheet->getCell('C'.$j)->setValue($disp["Nombre_Funcionario"]);
	$objSheet->getCell('D'.$j)->setValue($disp["Punto"]);
	$objSheet->getCell('E'.$j)->setValue($disp["Nombre_Comercial"]);
	$objSheet->getCell('F'.$j)->setValue($disp["Nombre_Producto"]);
	$objSheet->getCell('G'.$j)->setValue($disp["Laboratorio_Comercial"]);
	$objSheet->getCell('H'.$j)->setValue($disp["Codigo_Cum"]);
	$objSheet->getCell('I'.$j)->setValue($disp["Cantidad"]);
	$objSheet->getCell('J'.$j)->setValue($disp["Cantidad_Enviada"]);
	$objSheet->getCell('K'.$j)->setValue($disp["Cantidad_Recibida"]);
	$objSheet->getCell('L'.$j)->setValue($disp["Costo"]);
	$objSheet->getCell('M'.$j)->setValue($disp["Motivo"]);
	$objSheet->getCell('N'.$j)->setValue($disp["REM"]);
	$objSheet->getCell('O'.$j)->setValue($disp["Observaciones"]);
	$objSheet->getCell('P'.$j)->setValue($disp["Funcionario_Crea"]);
	$objSheet->getCell('Q'.$j)->setValue($disp["Funcionario_Fase_1"]);
	$objSheet->getCell('R'.$j)->setValue($disp["Funcionario_Fase_2"]);
	$objSheet->getCell('S'.$j)->setValue($disp["Fecha_Remision"]);
	$objSheet->getCell('T'.$j)->setValue($disp["Estado"]);
	  
}

$objSheet->getColumnDimension('A')->setAutoSize(true);
$objSheet->getColumnDimension('B')->setAutoSize(true);
$objSheet->getColumnDimension('C')->setAutoSize(true);
$objSheet->getColumnDimension('G')->setAutoSize(true);
$objSheet->getColumnDimension('I')->setAutoSize(true);
$objSheet->getStyle('A1:M'.$j)->getAlignment()->setWrapText(true);


$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');



?>