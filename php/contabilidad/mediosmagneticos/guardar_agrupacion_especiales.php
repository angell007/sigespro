<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$datos = isset($_REQUEST['datos']) ? $_REQUEST['datos'] : false;
$formatos = isset($_REQUEST['formatos']) ? $_REQUEST['formatos'] : false;

if ($datos) {
    $datos = json_decode($datos, true);
    $formatos = json_decode($formatos, true);

    $oItem = new complex('Formato_Agrupacion_Medio_Magnetico','Id_Formato_Agrupacion_Medio_Magnetico');

    if (isset($datos['Id_Formato_Agrupacion_Medio_Magnetico']) && $datos['Id_Formato_Agrupacion_Medio_Magnetico'] != '') {
        $oItem = new complex('Formato_Agrupacion_Medio_Magnetico','Id_Formato_Agrupacion_Medio_Magnetico',$datos['Id_Formato_Agrupacion_Medio_Magnetico']);

        deleteListFormatos($datos['Id_Formato_Agrupacion_Medio_Magnetico']); // Para eliminar los formatos y volverlos a crear.
    } 

    foreach ($datos as $index => $value) {
        $oItem->$index = $value;
    }
    $oItem->save();
    $id = $oItem->getId();
    unset($oItem);

    unset($formatos[count($formatos)-1]);
    foreach ($formatos as $i => $value) {
        $oItem = new complex('Medio_Magnetico_Agrupacion','Id_Medio_Magnetico_Agrupacion');
        $oItem->Id_Medio_Magnetico_Especial = $value['Formato'];
        $oItem->Id_Formato_Agrupacion_Medio_Magnetico = $id;
        $oItem->save();
        unset($oItem);
    }

    if ($id || $datos['Id_Formato_Agrupacion_Medio_Magnetico']) {
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


function deleteListFormatos($id) {
    $query = "DELETE FROM Medio_Magnetico_Agrupacion WHERE Id_Formato_Agrupacion_Medio_Magnetico = $id";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);

    return;
}
?>