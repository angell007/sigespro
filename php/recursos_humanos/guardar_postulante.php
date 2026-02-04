<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require('../../class/class.guardar_archivos.php');

//Objeto de la clase que almacena los archivos
$storer = new FileStorer();
 
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );;

$datos = (array) json_decode($datos , true);

if(isset($datos['Id_Postulantes']) && ($datos['Id_Postulantes']!=null || $datos['Id_Postulantes']!="")){
    $oItem = new complex("Postulante","Id_Postulante",$datos['Id_Postulantes']);

}else{
    $oItem = new complex("Postulante","Id_Postulante");
}
$datos['Nombres']=strtoupper($datos['Nombres']);
$datos['Apellidos']=strtoupper($datos['Apellidos']);
foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
$id_postulante = $oItem->getId();
unset($oItem);

if (!file_exists( $MY_FILE.'IMAGENES/POSTULANTES/'.$id_postulante)) {
    mkdir($MY_FILE.'IMAGENES/POSTULANTES/'.$id_postulante, 0777, true);
}
if (!empty($_FILES['Archivo']['name'])){
    //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
    $nombre_archivo = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'IMAGENES/POSTULANTES/');
    $nombre_archivo = $nombre_archivo[0];

    // $posicion1 = strrpos($_FILES['Archivo']['name'],'.')+1;
    // $extension1 =  substr($_FILES['Archivo']['name'],$posicion1);
    // $extension1 =  strtolower($extension1);
    // $_filename1 = uniqid() . "." . $extension1;
    // $_file1 = $MY_FILE . "IMAGENES/POSTULANTES/" . $_filename1;

    // $subido1 = move_uploaded_file($_FILES['Archivo']['tmp_name'], $_file1);
    //     if ($subido1){
    //         @chmod ( $_file1, 0777 );
    //         $nombre_archivo = $_filename1;
    //     }
}
if( $nombre_archivo){
    $oItem = new complex("Postulante","Id_Postulante",$id_postulante );
    $oItem->Archivo=$nombre_archivo;
    $oItem->save();
    unset($oItem);
}


$resultado['mensaje'] = "¡Hoja de Vida Guardada Exitosamente!";
$resultado['tipo'] = "success";

echo json_encode($resultado);

?>
