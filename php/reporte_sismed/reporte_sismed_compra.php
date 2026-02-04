<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$meses= ( isset( $_REQUEST['meses'] ) ? $_REQUEST['meses'] : '' );
$ano= ( isset( $_REQUEST['ano'] ) ? $_REQUEST['ano'] : '' );


$meses=explode("-", $meses);

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_Sismed.xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;

$resultado=[];

for ($i=0; $i < count($meses); $i++) { 
    
    $query2 = 'SELECT
    MONTH(FAR.Fecha_Factura) as Mes,
    P.Codigo_Cum,
    PR.Precio as Precio_Regulacion,
    MAX(PAR.Precio) as Maximo,
    MIN(PAR.Precio) as Minimo,
    MAX(CONCAT(PAR.Precio,"|",FAR.Factura)) AS Maximo_Factura,
    MIN(CONCAT(PAR.Precio,"|",FAR.Factura)) AS Minimo_Factura,
    SUM(PAR.Precio*PAR.Cantidad) AS Precio,
    SUM(PAR.Cantidad) AS Cantidad,
    IFNULL(CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," (",P.Nombre_Comercial, ") ", P.Cantidad," ", P.Unidad_Medida, " "), CONCAT(P.Nombre_Comercial, " LAB-", P.Laboratorio_Comercial)) as Nombre_Producto
    FROM
    Producto_Acta_Recepcion PAR
    INNER JOIN Factura_Acta_Recepcion FAR ON PAR.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND FAR.Factura = PAR.Factura
    INNER JOIN Producto P ON P.Id_Producto = PAR.Id_Producto
    LEFT JOIN Precio_Regulado PR ON P.Codigo_Cum = PR.Codigo_Cum
    WHERE MoNTH(FAR.Fecha_Factura)='.$meses[$i].' AND YEAR(FAR.Fecha_Factura)='.$ano.' AND P.Id_Categoria IN (12,8,9,3,5,10) GROUP by P.Codigo_Cum;';
    


    $oCon= new consulta();
    $oCon->setQuery($query2);
    $oCon->setTipo('Multiple');
    $resultado[] = $oCon->getData();
    unset($oCon);
}

$resultado=array_merge($resultado[0],$resultado[1],$resultado[2]);

$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Reporte Sismed');

$objSheet->getCell('A1')->setValue("");
$objSheet->getCell('B1')->setValue("");
$objSheet->getCell('C1')->setValue(" ");
$objSheet->getCell('D1')->setValue(" ");
$objSheet->getCell('E1')->setValue(" ");
$objSheet->getCell('F1')->setValue(" ");
$objSheet->getCell('G1')->setValue(" ");
$objSheet->getCell('H1')->setValue(" ");
$objSheet->getCell('I1')->setValue(" ");
$objSheet->getCell('J1')->setValue(" ");
$objSheet->getCell('K1')->setValue(" ");
$objSheet->getCell('L1')->setValue(" ");
$objSheet->getCell('M1')->setValue(" ");

$objSheet->getStyle('A1:M1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
/*$objSheet->getStyle('A1:F1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:F1')->getFont()->setBold(true);
$objSheet->getStyle('A1:F1')->getFont()->getColor()->setARGB('FFFFFFFF');*/

$j=1;
$i=0;

foreach($resultado as $item){ $j++;$i++;
	$objSheet->getCell('A'.$j)->setValue("2");
	$objSheet->getCell('B'.$j)->setValue($i);
	$objSheet->getCell('C'.$j)->setValue($item['Mes']);
	$objSheet->getCell('D'.$j)->setValue("INS");
	$objSheet->getCell('E'.$j)->setValue($item["Codigo_Cum"]);
	$objSheet->getCell('F'.$j)->setValue($item["Minimo"]);
	$objSheet->getCell('G'.$j)->setValue($item["Maximo"]);
	$objSheet->getCell('H'.$j)->setValue($item["Precio"]);
    $objSheet->getCell('I'.$j)->setValue($item["Cantidad"]);
    $factura=explode("|",$item['Minimo_Factura']);
    $factura_maxima=explode("|",$item['Maximo_Factura']);
	$objSheet->getCell('J'.$j)->setValue($factura[1],PHPExcel_Cell_DataType::TYPE_STRING);
	$objSheet->getCell('K'.$j)->setValue( $factura_maxima[1]);
	$objSheet->getCell('L'.$j)->setValue( $item['Nombre_Producto']);
    $objSheet->getCell('M'.$j)->setValue( $item['Precio_Regulacion']);
	
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
$objSheet->getStyle('A1:M'.$j)->getAlignment()->setWrapText(true);

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

?>