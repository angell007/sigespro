<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query='SELECT M.Nombre as Ciudad, p.Id_Proveedor , p.Nombre, p.Direccion, p.Celular , p.Correo , p.Regimen
        FROM Proveedor p 
        INNER JOIN Municipio M
        ON p.Ciudad=M.Id_Municipio';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$proveedores = $oCon->getData();
unset($oCon);

echo json_encode($proveedores);
?>