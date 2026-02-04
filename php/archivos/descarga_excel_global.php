<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set("America/Bogota");

$punto = ( isset( $_REQUEST['id_tipo'] ) ? $_REQUEST['id_tipo'] : '' );
$fecha = ( isset( $_REQUEST['fecha'] ) ? $_REQUEST['fecha'] : '' );
$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : false );
$estado = ( isset( $_REQUEST['estado'] ) ? $_REQUEST['estado'] : false );
$tipo_bod = ( isset( $_REQUEST['tipo_bod'] ) ? $_REQUEST['tipo_bod'] : false );

$cond_estado = $estado != 'Todos' ? " AND R.Estado = '$estado'" : "";
$condicion='';

$tipo_bod = $tipo_bod == 'Punto' ? 'Punto_Dispensacion' : $tipo_bod;
if($tipo_bod=='Punto_Dispensacion'){
	$condicion = " AND Tipo_Destino = '$tipo_bod' AND Id_Destino=$punto";
}elseif($tipo_bod=='Bodega'){
	$condicion = " AND Tipo_Origen = '$tipo_bod' AND Id_Origen=$punto ";
}


if ($tipo && $tipo == 'cliente') {
	$condicion = "AND Id_Destino=$punto AND Tipo_Destino='Cliente'";
}
$condicion.= "  $cond_estado";

$fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
$fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Productos_Remision_'.$rem["Codigo"].'.xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;


$query='SELECT P.Mantis, P.Nombre_Comercial, PR.Cantidad, PR.Precio, PR.Lote, PR.Fecha_Vencimiento, R.Fecha, R.Codigo,(R.Fin_Fase1) as Fase_1, (R.Fin_Fase2) as Fase_2,R.Observaciones,P.Codigo_Cum,P.Fecha_Vencimiento_Invima,P.Invima,P.Laboratorio_Comercial,P.Laboratorio_Generico,
R.Nombre_Destino,
IFNULL(BD.Nombre, PD.Nombre) as Nombre_Origen,
CONCAT(F.Nombres," ", F.Apellidos) as Funcionario, R.Estado
	FROM Producto_Remision PR
	INNER JOIN Producto P ON P.Id_Producto = PR.Id_Producto
	INNER JOIN Remision R ON PR.Id_Remision = R.Id_Remision
	left JOIN Punto_Dispensacion PD ON PD.Id_Punto_Dispensacion = R.Id_Origen AND R.Tipo_Origen = "Punto_Dispensacion"
	Left JOIN Bodega_Nuevo BD ON BD.Id_Bodega_Nuevo = R.Id_Origen AND R.Tipo_Origen = "Bodega"
	INNER JOIn Funcionario F ON R.Identificacion_Funcionario=F.Identificacion_Funcionario
	WHERE (DATE(R.Fecha) BETWEEN "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59") AND R.Estado!="Anulada"
	'.$condicion;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos= $oCon->getData();
unset($oCon);

$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Remision '.$rem["Codigo"]);



$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->getCell('A1')->setValue('Nombre Comercial');
$objSheet->getCell('B1')->setValue('Cantidad');
$objSheet->getCell('C1')->setValue('Precio');
$objSheet->getCell('D1')->setValue('Lote');
$objSheet->getCell('E1')->setValue('Fecha Vencimiento');
$objSheet->getCell('F1')->setValue("Codigo");
$objSheet->getCell('G1')->setValue("Fecha Creacion");
$objSheet->getCell('H1')->setValue("Fase 1");
$objSheet->getCell('I1')->setValue("Fase 2");
$objSheet->getCell('J1')->setValue("Observaciones");
$objSheet->getCell('K1')->setValue("Codigo Cum");
$objSheet->getCell('L1')->setValue("Invima");
$objSheet->getCell('M1')->setValue("Fecha Vencimiento Invima");
$objSheet->getCell('N1')->setValue("Laboratorio Comercial");
$objSheet->getCell('O1')->setValue("Laboratorio Generico");
$objSheet->getCell('P1')->setValue("Origen");
$objSheet->getCell('Q1')->setValue("Destino");
$objSheet->getCell('R1')->setValue("Funcionario");
$objSheet->getCell('S1')->setValue("Estado");


$objSheet->getStyle('A1:S1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:S1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:S1')->getFont()->setBold(true);
$objSheet->getStyle('A1:S1')->getFont()->getColor()->setARGB('FFFFFFFF');

$j=1;
foreach($productos as $prod){ $j++;
	$objSheet->getCell('A'.$j)->setValue($prod["Nombre_Comercial"]);
	$objSheet->getCell('B'.$j)->setValue($prod["Cantidad"]);
	$objSheet->getCell('C'.$j)->setValue($prod["Precio"]);
	$objSheet->getCell('D'.$j)->setValue($prod["Lote"]);
	$objSheet->getCell('E'.$j)->setValue($prod["Fecha_Vencimiento"]);
	$objSheet->getCell('F'.$j)->setValue($prod["Codigo"]);
	$objSheet->getCell('G'.$j)->setValue($prod["Fecha"]);
	$objSheet->getCell('H'.$j)->setValue($prod["Fase_1"]);
	$objSheet->getCell('I'.$j)->setValue($prod["Fase_2"]);
	$objSheet->getCell('J'.$j)->setValue($prod["Observaciones"]);
	$objSheet->getCell('K'.$j)->setValue($prod["Codigo_Cum"]);
	$objSheet->getCell('L'.$j)->setValue($prod["Invima"]);
	$objSheet->getCell('M'.$j)->setValue($prod["Fecha_Vencimiento_Invima"]);
	$objSheet->getCell('N'.$j)->setValue($prod["Laboratorio_Comercial"]);
	$objSheet->getCell('O'.$j)->setValue($prod["Laboratorio_Generico"]);
	$objSheet->getCell('P'.$j)->setValue($prod["Nombre_Origen"]);
	$objSheet->getCell('Q'.$j)->setValue($prod["Nombre_Destino"]);
	$objSheet->getCell('R'.$j)->setValue($prod["Funcionario"]);
	$objSheet->getCell('S'.$j)->setValue($prod["Estado"]);
}

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');


?>