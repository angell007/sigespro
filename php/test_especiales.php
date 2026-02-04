<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../config/start.inc.php');
	include_once('../class/class.querybasedatos.php');
	include_once('../class/class.http_response.php');

	$queryObj = new QueryBaseDatos();
	$response = array();
	$http_response = new HttpResponse();
	//var_dump($_REQUEST);

	$modelo = ( isset( $_REQUEST['char'] ) ? $_REQUEST['char'] : '' );
	$modelo = utf8_decode($modelo);

	$oItem = new complex('Prueba_Letras_Especiales','Id_Prueba_Letras_Especiales');
	$oItem->Texto = $modelo;
	$oItem->save();
	unset($oItem);

	$query = 'SELECT * FROM Prueba_Letras_Especiales ORDER BY Id_Prueba_Letras_Especiales DESC LIMIT 1';
	$queryObj->SetQuery($query);
	$result = $queryObj->ExecuteQuery('multiple');

	if (count($result) > 0) {
		foreach ($result as $key => $t) {
			$result[$key]['Texto'] = utf8_decode($t['Texto']);
		}
	}
	
	echo json_encode($result[0]['Texto']);
?>