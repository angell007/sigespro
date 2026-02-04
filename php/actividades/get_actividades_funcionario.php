<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();
	$condicion = SetCondiciones($_REQUEST);
 
	$fecha = date('Y-m-d');
 
	$query = '
		SELECT 
			A.*,
			C.Latitud,
			C.Longitud
		FROM Actividad A
		INNER JOIN Cliente C ON A.Id_Cliente = C.Id_Cliente
		'.$condicion.' 
		ORDER BY Fecha_Inicio DESC';

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $actividades_funcionario = $queryObj->Consultar('Multiple');

	echo json_encode($actividades_funcionario);

	function SetCondiciones($req){
		$condicion = '';

        if (isset($req['id_funcionario']) && $req['id_funcionario']) {
            if ($condicion != "") {
                $condicion .= " AND Identificacion_Funcionario = ".$req['id_funcionario'];
            } else {
                $condicion .= " WHERE Identificacion_Funcionario = ".$req['id_funcionario'];
            }
        }

        if (isset($req['desde']) && $req['desde']) {
            if ($condicion != "") {
                $condicion .= " AND Fecha_Inicio >= '".$req['desde']."' AND Fecha_Fin <= '".$req['hasta']."'";
            } else {
                $condicion .= " WHERE Fecha_Inicio >= '".$req['desde']."' AND Fecha_Fin <= '".$req['hasta']."'";
            }
        }

        return $condicion;
	}
?>