<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query ='SELECT OCN.*, B.Nombre as Bodega, CONCAT(F.Nombres," ",F.Apellidos) as Funcionario
FROM  Orden_Compra_Nacional OCN
INNER JOIN Bodega B
on B.Id_Bodega=OCN.Id_Bodega
INNER JOIN Funcionario F
on OCN.Identificacion_Funcionario=F.Identificacion_Funcionario' ;
    
$oCon= new consulta();
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);

?>