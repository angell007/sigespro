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

	$pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
	$tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

	$condicion = SetCondiciones($_REQUEST);
	//$having = SetHaving($_REQUEST);

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
		INNER JOIN Grupo G ON F.Id_Grupo = G.Id_Grupo'
		.$condicion;

	$query_count = '
		SELECT
			COUNT(F.Identificacion_Funcionario) AS Total
		FROM Funcionario F
		INNER JOIN Dependencia D ON F.Id_Dependencia = D.Id_Dependencia
		INNER JOIN Cargo C ON F.Id_Cargo = C.Id_Cargo
		INNER JOIN Grupo G ON F.Id_Grupo = G.Id_Grupo'
		.$condicion;   

	$paginationData = new PaginacionData($tam, $query_count, $pag);

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $funcionarios_ruta = $queryObj->Consultar('Multiple', true, $paginationData);

	echo json_encode($funcionarios_ruta);

	function SetCondiciones($req){
		$condicion = ' where F.Id_Cargo in (41,42)';

        /*if (isset($req['nombre']) && $req['nombre'] != '') {
            if ($condicion != "") {
                $condicion .= " AND CONCAT_WS(' ', F.Nombres, F.Apellidos) LIKE '%".$req['nombre']."%'";
            } else {
                $condicion .= " WHERE CONCAT_WS(' ', F.Nombres, F.Apellidos) LIKE '%".$req['nombre']."%'";
            }
        }

        if (isset($req['cargo']) && $req['cargo'] != '') {
            if ($condicion != "") {
                $condicion .= " AND F.Id_Cargo = ".$req['cargo'];
            } else {
                $condicion .= " WHERE F.Id_Cargo = ".$req['cargo'];
            }
        }

        if (isset($req['dependencia']) && $req['dependencia'] != '') {
            if ($condicion != "") {
                $condicion .= " AND F.Id_Dependencia = ".$req['dependencia'];
            } else {
                $condicion .= " WHERE F.Id_Dependencia = ".$req['dependencia'];
            }
        }

        if (isset($req['grupo']) && $req['grupo'] != '') {
            if ($condicion != "") {
                $condicion .= " AND F.Id_Grupo = ".$req['grupo'];
            } else {
                $condicion .= " WHERE F.Id_Grupo = ".$req['grupo'];
            }
        }*/

        return $condicion;
	}

	function SetHaving($req){
		$having = '';

        if (isset($req['actividades']) && $req['actividades'] != '') {
            $having = " HAVING Actividades = ".$req['actividades'];
        }else{
        	$having = " HAVING Actividades > 0";
        }

        return $having;
	}
?>