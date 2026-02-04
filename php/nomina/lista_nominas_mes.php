<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

$m = date("Y-m");
$m_a = date("Y-m",strtotime("-1 month",strtotime($m)));

$mes_actual = (INT)date('m')-1;
$mes_ant=(INT)date('m',strtotime($m_a))-1;
$ultimo_dia=(INT)date('d',strtotime(date("d")));

if(date("Y-m-d")<=date("Y-m-").$ultimo_dia){
   $nomina_actual_mes=date('Y-m').";1";
   $nombre_mes='Mes de '.$meses[$mes_actual];

   $nomina_anterior = $m_a.";1";
   $nombre_mes_anterior='Mes de '.$meses[$mes_ant];  
}
else{   
   $nomina_actual_mes=date('Y-m').";1";
   $nombre_mes='Mes de '.$meses[$mes_actual];

   $nomina_anterior=date('Y-m',strtotime($fecha_actual."- 1 month").";1");
   $nombre_mes_anterior='Mes de '.$meses[$mes_ant];
}

$actualmes=[[
   'Nomina'=> $nomina_actual_mes,
   'Nombre'=>$nombre_mes,
]];
$entriormes=[[
   'Nomina'=> $nomina_anterior,
   'Nombre'=>$nombre_mes_anterior,
]];

   $nomina=array_merge($actualmes,$entriormes);
echo json_encode($nomina);

