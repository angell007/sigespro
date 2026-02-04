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
			$condicion = ' AND PC.Identificacion_Funcionario ='.$funcionario.' ';
		}
	}else{
		if ($funcionario != '') {
			$condicion = ' WHERE PC.Identificacion_Funcionario ='.$funcionario.' ';
		}
	}

	$query = 'SELECT PC.*, F.Imagen, P.Nombre ,
				COALESCE( (SELECT CONCAT("OP",OP.Id_Orden_Pedido ) FROM Orden_Pedido OP WHERE OP.Id_Orden_Pedido = PC.Id_Orden_Pedido) , "") AS Orden_Pedido
	 		  FROM Pre_Compra PC
	        INNER JOIN Funcionario F
			ON PC.Identificacion_Funcionario=F.Identificacion_Funcionario
			INNER JOIN Proveedor P
			ON PC.Id_Proveedor=P.Id_Proveedor'
			.$condicion
			.' AND PC.Estado="Pendiente" ORDER BY PC.Id_Pre_Compra DESC';
	$oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$resultado = $oCon->getData();
	unset($oCon);

	echo json_encode($resultado);
          
?>