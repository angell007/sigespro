<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.complex.php');

	$id			 = (isset($_REQUEST['id']) ? $_REQUEST['id'] : false);
	$Id_Contrato = (isset($_REQUEST['Id_Contrato']) ? $_REQUEST['Id_Contrato'] : false);
	$response 	 = [];

	if ($id) {
		$oItem = new complex('Funcionario','Identificacion_Funcionario',$id);
		$oItem->Preliquidado = 'SI';
		$oItem->Fecha_Preliquidado = date("Y-m-d");
		$oItem->save();
		unset($oItem);  

		$oItem = new complex('Contrato_Funcionario','Id_Contrato_Funcionario ',$Id_Contrato);
		$oItem->Estado = 'Preliquidado';
		$oItem->Fecha_Preliquidado = date("Y-m-d H:i:s");
		$oItem->save();
		unset($oItem);  

		$response['titulo'] = "Exito!";
		$response['mensaje'] = "Se ha preliquidado el funcionario correctamente.";
		$response['tipo'] = "success";
	} else {
		$response['titulo'] = "Oops!";
		$response['mensaje'] = "Ha ocurrido un error en el proceso. Por favor intentalo de nuevo.";
		$response['tipo'] = "error";
	}

	echo json_encode($response);
?>
