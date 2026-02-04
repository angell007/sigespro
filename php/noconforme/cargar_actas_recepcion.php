<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_proveedor = isset($_REQUEST['id_proveedor']) ? $_REQUEST['id_proveedor'] : false;
$actas = [];

if ($id_proveedor) {
    $query = "SELECT Id_Acta_Recepcion AS ID, Codigo AS Acta FROM Acta_Recepcion WHERE Id_Proveedor = $id_proveedor AND ( Estado = 'Acomodada' OR (Fecha_Creacion < '2020-07-22' AND Estado = 'Aprobada' ) ) ORDER BY 1 DESC LIMIT 1000";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $actas = $oCon->getData();
    unset($oCon);
}

echo json_encode($actas);
?>