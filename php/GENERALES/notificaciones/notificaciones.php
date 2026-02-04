<?php
header('Content-type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

function fecha($str)
{
	$parts = explode(" ",$str);
	$date = explode("-",$parts[0]);
	return $date[2] . "/". $date[1] ."/". $date[0];
}
function fecha2($str)
{
	$parts = explode(" ",$str);
	$date = explode("-",$parts[0]);
	$hora= explode(":",$parts[1]);
	if($hora[0]>12){$hora[0]=$hora[0]-12;$p=" pm";}else{$p=" am";}
	return "<b>" . $date[2] . "/". $date[1] ."/". $date[0] . "</b> <span style='color:red;'>" . $hora[0] . ":" .$hora[1]. $p . "</span>";
}
function fecha3($str)
{
	$parts = explode(" ",$str);
	$date = explode("-",$parts[0]);
	return $date[2] . "/". $date[1] ."/". str_replace("20","",$date[0]);
}


$oLista= new lista('Alerta');
$oLista->setRestrict("Respuesta","=","No");
$oLista->setOrder("Fecha","DESC");
$alertas=$oLista->getList();
unset($oLista);

$num=count($alertas);
	
$texto='<a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="ti-bell"></i><span class="notification">'.count($alertas).'</span></a>
       <ul class="dropdown-menu">';
       foreach($alertas as $alerta){
           $texto.='<li><a href="#"><b>'.fecha($alerta["Fecha"]).'</b> '. $alerta["Detalles"].'</a></li>';
       } 
$texto.='</ul>';


echo json_encode(array('num'=>$num,'text'=>$texto));                    
?>