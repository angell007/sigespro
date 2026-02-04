<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.configuracion.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.consulta.php');

	$http_response = new HttpResponse();
	$response = array();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '');
	$errores = ( isset( $_REQUEST['errores'] ) ? $_REQUEST['errores'] : []);
	$modelo = json_decode($modelo, true);
	$errores = json_decode($errores, true);

	var_dump($modelo);
	var_dump($errores);exit;
	$estado='';
	
	$oItem= new complex("Auditoria", "Id_Auditoria", $modelo['Id_Auditoria']);
    $oItem->Estado=$modelo['Estado'];    
    $oItem->save();
	unset($oItem);
	
	if(count($errores)>0){
		foreach ($errores as $value) {
			$estado.=$value['Tipo_Soporte'].", ";
		}
		$estado=trim($estado,', ');
	}

	if($estado==''){
		$estado='Ninguno';
	}

	GuardarActividadAuditoria($modelo['Id_Auditoria'], $modelo['Identificacion_Funcionario'],$modelo['Estado'], $modelo['Observacion']);
	if($modelo['Estado']=='Rechazar'){
		
		GuardarAlerta($modelo['Id_Auditoria']);
	}
    $http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha(n) agregado(s) exitosamente los datos de la Auditoria!');
    $response = $http_response->GetRespuesta();

	echo json_encode($response);

	function GuardarActividadAuditoria($idAuditoria, $idFuncionario,$tipo, $observacion){
		global $estado;
		$oItem= new complex("Actividad_Auditoria","Id_Actividad_Auditoria");
	    $oItem->Identificacion_Funcionario = $idFuncionario;
	    $oItem->Id_Auditoria = $idAuditoria;
	    $oItem->Detalle = ObtenerTexto($tipo, $observacion) ;
        $oItem->Estado ='Validacion' ;
		$oItem->Fecha=date("Y-m-d H:i:s");
		$oItem->Observacion=$observacion!='' ?  $observacion : "Sin Observacion";
		$oItem->Errores=$estado;
	    $oItem->save();
	    unset($oItem);
    }
    function ObtenerTexto($tipo, $observacion){
        $texto='';
        if($tipo=='Aceptar'){
            $texto='Se verifica que toda la informacion de la auditoria es correcta';
        }elseif($tipo=='Rechazar'){
            $texto='Se evidencia que hay algunos errores, se anexa la siguiente observacion : '.$observacion;
        }
        return $texto;
	}
	function GuardarAlerta($idAuditoria){
		$oItem= new complex("Alerta","Id_Alerta");
		$oItem->Identificacion_Funcionario=ObtenerFuncionario($idAuditoria);
		$oItem->Tipo="Auditoria";
		$oItem->Detalles="La Auditoria con codigo: AUD00".$idAuditoria." presenta algunos errores con los documentos, por favor revise";
		$oItem->Modulo="/corregirdocumentos";
		$oItem->Id=$idAuditoria;
		$oItem->save();
		unset($oItem);

		$func=GetFuncionarioDispensacion($idAuditoria);
		if($func['Identificacion_Funcionario']){

			if($func['Identificacion_Funcionario']!=ObtenerFuncionario($idAuditoria)){
				$oItem= new complex("Alerta","Id_Alerta");
				$oItem->Identificacion_Funcionario=$func['Identificacion_Funcionario'];
				$oItem->Tipo="Auditoria";
				$oItem->Detalles="La Auditoria con codigo: AUD00".$idAuditoria." presenta algunos errores con los documentos, por favor revise";
				$oItem->Modulo="";
				$oItem->Id=$idAuditoria;
				$oItem->save();
				unset($oItem);
			}
			
		}
	}

	function ObtenerFuncionario($idAuditoria){
		$query='SELECT Funcionario_Preauditoria as Identificacion_Funcionario FROM Auditoria WHERE Id_Auditoria='.$idAuditoria;
		$oCon=new consulta();
		$oCon->setQuery($query);
		$funcionario=$oCon->getData();
		unset($oCon);
		return $funcionario['Identificacion_Funcionario'];
	}

	function GetFuncionarioDispensacion($idAuditoria){
		$query='SELECT D.Identificacion_Funcionario  FROM Auditoria A INNER JOIN Dispensacion D ON A.Id_Dispensacion=D.Id_Dispensacion WHERE A.Id_Auditoria='.$idAuditoria;
		$oCon=new consulta();
		$oCon->setQuery($query);
		$auditoria=$oCon->getData();
		unset($oCon);
		return $auditoria;
	}

?>