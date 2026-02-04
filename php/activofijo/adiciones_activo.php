<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.consulta.php');

	$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
	$adiciones = [];

	if ($id) {
		$query = "SELECT Id_Adicion_Activo_Fijo, Id_Activo_Fijo, Fecha, Nombre, Concepto, Base, Iva, Costo_NIIF AS Costo FROM Adicion_Activo_Fijo WHERE Id_Activo_Fijo = $id";

		$oCon = new consulta();
		$oCon->setQuery($query);
		$oCon->setTipo('Multiple');
		$adiciones = $oCon->getData();
		unset($oCon);
	}

	echo json_encode($adiciones);
?>