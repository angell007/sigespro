<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$query = '(SELECT CONCAT(C.Id_Cliente, " - ", C.Nombre) AS Nombre, C.Id_Cliente AS ID, "Cliente" AS Tipo FROM Cliente C WHERE C.Estado != "Inactivo") UNION (SELECT IF(P.Nombre = "" OR P.Nombre IS NULL, CONCAT_WS(" ",P.Id_Proveedor,"-",P.Primer_Nombre,P.Segundo_Nombre,P.Primer_Apellido,P.Segundo_Apellido),CONCAT(P.Id_Proveedor, " - ", P.Nombre)) AS Nombre, P.Id_Proveedor AS ID, "Proveedor" AS Tipo FROM Proveedor P WHERE P.Estado != "Inactivo") UNION (SELECT CONCAT(F.Identificacion_Funcionario, " - ", F.Nombres," ", F.Apellidos) AS Nombre, F.Identificacion_Funcionario AS ID, "Funcionario" AS Tipo FROM Funcionario F) UNION (SELECT CONCAT(CC.Nit, " - ", CC.Nombre) AS Nombre, CC.Nit AS ID, "Caja_Compensacion" AS Tipo FROM Caja_Compensacion CC WHERE CC.Nit IS NOT NULL)' ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$proveedorbucar = $oCon->getData();
unset($oCon);


echo json_encode($proveedorbucar);
          
?>