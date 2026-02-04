<?php
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set("America/Bogota");

$condicion='';

if (isset( $_REQUEST['id'] )&& $_REQUEST['id'] != "" && $_REQUEST['id'] != "0") {
    $condicion .= " AND I.Id_Bodega=$_REQUEST[id] ";
} else {
	$condicion .= " AND I.Id_Bodega<>0";
}
// var_dump($_SESSION);
// exit;
$permiso = permiso();

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_Inventario.xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;


$query ='SELECT I.*, (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) AS Cantidad_Disponible, PRD.Laboratorio_Generico , CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida, " ") as Nombre_Producto, PRD.Tipo,
PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, PRD.Invima, PRD.Embalaje, b.Nombre as Bodega,(SELECT CONCAT(" Fecha : ",DATE(AR.Fecha_Creacion) ," -  Acta: ", AR.Codigo ) FROM Producto_Acta_Recepcion PAR INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion  WHERE PAR.Id_Producto=I.Id_Producto AND AR.Tipo_Acta="Bodega" Order BY PAR.Id_Producto_Acta_Recepcion DESC Limit 1 ) as Ultima_Compra, ( SELECT CONCAT("Fecha: ",DATE(F.Fecha_Documento), " - Factura: ",F.Codigo) FROM Producto_Factura_Venta PF INNER JOIN Factura_Venta F ON PF.Id_Factura_Venta=F.Id_Factura_Venta WHERE PF.Id_Producto=I.Id_Producto ORDER BY PF.Id_Producto_Factura_Venta DESC LIMIT 1 ) as Ultima_Venta,(SELECT CONCAT(DATE(R.Fecha), " - ",R.Codigo ) FROM Producto_Remision PR INNER JOIN Remision R ON PR.Id_Remision=R.Id_Remision WHERE R.Tipo="Interna" AND PR.Id_Producto=I.Id_Producto ORDER BY PR.Id_Producto_Remision DESC LIMIT 1) as Ultima_Rem,(SELECT Nombre FROM Categoria WHERE ID_Categoria=PRD.Id_Categoria) as Categoria
FROM Inventario I
STRAIGHT_JOIN Producto PRD
On I.Id_Producto=PRD.Id_Producto
STRAIGHT_JOIN Bodega b ON I.Id_Bodega=b.Id_Bodega
WHERE (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) >= 0  '.$condicion.' Order BY I.Id_Bodega,Nombre_Producto';

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

$objSheet->getCell('A1')->setValue("Nombre Comercial");
$objSheet->getCell('B1')->setValue("Nombre");
$objSheet->getCell('C1')->setValue("Embalaje");
$objSheet->getCell('D1')->setValue("Lab. Comercial");
$objSheet->getCell('E1')->setValue("Lab. Generico");
$objSheet->getCell('F1')->setValue("Invima");
$objSheet->getCell('G1')->setValue("Categoria");
$objSheet->getCell('H1')->setValue("Cum");
$objSheet->getCell('I1')->setValue("Lote");
$objSheet->getCell('J1')->setValue("Fecha Vencimiento");
$objSheet->getCell('K1')->setValue("Cantidad");
$objSheet->getCell('L1')->setValue("Costo");
$objSheet->getCell('M1')->setValue("Bodega");
if($permiso){
	$objSheet->getCell('N1')->setValue("Ultima Compra");
	$objSheet->getCell('O1')->setValue("Ultima Venta");
	$objSheet->getCell('O1')->setValue("Ultima Remision ");
}

$objSheet->getStyle('A1:M1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:M1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:M1')->getFont()->setBold(true);
$objSheet->getStyle('A1:M1')->getFont()->getColor()->setARGB('FFFFFFFF');

if($permiso){
	$objSheet->getStyle('A1:O1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objSheet->getStyle('A1:O1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
	$objSheet->getStyle('A1:O1')->getFont()->setBold(true);
	$objSheet->getStyle('A1:O1')->getFont()->getColor()->setARGB('FFFFFFFF');
}

$j=1;
foreach($productos as $disp){ $j++;
	$objSheet->getCell('A'.$j)->setValue($disp["Nombre_Comercial"]);
	$objSheet->getCell('B'.$j)->setValue($disp["Nombre_Producto"]);
	$objSheet->getCell('C'.$j)->setValue($disp["Embalaje"]);
	$objSheet->getCell('D'.$j)->setValue($disp["Laboratorio_Comercial"]);
	$objSheet->getCell('E'.$j)->setValue($disp["Laboratorio_Generico"]);
	$objSheet->getCell('F'.$j)->setValue($disp["Invima"]);
	$objSheet->getCell('G'.$j)->setValue($disp["Categoria"]);
	$objSheet->getCell('H'.$j)->setValue($disp["Codigo_CUM"]);
	$objSheet->getCell('I'.$j)->setValue($disp["Lote"]);
	$objSheet->getCell('J'.$j)->setValue($disp["Fecha_Vencimiento"]);
	$objSheet->getCell('K'.$j)->setValue($disp["Cantidad_Disponible"]);
	if ($permiso) {
		
		$objSheet->getCell('L'.$j)->setValue($disp["Costo"]);
	}
	$objSheet->getCell('M'.$j)->setValue($disp["Bodega"]);
	if($permiso){
		$objSheet->getCell('N'.$j)->setValue($disp["Ultima_Compra"]);
		$objSheet->getCell('O'.$j)->setValue($disp["Ultima_Venta"]);
		$objSheet->getCell('O'.$j)->setValue($disp["Ultima_Rem"]);
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
$objSheet->getColumnDimension('L')->setAutoSize(true);
$objSheet->getColumnDimension('M')->setAutoSize(true);
$objSheet->getColumnDimension('N')->setAutoSize(true);
$objSheet->getStyle('A1:N'.$j)->getAlignment()->setWrapText(true);


$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

function permiso(){
	$identificacion_funcionario = $_SESSION["user"];
	if($identificacion_funcionario==''){
		$identificacion_funcionario=$_REQUEST['funcionario'];
	}
	$query = 'SELECT Ver_Costo FROM Funcionario WHERE Ver_Costo="Si" AND Identificacion_Funcionario='.$identificacion_funcionario; 
	$oCon= new consulta();
	$oCon->setQuery($query);
	$permisos = $oCon->getData();
	unset($oCon);



	$status = false; // Sin permisos

	if ($permisos) {
		$status = true;
	}

	return $status;
}

?>