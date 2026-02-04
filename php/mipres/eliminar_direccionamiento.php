<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

include_once('../../class/class.mipres.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$mipres= new Mipres();

$oLista = new lista("Producto_Dispensacion_Mipres");
$oLista->setRestrict("Id_Dispensacion_Mipres","=",$id);
$prods = $oLista->getList();
unset($oLista);

foreach($prods as $prod){
    if($prod["IdProgramacion"]!=0){
        $respuesta1= $mipres->AnularProgramacion($prod["IdProgramacion"]);
    }
    $oItem = new complex("Producto_Dispensacion_Mipres","Id_Producto_Dispensacion_Mipres",(INT)$prod["Id_Producto_Dispensacion_Mipres"]);
    $oItem->delete();
    unset($oItem);
}

$oItem = new complex("Dispensacion_Mipres","Id_Dispensacion_Mipres",(INT)$id);
$oItem->delete();
unset($oItem);

$resultado["mensaje"]="Se ha eliminado el Direccionamiento y se Ha anulado su programación!";
$resultado["tipo"]="success";
echo json_encode($resultado);
?>