<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');

	$query = "SELECT F.*, 
				CONCAT_WS(' ',F.Nombres,F.Apellidos) AS Nombre,
				C.Nombre AS Cargo,
				CF.Id_Contrato_Funcionario,
				CF.Id_Contrato_Funcionario AS Id_Contrato,
				CF.Fecha_Preliquidado
			 FROM Funcionario F 
			 INNER JOIN Contrato_Funcionario CF 
			 		 ON F.Identificacion_Funcionario = CF.Identificacion_Funcionario 
			 INNER JOIN Cargo C 
			         ON C.Id_Cargo = F.Id_Cargo 
		 	 WHERE CF.Estado = 'Preliquidado' ";

	$oCon = new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$funcionarios = $oCon->getData();
	unset($oCon);

	echo json_encode($funcionarios);
?>