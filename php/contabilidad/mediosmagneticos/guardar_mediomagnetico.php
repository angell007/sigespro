<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$datos = isset($_REQUEST['datos']) ? $_REQUEST['datos'] : false;
$cuentas = isset($_REQUEST['cuentas']) ? $_REQUEST['cuentas'] : false;
$tipos_documentos = isset($_REQUEST['tipos_documentos']) ? $_REQUEST['tipos_documentos'] : false;

if ($datos) {
    $datos = json_decode($datos, true);
    $datos['Tipos'] = $tipos_documentos;
    $datos['Detalles'] = $cuentas;
    
    $cuentas = json_decode($cuentas, true);

    $oItem = new complex('Medio_Magnetico','Id_Medio_Magnetico');

    if (isset($datos['Id_Medio_Magnetico']) && $datos['Id_Medio_Magnetico'] != '') {
        $oItem = new complex('Medio_Magnetico','Id_Medio_Magnetico',$datos['Id_Medio_Magnetico']);

        deleteCuentas($datos['Id_Medio_Magnetico']);
    } 

    foreach ($datos as $index => $value) {
        $oItem->$index = $value;
    }
    $oItem->save();
    $id = $oItem->getId();
    unset($oItem);

    if (isset($datos['Id_Medio_Magnetico']) && $datos['Id_Medio_Magnetico'] != '') {
        $id = $datos['Id_Medio_Magnetico'];
    }

    unset($cuentas[count($cuentas)-1]);
    foreach ($cuentas as $cta) {
        $oItem = new complex('Medio_Magnetico_Cuentas','Id_Medio_Magnetico_Cuentas');
        $oItem->Id_Medio_Magnetico = $id;
        $oItem->Id_Plan_Cuenta = $cta['Id_Plan_Cuenta'];
        $oItem->Concepto = $cta['Concepto'];
        $oItem->save();
        unset($oItem);
    }

    if ($id || $datos['Id_Medio_Magnetico']) {
        $resultado['mensaje'] = "Se ha registrado correctamente el formato.";
        $resultado['titulo'] = "Exito!";
        $resultado['tipo'] = "success";
    } else {
        $resultado['mensaje'] = "Ocurrio un error al intentar guardar el formato.";
        $resultado['titulo'] = "Oops!";
        $resultado['tipo'] = "error";
    }
} else {
    $resultado['mensaje'] = "Datos incompletos para el guardado.";
    $resultado['titulo'] = "Oops!";
    $resultado['tipo'] = "warning";
}

echo json_encode($resultado);

function deleteCuentas($id) {
    $query = "DELETE FROM Medio_Magnetico_Cuentas WHERE Id_Medio_Magnetico = $id";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);

    return;
}
?>