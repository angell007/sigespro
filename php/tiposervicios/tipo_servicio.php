<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query='SELECT TP.*
FROM Tipo_Servicio TP 
WHERE TP.Id_Tipo_Servicio ='.$id; 
$oCon= new consulta();
$oCon->setQuery($query);
$tipo= $oCon->getData();
unset($oCon);

$query='SELECT TS.*
FROM Tipo_Soporte TS
WHERE TS.Id_Tipo_Servicio ='.$id; 
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$soporte= $oCon->getData();
unset($oCon);



$resultado['Tipo']=$tipo;
$resultado['Soporte']=$soporte;

echo json_encode($resultado);

?>