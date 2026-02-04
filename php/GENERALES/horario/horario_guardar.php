<?php

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

 

$horarios = ( isset( $_REQUEST['horario'] ) ? $_REQUEST['horario'] : '' );

if(is_array($horarios)){
	$i=0;
	foreach($horarios as $hora){ $i++;
		if($hora["Id_Turno"]!=""){
			if(isset($hora["Id_Horario"])){
			$oItem = new complex('Horario','Id_Horario',$hora["Id_Horario"]);	
			}else{
			$oItem = new complex('Horario','Id_Horario');
			}
			foreach($hora as $index=>$value) {
			    $oItem->$index=$value;
			}
			$oItem->save();
			unset($oItem);
		}
	}	
}


echo "Turnos Asignados Exitosamente";

?>