<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

	require('../../class/class.querybasedatos.php');
	require('../../class/class.http_response.php');

	$queryObj = new QueryBaseDatos();
	$http_response = new HttpResponse();

	$codigo_ini = ( isset( $_REQUEST['codigo_inicial'] ) ? $_REQUEST['codigo_inicial'] : '' );
	$codigo_fin = ( isset( $_REQUEST['codigo_final'] ) ? $_REQUEST['codigo_final'] : '' );

	$revision = VerificarCodigos($codigo_ini, $codigo_fin);
	$response = array();

	if (!$revision['seguir']) {
		$http_response->SetRespuesta(2, "Alerta", $revision['mensaje']);
		$response = $http_response->GetRespuesta();
	}else{

		$query = '
			SELECT
				Id_Factura
			FROM Factura
			WHERE
				Id_Factura BETWEEN '.$revision['cod_ini'].' AND '.$revision['cod_fin'];

		$queryObj->SetQuery($query);
		$facturas = $queryObj->ExecuteQuery('Multiple');

		if (count($facturas) > 20) {			
			$http_response->SetRespuesta(2, "Alerta", 'El maximo de facturas por impresion es de 20, por favor cambie los codigos!');
			$response = $http_response->GetRespuesta();
		}else{
			$http_response->SetRespuesta(0, "Descarga Permitida", 'Puede descargar las facturas');
			$response = $http_response->GetRespuesta();
		}		
	}

	echo json_encode($response);

	function VerificarCodigos($codigo_inicial, $codigo_final){
		$result = array('seguir' => false, 'mensaje' => '');

		$id_inicial = GetIdFacturaCodigo($codigo_inicial);
		$id_final = GetIdFacturaCodigo($codigo_final); 

		if ($id_inicial == 0) {
			$result['seguir'] = false;
			$result['mensaje'] = 'El codigo de la factura inicial no existe, cambielo por favor!';
		}elseif($id_final == 0){
			$result['seguir'] = false;
			$result['mensaje'] = 'El codigo de la factura final no existe, cambielo por favor!';
		}elseif ($id_inicial > $id_final) {
			$result['seguir'] = false;
			$result['mensaje'] = 'El codigo de la factura final no puede ser superior al codigo de la factura inicial';
		}elseif ($id_inicial == $id_final) {
			$result['seguir'] = true;
			$result['mensaje'] = '';
			$result['cod_ini'] = $id_inicial;
			$result['cod_fin'] = $id_final;
		}else{
			$result['seguir'] = true;
			$result['mensaje'] = '';
			$result['cod_ini'] = $id_inicial;
			$result['cod_fin'] = $id_final;
		}

		return $result;
	}

	function GetIdFacturaCodigo($codigo){
		global $queryObj;

		$query = '
			SELECT
				Id_Factura
			FROM Factura
			WHERE
				Codigo = "'.$codigo.'"';

		$queryObj->SetQuery($query);
		$cod = $queryObj->ExecuteQuery('simple');
		return intval($cod['Id_Factura']);
	}

?>