<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set("America/Bogota");

$id= ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Productos_Lista_'.$id.'.xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;


$query='SELECT P.Nombre_Comercial, P.Embalaje, P.Codigo_Cum, P.Invima, 
CONCAT( P.Principio_Activo, " ",
P.Presentacion, " ",
P.Concentracion, " ",
P.Cantidad," ",
P.Unidad_Medida) as Nombre,
P.Laboratorio_Comercial,
P.Laboratorio_Generico,
PLG.Precio,C.Nombre as Categoria,
IFNULL((SELECT IF(SUM(Cantidad-(Cantidad_Seleccionada+Cantidad_Apartada))<0,0,SUM(Cantidad-(Cantidad_Seleccionada+Cantidad_Apartada))) FROM Inventario WHERE Id_Producto= P.Id_Producto AND Id_Punto_Dispensacion=0),0) as Cantidad_Disponible,(SELECT GROUP_CONCAT(Fecha_Vencimiento SEPARATOR " | ") FROM Inventario WHERE Id_Producto= P.Id_Producto AND Id_Punto_Dispensacion=0 AND Cantidad>0) as Fecha_Vencimiento,P.Gravado
FROM Producto_Lista_Ganancia PLG
INNER JOIN Producto P
ON P.Codigo_Cum = PLG.Cum
INNER JOIn Categoria C On P.Id_Categoria=C.Id_Categoria
WHERE PLG.Id_Lista_Ganancia ='.$id.'
GROUP BY P.Id_Producto
ORDER BY P.Nombre_Comercial ASC

';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos= $oCon->getData();
unset($oCon);

$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Productos_Lista_'.$id);

$j=0;
foreach($productos as $prod){ $j++;
	$objSheet->getCell('A'.$j)->setValue($prod["Nombre_Comercial"]);
	$objSheet->getCell('B'.$j)->setValue($prod["Nombre"]);
	$objSheet->getCell('C'.$j)->setValue($prod["Embalaje"]);
	$objSheet->getCell('D'.$j)->setValue($prod["Laboratorio_Comercial"]);
	$objSheet->getCell('E'.$j)->setValue($prod["Laboratorio_Generico"]);
	$objSheet->getCell('F'.$j)->setValue($prod["Codigo_Cum"]);
	$objSheet->getCell('G'.$j)->setValue($prod["Invima"]);
	$objSheet->getCell('H'.$j)->setValue($prod["Precio"]);
	$objSheet->getCell('I'.$j)->setValue($prod["Cantidad_Disponible"]);
	$objSheet->getCell('J'.$j)->setValue($prod["Fecha_Vencimiento"]);
	$objSheet->getCell('K'.$j)->setValue($prod["Gravado"]);
	$objSheet->getCell('L'.$j)->setValue($prod["Categoria"]);
}

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');


?>