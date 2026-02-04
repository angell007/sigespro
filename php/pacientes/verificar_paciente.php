<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$idPaciente = ( isset( $_REQUEST['id'] ) ? trim($_REQUEST['id']) : '' );
$idPacienteSql = "''";
if ($idPaciente !== '' && ctype_digit($idPaciente)) {
    // forzar comparacion string para evitar falsos positivos
    $idPacienteSql = "'" . $idPaciente . "'";
}

$query = 'SELECT p.Id_Paciente FROM Paciente p
          WHERE p.Id_Paciente=' . $idPacienteSql ;

    
$oCon= new consulta();
$oCon->setQuery($query);
$pacientes = $oCon->getData();
unset($oCon);

if ($pacientes) {
    $resultado["response"] = true;
} else {
    $resultado["response"] = false;
}


echo json_encode($resultado);

?>
