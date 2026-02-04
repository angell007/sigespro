<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT PF.*
FROM Perfil_Funcionario PF
WHERE PF.Identificacion_Funcionario='.$id .' GROUP BY PF.Id_Perfil';
$oCon= new consulta();
$oCon->setQuery($query);
$perfil = $oCon->getData();
unset($oCon);

echo json_encode($perfil);

?>