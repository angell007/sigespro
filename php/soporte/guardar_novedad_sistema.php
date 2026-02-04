<?php
	header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    date_default_timezone_set('America/Bogota');
	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');

	$datos = ( isset( $_REQUEST['Datos'] ) ? $_REQUEST['Datos'] : '' );
	$datos = (array) json_decode($datos);

	$datos['Estado_Novedad'] = 'Pendiente';

	$oItem = new complex("Novedad_Sistema","Id_Novedad_Sistema");
	foreach($datos as $index=>$value) {
	    $oItem->$index=$value;	    
	}

	$oItem->save();
	unset($oItem);

	$result['tipo'] = 'success';
	$result['msg'] = 'Reporte guardado exitosamente!';

	echo json_encode($result);
?>