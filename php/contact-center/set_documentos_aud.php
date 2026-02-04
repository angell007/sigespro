<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.awsS3.php');

$files = (isset($_FILES) ? $_FILES : '');
$soportes = (isset($_REQUEST['Soportes']) ? $_REQUEST['Soportes'] : '');
$soportes = json_decode($soportes, true);
$Id_Auditoria = (isset($_REQUEST['Id_Auditoria']) ? $_REQUEST['Id_Auditoria'] : '');
$idFuncionario = (isset($_REQUEST['Identificacion_Funcionario']) ? $_REQUEST['Identificacion_Funcionario'] : '');
$estado = (isset($_REQUEST['Estado']) ? $_REQUEST['Estado'] : '');


$oItem = new complex("Auditoria", "Id_Auditoria", $Id_Auditoria);
$oItem->Estado = $estado;
$oItem->save();
unset($oItem);

foreach ($soportes as $key => $value) {
    # code...
  
    if (array_key_exists('newFile', $value)) {

      //  try {
            $s3 = new AwsS3();
            $oItem =  new complex('Soporte_Auditoria', 'Id_Soporte_Auditoria', $value['Id_Soporte_Auditoria']);
            GuardarActividadAuditoria($value['Id_Auditoria'],  $idFuncionario, 'Cambio de soporte'.$value['Tipo_Soporte'], 'Se ha modificado  el archvio de ' . $value['Tipo_Soporte']);
            //actualizar el nuevo objeto
            $ruta = 'dispensacion/auditoria/soportes/'.$Id_Auditoria.'/'. $value['Id_Tipo_Soporte'];     		
           
      
            $uri = $s3->putObject( $ruta, $files[ $value['Id_Tipo_Soporte'] ]);

            $oItem->Archivo = $uri;
            $oItem->save();
            unset($oItem);
      
 			$existObject = $s3->doesObjectExist($value['Archivo']);
            
      		if($existObject){
               $uri = $s3->deleteObject($value['Archivo']);
            }
      		
       // } catch (Aws\S3\Exception\S3Exception $e) {

           // echo json_encode('asdasd'.$e->getMessage());
        //}
    }
}


function GuardarActividadAuditoria($idAuditoria, $idFuncionario, $tipo, $observacion)
{

    if ($idFuncionario) {
        $oItem = new complex("Actividad_Auditoria", "Id_Actividad_Auditoria");
        $oItem->Identificacion_Funcionario = $idFuncionario;
        $oItem->Id_Auditoria = $idAuditoria;
        $oItem->Detalle = $tipo;
        $oItem->Estado = 'Validacion';
        $oItem->Fecha = date("Y-m-d H:i:s");
        $oItem->Observacion = $observacion != '' ?  $observacion : "Sin Observacion";
        $oItem->save();
        unset($oItem);
    }
}
