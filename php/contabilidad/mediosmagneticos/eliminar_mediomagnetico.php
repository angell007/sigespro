<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

if ($id) {
    
    $oItem = new complex('Medio_Magnetico','Id_Medio_Magnetico', $id);
    $oItem->delete();
    unset($oItem);

    $query = "DELETE FROM Medio_Magnetico_Cuentas WHERE Id_Medio_Magnetico = $id";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);

    if ($id) {
        $resultado['mensaje'] = "Se ha eliminado correctamente el formato.";
        $resultado['titulo'] = "Exito!";
        $resultado['tipo'] = "success";
    } else {
        $resultado['mensaje'] = "Ocurrio un error al intentar eliminar el formato.";
        $resultado['titulo'] = "Oops!";
        $resultado['tipo'] = "error";
    }
} else {
    $resultado['mensaje'] = "Datos incompletos para eliminar el formato.";
    $resultado['titulo'] = "Oops!";
    $resultado['tipo'] = "warning";
}

echo json_encode($resultado);
?>