<?php
 header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json'); 

require_once('../../config/start.inc.php');
include_once('../../class/class.consulta.php');



$query = 'SELECT  CONCAT(Codigo," - ",Concepto) as Nombre,Codigo,Id_Codigo_General_Glosa,Concepto
FROM Codigo_General_Glosa ';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$codigos = $oCon->getData();
unset($oCon);

echo json_encode($codigos);

?>