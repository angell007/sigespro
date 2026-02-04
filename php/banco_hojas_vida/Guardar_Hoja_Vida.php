<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require('../../class/class.guardar_archivos.php');

$Archivo = isset($_FILES['Archivo']) ? $_FILES['Archivo'] : false;
$file_path = __DIR__.'/../../ARCHIVOS/BANCO_HOJA_VIDA/';
$extensions = array("pdf");

//$file_path = '/../../ARCHIVOS/BANCO_HOJA_VIDA/';

//Objeto de la clase que almacena los archivos
$storer = new FileStorer();
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$datos = (array) json_decode($datos , true);
$Modo  = $_REQUEST['Modo'];

if($Modo == 'true'){
    $Id_HDV = $_REQUEST['Id_HDV'];
    $oItem  = new complex("Banco_Hoja_Vida","Id_Banco_Hoja_Vida",$Id_HDV);
}else{
    $oItem  = new complex("Banco_Hoja_Vida","Id_Banco_Hoja_Vida");
}


$datos['Identificacion'] = $datos['Identificacion'];
$datos['Nombre']         = ucwords($datos['Nombre']);
$datos['Apellido']       = ucwords($datos['Apellido']); 
$datos['Id_Cargo']       = $datos['Id_Cargo'];
$datos['NumeroTelefono'] = $datos['NumeroTelefono'];
$datos['Direccion']      = strtoupper($datos['Direccion']);
$datos['Id_Municipio']   = intval(strtoupper($datos['Id_Municipio']));
$datos['Id_Creador']     = $datos['Id_Creador'];


if ($Archivo){

    validarHoja();    
    $temp_mapa = $Archivo['tmp_name'];
    $nombre_CV = generarNombre($Archivo);
    move_uploaded_file($temp_mapa, $file_path . '/' . $nombre_CV);
    $oItem->Archivo = $nombre_CV ;

    
    
    //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
    $nombre_archivo   = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'ARCHIVOS/BANCO_HOJA_VIDA/');
    $nombre_archivo   = $nombre_archivo[0];
    $datos['Archivo'] = $nombre_CV;

}/// FINAL GUARDANDO ARCHIVO

if( $nombre_archivo){
    $oItem = new complex("Banco_Hoja_Vida","Id_Banco_Hoja_Vida",$Id_BHV);
    $oItem->Archivo=$nombre_archivo;
    $oItem->save();
    unset($oItem);
}/// DEFINIR NOMBRE ARCHIVO

foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}/// GUARDAR DATOS
$oItem->save();
$Id_BHV = $oItem->getId();
unset($oItem); 


$resultado['mensaje'] = "¡Hoja de Vida Guardada Exitosamente!";
$resultado['tipo'] = "success";

echo json_encode($resultado);

function validarHoja(){
    global $Archivo ,$extensions;
    $HvidaExtencion = getExtension($Archivo);

    $valido = in_array($HvidaExtencion, $extensions);
   
    if ( !$valido ) {
        //throw new Exception("Error, El tipo de archivo no es permitido");
        $resultado['mensaje'] = "El tipo de archivo no es permitido";
        $resultado['tipo'] = "error";

        echo json_encode($resultado);
        exit;
    }
}/// VALIDAR HOJA

function generarNombre($ArchivoF){ 

    $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';
    $name = substr(str_shuffle($permitted_chars), 0, 30);
    $HvidaExtencion = getExtension($ArchivoF);
    $name.= '.'.$HvidaExtencion;
    
    return $name;
}

function getExtension($Archivo){
    $HvidaExtencion = pathinfo($Archivo['name'],PATHINFO_EXTENSION);
    return $HvidaExtencion;
}

?>
