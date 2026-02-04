<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_turnero = ( isset( $_REQUEST['id_turneros'] ) ? $_REQUEST['id_turneros'] : '' );


$oItem = new complex("Turneros","Id_Turneros",(INT)$id_turnero);
$oItem->delete();
unset($oItem);

$query="DELETE 
FROM Punto_Turnero 
WHERE Id_Turneros= ".$id_turnero;

$oCon= new consulta();
$oCon->setQuery($query);
$bod = $oCon->deleteData();
unset($oCon);

$resultado["mensaje"]="Se ha eliminado el Tipo de Soporte Correctamente";
$resultado["tipo"]="success";
echo json_encode($resultado);

?>