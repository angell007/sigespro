<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
 

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');
require('../class/class.guardar_archivos.php');

include_once('../class/PDFMerge/PDFMerger.php');
include_once('../class/class.dividir_pdf.php');
    

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$soportes = ( isset( $_REQUEST['soporte'] ) ? $_REQUEST['soporte'] : '' );
$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : 'Sustituir' );

$storer = new FileStorer();

if($datos["Id_Auditoria"]==''){
    
    $oItem = new complex("Auditoria","Id_Auditoria");
    $oItem->Estado = "Pre Auditado";
    $oItem->Funcionario_Preauditoria=$datos["Identificacion_Funcionario"];
    $oItem->Fecha_Preauditoria=date("Y-m-d H:i:s");
    $oItem->Id_Dispensacion = $datos["Id_Dispensacion"];
    $oItem->Id_Paciente = $datos["Id_Paciente"];
    $oItem->Id_Tipo_Servicio = $datos["Id_Tipo_Servicio"];
    $oItem->Punto_Pre_Auditoria =172;
    $oItem->Origen = "Auditor";
    $oItem->Fecha = date("Y-m-d H:i:s");
    $oItem->Id_Servicio = $datos["Id_Servicio"];
    $oItem->Id_Dispensacion_Mipres=$datos["Id_Dispensacion_Mipres"];
    $oItem->save();
    $id_auditoria=$oItem->getId();
    unset($oItem);
}else{
    $id_auditoria=$datos["Id_Auditoria"];
}

if (!empty($_FILES['soportes_nuevos']['name'])){
    //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
    $nombre_archivo = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'IMAGENES/AUDITORIAS/'.$id_auditoria.'/');
    $nombre_archivo = $nombre_archivo[0];
}

if( $nombre_archivo){
    $oItem = new complex("Auditoria","Id_Auditoria",$id_auditoria );
    $aud=$oItem->getData();
    
    if($tipo=="Sustituir"){
        $oItem->Archivo=$nombre_archivo;
    }elseif($tipo=="Adicionar"){
        $pdf_merge2 = new PDFMerger; 
        $pdf_merge2->addPDF($_SERVER['DOCUMENT_ROOT'].'/IMAGENES/AUDITORIAS/'.$id_auditoria.'/'.$aud["Archivo"], 'all');
        $pdf_merge2->addPDF($_SERVER['DOCUMENT_ROOT'].'/IMAGENES/AUDITORIAS/'.$id_auditoria.'/'.$nombre_archivo, 'all');
        $pdf_merge2->merge('file', $_SERVER['DOCUMENT_ROOT'].'/IMAGENES/AUDITORIAS/'.$id_auditoria.'/Archivo_Auditoria.pdf');
        
        $oItem->Archivo='Archivo_Auditoria.pdf';
    }
    $oItem->save();
    unset($oItem);
}

foreach($soportes as $soporte){ $i++;
    if($soporte["Id_Soporte_Auditoria"]!=''){
        $oItem = new complex('Soporte_Auditoria',"Id_Soporte_Auditoria",$soporte["Id_Soporte_Auditoria"]);
        $oItem->Paginas=$soporte["Paginas"];
        $oItem->save();
        unset($oItem);
    }else{
        $oItem = new complex('Soporte_Auditoria',"Id_Soporte_Auditoria");
        $oItem->Id_Tipo_Soporte = $soporte["Id_Tipo_Soporte"];
        $oItem->Tipo_Soporte =  $soporte["Tipo_Soporte"];
        $oItem->Cumple=1;
        $oItem->Id_Auditoria=$id_auditoria;
        $oItem->Paginas=$soporte["Paginas"];
        $oItem->save();
        unset($oItem);
    }
    
}

echo "Guardado Satisfactoriamente"
    
    
?>
