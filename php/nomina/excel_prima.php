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

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Resumen_Prima.xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel; 

$query="SELECT ROUND(Salario_Base*2) as Maximo_Salario, Subsidio_Transporte FROm Configuracion WHERE Id_Configuracion=1";
$oCon= new consulta();
$oCon->setQuery($query);
$conf = $oCon->getData();
unset($oCon);
 
$hoy = date("Y-m-d");
$query = 'SELECT CF.*, F.Identificacion_Funcionario, F.Imagen, CF.Valor as Salario,CONCAT(F.Nombres," ", Apellidos) as Funcionario
FROM Contrato_Funcionario CF 
INNER JOIN Funcionario F ON CF.Identificacion_Funcionario=F.Identificacion_Funcionario        
WHERE  CF.Estado="Activo" AND CF.Id_Tipo_Contrato < 5 
AND (CF.Fecha_Fin_Contrato>="'.$hoy.'" OR CF.Fecha_Fin_Contrato IS NULL OR CF.Fecha_Fin_Contrato="0000-00-00")
GROUP BY CF.Identificacion_Funcionario
ORDER BY F.Nombres ASC
';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$funcionarios = $oCon->getData();
unset($oCon);

$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Resumen_Prima');
$objSheet->getCell('A1')->setValue('Prima de '.$meses[date("n")]." del ".date("Y"));

$objSheet->getCell('A3')->setValue("Funcionario");
$objSheet->getCell('B3')->setValue("Salario");
$objSheet->getCell('C3')->setValue("Formula");
$objSheet->getCell('D3')->setValue("Calculos");
$objSheet->getCell('E3')->setValue("Valor Prima");
$objSheet->getStyle('A3:E3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A3:E3')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A3:E3')->getFont()->setBold(true);
$objSheet->getStyle('A3:E3')->getFont()->getColor()->setARGB('FFFFFFFF');
 
$j=3;
$i=-1;
$total_prima=0;
foreach ($funcionarios as $func) { $j++; $i++;
    $fechahoy=$fechahoy=date("Y-m-30");
    $mes=date("m",strtotime($func['Fecha_Inicio_Contrato']));
    $dias_des=0;
    if($mes=="01"){
        $dias_des=3;
    }elseif($mes=="02" || $mes=="03"){
        $dias_des=2;
    }elseif($mes=="04"|| $mes=="05"){
        $dias_des=1;
    }

    if($mes=="07"){
        $dias_des=3;
    }elseif($mes=="08"){
        $dias_des=2;
    }elseif($mes=="09"|| $mes=="10"){
        $dias_des=1;
    }

    $fechainicio= new DateTime($func['Fecha_Inicio_Contrato']);
    $fechahoy= new DateTime($fechahoy);
    $dias_trabajados = $fechainicio->diff($fechahoy);
    $dias_trabajados= $dias_trabajados->format('%R%a');
    $dias_trabajados=trim($dias_trabajados,'+');   
    $dias_trabajados=$dias_trabajados+1-$dias_des;

    if($dias_trabajados>180){
        $dias_trabajados=180;
    }

    
    if($func['Salario']<=$conf['Maximo_Salario']){
        $valor_prima=round((($dias_trabajados*($func['Salario']+$conf['Subsidio_Transporte']))/360),0);
        $funcionarios[$i]['Salario']=$funcionarios[$i]['Salario']+($conf['Subsidio_Transporte']);
    }else{
        $valor_prima=round((($dias_trabajados*$func['Salario'])/360),0);
    }
    
    $funcionarios[$i]['Dias_Trabajados']=$dias_trabajados;
    $funcionarios[$i]['Valor_Prima']=$valor_prima;
    $total_prima+= $valor_prima;

    $objSheet->getCell('A'.$j)->setValue($func["Funcionario"]);
    $objSheet->getCell('B'.$j)->setValue($func["Salario"]);
    $objSheet->getCell('C'.$j)->setValue("(Dias_trabajados * Salario) / 360");
    $objSheet->getCell('D'.$j)->setValue("(".$dias_trabajados." * $ ".number_format($funcionarios[$i]['Salario'],0,",",".").") / 360");
    $objSheet->getCell('E'.$j)->setValue($valor_prima);
    $objSheet->getStyle('E'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
    $objSheet->getStyle('B'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
}


$j+=2;
$objSheet->getCell('D'.$j)->setValue("TOTAL");
$objSheet->getStyle('D'.$j)->getFont()->setBold(true);
$objSheet->getCell('E'.$j)->setValue($total_prima);
$objSheet->getStyle('E'.$j)->getFont()->setBold(true);
$objSheet->getStyle('E'.$j)->getNumberFormat()->setFormatCode("#,##0.00");


$objSheet->getColumnDimension('A')->setAutoSize(true);
$objSheet->getColumnDimension('B')->setAutoSize(true);
$objSheet->getColumnDimension('C')->setAutoSize(true);
$objSheet->getColumnDimension('D')->setAutoSize(true);
$objSheet->getColumnDimension('E')->setAutoSize(true);
$objSheet->getStyle('A1:E'.$j)->getAlignment()->setWrapText(true);
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

