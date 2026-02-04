<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');
require('../../class/class.guardar_archivos.php');

$storer = new FileStorer();

$queryObj = new QueryBaseDatos();
$response = array();
$http_response = new HttpResponse();

$configuracion = new Configuracion();
date_default_timezone_set('America/Bogota');

$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
$soportes = ( isset( $_REQUEST['soportes'] ) ? $_REQUEST['soportes'] : '' );
 

$modelo = (array) json_decode(utf8_decode($modelo));
$soportes = (array) json_decode(utf8_decode($soportes) , true);



    $modelo["Fecha_Preauditoria"]=date("Y-m-d H:i:s");

    $modelo['Origen'] ='Dispensador' ;
    $modelo['Punto_Pre_Auditoria']=$modelo['Id_Punto_Dispensacion'];
    $modelo['Funcionario_Preauditoria']=$modelo['Identificacion_Funcionario'];
    $modelo['Estado']="Pre Auditado";
    $modelo['Id_Paciente']=$modelo['Numero_Documento'];

    $oItem = new complex("Auditoria","Id_Auditoria");
  
    foreach($modelo as $index=>$value) {
        if($value!=''){
            $oItem->$index=$value;
        }
       
    }
    $oItem->save();
    $id_auditoria = $oItem->getId();
    unset($oItem);

    $nombre_archivo = '';


    if (!empty($_FILES['Archivo']['name'])){
        //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
        $nombre_archivo = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'IMAGENES/AUDITORIAS/'.$id_auditoria.'/');
        $nombre_archivo = $nombre_archivo[0];

    }
    if( $nombre_archivo){
        $oItem = new complex("Auditoria","Id_Auditoria",$id_auditoria );
        $oItem->Archivo=$nombre_archivo;
        $oItem->save();
        unset($oItem);
    }
    foreach($soportes as $soporte){ $i++;
        $oItem = new complex('Soporte_Auditoria',"Id_Soporte_Auditoria");
        $soporte['Id_Auditoria']=$id_auditoria;
        foreach($soporte as $index=>$value) {
            $oItem->$index=$value;
        }
        $oItem->save();
        unset($oItem);
    }





$http_response->SetRespuesta(0, 'Guardado Correctamente', 'Se ha guardado preauditoria ');
$response = $http_response->GetRespuesta();

echo json_encode($response);




 



?>





