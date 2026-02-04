<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');
    
$contabilizar = new Contabilizar();

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

$datos = (array) json_decode($datos, true);
    
$oItem = new complex('Factura_Venta',"Id_Factura_Venta", $datos['Id_Factura_Venta']);
$data = $oItem->getData();
$fecha = date('Y-m-d',strtotime($data['Fecha_Documento']));

if ($contabilizar->validarMesOrAnioCerrado($fecha)) {
    $observaciones=$oItem->Observacion_Factura_Venta;

    $oItem->Estado="Anulada";
    $oItem->Observacion_Factura_Venta=$observaciones.' NOTA-ANULACION: '.$datos['Observaciones'];
    $oItem->Fecha_Anulacion=date("Y-m-d H:i:s");
    $oItem->Funcionario_Anula=$datos['Funcionario_Anula'];
    $oItem->Id_Causal_Anulacion=$datos['Id_Causal_Aunulacion'];
    $oItem->save();
    unset($oItem);

    $cod_remisiones = [];

    $codigos = explode(', ', $observaciones); // La variable observaciones contiene el/los códigos de la remisiones.

    foreach ($codigos as $value) {
        $codigo = trim($value);

        $cod_remisiones[] = "'".$codigo."'";
    }

    $cod_remisiones = implode(",",$cod_remisiones);

    /* $query='SELECT GROUP_CONCAT(DISTINCT PFV.Id_Remision) as Remisiones FROM Producto_Factura_Venta PFV WHERE PFV.Id_Factura_Venta='.$datos['Id_Factura_Venta'];

    $oCon= new consulta();
    $oCon->setQuery($query);
    $remisiones = $oCon->getData();
    unset($oCon); */


    $queryremisiones='UPDATE Remision SET Estado ="Enviada"
    WHERE Codigo IN ('.$cod_remisiones.')';

    $oCon= new consulta();
    $oCon->setQuery($queryremisiones);     
    $oCon->createData();     
    unset($oCon);

    AnularMovimientoContable($datos['Id_Factura_Venta']);

    $resultado['mensaje'] = "Se anulado la factura correctamente";
    $resultado['tipo'] = "success";
} else {
    $resultado['mensaje'] = "No es posible anular esta factura debido a que el mes o el año del documento ha sido cerrado contablemente. Si tienes alguna duda por favor comunicarse al Dpto. Contabilidad.";
    $resultado['tipo'] = "info";
}

echo json_encode($resultado);

function AnularMovimientoContable($idRegistroModulo){
    global $contabilizar;

    $contabilizar->AnularMovimientoContable($idRegistroModulo, 2);
}

?>		