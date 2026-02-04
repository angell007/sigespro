<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

date_default_timezone_set("America/Bogota");

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Listado_Pacientes_Callcenter_Rem_'.$id.'.xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;

$query='SELECT  R.Id_Remision, P.Id_Paciente,  R.Id_Destino, R.Fini_Rotativo,  R.FFin_Rotativo, R.Codigo AS Remision, 
CONCAT_WS(" ", P.Primer_Nombre, P.Primer_Apellido) AS Paciente, 
P.Telefono,
P.EPS, 
RC.*,
(SELECT PT.Numero_Telefono FROM Paciente_Telefono PT WHERE PT.Id_Paciente = P.Id_Paciente LIMIT 1) AS Telefono2,
IFNULL(PD.Nombre_Comercial,"Sin Producto") as Producto, RC.Id_Producto,
IFNULL(D.Codigo,"Sin Dispensacion") as Dispensacion, RC.Id_Dispensacion
FROM Remision_Callcenter RC
INNER JOIN Paciente P ON P.Id_Paciente = RC.Id_Paciente
INNER JOIN Remision R ON R.Id_Remision = RC.Id_Remision 
LEFT JOIN Producto PD ON PD.Id_Producto = RC.Id_Producto
LEFT JOIN Dispensacion D ON D.Id_Dispensacion = RC.Id_Dispensacion
WHERE RC.Id_Remision_Callcenter_Anterior IS NULL AND RC.Id_Remision ='.$id;

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$pacientes = $oCon->getData();
unset($oCon);


$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Listado Pacientes');

$objSheet->getCell('A1')->setValue("Remision");
$objSheet->getCell('B1')->setValue("Dispensacion");
$objSheet->getCell('C1')->setValue("Paciente");
$objSheet->getCell('D1')->setValue("Producto");
$objSheet->getCell('E1')->setValue("Cantidad Pendiente");
$objSheet->getCell('F1')->setValue("Telefonos");
$objSheet->getCell('G1')->setValue("Estado Llamada");
$objSheet->getCell('H1')->setValue("Observaciones Llamada");
$objSheet->getCell('I1')->setValue("Proxima Fecha");

$j=1;
$cont = 0;

foreach($pacientes as $pac){ $j++;

    $objSheet->getCell('A'.$j)->setValue($pac["Remision"]);
    $objSheet->getCell('B'.$j)->setValue($pac["Dispensacion"]);
    $objSheet->getCell('C'.$j)->setValue($pac["Paciente"]);
    $objSheet->getCell('D'.$j)->setValue($pac["Producto"]);
    $objSheet->getCell('E'.$j)->setValue($pac["Pendientes"]);
    $objSheet->getCell('F'.$j)->setValue($pac["Telefono"]." - ".$pac["Telefono2"]);
    $objSheet->getCell('G'.$j)->setValue($pac["Estado"]);
    $objSheet->getCell('H'.$j)->setValue($pac["Observacion"]);
    $objSheet->getCell('I'.$j)->setValue($pac["Fecha_Prox_Llamada"]);
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

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');
?>