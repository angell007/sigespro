<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );


$query = 'SELECT PD.*
FROM Funcionario_Punto FP
INNER JOIN Punto_Dispensacion PD 
ON FP.Id_Punto_Dispensacion=PD.Id_Punto_Dispensacion
WHERE FP.Identificacion_Funcionario='.$id;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$puntos = $oCon->getData();
unset($oCon);

$resultado=[];
$resultado["Puntos"]=$puntos;
echo json_encode($resultado);

?>