<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.table.php');

/* 	$oTabla=new table('Dispensacion');
	//$oTabla->setName('Dispensacion');
	$oTabla->addColumn('Nombre_Prueba','varchar(100)');
	$oTabla->save();
	unset($oTabla); */

	 $http_response = new HttpResponse();
	$queryObj = new QueryBaseDatos();
	$repsonse = array();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
	$campos = ( isset( $_REQUEST['campos'] ) ? $_REQUEST['campos'] : '' );
	$id_tipo_servicio = ( isset( $_REQUEST['id_tipo_servicio'] ) ? $_REQUEST['id_tipo_servicio'] : '' );
	$tiposoporte = ( isset( $_REQUEST['tiposoporte'] ) ? $_REQUEST['tiposoporte'] : '' );
	$contratos = ( isset( $_REQUEST['contratos'] ) ? $_REQUEST['contratos'] : '' );


	$modelo = json_decode($modelo, true);
	$campos = json_decode($campos, true);
	$tiposoporte = json_decode($tiposoporte, true);
	$contratos = (array) json_decode($contratos , true); 

	$oItem = new complex('Tipo_Servicio','Id_Tipo_Servicio',$modelo['Id_Tipo_Servicio']);

	foreach($modelo as $index=>$value) {
		if($value!='' && $value!=null){
			$oItem->$index=$value;
		}
	}
	$oItem->save();
	$id_servicio=$oItem->getId();
	unset($oItem);

	$i = -1;
	foreach ($contratos as $index) {$i++;
		if($id_servicio){		
			$oItem = new complex("Tipo_Servicio_Contrato","Id_Tipo_Servicio_Contrato",$index['Id_Contrato']);
		}else{
			$oItem = new complex("Tipo_Servicio_Contrato","Id_Tipo_Servicio_Contrato");
		}
	$oItem->Id_Tipo_Servicio=$id_servicio;
	$oItem->Id_Contrato=$index['Id_Contrato'];
	$oItem->save();
	unset($oItem);  
}





	
	foreach($tiposoporte as $tipo){
		if($tipo['Id_Tipo_Soporte']!=''){
			$oItem = new complex('Tipo_Soporte','Id_Tipo_Soporte',$tipo['Id_Tipo_Soporte']);
			foreach($tipo as $index=>$value) {
				$oItem->$index=$value;
			}
			$oItem->save();
			unset($oItem);
		}else if ($tipo['Tipo_Soporte']!=''){
			$oItem = new complex('Tipo_Soporte','Id_Tipo_Soporte');
			$tipo['Id_Tipo_Servicio']=$modelo['Id_Tipo_Servicio'];
			foreach($tipo as $index=>$value) {
				$oItem->$index=$value;
			}
			$oItem->save();
			unset($oItem);
		}
	   
		
	}

	foreach ($campos as $item) {
	
	
		if($item['Edicion']!='0'){
			
			$val = '';
			$oItem= new complex("Campos_Tipo_Servicio","Id_Campos_Tipo_Servicio", $item['Id_Campos_Tipo_Servicio']);
			unset($item['Id_Campos_Tipo_Servicio']);

			$tipo = '';
			if ($item['Tipo'] == 'text') {
				$tipo = ' VARCHAR(200)';
			}elseif ($item['Tipo'] == 'number') {
				$tipo = ' BIGINT(20)';
			}elseif ($item['Tipo'] == 'date') {
				$tipo = ' DATE';
			}

			$comparacion = ComparacionNombres($item['Nombre_Original'], $item['Nombre']);
		
			if (!$comparacion) {
				$val = AjustarNombreCampo($item['Nombre']);				

				if($item['Tipo_Campo'] == 'Cabecera'){
					
					if ($item['Modulo'] == 'Dispensacion') {					
						ActualizarCampo($item['Nombre_Original'], $val, $tipo, "Dispensacion");
					}elseif($item['Modulo'] == 'Auditoria'){					
						ActualizarCampo($item['Nombre_Original'], $val, $tipo, "Auditoria");
					}elseif($item['Modulo'] == 'Ambos'){

				
						ActualizarCampo($item['Nombre_Original'], $val, $tipo, "Dispensacion");					
						
						ActualizarCampo($item['Nombre_Original'], $val, $tipo, "Auditoria");

				
					}
				}elseif($item['Tipo_Campo'] == 'Producto'){
					ActualizarCampo($item['Nombre_Original'], $val, $tipo, "Producto_Dispensacion");
				}
			}
			
			foreach($item as $index=>$value) {
				if ($index == 'Nombre') {
					if (!$comparacion) {
						$oItem->$index=$val;
					}else{
						$oItem->$index=$item['Nombre_Original'];	
					}
				}else{
					if($value!='' && $value!=null){
						$oItem->$index=$value;
					}
					  				
				}
			}

			$oItem->Id_Tipo_Servicio=$id_servicio;
			$oItem->save();
			unset($oItem);
		}else if ($item['Edicion']=='0'){
			unset($item['Id_Campos_Tipo_Servicio']);
			$val = '';
			$oItem= new complex("Campos_Tipo_Servicio","Id_Campos_Tipo_Servicio");

			$tipo = '';
			if ($item['Tipo'] == 'text') {
				$tipo = ' VARCHAR(200)';
			}elseif ($item['Tipo'] == 'number') {
				$tipo = ' BIGINT(20)';
			}elseif ($item['Tipo'] == 'date') {
				$tipo = ' DATE';
			}

			if($item['Tipo_Campo'] == 'Cabecera'){

				$val = AjustarNombreCampo($item['Nombre']);
				
				if ($item['Modulo'] == 'Dispensacion') {
					AgregarCampo($val, $tipo, "Dispensacion");
				}elseif($item['Modulo'] == 'Auditoria'){
					AgregarCampo($val, $tipo, "Auditoria");
				}elseif($item['Modulo'] == 'Ambos'){
					AgregarCampo($val, $tipo, "Dispensacion");
					AgregarCampo($val, $tipo, "Auditoria");
				}
			}elseif($item['Tipo_Campo'] == 'Producto'){
				
				$val = AjustarNombreCampo($item['Nombre']);
				AgregarCampo($val, $tipo, "Producto_Dispensacion");
			}

			foreach($item as $index=>$value) {
				if ($index == 'Nombre') {
					$oItem->$index=$val;
				}else{
					if($value!='' && $value!=null){
						$oItem->$index=$value;
					}				
				}
			}

			$oItem->Id_Tipo_Servicio=$id_servicio;
			$oItem->save();
			unset($oItem);
		}
	}

	$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha registrado el tipo de activo fijo exitosamente!');
	$repsonse = $http_response->GetRespuesta();

	echo json_encode($repsonse); 

	function AgregarCampo($nombreCampo, $tipo, $tabla){
		global $queryObj;

		$query = "SELECT * FROM $tabla LIMIT 1";
		$queryObj->SetQuery($query);
		$data = $queryObj->ExecuteQuery('simple');

		$campos = ArmarCadenaCamposTabla($data);
		
		$exist = ValidarExistencia($campos, $nombreCampo);

		

		if(!$exist){
			$modificar_tabla = new table($tabla);
			$modificar_tabla->addColumn($nombreCampo, $tipo);
			$modificar_tabla->save();
			unset($modificar_tabla);
		}
	}

	function ActualizarCampo($oldName, $nombreCampo, $tipo, $tabla){
		global $queryObj;

		$query = "SELECT * FROM $tabla LIMIT 1";
		$queryObj->SetQuery($query);
		$data = $queryObj->ExecuteQuery('simple');

		$campos = ArmarCadenaCamposTabla($data);
		

		$exist = ValidarExistencia($campos, $nombreCampo);
		
		if(!$exist){
			$modificar_tabla = new table($tabla);
			$modificar_tabla->setColumn($oldName, $nombreCampo, $tipo);
			$modificar_tabla->save();
			unset($modificar_tabla);
		}
	}

	function AjustarNombreCampo($nombreCampo){
		$palabras_campo = explode(" ", $nombreCampo);
		$nueva_palabra = '';

		foreach ($palabras_campo as $palabra) {
			$p = strtolower($palabra);
			$p = ucfirst($p);
			$nueva_palabra .= $p.'_';
		}

		return trim($nueva_palabra, "_");
	}

	function ArmarCadenaCamposTabla($tableData){
		$cadena = '';
		foreach ($tableData as $key => $value) {
			$cadena .= $key.",";
		}

		$camposTabla = explode(",", $cadena);

		return $camposTabla;
	}

	function ValidarExistencia($data, $key){
		foreach ($data as $k => $value) {
			$data[$k]=strtolower($value);
		}
	    $pos = array_search(strtolower($key),$data);
	    return $pos;
	}

	function ComparacionNombres($oldName, $newName){
		return $oldName == $newName;
	}
?>