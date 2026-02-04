<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

require_once '../../../config/start.inc.php';
include_once '../../../class/class.complex.php';
require '../../../class/class.awsS3.php';

$datos = (isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '');
$datos = json_decode($datos, true);

$nombre_archivo = '';
try {
    if (!empty($_FILES['carta_agotado']['name'])) {
        $s3 = new AwsS3();
        $ruta = "productos/cartas_agotados";
        $nombre_archivo = $s3->putObject( $ruta, $_FILES['carta_agotado']);
    }
} catch (\Throwable $th) {
	$respuesta = array('tipo'=>'error', 'mensaje'=>'ocurri¨® un error al cargar el archivo', 'titulo'=>'Error');
	echo json_encode($respuesta); exit;
}

$oItem= new complex("Producto_Agotado","Id_Producto_Agotado");
foreach ($datos as $key => $value) {
	if($value)
	$oItem->$key = $value;
}
$oItem->Carta_Agotado = $nombre_archivo;
$oItem->save();


$id = $oItem->getId();

if($id){
	$respuesta = array('tipo'=>'success', 'mensaje'=>'Se ha cargado correctamente', 'titulo'=>'Correcto');
}else{
	$respuesta = array('tipo'=>'error', 'mensaje'=>'ocurri¨® un error al cargar el archivo', 'titulo'=>'Error');
}
echo json_encode($respuesta); exit;