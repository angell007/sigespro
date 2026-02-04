<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');



$tiposervicio = ( isset( $_REQUEST['tiposervicio'] ) ? $_REQUEST['tiposervicio'] : '' );
$tiposoporte = ( isset( $_REQUEST['tiposoporte'] ) ? $_REQUEST['tiposoporte'] : '' );


$tiposervicio = (array) json_decode($tiposervicio , true);
$tiposoporte = (array) json_decode($tiposoporte , true);

unset($tiposoporte[count($tiposoporte)-1]);

$oItem = new complex('Tipo_Servicio','Id_Tipo_Servicio');
foreach($tiposervicio as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
$id_servicio=$oItem->getId();
unset($oItem);

if(is_array($tiposoporte)){
	foreach($tiposoporte as $tipo){
	  
	    if($tipo["Pre_Auditoria"]!=""){
	        $tipo['Pre_Auditoria'] = "Si";
	    }else{
	        $tipo['Pre_Auditoria'] = "No";
	    }
	    
	    if($tipo["Auditoria"]!=""){
	        $tipo['Auditoria'] = "Si";
	    }else{
	        $tipo['Auditoria'] = "No";
	    }
	    
		if($tipo["Tipo_Soporte"]!=""){
			$tipo["Id_Tipo_Servicio"]=$id_servicio;
			$oItem = new complex('Tipo_Soporte','Id_Tipo_Soporte');
			foreach($tipo as $index=>$value) {
			    $oItem->$index=$value;
			}
			$oItem->save();
			unset($oItem);
		}
	}
}


    $resultado['mensaje'] = "Se ha guardado correctamente  ";
    $resultado['tipo'] = "success";


echo json_encode($resultado);
?>