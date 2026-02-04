<?php

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');


$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$horas = ( isset( $_REQUEST['hora'] ) ? $_REQUEST['hora'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );


$oItem = new complex('Turno','Id_Turno',$id);
foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
$id_turno=$oItem->getId();
unset($oItem);
if(is_array($horas)){
	foreach($horas as $hora){
		$hora["Id_Turno"]=$id_turno;
		if($hora["Hora_Inicio1"]!=""){
			if(isset($hora["Id_Hora_Turno"])){
				$oItem = new complex('Hora_Turno','Id_Hora_Turno',$hora['Id_Hora_Turno']);	
			}else{
				$oItem = new complex('Hora_Turno','Id_Hora_Turno');	
			}
			
			foreach($hora as $index=>$value) {
			    $oItem->$index=$value;
			}
			$oItem->save();
			unset($oItem);
		}
	}	
}


echo "Turno Guardado Exitosamente";

?>