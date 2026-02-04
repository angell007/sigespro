<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');
	include_once('../../class/class.configuracion.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.querybasedatos.php');

	$http_response = new HttpResponse();
	$queryObj = new QueryBaseDatos();
	$response = array();

	$id_factura = ( isset( $_REQUEST['id_factura'] ) ? $_REQUEST['id_factura'] : '' );
	$id_radicado = ( isset( $_REQUEST['id_radicado'] ) ? $_REQUEST['id_radicado'] : '' );
	$id_funcionario = ( isset( $_REQUEST['id_funcionario'] ) ? $_REQUEST['id_funcionario'] : '' );
	$codigo = ( isset( $_REQUEST['codigo'] ) ? $_REQUEST['codigo'] : '' );
	$tipo_servicio = ( isset( $_REQUEST['tipo_servicio'] ) ? $_REQUEST['tipo_servicio'] : '' );

	$query_radicado_factura = "SELECT Id_Radicado_Factura FROM Radicado_Factura WHERE Id_Factura = $id_factura AND Id_Radicado = $id_radicado";
	$queryObj->SetQuery($query_radicado_factura);
	$id_radicado_factura = $queryObj->ExecuteQuery('simple');
	$id_radicado_factura = $id_radicado_factura["Id_Radicado_Factura"];



	$query_delete = "DELETE FROM Radicado_Factura WHERE Id_Factura = $id_factura AND Id_Radicado = $id_radicado";
	$queryObj->SetQuery($query_delete);
	$queryObj->QueryUpdate();

	$query_codigo = '';
	if ($tipo_servicio == 'CAPITA') {
		$oItem= new complex("Factura_Capita", "Id_Factura_Capita", $id_factura);
	    $oItem->Estado_Radicacion = 'Pendiente';
	    $oItem->save();
	    unset($oItem);

	    $query_codigo = '
	    	SELECT
	    		Codigo
			FROM Factura_Capita
			WHERE
				Id_Factura_Capita = '.$id_factura;
	}else{
		
		$oItem= new complex("Factura", "Id_Factura", $id_factura);
	    $oItem->Estado_Radicacion = 'Pendiente';
	    $oItem->save();
	    unset($oItem);
	    $query_codigo = '
	    	SELECT
				Id_Factura,
	    		Codigo
			FROM Factura
			WHERE
				Id_Factura = '.$id_factura;
	}
	
	$queryObj->SetQuery($query_codigo);
	$codigo_factura = $queryObj->ExecuteQuery('simple');
 


    GuardarActividadRadicado($id_radicado, $id_funcionario, $codigo, $codigo_factura['Codigo']);


    GuardarActividadFactura($id_radicado, $id_funcionario, $codigo, $codigo_factura['Id_Factura'], $codigo_factura['Codigo']);

    $idGlosas = GetIdGlosasFactura($id_radicado_factura);
	
    EliminarGlosasFactura($id_radicado_factura);
    EliminarRespuestasGlosas($idGlosas);
    $http_response->SetRespuesta(0, 'Proceso Exitoso', 'Se ha eliminado la factura exitosamente!');
    $response = $http_response->GetRespuesta();

    unset($http_response);
    unset($queryObj);

	echo json_encode($response);
	
	function GuardarActividadRadicado($idRadicado, $idFuncionario, $codigo, $codigoFactura){
		$oItem= new complex("Actividad_Radicado","Id_Actividad_Radicado");
	    $oItem->Id_Funcionario = $idFuncionario;
	    $oItem->Id_Radicado = $idRadicado;
	    $oItem->Detalle = "Se elimino la factura con codigo ".$codigoFactura." del radicado ".$codigo;
	    $oItem->Estado = 'Factura Eliminada';
	    $oItem->save();
	    unset($oItem);
	}
	function GuardarActividadFactura($idRadicado, $idFuncionario,$codigo, $codigoFactura, $cod ){
			
		$oItem= new complex("Actividad_Factura","Id_Actividad_Factura");
	    $oItem->Id_Funcionario = $idFuncionario;
	    $oItem->Id_Radicado = $idRadicado;
	    $oItem->Factura = $codigoFactura;
	    $oItem->Fecha = date("Y-m-d H:i:s");
	    $oItem->Detalle = "Se elimina factura ". $cod ." del radicado ".$codigo;
	    $oItem->Estado = 'Factura Eliminada';
	    $oItem->save();
	    unset($oItem);
	}
	function GetIdGlosasFactura($idRadicadoFactura){
		global $queryObj;

	    $query ='SELECT	GROUP_CONCAT(Id_Glosa_Factura) AS Id_Glosas
    		FROM Glosa_Factura
    		WHERE Id_Radicado_Factura = '.$idRadicadoFactura;

	    $queryObj->SetQuery($query);
	    $id_glosas = $queryObj->ExecuteQuery('simple');

	    return $id_glosas['Id_Glosas'];
	}
	function EliminarGlosasFactura($idRadicadoFactura){
		global $queryObj;

	    $query = 'DELETE FROM Glosa_Factura WHERE Id_Radicado_Factura = '.$idRadicadoFactura;
	    $queryObj->SetQuery($query);
	    $queryObj->QueryUpdate();
	}
	function EliminarRespuestasGlosas($idGlosas){
		global $queryObj;

	    $query = 'DELETE FROM Respuesta_Glosa WHERE Id_Glosa_Factura IN ('.$idGlosas.')';
	    $queryObj->SetQuery($query);
	    $queryObj->QueryUpdate();
	}
?>