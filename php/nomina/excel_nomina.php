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

//**************************** */
//**************************** */


$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
//$nom = ( isset( $_REQUEST['nom'] ) ? $_REQUEST['nom'] : '' );
$nom = 'Mensual';
$fini  = (isset($_REQUEST['fini'] ) ? $_REQUEST['fini'] : '' );
$ffin  = (isset($_REQUEST['ffin'] ) ? $_REQUEST['ffin'] :  '' );

$d= explode("-",$fini);

$mes_actual = date('m',strtotime($fini));
$anio_actual = date('Y',strtotime($fini));
$dia_actual = date('d',strtotime($fini));

$mes_fin = date('m',strtotime($ffin));
$anio_fin = date('Y',strtotime($ffin));
$dia_fin = date('d',strtotime($ffin));

$quincena2 = '';
if ($nom == 'Mensual') {
    $concepto .= " LIKE '".$anio_actual."-$mes_actual%' ";
    $quincena2 = "%";
    $mensualidad = "'$anio_actual-$mes_actual%'";
}else{

    if($d[2]<=15){
        $quincena="".$anio_actual."-$mes_actual;1";

     }else{ 
        $quincena="".$anio_actual."-$mes_actual;2";
     }        
    if(isset($_REQUEST['nomina'])){
        $concepto= "= '".$_REQUEST['nomina']."' ";       
    }else{
        $concepto = date('Y-m').';'.$quincena;
    }
}

$total_efectivo=0;
$total_banco=[];

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';


$objPHPExcel = new PHPExcel; 
$fechas=CalcularFechaQuincena($dia_actual, $mes_actual, $anio_actual); 

 $query = "SELECT  CF.*, TC.Nombre as Tipo_Contrato, 
            IFNULL( (Select SUM(Valor) FROM Movimiento_Funcionario ME  WHERE ME.Identificacion_Funcionario=F.Identificacion_Funcionario AND ME.Tipo='Egreso' $quincena),0) as Egresos,
            CONCAT(F.Nombres, ' ', F.Apellidos) as Funcionario,  
            C.Nombre as Cargo ,(SELECT Nombre FROM Municipio WHERE Id_Municipio=CF.Id_Municipio) as Ciudad,
            IF(F.Id_Banco IS NOT NULL,'Si','No') AS Tiene_Banco,
            IF(F.Id_Banco IS NOT NULL,(SELECT Nombre FROM Banco WHERE Id_Banco = F.Id_Banco),'Pago en Efectivo') AS Banco,
            IFNULL(F.Cuenta,'Pago en Efectivo') AS Cuenta,
            IF(F.Id_Banco IS NOT NULL OR F.Id_Banco != '', 1, 2) AS Orden
        FROM Contrato_Funcionario CF 
        INNER JOIN Funcionario F ON CF.Identificacion_Funcionario=F.Identificacion_Funcionario
        LEFT JOIN Cargo C on F.Id_Cargo=C.Id_Cargo 
        LEFT JOIN Tipo_Contrato TC On TC.Id_Tipo_Contrato = CF.Id_Tipo_Contrato
    WHERE CF.Estado='Activo' 
    AND F.Liquidado = 'NO'
    AND (CF.Fecha_Fin_Contrato>='$fini' or CF.Id_Tipo_Contrato = 1) 
    AND NOT EXISTS (SELECT * FROM Nomina_Funcionario WHERE Periodo_Pago $concepto AND Identificacion_Funcionario = F.Identificacion_Funcionario)
        -- and CF.Identificacion_Funcionario = 1098755611
        GROUP BY F.Identificacion_Funcionario 
        ORDER BY Orden, Funcionario ";
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$funcionarios = $oCon->getData();

// echo json_encode($funcionarios); exit;

unset($oCon);
$i=0;
foreach ($funcionarios as $value) {  
    if($value['Fecha_Inicio_Contrato']>$fini){
        $fini=$value['Fecha_Inicio_Contrato'];
    }  
    if($value['Fecha_Fin_Contrato']>$fini && $value['Fecha_Fin_Contrato']<$ffin ){
        $ffin=$value['Fecha_Fin_Contrato'];
    }
    // $funcionario=new CalculoNomina($value['Identificacion_Funcionario'],$quincena,$fini,$ffin,'Nomina');
    
    $quince = $quincena2;
    // echo "$value[Identificacion_Funcionario],$quince,$fini,$ffin,'Nomina', $nom, Activo"; exit;
    $funcionario=new CalculoNomina($value['Identificacion_Funcionario'],$quince,$fini,$ffin,'Nomina', $nom, "Activo");
    
    $funcionario=$funcionario->CalculosNomina();

    // echo json_encode($funcionario); exit;
    
    $funcionarios[$i]['Bono']=$funcionario['Resumen'][4]['Valor'];
    $funcionarios[$i]['Total_Licencias']=$funcionario['Total_Licencias'];
    $funcionarios[$i]['Dias_Licencia']=$funcionario['Dias_Licencia'];
    $funcionarios[$i]['Total_Incapacidades']=$funcionario['Total_Incapacidades'];
    $funcionarios[$i]['Dias_Incapacidad']=$funcionario['Dias_Incapacidad'];
    $funcionarios[$i]['Dias_Vacaciones']=$funcionario['Dias_Vacaciones'];
    $funcionarios[$i]['Ingresos_NS']=$funcionario['Ingresos_NS'];
    $funcionarios[$i]['Total_Quincena']=$funcionario['Total_Quincena'] + $funcionario['Resumen'][4]['Valor'];
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
    // $funcionarios[$i]['Pestamos']=ObtenerValores('Prestamo',$value['Identificacion_Funcionario']);
    $funcionarios[$i]['Pestamos'] = $funcionario['Conceptos_Contabilizacion']["Prestamo"];
    $funcionarios[$i]['POLIZA_FUNERARIA']=ObtenerValores('POLIZA FUNERARIA',$value['Identificacion_Funcionario']);
    $funcionarios[$i]['Librazas']=ObtenerValores('Librazas',$value['Identificacion_Funcionario']);
    $funcionarios[$i]['Bonificacion_desempeno']=ObtenerValores('Bonificacion extralegal por desempeño',$value['Identificacion_Funcionario']);
    $funcionarios[$i]['Otras_deducciones']=ObtenerValores('Otras deducciones',$value['Identificacion_Funcionario']);
    $funcionarios[$i]['Rodamiento']=ObtenerValores('Auxilio de movilizacions',$value['Identificacion_Funcionario']);
    $funcionarios[$i]['RESPONSABILIDADES']=ObtenerValores('RESPONSABILIDADES',$value['Identificacion_Funcionario']);
    
    $i++;
}

// echo json_encode($funcionarios); exit;

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
$objSheet->getCell('A2')->setValue("Item");
$objSheet->getCell('B2')->setValue("Nombre Empleado");
$objSheet->getCell('C2')->setValue("C.C.");
$objSheet->getCell('D2')->setValue("Ciudad");
$objSheet->getCell('E2')->setValue("Cargo");
$objSheet->getCell('F2')->setValue("Sueldo Basico");
$objSheet->getCell('G2')->setValue("Dias Trabajados");
$objSheet->getCell('H2')->setValue("Pago días trabajados");
$objSheet->getCell('I2')->setValue("Auxilio No Salarial ");
$objSheet->getCell('J2')->setValue("Auxilio Transporte");
$objSheet->getCell('K2')->setValue("Dias Vacaciones");
$objSheet->getCell('L2')->setValue("Total Vacaciones");
$objSheet->getCell('M2')->setValue("Dias Incapacidades");
$objSheet->getCell('N2')->setValue("Total_Incapacidades");
$objSheet->getCell('O2')->setValue("Dias Licencias");
$objSheet->getCell('P2')->setValue("Total Licencias ");
$objSheet->getCell('Q2')->setValue("Bonificacion Indicador ");
$objSheet->getCell('R2')->setValue("Auxilio Rodamiento ");
$objSheet->getCell('S2')->setValue("Salud");
$objSheet->getCell('T2')->setValue("Pension");
$objSheet->getCell('U2')->setValue("Otras Deducciones ");
$objSheet->getCell('V2')->setValue("Prestamos");
$objSheet->getCell('W2')->setValue("Libranzas");
$objSheet->getCell('X2')->setValue("Responsabilidades");
$objSheet->getCell('Y2')->setValue("Poliza Funeraria ");
$objSheet->getCell('Z2')->setValue("Fondo de Solidaridad ");
$objSheet->getCell('AA2')->setValue("Fondo de Subsistencia");
$objSheet->getCell('AB2')->setValue("Bonos");
$objSheet->getCell('AC2')->setValue("Neto a Cancelar");

$objSheet->getStyle('A2:AC2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A2:AC2')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A2:AC2')->getFont()->setBold(true);
$objSheet->getStyle('A2:AC2')->getFont()->getColor()->setARGB('FFFFFFFF');

$j=2;
foreach($funcionarios as $value){ $j++;
	$objSheet->getCell('A'.$j)->setValue($j);
	$objSheet->getCell('B'.$j)->setValue($value["Funcionario"]);
	$objSheet->getCell('C'.$j)->setValue($value["Identificacion_Funcionario"]);
	$objSheet->getCell('D'.$j)->setValue($value["Ciudad"]);
	$objSheet->getCell('E'.$j)->setValue($value["Cargo"]);
    $objSheet->getCell('F'.$j)->setValue($value["Valor"]);
    $objSheet->getCell('G'.$j)->setValue($value['Dias_Laborados'] );
	$objSheet->getCell('H'.$j)->setValue($value['Salario_Quincena'] );	
	$objSheet->getCell('I'.$j)->setValue($quincena==2 ? $value['Auxilio_No_Prestacional']+$value['Ingresos_NS'] : 0 );
	$objSheet->getCell('J'.$j)->setValue($value['Auxilio']);
	$objSheet->getCell('K'.$j)->setValue((INT)$value['Dias_Vacaciones']);
	$objSheet->getCell('L'.$j)->setValue((INT)$value['Total_Vacaciones']);
	$objSheet->getCell('M'.$j)->setValue((INT)$value['Dias_Incapacidad']);
	$objSheet->getCell('N'.$j)->setValue((INT)$value['Total_Incapacidades']);
	$objSheet->getCell('O'.$j)->setValue((INT)$value['Dias_Licencia']);
	$objSheet->getCell('P'.$j)->setValue((INT)$value['Total_Licencias']);
	$objSheet->getCell('Q'.$j)->setValue((INT)$value['Bonificacion_desempeno']);
    $objSheet->getCell('R'.$j)->setValue((INT)$value['Rodamiento']);
    $objSheet->getCell('S'.$j)->setValue($value['Total_Salud']);
	$objSheet->getCell('T'.$j)->setValue($value['Total_Pension']);
	$objSheet->getCell('U'.$j)->setValue((INT)$value['Otras_deducciones']);
	$objSheet->getCell('V'.$j)->setValue((INT)$value['Pestamos']);
	$objSheet->getCell('W'.$j)->setValue((INT)$value['Librazas']);
	$objSheet->getCell('X'.$j)->setValue((INT)$value['RESPONSABILIDADES']);
	$objSheet->getCell('Y'.$j)->setValue((INT)$value['POLIZA_FUNERARIA']);	
	$objSheet->getCell('Z'.$j)->setValue($value['Total_Solidaridad']);
    $objSheet->getCell('AA'.$j)->setValue($value['Total_Subsistencia']);
    $objSheet->getCell('AB'.$j)->setValue($value['Bono']);
    $objSheet->getCell('AC'.$j)->setValue($value['Total_Quincena']);
    
    $objSheet->getStyle('F'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
    $objSheet->getStyle('H'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	$objSheet->getStyle('I'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	$objSheet->getStyle('J'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	$objSheet->getStyle('L'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	$objSheet->getStyle('N'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	$objSheet->getStyle('P'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	$objSheet->getStyle('Q'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
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
$objSheet->getColumnDimension('L')->setAutoSize(true);
$objSheet->getColumnDimension('M')->setAutoSize(true);
$objSheet->getColumnDimension('N')->setAutoSize(true);
$objSheet->getColumnDimension('O')->setAutoSize(true);
$objSheet->getColumnDimension('P')->setAutoSize(true);
$objSheet->getColumnDimension('Q')->setAutoSize(true);
$objSheet->getColumnDimension('R')->setAutoSize(true);
$objSheet->getStyle('A1:R'.$j)->getAlignment()->setWrapText(true);
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Resumen_Nomina.xls"');
header('Cache-Control: max-age=0');

$objWriter->save('php://output');

function CalcularFechaQuincena($dia_actual, $mes_actual, $anio_actual){

    if ($dia_actual > 15) {

        $fechas = ArmarFecha($mes_actual, $anio_actual);        
        $fecha_quincena = $fechas['quincena2'];
        return $fecha_quincena;
    }else{

        $mes_anio_actual = CalcularMes($mes_actual, 0, $anio_actual);
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





