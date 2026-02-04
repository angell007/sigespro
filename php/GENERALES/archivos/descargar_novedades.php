<?php

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$fini  = (isset($_REQUEST['inicio'] ) ? $_REQUEST['inicio'] : '');
$ffin  = (isset($_REQUEST['fin'] ) ? $_REQUEST['fin'] : '');

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


require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Novedades ('.$fini.' - '.$ffin.').xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;

$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Vacaciones');

$oLista= new lista('Novedad');
$oLista->setRestrict("Inicio",">=",$fini);
$oLista->setRestrict("Tipo","=","Vacaciones");
$vacaciones=$oLista->getList();
unset($oLista);

$oLista= new lista('Novedad');
$oLista->setRestrict("Inicio",">=",$fini);
$oLista->setRestrict("Tipo","=","Incapacidad");
$incapacidades=$oLista->getList();
unset($oLista);

$oLista= new lista('Novedad');
$oLista->setRestrict("Inicio",">=",$fini);
$oLista->setRestrict("Tipo","=","Ausentismo");
$ausentismos=$oLista->getList();
unset($oLista);

$objSheet->getCell('A1')->setValue('CODEMP');
$objSheet->getCell('B1')->setValue('CONCEPTO');
$objSheet->getCell('C1')->setValue('DIAST');
$objSheet->getCell('D1')->setValue('DISFRUDIAS');
$objSheet->getCell('E1')->setValue('FECHAR');
$objSheet->getCell('F1')->setValue('FECHAV');
$objSheet->getCell('G1')->setValue('NOMABIERTA');
$objSheet->getCell('H1')->setValue('NOTA');
$objSheet->getCell('I1')->setValue('PESOSVAC');
$objSheet->getCell('J1')->setValue('PRIMA');
$objSheet->getCell('K1')->setValue('STADSINCRO');
$objSheet->getCell('L1')->setValue('VALOR');
$objSheet->getCell('M1')->setValue('VPROMEDIO');
$objSheet->getCell('N1')->setValue('CONTROLCAMBIO');
$objSheet->getStyle('A1:N1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:N1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('b0b0b0B0');
$objSheet->getStyle('A1:N1')->getFont()->setBold(true);

$filas=1;
foreach($vacaciones as $vac){ $filas++;
	$oItem = new complex("Funcionario","Identificacion_Funcionario",$vac["Identificacion_Funcionario"]);
	$func=$oItem->getData();
	
	$objSheet->getCell('A'.$filas)->setValue($func["Codigo"]);
	$objSheet->getCell('B'.$filas)->setValue($vac["Concepto"]);
	$objSheet->getCell('C'.$filas)->setValue(number_format(diff($vac["Inicio"],$vac["Fin"])/24,1,",","."));
	$objSheet->getCell('D'.$filas)->setValue('1');
	$objSheet->getCell('E'.$filas)->setValue(fecha($vac["Fin"]));
	$objSheet->getCell('F'.$filas)->setValue(fecha($vac["Inicio"]));
	$objSheet->getCell('G'.$filas)->setValue('0');
	$objSheet->getCell('H'.$filas)->setValue($vac["Observaciones"]);
	$objSheet->getCell('I'.$filas)->setValue('0');
	$objSheet->getCell('J'.$filas)->setValue('0');
	$objSheet->getCell('K'.$filas)->setValue('0');
	$objSheet->getCell('L'.$filas)->setValue('0');
	$objSheet->getCell('M'.$filas)->setValue('0');
	$objSheet->getCell('N'.$filas)->setValue('');
}

$objSheet->getColumnDimension('A')->setWidth(15);
$objSheet->getColumnDimension('B')->setWidth(15);
$objSheet->getColumnDimension('C')->setWidth(15);
$objSheet->getColumnDimension('D')->setWidth(15);
$objSheet->getColumnDimension('E')->setWidth(15);
$objSheet->getColumnDimension('F')->setWidth(15);
$objSheet->getColumnDimension('G')->setWidth(15);
$objSheet->getColumnDimension('H')->setWidth(15);
$objSheet->getColumnDimension('I')->setWidth(15);
$objSheet->getColumnDimension('J')->setWidth(15);
$objSheet->getColumnDimension('K')->setWidth(15);
$objSheet->getColumnDimension('L')->setWidth(15);
$objSheet->getColumnDimension('M')->setWidth(15);
$objSheet->getColumnDimension('N')->setWidth(15);




$objWorkSheet = $objPHPExcel->createSheet();
$objPHPExcel->setActiveSheetIndex(1);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Incapacidades');

$objSheet->getCell('A1')->setValue('AUTORIZINI');
$objSheet->getCell('B1')->setValue('CODIGO');
$objSheet->getCell('C1')->setValue('CONCEPTO');
$objSheet->getCell('D1')->setValue('DETALLE');
$objSheet->getCell('E1')->setValue('DIASOGP');
$objSheet->getCell('F1')->setValue('FECFIN');
$objSheet->getCell('G1')->setValue('FECHACOBRO');
$objSheet->getCell('H1')->setValue('FECINI');
$objSheet->getCell('I1')->setValue('FECPROGRA');
$objSheet->getCell('J1')->setValue('NAUTORIZA');
$objSheet->getCell('K1')->setValue('NOMABIERTA');
$objSheet->getCell('L1')->setValue('NOMEMP');
$objSheet->getCell('M1')->setValue('VLRPROM');
$objSheet->getCell('N1')->setValue('CODCARGO');
$objSheet->getCell('O1')->setValue('TIPNOVEDAD');
$objSheet->getCell('P1')->setValue('CONTROLCAMBIOS');
$objSheet->getStyle('A1:P1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:P1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('b0b0b0B0');
$objSheet->getStyle('A1:P1')->getFont()->setBold(true);

$filas=1;
foreach($incapacidades as $inc){ $filas++;
	$oItem = new complex("Funcionario","Identificacion_Funcionario",$inc["Identificacion_Funcionario"]);
	$func=$oItem->getData();
	
	$objSheet->getCell('A'.$filas)->setValue('');
	$objSheet->getCell('B'.$filas)->setValue($func["Codigo"]);
	$objSheet->getCell('C'.$filas)->setValue($inc["Concepto"]);
	$objSheet->getCell('D'.$filas)->setValue($inc["Observaciones"]);
	$objSheet->getCell('E'.$filas)->setValue(number_format(diff($inc["Inicio"],$inc["Fin"])/24,1,",","."));
	$objSheet->getCell('F'.$filas)->setValue(fecha($inc["Fin"]));
	$objSheet->getCell('G'.$filas)->setValue('');
	$objSheet->getCell('H'.$filas)->setValue(fecha($inc["Inicio"]));
	$objSheet->getCell('I'.$filas)->setValue('');
	$objSheet->getCell('J'.$filas)->setValue('0');
	$objSheet->getCell('K'.$filas)->setValue('0');
	$objSheet->getCell('L'.$filas)->setValue('0');
	$objSheet->getCell('M'.$filas)->setValue('0');
	$objSheet->getCell('N'.$filas)->setValue('0');
	$objSheet->getCell('O'.$filas)->setValue('0');
	$objSheet->getCell('P'.$filas)->setValue('0');
}


$objSheet->getColumnDimension('A')->setWidth(15);
$objSheet->getColumnDimension('B')->setWidth(15);
$objSheet->getColumnDimension('C')->setWidth(15);
$objSheet->getColumnDimension('D')->setWidth(15);
$objSheet->getColumnDimension('E')->setWidth(15);
$objSheet->getColumnDimension('F')->setWidth(15);
$objSheet->getColumnDimension('G')->setWidth(15);
$objSheet->getColumnDimension('H')->setWidth(15);
$objSheet->getColumnDimension('I')->setWidth(15);
$objSheet->getColumnDimension('J')->setWidth(15);
$objSheet->getColumnDimension('K')->setWidth(15);
$objSheet->getColumnDimension('L')->setWidth(15);
$objSheet->getColumnDimension('M')->setWidth(15);
$objSheet->getColumnDimension('N')->setWidth(15);
$objSheet->getColumnDimension('O')->setWidth(15);
$objSheet->getColumnDimension('P')->setWidth(15);

$objWorkSheet = $objPHPExcel->createSheet();
$objPHPExcel->setActiveSheetIndex(2);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Ausentismo');

$objSheet->getCell('A1')->setValue('CODAUS');
$objSheet->getCell('B1')->setValue('CODCC');
$objSheet->getCell('C1')->setValue('CODIGO');
$objSheet->getCell('D1')->setValue('CONCEP');
$objSheet->getCell('E1')->setValue('FECFIN');
$objSheet->getCell('F1')->setValue('FECINI');
$objSheet->getCell('G1')->setValue('GRUPO');
$objSheet->getCell('H1')->setValue('LICNOREMU');
$objSheet->getCell('I1')->setValue('MINVALOR');
$objSheet->getCell('J1')->setValue('NDIASBASE');
$objSheet->getCell('K1')->setValue('NOMABIERTA');
$objSheet->getCell('L1')->setValue('NOTA');
$objSheet->getCell('M1')->setValue('NRODIAS');
$objSheet->getCell('N1')->setValue('NROHORAS');
$objSheet->getCell('O1')->setValue('PASANOMINA');
$objSheet->getCell('P1')->setValue('TEMPORADA');
$objSheet->getCell('Q1')->setValue('YARESTO');
$objSheet->getCell('R1')->setValue('CLASIFICA');
$objSheet->getCell('S1')->setValue('PRESTACIO');
$objSheet->getStyle('A1:S1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:S1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('b0b0b0B0');
$objSheet->getStyle('A1:S1')->getFont()->setBold(true);

$filas=1;
foreach($ausentismos as $aus){ $filas++;
	$oItem = new complex("Funcionario","Identificacion_Funcionario",$aus["Identificacion_Funcionario"]);
	$func=$oItem->getData();
	
	$oItem = new complex("Grupo","Id_Grupo",$func["Id_Grupo"]);
	$grupo=$oItem->getData();
	
	$objSheet->getCell('A'.$filas)->setValue('');
	$objSheet->getCell('B'.$filas)->setValue('');
	$objSheet->getCell('C'.$filas)->setValue($func["Codigo"]);
	$objSheet->getCell('D'.$filas)->setValue($aus["Concepto"]);
	$objSheet->getCell('E'.$filas)->setValue(fecha($aus["Fin"]));
	$objSheet->getCell('F'.$filas)->setValue(fecha($aus["Inicio"]));
	$objSheet->getCell('G'.$filas)->setValue($grupo["Codigo"]);
	$objSheet->getCell('H'.$filas)->setValue("");
	$objSheet->getCell('I'.$filas)->setValue('0');
	$objSheet->getCell('J'.$filas)->setValue('0');
	$objSheet->getCell('K'.$filas)->setValue('0');
	$objSheet->getCell('L'.$filas)->setValue($aus["Observaciones"]);
	$objSheet->getCell('M'.$filas)->setValue(number_format(diff($aus["Inicio"],$aus["Fin"])/24,1,",","."));
	$objSheet->getCell('N'.$filas)->setValue(number_format(8*(diff($aus["Inicio"],$aus["Fin"])/24),1,",","."));
	$objSheet->getCell('O'.$filas)->setValue('0');
	$objSheet->getCell('P'.$filas)->setValue('0');
	$objSheet->getCell('Q'.$filas)->setValue('0');
	$objSheet->getCell('R'.$filas)->setValue('0');
	$objSheet->getCell('S'.$filas)->setValue('0');
}


$objSheet->getColumnDimension('A')->setWidth(15);
$objSheet->getColumnDimension('B')->setWidth(15);
$objSheet->getColumnDimension('C')->setWidth(15);
$objSheet->getColumnDimension('D')->setWidth(15);
$objSheet->getColumnDimension('E')->setWidth(15);
$objSheet->getColumnDimension('F')->setWidth(15);
$objSheet->getColumnDimension('G')->setWidth(15);
$objSheet->getColumnDimension('H')->setWidth(15);
$objSheet->getColumnDimension('I')->setWidth(15);
$objSheet->getColumnDimension('J')->setWidth(15);
$objSheet->getColumnDimension('K')->setWidth(15);
$objSheet->getColumnDimension('L')->setWidth(15);
$objSheet->getColumnDimension('M')->setWidth(15);
$objSheet->getColumnDimension('N')->setWidth(15);
$objSheet->getColumnDimension('O')->setWidth(15);
$objSheet->getColumnDimension('P')->setWidth(15);
$objSheet->getColumnDimension('Q')->setWidth(15);
$objSheet->getColumnDimension('R')->setWidth(15);
$objSheet->getColumnDimension('S')->setWidth(15);




$objPHPExcel->setActiveSheetIndex(0);
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

?>