<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');

	$query = 'SELECT C.*, F.Imagen, CONCAT(F.Nombres," ",F.Apellidos) as Funcionario1, 
			  (SELECT  CONCAT(Nombres," ",Apellidos) FROM Funcionario 
			  WHERE Identificacion_Funcionario=C.Funcionario) As Funcionario_Solicitud, CF.Valor
			  FROM Otrosi_Contrato C
			  INNER JOIN Contrato_Funcionario CF ON C.Id_Contrato_Funcionario=CF.Id_Contrato_Funcionario
			  INNER JOIN Funcionario F ON CF.Identificacion_Funcionario=F.Identificacion_Funcionario 
			  WHERE C.Estado="Pendiente"';
    $oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$resultado = $oCon->getData();
	unset($oCon);

	echo json_encode($resultado);
?>