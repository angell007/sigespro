<?php 
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$Id_BHV = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
//$Id_BHV = $datos['id'];

    

    try {
        //code...
        $oItem = new complex("Banco_Hoja_Vida","Id_Banco_Hoja_Vida",$Id_BHV);
        $oItem->Estado = 'Inactivo';
        $oItem->save();
        unset($oItem);

        $mensaje['title']   = "Eliminacion de Hoja de Vida";
        $mensaje['message'] = "Se Elimino la Hoja de Vida satisfactoriamente";
        $mensaje['type']    = "success";

        echo json_encode($mensaje);
        
    } catch (Exception $th) {
        $mensaje['title']   = "Eliminación de Hoja de Vida";
        $mensaje['message'] = "No se puede Eliminar la bodega";
        $mensaje['type']    = "error";
        //throw $th;
        header("HTTP/1.0 400 ".$th->getMessage());
        echo json_encode($mensaje);
    }

?>