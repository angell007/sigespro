<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

$detalles = [];

if ($id) {
    $query = "SELECT Id_Medio_Magnetico, Periodo, Codigo_Formato, Nombre_Formato, Tipo_Exportacion, Detalles, Tipos, Tipo_Medio_Magnetico, Tipo_Columna, Columna_Principal FROM Medio_Magnetico WHERE Estado = 'Activo' AND Id_Medio_Magnetico = $id";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);

    $detalles['encabezado'] = $resultado;
    $detalles['cuentas'] = $resultado['Detalles'];
    $detalles['tipos'] = $resultado['Tipos'];
}

echo json_encode($detalles);
?>