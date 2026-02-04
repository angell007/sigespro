<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$datos = isset($_REQUEST['datos']) ? $_REQUEST['datos'] : false;

if ($datos) {
    $datos = json_decode($datos, true);

    $datos['Cuentas'] = concatPlanCuentas($datos['Cuentas']);

    if (existsParam($datos['Renglon'])) {
        actualizarParametro($datos);
    } else {
        $id = registrarParametro($datos);
    }

    $resultado['mensaje'] = "Se ha registrado el parametro correctamente.";
    $resultado['titulo'] = "Exito!";
    $resultado['tipo'] = 'success';

} else {
    $resultado['mensaje'] = "Ha ocurrido un error en el proceso.";
    $resultado['titulo'] = "Oops!";
    $resultado['tipo'] = 'error';
}

echo json_encode($resultado);

function concatPlanCuentas($cuentas) {
    $ids = [];

    unset($cuentas[count($cuentas)-1]);
    foreach ($cuentas as $value) {
        $ids[] = $value['Id_Plan_Cuenta'];
    }

    return implode(',',$ids);
}

function registrarParametro($datos) {
    $oItem = new complex('Parametro_Certificado_Ingreso_Retencion_Renglon','Id_Parametro_Certificado_Ingreso_Retencion_Renglon');
    foreach ($datos as $index => $value) {
        $oItem->$index = $value;
    }
    $oItem->save();
    $id = $oItem->getId();
    unset($oItem);

    return $id;
}

function actualizarParametro($datos) {
    $query = "UPDATE Parametro_Certificado_Ingreso_Retencion_Renglon SET Tipo_Valor = '$datos[Tipo_Valor]', Cuentas = '$datos[Cuentas]' WHERE Renglon = $datos[Renglon]";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);

    return;
}

function existsParam($renglon) {
    $query = "SELECT Id_Parametro_Certificado_Ingreso_Retencion_Renglon AS Id FROM Parametro_Certificado_Ingreso_Retencion_Renglon WHERE Renglon = $renglon";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);

    return $resultado ? true: false;
}

?>