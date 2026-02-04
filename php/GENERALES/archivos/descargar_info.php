<?php

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$fini  = (isset($_REQUEST['inicio'] ) ? $_REQUEST['inicio'] : '');
$ffin  = (isset($_REQUEST['fin'] ) ? $_REQUEST['fin'] : '');

$fecha=explode("-",$ffin);
function getUltimoDiaMes($elAnio,$elMes) {
  return date("d",(mktime(0,0,0,$elMes+1,1,$elAnio)-1));
}
$ud = getUltimoDiaMes($fecha[0],$fecha[1]);

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

$oLista= new lista('Funcionario');
$oLista->setOrder("Id_Grupo","ASC");
$funcionarios=$oLista->getList();
unset($oLista);

$oLista= new lista('Maestro');
$oLista->setRestrict("Codigo","=","HORAS EXTRAS");
$oLista->setRestrict("Concepto","!=","1044");
$oLista->setOrder("Concepto","ASC");
$maestros=$oLista->getList();
unset($oLista);



header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Ofimatica ('.$fini.' - '.$ffin.').xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;

$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Ofimatica');

$objSheet->getCell('A1')->setValue('MVNOVPER | Fecha: '.$fini.' - '.$ffin);
$objSheet->mergeCells('A1:O1');
$objSheet->getStyle('A1:O1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getCell('A2')->setValue('CODCC');
$objSheet->getCell('B2')->setValue('CODIGO');
$objSheet->getCell('C2')->setValue('CONCEP');
$objSheet->getCell('D2')->setValue('FECHA');
$objSheet->getCell('E2')->setValue('FECING');
$objSheet->getCell('F2')->setValue('FECLIQUIDA');
$objSheet->getCell('G2')->setValue('FECMOD');
$objSheet->getCell('H2')->setValue('GRUPO');
$objSheet->getCell('I2')->setValue('INTEGRADO');
$objSheet->getCell('J2')->setValue('NOMABIERTA');
$objSheet->getCell('K2')->setValue('NHORAS');
$objSheet->getCell('L2')->setValue('PASSWORDIN');
$objSheet->getCell('M2')->setValue('PASSWORDMO');
$objSheet->getCell('N2')->setValue('STADSINCRO');
$objSheet->getCell('O2')->setValue('VALOR');
$objSheet->getStyle('A2:O2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A2:O2')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('b0b0b0B0');
$objSheet->getStyle('A2:O2')->getFont()->setBold(true);

$fila=2;

foreach($funcionarios as $func){ 
	
	$oItem = new complex("Grupo","Id_Grupo",$func["Id_Grupo"]);
	$grupo = $oItem->getData();
		
	$oLista= new lista('Reporte');
	$oLista->setRestrict("Fecha","<=",$ffin);
	$oLista->setRestrict("Fecha",">=",$fini);
	$oLista->setRestrict("Identificacion_Funcionario","=",$func["Identificacion_Funcionario"]);
	$reportes=$oLista->getList();
	unset($oLista);
	
	$tot["1005"]=0;
	$tot["1006"]=0;
	$tot["1007"]=0;
	$tot["1008"]=0;
	$tot["1009"]=0;
	$tot["1010"]=0;
	$tot["1013"]=0;
	foreach($reportes as $rep){
		
		$tot["1005"]+=str_replace(",",".",$rep["HED"]);
		$tot["1006"]+=str_replace(",",".",$rep["HEN"]);
		$tot["1007"]+=str_replace(",",".",$rep["HEDD"]);
		$tot["1008"]+=str_replace(",",".",$rep["HEDN"]);
		$tot["1009"]+=str_replace(",",".",$rep["RN"]);
		$tot["1010"]+=str_replace(",",".",$rep["RDD"]);
		$tot["1013"]+=str_replace(",",".",$rep["RDN"]);
		 
	}
	
	foreach($maestros as $ma){
		
		if($tot[$ma["Concepto"]]>0){ $fila++;
			
			$objSheet->setCellValueExplicit('A'.$fila, $grupo["Numero"],PHPExcel_Cell_DataType::TYPE_STRING);
			$objSheet->setCellValueExplicit('B'.$fila, $func["Codigo"],PHPExcel_Cell_DataType::TYPE_STRING);
			
			$objSheet->getCell('C'.$fila)->setValue($ma["Concepto"]);
			$objSheet->getCell('D'.$fila)->setValue($ud."/".$fecha[1]."/".$fecha[0]);
			$objSheet->getCell('E'.$fila)->setValue($ud."/".$fecha[1]."/".$fecha[0]);
			$objSheet->getCell('F'.$fila)->setValue($ud."/".$fecha[1]."/".$fecha[0]);
			$objSheet->getCell('G'.$fila)->setValue($ud."/".$fecha[1]."/".$fecha[0]);
			$objSheet->getCell('H'.$fila)->setValue($grupo["Codigo"]);
			$objSheet->getCell('I'.$fila)->setValue('0');
			$objSheet->getCell('J'.$fila)->setValue('1');
			//$objSheet->setCellValueExplicit('K'.$fila, $tot[$ma["Concepto"]],PHPExcel_Cell_DataType::TYPE_STRING);
			$objSheet->getCell('K'.$fila)->setValue($tot[$ma["Concepto"]]);
			$objSheet->getCell('L'.$fila)->setValue("EREY");
			$objSheet->getCell('M'.$fila)->setValue("EREY");
			$objSheet->getCell('N'.$fila)->setValue("0");
			
			$objSheet->getCell('O'.$fila)->setValue("0");
		}
	}
	
	
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
$objSheet->getColumnDimension('O')->setWidth(5);
$objSheet->getColumnDimension('P')->setWidth(5);
$objSheet->getColumnDimension('Q')->setWidth(15);

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

?>