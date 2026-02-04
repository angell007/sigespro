<?php
	header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.lista.php');
    include_once('../../class/class.complex.php');
    include_once('../../class/class.consulta.php');
    include('../../class/class.system_constants.php');
	include('../../class/class.http_response.php');
	

    $respuesta = array();
    $http_response = new HttpResponse();

    $datos = ( isset( $_REQUEST['Datos'] ) ? $_REQUEST['Datos'] : '' );
    $accion = ( isset( $_REQUEST['accion'] ) ? $_REQUEST['accion'] : '' );
    $datos = (array) json_decode($datos);

    if($accion == 'guardar'){
    	if(ComprobarExistenciaCentro($datos['Codigo'])){
	    	$http_response->SetRespuesta(2, "Respuesta",'El centro de costo que intenta registrar ya existe!');
	    	$respuesta = $http_response->GetRespuesta();
	    	echo json_encode($respuesta);
	    	return;
	    }
    }
    
    
    if(isset($datos['Id_Centro_Costo']) && $datos['Id_Centro_Costo'] != ''){
        $oItem = new complex("Centro_Costo","Id_Centro_Costo", $datos['Id_Centro_Costo']);
    }else{
    
    	$oItem = new complex("Centro_Costo","Id_Centro_Costo");
    }

    
	foreach($datos as $index=>$value) {
		if ($index == 'Id_Tipo_Centro' && $value == '') {
			$value = '0'; 
		}
		if ($index == 'Valor_Tipo_Centro' && $value == '') {
			$value = '0'; 
		}
		if ($index == 'Id_Centro_Padre' && $value == '') {
			$value = '0'; 
		}
	    $oItem->$index=$value;
	}

	$oItem->save();
	unset($oItem);

	$http_response->SetRespuesta(0, "Respuesta",'Operación Exitosa!');
	$respuesta = $http_response->GetRespuesta();
	echo json_encode($respuesta);

	function ComprobarExistenciaCentro($codigoCentro){

		$query_centro_costo = '
			SELECT
				*
			FROM Centro_Costo
			WHERE
				Codigo = "'.$codigoCentro.'"';

		$oCon= new consulta();
	    $oCon->setQuery($query_centro_costo);
	    $total = $oCon->getData();
	    unset($oCon);

	    return $total != false;
	}

	function SetIdentificadorVisual($codigoCentro){
		$longitud_codigo = strlen($codigoCentro);
		$nivel_visual = $longitud_codigo / SystemConstant::$Digitos_Codigo_Centro_Costo;
		$identificador_visual = '';

		for ($i=$nivel_visual; $i == 0 ; $i--) { 
			$identificador_visual .= '-';
		}

		return $identificador_visual;
	}

?>