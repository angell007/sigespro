<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set("America/Bogota");


$condicion='';
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : false );
if($id=='todos'){
	$condicion=' AND Es.Id_Punto_Dispensacion!=0';
}else{
	$condicion=' AND Es.Id_Punto_Dispensacion='.$id;
}
$permiso = permiso();
 
require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

// header('Content-Type: application/json');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Inventario_Punto.xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;


$query ='SELECT I.*,IFNULL(I.Costo,(SELECt ROUND(AVG(Costo)) FROM Inventario_Nuevo WHERE Id_Bodega!=0  AND Id_Producto=I.Id_Producto)) as Costo, (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) AS Cantidad_Disponible, PRD.Laboratorio_Generico , CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida, " ") as Nombre_Producto, PRD.Tipo,
PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, PRD.Invima, PRD.Embalaje, (SELECT Nombre FROM Punto_Dispensacion P WHERE P.Id_Punto_Dispensacion=I.Id_Punto_Dispensacion) as Punto, (SELECT CONCAT(" Fecha : ",DATE(AR.Fecha_Creacion) ," -  Acta: ", AR.Codigo ) FROM Producto_Acta_Recepcion PAR INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion  WHERE PAR.Id_Producto=I.Id_Producto AND AR.Tipo_Acta="Punto_Dispensacion" Order BY PAR.Id_Producto_Acta_Recepcion DESC Limit 1 ) as Ultima_Compra
FROM Inventario_Nuevo I
INNER JOIN Estiba As Es ON I.Id_Estiba = Es.Id_Estiba
INNER JOIN Producto PRD
On I.Id_Producto=PRD.Id_Producto
INNER JOIN Punto_Dispensacion PD ON Es.Id_Punto_Dispensacion=PD.Id_Punto_Dispensacion
WHERE 
 (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) > 0  
'
.$condicion.

' ORDER BY Punto';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos= $oCon->getData();



unset($oCon);


$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Reporte Inventario Punto');

$objSheet->getCell('A1')->setValue("Nombre Comercial");
$objSheet->getCell('B1')->setValue("Nombre");
$objSheet->getCell('C1')->setValue("Embalaje");
$objSheet->getCell('D1')->setValue("Lab. Comercial");
$objSheet->getCell('E1')->setValue("Lab. Generico");
$objSheet->getCell('F1')->setValue("Invima");
$objSheet->getCell('G1')->setValue("Cum");
$objSheet->getCell('H1')->setValue("Lote");
$objSheet->getCell('I1')->setValue("Fecha Vencimiento");
$objSheet->getCell('J1')->setValue("Cantidad");
$objSheet->getCell('K1')->setValue("Punto");
if($permiso){
	$objSheet->getCell('M1')->setValue("Costo");
	$objSheet->getCell('N1')->setValue("Ultima Compra");
}

$objSheet->getStyle('A1:N1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:N1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:N1')->getFont()->setBold(true);
$objSheet->getStyle('A1:N1')->getFont()->getColor()->setARGB('FFFFFFFF');


$j=1;
foreach($productos as $disp){ $j++;
	$objSheet->getCell('A'.$j)->setValue($disp["Nombre_Comercial"]);
	$objSheet->getCell('B'.$j)->setValue($disp["Nombre_Producto"]);
	$objSheet->getCell('C'.$j)->setValue($disp["Embalaje"]);
	$objSheet->getCell('D'.$j)->setValue($disp["Laboratorio_Comercial"]);
	$objSheet->getCell('E'.$j)->setValue($disp["Nombre_Comercial"]);
	$objSheet->getCell('F'.$j)->setValue($disp["Invima"]);
	$objSheet->getCell('G'.$j)->setValue($disp["Codigo_CUM"]);
	$objSheet->getCell('H'.$j)->setValue($disp["Lote"]);
	$objSheet->getCell('I'.$j)->setValue($disp["Fecha_Vencimiento"]);
	$objSheet->getCell('J'.$j)->setValue($disp["Cantidad_Disponible"]);
	$objSheet->getCell('K'.$j)->setValue($disp["Punto"]);
	if($permiso){
		$objSheet->getCell('M'.$j)->setValue($disp["Costo"]);
		$objSheet->getCell('N'.$j)->setValue($disp["Ultima_Compra"]);
	}
	
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
$objSheet->getColumnDimension('M')->setAutoSize(true);
$objSheet->getStyle('A1:M'.$j)->getAlignment()->setWrapText(true);


$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

function permiso(){
	$identificacion_funcionario = $_SESSION["user"];
	if($identificacion_funcionario==''){
		$identificacion_funcionario=$_REQUEST['funcionario'];
	}
	$permiso=false;
	if($identificacion_funcionario!=''){
		$query = 'SELECT Ver_Costo FROM Funcionario WHERE Ver_Costo="Si" AND Identificacion_Funcionario='.$identificacion_funcionario; 
		$oCon= new consulta();
		$oCon->setQuery($query);
		$permisos = $oCon->getData();
		unset($oCon);
	}
	$status = false; // Sin permisos

	if ($permisos) {
		$status = true;
	}
	return $status;
}
