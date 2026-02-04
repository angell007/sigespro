<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');

	$meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

	$dias=array();
	$month = date("m");
	$day = date("d");
	$result = array();	
	$year = date("Y");
	$j=-1;

	$total_llegadas = GetTotalLlegadasTardeMensual();
	$resultado['Total_llegadas_mensual'] = $total_llegadas;

	$fecha_fin = date("y-m-d");		
	$fecha=strtotime ( '-15 days' , strtotime ( $fecha_fin) ) ;
	$fecha_inicial= date('Y-m-d', $fecha);

	for($d=strtotime($fecha_inicial); $d<=strtotime($fecha_fin); $d=strtotime ('+ 1 days' ,$d))
	{
		$j++;
	    
		$dia = date('d', $d);
		$dias[$j]['dia']= $dia;

	    $fecha_ini = date("Y-m-d",$d);
	    $condicion = ' WHERE x.Fecha = "'.$fecha_ini.'"';

	    $query_llegadas_tarde = 'SELECT 
								COUNT(*) AS cantidad_llegadas_tarde
					        FROM Llegada_Tarde x'
					        .$condicion;

	    $oCon= new consulta();
		$oCon->setQuery($query_llegadas_tarde);
		$oCon->setTipo('Multiple');
		$dias[$j]['llegadas_tarde'] = $oCon->getData();
		$dias[$j]['llegadas_tarde'] = count($dias[$j]['llegadas_tarde']) > 0 ? $dias[$j]['llegadas_tarde'][0]['cantidad_llegadas_tarde'] : $dias[$j]['llegadas_tarde'];
		unset($oCon);
	}

	$resultado['llegadas_count'] = $dias;

	/*$fecha_inicial = date("Y-m")."-01";
	$fecha_fin = date("Y-m")."-". date("d",(mktime(0,0,0,date("m")+1,1,date("Y"))-1));*/

	$condicion_fecha = ' WHERE x.Fecha BETWEEN "'.$fecha_inicial.'" AND "'.$fecha_fin.'"';

	$query_llegadas_tarde_funcionario = 'SELECT 
											xa.Identificacion_Funcionario,
											CONCAT(xa.Nombres, " ", xa.Apellidos) AS Nombre_Funcionario,
											xa.Imagen,
											COUNT(x.Id_Llegada_Tarde) AS Cantidad_Llegadas
										FROM Llegada_Tarde x
										INNER JOIN Funcionario xa ON x.Identificacion_Funcionario = xa.Identificacion_Funcionario'
										.$condicion_fecha
										.' GROUP BY x.Identificacion_Funcionario ORDER BY Cantidad_LLegadas DESC LIMIT 6';

	$oCon= new consulta();
	$oCon->setQuery($query_llegadas_tarde_funcionario);
	$oCon->setTipo('Multiple');
	$resultado['funcionarios_llegadas_tarde'] = $oCon->getData();
	unset($oCon);

	echo json_encode($resultado);

	function CalcularMesAnterior($mesActual){
		$mesAnterior = $mesActual - 1;

		if ($mesAnterior == 0) {
			return 12;
		}else{
			return $mesAnterior;
		}
	}

	function NombreMes($mes){
		global $meses;

		return $meses[$mes];
	}

	function GetTotalLlegadasTardeMensual(){
		$fecha_fin = date("y-m-d");		
		$fecha=strtotime ( '-15 days' , strtotime ( $fecha_fin) ) ;
		$fecha_inicial= date('Y-m-d', $fecha);
		$total = array();
		$condicion_total = ' WHERE x.Fecha BETWEEN "'.$fecha_inicial.'" AND "'.$fecha_fin.'"';

	    $query_total_llegadas = 'SELECT 
								COUNT(*) AS cantidad_llegadas_tarde_mensual
					        FROM Llegada_Tarde x'
					        .$condicion_total;

	    $oCon= new consulta();
		$oCon->setQuery($query_total_llegadas);
		$oCon->setTipo('Multiple');
		$total = $oCon->getData();
		if (count($total[0]['cantidad_llegadas_tarde_mensual']) > 0) {
			
			$total = $total[0]['cantidad_llegadas_tarde_mensual'];
		}

		unset($oCon);

		return $total;
	}
?>