<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
require_once('../../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */


$datos = isset($_REQUEST['Datos']) ? $_REQUEST['Datos'] : false;

$datos = json_decode($datos, true);

$guardar = true;

//var_dump($datos);
//exit;

$oItem = '';

if(isset($datos['Id_Plan_Cuentas']) && $datos['Id_Plan_Cuentas'] != ''){
    $oItem = new complex("Plan_Cuentas","Id_Plan_Cuentas", $datos['Id_Plan_Cuentas']);    
}else{
    $oItem = new complex("Plan_Cuentas","Id_Plan_Cuentas");
}

foreach ($datos as $index => $value) {
	if ($index == 'Codigo' || $index == 'Codigo_Niif') {
		if(!isset($datos['Id_Plan_Cuentas'])){ // Si no es un proceso de edición
			if (validarPUC($value)) { // Si existe un PUC con el mismo código que no se guarde.
				$guardar = false;
				break;
			}
		}
	}
    $oItem->$index = $value;
}

if ($guardar) {
	
	$oItem->save();
	$id_plan = $oItem->getId();
	unset($oItem);

	if ($id_plan) {
	    $resultado['mensaje'] = "Plan de cuenta registrado satisfactoriamente.";
	    $resultado['tipo'] = "success";
	} else {
	    $resultado['mensaje'] = "Ha ocurrido un error de conexion, comunicarse con el soporte tecnico.";
	    $resultado['tipo'] = "error";
	}
} else {
	$resultado['mensaje'] = "Ya existe un PUC con ese código.";
	$resultado['tipo'] = "error";
}


echo json_encode($resultado);

function validarPUC($cod){
	$query = "SELECT Id_Plan_Cuentas FROM Plan_Cuentas WHERE Codigo = '$cod' OR Codigo_Niif = '$cod'";

	$oCon = new consulta();
	$oCon->setQuery($query);
	$resultado = $oCon->getData();
	unset($oCon);

	return $resultado || false;
}

?>