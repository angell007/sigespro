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
    $query = "SELECT Id_Formato_Agrupacion_Medio_Magnetico, Codigo_Formato, Nombre_Formato FROM Formato_Agrupacion_Medio_Magnetico WHERE Id_Formato_Agrupacion_Medio_Magnetico = $id";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);

    $query = "SELECT Id_Medio_Magnetico_Especial AS Formato FROM Medio_Magnetico_Agrupacion WHERE Id_Formato_Agrupacion_Medio_Magnetico = $id";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $formatos = $oCon->getData();
    unset($oCon);

    $detalles['encabezado'] = $resultado;
    $detalles['formatos'] = $formatos;
}

echo json_encode($detalles);
?>