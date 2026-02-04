<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set("America/Bogota");

$id= ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$bodega= ( isset( $_REQUEST['bodega'] ) ? $_REQUEST['bodega'] : 1 );

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Productos_Lista_'.$id.'.xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;


$query="SELECT 
P.Nombre_Comercial,
P.Embalaje,
P.Codigo_Cum,
P.Invima,
CONCAT(P.Principio_Activo,
	  ' ',
	  P.Presentacion,
	  ' ',
	  P.Concentracion,
	  ' ',
	  P.Cantidad,
	  ' ',
	  P.Unidad_Medida) AS Nombre,
P.Laboratorio_Comercial,
P.Laboratorio_Generico,
LEAST(ifnull(REG.Precio_Venta, PLG.Precio) , IFNULL(PLG.Precio, 0)) as Precio,
C.Nombre AS Subcategoria,
IFNULL((SELECT 
		    IF(SUM(Cantidad - (Cantidad_Seleccionada + Cantidad_Apartada)) < 0,
				0,
				SUM(Cantidad - (Cantidad_Seleccionada + Cantidad_Apartada)))
		FROM
		    Inventario_Nuevo
		WHERE
		    Id_Producto = P.Id_Producto
			  AND Id_Estiba IN (SELECT 
				Id_Estiba
			  FROM
				Estiba
			  WHERE
				Id_Bodega_Nuevo = $bodega)),
	  0) AS Cantidad_Disponible,
(SELECT 
	  GROUP_CONCAT(Fecha_Vencimiento
		    SEPARATOR ' | ')
    FROM
	  Inventario_Nuevo
    WHERE
	  Id_Producto = P.Id_Producto
		AND Id_Punto_Dispensacion = 0
		AND Cantidad > 0) AS Fecha_Vencimiento,
P.Gravado, 
IF(REG.Codigo_Cum is not null, 'Si', 'No') as Regulado
FROM
Producto_Lista_Ganancia PLG
    INNER JOIN Producto P ON P.Codigo_Cum = PLG.Cum
    INNER JOIN Subcategoria C ON P.Id_Subcategoria = C.Id_Subcategoria
    LEFT JOIN Precio_Regulado REG ON REG.Codigo_Cum = PLG.Cum
WHERE
PLG.Id_Lista_Ganancia = $id
GROUP BY P.Id_Producto
ORDER BY P.Nombre_Comercial ASC";
// echo $query; exit;

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


$objSheet->getCell('A1')->setValue("Nombre_Comercial");
$objSheet->getCell('B1')->setValue("Nombre");
$objSheet->getCell('C1')->setValue("Embalaje");
$objSheet->getCell('D1')->setValue("Laboratorio_Comercial");
$objSheet->getCell('E1')->setValue("Laboratorio_Generico");
$objSheet->getCell('F1')->setValue("Codigo_Cum");
$objSheet->getCell('G1')->setValue("Invima");
$objSheet->getCell('H1')->setValue("Precio");
$objSheet->getCell('I1')->setValue("Cantidad_Disponible");
$objSheet->getCell('J1')->setValue("Fecha_Vencimiento");
$objSheet->getCell('K1')->setValue("Gravado");
$objSheet->getCell('L1')->setValue("Subcategoria");
$objSheet->getCell('L1')->setValue("Regulado");



$j=1;
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
	$objSheet->getCell('L'.$j)->setValue($prod["Subcategoria"]);
	$objSheet->getCell('L'.$j)->setValue($prod["Regulado"]);
}

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');


?>