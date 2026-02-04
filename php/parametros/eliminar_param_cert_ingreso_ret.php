<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

if ($id) {

    $query = "DELETE FROM Parametro_Certificado_Ingreso_Retencion_Renglon WHERE Renglon = $id";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);

    $resultado['mensaje'] = "Se ha eliminado el parametro correctamente.";
    $resultado['titulo'] = "Exito!";
    $resultado['tipo'] = 'success';

} else {
    $resultado['mensaje'] = "Ha ocurrido un error en el proceso.";
    $resultado['titulo'] = "Oops!";
    $resultado['tipo'] = 'error';
}

echo json_encode($resultado);
?>