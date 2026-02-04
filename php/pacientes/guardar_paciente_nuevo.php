<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');

	$queryObj = new QueryBaseDatos();
	$http_response = new HttpResponse();
	$response = array();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
	$modelo = (array) json_decode(utf8_decode($modelo) , true);

	unset($modelo['Nit_IPS']);
	unset($modelo['IPS']);

	$query = '
		SELECT 
			* 
		FROM Eps 
		WHERE 
			Nombre = "'.$modelo["EPS"].'"';

	$queryObj->setQuery($query);
	$eps = $queryObj->ExecuteQuery('simple');

	$municipio = GetInformacionMunicipio($modelo['Cod_Municipio']);
	$codigo_departamento = GetCodigoDepartamento($modelo['Id_Departamento']);

	$oItem = new complex('Paciente',"Id_Paciente");
	$modelo["Nit"]=$eps["Nit"];
	$modelo["Cod_Municipio_Dane"]=$municipio["Codigo_Dane"];
	$modelo["Cod_Municipio_Dian"]=$municipio["Codigo"];
	$modelo["Cod_Departamento"]=$codigo_departamento;
	$modelo["Primer_Nombre"]=strtoupper($modelo["Primer_Nombre"]);
	$modelo["Segundo_Nombre"]=strtoupper($modelo["Segundo_Nombre"]);
	$modelo["Primer_Apellido"]=strtoupper($modelo["Primer_Apellido"]);
	$modelo["Segundo_Apellido"]=strtoupper($modelo["Segundo_Apellido"]);
	foreach($modelo as $index=>$value){
		if ($index == 'Cod_Municipio') {
			$index = 'Codigo_Municipio';
		}
		$oItem->$index=$value;
	}
	$oItem->save();
	unset($oItem);

	unset($oCon);
     
 
	$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha guardado el paciente exitosamente!');
	$response = $http_response->GetRespuesta();

	echo json_encode($response);

	function GetInformacionMunicipio($idMunicipio){
		global $queryObj;

		$query = '
			SELECT 
				* 
			FROM Municipio 
			WHERE 
				Id_Municipio = '.$idMunicipio;

		$queryObj->setQuery($query);
		$municipio = $queryObj->ExecuteQuery('simple');

		return $municipio;
	}

	function GetCodigoDepartamento($idDepartamento){
		global $queryObj;

		$query = '
			SELECT 
				* 
			FROM Departamento 
			WHERE 
				Id_Departamento = '.$idDepartamento;

		$queryObj->setQuery($query);
		$codigo = $queryObj->ExecuteQuery('simple');

		return $codigo['Codigo'];
	}
?>