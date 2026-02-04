<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');
    include_once('../../class/class.complex.php');
    include_once('../../class/class.consulta.php');

	$id = ( isset( $_REQUEST['Id'] ) ? $_REQUEST['Id'] : '' );

    $query = "SELECT CONCAT(F.Nombres, ' ', F.Apellidos) AS Nombre_Funcionario, AR.Fecha_Actividad Fecha, AR.Detalle, AR.Estado
                FROM Actividad_Radicado AR
                INNER JOIN Funcionario F ON AR.Id_Funcionario = F.Identificacion_Funcionario
                WHERE Id_Radicado = $id
                ORDER BY Id_Actividad_Radicado DESC";
    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $datos = $oCon->getData();
    unset($oCon);

echo json_encode($datos);
