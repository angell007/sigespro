<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.consulta.php');

	
    $query = 'SELECT  Id_Zona as value, Nombre as label FROm Zona ORDER BY Nombre ASC ';

    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $zona = $oCon->getData();
    unset($oCon);


echo json_encode($zona);

?>