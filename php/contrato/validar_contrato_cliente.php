<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
// include_once('../../class/class.utility.php');


$http_response = new HttpResponse();
$queryObj = new QueryBaseDatos();
// $util = new Utility();


$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );


if ($id) {

    try {

        $query = getContratoCLiente();
        
        $queryObj->SetQuery($query);

       $clientecontrato = $queryObj->ExecuteQuery('simple');

       echo json_encode($clientecontrato);

    } catch (\Throwable $th) {
        throw $th;
    }
}


function getContratoCLiente(){
    global $id;

	$query="SELECT * FROM Contrato WHERE Id_Contrato = $id";

	return $query;
}



