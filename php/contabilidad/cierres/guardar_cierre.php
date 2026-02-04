<?php
ini_set('max_execution_time', 3600);
ini_set('memory_limit','510M');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
include_once('../../../class/class.contabilizar.php');
include_once('../../comprobantes/funciones.php');

$datos = isset($_REQUEST['datos']) ? $_REQUEST['datos'] : false;

//echo json_encode($_REQUEST);exit;
$editar = false;
if ($datos) {

    $datos = json_decode($datos, true);
//echo json_encode($datos);exit;
    $contabilidad = new Contabilizar();
    
    if (!isset($datos['Id_Cierre_Contable']) || $datos['Id_Cierre_Contable'] == '') {

        if ($datos['Tipo_Cierre'] == 'Anio') {
            $cod = generarConsecutivo('Cierre_Anio',null,$datos['Anio']);
            $datos['Codigo'] = $cod;
          
        }
        $oItem = new complex('Cierre_Contable','Id_Cierre_Contable');
        unset($datos['Id_Cierre_Contable']);
        foreach ($datos as $index => $value) {
            $oItem->$index = $value;
        }
        $oItem->save();
        $id_cierre = $oItem->getId();
        
        
        unset($oItem);

        if ($datos['Tipo_Cierre'] == 'Anio') {
            $datos_contabilidad['Id_Registro'] = $id_cierre;
            $datos_contabilidad['Anio'] = $datos['Anio'];
            $datos_contabilidad['Codigo'] = $datos['Codigo'];

            $contabilidad->CrearMovimientoContable('Cierre Anio', $datos_contabilidad);
        }
    } else {
       $oItem = new complex('Cierre_Contable','Id_Cierre_Contable',$datos['Id_Cierre_Contable']);
        $oItem->Estado = $datos['Estado'];
        $oItem->save();
        unset($oItem);
        $editar = true;
    }

    $response['mensaje'] = "Proceso realizado exitosamente.";
    $response['titulo'] = "Exito!";
    $response['codigo'] = "success";
    $response['nroId'] = $id_cierre;
} else {
    $response['mensaje'] = "Ha ocurrido un error inesperado al procesar la información.";
    $response['titulo'] = "Ooops!";
    $response['codigo'] = "error";
}

echo json_encode($response);

?>