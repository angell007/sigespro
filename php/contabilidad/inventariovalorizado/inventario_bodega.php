<?php
ini_set('memory_limit', '2048M');
set_time_limit(0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

date_default_timezone_set("America/Bogota");

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

 header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Inventario Valorizado - Bodega.xls"');
header('Cache-Control: max-age=0'); 

$objPHPExcel = new PHPExcel;


$query = "SELECT
    B.Nombre AS Bodega,
    COUNT(DISTINCT(I.Id_Producto)) AS Cant_Producto,
    SUM(Cantidad) AS Cantidad,
    ROUND(
        SUM(
            Cantidad *(
                COALESCE( CP.Costo_Promedio,0 )
            )),
            2
        ) AS Costo
    FROM
        Inventario_Nuevo I
    INNER JOIN Estiba E ON
        E.Id_Estiba = I.Id_Estiba
    INNER JOIN Bodega_Nuevo B ON
        B.Id_Bodega_Nuevo = E.Id_Bodega_Nuevo
    LEFT JOIN Costo_Promedio CP ON CP.Id_Producto = I.Id_Producto
    GROUP BY
        E.Id_Bodega_Nuevo";


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$bodegas= $oCon->getData();
unset($oCon);



$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Inventario Valorizado - Bodega');

$objSheet->getCell('A1')->setValue("BODEGA");
$objSheet->getCell('B1')->setValue("PRODUCTOS");
$objSheet->getCell('C1')->setValue("CANTIDADES TOTALES");
$objSheet->getCell('D1')->setValue("COSTO TOTAL");
$objSheet->getStyle('A1:D1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:D1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:D1')->getFont()->setBold(true);
$objSheet->getStyle('A1:D1')->getFont()->getColor()->setARGB('FFFFFFFF');

$totalCant = 0;
$totalCosto = 0;
$j=1;
foreach($bodegas as $value){ $j++;
	$objSheet->getCell('A'.$j)->setValue($value["Bodega"]);
	$objSheet->getCell('B'.$j)->setValue(($value["Cant_Producto"]));
	$objSheet->getCell('C'.$j)->setValue($value["Cantidad"]);
	$objSheet->getCell('D'.$j)->setValue($value["Costo"]);

	$totalCant += $value["Cantidad"];
	$totalCosto += $value["Costo"];
}
$objSheet->getCell('C'.($j+1))->setValue($totalCant);
$objSheet->getCell('D'.($j+1))->setValue($totalCosto);
$objSheet->getStyle('C'.($j+1).':D'.($j+1))->getFont()->setBold(true);

$objSheet->getColumnDimension('A')->setAutoSize(true);
$objSheet->getColumnDimension('B')->setAutoSize(true);
$objSheet->getColumnDimension('C')->setAutoSize(true);
$objSheet->getColumnDimension('D')->setAutoSize(true);
$objSheet->getStyle('A1:D'.$j)->getAlignment()->setWrapText(true);


$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');



?>