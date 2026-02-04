<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');


	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');

	//$fecha_inicial = date("Y-m")."-01";
	//$fecha_fin = date("Y-m")."-". date("d",(mktime(0,0,0,date("m")+1,1,date("Y"))-1));
	
	$fecha_inicial = date("m-d");
	$fecha_fin = "12-". date("d",(mktime(0,0,0,date("m")+1,1,date("Y"))-1));
	$fecha_fin2 = date("m-t");

	$condicion = ' WHERE DATE_FORMAT(x.Fecha_Nacimiento, "%m-%d") BETWEEN "'.$fecha_inicial.'" AND "'.$fecha_fin2.'" AND x.Liquidado="NO" AND x.Suspendido="NO"';
	$query_cumpleanos = 'SELECT 
							x.Imagen,
							CONCAT(x.Nombres, " ", x.Apellidos) AS Nombre_Funcionario,
							DATE_FORMAT(x.Fecha_Nacimiento, "%m-%d") as Fecha_N
				        FROM Funcionario x'
				        .$condicion
				        .' ORDER BY DATE_FORMAT(x.Fecha_Nacimiento, "%m-%d") ASC';

    $oCon= new consulta();
	$oCon->setQuery($query_cumpleanos);
	$oCon->setTipo('Multiple');
	$resultado['cumpleaneros'] = $oCon->getData();
	unset($oCon);

	echo json_encode($resultado);
?>