<?php
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$oLista = new lista("Llegada_Tarde");
$llegadas = $oLista->getList();
unset($oLista);

$i=0;
foreach($llegadas as $llegada){ $i++;
	$oItem = new complex('Funcionario','Identificacion_Funcionario',$llegada["Identificacion_Funcionario"]);
	$funcionario=$oItem->getData();
	unset($oItem);
	
	
	$oItem = new complex('Alerta','Id_Alerta');
	$oItem->Identificacion_Funcionario=$llegada["Identificacion_Funcionario"];
	$oItem->Fecha=$llegada["Fecha"]." ".$llegada["Entrada_Real"];
	$oItem->Tiempo=$llegada["Tiempo"];
	$oItem->Tipo="Llegada Tarde";
	$oItem->Detalles=$funcionario["Nombres"]." ".$funcionario["Apellidos"]." ha llegado Tarde";
	//$oItem->save();
	echo $i." - ".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."<br>";
	unset($oItem);
}






?>