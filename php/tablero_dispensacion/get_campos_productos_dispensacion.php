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

	$id_tipo_servicio = ( isset( $_REQUEST['tipo_servicio'] ) ? $_REQUEST['tipo_servicio'] : '' );
	$modulo = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );

	$campos = GetCampos($id_tipo_servicio, $modulo);

	if (count($campos) > 0) {
		
		foreach ($campos as $key => $value) {
			$campos[$key]['Nombre'] = str_replace("_", " ", $value['Nombre']);
		}
	}

	echo json_encode($campos);

	function GetCampos($idTipoServicio, $modulo){
		global $queryObj;

		$query='SELECT 
				*,
				Nombre AS Field_Name,
				"" AS Valor
			FROM Campos_Tipo_Servicio 
			WHERE 
				Id_Tipo_Servicio = '.$idTipoServicio.' AND Estado="Activo" AND Tipo_Campo = "Producto" AND (Modulo = "'.$modulo.'" OR Modulo = "Ambos")';
		$queryObj->SetQuery($query);
		$campos = $queryObj->ExecuteQuery('Multiple');

		return $campos;
	}

?>