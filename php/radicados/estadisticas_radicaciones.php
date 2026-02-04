<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	//date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.configuracion.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.querybasedatos.php');

	$http_response = new HttpResponse();
	$queryObj = new QueryBaseDatos();
	$response = array();

	$backgroundColors = ['#4286f4', '#266ee2', '#1251b7', '#187299', '#4d9bbc', '#115977', '#054b68', '#12a0db', '#1e348a', '#17e6b2'];

    $query_estadistica_radicado = '
    	SELECT
    		(SELECT COUNT(Id_Radicado) FROM Radicado WHERE LOWER(Tipo_Servicio) = "evento") AS Radicados_Evento,
    		(SELECT COUNT(Id_Radicado) FROM Radicado WHERE LOWER(Tipo_Servicio) = "capita") AS Radicados_Capita,
    		(SELECT COUNT(Id_Radicado) FROM Radicado WHERE LOWER(Tipo_Servicio) = "mipres") AS Radicados_Mipres,
    		(SELECT COUNT(Id_Radicado) FROM Radicado WHERE LOWER(Tipo_Servicio) = "tutela") AS Radicados_Tutela,
    		(SELECT COUNT(Id_Radicado) FROM Radicado WHERE LOWER(Tipo_Servicio) = "ctc") AS Radicados_Ctc,
    		(SELECT COUNT(Id_Radicado) FROM Radicado WHERE LOWER(Tipo_Servicio) = "cohortes") AS Radicados_Cohortes,
    		(SELECT COUNT(Id_Radicado) FROM Radicado WHERE LOWER(Tipo_Servicio) = "todos") AS Radicados_Todo_Tipo,
    		(SELECT COUNT(Id_Radicado) FROM Radicado WHERE LOWER(Tipo_Servicio) = "mipres subsidiado") AS Radicados_Mipres_Subsidiado,
    		(SELECT COUNT(Id_Radicado) FROM Radicado) AS Radicados_Total,
    		(SELECT COUNT(Id_Radicado_Factura) FROM Radicado_Factura) AS Facturas_Radicadas,
    		(SELECT COUNT(Id_Factura) FROM Factura wHERE Estado_Factura = "Facturada" AND Estado_Radicacion = "Pendiente") AS Facturas_Facturadas';

	$queryObj->SetQuery($query_estadistica_radicado);
	$estadistica = $queryObj->Consultar('simple');

	//var_dump($estadistica);
	$chart_data = ArmarChartDataRadicaciones2($estadistica['query_result']);
	$leyenda = ArmarLeyendaChart($estadistica['query_result']);
	$conteo_facturas = array('radicadas' => $estadistica['query_result']['Facturas_Radicadas'], 'facturadas' => $estadistica['query_result']['Facturas_Facturadas'], 'total_radicaciones' => $estadistica['query_result']['Radicados_Total']);

	$data = array('chartData' => $chart_data, 'conteo_facturas' => $conteo_facturas, 'leyenda' => $leyenda);

    $http_response->SetRespuesta(0, 'Datos Encontrados', 'Se han encontrado estadisticas!');
    $http_response->SetDatosRespuesta($data);
    $response = $http_response->GetRespuesta();

    unset($http_response);
    unset($queryObj);

	echo json_encode($response);

	function ArmarChartDataRadicaciones($datosRadicaciones){
		global $backgroundColors;

		$datasets = array();
		$labels = array();

		$i = 0;
		foreach ($datosRadicaciones as $key => $value) {

			if (strpos($key, 'Radicado') !== false) {
				array_push($labels, str_replace("_", " ", $key));
				
				$dataObj =  array();
				$dataObj['data'] = [$value];
				$dataObj['backgroundColor'] = $backgroundColors[$i];
				$dataObj['label'] = str_replace("_", " ", $key);
				array_push($datasets, $dataObj);

				$i++;
			}			
		}

		$data = array('datasets' => $datasets, 'labels' => $labels);

		return $data;
	}

	function ArmarChartDataRadicaciones2($datosRadicaciones){
		global $backgroundColors;

		$datasets = array();
		$labels = array();
		$dataObj =  array();

		$i = 0;
		foreach ($datosRadicaciones as $key => $value) {

			if ($key != 'Radicados_Total' && $key != 'Facturas_Radicadas' && $key != 'Facturas_Facturadas') {				

				if (strpos($key, 'Radicado') !== false) {
					array_push($labels, str_replace("_", " ", $key));
					$dataObj['data'][$i] = $value;
					$dataObj['backgroundColor'][$i] = $backgroundColors[$i];

					$i++;
				}			
			}
		}

		$dataObj['borderWidth'] = '0';

		$data = array('datasets' => array($dataObj), 'labels' => $labels);

		return $data;
	}

	function ArmarLeyendaChart($datosRadicaciones){
		$leyenda = array();
		foreach ($datosRadicaciones as $key => $value) {

			if ($key != 'Radicados_Total') {				

				if (strpos($key, 'Radicado') !== false) {

					if ($value != '0') {

						$leyenda[] = array('nombre' => str_replace("_", " ", $key), 'porcentaje' => CalcularPorcentaje($datosRadicaciones['Radicados_Total'], $value));
					}
				}			
			}
		}

		return $leyenda;
	}

	function CalcularPorcentaje($total, $parcial){

		$calculo = number_format((floatval($parcial)/floatval($total))*100, 2, ",", "");
		return $calculo;
	}
?>