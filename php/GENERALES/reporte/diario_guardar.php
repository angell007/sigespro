<?php

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');


$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

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