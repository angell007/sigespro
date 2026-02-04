<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_factura = isset($_REQUEST['id_factura']) ? $_REQUEST['id_factura'] : false;
$id_acta = isset($_REQUEST['id_acta']) ? $_REQUEST['id_acta'] : false;
$status = 202;

if ($id_factura && $id_acta) {
    $query = "SELECT FARR.Id_Retencion, FARR.Valor_Retencion AS Valor, R.Porcentaje, R.Tipo_Retencion AS Tipo FROM Factura_Acta_Recepcion_Retencion FARR INNER JOIN Retencion R ON FARR.Id_Retencion = R.Id_Retencion WHERE FARR.Id_Factura = $id_factura AND FARR.Id_Acta_Recepcion = $id_acta";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $response['resultado'] = $oCon->getData();
    $response['status'] = $status;
} else {
    $response['status'] = 500;
}


echo json_encode($response);

?>