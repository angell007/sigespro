<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$condicion = strConditions();

$query = "SELECT Id_Medio_Magnetico AS Id, Periodo, Codigo_Formato, Nombre_Formato, Tipo_Exportacion, Tipo_Columna FROM Medio_Magnetico WHERE Estado = 'Activo' $condicion";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$lista = $oCon->getData();
unset($oCon);

echo json_encode($lista);

function strConditions() {
    $condicion = '';

    if (isset($_REQUEST['Tipo']) && $_REQUEST['Tipo'] != '') {
        $condicion .= " AND Tipo_Medio_Magnetico = 'Especial'";
    } else {
        $condicion .= " AND Tipo_Medio_Magnetico = 'Basico'";
    }

    return $condicion;
}
?>