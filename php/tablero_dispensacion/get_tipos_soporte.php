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

	$id_tipo_servicio= ( isset( $_REQUEST['id_tipo_servicio'] ) ? $_REQUEST['id_tipo_servicio'] : '' );
	$id_auditoria= ( isset( $_REQUEST['id_auditoria'] ) ? $_REQUEST['id_auditoria'] : '' );
	$accion= ( isset( $_REQUEST['accion'] ) ? $_REQUEST['accion'] : '' );

	$condicion = '';
	$condicion = SetCondiciones($id_tipo_servicio, $accion);
	$soportes= GetTiposSoporte();

	// if (count($soportes['query_result']) > 0) {
	// 	foreach ($soportes['query_result'] as $key => $value) {
	// 		$soportes['query_result'][$key]['Paginas'] = $id_auditoria == '' ? '' : GetPaginasTipoSoporte($value['Id_Tipo_Soporte'], $id_auditoria);
	// 	}
	// }

	echo json_encode($soportes);

	function SetCondiciones($idTipoServicio, $accion){

		$condicion=" WHERE Id_Tipo_Servicio=$idTipoServicio "; 
		
		if (strtolower($accion) == "pre-auditoria") {
			$condicion .= "AND Pre_Auditoria = 'Si' ";
		}elseif (strtolower($accion) == "auditoria"){
			$condicion .= "AND Auditoria = 'Si' ";
		}elseif (strtolower($accion) == "ambos"){
			$condicion .= "AND Pre_Auditoria = 'Si' ";
		}

		return $condicion; 
	}

	function GetTiposSoporte()
	{
		global $condicion, $queryObj;

		$query='SELECT 
					Id_Tipo_Soporte,
					Id_Tipo_Servicio,
					Tipo_Soporte,
					Comentario,
					"" as Paginas
			    FROM Tipo_Soporte'
			    .$condicion;
		$queryObj->SetQuery($query);
		$soportes = $queryObj->Consultar("multiple");

		return $soportes;
	}

	function GetPaginasTipoSoporte($idTipoSoporte, $idAuditoria){
		global $queryObj;

		$query='
			SELECT 
				Paginas
			FROM Soporte_Auditoria
			WHERE
				Id_Tipo_Soporte = '.$idTipoSoporte
				.' AND Id_Auditoria = '.$idAuditoria;

		$queryObj->SetQuery($query);
		$paginas = $queryObj->ExecuteQuery("simple");

		return $paginas === false ? '' : $paginas['Paginas'];

	}




?>