<?php

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$fini  = (isset($_REQUEST['fini'] ) ? $_REQUEST['fini'] : '');
$ffin  = (isset($_REQUEST['ffin'] ) ? $_REQUEST['ffin'] : '');


require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte Horarios ('.$fini.' - '.$ffin.').xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;

$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Reporte de Horarios');

function diff($start, $end) {
	$start_ts = strtotime($start);
	$end_ts = strtotime($end);
	$diff = $end_ts - $start_ts;
	return $diff / 3600;
}

function fecha($str)
{
	$parts = explode(" ",$str);
	$date = explode("-",$parts[0]);
	return $date[2] . "/". $date[1] ."/". $date[0];
}

$meses = array(
    1 => "Enero",
    2 => "Febrero",
    3 => "Marzo",
    4 => "Abril",
    5 => "Mayo",
    6 => "Junio",
    7 => "Julio",
    8 => "Agosto",
    9 => "Septiembre",
    10 => "Octubre",
    11 => "Noviembre",
    12 => "Diciembre",
);
$dias = array(
	0=> "Domingo",
	1=> "Lunes",
	2=> "Martes",
	3=> "Miercoles",
	4=> "Jueves",
	5=> "Viernes",
	6=> "Sabado"
);

$oLista= new lista('Llegada_Tarde');
$oLista->setRestrict("Fecha","<=",$ffin);
$oLista->setRestrict("Fecha",">=",$fini);
$oLista->setRestrict("Cuenta","=","Si");
$oLista->setOrder("Identificacion_Funcionario","ASC");
$llegadas_tarde_actual=$oLista->getList();
unset($oLista);

$oItem= new complex("Configuracion","id",1);
$config = $oItem->getData();
unset($oItem);

$startTime = strtotime( $fini.' 00:00:00' );
$endTime = strtotime(date($ffin.' 23:59:59'));

$objSheet->getCell('A1')->setValue('Reporte de Llegadas Tarde | Fecha: '.$fini.' - '.$ffin);
$objSheet->mergeCells('A1:C1');
$objSheet->getStyle('A1:C1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getCell('A2')->setValue('Nombre Empleado');
$objSheet->getCell('B2')->setValue('Cantidad Llegadas Tarde');
$objSheet->getCell('C2')->setValue('Tiempo Llegedas Tarde');

$objSheet->getStyle('A2:C2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A2:C2')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A2:C2')->getFont()->setBold(true);
$objSheet->getStyle('A2:C2')->getFont()->getColor()->setARGB('FFFFFFFF');


$filas=3;
$persona=0; $cant=0; $tiempo=0; $txt=''; 

foreach($llegadas_tarde_actual as $act){ 
    if($persona==$act["Identificacion_Funcionario"]){
    	$cant++;
    	$tiempo+=$act["Tiempo"];
    }else{
    	if($persona!="0"){ 
        	
        	$oItem = new complex("Funcionario","Identificacion_Funcionario",$persona);
        	$func=$oItem->getData();
        	unset($oItem);
        	
        	$oLista = new lista("Memorando");
        	$oLista->setRestrict("Identificacion_Funcionario","=",$persona);
        	$oLista->setRestrict("Mes","=",$mes);
        	$memos=$oLista->getList();
        	unset($oLista);
        	
        	$horas = floor($tiempo / 3600);
            $minutos = floor(($tiempo - ($horas * 3600)) / 60);
            $segundos = $tiempo - ($horas * 3600) - ($minutos * 60);
    
        	if($cant>=$config["Llegadas_Tarde"]){ 
        	    $objSheet->getCell('A'.$filas)->setValue($func["Nombres"]." ".$func["Apellidos"]);
        	    $objSheet->getCell('B'.$filas)->setValue($cant);
        	    $objSheet->getCell('C'.$filas)->setValue($horas.":".$minutos.":".$segundos);
        	    
        	    $filas++;
            }    
	    }
    	$txt='';
    	$cant=1;
    	$tiempo=$act["Tiempo"];
    	$persona=$act["Identificacion_Funcionario"];
    }    
	
} 
					        
if($persona!="0"){ 
	$prom=$tiempo/$cant;
	$oItem = new complex("Funcionario","Identificacion_Funcionario",$persona);
	$func=$oItem->getData();
	unset($oItem);
	if($cant>=$config["Llegadas_Tarde"]){ 
        $objSheet->getCell('A'.$filas)->setValue($func["Nombres"]." ".$func["Apellidos"]);
        $objSheet->getCell('B'.$filas)->setValue($cant);
        $objSheet->getCell('C'.$filas)->setValue(floor($prom/60).":".$prom%60);
        $filas++;
	}
}



$objSheet->getColumnDimension('A')->setWidth(25);
$objSheet->getColumnDimension('B')->setWidth(40);
$objSheet->getColumnDimension('C')->setWidth(40);/*
$objSheet->getColumnDimension('D')->setWidth(15);
$objSheet->getColumnDimension('E')->setWidth(15);
$objSheet->getColumnDimension('F')->setWidth(10);*/

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

?>