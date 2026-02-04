<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.utility.php');

	$http_response = new HttpResponse();
	$queryObj = new QueryBaseDatos();
	$util = new Utility();

    $query=GetQuery();
    $queryObj->SetQuery($query);
    $eps = $queryObj->ExecuteQuery("multiple");

	echo json_encode($eps);

	function GetQuery(){ 

        $query='SELECT C.Id_Cliente as value, 
				IFNULL(C.Nombre, CONCAT_WS(" ",C.Primer_Nombre,C.Segundo_Nombre,C.Primer_Apellido,C.Segundo_Apellido) ) as label
				FROM Cliente C 
        INNER JOIN Contrato CT ON C.Id_Cliente=CT.Id_Cliente 
        INNER JOIN Eps E ON C.Id_Cliente = E.Nit WHERE E.Nit IS NOT NULL';

		return $query;
	}

?>