<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.http_response.php');

require '../../class/class.awsS3.php';

// $idsoporte = GetIDSoporte();

$dispensacion = (isset($_REQUEST['Dispensacion']) ? $_REQUEST['Dispensacion'] : '');
// $Archivo = (isset($_REQUEST['Id_Tipo_Soporte']) ? $_REQUEST['Id_Tipo_Soporte'] : '');

$files = (isset($_FILES) ? $_FILES : '');

$dispensacion = json_decode($dispensacion, true);

$http_response = new HttpResponse();

#actualiza soportes 
foreach ($dispensacion as $key => $value) {
  
    if (array_key_exists('newFile', $value)) {

        try {

            $s3 = new AwsS3();



            $oItem = new complex('Dispensacion', 'Id_Dispensacion', $value['Id_Dispensacion']);

            $ruta = 'dispensacion/auditoria/soportes/' . $value['Id_Auditoria'] . '/' . 'Acta_Entrega';

            // echo $ruta;

            $uri = $s3->putObject( $ruta, $files['Id_Tipo_Soporte']);


            $oItem->Acta_Entrega = $uri;

            $oItem->save();
            unset($oItem);


            // $oItem =  new complex('Soporte_Auditoria', 'Id_Soporte_Auditoria');
            // $ruta = 'dispensacion/auditoria/soportes/' . $value['Id_Auditoria'] . '/' . $idsoporte['Id_Tipo_Soporte'];

            // $oItem->Id_Tipo_Soporte = $idsoporte['Id_Tipo_Soporte'];

            // $uri = $s3->putObject( $ruta, $files[ 'Id_Tipo_Soporte' ]);
            // $oItem->Archivo = $uri;
            // $oItem->Tipo_Soporte = $idsoporte['Tipo_Soporte'];
            // $oItem->Id_Auditoria = $value['Id_Auditoria'];
            // $oItem->save();
            // unset($oItem);

            // $uri = $s3->deleteObject($value['Archivo']);

        } catch (Aws\S3\Exception\S3Exception $e) {

            echo json_encode($e->getMessage());
        }
        
    }   
    
}

function GetIDSoporte()
{
    $query= "SELECT Id_Tipo_Soporte, Tipo_Soporte
              FROM Tipo_Soporte
              WHERE Tipo_Soporte = 'Acta'";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('simple');
    $id = $oCon->getData();
    unset($oCon);

    return $id;
}

