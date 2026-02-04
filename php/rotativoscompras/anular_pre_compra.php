<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'DELETE 
FROM Pre_Compra
WHERE Id_Pre_Compra='.$id;
$oCon= new consulta();
$oCon->setQuery($query);
$dato = $oCon->deleteData();
unset($oCon);



$query2 = 'DELETE   FROM Producto_Pre_Compra  
           WHERE Id_Pre_Compra ='.$id ;

$oCon= new consulta();
$oCon->setQuery($query2);
$dato = $oCon->deleteData();
unset($oCon);;


$resultado["mensaje"]="Se ha eliminado correctamente la Pre-Compra";
$resultado["type"]="success";

echo json_encode($resultado);
          
?>