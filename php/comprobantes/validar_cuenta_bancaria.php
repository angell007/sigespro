<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');

	$cuenta_validar = ( isset( $_REQUEST['cta_bancaria'] ) ? $_REQUEST['cta_bancaria'] : '' );
	$id_pais = ( isset( $_REQUEST['id_pais'] ) ? $_REQUEST['id_pais'] : '' );

	$query = '
		SELECT
			*
		FROM Destinatario_Cuenta
		WHERE
			Numero_Cuenta = '.$cuenta_validar;

	$oCon= new consulta();
    $oCon->setQuery($query);
    $result = $oCon->getData();
    $result = $result != false ? 1 : 0;
    unset($oCon); 

	echo json_encode($identificador);
?>