<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	//require_once('../../config/start.inc.php');
	include_once('../../../class/class.querybasedatos.php');
	include_once('../../../class/class.paginacion.php');
	include_once('../../../class/class.http_response.php');
	include_once('../../../class/class.utility.php');

	$http_response = new HttpResponse();
	$util = new Utility();

	// var_dump($_REQUEST);
	// exit;
	$condicion = SetCondiciones($_REQUEST);

	$fecha = date('Y-m-d'); 

    $query = '
        SELECT 
            F.Imagen,
            OCI.Codigo,
            OCI.Fecha_Registro,
            OCI.Estado,
            CONCAT_WS(" ", P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) AS Nombre_Proveedor
        FROM Orden_Compra_Internacional OCI
        INNER JOIN Proveedor P ON OCI.Id_Proveedor = P.Id_Proveedor
        INNER JOIN Funcionario F ON OCI.Identificacion_Funcionario = F.Identificacion_Funcionario
        '.$condicion;

    $query_paginacion = '
        SELECT 
            COUNT(OCI.Id_Orden_Compra_Internacional) AS Total
        FROM Orden_Compra_Internacional OCI
        INNER JOIN Proveedor P ON OCI.Id_Proveedor = P.Id_Proveedor
        INNER JOIN Funcionario F ON OCI.Identificacion_Funcionario = F.Identificacion_Funcionario
        '.$condicion;
	
    $paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj = new QueryBaseDatos($query)
    $ordenes_internacionales = $queryObj->Consultar('Multiple', true, $paginationData);

    unset($http_response);
    unset($queryObj);

	echo json_encode($ordenes_internacionales);

	function SetCondiciones($req){
		global $util;

        $condicion = ''; 

        if (isset($req['fechas']) && $req['fechas']) {
            $fechas_separadas = $util->SepararFechas($req['fechas']);

            if ($condicion != "") {
                $condicion .= " AND OCI.Fecha_Registro >= '".$fechas[0]."' AND Fecha_Registro <= '".$fechas[1]."'";
            } else {
                $condicion .= " WHERE OCI.Fecha_Registro >= '".$fechas[0]."' AND Fecha_Registro <= '".$fechas[1]."'";
            }
        }

        if (isset($req['codigo']) && $req['codigo']) {
            if ($condicion != "") {
                $condicion .= " AND OCI.Codigo LIKE '%".$req['codigo']."%'";
            } else {
                $condicion .= " WHERE OCI.Codigo LIKE '%".$req['codigo']."%'";
            }
        }

        if (isset($req['proveedor']) && $req['proveedor']) {
            if ($condicion != "") {
                $condicion .= " AND CONCAT_WS(" ", P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) LIKE '%".$req['proveedor']."%'";
            } else {
                $condicion .= " WHERE CONCAT_WS(" ", P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) LIKE '%".$req['proveedor']."%'";
            }
        }

        if (isset($req['estado']) && count($req['estado']) > 0) {
            if ($condicion != "") {
                $condicion .= " AND OCI.Estado = '".$req['estado']."'";
            } else {
                $condicion .= " WHERE OCI.Estado = '".$req['estado']."'";
            }
        }

        return $condicion;
	}


?>