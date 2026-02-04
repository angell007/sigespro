<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

$query = 'SELECT PE.*, PF.*
FROM Perfil PE
INNER JOIN Perfil_Funcionario PF
ON PE.Id_Perfil=PF.Id_Perfil
WHERE PF.Identificacion_Funcionario='.$id.' AND (PE.Id_Perfil=29 OR PE.Id_Perfil=16 OR PE.Id_Perfil=44)'; // 29 es Gerente Compras, 16 Administrador, 44 Grerente Comercial.
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$permisos = $oCon->getData();
unset($oCon);

$status = false; // Sin permisos

if ($permisos) {
    $status = true;
}

echo json_encode(["status" => $status]);

?>