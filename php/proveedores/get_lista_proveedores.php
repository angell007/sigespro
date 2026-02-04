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

	$condicion = SetCondiciones();

	$query = '
		SELECT 
			Id_Proveedor,
			Nombre AS Proveedor,
            IFNULL(Pais, "No Registrado") AS Pais,
            false as Seleccionado
		FROM Proveedor
		'.$condicion;

	$query_count = '
		SELECT 
            COUNT(Id_Proveedor) AS Total
        FROM Proveedor
		'.$condicion;    

	$paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj = new QueryBaseDatos($query);
    $proveedores = $queryObj->Consultar('Multiple', true, $paginationData);

	echo json_encode($proveedores);

	function SetCondiciones(){
        $req = $_REQUEST;
		$condicion = ' WHERE Estado = "Activo" ';

        if (isset($req['nombre']) && $req['nombre']) {
            if ($condicion != "") {
                $condicion .= " AND (CONCAT_WS(' ', Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido) LIKE '%".$req['nombre']."%' OR Nombre LIKE '%$req[nombre]%' OR Razon_Social LIKE '%$req[nombre]%')";
            } else {
                $condicion .= " WHERE (CONCAT_WS(' ', Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido) LIKE '%".$req['nombre']."%' OR Nombre LIKE '%$req[nombre]%' OR Razon_Social LIKE '%$req[nombre]%')";
            }
        }

        if (isset($req['id_tercero']) && $req['id_tercero']) {
            if ($condicion != "") {
                $condicion .= " AND Id_Proveedor LIKE ".$req['id_tercero'];
            } else {
                $condicion .= " WHERE Id_Proveedor LIKE ".$req['id_tercero'];
            }
        }

        if (isset($req['pais']) && $req['pais']) {
            if ($condicion != "") {
                $condicion .= " AND Pais LIKE '".$req['pais']."'";
            } else {
                $condicion .= " WHERE Pais LIKE '".$req['pais']."'";
            }
        }

        return $condicion;
	}
?>