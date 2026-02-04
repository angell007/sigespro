<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );


$query = 'SELECT C.*, F.Imagen
FROM Correspondencia C
INNER JOIN Funcionario F
On C.Id_Funcionario_Envia=F.Identificacion_Funcionario
WHERE C.Estado="Recibida" AND C.Id_Funcionario_Recibe ='.$id;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$datos = $oCon->getData();
unset($oCon);

echo json_encode($datos);


?>