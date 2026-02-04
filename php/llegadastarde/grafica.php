<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$mes_hoy = date('m');

$meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
$final=[];
$j=-1;
for($i=($mes_hoy-2);$i<=12;$i++){$j++;

	$query = 'SELECT COUNT(*) as Cantidad
	FROM `Llegada_Tarde` LT 
	WHERE LT.Fecha LIKE "%'.date("Y-").str_pad($i, 2, '0', STR_PAD_LEFT).'%"' ;
	
	$oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$mes = $oCon->getData();
	unset($oCon);
	if(isset($mes[0]["Cantidad"])){
	   $final[$j]["Cantidad"] = $mes[0]["Cantidad"];
	}else{
	   $final[$j]["Cantidad"] = 0;
	}
	
	$final[$j]["Mes"] = $meses[$i-1];
}




echo json_encode($final);


?>