<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$datos = (array)json_decode($datos,true);


foreach($datos as $dato){

    if((INT)$dato["Id_Inventario"]!=0){
        $oItem = new complex("Inventario","Id_Inventario",(INT)$dato["Id_Inventario"]);
        $actual = $oItem->getData();
        
        $act = number_format($actual["Cantidad_Seleccionada"],0,"","");
        $num = number_format($dato["Cantidad"],0,"","");
        $fin = $act - $num;
        if($fin<0){
            $fin=0;
        }
        $oItem->Cantidad_Seleccionada =  number_format($fin,0,"","");
       // $oItem->save();
        unset($oItem);
    }
   
}


$resultado["Respuesta"]="ok";
echo json_encode($resultado);

?>