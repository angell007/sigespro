<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	// include_once('../../class/class.http_response.php');

	// $http_response = new HttpResponse();

	$pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
	$tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

	$condicion = SetCondiciones($_REQUEST);

	$fecha = date('Y-m-d');

	$query = 'SELECT 
          R.*,
          CONCAT_WS(" ", F.Nombres, F.Apellidos) AS Nombre_Funcionario,
          C.Nombre AS Nombre_Cliente,
          CASE
            WHEN R.Id_Departamento = 0 THEN "Todos"
            ELSE D.Nombre
          END AS Nombre_Departamento,
          RE.Nombre AS Nombre_Regimen,
          CASE
            WHEN R.Id_Tipo_Servicio <> 0 THEN T.Nombre
            ELSE "TODOS"
          END AS ServicioTipoServicio,
          IFNULL(RF_COUNT.Facturas_Radicadas, 0) AS Facturas_Radicadas
        
        FROM Radicado R
        LEFT JOIN (
            SELECT Id_Radicado, COUNT(*) AS Facturas_Radicadas
            FROM Radicado_Factura
            GROUP BY Id_Radicado
        ) AS RF_COUNT ON RF_COUNT.Id_Radicado = R.Id_Radicado
        
        INNER JOIN Funcionario F ON R.Id_Funcionario = F.Identificacion_Funcionario
        INNER JOIN Cliente C ON R.Id_Cliente = C.Id_Cliente
        INNER JOIN Regimen RE ON R.Id_Regimen = RE.Id_Regimen
        LEFT JOIN Departamento D ON R.Id_Departamento = D.Id_Departamento
        
        LEFT JOIN (
          SELECT T.Id_Tipo_Servicio, CONCAT(S.Nombre, " - ", T.Nombre) AS Nombre
          FROM Tipo_Servicio T
          INNER JOIN Servicio S ON T.Id_Servicio = S.Id_Servicio
        ) T ON T.Id_Tipo_Servicio = R.Id_Tipo_Servicio
        
        '.$condicion.'
        
        ORDER BY R.Codigo DESC';
        
        //echo $query; exit;
	$query_count = '
		SELECT 
            COUNT(*) AS Total
        FROM  Radicado R
        LEFT JOIN Radicado_Factura RF ON R.Id_Radicado = RF.Id_Radicado
        LEFT JOIN Factura FA ON FA.Id_Factura =RF.Id_Factura
        INNER JOIN Funcionario F ON R.Id_Funcionario = F.Identificacion_Funcionario
        INNER JOIN Cliente C ON R.Id_Cliente = C.Id_Cliente
        INNER JOIN Regimen RE ON R.Id_Regimen = RE.Id_Regimen
        LEFT JOIN Departamento D ON R.Id_Departamento = D.Id_Departamento
       '.$condicion.'
       GROUP BY R.Id_Radicado
       ';
	$paginationData = new PaginacionData($tam, $query_count, $pag);

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $radicaciones = $queryObj->Consultar('Multiple', true, $paginationData);

	echo json_encode($radicaciones);

	function SetCondiciones($req){
		$condicion = '';

        if (isset($req['consecutivo']) && $req['consecutivo']) {
            if ($condicion != "") {
                $condicion .= " AND R.Consecutivo = ".$req['consecutivo'];
            } else {
                $condicion .= " WHERE R.Consecutivo = ".$req['consecutivo'];
            }
        }

        if (isset($req['codigo']) && $req['codigo']) {
            if ($condicion != "") {
                $condicion .= " AND R.Codigo LIKE '%".$req['codigo']."%'";
            } else {
                $condicion .= " WHERE R.Codigo LIKE '%".$req['codigo']."%'";
            }
        }

        if (isset($req['num_rad']) && $req['num_rad']) {
            if ($condicion != "") {
                $condicion .= " AND R.Numero_Radicado = ".$req['num_rad'];
            } else {
                $condicion .= " WHERE R.Numero_Radicado = ".$req['num_rad'];
            }
        }

        if (isset($req['fecha_radicacion']) && $req['fecha_radicacion']) {
            if ($condicion != "") {
                $condicion .= " AND R.Fecha_Radicado = '".$req['fecha_radicacion']."'";
            } else {
                $condicion .= " WHERE R.Fecha_Radicado = '".$req['fecha_radicacion']."'";
            }
        }

        if (isset($req['nombre_cliente']) && $req['nombre_cliente']) {
            if ($condicion != "") {
                $condicion .= " AND C.Nombre LIKE '%".$req['nombre_cliente']."%'";
            } else {
                $condicion .= " WHERE C.Nombre LIKE '%".$req['nombre_cliente']."%'";
            }
        }

        if (isset($req['departamento']) && $req['departamento']) {
            if ($condicion != "") {
                $condicion .= " AND R.Id_Departamento = ".$req['departamento'];
            } else {
                $condicion .= " WHERE R.Id_Departamento = ".$req['departamento'];
            }
        }

        if (isset($req['regimen']) && $req['regimen']) {
            if ($condicion != "") {
                $condicion .= " AND R.Id_Regimen = ".$req['regimen'];
            } else {
                $condicion .= " WHERE R.Id_Regimen = ".$req['regimen'];
            }
        }

        if (isset($req['tipo_servicio']) && $req['tipo_servicio'] != '') {
            if ($condicion != "") {
                $condicion .= " AND R.Id_Tipo_Servicio = ".$req['tipo_servicio'];
            } else {
                $condicion .= " WHERE R.Id_Tipo_Servicio = ".$req['tipo_servicio'];
            }
        }

        if (isset($req['estado']) && $req['estado']) {
            if ($condicion != "") {
                $condicion .= " AND R.Estado = '".$req['estado']."'";
            } else {
                $condicion .= " WHERE R.Estado = '".$req['estado']."'";
            }
        }
        
        if (isset($req['factura']) && $req['factura']) {
            if ($condicion != "") {
                $condicion .= " AND FA.Codigo LIKE '".$req['factura']."'";
            } else {
                $condicion .= " WHERE FA.Codigo LIKE '".$req['factura']."'";
            }
        }

        return $condicion;
	}
?>