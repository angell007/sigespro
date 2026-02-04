<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require('../../class/class.guardar_archivos.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.querybasedatos.php');
$storer = new FileStorer();
$http_response = new HttpResponse();

$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );

$modelo = (array) json_decode($modelo , true);

if (!empty($_FILES['Archivo']['name'])){
    //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
    $nombre_archivo = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'ARCHIVOS/SOPORTES_CIERRE/');
    $nombre_archivo = $nombre_archivo[0];
	
    $oItem=new complex ('Soporte_Consignacion','Id_Soporte_Consignacion');
    $fecha_inicio = trim(explode(' - ', $modelo['Fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $modelo['Fechas'])[1]);
    foreach ($modelo as $key => $value) {
        $oItem->$key=$value;
    }
    $oItem->Fecha_Inicio=$fecha_inicio;
    $oItem->Fecha_Fin=$fecha_fin;
    $oItem->Soporte= $nombre_archivo;
    $oItem->save();
    $id_soporte=$oItem->getId();
    unset($oItem);

    ActualizarCierres($id_soporte,$modelo['Id_Punto_Dispensacion']);

	$http_response->SetRespuesta(0, 'Guardado Correctamente', 'Se ha guardado correctamente el archivo');
	$response = $http_response->GetRespuesta();


}else{
	$http_response->SetRespuesta(1, 'Error', 'No Se ha podido guardado el archivo intente nuevamente');
	$response = $http_response->GetRespuesta();
}


echo json_encode($response);


function ActualizarCierres($id_soporte,$punto){
    global $modelo;
    $fecha_inicio = trim(explode(' - ', $modelo['Fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $modelo['Fechas'])[1]);
    $query="UPDATE Diario_Cajas_Dispensacion SET Id_Soporte_Consignacion=$id_soporte  WHERE (DATE(Fecha_Inicio) BETWEEN '$fecha_inicio' AND '$fecha_fin' AND DATE(Fecha_Fin) BETWEEN '$fecha_inicio' AND '$fecha_fin' ) AND Id_Soporte_Consignacion IS NUll AND Id_Punto_Dispensacion=$punto";
    $oCon= new consulta();
    $oCon->setQuery($query);     
    $oCon->createData();     
    unset($oCon);
}





?>