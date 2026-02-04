<?php

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');


$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

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

if($datos["Tipo"]=="Rotativo"){
	$nom='Diario';
}else{
	$nom='Diario_Fijo';
}

if($datos["id"]!=0){
	$oItem = new complex($nom,'Id_'.$nom,$datos["id"]);	
}else{
	$oItem = new complex($nom,'Id_'.$nom);
}

if($datos["Turno"]==''||$datos["Turno"]==0){
	$oLista= new lista("Horario");
	$oLista->setRestrict("Identificacion_Funcionario","=",$datos["Funcionario"]);
	$oLista->setRestrict("Fecha","LIKE",$datos["Fecha"]);
	$horarios=$oLista->getList();
	
	if(isset($horarios[0]["Id_Horario"])){
		$datos["Turno"]=$horarios[0]["Id_Turno"];
	}else{
		$datos["Turno"]=0;	
	}
	
}
$oItem->Fecha=$datos["Fecha"];
$oItem->Identificacion_Funcionario=$datos["Funcionario"];
$oItem->Id_Turno=$datos["Turno"];
	
if($nom=="Diario_Fijo"){
	
	if($datos["he1_or"]!=$datos["he1"]){
		
		$oLista= new lista("Llegada_Tarde");
		$oLista->setRestrict("Identificacion_Funcionario","=",$datos["Funcionario"]);
		$oLista->setRestrict("Fecha","=",$datos["Fecha"]);
		$oLista->setRestrict("Entrada_Real","=",$datos["he1_or"]);
		$tardes=$oLista->getList();

		$oLista= new lista("Alerta");
		$oLista->setRestrict("Identificacion_Funcionario","=",$datos["Funcionario"]);
		$oLista->setRestrict("Fecha","LIKE",$datos["Fecha"]." ".$datos["he1_or"]);
		$alertas=$oLista->getList();
			
		foreach($tardes as $tarde){
			$oItem6 = new complex('Llegada_Tarde','Id_Llegada_Tarde',$tarde["Id_Llegada_Tarde"]);
			$oItem6->delete();
			unset($oItem6);
		}
		foreach($alertas as $alerta){
			$oItem6 = new complex('Alerta','Id_Alerta',$alerta["Id_Alerta"]);
			$oItem6->delete();
			unset($oItem6);
		}
		$oItem2 = new complex('Turno','Id_Turno',$datos["Turno"]);
		$turno=$oItem2->getData();
		unset($oItem2);	
		
		$oLista= new lista('Hora_Turno');
		$oLista->setRestrict("Id_Turno","=",$datos["Turno"]);
		$oLista->setRestrict("Dia","=",$dias[date("w",strtotime($datos["Fecha"]))]);
		$horas=$oLista->getList();
		
		$diferencia=RestarHoras($datos["he1"],$horas[0]["Hora_Inicio1"]);
		$dife=$diferencia;
		$diferencia=explode(":",$diferencia);
		
		$sig=1;
		if(strpos($diferencia[0],"-")!==false){
			$sig=-1;
			$diferencia[0]=str_replace("-", "", $diferencia[0]);
		}
		
		$diff=(($diferencia[0]*60*60)+($diferencia[1]*60)+($diferencia[2]))*$sig;
		$tol_ent=($turno["Tolerancia_Entrada"]*60);
		
		if($diff>=$tol_ent){
			$oItem3 = new complex('Funcionario','Identificacion_Funcionario',$datos["Funcionario"]);
			$funcionario=$oItem3->getData();
			unset($oItem3);	
			$oItem4 = new complex('Llegada_Tarde','Id_Llegada_Tarde');
			$oItem4->Identificacion_Funcionario=$datos["Funcionario"];
			$oItem4->Fecha=$datos["Fecha"];
			$oItem4->Tiempo=$diff;
			$oItem4->Id_Grupo=$funcionario["Id_Grupo"];
			$oItem4->Id_Dependencia=$funcionario["Id_Dependencia"];
			$oItem4->Entrada_Turno=$horas[0]["Hora_Inicio1"];
			$oItem4->Entrada_Real=$datos["he1"];
			$oItem4->save();
			unset($oItem4);
			$oItem5 = new complex('Alerta','Id_Alerta');
			$oItem5->Identificacion_Funcionario=$datos["Funcionario"];
			$oItem5->Fecha=$datos["Fecha"]." ".$datos["he1"];
			$oItem5->Tiempo=$diff;
			$oItem5->Tipo="Llegada Tarde";
			$oItem5->Detalles=$funcionario["Nombres"]." ".$funcionario["Apellidos"]." ha llegado Tarde";
			$oItem5->save();
			unset($oItem5);
		}
	}
	if($datos["he2_or"]!=$datos["he2"]){
		$oLista= new lista("Llegada_Tarde");
		$oLista->setRestrict("Identificacion_Funcionario","=",$datos["Funcionario"]);
		$oLista->setRestrict("Fecha","LIKE",$datos["Fecha"]);
		$oLista->setRestrict("Entrada_Real","=",$datos["he2_or"]);
		$tardes=$oLista->getList();
		
		
		$oLista= new lista("Alerta");
		$oLista->setRestrict("Identificacion_Funcionario","=",$datos["Funcionario"]);
		$oLista->setRestrict("Fecha","LIKE",$datos["Fecha"]." ".$datos["he2_or"]);
		
		$alertas=$oLista->getList();
			
		foreach($tardes as $tarde){
			$oItem6 = new complex('Llegada_Tarde','Id_Llegada_Tarde',$tarde["Id_Llegada_Tarde"]);
			$oItem6->delete();
			unset($oItem6);
		}
		foreach($alertas as $alerta){
			$oItem6 = new complex('Alerta','Id_Alerta',$alerta["Id_Alerta"]);
			$oItem6->delete();
			unset($oItem6);
		}
		$oItem2 = new complex('Turno','Id_Turno',$datos["Turno"]);
		$turno=$oItem2->getData();
		unset($oItem2);	
		
		$oLista= new lista('Hora_Turno');
		$oLista->setRestrict("Id_Turno","=",$datos["Turno"]);
		$oLista->setRestrict("Dia","=",$dias[date("w",strtotime($datos["Fecha"]))]);
		$horas=$oLista->getList();
		
		$diferencia=RestarHoras($datos["he2"],$horas[0]["Hora_Inicio2"]);
		$dife=$diferencia;
		$diferencia=explode(":",$diferencia);
		
		$sig=1;
		if(strpos($diferencia[0],"-")!==false){
			$sig=-1;
			$diferencia[0]=str_replace("-", "", $diferencia[0]);
		}
		
		$diff=(($diferencia[0]*60*60)+($diferencia[1]*60)+($diferencia[2]))*$sig;
		$tol_ent=($turno["Tolerancia_Entrada"]*60);
		
		if($diff>=$tol_ent){
			$oItem3 = new complex('Funcionario','Identificacion_Funcionario',$datos["Funcionario"]);
			$funcionario=$oItem3->getData();
			unset($oItem3);	
			$oItem4 = new complex('Llegada_Tarde','Id_Llegada_Tarde');
			$oItem4->Identificacion_Funcionario=$datos["Funcionario"];
			$oItem4->Fecha=$datos["Fecha"];
			$oItem4->Tiempo=$diff;
			$oItem4->Id_Grupo=$funcionario["Id_Grupo"];
			$oItem4->Id_Dependencia=$funcionario["Id_Dependencia"];
			$oItem4->Entrada_Turno=$horas[0]["Hora_Inicio2"];
			$oItem4->Entrada_Real=$datos["he2"];
			$oItem4->save();
			unset($oItem4);
			$oItem5 = new complex('Alerta','Id_Alerta');
			$oItem5->Identificacion_Funcionario=$datos["Funcionario"];
			$oItem5->Fecha=$datos["Fecha"]." ".$datos["he2"];
			$oItem5->Tiempo=$diff;
			$oItem5->Tipo="Llegada Tarde";
			$oItem5->Detalles=$funcionario["Nombres"]." ".$funcionario["Apellidos"]." ha llegado Tarde";
			$oItem5->save();
			unset($oItem5);
		}
	}
	
	$oItem->Hora_Entrada1=$datos["he1"];
	$oItem->Hora_Entrada2=$datos["he2"];
	$oItem->Hora_Salida1=$datos["hs1"];
	$oItem->Hora_Salida2=$datos["hs2"];
}else{
	$oItem->Hora_Entrada=$datos["he1"];
	$oItem->Fecha_Salida=$datos["Salida"];
	$oItem->Hora_Salida=$datos["hs1"];
}

if(isset($datos["Proceso"])&&$datos["Proceso"]!=""){
	$oItem->Proceso=$datos["Proceso"];
}		
$oItem->save();
unset($oItem);
			



echo "Horario Actualizado Correctamente";

?>