<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.http_response.php');

	
	$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
    $queryObj = new QueryBaseDatos();

    $condicion = SetCondiciones($id);

    $query = 'SELECT Id_Tipo_Soporte,Tipo_Soporte  FROM Tipo_Soporte     
    '.$condicion.' ORDER BY Tipo_Soporte ASC ';


//Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
$queryObj->SetQuery($query);
$soportes = $queryObj->ExecuteQuery('Multiple');

echo json_encode($soportes);

function SetCondiciones($id){
    global $queryObj;
    $condicion = '';

    $query="SELECT Id_Tipo_Servicio FROM Auditoria WHERE Id_Auditoria=$id ";

    $queryObj->SetQuery($query);
    $id_tipo_servicio = $queryObj->ExecuteQuery('simple');
   $condicion.=" WHERE Id_Tipo_Servicio=$id_tipo_servicio[Id_Tipo_Servicio]";

    return $condicion;
}
?>