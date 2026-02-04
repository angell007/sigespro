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

if(date("Y-m-d")<=date("Y-m-15")){
   $nomina_actual=date('Y-m').";1";
   $nombre='Primera Quincena de '.$meses[$mes_actual];
   
   $nomina_2 = $m_a.";2";
   $nombre2='Segunda Quincena de '.$meses[$mes_ant];
   
}else{
   $nomina_actual=date('Y-m').";2";
   $nombre='Segunda Quincena de '.$meses[$mes_actual];
   
   $nomina_2=date('Y-m').";1";
   $nombre2='Primera Quincena de '.$meses[$mes_actual];
}

$actual=[[
   'Nomina'=> $nomina_actual,
   'Nombre'=>$nombre,
]];
$new=[[
   'Nomina'=> $nomina_2,
   'Nombre'=>$nombre2,
]];
   $nomina=array_merge($actual,$new);
echo json_encode($nomina);


/*

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

if(date("Y-m-d")<=date("Y-m-15")){
   $nomina_actual=date('Y-m').";1";
   $nombre='Primera Quincena de '.$meses[$mes_actual];
   
   $nomina_2 = $m_a.";2";
   $nombre2='Segunda Quincena de '.$meses[$mes_ant];
   
}else{
   $nomina_actual=date('Y-m').";2";
   $nombre='Segunda Quincena de '.$meses[$mes_actual];
   
   $nomina_2=date('Y-m').";1";
   $nombre2='Primera Quincena de '.$meses[$mes_actual];
}

$actual=[[
   'Nomina'=> $nomina_actual,
   'Nombre'=>$nombre,
]];

$new=[[
   'Nomina'=> $nomina_2,
   'Nombre'=>$nombre2,
]];

$nomina=array_merge($actual,$new);
echo json_encode($nomina);



?>




*/
?>


