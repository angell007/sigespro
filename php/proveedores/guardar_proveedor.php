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

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$datos2 = ( isset( $_REQUEST['datos2'] ) ? $_REQUEST['datos2'] : '' );

$datos = (array) json_decode($datos);
$datos2 = (array) json_decode($datos2);

if(isset($datos["id"])&&$datos["id"] != ""){
    $oItem = new complex($mod,"Id_".$mod,$datos["id"]);
    $id_proveedor = $oItem->Id_Proveedor;
}else{
	$oItem = new complex($mod,"Id_".$mod);
}

$datos['Primer_Apellido'] = $datos2['Primer_Apellido'];
$datos['Segundo_Apellido'] = $datos2['Segundo_Apellido'];
$datos['Primer_Nombre'] = $datos2['Primer_Nombre'];
$datos['Segundo_Nombre'] = $datos2['Segundo_Nombre'];
$datos['Razon_Social'] = $datos2['Razon_Social'];

foreach($datos as $index=>$value) {
    if($index=="Porcentaje_Descuento"){
        if($value=='' ){
            $value='0';
        }
    }
    $oItem->$index=$value;
}
$oItem->save();
if (!isset($datos["id"])) {
	$id_proveedor = $oItem->getId();

	$oItem2 = new complex('Proveedor','Id_Proveedor', $id_proveedor);

    if (!empty($_FILES["Rut"]['name'])){
        //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
        $nombre_archivo = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'ARCHIVOS/CONTABILIDAD/RUTS/');
        $oItem2->Rut = $nombre_archivo[0];
    }
    $oItem2->save();
    unset($oItem2);
}
unset($oItem);

if($id_proveedor){
    $resultado['mensaje'] = "Se ha guardado correctamente el proveedor";
    $resultado['tipo'] = "success";
}else{
    $resultado['mensaje'] = "Error en el proceso de registro del nuevo proveedor.";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);

?>