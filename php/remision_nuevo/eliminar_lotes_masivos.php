<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
require_once('../../class/class.configuracion.php');

$queryObj = new QueryBaseDatos();
$response = array();
$http_response = new HttpResponse();

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$datos = json_decode($datos,true);




foreach ($datos as $value) {
    foreach($value['Lotes_Seleccionados'] as $lote){
        $lote = (array)$lote;
        if((INT)$lote["Id_Inventario_Nuevo"]!=0){
          
            $oItem = new complex("Inventario_Nuevo","Id_Inventario_Nuevo",(INT)$lote["Id_Inventario_Nuevo"]);
            $actual = $oItem->getData();
            
            $act = number_format($actual["Cantidad_Seleccionada"],0,"","");
            $num = number_format($lote["Cantidad"],0,"","");
            $fin = $act - $num;
            if($fin<0){
                $fin=0;
            }
            $oItem->Cantidad_Seleccionada =  number_format($fin,0,"","");
            $oItem->save();
            unset($oItem);
        }
       
    }
}



$http_response->SetRespuesta(0, 'Operacion exitosa', 'Se ha borrado la cantidad seleccionada!');
$response = $http_response->GetRespuesta();

echo json_encode($response);




?>