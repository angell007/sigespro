<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../../config/start.inc.php');
	require_once('./helper_actividad/funciones_actividad.php');
	include_once('../../../class/class.complex.php');
	include_once('../../../class/class.http_response.php');
	include_once('../../../class/class.consulta.php');

	$http_response = new HttpResponse();
	$response = array();

	$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

	$query = 'SELECT fa.Id_Funcionario_Actividad, fa.Id_Actividad_Recursos, f.Nombres, ac.Actividad_Recursos_Humanos as Actividad
                FROM Funcionario f
                INNER JOIN Funcionario_Actividad fa ON f.Identificacion_Funcionario = fa.Id_Funcionario_Asignado
                INNER JOIN Actividad_Recursos_Humanos ac ON fa.Id_Actividad_Recursos = ac.Id_Actividad_Recursos_Humanos
                WHERE ac.Id_Actividad_Recursos_Humanos = '."$id".'';
    $consult = new consulta();
    $consult->setQuery($query);
    $consult->setTipo('Multiple');
    $total = $consult->getData();

	echo json_encode($total);