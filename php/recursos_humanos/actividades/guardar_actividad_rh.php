<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../../config/start.inc.php');
	require_once('./helper_actividad/funciones_actividad.php');
	include_once('../../../class/class.complex.php');
	include_once('../../../class/class.http_response.php');
	include_once('../../../class/class.consulta.php');

	$http_response = new HttpResponse();
	$response = array();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
	$modelo = (array)json_decode($modelo);
	
	
	$fecha = date('Y-m-d H:i:s');
	if($modelo['Id_Actividad_Recursos_Humanos'] == '') {
		$oItem= new complex("Actividad_Recursos_Humanos","Id_Actividad_Recursos_Humanos");
	    $oItem->Actividad_Recursos_Humanos 		   = $modelo['Actividad_Recursos_Humanos'];
	    $oItem->Identificacion_Funcionario 		   = (INT)$modelo['Identificacion_Funcionario'];
	    $oItem->Fecha_Inicio               		   = $modelo['Fecha_Inicio'];
	    $oItem->Fecha_Fin                          = $modelo['Fecha_Fin'];
	    $oItem->Id_Tipo_Actividad_Recursos_Humanos = $modelo['Id_Tipo_Actividad_Recursos_Humanos'];
	    $oItem->Estado 							   = "Aprobada";
		$oItem->Detalles                           = $modelo['Detalles'];
		$oItem->Id_Grupo						   = $modelo['Id_Grupo'];
		$oItem->Id_Dependencia					   = $modelo['Id_Dependencia'];
		$oItem->Funcionario_Asignado			   = $modelo['Funcionario_Asignado'];

	    $oItem->save();
	    $idActividad = $oItem->getId();
		unset($oItem);
		$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha registrado la actividad exitosamente!');
	    $response = $http_response->GetRespuesta();
	   
	}else{
		$oItem= new complex("Actividad_Recursos_Humanos","Id_Actividad_Recursos_Humanos",$modelo['Id_Actividad_Recursos_Humanos']);
	    $oItem->Actividad_Recursos_Humanos         = $modelo['Actividad_Recursos_Humanos'];
	    $oItem->Fecha_Inicio                       = $modelo['Fecha_Inicio'];
	    $oItem->Fecha_Fin                          = $modelo['Fecha_Fin'];
	    $oItem->Id_Tipo_Actividad_Recursos_Humanos = $modelo['Id_Tipo_Actividad_Recursos_Humanos'];
		$oItem->Detalles                           = $modelo['Detalles'];
		$oItem->Id_Grupo						   = $modelo['Id_Grupo'];
		$oItem->Id_Dependencia					   = $modelo['Id_Dependencia'];
		$oItem->Funcionario_Asignado			   = $modelo['Funcionario_Asignado'];
	    $oItem->save();			
	    unset($oItem);
	    $http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha editado la actividad exitosamente!');
	    $response = $http_response->GetRespuesta();

        if (empty($modelo['Funcionario_Asignado'])) {
			$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha editado la actividad exitosamente!');
			$response = $http_response->GetRespuesta();
        }
		if(in_array("Todos", $modelo['Funcionario_Asignado'])){
			$query= 'DELETE FROM Funcionario_Actividad 
					WHERE Id_Actividad_Recursos = '.$modelo['Id_Actividad_Recursos_Humanos'].'';
					$consult = new consulta();
					$consult->setQuery($query);
					$total = $consult->createData();
					$query = 'SELECT f.Identificacion_Funcionario IDENTIFICACION, f.Liquidado
								FROM Funcionario f
								INNER JOIN Dependencia d ON f.Id_Dependencia = d.Id_Dependencia
								INNER JOIN Grupo g ON d.Id_Grupo = d.Id_Grupo
								WHERE  f.Id_Dependencia = '.$modelo['Id_Dependencia'].' AND g.Id_Grupo = '.$modelo['Id_Grupo'].' AND  f.Liquidado = "NO"
								ORDER BY g.Nombre DESC';
					$consult = new consulta();
					$consult->setQuery($query);
					$consult->setTipo('Multiple');
					$total = $consult->getData();

					foreach($total as $value){
						actuFuncionActividad($modelo['Id_Actividad_Recursos_Humanos'], $value["IDENTIFICACION"]);
					}	
		}
		if(count($modelo['Funcionario_Asignado']) > 1){

				$query= 'DELETE FROM Funcionario_Actividad 
				 WHERE Id_Actividad_Recursos = '.$modelo['Id_Actividad_Recursos_Humanos'].'';
				$consult = new consulta();
				$consult->setQuery($query);
				$total1 = $consult->createData();
				foreach ($modelo['Funcionario_Asignado'] as $funcionario){
					actuFuncionActividad($modelo['Id_Actividad_Recursos_Humanos'], $funcionario);
				}
	}	
			
}

	if($modelo['Id_Grupo'] != 'Todas' && $modelo['Id_Dependencia'] != 'Todas' && $modelo['Funcionario_Asignado'] != ['Todos']) {
		if($modelo['Id_Actividad_Recursos_Humanos'] == '' ){
		    
			$max = 'SELECT Id_Actividad_Recursos_Humanos FROM Actividad_Recursos_Humanos';
			$consul = new consulta();
			$consul->setQuery($max);
			$tot = $consul->getId();
				   
			foreach ($modelo['Funcionario_Asignado'] as $funcionario){
			       	 
				$data = insertFuncionActividad($idActividad, $funcionario);
				insertAlert($funcionario,$modelo["Fecha_Inicio"],$modelo["Detalles"], $data);
				
		    }
		}
	}else if($modelo['Id_Grupo'] != 'Todas' && $modelo['Id_Dependencia'] != 'Todas' && $modelo['Funcionario_Asignado'] == ['Todos']) {
		$query = 'SELECT f.Identificacion_Funcionario IDENTIFICACION, f.Liquidado
					FROM Funcionario f
					INNER JOIN Dependencia d ON f.Id_Dependencia = d.Id_Dependencia
					INNER JOIN Grupo g ON d.Id_Grupo = d.Id_Grupo
					WHERE f.Id_Dependencia = '.$modelo['Id_Dependencia'].' AND g.Id_Grupo = '.$modelo['Id_Grupo'].' AND  f.Liquidado = "NO"
					ORDER BY g.Nombre DESC';
		// echo "para los funcionarios de cierta dependencia de cierto grupo";	
		$consult = new consulta();
		$consult->setQuery($query);
		$consult->setTipo('Multiple');
		$total = $consult->getData();

		foreach($total as $value){
				$data = insertFuncionActividad($idActividad, $funcionario);
				insertAlert($funcionario,$modelo["Fecha_Inicio"],$modelo["Detalles"], $data);
		}
		
	}else if($modelo['Id_Grupo'] == 'Todas' && $modelo['Id_Dependencia'] != 'Todas'){
		$query = 'SELECT d.Nombre DEPENDENCIA, f.Nombres FUNCIONARIO, f.Identificacion_Funcionario IDENTIFICACION
					FROM Funcionario f
					INNER JOIN Dependencia d ON f.Id_Dependencia = d.Id_Dependencia
					INNER JOIN Grupo g
					WHERE d.Id_Dependencia = '.$modelo['Id_Dependencia'].' AND g.Id_Grupo  > 0  AND  f.Liquidado = "NO"';	
		// echo "para todos los funcionarios de cierta dependencia de todo los grupos";
		$consult = new consulta();
		$consult->setQuery($query);
		$consult->setTipo('Multiple');
		$total = $consult->getData();
		
	
		foreach($total as $value){
				$data = insertFuncionActividad($idActividad, $funcionario);
				insertAlert($funcionario,$modelo["Fecha_Inicio"],$modelo["Detalles"], $data);
		}
		
	}else if($modelo['Id_Grupo'] != 'Todas' && $modelo['Id_Dependencia'] == 'Todas'){
	   
		$query = 'SELECT g.Nombre NOMBRE,  d.Nombre DEPENDENCIA, f.Nombres FUNCIONARIO,f.Identificacion_Funcionario IDENTIFICACION
					FROM Funcionario f
					INNER JOIN Dependencia d ON f.Id_Dependencia = d.Id_Dependencia
					INNER JOIN Grupo g 
					WHERE g.Id_Grupo = '.$modelo['Id_Grupo'].' AND  f.Liquidado = "NO"
					;';
		// echo "Para todo los funcionarios de todas las dependencias de un grupo en particular";
		$consult = new consulta();
		$consult->setQuery($query);
		$consult->setTipo('Multiple');
		$total = $consult->getData();
		foreach($total as $value){
				$data = insertFuncionActividad($idActividad, $funcionario);
				insertAlert($funcionario,$modelo["Fecha_Inicio"],$modelo["Detalles"], $data);
		}
		
	}else if($modelo['Id_Grupo'] == 'Todas' && $modelo['Id_Dependencia'] == 'Todas'){
	    
		$query = 'SELECT DISTINCT f.Nombres FUNCIONARIO,f.Identificacion_Funcionario IDENTIFICACION
					FROM Funcionario f
					INNER JOIN Dependencia d ON f.Id_Dependencia = d.Id_Dependencia
					INNER JOIN Grupo g ON d.Id_Grupo = d.Id_Grupo
					WHERE f.Liquidado = "NO";';
					

		// echo "para todos los funcionarios de todas las dependencias de todos los grupos";
		$consult = new consulta();
		$consult->setQuery($query);
		$consult->setTipo('Multiple');
		$total = $consult->getData();

		foreach($total as $value){
				$data = insertFuncionActividad($idActividad, $funcionario);
				insertAlert($funcionario,$modelo["Fecha_Inicio"],$modelo["Detalles"], $data);
		}
	}


	
	echo json_encode($response);
?>