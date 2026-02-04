<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query='SELECT NC.Estado, COUNT(NC.Estado) as cantidad
FROM No_Conforme NC
WHERE NC.Tipo="Remision"
GROUP BY NC.Estado';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$estado = $oCon->getData();
unset($oCon);


echo json_encode($estado);

?>