<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../../config/start.inc.php');
	include_once('../../../class/class.querybasedatos.php');
	include_once('../../../class/class.paginacion.php');
	include_once('../../../class/class.http_response.php');

	$http_response = new HttpResponse();
	//$condicion = SetCondiciones($_REQUEST);

	$anio = date('Y');
    
    $query = '
		SELECT 
			A.*, TA.Nombre as Tipo_Actividad_Recursos_Humanos, D.Nombre NombreDependencia,
			IF (A.Estado = "Anulada","#FF5370",TA.Color) AS Color, 
			IF (A.Estado = "Anulada", CONCAT(A.Actividad_Recursos_Humanos," (ANULADA)"),
									  CONCAT(A.Actividad_Recursos_Humanos," (",TA.Nombre,") ")) as Actividad_Recursos_Humanos
		FROM Actividad_Recursos_Humanos A
		INNER JOIN Tipo_Actividad_Recursos_Humanos TA ON TA.Id_Tipo_Actividad_Recursos_Humanos = A.Id_Tipo_Actividad_Recursos_Humanos
		LEFT JOIN Dependencia D ON A.Id_Dependencia = D.Id_Dependencia
		WHERE
			  YEAR(DATE(Fecha_Inicio)) = '.$anio.'
			  AND A.Estado <> "Pendiente"
		ORDER BY Fecha_Inicio DESC';

	// echo $query; exit;
	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $actividades_mes = $queryObj->Consultar('Multiple');

    unset($queryObj);

    TransformFechaToJsDate();

	echo json_encode($actividades_mes);

	function SetCondiciones($req){
		$condicion = '';

        if (isset($req['mes']) && $req['mes']) {
            if ($condicion != "") {
                $condicion .= " AND MONTH(DATE(Fecha_Inicio)) = ".$req['mes'];
            } else {
                $condicion .= " WHERE MONTH(DATE(Fecha_Inicio)) = ".$req['mes'];
            }
        }

        return $condicion;
	}

	function TransformFechaToJsDate(){
		global $actividades_mes;

		if (count($actividades_mes['query_result']) > 0) {
			$i = 0;
			foreach ($actividades_mes['query_result'] as $act) {
				
				$fechai_separada = explode(" ", $act['Fecha_Inicio']);
				$fechaf_separada = explode(" ", $act['Fecha_Fin']);
				
				$fechai_js = $fechai_separada[0]."T".$fechai_separada[1];
				$fechaf_js = $fechaf_separada[0]."T".$fechaf_separada[1];
				$actividades_mes['query_result'][$i]['Fecha_Inicio'] = $fechai_js;
				$actividades_mes['query_result'][$i]['Fecha_Fin']    = $fechaf_js;
				$i++;
			}
		}		
	}

?>