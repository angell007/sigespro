<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT sum(Cantidad) as Cantidad
          FROM Inventario_Nuevo I 
          INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba
          WHERE E.Id_Bodega_Nuevo= '.$id.' 
          GROUP BY Id_Bodega_Nuevo';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon); 

 $mensaje = array();
try {
    //code...
    
    if(count($resultado) > 0){
        $mensaje['title'] ="Eliminación de bodega";
        $mensaje['message']="No se puede inactivar la bodega, ya que todavia contiene productos asociados";
        $mensaje['type']="error";
     
        throw new Exception("No se puede eliminar la bodega, ya que todavia contiene productos asociados");
    }else{
        
        $oItem = new complex('Bodega_Nuevo','Id_Bodega_Nuevo',$id);
        $data = $oItem->getData();

        $oItem->Estado = $data['Estado'] == 'Activo' ? 'Inactivo' : 'Activo';
        $oItem->save();
        
        $mensaje['title'] ="Cambio de estado";
        $mensaje['message']="Se cambió el estado de la bodega satisfactoriamente";
        $mensaje['type']="success";

        echo json_encode($mensaje);
    }
} catch (Exception $th) {
    //throw $th;
    header("HTTP/1.0 400 ".$th->getMessage());
    echo json_encode($mensaje);
}


