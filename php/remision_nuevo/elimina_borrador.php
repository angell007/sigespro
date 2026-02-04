<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

$Id_Borrador = ( isset( $_REQUEST['Id_Borrador'] ) ? $_REQUEST['Id_Borrador'] : '' );

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$band = 0;
$borradores_fails = [];

$oItem = new complex("Borrador","Id_Borrador",$Id_Borrador);
$borrador = $oItem->getData();
unset($oItem);

function json_validator($data=NULL) {
    if (!empty($data)) {
        @json_decode($data);
        return (json_last_error() === JSON_ERROR_NONE);
    }
    return false;
}

//$borrador["Texto"]= str_replace("/\\'/g",,$borrador["Texto"],) */
 

 $texto = (array) json_decode($borrador["Texto"],true);
 
$valido = json_validator($borrador["Texto"]);

if($valido){
    $productos = $texto["Productos"];
   
    if ($productos) {
        foreach($productos as $prod){
            $seleccionados = $prod["Lotes_Seleccionados"];
            foreach($seleccionados as $sel){
            
                $oItem = new complex('Inventario_Nuevo',"Id_Inventario_Nuevo",$sel['Id_Inventario_Nuevo']);
                $inv = $oItem->getData();
                
                $seleccionada = number_format($inv["Cantidad_Seleccionada"],0,"","");
                $actual = number_format($sel["Cantidad"],0,"","");
                $fin = $seleccionada - $actual;
                if($fin<0){
                    $fin=0;
                }
                $oItem->Cantidad_Seleccionada=number_format($fin,0,"","");
                $oItem->save();
                unset($oItem);
            }
        }
    }

    $query = 'UPDATE Borrador
    Set Estado = "Eliminado" 
    WHERE Id_Borrador="'.$borrador['Id_Borrador'].'"' ;
    $oCon= new consulta();
    $oCon->setQuery($query);
    $dato = $oCon->createData();
    unset($oCon);

    $resultado['mensaje'] = "Borrador Eliminado Correctamente";
    $resultado['tipo'] = "success";
    }
    else
    {
        $error = json_last_error_msg();
        $resultado['mensaje'] = "Ha ocurrido un error al intentar eliminar el borrador # " . $borrador['Id_Borrador']. ", Error: ".$error;
        $resultado['tipo'] = "error";   
    } 

    echo json_encode($resultado);
?>