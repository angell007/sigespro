<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

$query = 'SELECT Ver_Costo FROM Funcionario WHERE Ver_Costo="Si" AND Identificacion_Funcionario='.$id; // 22 es Compras y 16 Administrador.
$oCon= new consulta();
$oCon->setQuery($query);
$permisos = $oCon->getData();
unset($oCon);

$status = false; // Sin permisos

if ($permisos) {
    $status = true;
}

echo json_encode(["status" => $status]);

?>