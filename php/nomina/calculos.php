<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

$debug = (isset($_REQUEST['debug']) && $_REQUEST['debug'] == '1');
$trace = array();
if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ob_start();
    $trace[] = 'debug_enabled';
    register_shutdown_function(function () {
        global $trace;
        $error = error_get_last();
        if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
            http_response_code(500);
            echo json_encode(array(
                'error' => $error['message'],
                'file' => basename($error['file']),
                'line' => $error['line'],
                'trace' => $trace
            ));
            return;
        }

        if (ob_get_length() === 0) {
            echo json_encode(array(
                'error' => 'empty_output',
                'trace' => $trace
            ));
            return;
        }
    });
    set_exception_handler(function ($e) {
        global $trace;
        http_response_code(500);
        echo json_encode(array(
            'error' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'trace' => $trace
        ));
    });
}

require_once('../../config/start.inc.php');
if ($debug) { $trace[] = 'start_inc'; }
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.nomina.php');
include_once('../../class/class.parafiscales.php');
include_once('../../class/class.provisiones.php');
if ($debug) { $trace[] = 'includes_done'; }

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$nom = ( isset( $_REQUEST['nom'] ) ? $_REQUEST['nom'] : '' );
$fini  = (isset($_REQUEST['fini'] ) ? $_REQUEST['fini'] : '' );
$ffin  = (isset($_REQUEST['ffin'] ) ? $_REQUEST['ffin'] :  '' );
$estado  = (isset($_REQUEST['estado'] ) ? $_REQUEST['estado'] :  'Activo' );
if ($debug) { $trace[] = 'params_loaded'; }

$d= explode("-",$fini);

$mes_actual = date('m',strtotime($fini));
$anio_actual = date('Y',strtotime($fini));
$dia_actual = date('d',strtotime($fini));

$aux= date('Y-m-d', strtotime("{$fini} + 1 month"));
$last_day = date('Y-m-d', strtotime("{$aux} - 1 day"));
//$ffin = $last_day;


//Calcular mes actual
$mes_fin = date('m',strtotime("{$ffin}- 1 month"));
$anio_fin = date('Y',strtotime($ffin));
$dia_fin = date('d',strtotime($ffin));

//Ultimo dia del mes
$ultimo_dia_mes = date('t', strtotime("$anio_actual-$mes_actual-01"));

if ($nom == 'Mensual') {
    $quincena = "%";
    $mensualidad = "'$anio_actual-$mes_actual%'";
}else{
    if($d[2]<=15){
        $quincena="1";
     }else{ 
        $quincena="2";
     }
}

$query = "SELECT F.Identificacion_Funcionario, CF.Fecha_Inicio_Contrato,CF.Fecha_Fin_Contrato
          FROM Contrato_Funcionario CF 
          INNER JOIN Funcionario F ON CF.Identificacion_Funcionario=F.Identificacion_Funcionario
          WHERE CF.Estado='$estado' AND F.Identificacion_Funcionario= $id";
          
$oCon= new consulta();
$oCon->setQuery($query);
$contrato = $oCon->getData();
unset($oCon);
if ($debug) { $trace[] = 'contrato_loaded'; }

if($contrato['Fecha_Inicio_Contrato']>$fini){
    $fini=$contrato['Fecha_Inicio_Contrato'];
}
if($contrato['Fecha_Fin_Contrato']>=$fini && $contrato['Fecha_Fin_Contrato']<$ffin ){
    $ffin=$contrato['Fecha_Fin_Contrato'];
     $fin = $ffin;
}

 //echo ("$id,$quincena,$fini,$ffin,'Nomina', $nom, $mensualidad");exit;
$funcionario=new CalculoNomina($id, $quincena, $fini, $ffin, 'Nomina', $nom, $estado);
$funcionario=$funcionario->CalculosNomina();
if ($debug) { $trace[] = 'nomina_calculated'; }
$paraficales=new CalculosParafiscales($funcionario['Total_IBC'],$funcionario['Sueldo'],$id);  
$paraficales=$paraficales->CalcularParafiscales();
if ($debug) { $trace[] = 'parafiscales_calculated'; }

$base_aux = $funcionario['Salario_Quincena'] + $funcionario['Auxilio'] + $funcionario['Total_Incapacidades'];
$provisiones=new CalculosProvisiones($base_aux,$id,$fini,$ffin, $funcionario['Salario_Quincena']);  
$provisiones=$provisiones->CalcularProvisiones();
if ($debug) { $trace[] = 'provisiones_calculated'; }

$funcionario['Parafiscales']= $paraficales;
$funcionario['Provisiones']=$provisiones;
$funcionario['Funcionario']=ObtenerFuncionario($id); 

$funcionario['Fecha_Quincena']= CalcularFechaQuincena($dia_actual, $mes_actual, $anio_actual);

if ($debug) {
    $funcionario['_debug_version'] = 'vacaciones-tomadas-cap-1';
    $funcionario['_debug_trace'] = $trace;
}

$json_flags = JSON_UNESCAPED_UNICODE;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
    $json_flags = $json_flags | JSON_INVALID_UTF8_SUBSTITUTE;
}
$payload = json_encode($funcionario, $json_flags);
if ($payload === false) {
    http_response_code(500);
    echo json_encode(array(
        'error' => 'json_encode_failed',
        'message' => json_last_error_msg(),
        'trace' => $trace
    ));
} else {
    echo $payload;
}

function CalcularFechaQuincena($dia_actual, $mes_actual, $anio_actual){
    global $nom,$anio_fin,$dia_fin,$mes_fin,$ultimo_dia_mes;

    

    if ($nom == 'Mensual') {
        $fechas = array();
        $fechas = array('inicio' => $anio_actual."-".$mes_actual."-".$dia_actual, 'fin' => $anio_fin."-".$mes_fin."-".$ultimo_dia_mes);

    return $fechas;

    }else{
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

function ObtenerFuncionario($id){
    $query = 'SELECT F.* FROM Funcionario F 
    WHERE F.Identificacion_Funcionario = '.$id ;
    $oCon= new consulta();
    $oCon->setQuery($query);
    $funcionario = $oCon->getData();
    unset($oCon);

    return $funcionario;

}



?>
