<?php
 header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json'); 

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');



$id = ( isset( $_REQUEST['punto'] ) ? $_REQUEST['punto'] : '' );


$query = 'SELECT PD.Wacom
FROM  Punto_Dispensacion PD 
WHERE PD.Id_Punto_Dispensacion='.$id;

$oCon= new consulta();
$oCon->setQuery($query);
$punto = $oCon->getData();
unset($oCon);

echo json_encode($punto);

?>