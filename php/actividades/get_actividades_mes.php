<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	//date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();
	//$condicion = SetCondiciones($_REQUEST);

	$anio = date('Y');

	$query = '
		SELECT 
			A.*,
			C.Nombre AS NombreCliente, CONCAT("Actividad: ",A.Actividad,", Funcionario: ", (SELECT CONCAT(F.Nombres," ", F.Apellidos) FROM Funcionario F WHERE  F.Identificacion_Funcionario=A.Identificacion_Funcionario)) as Actividad
		FROM Actividad A
		INNER JOIN Cliente C ON A.Id_Cliente = C.Id_Cliente
		WHERE
			 YEAR(Fecha_Inicio) = '.$anio.'
			  AND A.Estado <> "Pendiente" 
		ORDER BY Fecha_Inicio DESC';

	$query_funcionarios = '
		SELECT 
			A.Identificacion_Funcionario
		FROM Actividad A
		INNER JOIN Cliente C ON A.Id_Cliente = C.Id_Cliente
		WHERE
			 YEAR(DATE(Fecha_Inicio)) = '.$anio.'
			  AND A.Estado <> "Pendiente" 
		GROUP BY A.Identificacion_Funcionario';

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $actividades_mes = $queryObj->Consultar('Multiple');

    $queryObj->SetQuery($query_funcionarios);
    $funcionarios = $queryObj->Consultar('Multiple');


    unset($queryObj);

    SetColor();
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
		global $actividades_mes, $funcionarios;

		if (count($actividades_mes['query_result']) > 0) {
			$j = 0;
			foreach ($actividades_mes['query_result'] as $act) {
				
				$fechai_separada = explode(" ", $act['Fecha_Inicio']);
				$fechaf_separada = explode(" ", $act['Fecha_Fin']);
				
				if (count($funcionarios['query_result']) > 0) {
					for ($i=0; $i < count($funcionarios['query_result']); $i++) { 
						
						if (array_search($act['Identificacion_Funcionario'], $funcionarios['query_result'][$i]) !== false) {
							
							$actividades_mes['query_result'][$i]['Color'] = $funcionarios['query_result'][$i]['Color'];
							break;
						}
					}
					/*foreach ($funcionarios['query_result'] as $f) {
						var_dump($f);
						var_dump($act['Identificacion_Funcionario']);
						if (array_search($act['Identificacion_Funcionario'], $f) !== false) {
							
							$actividades_mes['query_result'][$i]['Color'] = $f['Color'];
						}
					}*/
				}

				$fechai_js = $fechai_separada[0]."T".$fechai_separada[1];
				$fechaf_js = $fechaf_separada[0]."T".$fechaf_separada[1];
			
				
				$actividades_mes['query_result'][$j]['Fecha_Inicio'] = $fechai_js;
				$actividades_mes['query_result'][$j]['Fecha_Fin'] =$fechaf_js ;
				$j++;

				
			
			}
		}		
	}

	function SetColor(){
		global $funcionarios;

		if (count($funcionarios['query_result']) > 0) {
			$i = 0;
			foreach ($funcionarios['query_result'] as $act) {
				$funcionarios['query_result'][$i]['Color'] = '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
				$i++;
			}
		}

		
	}
?>