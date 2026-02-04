<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.http_response.php');

	$match = ( isset( $_REQUEST['coincidencia'] ) ? $_REQUEST['coincidencia'] : '' );

	$condicion = '';

	if ($match != '') {
		$condicion .= ' WHERE
		T.Nit LIKE "%'.$match.'%" OR T.Nombre_Tercero LIKE "%'.$match.'%"';
	}

	$http_response = new HttpResponse();

	$query = '
		SELECT
			T.*
		FROM (SELECT 
				Identificacion_Funcionario AS Nit,
				Identificacion_Funcionario AS Id,
				CONCAT(Identificacion_Funcionario," - ", CONCAT_WS(" ", Nombres, Apellidos)) AS Nombre_Tercero,
				CONCAT(Identificacion_Funcionario," - ", CONCAT_WS(" ", Nombres, Apellidos)) AS Nombre,
				"Funcionario" as Tipo
			FROM Funcionario
				UNION
			SELECT 
				Id_Cliente AS Nit,
				Id_Cliente AS Id,
				CONCAT(Id_Cliente," - ",Nombre) AS Nombre_Tercero,CONCAT(Id_Cliente," - ",Nombre) AS Nombre, "Cliente" as Tipo
			FROM Cliente
				UNION
			SELECT 
				Id_Proveedor AS Nit,
				Id_Proveedor AS Id,
				CONCAT(Id_Proveedor," - ",IF((Primer_Nombre IS NULL OR Primer_Nombre = ""), Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido))) AS Nombre_Tercero, CONCAT(Id_Proveedor," - ",IF((Primer_Nombre IS NULL OR Primer_Nombre = ""), Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido))) AS Nombre, "Proveedor" as Tipo
			FROM Proveedor
		
			) T ' . $condicion; 


    $queryObj = new QueryBaseDatos($query);
    $matches = $queryObj->ExecuteQuery('Multiple');

	echo json_encode($matches);
?>