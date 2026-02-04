<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../../config/start.inc.php');
	include_once('../../../class/class.consulta.php');

	
	$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
	$resultado = [];

	$query = 'SELECT f.Nombres, ac.Actividad_Recursos_Humanos as Actividad
		FROM funcionario f
		INNER JOIN funcionario_actividad fa ON f.Identificacion_Funcionario = fa.Id_Funcionario_Asignado
		INNER JOIN actividad_recursos_humanos ac ON fa.Id_Actividad_Recursos = ac.Id_Actividad_Recursos_Humanos
		WHERE ac.Id_Actividad_Recursos_Humanos = 149';


	if ($id) {
		$query = "SELECT *
				  FROM Actividad_Recursos_Humanos WHERE Id_Actividad_Recursos_Humanos  = $id";
		$oCon = new consulta();
		$oCon->setQuery($query);
		$resultado = $oCon->getData();
		unset($oCon);

		$resultado['Fecha_Inicio'] = substr(date('c', strtotime($resultado['Fecha_Inicio'])),0,19);
		$resultado['Fecha_Fin'] = substr(date('c', strtotime($resultado['Fecha_Fin'])),0,19);

		
	}

	echo json_encode($resultado);
	
?>