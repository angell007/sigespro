<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

if(date("Y-m-d")<=date("Y-m-15")){
   $fini  = (isset($_REQUEST['fini'] ) ? $_REQUEST['fini'] : date("Y-m")."-01" );
   $ffin  = (isset($_REQUEST['ffin'] ) ? $_REQUEST['ffin'] : date("Y-m-15") );
   $quincena=1;
}else{
   $fini  = (isset($_REQUEST['fini'] ) ? $_REQUEST['fini'] : date("Y-m")."-15" );
   $ffin  = (isset($_REQUEST['ffin'] ) ? $_REQUEST['ffin'] : date("Y-m")."-". date("d",(mktime(0,0,0,date("m")+1,1,date("Y"))-1))); 
   $quincena=2;
}
$mes_actual = date('m');
$anio_actual = date('Y');
$dia_actual = date('d');
    
$oItem = new complex("Configuracion","Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);

$salario_minimo = $config["Salario_Base"];
$auxilio_transporte = $config["Subsidio_Transporte"];
$maximo_liquidacion = $config["Maximo_Cotizacion"];
$salario_maximo = $salario_minimo * $maximo_liquidacion;

$query = 'SELECT F.*, 

(Select SUM(Valor) 
FROM Movimiento_Funcionario ME 
INNER JOIN Tipo_Ingreso TI ON ME.Id_Tipo=TI.Id_Tipo_Ingreso 
WHERE ME.Identificacion_Funcionario=F.Identificacion_Funcionario AND ME.Tipo="Ingreso" AND TI.Tipo="Prestacional" AND ME.Quincena="'.date("Y-m;").$quincena.'") as Ingresos_S,

(Select SUM(Valor) 
FROM Movimiento_Funcionario ME 
INNER JOIN Tipo_Ingreso TI ON ME.Id_Tipo=TI.Id_Tipo_Ingreso 
WHERE ME.Identificacion_Funcionario=F.Identificacion_Funcionario AND ME.Tipo="Ingreso" AND TI.Tipo="No_Prestacional" AND ME.Quincena="'.date("Y-m;").$quincena.'") as Ingresos_N,

(Select SUM(Valor) 
FROM Movimiento_Funcionario ME 
WHERE ME.Identificacion_Funcionario=F.Identificacion_Funcionario AND ME.Tipo="Egreso" AND ME.Quincena="'.date("Y-m;").$quincena.'") as Egresos,


C.Nombre as Cargo
FROM  Funcionario F
INNER JOIN Cargo C
ON F.Id_Cargo = C.Id_Cargo
WHERE F.Identificacion_Funcionario = '.$id ;

$oCon= new consulta();
$oCon->setQuery($query);
$funcionario = $oCon->getData();
unset($oCon);

$query2 = 'SELECT TI.*, IFNULL(MF.Valor,0) as Valor, MF.Id_Movimiento_Funcionario
FROM Tipo_Ingreso TI
LEFT JOIN Movimiento_Funcionario MF
ON TI.Id_Tipo_Ingreso = MF.Id_Tipo AND MF.Tipo="Ingreso" AND MF.Identificacion_Funcionario='.$id.' AND MF.Quincena="'.date("Y-m;").$quincena.'"
WHERE TI.Tipo="Prestacional" 
';     
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query2);
$ingresos_salariales = $oCon->getData();
unset($oCon);

$query3 = 'SELECT TI.*, IFNULL(MF.Valor,0) as Valor, MF.Id_Movimiento_Funcionario
FROM Tipo_Ingreso TI
LEFT JOIN Movimiento_Funcionario MF
ON TI.Id_Tipo_Ingreso = MF.Id_Tipo AND MF.Tipo="Ingreso" AND MF.Identificacion_Funcionario='.$id.' AND MF.Quincena="'.date("Y-m;").$quincena.'"
WHERE TI.Tipo="No_Prestacional" 
';     
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query3);
$ingresos_no_salariales = $oCon->getData();
unset($oCon);

$query4 = 'SELECT TE.*, IFNULL(MF.Valor,0) as Valor , MF.Id_Movimiento_Funcionario
FROM Tipo_Egreso TE
LEFT JOIN Movimiento_Funcionario MF
ON TE.Id_Tipo_Egreso = MF.Id_Tipo AND MF.Tipo="Egreso" AND MF.Identificacion_Funcionario='.$id.' AND MF.Quincena="'.date("Y-m;").$quincena.'"
';     
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query4);
$lista_egresos = $oCon->getData();
unset($oCon);

$query5 = 'SELECT TN.*, N.*

FROM Tipo_Novedad TN
INNER JOIN Novedad N
On TN.Id_Tipo_Novedad = N.Id_Tipo_Novedad AND TN.Tipo_Novedad!="Hora_Extra" AND TN.Tipo_Novedad!="Recargo" AND N.Identificacion_Funcionario='.$id.' 
AND ((N.Fecha_Inicio>="'.$fini.'" AND N.Fecha_Inicio<="'.$ffin.'") OR (N.Fecha_Fin>="'.$fini.'" AND N.Fecha_Fin<="'.$ffin.'") OR (N.Fecha_Inicio<="'.$fini.'" AND N.Fecha_Fin>="'.$ffin.'"))
';     
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query5);
$lista_novedades = $oCon->getData();
unset($oCon);


$query6 = 'SELECT TN.*,
IFNULL((SELECT SUM(Tiempo) FROM Novedad WHERE Id_Tipo_Novedad = TN.Id_Tipo_Novedad AND Identificacion_Funcionario='.$id.' AND CAST(Fecha_Inicio AS DATE)>=".$fini." AND CAST(Fecha_Fin AS DATE)<=".$ffin."),0) as Tiempo
FROM Tipo_Novedad TN
WHERE TN.Tipo_Novedad="Hora_Extra" OR TN.Tipo_Novedad="Recargo"  ';
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query6);
$lista_extras = $oCon->getData();
unset($oCon);

$z=-1;
$total_extras = 0;
foreach($lista_extras as $extra){
   $val_extra = ($funcionario["Salario"]*$extra["Valor"]*$extra["Tiempo"])/(30*8);
   $total_extras+=$val_extra;
}
//var_dump($lista_novedades);


$y=-1;
$dias_ausente = 0;

$lista_vacaciones=[];
$lista_incapacidades=[];
$lista_licencias=[];
$total_vacaciones = 0;
$total_incapacidades=0;
$total_licencias=0;
foreach($lista_novedades as $nov){ $y++;
    if($nov["Fecha_Inicio"]<=$fini){
        $ini_nov = $fini;
    }else{
        $ini_nov=$nov["Fecha_Inicio"];
    }
    if($nov["Fecha_Fin"]>=$ffin){
        $fin_nov = $ffin;
    }else{
        $fin_nov=$nov["Fecha_Fin"];
    }
    $dias_nov = round((strtotime($fin_nov) - strtotime($ini_nov))/ 86400);
    $lista_novedades[$y]["Dias"]=$dias_nov;
    $dias_ausente += $dias_nov;
    
    if($nov["Tipo_Novedad"]=="Vacaciones"){
        $lista_vacaciones[]=$lista_novedades[$y];
        $total_vacaciones+=($funcionario["Salario"]*$lista_novedades[$y]["Dias"])/30;
    }
    if($nov["Tipo_Novedad"]=="Incapacidad"){
        $lista_incapacidades[]=$lista_novedades[$y];
        $total_incapacidades+=($funcionario["Salario"]*$lista_novedades[$y]["Dias"])/30;
    }
    if($nov["Tipo_Novedad"]=="Licencia"){
        $lista_licencias[]=$lista_novedades[$y];
        if($nov["Id_Tipo_Novedad"]<7){
            $total_licencias+=($funcionario["Salario"]*$lista_novedades[$y]["Dias"])/30;
        }
        
    }
    
}
$dias=15;
$dias_laborados = $dias - $dias_ausente;

$aux_trans = 0;
$sueldo_dia = $funcionario["Salario"]/30;
$salario_quincena= $sueldo_dia*$dias_laborados;


$total_ibc = $salario_quincena+$total_extras+$total_vacaciones+$total_incapacidades+$total_licencias+(INT)$funcionario["Ingresos_S"];


if($funcionario["Salario"]>$salario_maximo){
    $deduccion_salud=($salario_maximo/2)*4/100;
    $deduccion_pension=($salario_maximo/2)*4/100;
}elseif($funcionario["Salario"]<=($salario_minimo*2)){
    $deduccion_salud=$total_ibc*4/100;
    $deduccion_pension=$total_ibc*4/100;
    $aux_trans = ($auxilio_transporte/30)*$dias_laborados;
}else{
    $deduccion_salud=$total_ibc*4/100;
    $deduccion_pension=$total_ibc*4/100;
}




$egresos=(INT)$funcionario["Egresos"];
$bonos=(INT)$funcionario["Auxilio_No_Salarial"];
$ingresos_ns= (INT)$funcionario["Ingresos_N"];

$funcionario["Ingresos_S"]= (INT)$funcionario["Ingresos_S"];
$funcionario["Salario_Quincena"]=(INT)number_format($salario_quincena,0,"","");
$funcionario["Salario_Dia"]=(INT)number_format($sueldo_dia,0,"","");
$funcionario["Egresos"]=(INT)number_format($egresos,0,"","");
$funcionario["Auxilio_Transporte"]=(INT)number_format($aux_trans,0,"","");
$funcionario["Subsidio_Transporte"]=(INT)number_format($auxilio_transporte,0,"","");
$funcionario["Total_Extras"]=(INT)number_format($total_extras,0,"","");
$funcionario["Total_Vacaciones"]=(INT)number_format($total_vacaciones,0,"","");
$funcionario["Total_Incapacidades"]=(INT)number_format($total_incapacidades,0,"","");
$funcionario["Total_Licencias"]=(INT)number_format($total_licencias,0,"","");
$funcionario["Total_IBC"]=(INT)number_format($total_ibc,0,"","");


$funcionario["Deduccion_Salud"]=(INT)number_format($deduccion_salud,0,"","");
$funcionario["Deduccion_Pension"]=(INT)number_format($deduccion_pension,0,"","");


$funcionario["Lista_Ingresos_Salariales"]=$ingresos_salariales;
$funcionario["Lista_Ingresos_No_Salariales"]=$ingresos_no_salariales;
$funcionario["Lista_Egresos"]=$lista_egresos;
$funcionario["Lista_Novedades"]=$lista_novedades;
$funcionario["Lista_Extras"]=$lista_extras;
$funcionario["Lista_Vacaciones"]=$lista_vacaciones;
$funcionario["Lista_Incapacidades"]=$lista_incapacidades;
$funcionario["Lista_Licencias"]=$lista_licencias;

$funcionario["Dias_Periodo"]=$dias;
$funcionario["Dias_Laborados"]=$dias_laborados;

if($quincena==2){
  $ingresos_ns+=(INT)number_format($bonos,0,"","");  
}
$funcionario["Ingresos_NS"]=(INT)number_format($ingresos_ns,0,"","");
$funcionario["Fecha_Quincena"]= CalcularFechaQuincena($dia_actual, $mes_actual, $anio_actual);

echo json_encode($funcionario,JSON_UNESCAPED_UNICODE);


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

?>