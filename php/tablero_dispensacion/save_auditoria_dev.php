<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');
require_once('../../class/class.guardar_archivos.php');

$storer = new FileStorer();

$queryObj = new QueryBaseDatos();
$response = array();
$http_response = new HttpResponse();

$configuracion = new Configuracion();

$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
$soportes = ( isset( $_REQUEST['soportes'] ) ? $_REQUEST['soportes'] : '' );

$modelo = (array) json_decode(utf8_decode($modelo));
$soportes = (array) json_decode(utf8_decode($soportes) , true);

if (isset($modelo['Id_Auditoria']) && $modelo['Id_Auditoria'] != '') {

    $oItem = new complex('Auditoria','Id_Auditoria',$modelo['Id_Auditoria']);
    $dispensador_preauditoria = $oItem->Funcionario_Preauditoria;
    $oItem->Funcionario_Preauditoria = $modelo['Identificacion_Funcionario'];
    $oItem->Punto_Pre_Auditoria = $modelo['Id_Punto_Dispensacion'];
    $oItem->Dispensador_Preauditoria = $dispensador_preauditoria;
    $oItem->save();
    unset($oItem);

    foreach($soportes as $soporte){ $i++;
        $oItem = new complex('Soporte_Auditoria',"Id_Soporte_Auditoria");
        $soporte['Id_Auditoria']=$modelo['Id_Auditoria'];
        foreach($soporte as $index=>$value) {
            $oItem->$index=$value;
        }
        $oItem->save();
        unset($oItem);
    }

    $http_response->SetRespuesta(0, 'Guardado Correctamente', 'Se ha guardado correctamente la Auditoria ');
    $response = $http_response->GetRespuesta();

}else{

    if($modelo['Id_Dispensacion_Mipres']!='0'){
       
        //if($modelo['ValidacionProductoMipres']=='Si'){
            
            $oItem = new complex("Dispensacion_Mipres","Id_Dispensacion_Mipres",$modelo["Id_Dispensacion_Mipres"]);
            $oItem->Estado='Radicado Programado';
            $oItem->save();
            unset($oItem);          

            $idAuditoria=SaveAuditoria($modelo);
            $http_response->SetRespuesta(0, 'Guardado Correctamente', 'Se ha guardado correctamente la Auditoria, Registro la Dispensacion Mipres ');
            $response = $http_response->GetRespuesta();  
       
        // }else{

        //     $oItem = new complex("Dispensacion_Mipres","Id_Dispensacion_Mipres",$modelo["Id_Dispensacion_Mipres"]);
        //     $oItem->Observaciones='Los productos programados por el mipres no corresponden con los que la EPS autorizo';
        //     $oItem->Estado='Rechazado';
        //     $oItem->save();
        //     unset($oItem);

        //     $http_response->SetRespuesta(1, 'No se guardo la auditoria', 'No se guardo la auditoria debiadoa que el producto del mipres no correspondia con la autorizacion');
        //      $response = $http_response->GetRespuesta();
        // }   

    }else{
        SaveAuditoria($modelo);
        $http_response->SetRespuesta(0, 'Guardado Correctamente', 'Se ha guardado correctamente la Auditoria, No Registro la Dispensacion Mipres ');
        $response = $http_response->GetRespuesta();
    }
}

echo json_encode($response);

function GetTurnero($id){

    global $queryObj;
    $query="SELECT Id_Turneros FROM Punto_Turnero WHERE Id_Punto_Dispensacion=$id ";
    $queryObj->SetQuery($query);
    $turnero = $queryObj->ExecuteQuery('simple');

    return $turnero['Id_Turneros'];

}

function SaveAuditoria($modelo){

    global $soportes, $storer;

    $punto=GetTurnero($modelo['Id_Punto_Dispensacion']);
    $modelo["Fecha_Preauditoria"]=date("Y-m-d H:i:s");

    $modelo['Origen'] ='Auditor' ;
    $modelo['Punto_Pre_Auditoria']=$modelo['Id_Punto_Dispensacion'];
    $modelo['Funcionario_Preauditoria']=$modelo['Identificacion_Funcionario'];
    $modelo['Id_Paciente']=$modelo['Numero_Documento'];
    $modelo['Estado']="Pre Auditado";

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

    foreach($soportes as $soporte){ 

        $oItem = new complex('Soporte_Auditoria',"Id_Soporte_Auditoria");
        $soporte['Id_Auditoria']=$id_auditoria;

        foreach($soporte as $index=>$value) {
            $oItem->$index=$value;
        }

        $oItem->save();
        unset($oItem);

    }

    if($modelo["Id_Turnero"]!="" && $modelo["Id_Turnero"]!=null){

        $oItem = new complex("Turnero","Id_Turnero",$modelo["Id_Turnero"]);
        $oItem->Id_Auditoria = $id_auditoria;
        $oItem->Estado = "Espera";
        $oItem->Id_Dispensacion_Mipres=$modelo['Id_Dispensacion_Mipres']!='0' ? $modelo['Id_Dispensacion_Mipres'] : '0';
        $oItem->save();
        $id_turnero = $oItem->getId();
        unset($oItem);

    }else{

        $oItem = new complex("Turnero","Id_Turnero");
        $oItem->Identificacion_Persona = $modelo["Numero_Documento"];
        $oItem->Persona =$modelo["Paciente"];
        $oItem->Fecha=date("Y-m-d");
        // $oItem->Hora_Turno = date("H:i:s");
        $oItem->Hora_Turno = "23:59:59";
        $oItem->Id_Turneros = (INT)$punto;
        $oItem->Tipo ='OtroServicio';
        $oItem->Id_Auditoria = $id_auditoria;
        $oItem->Estado = "Espera";
        $oItem->Id_Dispensacion_Mipres=$modelo['Id_Dispensacion_Mipres']!='0' ? $modelo['Id_Dispensacion_Mipres'] : '0';
        $oItem->save();
        $id_turnero = $oItem->getId();
        unset($oItem);  

    }

    return $id_auditoria;
}

function GetPaciente($idAuditoria){

    global $queryObj;
    $query="SELECT A.Id_Paciente,P.Id_Regimen FROM Auditoria A INNER JOIN (SELECT Id_Paciente, Id_Regimen FROM Paciente ) P ON A.Id_Paciente=P.Id_Paciente WHERE A.Id_Auditoria=$idAuditoria ";
    $queryObj->SetQuery($query);
    $paciente = $queryObj->ExecuteQuery('simple');
    return $paciente;

}

?>





