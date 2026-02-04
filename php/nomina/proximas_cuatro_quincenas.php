<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

$quincena = getQuincena();

echo json_encode($quincena);

function getQuincena() {
    $mes_hoy = date('Y-m');
    $dia_hoy = date('d');
    
    $nuevo_mes1 = strtotime("+1 months", strtotime($mes_hoy));
    $nuevo_mes2 = strtotime("+2 months", strtotime($mes_hoy));
    $mes_actual = getMes($mes_hoy);
    $mes_proximo1 = getMes(date('Y-m', $nuevo_mes1));
    $mes_proximo2 = getMes(date('Y-m', $nuevo_mes2));
    if($dia_hoy<=15){
        $quincena[0]["Nombre"] = "1 Quincena de ".$mes_actual;
        $quincena[0]["Fecha"] = date("Y-m-15");
        $quincena[1]["Nombre"] = "2 Quincena de ".$mes_actual;
        $quincena[1]["Fecha"] = date("Y-m-").date("d",(mktime(0,0,0,date("m")+1,1,date("Y"))-1));
        $quincena[2]["Nombre"] = "1 Quincena de ".$mes_proximo1;
        $quincena[2]["Fecha"] = date('Y-m-15', $nuevo_mes1);
        $quincena[3]["Nombre"] = "2 Quincena de ".$mes_proximo1; 
        $quincena[3]["Fecha"] = date('Y-m-', $nuevo_mes1).date("d",(mktime(0,0,0,date("m", $nuevo_mes1)+1,1,date("Y", $nuevo_mes1))-1));
    }else{
        $quincena[0]["Nombre"] = "2 Quincena de ".$mes_actual;
        $quincena[0]["Fecha"] = date("Y-m-").date("d",(mktime(0,0,0,date("m")+1,1,date("Y"))-1));
        $quincena[1]["Nombre"] = "1 Quincena de ".$mes_proximo1;
        $quincena[1]["Fecha"] =  date('Y-m-15', $nuevo_mes1);
        $quincena[2]["Nombre"] = "2 Quincena de ".$mes_proximo1;
        $quincena[2]["Fecha"] = date('Y-m-', $nuevo_mes1).date("d",(mktime(0,0,0,date("m", $nuevo_mes1)+1,1,date("Y", $nuevo_mes1))-1));
        $quincena[3]["Nombre"] = "1 Quincena de ".$mes_proximo2; 
        $quincena[3]["Fecha"] = date('Y-m-15', $nuevo_mes2);
    }
    
    $response = $quincena;

    return $response;
}

function getMes($fecha){
    global $meses;
    $f=explode("-",$fecha);
    $m=(INT)$f[1]-1;
    return $meses[$m]." del ".$f[0];
}

?>