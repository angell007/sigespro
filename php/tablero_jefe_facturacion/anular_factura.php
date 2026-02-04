<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
$datos = isset($_REQUEST['datos']) ? $_REQUEST['datos'] : false;

$contabilidad = new Contabilizar();

$datos = (array) json_decode($datos, true);

$id_dispensacion = null;
$tipo_factura = null;
$factura = null;

$oItem = new complex('Factura','Id_Factura', $id);
$data = $oItem->getData();
$fecha = date('Y-m-d',strtotime($data['Fecha_Documento']));
if ($contabilidad->validarMesOrAnioCerrado($fecha)) {
    
    $id_dispensacion = $oItem->Id_Dispensacion;
    $tipo_factura = $oItem->Tipo;
    $factura = $oItem->Codigo;

    $oItem->Estado_Factura = 'Anulada';
    $oItem->Observacion_Factura = 'NOTA DE ANULACION: '. $datos['Observaciones'];
    $oItem->Id_Causal_Anulacion = $datos['Causal_Anulacion'];
    $oItem->Funcionario_Anula = $datos['Funcionario_Anula'];
    $oItem->Fecha_Anulacion = date("Y-m-d H:i:s");
    $oItem->save();
    unset($oItem);

    if ($tipo_factura != 'homologo') { // Porque el ID de los homologos no se relacionan con las dispensaciones.
        $oItem = new complex('Dispensacion','Id_Dispensacion',$id_dispensacion);
        $oItem->Estado_Facturacion = 'Sin Facturar';
        $oItem->Id_Factura = "0";
        $oItem->Fecha_Facturado = '0000-00-00';
        $oItem->save();
        unset($oItem);
    }

    $id_modulo = "12,13,14,17,19,20"; // Modulos que corresponden a facturas de dispensación.
        
    $contabilidad->AnularMovimientoContable($id, $id_modulo);

    $resultado['mensaje'] = "Factura $factura anulada satisfactoriamente";
    $resultado['tipo'] = "success";
    $resultado['titulo'] = "Exito!";
} else {
    $resultado['mensaje'] = "No es posible anular esta factura debido a que el mes o el año del documento ha sido cerrado contablemente. Si tienes alguna duda por favor comunicarse al Dpto. Contabilidad.";
    $resultado['tipo'] = "info";
    $resultado['titulo'] = "No es posible!";
}


echo json_encode($resultado);
