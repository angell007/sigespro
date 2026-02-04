<?php

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');


$reportes = ( isset( $_REQUEST['reporte'] ) ? $_REQUEST['reporte'] : '' );

if(is_array($reportes)){
	
	foreach($reportes as $reporte){
		if($reporte["Id_Turno"]!="0"){
			if(isset($reporte["Id_Reporte"])){
			$oItem = new complex('Reporte','Id_Reporte',$reporte["Id_Reporte"]);	
			}else{
			$oItem = new complex('Reporte','Id_Reporte');
			}
			foreach($reporte as $index=>$value) {
			    $oItem->$index=$value;
			}
			$oItem->save();
			unset($oItem);
		}
	}	
}


echo "Horas Validadas Exitosamente";

?>