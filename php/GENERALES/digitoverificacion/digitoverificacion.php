<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../../config/start.inc.php');
	include_once('../../../class/class.lista.php');
	include_once('../../../class/class.complex.php');
	include_once('../../../class/class.consulta.php');

	$nit = isset($_REQUEST['Nit']) ? $_REQUEST['Nit'] : false;
	$nit = limpiarString($nit);

	$totalSum = 0;
	$nrosPrimos = [3,7,13,17,19,23,29,37,41,43,47,53,59,67,71];

	$carLength = strlen($nit);

	$j = 0;
	for ($i=($carLength - 1); $i >= 0; $i--) { 
		$nro = $nit[$i];
		$totalSum += ($nro * $nrosPrimos[$j]);

		$j++;
	}

	$mod = $totalSum % 11;

	$digito_verificacion = $mod > 1 ? (11 - $mod) : $mod;

	echo json_encode([
		"Digito_Verificacion" => $digito_verificacion
	]);


	function limpiarString($nit) {
		
		$car1 = ['.','-'];
		$clean = ['',''];

		return str_replace($car1, $clean, $nit);
	}
?>