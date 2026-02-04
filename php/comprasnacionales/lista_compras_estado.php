<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');
	include_once('../../class/class.querybasedatos.php');

	$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
	$estado = ( isset( $_REQUEST['estado'] ) ? $_REQUEST['estado'] : '' );


	$condicion_func = '';

	if ($funcionario != '') {
		$condicion_func = "  AND F.Identificacion_Funcionario =".$funcionario;
	}

	$query = 'SELECT 
				OCN.*, 
				P.Nombre as Proveedor, 
				F.Imagen 
			FROM Orden_Compra_Nacional OCN
			INNER JOIN Proveedor P ON OCN.Id_Proveedor=P.Id_Proveedor 
			INNER JOIN Funcionario F ON OCN.Identificacion_Funcionario=F.Identificacion_Funcionario
			WHERE OCN.Aprobacion="'.$estado.'" '.$condicion_func.'  ORDER BY OCN.Fecha DESC';


	$oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$resultado = $oCon->getData();
	unset($oCon);


	echo json_encode($resultado);

?>