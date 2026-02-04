<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT PR.Id_Proveedor, CONCAT(PR.Nombre," - ",PR.Id_Proveedor) as NombreProveedor
           FROM Proveedor PR' ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$proveedorbucar = $oCon->getData();
unset($oCon);


echo json_encode($proveedorbucar);
          
?>