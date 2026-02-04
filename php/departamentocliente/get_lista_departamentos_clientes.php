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
			DC.Id_Departamento_Cliente,
			D.Nombre AS Nombre_Departamento,
			IFNULL(C.Nombre, "Sin Cliente") AS Nombre_Cliente
		FROM Departamento_Cliente DC
		INNER JOIN Departamento D ON DC.Id_Departamento = D.Id_Departamento
		LEFT JOIN Cliente C ON DC.Id_Cliente = C.Id_Cliente 
		'.$condicion.' 
		ORDER BY DC.Id_Departamento_Cliente ASC, C.Nombre ASC';

	$query_count = '
		SELECT 
			COUNT(DC.Id_Departamento_Cliente) AS Total
		FROM Departamento_Cliente DC
		INNER JOIN Departamento D ON DC.Id_Departamento = D.Id_Departamento
		LEFT JOIN Cliente C ON DC.Id_Cliente = C.Id_Cliente 
		'.$condicion;    

	$paginationData = new PaginacionData($tam, $query_count, $pag);

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $departamentos_clientes = $queryObj->Consultar('Multiple', true, $paginationData);

	echo json_encode($departamentos_clientes);

	function SetCondiciones($req){
		$condicion = '';

        if (isset($req['departamento']) && $req['departamento']) {
            if ($condicion != "") {
                $condicion .= " AND D.Nombre LIKE '%".$req['departamento']."%'";
            } else {
                $condicion .= " WHERE D.Nombre LIKE '%".$req['departamento']."%'";
            }
        }

        if (isset($req['cliente']) && $req['cliente']) {
            if ($condicion != "") {
            	if (strtolower($req['cliente']) == 'sin cliente') {
            		$condicion .= " AND DC.Id_Cliente IS NULL";
            	}else{
            		$condicion .= " AND C.Nombre LIKE '%".$req['cliente']."%'";
            	}                
            } else {
            	if (strtolower($req['cliente']) == 'sin cliente') {
            		$condicion .= " WHERE DC.Id_Cliente IS NULL";
            	}else{
            		$condicion .= " WHERE C.Nombre LIKE '%".$req['cliente']."%'";
            	}                 
            }
        }

        return $condicion;
	}
?>