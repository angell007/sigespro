<?php
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

/*
$oLista = new lista("Novedad");
$llegadas_tarde= $oLista->getlist();



foreach($llegadas_tarde as $lleg){
	
	$oItem = new complex('Funcionario','Identificacion_Funcionario',$lleg["Identificacion_Funcionario"]);
	$func=$oItem->getData();
	unset($oItem);
	
	$oItem = new complex('Novedad','Id_Novedad',$lleg["Id_Novedad"]);
	$oItem->Id_Grupo=$func["Id_Grupo"];
	$oItem->Id_Dependencia=$func["Id_Dependencia"];
	$oItem->save();
	unset($oItem);
	
	
}
*/

$oLista = new lista("Funcionario");
//$oLista->setRestrict("Id_Turno","=",);
$oLista->setRestrict("Identificacion_Funcionario","!=",1127943747);
$funcionarios= $oLista->getlist();
unset($oLista);

foreach($funcionarios as $func){
	echo $func["Nombres"]."<br>";
	$oItem = new complex('Diario_Fijo','Id_Diario_Fijo');
	$oItem->Identificacion_Funcionario=$func["Identificacion_Funcionario"];
	$oItem->Fecha="2018-03-10";
	$oItem->Id_Turno=$func["Id_Turno"];
	$oItem->Hora_Entrada1="08:00:00";
//	$oItem->Hora_Salida1="11:00:00";
	$oItem->Id_Dependencia=$func["Id_Dependencia"];
//	$oItem->save();
	unset($oItem);	
}


?>