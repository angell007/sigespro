<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.nomina.php');
include_once('../../class/class.parafiscales.php');
include_once('../../class/class.provisiones.php');

$nom=( isset( $_REQUEST['quincena'] ) ? $_REQUEST['quincena'] : '' );

$quin = explode(";",$nom);
$ini="-1";
$fin="-15";
if($quin[1]==2){
    $ini="-16";
    $fin="-".date("d",(mktime(0,0,0,date("m",strtotime($quin[0]."-1"))+1,1,date("Y",strtotime($quin[0]."-1")))-1));
}
$fini = $quin[0].$ini;
$ffin = $quin[0].$fin;

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Resumen_Novedades.xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel; 

 $query = 'SELECT  N.*, CONCAT_WS(" ",F.Nombres,F.Apellidos) as Funcionario, TN.Novedad as Novedad, TN.Tipo_Novedad as Tipo_Novedad
 FROM Novedad N
 INNER JOIN Funcionario F ON F.Identificacion_Funcionario = N.Identificacion_Funcionario
 INNER JOIN Tipo_Novedad TN ON TN.Id_Tipo_Novedad = N.Id_Tipo_Novedad
 WHERE DATE(N.Fecha_Inicio) BETWEEN "'.$fini.'" AND "'.$ffin.'"';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$novedades = $oCon->getData();
unset($oCon);
$i=0;


$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Novedades Quincena');
$objSheet->getCell('A1')->setValue('Quincena del');
$objSheet->getCell('B1')->setValue($fini);
$objSheet->getCell('C1')->setValue('al');
$objSheet->getCell('D1')->setValue($ffin);
// $objSheet->getCell('A2')->setValue('Pagos por Bancos');
$objSheet->getStyle('A2')->getFont()->setBold(true)->setSize(13);
$objSheet->getCell('A3')->setValue("Item");
$objSheet->getCell('B3')->setValue("Funcionario");
$objSheet->getCell('C3')->setValue("Tipo Novedad");
$objSheet->getCell('D3')->setValue("Novedad");
$objSheet->getCell('E3')->setValue("Fecha Inicio");
$objSheet->getCell('F3')->setValue("Fecha Fin");
$objSheet->getCell('G3')->setValue("Tiempo Novedad");
$objSheet->getStyle('A3:G3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A3:G3')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A3:G3')->getFont()->setBold(true);
$objSheet->getStyle('A3:G3')->getFont()->getColor()->setARGB('FFFFFFFF');

$j=3;
$totales = [];
$i = 0;
foreach($novedades as $value){ 
        $j++;
        $date1 = new DateTime($value["Fecha_Inicio"]);
        $date2 = new DateTime($value["Fecha_Fin"]);
        $diff = $date1->diff($date2);
        $objSheet->getCell('A'.$j)->setValue(($i+1));
        $objSheet->getCell('B'.$j)->setValue($value["Funcionario"]);
        $objSheet->getCell('C'.$j)->setValue($value["Tipo_Novedad"]);
        $objSheet->getCell('D'.$j)->setValue($value["Novedad"]);
        $objSheet->getCell('E'.$j)->setValue(date("d/m/Y",strtotime($value["Fecha_Inicio"])));
        $objSheet->getCell('F'.$j)->setValue(date("d/m/Y",strtotime($value['Fecha_Fin'])));
        $objSheet->getCell('G'.$j)->setValue(($diff->days+1)." dias");
        $i++;
}

$j++;

$objSheet->getColumnDimension('A')->setAutoSize(true);
$objSheet->getColumnDimension('B')->setAutoSize(true);
$objSheet->getColumnDimension('C')->setAutoSize(true);
$objSheet->getColumnDimension('D')->setAutoSize(true);
$objSheet->getColumnDimension('E')->setAutoSize(true);
$objSheet->getColumnDimension('F')->setAutoSize(true);
$objSheet->getColumnDimension('G')->setAutoSize(true);
$objSheet->getStyle('A1:G'.$j)->getAlignment()->setWrapText(true);
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');


?>

