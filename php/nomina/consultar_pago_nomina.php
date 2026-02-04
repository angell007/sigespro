<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$fecha_inicio = (isset($_REQUEST['Fecha_Inicio']) ? $_REQUEST['Fecha_Inicio'] : false);
$fecha_fin = (isset($_REQUEST['Fecha_Fin']) ? $_REQUEST['Fecha_Fin'] : false);
$response = [];

if ($fecha_inicio && $fecha_fin) {
    $query = "SELECT * FROM Nomina WHERE DATE(Fecha_inicio) BETWEEN '$fecha_inicio' AND '$fecha_fin'";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);

    if ($resultado) {
        $response['titulo'] = 'Información';
        $response['mensaje'] = "Existe un pago de nomina en este periodo con un total de $resultado[Total_Empleados]. Por favor revisar el historial de pagos de nomina si desea.";
        $response['tipo'] = 'info';
        $response['pagado'] = 'si';
    } else {
        $response['pagado'] = 'no';
    }
} else {
    $response['pagado'] = 'no';
}

echo json_encode($response);
?>

