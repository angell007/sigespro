<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.http_response.php');


$id_dispensacion = isset($_REQUEST['Id_Dispensacion']) ? $_REQUEST['Id_Dispensacion'] : '';

$reclamante = [];

if ($id_dispensacion) {
  $query = 'SELECT R.* FROM Dispensacion_Reclamante DR 
      INNER JOIN Reclamante R ON R.Id_Reclamante = DR.Reclamante_Id
      WHERE DR.Dispensacion_Id = '.$id_dispensacion;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $reclamante = $oCon->getData();
}

$data=[];

$data['Reclamante']=$reclamante;

echo json_encode($data);
