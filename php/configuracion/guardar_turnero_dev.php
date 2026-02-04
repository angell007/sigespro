<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');

	$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
	$puntos = ( isset( $_REQUEST['puntos'] ) ? $_REQUEST['puntos'] : '' );
	$servicios = ( isset( $_REQUEST['servicios'] ) ? $_REQUEST['servicios'] : '' );

	$datos = (array) json_decode($datos);
	$puntos=(array) json_decode($puntos, true );
	$servicios=(array) json_decode($servicios);

	$oItem = new complex("Turneros","Id_Turneros");
	$oItem->Nombre=strtoupper($datos["Nombre"]);
	$oItem->Direccion=strtoupper($datos["Direccion"]);
	$oItem->Capita=$datos["Capita"];
	$oItem->No_Pos=$datos["No_Pos"];
	$oItem->save();
	$id_turnero = $oItem->getId();
	unset($oItem);

	foreach ($puntos as  $value) {
	    $oItem = new complex("Punto_Turnero","Id_Punto_Turnero");
	    $oItem->Id_Punto_Dispensacion=$value;
	    $oItem->Id_Turneros=$id_turnero;
	    $oItem->Capita=$datos["Capita"];
	    $oItem->No_Pos=$datos["No_Pos"];
	    $oItem->save();
	    unset($oItem);

	}

    GuardarServiciosTunero($servicios, $id_turnero);

	$resultado['mensaje']="Tunero creado Correctamente";
	$resultado['tipo']="success";

	echo json_encode($resultado);

	function GuardarServiciosTunero($servicios, $idTunero){

		foreach ($servicios as $service) {
			$oItem= new complex("Servicio_Turnero","Id_Servicio_Turnero");
		    $oItem->Id_Turnero =$idTunero;
		    $oItem->Id_Servicio =$service;
	    	$oItem->save();
		    unset($oItem);
		}
	}

?>