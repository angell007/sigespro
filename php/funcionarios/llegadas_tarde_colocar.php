<?php
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once 'HTTP/Request2.php';
											    
date_default_timezone_set('America/Bogota');

function RestarHoras($horaini,$horafin)
{
	$horai=substr($horaini,0,2);
	$mini=substr($horaini,3,2);
	$segi=substr($horaini,6,2);
 
	$horaf=substr($horafin,0,2);
	$minf=substr($horafin,3,2);
	$segf=substr($horafin,6,2);
 
	$ini=((($horai*60)*60)+($mini*60)+$segi);
	$fin=((($horaf*60)*60)+($minf*60)+$segf);
 
	$dif=$fin-$ini;
	$band=0;
	if($dif<0){
		$dif=$dif*(-1);
		$band=1;
	}
 
	$difh=floor($dif/3600);
	$difm=floor(($dif-($difh*3600))/60);
	$difs=$dif-($difm*60)-($difh*3600);
	if($band==0){
		return "-".date("H:i:s",mktime($difh,$difm,$difs));
	}else{
		return date("H:i:s",mktime($difh,$difm,$difs));
	}
	
}

$dias = array(
	0=> "Domingo",
	1=> "Lunes",
	2=> "Martes",
	3=> "Miercoles",
	4=> "Jueves",
	5=> "Viernes",
	6=> "Sabado"
);



$oLista= new lista('Diario_Fijo');
$oLista->setRestrict("Id_Turno","!=","5");
$oLista->setRestrict("Identificacion_Funcionario","!=","63321784");
$diarios=$oLista->getList();


foreach($diarios as $diario){
    $oItem = new complex('Turno','Id_Turno',$diario["Id_Turno"]);
    $turno=$oItem->getData();
    
    $oLista= new lista('Hora_Turno');
	$oLista->setRestrict("Id_Turno","=",$turno["Id_Turno"]);
	$oLista->setRestrict("Dia","=",$dias[date("w",strtotime($diario["Fecha"]))]);
	$horas=$oLista->getList();
	
	
	$diferencia=RestarHoras($diario["Hora_Entrada2"],$horas[0]["Hora_Inicio2"]);
	
	$diferencia=explode(":",$diferencia);
	
	$a=str_replace("00","",$diferencia[0]);
	$diff=($diferencia[0]*60*60)+($diferencia[1]*60)+($diferencia[2]);
    if(strpos($a, "-")===false){
        
        echo $diario["Hora_Entrada2"]." - ".$horas[0]["Hora_Inicio2"]." - ".$diff."<br>";
 /*
        $oItem = new complex('Llegada_Tarde','Id_Llegada_Tarde');
        $oItem->Identificacion_Funcionario=$diario["Identificacion_Funcionario"];
        $oItem->Fecha=$diario["Fecha"];
        $oItem->Tiempo=$diff;
        $oItem->Entrada_Turno=$horas[0]["Hora_Inicio2"];
        $oItem->Entrada_Real=$diario["Hora_Entrada2"];
        $oItem->save();
        unset($oItem);
       */ 
        
        $oItem = new complex('Funcionario','Identificacion_Funcionario',$diario["Identificacion_Funcionario"]);
		$funcionario=$oItem->getData();
		unset($oItem);
        
        $oItem = new complex('Alerta','Id_Alerta');
		$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
		$oItem->Fecha=$diario["Fecha"]." ".$diario["Hora_Entrada1"];
		$oItem->Tiempo=$diff;
		$oItem->Tipo="Llegada Tarde";
		$oItem->Detalles=$funcionario["Nombres"]." ".$funcionario["Apellidos"]." ha llegado Tarde";
		$oItem->save();
		unset($oItem);
        
    }
    
    
}



?>