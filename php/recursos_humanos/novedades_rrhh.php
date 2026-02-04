<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');

	$fecha_inicial = date("y-m")."-01";
	$fecha_fin = date("y-m-t");

	$total = 0;
	//$porcentajes = array('Vacaciones' => 0, 'Incapacidades' => 0, 'Permisos' => '', 'Licencias' => '', 'Horas_Extras' => '', 'Recargos' => '');

	$porcentajes = array();
	$totales_novedades = array();

	$query_novedad = 'SELECT 
						COUNT(*) AS Total_Novedades
					FROM Novedad t1
					WHERE
						CAST(t1.Fecha_Inicio AS DATE) >="'.$fecha_inicial
						.'" AND CAST(t1.Fecha_Fin AS DATE) <="'.$fecha_fin
						.'" LIMIT 1';

	/*$query = "SELECT D.Nombre AS Dependencia, 
				COUNT(N.Id_Novedad) AS Cantidad, 
				(SELECT COUNT(*) FROM Novedad) AS Total 
			FROM Novedad N 
			INNER JOIN Funcionario F ON N.Identificacion_Funcionario = F.Identificacion_Funcionario 
			INNER JOIN Dependencia D ON F.Id_Dependencia = D.Id_Dependencia GROUP BY F.Id_Dependencia" ;*/

	$oCon= new consulta();
	$oCon->setQuery($query_novedad);
	$oCon->setTipo('Multiple');
	$resultado['Grafica']['Total_Novedades'] = $oCon->getData();
	$resultado['Grafica']['Total_Novedades'] = $resultado['Grafica']['Total_Novedades'][0]['Total_Novedades'];
	unset($oCon);

	$query_tipo_novedad = 'SELECT 
						Tipo_Novedad
					FROM Tipo_Novedad
					group by Tipo_Novedad
					order by Tipo_Novedad ASC';

	$oCon= new consulta();
	$oCon->setQuery($query_tipo_novedad);
	$oCon->setTipo('Multiple');
	$resultado['Grafica']['Tipos_Novedades'] = $oCon->getData();
	unset($oCon);

	/*foreach ($resultado['Grafica'] as $i => $value) {
	    $resultado['Grafica'][$i]['porcentaje'] = round(($resultado['Grafica'][$i]['Cantidad']/$resultado['Grafica'][$i]['Total'])*100,2);
	}*/

	$query_totales_tipo_novedad = "SELECT
				(SELECT COUNT(*) 
					 FROM Novedad N 
					 INNER JOIN Tipo_Novedad TN ON N.Id_Tipo_Novedad = TN.Id_Tipo_Novedad 
					 WHERE TN.Tipo_Novedad = 'Hora_Extra'
						AND CAST(N.Fecha_Inicio AS DATE) >='".$fecha_inicial
						."' AND CAST(N.Fecha_Fin AS DATE) <='".$fecha_fin
						."') AS Horas_Extras, 
				(SELECT COUNT(*) 
					FROM Novedad N 
					INNER JOIN Tipo_Novedad TN ON N.Id_Tipo_Novedad = TN.Id_Tipo_Novedad 
					WHERE TN.Tipo_Novedad = 'Incapacidad'
						AND CAST(N.Fecha_Inicio AS DATE) >='".$fecha_inicial
						."' AND CAST(N.Fecha_Fin AS DATE) <='".$fecha_fin
						."') AS Incapacidades,  
				 (SELECT COUNT(*) 
					 FROM Novedad N 
					 INNER JOIN Tipo_Novedad TN ON N.Id_Tipo_Novedad = TN.Id_Tipo_Novedad 
					 WHERE TN.Tipo_Novedad = 'Licencia'
						AND CAST(N.Fecha_Inicio AS DATE) >='".$fecha_inicial
						."' AND CAST(N.Fecha_Fin AS DATE) <='".$fecha_fin
						."') AS Licencias, 
				(SELECT COUNT(*)
					 FROM Novedad N 
					 INNER JOIN Tipo_Novedad TN ON N.Id_Tipo_Novedad = TN.Id_Tipo_Novedad 
					 WHERE TN.Tipo_Novedad = 'Permiso'
					 	AND CAST(N.Fecha_Inicio AS DATE) >='".$fecha_inicial
						."' AND CAST(N.Fecha_Fin AS DATE) <='".$fecha_fin
						."') AS Permisos,
				(SELECT COUNT(*) 
					 FROM Novedad N 
					 INNER JOIN Tipo_Novedad TN ON N.Id_Tipo_Novedad = TN.Id_Tipo_Novedad 
					 WHERE TN.Tipo_Novedad = 'Recargo'
						AND CAST(N.Fecha_Inicio AS DATE) >='".$fecha_inicial
						."' AND CAST(N.Fecha_Fin AS DATE) <='".$fecha_fin
						."') AS Recargos,
				(SELECT COUNT(*)
					FROM Novedad N 
					INNER JOIN Tipo_Novedad TN ON N.Id_Tipo_Novedad = TN.Id_Tipo_Novedad 
					WHERE TN.Tipo_Novedad = 'Vacaciones'  
						AND CAST(N.Fecha_Inicio AS DATE) >='".$fecha_inicial
						."' AND CAST(N.Fecha_Fin AS DATE) <='".$fecha_fin
						."') AS Vacaciones";

	$oCon= new consulta();
	$oCon->setQuery($query_totales_tipo_novedad);
	$oCon->setTipo('Multiple');
	$totales_novedades = $oCon->getData();
	unset($oCon);

	PorcentajeNovedades();

	$resultado['Grafica']['Porcentajes'] = $porcentajes;

	echo json_encode($resultado);
	//echo json_encode($porcentajes_a);

	function PorcentajeNovedades(){
		global $resultado, $totales_novedades, $porcentajes;

		$total = intval($resultado['Grafica']['Total_Novedades'][0]);
		$index = 0;

		foreach ($totales_novedades[0] as $tn => $valor) {
			if($total>0){
				$porcentaje_tipo_novedad = strval((intval($valor) * 100) / $total);
			}else{
				$porcentaje_tipo_novedad=0;
			}
		

			/*if ($index == 0) {
				$porcentajes['Vacaciones'] = $porcentaje_tipo_novedad;;
			}else if ($index == 1) {
				$porcentajes['Incapacidades'] = $porcentaje_tipo_novedad;;
			}else if ($index == 2) {
				$porcentajes['Permisos'] = $porcentaje_tipo_novedad;;
			}else if ($index == 3) {
				$porcentajes['Licencias'] = $porcentaje_tipo_novedad;;
			}else if ($index == 4) {
				$porcentajes['Horas_Extras'] = $porcentaje_tipo_novedad;;
			}else if ($index == 5) {
				$porcentajes['Recargos'] = $porcentaje_tipo_novedad;;
			}*/

			$porcentajes[$index] = $porcentaje_tipo_novedad;
			$index++;
		}
	}
?>