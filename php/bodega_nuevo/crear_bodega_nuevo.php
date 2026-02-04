<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
include_once('../../class/class.consulta.php');
include_once('../../class/class.complex.php');


$mapa = isset($_FILES['mapa']) ? $_FILES['mapa'] : false;
$bodega = isset($_REQUEST['bodega']) ? $_REQUEST['bodega'] : false;
$tipo = isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : false;
$bodega = json_decode($bodega,true);

$file_path = __DIR__.'/../../IMAGENES/MAPABODEGA/';
$extensions = array("jpg","png","jpeg");
try {
    if($tipo=='Editar'){
        $oItem = new complex('Bodega_Nuevo','Id_Bodega_Nuevo',$bodega['Id_Bodega_Nuevo']);
        $bodegaDB = $oItem->getData();
    }else{
        $oItem = new complex('Bodega_Nuevo','Id_Bodega_Nuevo');
    }

    if($mapa){
       validarMapa();
       $temp_mapa = $mapa['tmp_name'];
       
        if ($tipo=='Editar') {
           eliminarImagenActual($bodegaDB);
        }   

        $nombre_mapa= generarNombre($mapa);
        move_uploaded_file($temp_mapa, $file_path . '/' . $nombre_mapa);
        $oItem->Mapa = $nombre_mapa;
    }

    $oItem->Nombre = $bodega['Nombre'];
    $oItem->Direccion = $bodega['Direccion'];
    $oItem->Telefono = $bodega['Telefono'];
    $oItem->Compra_Internacional = $bodega['Compra_Internacional'];
    $oItem->Tipo = $bodega['Tipo'];
    
    $oItem->save();
    unset($oItem);
    echo json_encode(['message'=>'Operación exitosa']);

} catch (Exception $th) {
    //throw $th;
    header("HTTP/1.0 400 ".$th->getMessage());
    echo json_encode(['message'=>$th->getMessage()]);
}


function validarMapa(){
    global $mapa ,$extensions;
    $mapaExtension = getExtension($mapa);

    $valido = in_array($mapaExtension, $extensions);
   
    if ( !$valido ) {
        throw new Exception("Error, El tipo de archivo no es permitido");
    }
}

function eliminarImagenActual($bodegaBD){
    global $file_path;

    $imagePath = $file_path.$bodegaBD['Mapa'];
    if( file_exists ( $imagePath ) ){
        unlink($imagePath);
    }
}

function generarNombre($mapa){

    $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';
    $name = substr(str_shuffle($permitted_chars), 0, 30);
    $mapaExtension = getExtension($mapa);
    $name.= '.'.$mapaExtension;
    
    return $name;
}

function getExtension($mapa){
    $mapaExtension = pathinfo($mapa['name'],PATHINFO_EXTENSION);
    return $mapaExtension;
}