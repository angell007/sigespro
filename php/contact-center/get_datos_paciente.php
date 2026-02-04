<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.http_response.php');


$id_paciente = isset($_REQUEST['Id_Paciente']) ? $_REQUEST['Id_Paciente'] : '';
$id_dispensacion = isset($_REQUEST['Id_Dispensacion']) ? $_REQUEST['Id_Dispensacion'] : '';

$query = 'SELECT * FROM Paciente WHERE Id_Paciente = '.$id_paciente;
$oCon = new consulta();
$oCon->setQuery($query);
$paciente = $oCon->getData();
$reclamante = [];

$query = 'SELECT * , False as Selected FROM Paciente_Telefono WHERE Id_Paciente = '.$id_paciente;
$oCon = new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$telefonos = $oCon->getData();

$query="Select Id_Paciente_Telefono , Id_Dispensacion_Domicilio , Hora_Entrega ,Fecha_Entrega  FROM Dispensacion_Domicilio
	WHERE Id_Dispensacion = ".$id_dispensacion;

$oCon = new consulta();
$oCon->setQuery($query);
$domicilio = $oCon->getData();

$isComplete = false;
if($domicilio){
  $isComplete = true;
  foreach($telefonos as $key => &$tel){
    if( $tel['Id_Paciente_Telefono'] == $domicilio['Id_Paciente_Telefono']  ){
      $tel['Selected'] = true;
    }
  }
}
$data=[];
$data['Paciente']=$paciente;
$data['Telefonos']=$telefonos;
$data['Id_Dispensacion_Domicilio'] = $domicilio ? $domicilio['Id_Dispensacion_Domicilio'] : "";
$data['Hora_Entrega'] = $domicilio ? $domicilio['Hora_Entrega'] : "";
$data['Fecha_Entrega'] = $domicilio ? $domicilio['Fecha_Entrega'] : "";

echo json_encode($data);
