<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.consulta.php');

	
	$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
	$resultado = [];

	if ($id) {
		$query = "SELECT Id_Actividad, Actividad, Fecha_Inicio, Fecha_Fin, Id_Tipo_Actividad, Detalles FROM Actividad WHERE Id_Actividad = $id";

		$oCon = new consulta();
		$oCon->setQuery($query);
		$resultado = $oCon->getData();
		unset($oCon);

		$resultado['Fecha_Inicio'] = substr(date('c', strtotime($resultado['Fecha_Inicio'])),0,19);
		$resultado['Fecha_Fin'] = substr(date('c', strtotime($resultado['Fecha_Fin'])),0,19);
	}

	echo json_encode($resultado);
	
?>