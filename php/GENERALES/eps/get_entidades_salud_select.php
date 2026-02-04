<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	include_once('../../../class/class.querybasedatos.php');

	$query='
		SELECT 
			Id_Eps AS value,
			Nombre As label 
		FROM Eps 
		WHERE Nit IS NOT NULL
		ORDER BY Nombre'; 

	$queryObj= new QueryBaseDatos($query);
	$eps = $queryObj->Consultar('Multiple');
	unset($queryObj);

	echo json_encode($eps);
?>