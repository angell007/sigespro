<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
require_once('../../../class/html2pdf.class.php');

$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

/* FUNCIONES BASICAS */
function fecha($str)
{
	$parts = explode(" ",$str);
	$date = explode("-",$parts[0]);
	return $date[2] . "/". $date[1] ."/". $date[0];
}
/* FIN FUNCIONES BASICAS*/

/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);
/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_Plan_Cuenta").xls"');
header('Cache-Control: max-age=0');

$query = '
	SELECT 
		*
    FROM Plan_Cuentas ORDER BY Codigo';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$planes_cuentas = $oCon->getData();
unset($oCon); 

$objPHPExcel = new PHPExcel;

$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Plan de Cuentas');

$objSheet->getCell('A1')->setValue("Tipo Plan");
$objSheet->getCell('B1')->setValue("Nombre Plan");
$objSheet->getCell('C1')->setValue("Codigo");
$objSheet->getCell('D1')->setValue("Tipo Niif");
$objSheet->getCell('E1')->setValue("Nombre Niif");
$objSheet->getCell('F1')->setValue("Codigo Niif");
$objSheet->getCell('G1')->setValue("Estado");
$objSheet->getCell('H1')->setValue("Ajuste Contable");
$objSheet->getCell('I1')->setValue("¿Cierra Terceros?");
$objSheet->getCell('J1')->setValue("Movimiento");
$objSheet->getCell('K1')->setValue("Documento");
$objSheet->getCell('L1')->setValue("Base");
$objSheet->getCell('M1')->setValue("Valor");
$objSheet->getCell('N1')->setValue("Porcentaje");
$objSheet->getCell('O1')->setValue("Centro de Costo");
$objSheet->getCell('P1')->setValue("Depreciacion");
$objSheet->getCell('Q1')->setValue("Amortizacion");
$objSheet->getCell('R1')->setValue("Exogeno");
$objSheet->getCell('S1')->setValue("Naturaleza");
$objSheet->getCell('T1')->setValue("¿Maneja Nit?");

$objSheet->getStyle('A1:T1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:T1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:T1')->getFont()->setBold(true);
$objSheet->getStyle('A1:T1')->getFont()->getColor()->setARGB('FFFFFFFF');

$j=1;
foreach($planes_cuentas as $plan){ $j++;
	$objSheet->getCell('A'.$j)->setValue($plan["Tipo_P"]);
	$objSheet->getCell('B'.$j)->setValue($plan["Nombre"]);
	$objSheet->getCell('C'.$j)->setValue($plan["Codigo"]);
	$objSheet->getCell('D'.$j)->setValue($plan["Tipo_Niif"]);
	$objSheet->getCell('E'.$j)->setValue($plan["Nombre_Niif"]);
	$objSheet->getCell('F'.$j)->setValue($plan["Codigo_Niif"]);
	$objSheet->getCell('G'.$j)->setValue($plan["Estado"]);
	$objSheet->getCell('H'.$j)->setValue(TransformarValor($plan["Ajuste_Contable"]));
	$objSheet->getCell('I'.$j)->setValue(TransformarValor($plan["Cierra_Terceros"]));
	$objSheet->getCell('J'.$j)->setValue(TransformarValor($plan["Movimiento"]));
	$objSheet->getCell('K'.$j)->setValue(TransformarValor($plan["Documento"]));
	$objSheet->getCell('L'.$j)->setValue(TransformarValor($plan["Valor"]));
	$objSheet->getCell('M'.$j)->setValue($plan["Base"]);
	$objSheet->getCell('N'.$j)->setValue($plan["Porcentaje"]);
	$objSheet->getCell('O'.$j)->setValue(TransformarValor($plan["Centro de Costo"]));
	$objSheet->getCell('P'.$j)->setValue(TransformarValor($plan["Depreciacion"]));
	$objSheet->getCell('Q'.$j)->setValue(TransformarValor($plan["Amortizacion"]));
	$objSheet->getCell('R'.$j)->setValue(TransformarValor($plan["Exogeno"]));
	$objSheet->getCell('S'.$j)->setValue($plan["Naturaleza"]);
	$objSheet->getCell('T'.$j)->setValue(TransformarValor($plan["¿Maneja Nit?"]));
	
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
$objSheet->getColumnDimension('K')->setAutoSize(true);
$objSheet->getColumnDimension('J')->setAutoSize(true);
$objSheet->getColumnDimension('L')->setAutoSize(true);
$objSheet->getColumnDimension('M')->setAutoSize(true);
$objSheet->getColumnDimension('N')->setAutoSize(true);
$objSheet->getColumnDimension('O')->setAutoSize(true);
$objSheet->getColumnDimension('P')->setAutoSize(true);
$objSheet->getColumnDimension('Q')->setAutoSize(true);
$objSheet->getColumnDimension('R')->setAutoSize(true);
$objSheet->getColumnDimension('S')->setAutoSize(true);
$objSheet->getColumnDimension('T')->setAutoSize(true);
$objSheet->getStyle('A1:K'.$j)->getAlignment()->setWrapText(true);

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

function TransformarValor($value){
	if ($value == 'N' || $value == '' || is_null($value)) {
		return 'NO';
	}else{
		return 'SI';
	}
}

?>