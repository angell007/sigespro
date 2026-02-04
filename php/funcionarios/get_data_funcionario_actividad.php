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
			F.Imagen,
			F.Identificacion_Funcionario AS Id_Funcionario,
			CONCAT_WS(" ", F.Nombres, F.Apellidos) AS Nombre,
			D.Nombre AS Dependencia,
			G.Nombre AS Grupo,
			C.Nombre AS Cargo
		FROM Funcionario F
		INNER JOIN Dependencia D ON F.Id_Dependencia = D.Id_Dependencia
		INNER JOIN Cargo C ON F.Id_Cargo = C.Id_Cargo
		INNER JOIN Grupo G ON F.Id_Grupo = G.Id_Grupo
		'.$condicion;

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $funcionario = $queryObj->Consultar('simple');

	echo json_encode($funcionario);

	function SetCondiciones($req){
		$condicion = '';

        if (isset($req['id_funcionario']) && $req['id_funcionario']) {
            if ($condicion != "") {
                $condicion .= " AND F.Identificacion_Funcionario = ".$req['id_funcionario'];
            } else {
                $condicion .= " WHERE F.Identificacion_Funcionario = ".$req['id_funcionario'];
            }
        }

        return $condicion;
	}
?>