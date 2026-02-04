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
header('Content-Disposition: attachment;filename="Inventario Fisico Puntos.xls"');
header('Cache-Control: max-age=0'); 

$objPHPExcel = new PHPExcel;

$id_inventario_fisico = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
$id_pto = isset($_REQUEST['id_pto']) ? $_REQUEST['id_pto'] : false;
$condicion = '';

if ($id_inventario_fisico) {
    $condicion = "WHERE INF.Id_Inventario_Fisico_Punto=$id_inventario_fisico";
} else {
    $condicion = "WHERE INF.Id_Punto_Dispensacion=$id_pto";
}


$query = 'SELECT INF.*, DATE_FORMAT(Fecha_Inicio, "%d/%m/%Y %r") AS f_inicio, DATE_FORMAT(Fecha_Fin, "%d/%m/%Y %r") AS f_fin, (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=INF.Id_Punto_Dispensacion) AS Nom_Bodega,  (SELECT CONCAT(Nombres," ",Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=INF.Funcionario_Digita) AS Funcionario_Digitador, (SELECT CONCAT(Nombres," ",Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=INF.Funcionario_Cuenta) AS Funcionario_Cuenta, (SELECT CONCAT(Nombres," ",Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=INF.Funcionario_Digita) AS Funcionario_Autorizo
FROM Inventario_Fisico_Punto INF '.$condicion;
$oCon= new consulta();
$oCon->setQuery($query);
$datos = $oCon->getData();
unset($oCon);

$query = 'SELECT PIF.Id_Producto_Inventario_Fisico,PIF.Cantidad_Inventario, PIF.Id_Inventario_Fisico_Punto,  P.Nombre_Comercial, CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion, P.Cantidad," ", P.Unidad_Medida, " ") AS Nombre_Producto, PIF.Lote, PIF.Fecha_Vencimiento, PIF.Primer_Conteo AS Cantidad_Encontrada, IF(INF.Inventario="Si", PIF.Cantidad_Inventario,PIF.Segundo_Conteo) AS Segundo_Conteo, (SELECT AVG(I.Costo) FROM Inventario I WHERE I.Id_Producto=PIF.Id_Producto) as Costo
, IF(INF.Inventario="Si", (PIF.Cantidad_Inventario-PIF.Primer_Conteo), (PIF.Segundo_Conteo-PIF.Primer_Conteo) ) AS Cantidad_Diferencial, PIF.Cantidad_Final,P.Codigo_Cum, P.Invima FROM Producto_Inventario_Fisico_Punto PIF INNER JOIN Producto P ON PIF.Id_Producto=P.Id_Producto INNER JOIN Inventario_Fisico_Punto INF ON PIF.Id_Inventario_Fisico_Punto=INF.Id_Inventario_Fisico_Punto '.$condicion.' ORDER BY P.Nombre_Comercial';
 
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos = $oCon->getData();
unset($oCon);

$total = count($productos);


$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Inventario Fisico Punto');

$objSheet->getCell('A1')->setValue("Punto Dispensacion ");
$objSheet->getCell('B1')->setValue($datos['Nom_Bodega']);
$objSheet->getCell('C1')->setValue("Funcionario Cuenta");
$objSheet->getCell('D1')->setValue($datos["Funcionario_Digitador"]);
$objSheet->getCell('E1')->setValue("Funcionario Digita");
$objSheet->getCell('F1')->setValue($datos["Funcionario_Cuenta"]);
$objSheet->getCell('A2')->setValue("Nro.");
$objSheet->getCell('B2')->setValue("Producto");
$objSheet->getCell('C2')->setValue("Lote");
$objSheet->getCell('D2')->setValue("Fecha Venc.");
$objSheet->getCell('E2')->setValue("Primer Conteo");
$objSheet->getCell('F2')->setValue($datos['Inventario']=='Si' ? "Cantidad Inventario" : "Segundo Conteo");
$objSheet->getCell('G2')->setValue("Diferencia");
$objSheet->getCell('H2')->setValue("Cantidad Final");
$objSheet->getCell('I2')->setValue("Costo del Producto");
$objSheet->getCell('J2')->setValue("Valor Inicial");
$objSheet->getCell('K2')->setValue("Valor Final");
$objSheet->getCell('L2')->setValue("Codigo Cum");
$objSheet->getCell('M2')->setValue("Invima");
$objSheet->getStyle('A2:M2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A2:M2')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A2:M2')->getFont()->setBold(true);
$objSheet->getStyle('A2:M2')->getFont()->getColor()->setARGB('FFFFFFFF');
  

$j=2;
foreach($productos as $value){ $j++;
	$valor_diferencia=$value['Cantidad_Final']*$value["Costo"];
	$valor_inicial=$value['Cantidad_Inventario']*$value["Costo"];
	$objSheet->getCell('A'.$j)->setValue($j);
	$objSheet->getCell('B'.$j)->setValue($value["Nombre_Comercial"].' '.$value["Nombre_Producto"]);
	$objSheet->getCell('C'.$j)->setValue($value["Lote"]);
	$objSheet->getCell('D'.$j)->setValue($value["Fecha_Vencimiento"]);
	$objSheet->getCell('E'.$j)->setValue($value["Cantidad_Encontrada"]);
	$objSheet->getCell('F'.$j)->setValue($value['Segundo_Conteo']);
	$objSheet->getCell('G'.$j)->setValue($value["Cantidad_Diferencial"]);
	$objSheet->getCell('H'.$j)->setValue($value["Cantidad_Final"]);
	$objSheet->getCell('I'.$j)->setValue($value["Costo"]);
	$objSheet->getCell('J'.$j)->setValue($valor_inicial);
	$objSheet->getCell('K'.$j)->setValue($valor_diferencia);
	$objSheet->getStyle('J'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	$objSheet->getStyle('I'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	$objSheet->getStyle('K'.$j)->getNumberFormat()->setFormatCode("#,##0.00");

	$objSheet->getCell('L'.$j)->setValue($value["Codigo_Cum"]); 
	$objSheet->getCell('M'.$j)->setValue($value["Invima"]); 
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
$objSheet->getStyle('A1:L'.$j)->getAlignment()->setWrapText(true);


$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');



?>