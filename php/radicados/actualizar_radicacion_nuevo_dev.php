<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	// require_once('../../config/start.inc.php');
	// include_once('../../class/class.complex.php');
	// include_once('../../class/class.consulta.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.utility.php');
	include_once('../../class/class.querybasedatos.php');
	require('../../class/class.guardar_archivos.php');

	$storer = new FileStorer();

	$util = new Utility();
	$http_response = new HttpResponse();
	$queryObj = new QueryBaseDatos();
	$response = array();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
	$facturas = ( isset( $_REQUEST['facturas'] ) ? $_REQUEST['facturas'] : '' );
	$id_facturas = ( isset( $_REQUEST['id_facturas'] ) ? $_REQUEST['id_facturas'] : '' );
	$cerrar_radicacion = ( isset( $_REQUEST['cerrar'] ) ? $_REQUEST['cerrar'] : '' );

	$modelo = json_decode($modelo, true);
	$facturas = json_decode($facturas, true);
	$id_facturas = json_decode($id_facturas, true);
	$fecha = date('Y-m-d');

	// var_dump($MY_FILE);
	// var_dump($modelo);
	// var_dump($facturas);
	// var_dump($id_facturas);
	// var_dump($cerrar_radicacion);
	var_dump($_FILES);

	// $nombres_archivos_glosas = $storer->UploadFileToRemoteServer2($_FILES, 'store_remote_files', 'ARCHIVOS/GLOSA/');
	// var_dump($nombres_archivos_glosas);
	exit;

	$nombres_archivos_glosas = array();

	if (count($facturas) == 0) {
		
		$http_response->SetRespuesta(2, 'Alerta', 'No hay facturas para actualizar el registro, verifique o contacte al administrador del sistema!');
		$response = $http_response->GetRespuesta();
		echo json_encode($response);
		return;
	}

	//SE GUARDAN LOS ARCHIVOS DE LAS GLOSAS PARA OBTENER LOS NOMBRES DE RETORNO Y ASOCIARLO A SU RESPECTIVO REGISTRO
	$nombres_archivos_glosas = $storer->UploadFileToRemoteServer2($_FILES, 'store_remote_files', 'ARCHIVOS/GLOSA/');

	//SE ACTUALIZAN LAS FACTURAS
	foreach ($facturas as $factura) {
		//var_dump("guardando glosas");
    	GuardarGlosasFactura($factura['Glosas_Factura'], $factura['Id_Factura'], $factura['Codigo_Factura'], $nombres_archivos_glosas);
		
		$oItem= new complex("Radicado_Factura","Id_Radicado_Factura", $factura['Id_Radicado_Factura']);
		$oItem->Estado_Radicado_Factura = SetEstadoRadicadoFactura($factura['Id_Radicado_Factura'], true);
		$oItem->Total_Glosado = SetEstadoRadicadoFactura($factura['Id_Radicado_Factura']);
		// $oItem->save();
	    unset($oItem);

	    
	}

	//SE CREA LA ACTIVIDAD DE ACTUALIZACION DE LAS FACTURAS
	$cadena_facturas = $util->ArrayToCommaSeparatedString($id_facturas);
	$detalle_actividad = 'Se editaron las facturas '.$cadena_facturas;

	GuardarActividadRadicado($modelo['Id_Radicado'], $modelo['Id_Funcionario'], $modelo['Codigo'], $detalle_actividad);
	$http_response->SetRespuesta(0, 'Actualizacion Exitosa', 'Se ha actualizado la radicacion exitosamente!');


	//CERRAR RADICACION
	if ($cerrar_radicacion == 'si') {
		
		$oItem= new complex("Radicado","Id_Radicado", $modelo['Id_Radicado']);
		$oItem->Estado = "Cerrada";
		$oItem->Fecha_Cierre = $fecha;
		// $oItem->save();
		unset($oItem);

		GuardarActividadCierre($modelo['Id_Radicado'], $modelo['Id_Funcionario'], $modelo['Codigo']);
		$http_response->SetRespuesta(0, 'Cierre Exitoso', 'Se ha cerrado la radicacion exitosamente!');
	}

    
    $response = $http_response->GetRespuesta();
    unset($http_response);

	echo json_encode($response);

	function GuardarGlosasFactura($glosas, $idFactura, $codFactura, $nombresArchivosGlosas){
		$id_radicado_factura = $glosas[0]['Id_Radicado_Factura'];

    	$idGlosasActualizar = ArmarCadena2($glosas);
    	InhabilitarGlosasFactura($idGlosasActualizar, $id_radicado_factura);
    	
		//ASIGNAR DATOS DE GLOSAS Y ACTUALIZARLAS
	    if (count($glosas) > 0) {
		
			foreach ($glosas as $k => $g) {
				$archivo = '';
				if ($g['Archivo']) {
					$posible_nombre_archivo = $codFactura.'_'.$g['Codigo_Glosa'].'.pdf';
					$existe_nombre = array_search($posible_nombre_archivo, $nombresArchivosGlosas);

					if ($existe_nombre !== false) {
						$archivo = $nombresArchivosGlosas[$existe_nombre];
					}
				}

				// var_dump($archivo);
				// exit;

				$idGlosaFactura = ExisteRegistroGlosaFactura($g['Codigo_Glosa'], $id_radicado_factura, $idFactura);

				if ($idGlosaFactura == '0') {	
			 		$oItem = new complex("Glosa_Factura","Id_Glosa_Factura");

			 		$oItem->Codigo_Glosa=$g['Codigo_Glosa'];
			 		$oItem->Id_Codigo_Especifico_Glosa=$g['Id_Codigo_Especifico_Glosa'];
			 		$oItem->Id_Codigo_General_Glosa=$g['Id_Codigo_General_Glosa'];
				    $oItem->Id_Radicado_Factura=$g['Id_Radicado_Factura'];
				    $oItem->Valor_Glosado=floatval(number_format($g['Valor_Glosado'], 2, ",", ""));
				    $oItem->Observacion_Glosa = $g['Observacion_Glosa'];
				    $oItem->Archivo_Glosa = $archivo;
				    $oItem->save();
				    unset($oItem);
				}else{
					//ELIMINAR EL ARCHIVO DE LA GLOSA EN EL SERVIDOR, SI TIENE UNO.
					EliminarArchivoGlosaServidor($idGlosaFactura, $archivo);

					$oItem = new complex("Glosa_Factura","Id_Glosa_Factura", $idGlosaFactura);
					$oItem->Codigo_Glosa=$g['Codigo_Glosa'];
					$oItem->Id_Codigo_Especifico_Glosa=$g['Id_Codigo_Especifico_Glosa'];
					$oItem->Id_Codigo_General_Glosa=$g['Id_Codigo_General_Glosa'];
				    $oItem->Valor_Glosado=floatval(number_format($g['Valor_Glosado'], 2, ",", ""));
				    $oItem->Observacion_Glosa = $g['Observacion_Glosa'];
				    $oItem->Fecha_Registro = date('Y-m-d H:i:s');
				    $oItem->Estado = 'Activa';
				    $oItem->Archivo_Glosa = $archivo;
				    $oItem->save();
				    unset($oItem);
				}
			}
		}
	}

	function GuardarActividadRadicado($idRadicado, $idFuncionario, $codigo, $detalle){
			
		$oItem= new complex("Actividad_Radicado","Id_Actividad_Radicado");
	    $oItem->Id_Funcionario = $idFuncionario;
	    $oItem->Id_Radicado = $idRadicado;
	    //$oItem->Detalle = $detalle.', de la radicacion codigo '.$codigo;
	    $oItem->Detalle = 'Se edito la radicacion con codigo '.$codigo;
	    $oItem->Estado = 'Edicion';
	    // $oItem->save();
	    unset($oItem);
	}

	function GuardarActividadCierre($idRadicado, $idFuncionario, $codigo){
			
		$oItem= new complex("Actividad_Radicado","Id_Actividad_Radicado");
	    $oItem->Id_Funcionario = $idFuncionario;
	    $oItem->Id_Radicado = $idRadicado;
	    $oItem->Detalle = 'Se ha cerrado la radicacion codigo '.$codigo;
	    $oItem->Estado = 'Cerrado';
	    // $oItem->save();
	    unset($oItem);
	}

	function ArmarCadena($glosas){
		$ids = '';

		if (count($glosas) > 0) {
			$glosas = array_unique($glosas);
			foreach ($glosas as $g) {
			
				$ids .= $g['Id_Tipo_Glosa'].', ';
			}
		}else{

			$ids = '0';
		}
		

		return trim($ids, ", ");
	}

	function ArmarCadena2($glosas){
		$ids = '';

		if (count($glosas) > 0) {
			$glosas = array_unique($glosas);
			foreach ($glosas as $g) {
			
				$ids .= $g['Id_Codigo_General_Glosa'].', ';
			}
		}else{

			$ids = '0';
		}
		

		return trim($ids, ", ");
	}

	function InhabilitarGlosasFactura($idGlosas, $idRadicadoFactura){
		global $queryObj;

		if ($idGlosas != '0') {

			$query = '
				SELECT
					IFNULL(GROUP_CONCAT(Id_Codigo_General_Glosa), "0") AS Id_Glosas_Inactivar
				FROM Glosa_Factura
				WHERE
					Id_Radicado_Factura = '.$idRadicadoFactura
					.' AND Id_Codigo_General_Glosa NOT IN ('.$idGlosas.')';

			$queryObj->SetQuery($query);
			$result = $queryObj->ExecuteQuery('simple');

			if ($result !== false) {

				$query_update = '
					UPDATE Glosa_Factura SET Estado = "Inactiva" WHERE Id_Codigo_General_Glosa IN ('.$result["Id_Glosas_Inactivar"].') AND Id_Radicado_Factura = '.$idRadicadoFactura;				

				$queryObj->SetQuery($query_update);
				$queryObj->QueryUpdate();
			}			
		}
	}

	function ExisteRegistroGlosaFactura($idTipoGlosa, $idRadicadoFactura, $idFactura){
		global $queryObj;
		
		$query = '
			SELECT
				GF.Id_Glosa_Factura
			FROM Glosa_Factura GF
			INNER JOIN Radicado_Factura RF ON GF.Id_Radicado_Factura = RF.Id_Radicado_Factura
			WHERE
				GF.Id_Radicado_Factura = '.$idRadicadoFactura
				.' AND GF.Codigo_Glosa = "'.$idTipoGlosa
				.'" AND RF.Id_Factura = '.$idFactura;

		$queryObj->SetQuery($query);
		$result = $queryObj->ExecuteQuery('simple');

		return $result !== false ? $result['Id_Glosa_Factura'] : '0';
	}

	function SetEstadoRadicadoFactura($idRadicadoFactura, $estado = false){
		global $queryObj;

		$query = '
			SELECT
				IFNULL(SUM(Valor_Glosado), 0) AS Total_Glosado
			FROM Glosa_Factura
			WHERE
				Id_Radicado_Factura = '.$idRadicadoFactura
				.' AND Estado = "Activa"';

		$queryObj->SetQuery($query);
		$result = $queryObj->ExecuteQuery('simple');

		if ($estado) {
			if ($result !== false && floatval($result['Total_Glosado']) > 0) {
				return "Glosada";	
			}else if($result !== false && floatval($result['Total_Glosado']) == 0){
				return "Radicada";
			}else if($result === false){
				return "Radicada";
			}
			
		}else{
			if ($result !== false && floatval($result['Total_Glosado']) > 0) {
				return $result['Total_Glosado'];	
			}else if($result !== false && floatval($result['Total_Glosado']) == 0){
				return $result['Total_Glosado'];
			}else if($result === false){
				return "0";
			}
		}		
	}
	
	function EliminarArchivoGlosaServidor($idGlosa, $nombreArchivo){
		global $queryObj, $MY_FILE;
		
		$query = "
			SELECT
				Archivo_Glosa
			FROM Glosa_Factura
			WHERE
				Id_Glosa_Factura = $idGlosa";

		$queryObj->SetQuery($query);
		$result = $queryObj->ExecuteQuery('simple');

		if ($result['Archivo_Glosa'] != '' && $result['Archivo_Glosa'] != $nombreArchivo) {
			$file = $MY_FILE.'ARCHIVOS/GLOSA/'.$result['Archivo_Glosa'];
			unlink($file);
		}

	}
?>