<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	//require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.utility.php');

	$http_response = new HttpResponse();
    $queryObj = new QueryBaseDatos();
    $util = new Utility();
    
    $codigo= ( isset( $_REQUEST['codigo'] ) ? $_REQUEST['codigo'] : '' );
    

      
   
    $query= GetQuery();
  

    $queryObj->SetQuery($query);
    $borrador = $queryObj->ExecuteQuery('simple');

    

	echo json_encode($borrador);



    function GetQuery(){
        global $codigo, $queryObj;
        $query="SELECT * FROM Borrador WHERE Codigo like '$codigo'";

        return $query;
    }

 
    


?>