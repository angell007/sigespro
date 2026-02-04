<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['Id'] ) ? $_REQUEST['Id'] : '' );
 
$query = "SELECT Ale.Id_Alerta, Fun.Identificacion_Funcionario, CONCAT(Fun.Nombres, ' ', Fun.Apellidos) Nombres, Ale.Tipo Tipo, Ale.Fecha, Ale.Detalles
            FROM Alerta Ale
            inner join Funcionario Fun on Ale.Identificacion_Funcionario = Fun.Identificacion_Funcionario
            WHERE Ale.Tipo = 'Auditoria' and Ale.Identificacion_Funcionario = $id ";
$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado["Listado"] = $oCon->getData();
unset($oCon);

echo json_encode($resultado);