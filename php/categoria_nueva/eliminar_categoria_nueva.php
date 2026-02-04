<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
/* 
$query = 'SELECT sum(Cantidad) as Cantidad
          FROM Inventario
          WHERE Id_Bodega= '.$id.' 
          GROUP BY Id_Bodega';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon); */

/* $mensaje = array();
if(count($resultado) > 0){
    $mensaje['title'] ="Eliminacion de bodega";
    $mensaje['text']="No se puede eliminar la bodega, ya que todavia contiene productos asociados";
    $mensaje['type']="error";
}else{
     */
    $oItem = new complex('Categoria_Nueva','Id_Categoria_Nueva',$id);
    $oItem->delete();
    $mensaje['title'] ="Eliminacion de categoría";
    $mensaje['text']="Categoría eliminada conrrectamente";
    $mensaje['type']="success";
//}

echo json_encode($mensaje);

