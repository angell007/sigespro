<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');

	$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
	$condicion = ' WHERE AR.Estado="Pendiente"';

	if ($condicion != '') {
		if ($funcionario != '') {
			$condicion = ' AND OCN.Identificacion_Funcionario ='.$funcionario.' ';
		}
	}

	$query = "SELECT 
				AR.Id_Acta_Recepcion AS id, 
				AR.Codigo, 
				OCN.Codigo as Codigo_Compra, 
				AR.Fecha_Creacion AS Fecha,
				PRO.Nombre AS Nombre_Proveedor 
			FROM Acta_Recepcion AR 
			INNER JOIN Orden_Compra_Nacional OCN ON AR.Id_Orden_Compra_Nacional=OCN.Id_Orden_Compra_Nacional
			INNER JOIN Proveedor PRO ON AR.Id_Proveedor = PRO.Id_Proveedor 
			".$condicion
			." ORDER BY AR.Fecha_Creacion DESC";

	$oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$resultado = $oCon->getData();
	unset($oCon);

	echo json_encode($resultado);

?>