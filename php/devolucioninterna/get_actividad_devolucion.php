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

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$condicion = SetCondiciones($id);    
$query= GetQuery();
$queryObj->SetQuery($query);
$actividades = $queryObj->ExecuteQuery('Multiple');


echo json_encode($actividades);

function SetCondiciones($id){

  $condicion='WHERE D.Id_Devolucion_Interna='.$id;
  return $condicion; 
}

function GetQuery(){
	global $condicion;


	$query ="SELECT D.Fecha, D.Detalles, D.Estado, F.* FROM Actividad_Devolucion_Interna D INNER JOIN (SELECT Identificacion_Funcionario, CONCAT_WS(' ', Nombres, Apellidos) AS Funcionario, Imagen FROM Funcionario) F ON F.Identificacion_Funcionario = D.Identificacion_Funcionario ".$condicion;

	return $query;

}



?>