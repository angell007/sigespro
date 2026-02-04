<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/class.querybasedatos.php');

$id_orden = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$queryObj = new QueryBaseDatos();
$datos_procentajes=[];

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Parcial.xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;

$parcial = GetParcial($id_orden);
$productos_orden_compra = GetProductosParcial($id_orden);



$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Parcial');

$objSheet->getCell('A1')->setValue("Nombre Comercial");
$objSheet->getCell('B1')->setValue("Lote");
$objSheet->getCell('C1')->setValue("Cantidad");
$objSheet->getCell('D1')->setValue("Precio Unitario Pesos");
$objSheet->getCell('E1')->setValue("Porcentaje Arancel");
$objSheet->getCell('F1')->setValue("Total Flete");
$objSheet->getCell('G1')->setValue("Total Seguro");
$objSheet->getCell('H1')->setValue("Total Arancel");
$objSheet->getCell('I1')->setValue("Precio Unitario Final");
$objSheet->getCell('J1')->setValue("Subtotal");


$objSheet->getStyle('A1:J1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:J1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:J1')->getFont()->setBold(true);
$objSheet->getStyle('A1:J1')->getFont()->getColor()->setARGB('FFFFFFFF');


$j=1;
foreach($productos_orden_compra as $p){ $j++;
	$objSheet->getCell('A'.$j)->setValue($p["Nombre_Comercial"]);
	$objSheet->getCell('B'.$j)->setValue($p["Lote"]);
	$objSheet->getCell('C'.$j)->setValue($p["Cantidad"]);
	$objSheet->getCell('D'.$j)->setValue($p["Precio_Unitario_Pesos"]);
	$objSheet->getCell('E'.$j)->setValue($p["Porcentaje_Arancel"]);
	$objSheet->getCell('F'.$j)->setValue($p["Total_Flete"]);
	$objSheet->getCell('G'.$j)->setValue($p["Total_Seguro"]);
	$objSheet->getCell('H'.$j)->setValue($p["Total_Arancel"]);
	$objSheet->getCell('I'.$j)->setValue($p["Precio_Unitario_Final"]);
    $objSheet->getCell('J'.$j)->setValue($p["Subtotal"]);
    $objSheet->getStyle('D'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	$objSheet->getStyle('F'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	$objSheet->getStyle('G'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	$objSheet->getStyle('H'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	$objSheet->getStyle('I'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	$objSheet->getStyle('J'.$j)->getNumberFormat()->setFormatCode("#,##0.00");

	
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
$objSheet->getStyle('A1:J'.$j)->getAlignment()->setWrapText(true);


$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');


function GetParcial($id_orden){
    global $queryObj;

    $query = 'SELECT 
    NP.*,
    (SELECT 
        IF((Primer_Nombre IS NULL OR Primer_Nombre = ""), Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido))
     FROM Proveedor
    WHERE Id_Proveedor = ARI.Tercero_Flete_Nacional) AS Nombre_Tercero_Flete_Nacional,
    (SELECT 
        IF((Primer_Nombre IS NULL OR Primer_Nombre = ""), Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido))
     FROM Proveedor
    WHERE Id_Proveedor = NP.Tercero_Tramite_Sia) AS Nombre_Tercero_Tramite_Sia,
    (SELECT 
        IF((Primer_Nombre IS NULL OR Primer_Nombre = ""), Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido))
     FROM Proveedor
    WHERE Id_Proveedor = ARI.Tercero_Licencia_Importacion) AS Nombre_Tercero_Licencia_Importacion,
    (SELECT 
        IF((Primer_Nombre IS NULL OR Primer_Nombre = ""), Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido))
     FROM Proveedor
    WHERE Id_Proveedor = NP.Tercero_Formulario) AS Nombre_Tercero_Formulario,
    (SELECT 
        IF((Primer_Nombre IS NULL OR Primer_Nombre = ""), Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido))
     FROM Proveedor
    WHERE Id_Proveedor = NP.Tercero_Cargue) AS Nombre_Tercero_Cargue,
    (SELECT 
        IF((Primer_Nombre IS NULL OR Primer_Nombre = ""), Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido))
     FROM Proveedor
    WHERE Id_Proveedor = NP.Tercero_Gasto_Bancario) AS Nombre_Tercero_Gasto_Bancario,(SELECT CONCAT(Nombres,"", Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=ARI.Identificacion_Funcionario) as Funcionario
FROM Nacionalizacion_Parcial NP
INNEr JOIN Acta_Recepcion_Internacional ARI ON NP.Id_Acta_Recepcion_Internacional = ARI.Id_Acta_Recepcion_Internacional
WHERE
    NP.Id_Nacionalizacion_Parcial = '.$id_orden;

    $queryObj->SetQuery($query);
    $orden_compra = $queryObj->ExecuteQuery('simple');
    return $orden_compra;
}

function GetProductosParcial($id_orden){
    global $queryObj, $datos_procentajes;

    

    $query = ' SELECT 
    PNP.*,
    P.Nombre_Comercial,
    IFNULL(P.Nombre_Listado, "No english name") AS Nombre_Ingles,
    P.Embalaje,
    IF(P.Gravado = "No", 0, 19) AS Gravado,
    PARI.Lote
FROM Producto_Nacionalizacion_Parcial PNP
INNER JOIN Producto P ON PNP.Id_Producto = P.Id_Producto
INNER JOIN  Producto_Acta_Recepcion_Internacional PARI ON PNP.Id_Producto_Acta_Recepcion_Internacional = PARI.Id_Producto_Acta_Recepcion_Internacional
WHERE
    PNP.Id_Nacionalizacion_Parcial ='.$id_orden;

    $queryObj->SetQuery($query);
    $productos_orden = $queryObj->ExecuteQuery('multiple');
    
    $datos_procentajes=$productos_orden[0];

    return $productos_orden;
}


?>