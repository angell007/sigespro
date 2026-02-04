<?php
	header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.lista.php');
    include_once('../../class/class.complex.php');
    include_once('../../class/class.consulta.php');

	$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
	$resultado = [];

	if ($id) {
		
		$oItem = new complex('Centro_Costo','Id_Centro_Costo', $id);
		$oItem->delete();
		unset($oItem);

		$resultado['titulo'] = "Exito!";
		$resultado['mensaje'] = "Se ha eliminado correctamente el centro de costo";
		$resultado['tipo'] = "success";
		
	} else {
		$resultado['titulo'] = "Error!";
		$resultado['mensaje'] = "Ha ocurrido un error inesperado. Verifique si su conexión a internet es estable.";
		$resultado['tipo'] = "error";
	}

	echo json_encode($resultado);

?>