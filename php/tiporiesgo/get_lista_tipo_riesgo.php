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

	$fecha = date('Y-m-d');

	$query = '
		SELECT 
			R.Id_Riesgo,
			R.Nombre,
			PC.Nombre AS Nombre_Plan
		FROM Riesgo R
		INNER JOIN Plan_Cuentas PC ON R.Id_Plan_Cuentas = PC.Id_Plan_Cuentas 
		'.$condicion.' 
		ORDER BY R.Nombre ASC';

	$query_count = '
		SELECT 
			COUNT(Id_Riesgo) AS Total
		FROM Riesgo R
		INNER JOIN Plan_Cuentas PC ON R.Id_Plan_Cuentas = PC.Id_Plan_Cuentas
		'.$condicion;    

	$paginationData = new PaginacionData($tam, $query_count, $pag);

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $tipos_contrato = $queryObj->Consultar('Multiple', true, $paginationData);

	echo json_encode($tipos_contrato);

	function SetCondiciones($req){
		$condicion = '';

        if (isset($req['nombre']) && $req['nombre']) {
            if ($condicion != "") {
                $condicion .= " AND R.Nombre LIKE '%".$req['nombre']."%'";
            } else {
                $condicion .= " WHERE R.Nombre LIKE '%".$req['nombre']."%'";
            }
        }

        if (isset($req['nombre_plan']) && $req['nombre_plan']) {
            if ($condicion != "") {
                $condicion .= " AND PC.Nombre LIKE '%".$req['nombre_plan']."%'";
            } else {
                $condicion .= " WHERE PC.Nombre LIKE '%".$req['nombre_plan']."%'";
            }
        }

        return $condicion;
	}
?>