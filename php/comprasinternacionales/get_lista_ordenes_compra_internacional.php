<?php
    header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	//require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.utility.php');

	$http_response = new HttpResponse();
	$util = new Utility();

    $pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
    $tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

	// var_dump($_REQUEST);
	// exit;
	$condicion = SetCondiciones($_REQUEST);

	$fecha = date('Y-m-d'); 

    $query = '
        SELECT 
            OCI.Id_Orden_Compra_Internacional,
            F.Imagen,
            OCI.Codigo,
            OCI.Fecha_Registro,
            OCI.Estado,
            OCI.Flete_Internacional,
            OCI.Seguro_Internacional,
            OCI.Flete_Nacional,
            OCI.Tramite_Sia,
            CONCAT_WS(" ", P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) AS Nombre_Proveedor
        FROM Orden_Compra_Internacional OCI
        INNER JOIN Proveedor P ON OCI.Id_Proveedor = P.Id_Proveedor
        INNER JOIN Funcionario F ON OCI.Identificacion_Funcionario = F.Identificacion_Funcionario
        '.$condicion;

    $query_count = '
        SELECT 
            COUNT(OCI.Id_Orden_Compra_Internacional) AS Total
        FROM Orden_Compra_Internacional OCI
        INNER JOIN Proveedor P ON OCI.Id_Proveedor = P.Id_Proveedor
        INNER JOIN Funcionario F ON OCI.Identificacion_Funcionario = F.Identificacion_Funcionario
        '.$condicion;
	
    $paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj = new QueryBaseDatos($query);
    $ordenes_internacionales = $queryObj->Consultar('Multiple', true, $paginationData);

    if (count($ordenes_internacionales['query_result']) > 0) {
        
        $ordenes_internacionales['query_result'] = SetMostrarAccionCompletarDatos($ordenes_internacionales['query_result']);
    }

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
                $condicion .= " AND CONCAT_WS(' ', P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) LIKE '%".$req['proveedor']."%'";
            } else {
                $condicion .= " WHERE CONCAT_WS(' ', P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) LIKE '%".$req['proveedor']."%'";
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

    function SetMostrarAccionCompletarDatos($ordenes){
        $i = 0;
        foreach ($ordenes as $orden) {
            
            $ordenes[$i]['completar_datos'] = ValidarInformacionCompleta($orden);
            $i++;
        }
        
        return $ordenes;
    }

    function ValidarInformacionCompleta($orden) {
        if ($orden['Estado'] == 'Pendiente') {
            if ($orden['Flete_Internacional'] == '0.00') {
                return  true;
            }else if ($orden['Seguro_Internacional'] == '0.00') {
                return  true;
            }else if ($orden['Flete_Nacional'] == '0.00') {
                return  true;
            }else if ($orden['Tramite_Sia'] == '0.00') {
                return  true;
            }else{        
                return false;
            }
        }else{
          return false;
        }    
    }


?>