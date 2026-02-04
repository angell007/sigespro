<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$query = '(
    SELECT C.Id_Cliente AS ID, IF(Nombre IS NULL OR Nombre = "", CONCAT_WS(" ", C.Id_Cliente,"-",Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido), CONCAT(C.Id_Cliente, " - ", C.Nombre)) AS Nombre, "Cliente" AS Tipo FROM Cliente C WHERE C.Estado != "Inactivo") 
    UNION (SELECT P.Id_Proveedor AS ID, IF(P.Nombre = "" OR P.Nombre IS NULL, CONCAT_WS(" ",P.Id_Proveedor,"-",P.Primer_Nombre,P.Segundo_Nombre,P.Primer_Apellido,P.Segundo_Apellido),CONCAT(P.Id_Proveedor, " - ", P.Nombre)) AS Nombre, "Proveedor" AS Tipo FROM Proveedor P WHERE P.Estado != "Inactivo") 
    UNION (SELECT F.Identificacion_Funcionario AS ID, CONCAT(F.Identificacion_Funcionario, " - ", F.Nombres," ", F.Apellidos) AS Nombre, "Funcionario" AS Tipo FROM Funcionario F) 
    UNION (SELECT CC.Nit AS ID, CONCAT(CC.Nit, " - ", CC.Nombre) AS Nombre, "Caja_Compensacion" AS Tipo FROM Caja_Compensacion CC WHERE CC.Nit IS NOT NULL
)' ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$proveedorbucar = $oCon->getData();
unset($oCon);


echo json_encode($proveedorbucar);
          
?>