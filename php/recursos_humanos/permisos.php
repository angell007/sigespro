<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');


	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');

	//$fecha_inicial = date("Y-m")."-01";
	//$fecha_fin = date("Y-m")."-". date("d",(mktime(0,0,0,date("m")+1,1,date("Y"))-1));



	$query = 'SELECT N.*, F.Imagen, UPPER(CONCAT(F.Nombres, " ", F.Apellidos)) as Funcionario
	FROM Novedad N INNER JOIN Funcionario F ON N.Funcionario_Reporta=F.Identificacion_Funcionario
	WHERE Estado="Pendiente" AND Id_Tipo_Novedad=17 ORDER BY N.Id_Novedad DESC ';

    $oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$resultado['Permisos'] = $oCon->getData();
	unset($oCon);

	echo json_encode($resultado);
?>