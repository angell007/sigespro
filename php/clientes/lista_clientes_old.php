<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query='SELECT c.Id_Cliente as Id_Cliente,  c.Nombre as Nombre , c.Direccion as Direccion , d.Nombre as Ciudad, c.Tipo as Tipo
        FROM Cliente c 
        INNER JOIN Departamento d 
        ON d.Id_Departamento = c.Ciudad';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$clientes = $oCon->getData();
unset($oCon);

echo json_encode($clientes);
?>