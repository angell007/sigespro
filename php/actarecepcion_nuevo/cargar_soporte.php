<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
include_once '../../class/class.awsS3.php';

$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');

$s3 = new AwsS3();
try {
    if (!empty($_FILES['archivo']['name'])) {
        $s3 = new AwsS3();
        $ruta = "acta_recepcion/archivo";
        $nombre_archivo = $s3->putObject( $ruta, $_FILES['archivo']);
    }
} catch (\Throwable $th) {
    http_response_code(500);
    echo $th->getMessage();
    exit;
}

$oItem = new complex('Acta_Recepcion', 'Id_Acta_Recepcion', $id); 
$oItem->Archivo_Adjunto = $nombre_archivo; 
$oItem->save();

http_response_code(200); 
$respuesta['tipo']='success';
$respuesta['titulo']='Cargado con éxito';
$respuesta['mensaje']='Documento adjunto con éxito';

echo json_encode($respuesta); 



