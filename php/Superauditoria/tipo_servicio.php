<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.consulta.php');

	
    $query = 'SELECT  Id_Tipo_Servicio, Nombre FROM Tipo_Servicio WHERE Nombre NOT LIKE "COHORTES" AND Nombre NOT LIKE "Evento" ';

    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $servicios = $oCon->getData();
    unset($oCon);


echo json_encode($servicios);

?>