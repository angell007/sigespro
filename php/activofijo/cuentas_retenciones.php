<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.http_response.php');

	$match = ( isset( $_REQUEST['coincidencia'] ) ? $_REQUEST['coincidencia'] : '' );

	$http_response = new HttpResponse();

	$query = '
		SELECT
			T.*
		FROM (SELECT 
                P.Id_Plan_Cuentas AS Id,
                Codigo,
				CONCAT_WS(" ", P.Codigo," - ",P.Nombre) AS Nombre
			FROM Retencion R 
			INNER JOIN  Plan_Cuentas P ON R.Id_Plan_Cuenta=P.Id_Plan_Cuentas
				) T
		WHERE
			T.Codigo LIKE "%'.$match.'%" OR T.Nombre LIKE "%'.$match.'%"'; 


    $queryObj = new QueryBaseDatos($query);
    $matches = $queryObj->ExecuteQuery('Multiple');

	echo json_encode($matches);
?>