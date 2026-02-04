<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = '(SELECT C.Id_Cliente, CONCAT(C.Id_Cliente," - ", C.Nombre) as Nombre_Find, C.Nombre ,  C.Condicion_Pago
FROM Cliente C WHERE C.Estado != "Inactivo") 
';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$clientes = $oCon->getData();
unset($oCon); 

echo json_encode($clientes);
?>