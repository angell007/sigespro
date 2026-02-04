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

	$id_punto= ( isset( $_REQUEST['id_punto'] ) ? $_REQUEST['id_punto'] : '' );
	$func = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
	   
	$turnos= GetTurnos($id_punto, $func);

	echo json_encode($turnos);

	function GetTurnos($idPunto, $func){
		global $queryObj;

		$query="
			SELECT 
				A.Id_Auditoria, 
				CONCAT(P.Primer_Nombre,' ',P.Segundo_Nombre,' ',P.Primer_Apellido,' ',P.Segundo_Apellido) as NombrePaciente, 
				P.Id_Paciente, 
				IFNULL(A.Archivo, '') as Archivo, 
				IFNULL(A.Id_Servicio,'') as Id_Servicio,
				IFNULL(A.Id_Tipo_Servicio,'') as Id_Tipo_Servicio 
			FROM Auditoria A
			INNER JOIN Paciente P ON A.Id_Paciente=P.Id_Paciente 
			WHERE 
				A.Punto_Pre_Auditoria = $idPunto 
				AND A.Origen = 'Dispensador' 
				AND A.Dispensador_Preauditoria = $func 
				AND A.Id_Dispensacion IS NULL";

		$queryObj->SetQuery($query);
		$turnos = $queryObj->ExecuteQuery('Multiple');

		return $turnos;
	}




?>