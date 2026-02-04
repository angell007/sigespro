<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	include_once('../../../class/class.querybasedatos.php');
	include_once('../../../class/class.http_response.php');

	$queryObj = new QueryBaseDatos();
	$http_response = new HttpResponse();
	$response = array();

	$id_parcial = ( isset( $_REQUEST['id_parcial'] ) ? $_REQUEST['id_parcial'] : '' );

	AnularParcial($id_parcial);

	$http_response->SetRespuesta(0, 'Anulacion Exitosa', 'Se ha anulado el parcial exitosamente!');
	$response = $http_response->GetRespuesta();

	echo json_encode($response);

	function AnularParcial($id_parcial){
        global $queryObj;

        $query = '
            UPDATE Nacionalizacion_Parcial
            SET
            	Estado = "Anulado"
            WHERE
                Id_Nacionalizacion_Parcial = '.$id_parcial;

        $queryObj->SetQuery($query);
        $queryObj->QueryUpdate();
    }

?>