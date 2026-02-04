<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set("America/Bogota");


$year = ( isset( $_REQUEST['year'] ) ? $_REQUEST['year'] : '' );
$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : false );
$id_bodega_punto = ( isset( $_REQUEST['id_bodega_punto'] ) ? $_REQUEST['id_bodega_punto'] : false );

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

// header('Content-Type: application/vnd.ms-excel');
header('Content-Type: application/json');
header('Content-Disposition: attachment;filename="Reporte_Vencimientos.xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;


$condicion = '';

if ($tipo) {
    if ($tipo == 'Bodega') {
        if(isset($_REQUEST['id_bodega_punto']) && $_REQUEST['id_bodega_punto']!='todos'){
            $condicion .= " AND E.Id_Bodega_Nuevo =$_REQUEST[id_bodega_punto]";
        }
            
        else{
            $condicion .= " AND I.Id_Bodega!=0 ";
        }
    } else {
        if($_REQUEST['id_bodega_punto']!='todos'){
            $condicion .= " AND E.Id_Punto_Dispensacion=$_REQUEST[id_bodega_punto]";
        }else{
            $condicion .= " AND E.Id_Punto_Dispensacion!=0 ";
        }
       
    }
}

    $query='SELECT P.Nombre_Comercial, P.Embalaje, 
            CONCAT( P.Principio_Activo, " ",
            P.Presentacion, " ",
            P.Concentracion, " ",
            P.Cantidad," ",
            P.Unidad_Medida) as Nombre,
            P.Laboratorio_Comercial,
            I.Lote, I.Fecha_Vencimiento,I.Costo,
            (SELECT Nombre FROM Bodega_Nuevo WHERE Id_Bodega_Nuevo = E.Id_Bodega_Nuevo) as Bodega,
            (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion = E.Id_Punto_Dispensacion) as Punto,
            (I.Cantidad - I.Cantidad_Apartada - I.Cantidad_Seleccionada) as Cantidad
            
            FROM Inventario_Nuevo I
            Inner join Estiba E on E.Id_Estiba = I.Id_Estiba
            INNER JOIN Producto P
            ON P.Id_Producto = I.Id_Producto
            WHERE I.Fecha_Vencimiento LIKE "%'.$year.'%"
            '.$condicion.' AND (I.Cantidad - I.Cantidad_Apartada - I.Cantidad_Seleccionada) > 0
            ORDER BY I.Fecha_Vencimiento ASC';

            $oCon= new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query);
            $vencidos = $oCon->getData();
            unset($oCon);




$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Reporte Vencimientos');

$objSheet->getCell('A1')->setValue("Nombre Comercial");
$objSheet->getCell('B1')->setValue("Nombre");
$objSheet->getCell('C1')->setValue("Embalaje");
$objSheet->getCell('D1')->setValue("Laboratoria Comercial");
$objSheet->getCell('E1')->setValue("Lote");
$objSheet->getCell('F1')->setValue("Fecha Vencimiento");
$objSheet->getCell('G1')->setValue("Bodega");
$objSheet->getCell('H1')->setValue("Punto");
$objSheet->getCell('I1')->setValue("Cantidad");
$objSheet->getCell('J1')->setValue("Costo");

$objSheet->getStyle('A1:J1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:J1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:J1')->getFont()->setBold(true);
$objSheet->getStyle('A1:J1')->getFont()->getColor()->setARGB('FFFFFFFF');


$j=1;
foreach($vencidos as $disp){ $j++;
	$objSheet->getCell('A'.$j)->setValue($disp["Nombre_Comercial"]);
	$objSheet->getCell('B'.$j)->setValue($disp["Nombre"]);
	$objSheet->getCell('C'.$j)->setValue($disp["Embalaje"]);
	$objSheet->getCell('D'.$j)->setValue($disp["Laboratorio_Comercial"]);
	$objSheet->getCell('E'.$j)->setValue($disp["Lote"]);
	$objSheet->getCell('F'.$j)->setValue($disp["Fecha_Vencimiento"]);
	$objSheet->getCell('G'.$j)->setValue($disp["Bodega"]);
	$objSheet->getCell('H'.$j)->setValue($disp["Punto"]);
	$objSheet->getCell('I'.$j)->setValue($disp["Cantidad"]);
	$objSheet->getCell('J'.$j)->setValue($disp["Costo"]);
	
}

$objSheet->getColumnDimension('A')->setAutoSize(true);
$objSheet->getColumnDimension('E')->setAutoSize(true);
$objSheet->getColumnDimension('F')->setAutoSize(true);
$objSheet->getColumnDimension('G')->setAutoSize(true);
$objSheet->getColumnDimension('H')->setAutoSize(true);
$objSheet->getColumnDimension('I')->setAutoSize(true);
$objSheet->getColumnDimension('J')->setAutoSize(true);
$objSheet->getStyle('A1:J'.$j)->getAlignment()->setWrapText(true);


$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');



?>