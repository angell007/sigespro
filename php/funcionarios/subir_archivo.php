<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require('../../class/class.guardar_archivos.php');
include_once('../../class/class.http_response.php');

$storer = new FileStorer();
$http_response = new HttpResponse();

$datos = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );

$datos = (array) json_decode($datos , true);


$nombre=$datos['tipo'].date('Y_m_d').'.pdf';
if ($datos){
	$oItem=new complex('Alerta','Id_Alerta',$datos['Id_Alerta']);
	$oItem->delete();
	unset($oItem);

	$oItem=new complex('Actividad_Funcionario','Id_Actividad_Funcionario');
	$oItem->Identificacion_Funcionario=$datos['funcionario'];
	$oItem->Detalles="El funcionario verifico el $datos[tipo] y lo cargo al sistema";
	$oItem->Tipo=$datos['tipo'];
	$oItem->Archivo=$nombre_archivo;
	$oItem->save();
	unset($oItem);

	$http_response->SetRespuesta(0, 'Guardado Correctamente', 'Se ha guardado correctamente el archivo');
	$response = $http_response->GetRespuesta();
}

//Guarda la fecha en el que el memorando fue aceptado
$oItem = new complex("Memorando","Id_Memorando",$datos['Id']);
$oItem->Fecha_Aceptado       =date('Y-m-d H:i:s');
$oItem->save();
unset($oItem);

echo json_encode($response);


// $storer = new FileStorer();
// $http_response = new HttpResponse();

// $datos = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );

// $datos = (array) json_decode($datos , true);
// $nombre=$datos['tipo'].date('Y_m_d').'.pdf';
// if (empty($_FILES['Archivo']['name'])){
//     //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
//     $nombre_archivo = $storer->UploadFileToRemoteServerWithName($_FILES, 'store_remote_files', 'DOCUMENTOS/'.$datos['funcionario'].'/',$nombre);
// 	$nombre_archivo = $nombre_archivo;
	
// 	//Eliminar la alerta 
// 	$oItem=new complex('Alerta','Id_Alerta',$datos['id']);
// 	$oItem->delete();
// 	unset($oItem);

// 	$oItem=new complex('Actividad_Funcionario','Id_Actividad_Funcionario');
// 	$oItem->Identificacion_Funcionario=$datos['funcionario'];
// 	$oItem->Detalles="El funcionario verifico el $datos[tipo] y lo cargo al sistema";
// 	$oItem->Tipo=$datos['tipo'];
// 	$oItem->Archivo=$nombre_archivo;
// 	$oItem->save();
// 	unset($oItem);

// 	$http_response->SetRespuesta(0, 'Guardado Correctamente', 'Se ha guardado correctamente el archivo');
// 	$response = $http_response->GetRespuesta();
// }else{
// 	$http_response->SetRespuesta(1, 'Error', 'No Se ha podido guardado el archivo intente nuevamente');
// 	$response = $http_response->GetRespuesta();
// }

?>