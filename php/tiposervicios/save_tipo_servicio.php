<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.table.php');

	$http_response = new HttpResponse();
	$queryObj = new QueryBaseDatos();
	$repsonse = array();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
	$campos = ( isset( $_REQUEST['campos'] ) ? $_REQUEST['campos'] : '' );
	$tiposoporte = ( isset( $_REQUEST['tiposoporte'] ) ? $_REQUEST['tiposoporte'] : '' );
	$contratos = ( isset( $_REQUEST['contratos'] ) ? $_REQUEST['contratos'] : '' );

	
	$modelo = json_decode($modelo, true);
	$campos = json_decode($campos, true);
	$tiposoporte = json_decode($tiposoporte, true);
	// $contratos = json_decode($contratos, true);

    $contratos = (array) json_decode($contratos , true); 

	$fecha = date('Y-m-d H:i:s');
	if ($modelo['Id_Tipo_Servicio'] == '') {
		$oItem= new complex("Tipo_Servicio","Id_Tipo_Servicio");    
	}else{
		$oItem= new complex("Tipo_Servicio","Id_Tipo_Servicio", $modelo['Id_Tipo_Servicio']);	 
	}
	foreach($modelo as $index=>$value) {
            $oItem->$index=$value;  
    }
	$oItem->save();
	$id_servicio=$oItem->getId();
	unset($oItem);
	if(is_array($tiposoporte)){
		foreach($tiposoporte as $tipo){
		  
			if($tipo["Pre_Auditoria"]!=""){
				$tipo['Pre_Auditoria'] = "Si";
			}else{
				$tipo['Pre_Auditoria'] = "No";
			}
			
			if($tipo["Auditoria"]!=""){
				$tipo['Auditoria'] = "Si";
			}else{
				$tipo['Auditoria'] = "No";
			}
			
			if($tipo["Tipo_Soporte"]!=""){
				$tipo["Id_Tipo_Servicio"]=$id_servicio;
				$oItem = new complex('Tipo_Soporte','Id_Tipo_Soporte');
				foreach($tipo as $index=>$value) {
					$oItem->$index=$value;
				}
				$oItem->save();
				unset($oItem);
			}
		}
	}

	//modificado roberth 04-08-2021
	$i = -1;
	foreach ($contratos as $index) {$i++;
		if($id_servicio){
			$oItem = new complex("Tipo_Servicio_Contrato","Id_Tipo_Servicio_",$id_servicio);
		}else{
			$oItem = new complex("Tipo_Servicio_Contrato","Id_Tipo_Servicio_Contrato");
		}
	 $oItem->Id_Tipo_Servicio=$id_servicio;
	$oItem->Id_Contrato=$index['Id_Contrato'];
	$oItem->save();
	unset($oItem);  
}




	$i = -1;
	foreach ($campos as $item) {$i++;
		$val = '';
		$oItem= new complex("Campos_Tipo_Servicio","Id_Campos_Tipo_Servicio");	

		$tipo = '';
		if ($item['Tipo'] == 'text') {
			$tipo = ' VARCHAR(200)';
		}elseif ($item['Tipo'] == 'number') {
			if($item['Longitud']>10){
				$tipo = ' BIGINT(20)';
			}else{
				$tipo = ' INT(20)';
			}
			
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

	function AgregarCampoDispensacion($nombreCampo, $tipo){
		global $queryObj;

		$query = 'SELECT * FROM Dispensacion LIMIT 1';
		$queryObj->SetQuery($query);
		$data = $queryObj->ExecuteQuery('simple');

		$camposDispensacion = ArmarCadenaCamposTabla($data);
		$exist = ValidarExistencia($camposDispensacion, $nombreCampo);
		if(!$exist){
			$modificar_tabla = new table("Dispensacion");
			$modificar_tabla->addColumn($nombreCampo, $tipo);
			$modificar_tabla->save();
			unset($modificar_tabla);
		}
	}

	function AgregarCampoAuditoria($nombreCampo, $tipo){
		global $queryObj;

		$query = 'SELECT * FROM Auditoria LIMIT 1';
		$queryObj->SetQuery($query);
		$data = $queryObj->ExecuteQuery('simple');

		$camposAuditoria = ArmarCadenaCamposTabla($data);
		$exist = ValidarExistencia($camposAuditoria, $nombreCampo);
		if(!$exist){
			$modificar_tabla = new table("Auditoria");
			$modificar_tabla->addColumn($nombreCampo, $tipo);
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
	    $pos = array_search($key,$data);
	    return $pos;
	}
?>