<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');

	$query = 'SELECT Id_Proveedor, IF(Nombre = "" OR Nombre IS NULL,CONCAT_WS(" ",Id_Proveedor,"-",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido),CONCAT_WS(" ",Id_Proveedor,"-",Nombre)) AS NombreProveedor
	FROM Proveedor UNION (SELECT F.Identificacion_Funcionario AS Id_Proveedor, CONCAT(CONCAT_WS(" ",F.Nombres,F.Apellidos)," - ",F.Identificacion_Funcionario) AS NombreProveedor FROM Funcionario F)';

	$oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$proveedores = $oCon->getData();
	unset($oCon);

	echo json_encode($proveedores);
?>