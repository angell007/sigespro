<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');




$estado = $_REQUEST['estado'] ;
$Id = $_REQUEST['Id'];

$query = "UPDATE Cierre_Contable C SET C.Estado = '$estado' WHERE C.Id_Cierre_Contable = $Id";

$ocon = new consulta();
$ocon->setQuery($query);
$ocon->getData();


$respuesta = array(
    "Mensaje"=>"actualizado con Exito", 
    "Id_Cierre"=>$Id, 
    "Tipo" => "Correcto"
);


echo json_encode($respuesta);





?>