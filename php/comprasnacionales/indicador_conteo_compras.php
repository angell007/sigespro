<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');

	$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

	$condicion = '';

	if ($condicion != '') {
		if ($funcionario != '') {
			$condicion = ' AND OCN.Identificacion_Funcionario ='.$funcionario.' ';
		}
	}else{
		if ($funcionario != '') {
			$condicion = ' WHERE OCN.Identificacion_Funcionario ='.$funcionario.' ';
		}
	}

	$query = 'SELECT 
				COUNT(*) AS pendientes
			FROM Orden_Compra_Nacional OCN
			WHERE OCN.Aprobacion="Pendiente" AND OCN.Identificacion_Funcionario ='.$funcionario.'  ORDER BY OCN.Fecha DESC';//pendientes

	$oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$resultado['pendientes'] = $oCon->getData();
	$resultado['pendientes'] = $resultado['pendientes'][0]['pendientes'];
	unset($oCon);

	$query = 'SELECT 
				COUNT(*) AS aprobadas
			FROM Orden_Compra_Nacional OCN
			WHERE OCN.Aprobacion="Aprobada" AND OCN.Identificacion_Funcionario ='.$funcionario.'  ORDER BY OCN.Fecha DESC';//aprobadas

	$oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$resultado['aprobadas'] = $oCon->getData();
	$resultado['aprobadas'] = $resultado['aprobadas'][0]['aprobadas'];
	unset($oCon);

	$query = 'SELECT 
				COUNT(*) AS recibidas
			FROM Orden_Compra_Nacional OCN
			WHERE OCN.Estado = "Recibida" AND OCN.Identificacion_Funcionario ='.$funcionario.'  ORDER BY OCN.Fecha DESC';//recibidas

	$oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$resultado['recibidas'] = $oCon->getData();
	$resultado['recibidas'] = $resultado['recibidas'][0]['recibidas'];
	unset($oCon);

	echo json_encode($resultado);
          
?>