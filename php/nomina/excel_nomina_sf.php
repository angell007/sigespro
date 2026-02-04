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

$mes_actual = date('m');
$anio_actual = date('Y');
$dia_actual = date('d');

$total_efectivo=0;
$total_banco=[];

if(date("Y-m-d")<=date("Y-m-15")){
    $fini  = (isset($_REQUEST['fini'] ) ? $_REQUEST['fini'] : date("Y-m")."-01" );
    $ffin  = (isset($_REQUEST['ffin'] ) ? $_REQUEST['ffin'] : date("Y-m-15") );
    $quincena=1;
 }else{
    $fini  = (isset($_REQUEST['fini'] ) ? $_REQUEST['fini'] : date("Y-m")."-16" );
    $ffin  = (isset($_REQUEST['ffin'] ) ? $_REQUEST['ffin'] : date("Y-m")."-". date("d",(mktime(0,0,0,date("m")+1,1,date("Y"))-1))); 
    $quincena=2;
 }

 if(isset($_REQUEST['nomina'])){
     $concepto=$_REQUEST['nomina'];
     $tem=explode(';',$concepto);
     $anio=explode('-',$tem[0]);

     $anio_actual=$anio[0];
     $mes_actual=$anio[1];
     if($tem[1]=='1'){
        $dia_actual=10;
        $quincena=1;
     }else{
        $dia_actual=17;
        $quincena=2;
     }
 }else{
     $concepto=date('Y-m').';'.$quincena;
 }



require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Resumen_Quincena.xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel; 
$fechas=CalcularFechaQuincena($dia_actual, $mes_actual, $anio_actual); 

 $query = 'SELECT  CF.*, TC.Nombre as Tipo_Contrato,  IFNULL((Select SUM(Valor) 
 FROM Movimiento_Funcionario ME 
 WHERE ME.Identificacion_Funcionario=F.Identificacion_Funcionario AND ME.Tipo="Egreso" AND ME.Quincena="'.date("2019-05;").$quincena.'"),0) as Egresos,
 CONCAT(F.Nombres, " ", F.Apellidos) as Funcionario,  
 C.Nombre as Cargo ,(SELECT Nombre FROM Municipio WHERE Id_Municipio=CF.Id_Municipio) as Ciudad,
 IF(F.Id_Banco IS NOT NULL,"Si","No") AS Tiene_Banco,
 IF(F.Id_Banco IS NOT NULL,(SELECT Nombre FROM Banco WHERE Id_Banco = F.Id_Banco),"Pago en Efectivo") AS Banco,
 IFNULL(F.Cuenta,"Pago en Efectivo") AS Cuenta,
 IF(F.Id_Banco IS NOT NULL OR F.Id_Banco != "", 1, 2) AS Orden
 FROM Contrato_Funcionario CF 
 INNER JOIN Funcionario F ON CF.Identificacion_Funcionario=F.Identificacion_Funcionario
 LEFT JOIN Cargo C 
 on F.Id_Cargo=C.Id_Cargo 
 LEFT JOIN Tipo_Contrato TC On TC.Id_Tipo_Contrato = CF.Id_Tipo_Contrato
 WHERE CF.Estado="Activo" AND F.Preliquidado = "NO" AND F.Liquidado = "NO" AND CF.Fecha_Fin_Contrato>="'.$fini.'" AND NOT EXISTS (SELECT * FROM Nomina_Funcionario WHERE Periodo_Pago = "'.$concepto.'" AND Identificacion_Funcionario = F.Identificacion_Funcionario)
 GROUP BY F.Identificacion_Funcionario 
 ORDER BY Orden, Funcionario ';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$funcionarios = $oCon->getData();
unset($oCon);
$i=0;
foreach ($funcionarios as $value) {  
    if($value['Fecha_Inicio_Contrato']>$fini){
        $fini=$value['Fecha_Inicio_Contrato'];
    }  
    if($value['Fecha_Fin_Contrato']>$fini && $value['Fecha_Fin_Contrato']<$ffin ){
        $ffin=$value['Fecha_Fin_Contrato'];
    }
    $funcionario=new CalculoNomina($value['Identificacion_Funcionario'],$quincena,$fini,$ffin,'Nomina');
    $funcionario=$funcionario->CalculosNomina();
    $funcionarios[$i]['Total_Licencias']=$funcionario['Total_Licencias'];
    $funcionarios[$i]['Dias_Licencia']=$funcionario['Dias_Licencia'];
    $funcionarios[$i]['Total_Incapacidades']=$funcionario['Total_Incapacidades'];
    $funcionarios[$i]['Dias_Incapacidad']=$funcionario['Dias_Incapacidad'];
    $funcionarios[$i]['Dias_Vacaciones']=$funcionario['Dias_Vacaciones'];
    $funcionarios[$i]['Ingresos_NS']=$funcionario['Ingresos_NS'];
    $funcionarios[$i]['Total_Quincena']=$funcionario['Total_Quincena'];
    $funcionarios[$i]['Dias_Laborados']=$funcionario['Dias_Laborados'];
    $funcionarios[$i]['Total_Vacaciones']=$funcionario['Total_Vacaciones'];
    $funcionarios[$i]['Total_Responsabilidades']=$funcionario['Total_Responsabilidades'];
    $funcionarios[$i]['Auxilio']=$funcionario['Auxilio'];
    $funcionarios[$i]['Total_Salud']=$funcionario['Total_Salud'];
    $funcionarios[$i]['Total_Pension']=$funcionario['Total_Pension'];
    $funcionarios[$i]['Total_Subsistencia']=$funcionario['Total_Subsistencia'];
    $funcionarios[$i]['Total_Solidaridad']=$funcionario['Total_Solidaridad'];
    $funcionarios[$i]['Total_Libranzas']=$funcionario['Total_Libranzas'];
    $funcionarios[$i]['Salario_Quincena']=$funcionario['Salario_Quincena'];
    ObtenerFechas();
    $funcionarios[$i]['Pestamos']=ObtenerValores('Prestamo',$value['Identificacion_Funcionario']);
    $funcionarios[$i]['POLIZA_FUNERARIA']=ObtenerValores('POLIZA FUNERARIA',$value['Identificacion_Funcionario']);
    $funcionarios[$i]['Librazas']=ObtenerValores('Librazas',$value['Identificacion_Funcionario']);
    $funcionarios[$i]['Bonificacion_desempeno']=ObtenerValores('Bonificacion extralegal por desempeño',$value['Identificacion_Funcionario']);
    $funcionarios[$i]['Otras_deducciones']=ObtenerValores('Otras deducciones',$value['Identificacion_Funcionario']);
    $funcionarios[$i]['Rodamiento']=ObtenerValores('Auxilio de movilizacions',$value['Identificacion_Funcionario']);
    $funcionarios[$i]['RESPONSABILIDADES']=ObtenerValores('RESPONSABILIDADES',$value['Identificacion_Funcionario']);
    
    $i++;
    

}



$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Resumen_Nomina');
$objSheet->getCell('A1')->setValue('Quincena del');
$objSheet->getCell('B1')->setValue($fechas['inicio']);
$objSheet->getCell('C1')->setValue('al');
$objSheet->getCell('D1')->setValue($fechas['fin']);
$objSheet->getCell('A1')->setValue('Quincena del');
// $objSheet->getCell('A2')->setValue('Pagos por Bancos');
$objSheet->getStyle('A2')->getFont()->setBold(true)->setSize(13);
$objSheet->getCell('A3')->setValue("Item");
$objSheet->getCell('B3')->setValue("Nombre Empleado");
$objSheet->getCell('C3')->setValue("C.C.");
$objSheet->getCell('D3')->setValue("Ciudad");
$objSheet->getCell('E3')->setValue("Cargo");
$objSheet->getCell('F3')->setValue("Banco");
$objSheet->getCell('G3')->setValue("Cuenta");
$objSheet->getCell('H3')->setValue("Sueldo Basico");
$objSheet->getCell('I3')->setValue("Dias Trabajados");
$objSheet->getCell('J3')->setValue("Pago días trabajados");
$objSheet->getCell('K3')->setValue("Auxilio No Salarial ");
$objSheet->getCell('L3')->setValue("Auxilio Transporte");
$objSheet->getCell('M3')->setValue("Dias Vacaciones");
$objSheet->getCell('N3')->setValue("Total Vacaciones");
$objSheet->getCell('O3')->setValue("Dias Incapacidades");
$objSheet->getCell('P3')->setValue("Total_Incapacidades");
$objSheet->getCell('Q3')->setValue("Dias Licencias");
$objSheet->getCell('R3')->setValue("Total Licencias ");
// $objSheet->getCell('S3')->setValue("Bonificacion Indicador ");
// $objSheet->getCell('T3')->setValue("Auxilio Rodamiento ");
$objSheet->getCell('S3')->setValue("Salud");
$objSheet->getCell('T3')->setValue("Pension");
// $objSheet->getCell('W3')->setValue("Otras Deducciones ");
$objSheet->getCell('U3')->setValue("Prestamos");
$objSheet->getCell('V3')->setValue("Libranzas, Prestamos o Sanciones");
// $objSheet->getCell('Z3')->setValue("Responsabilidades");
// $objSheet->getCell('AA3')->setValue("Poliza Funeraria ");
// $objSheet->getCell('AB3')->setValue("Fondo de Solidaridad ");
// $objSheet->getCell('AC3')->setValue("Fondo de Subsistencia");
$objSheet->getCell('W3')->setValue("Neto a Cancelar");
$objSheet->getStyle('A3:W3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A3:W3')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A3:W3')->getFont()->setBold(true);
$objSheet->getStyle('A3:W3')->getFont()->getColor()->setARGB('FFFFFFFF');

$j=3;
$totales = [];
$i = 0;
foreach($funcionarios as $value){ 
        addTotal($value['Tiene_Banco'],$value['Banco'],($value["Total_Quincena"]-(($value['Auxilio_No_Prestacional']/30)*$value['Dias_Laborados'])),(($value['Auxilio_No_Prestacional']/30)*$value['Dias_Laborados']));
    // if ($value['Tiene_Banco'] == 'Si') {
        $j++;
        $objSheet->getCell('A'.$j)->setValue(($i+1));
        $objSheet->getCell('B'.$j)->setValue($value["Funcionario"]);
        $objSheet->getCell('C'.$j)->setValue($value["Identificacion_Funcionario"]);
        $objSheet->getCell('D'.$j)->setValue($value["Ciudad"]);
        $objSheet->getCell('E'.$j)->setValue($value["Cargo"]." / ".$value["Tipo_Contrato"]);
        $objSheet->getCell('F'.$j)->setValue($value['Banco']);
        $objSheet->getCell('G'.$j)->setValue($value['Cuenta']);
        $objSheet->getCell('H'.$j)->setValue($value["Valor"]);
        addValueTotales('H',$value["Valor"]);
        $objSheet->getCell('I'.$j)->setValue($value['Dias_Laborados'] );
        $objSheet->getCell('J'.$j)->setValue(number_format($value['Salario_Quincena'],0,"","") );	
        addValueTotales('J',$value["Salario_Quincena"]);
        $aux= $value["Ingresos_NS"];
        $objSheet->getCell('K'.$j)->setValue(number_format($aux,0,"",""));
        addValueTotales('K',$aux);
        $objSheet->getCell('L'.$j)->setValue(number_format($value['Auxilio'],0,"","")); 
        addValueTotales('L',$value["Auxilio"]);
        $objSheet->getCell('M'.$j)->setValue((INT)$value['Dias_Vacaciones']);
        $objSheet->getCell('N'.$j)->setValue((INT)number_format($value['Total_Vacaciones'],0,"",""));
        addValueTotales('N',$value["Total_Vacaciones"]);
        $objSheet->getCell('O'.$j)->setValue((INT)$value['Dias_Incapacidad']);
        $objSheet->getCell('P'.$j)->setValue((INT)number_format($value['Total_Incapacidades'],0,"",""));
        addValueTotales('P',$value["Total_Incapacidades"]);
        $objSheet->getCell('Q'.$j)->setValue((INT)$value['Dias_Licencia']);
        $objSheet->getCell('R'.$j)->setValue((INT)number_format($value['Total_Licencias'],0,"",""));
        addValueTotales('R',$value["Total_Licencias"]);
        /* $objSheet->getCell('S'.$j)->setValue((INT)$value['Bonificacion_desempeno']);
        addValueTotales('S',$value["Bonificacion_desempeno"]); */
        /* $objSheet->getCell('T'.$j)->setValue((INT)$value['Rodamiento']);
        addValueTotales('T',$value["Rodamiento"]); */
        $objSheet->getCell('S'.$j)->setValue(number_format($value['Total_Salud'],0,"",""));
        addValueTotales('S',$value["Total_Salud"]);
        $objSheet->getCell('T'.$j)->setValue(number_format($value['Total_Pension'],0,"",""));
        addValueTotales('T',$value["Total_Pension"]);
        /* $objSheet->getCell('W'.$j)->setValue((INT)$value['Otras_deducciones']);
        addValueTotales('W',$value["Otras_deducciones"]); */
        $objSheet->getCell('U'.$j)->setValue((INT)number_format($value['Pestamos'],0,"",""));
        addValueTotales('U',$value["Pestamos"]);
        $objSheet->getCell('V'.$j)->setValue((INT)$value['Total_Libranzas']);
        addValueTotales('V',$value["Total_Librazas"]);
        /*$objSheet->getCell('Z'.$j)->setValue((INT)$value['RESPONSABILIDADES']);
        addValueTotales('Z',$value["RESPONSABILIDADES"]);
        $objSheet->getCell('AA'.$j)->setValue((INT)$value['POLIZA_FUNERARIA']);
        addValueTotales('AA',$value["POLIZA_FUNERARIA"]);	
        $objSheet->getCell('AB'.$j)->setValue($value['Total_Solidaridad']);
        addValueTotales('AB',$value["Total_Solidaridad"]);
        $objSheet->getCell('AC'.$j)->setValue($value['Total_Subsistencia']);
        addValueTotales('AC',$value["Total_Subsistencia"]); */
        $final = $value['Total_Quincena'];
        $objSheet->getCell('W'.$j)->setValue(number_format($final,0,"",""));
        addValueTotales('W',$final);
        if($value["Tipo_Contrato"]=="PRECONTRATO"){
            $objSheet->getStyle('A'.$j.':W'.$j)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00FFFF00');
        }
        
        $objSheet->getStyle('H'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('J'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('K'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('L'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('N'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('P'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('R'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('S'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('T'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('U'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('V'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('W'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        /* $objSheet->getStyle('X'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('Y'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('Z'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('AA'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('AB'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('AC'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('AD'.$j)->getNumberFormat()->setFormatCode("#,##0.00"); */
        $i++;
    // }
	
}

$j++;

foreach ($totales as $row => $value) { // FILA DE TOTALES.
    $objSheet->getCell($row.$j)->setValue($value);
    $objSheet->getStyle($row.$j)->getNumberFormat()->setFormatCode("#,##0.00");
    $objSheet->getStyle($row.$j)->getFont()->setBold(true);
}
$j+=2;
$objSheet->getCell('V'.$j)->setValue("TOTAL EFECTIVO");
$objSheet->getStyle('V'.$j)->getFont()->setBold(true);
$objSheet->getCell('W'.$j)->setValue($total_efectivo);
$objSheet->getStyle('W'.$j)->getFont()->setBold(true);
$objSheet->getStyle('W'.$j)->getNumberFormat()->setFormatCode("#,##0.00");

$j++;
foreach($total_banco as $row => $value){
    $objSheet->getCell('V'.$j)->setValue("TOTAL BANCO ".$row);
    $objSheet->getStyle('V'.$j)->getFont()->setBold(true);
    $objSheet->getCell('W'.$j)->setValue($value);
    $objSheet->getStyle('W'.$j)->getFont()->setBold(true);
    $objSheet->getStyle('W'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
    $j++;
}



/* $j += 3;

$objSheet->getCell('A'.$j)->setValue('Pagos en Efectivo');
$objSheet->getStyle('A'.$j)->getFont()->setBold(true)->setSize(13);

$j++;
$objSheet->getCell('A'.$j)->setValue("Item");
$objSheet->getCell('B'.$j)->setValue("Nombre Empleado");
$objSheet->getCell('C'.$j)->setValue("C.C.");
$objSheet->getCell('D'.$j)->setValue("Ciudad");
$objSheet->getCell('E'.$j)->setValue("Cargo");
$objSheet->getCell('F'.$j)->setValue("Banco");
$objSheet->getCell('G'.$j)->setValue("Cuenta");
$objSheet->getCell('H'.$j)->setValue("Sueldo Basico");
$objSheet->getCell('I'.$j)->setValue("Dias Trabajados");
$objSheet->getCell('J'.$j)->setValue("Pago días trabajados");
$objSheet->getCell('K'.$j)->setValue("Auxilio No Salarial ");
$objSheet->getCell('L'.$j)->setValue("Auxilio Transporte");
$objSheet->getCell('M'.$j)->setValue("Dias Vacaciones");
$objSheet->getCell('N'.$j)->setValue("Total Vacaciones");
$objSheet->getCell('O'.$j)->setValue("Dias Incapacidades");
$objSheet->getCell('P'.$j)->setValue("Total_Incapacidades");
$objSheet->getCell('Q'.$j)->setValue("Dias Licencias");
$objSheet->getCell('R'.$j)->setValue("Total Licencias ");
$objSheet->getCell('S'.$j)->setValue("Bonificacion Indicador ");
$objSheet->getCell('T'.$j)->setValue("Auxilio Rodamiento ");
$objSheet->getCell('U'.$j)->setValue("Salud");
$objSheet->getCell('V'.$j)->setValue("Pension");
$objSheet->getCell('W'.$j)->setValue("Otras Deducciones ");
$objSheet->getCell('X'.$j)->setValue("Prestamos");
$objSheet->getCell('Y'.$j)->setValue("Libranzas");
$objSheet->getCell('Z'.$j)->setValue("Responsabilidades");
$objSheet->getCell('AA'.$j)->setValue("Poliza Funeraria ");
$objSheet->getCell('AB'.$j)->setValue("Fondo de Solidaridad ");
$objSheet->getCell('AC'.$j)->setValue("Fondo de Subsistencia");
$objSheet->getCell('AD'.$j)->setValue("Neto a Cancelar");
$objSheet->getStyle('A'.$j.':AD'.$j)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A'.$j.':AD'.$j)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A'.$j.':AD'.$j)->getFont()->setBold(true);
$objSheet->getStyle('A'.$j.':AD'.$j)->getFont()->getColor()->setARGB('FFFFFFFF');

$totales = [];
$i = 0;
foreach($funcionarios as $value){ 
    if ($value['Tiene_Banco'] == 'No') {
        $j++;
        $objSheet->getCell('A'.$j)->setValue(($i+1));
        $objSheet->getCell('B'.$j)->setValue($value["Funcionario"]);
        $objSheet->getCell('C'.$j)->setValue($value["Identificacion_Funcionario"]);
        $objSheet->getCell('D'.$j)->setValue($value["Ciudad"]);
        $objSheet->getCell('E'.$j)->setValue($value["Cargo"]." / ".$value["Tipo_Contrato"]);
        $objSheet->getCell('F'.$j)->setValue("S/Banco");
        $objSheet->getCell('G'.$j)->setValue("S/Cuenta");
        $objSheet->getCell('H'.$j)->setValue($value["Valor"]);
        addValueTotales('H',$value["Valor"]);
        $objSheet->getCell('I'.$j)->setValue($value['Dias_Laborados'] );
        $objSheet->getCell('J'.$j)->setValue($value['Salario_Quincena'] );	
        addValueTotales('J',$value["Salario_Quincena"]);
        $objSheet->getCell('K'.$j)->setValue($value['Auxilio_No_Prestacional']/2);
        addValueTotales('K',$value["Auxilio_No_Prestacional"]/2);
        $objSheet->getCell('L'.$j)->setValue($value['Auxilio']);
        addValueTotales('L',$value["Auxilio"]);
        $objSheet->getCell('M'.$j)->setValue((INT)$value['Dias_Vacaciones']);
        $objSheet->getCell('N'.$j)->setValue((INT)$value['Total_Vacaciones']);
        addValueTotales('N',$value["Total_Vacaciones"]);
        $objSheet->getCell('O'.$j)->setValue((INT)$value['Dias_Incapacidad']);
        $objSheet->getCell('P'.$j)->setValue((INT)$value['Total_Incapacidades']);
        addValueTotales('P',$value["Total_Incapacidades"]);
        $objSheet->getCell('Q'.$j)->setValue((INT)$value['Dias_Licencia']);
        $objSheet->getCell('R'.$j)->setValue((INT)$value['Total_Licencias']);
        addValueTotales('R',$value["Total_Licencias"]);
        $objSheet->getCell('S'.$j)->setValue((INT)$value['Bonificacion_desempeno']);
        addValueTotales('S',$value["Bonificacion_desempeno"]);
        $objSheet->getCell('T'.$j)->setValue((INT)$value['Rodamiento']);
        addValueTotales('T',$value["Rodamiento"]);
        $objSheet->getCell('U'.$j)->setValue($value['Total_Salud']);
        addValueTotales('U',$value["Total_Salud"]);
        $objSheet->getCell('V'.$j)->setValue($value['Total_Pension']);
        addValueTotales('V',$value["Total_Pension"]);
        $objSheet->getCell('W'.$j)->setValue((INT)$value['Otras_deducciones']);
        addValueTotales('W',$value["Otras_deducciones"]);
        $objSheet->getCell('X'.$j)->setValue((INT)$value['Pestamos']);
        addValueTotales('X',$value["Pestamos"]);
        $objSheet->getCell('Y'.$j)->setValue((INT)$value['Librazas']);
        addValueTotales('Y',$value["Librazas"]);
        $objSheet->getCell('Z'.$j)->setValue((INT)$value['RESPONSABILIDADES']);
        addValueTotales('Z',$value["RESPONSABILIDADES"]);
        $objSheet->getCell('AA'.$j)->setValue((INT)$value['POLIZA_FUNERARIA']);
        addValueTotales('AA',$value["POLIZA_FUNERARIA"]);	
        $objSheet->getCell('AB'.$j)->setValue($value['Total_Solidaridad']);
        addValueTotales('AB',$value["Total_Solidaridad"]);
        $objSheet->getCell('AC'.$j)->setValue($value['Total_Subsistencia']);
        addValueTotales('AC',$value["Total_Subsistencia"]);
        $objSheet->getCell('AD'.$j)->setValue($value['Total_Quincena']);
        addValueTotales('AD',$value["Total_Quincena"]);
        
        $objSheet->getStyle('H'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('J'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('K'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('L'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('N'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('P'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('R'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('S'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('T'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('U'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('V'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('W'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('X'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('Y'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('Z'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('AA'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('AB'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('AC'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $objSheet->getStyle('AD'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        $i++;
    }
	
}

$j++;

foreach ($totales as $row => $value) { // FILA DE TOTALES.
    $objSheet->getCell($row.$j)->setValue($value);
    $objSheet->getStyle($row.$j)->getNumberFormat()->setFormatCode("#,##0.00");
    $objSheet->getStyle($row.$j)->getFont()->setBold(true);
} */

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
$objSheet->getColumnDimension('N')->setAutoSize(true);
$objSheet->getColumnDimension('O')->setAutoSize(true);
$objSheet->getColumnDimension('P')->setAutoSize(true);
$objSheet->getColumnDimension('Q')->setAutoSize(true);
$objSheet->getColumnDimension('R')->setAutoSize(true);
$objSheet->getColumnDimension('S')->setAutoSize(true);
$objSheet->getColumnDimension('T')->setAutoSize(true);
$objSheet->getColumnDimension('U')->setAutoSize(true);
$objSheet->getColumnDimension('V')->setAutoSize(true);
$objSheet->getColumnDimension('W')->setAutoSize(true);
$objSheet->getStyle('A1:W'.$j)->getAlignment()->setWrapText(true);
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

function CalcularFechaQuincena($dia_actual, $mes_actual, $anio_actual){

    if ($dia_actual > 15) {

        $fechas = ArmarFecha($mes_actual, $anio_actual);        
        $fecha_quincena = $fechas['quincena2'];
        return $fecha_quincena;
    }else{

       // $mes_anio_anterior = CalcularMes($mes_actual, 1, $anio_actual);
        $mes_anio_actual = CalcularMes($mes_actual, 0, $anio_actual);

       // $fechas = ArmarFecha($mes_anio_anterior['mes'], $mes_anio_anterior['anio']);
        $fechas2 = ArmarFecha($mes_anio_actual['mes'], $mes_anio_actual['anio']);

        $fecha_quincena = $fechas2['quincena1'];

     
        return $fecha_quincena;
    }

}

function ArmarFecha($mes, $anio, $ColocarCeroAlMes = false){
    $fechas = array();

    if ($ColocarCeroAlMes) {
        
        $mes = MesDosDigitos($mes);
    }else{
        $mes = $mes;
    }

    $fechas['quincena1'] = array('inicio' => $anio."-".$mes."-01", 'fin' => $anio."-".$mes."-15");
    $fechas['quincena2'] = array('inicio' => $anio."-".$mes."-16", 'fin' => $anio."-".$mes."-". date("d",(mktime(0,0,0,date($mes)+1,1,date($anio))-1)));

    return $fechas;
}
function MesDosDigitos($mes){
    if ($mes < 10) {
        return "0".$mes;
    }

    return $mes;
}
function CalcularMes($mes_actual, $restar_meses, $anio){

    $mes = $mes_actual - $restar_meses;
    $anio = $anio;

    if ($mes <= 0) {
        $mes = $mes + 12;
        $anio = $anio - 1;      
    }else{
        $mes = $mes;
    }

    return array('anio' => $anio, 'mes' => MesDosDigitos($mes));
}

function ObtenerValores($tipo,$funcionario){
    global $quincena,$fini;
    $query='SELECT IFNULL(SUM(Valor),0) as Valor 
    FROM Movimiento_Funcionario ME 
    INNER JOIN Concepto_Parametro_Nomina CPN ON ME.Id_Tipo=CPN.Id_Concepto_Parametro_Nomina 
    INNER JOIN  Parametro_Nomina PN ON PN.Id_Parametro_Nomina=CPN.Id_Parametro_Nomina
    WHERE ME.Identificacion_Funcionario='.$funcionario.' AND ME.Quincena="'.date("Y-m;",strtotime($fini)).$quincena.'" AND CPN.Nombre LIKE "'.$tipo.'%" ';

  
    $oCon= new consulta();
    $oCon->setQuery($query);
    $datos = $oCon->getData();
    unset($oCon);
    $valor=0;
    if($datos['Valor']){
        $valor=$datos['Valor'];
    }
    return $valor;
}

function ObtenerFechas(){
    global $ffin,$fini,$quincena, $_REQUEST;


    if(date("Y-m-d")<=date("Y-m-15")){
        $fini  = (isset($_REQUEST['fini'] ) ? $_REQUEST['fini'] : date("Y-m")."-01" );
        $ffin  = (isset($_REQUEST['ffin'] ) ? $_REQUEST['ffin'] : date("Y-m-15") );
        $quincena=1;
     }else{
        $fini  = (isset($_REQUEST['fini'] ) ? $_REQUEST['fini'] : date("Y-m")."-16" );
        $ffin  = (isset($_REQUEST['ffin'] ) ? $_REQUEST['ffin'] : date("Y-m")."-". date("d",(mktime(0,0,0,date("m")+1,1,date("Y"))-1))); 
        $quincena=2;
     }

     if(isset($_REQUEST['nomina'])){
        $concepto=$_REQUEST['nomina'];
        $tem=explode(';',$concepto);
        $anio=explode('-',$tem[0]);
   
        $anio_actual=$anio[0];
        $mes_actual=$anio[1];
        if($tem[1]=='1'){
           $dia_actual=10;
           $quincena=1;
        }else{
           $dia_actual=17;
           $quincena=2;
        }
   
    }else{
        $concepto=date('Y-m').';'.$quincena;
    }


}

function addValueTotales($row, $valor) {
    global $totales;

    if (array_key_exists($row,$totales)) {
        $totales[$row] += $valor;
    } else {
        $totales[$row] = $valor ? $valor : 0;
    }
    
}
function addTotal($tiene, $banco, $salario, $auxilio){
    global $total_banco, $total_efectivo;
    if($tiene=="Si"){
        if(isset($total_banco[$banco])){
            $total_banco[$banco]+=$salario;
        }else{
            $total_banco[$banco]=$salario;
        }
        $total_efectivo+= $auxilio;
    }else{
        $total_efectivo+=$salario+$auxilio;
    }
}



