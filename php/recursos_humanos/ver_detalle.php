<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');



	$funcionario = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
	$fecha_inicial = isset($_REQUEST['inicio']) ? $_REQUEST['inicio'] : false;
	$fecha_final = isset($_REQUEST['fin']) ? $_REQUEST['fin'] : false;
	

	$query = "SELECT DF.*, 
	 DATE_FORMAT(DF.Fecha, '%W' ) as Dia,
	Concat_ws(' ', F.Nombres, F.Apellidos) as Funcionario
	
	FROM Diario_Fijo DF 
	INNER JOIN Funcionario F on F.Identificacion_Funcionario = DF.Identificacion_Funcionario
	WHERE DF.Identificacion_Funcionario like '%$funcionario%' AND  DF.Fecha BETWEEN '$fecha_inicial' AND '$fecha_final'
	Order by DF.Fecha asc, DF.Hora_Entrada1 asc	";

	$oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$llegadas = $oCon->getData();
	unset($oCon);

	

	echo json_encode($llegadas);

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

	
?>