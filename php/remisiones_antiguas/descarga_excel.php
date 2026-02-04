<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set("America/Bogota");

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$oItem = new complex('Remision',"Id_Remision",$id);
$rem = $oItem->getData();
unset($oItem);


require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Productos_Remision_'.$rem["Codigo"].'.xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;

$query='SELECT P.Mantis, P.Nombre_Comercial, PR.Cantidad, PR.Precio, PR.Lote, PR.Fecha_Vencimiento
FROM Producto_Remision_Antigua PR
INNER JOIN Producto P
ON P.Id_Producto = PR.Id_Producto
WHERE PR.Id_Remision ='.$id;


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

$j=0;
foreach($productos as $prod){ $j++;
	$objSheet->getCell('A'.$j)->setValue($prod["Mantis"]);
	$objSheet->getCell('B'.$j)->setValue($prod["Nombre_Comercial"]);
	$objSheet->getCell('C'.$j)->setValue($prod["Cantidad"]);
	$objSheet->getCell('D'.$j)->setValue($prod["Precio"]);
	$objSheet->getCell('E'.$j)->setValue($prod["Lote"]);
	$objSheet->getCell('F'.$j)->setValue($prod["Fecha_Vencimiento"]);
}

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');



?>