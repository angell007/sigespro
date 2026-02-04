<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');
	require('../../class/class.guardar_archivos.php');

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
	
	$modelo = (array) json_decode($modelo , true);

	$oItem = new complex('Producto','Id_Producto',$modelo['Id_Producto']);
	$oItem->Estado=$modelo['Estado'];
	$oItem->save();
	unset($oItem);

	$oItem = new complex("Actividad_Producto","Id_Actividad_Producto");
	$oItem->Identificacion_Funcionario=$modelo['Funcionario'];
	$oItem->Id_Producto=$modelo['Id_Producto'];
	$oItem->Detalles="Se le cambio es estado al producto, el nuevo estado es $modelo[Estado]";
	$oItem->save();			
	unset($oItem);

	$resultado['mensaje'] = "Se ha $modelo[Estado] el producto Exitosamente!";
	$resultado['titulo'] = "Operación Exitosa!";
	$resultado['tipo'] = "success";

	echo json_encode($resultado);

?>