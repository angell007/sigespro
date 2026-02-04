<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query="SELECT Id_Factura FROM Factura WHERE Id_Funcionario=1095815196 AND DATE(Fecha_Documento) = CURDATE()";

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$facturas = $oCon->getData();
unset($oCon);



foreach ($facturas as  $value) {
    $oItem = new complex('Factura','Id_Factura', $value['Id_Factura']);
    $id_dispensacion = $oItem->Id_Dispensacion;
    $tipo_factura = $oItem->Tipo;
    $factura = $oItem->Codigo;
    
    $oItem->Estado_Factura = 'Anulada';
    $oItem->Observacion_Factura = 'Anulacion Masiva';
    $oItem->Id_Causal_Anulacion = 2;
    $oItem->Funcionario_Anula = 12345;
    $oItem->save();
    unset($oItem);

    if ($tipo_factura != 'homologo') { 
    $oItem = new complex('Dispensacion','Id_Dispensacion',$id_dispensacion);
    $oItem->Estado_Facturacion = 'Sin Facturar';
    $oItem->Id_Factura = "0";
    $oItem->Fecha_Facturado = '0000-00-00';
    $oItem->save();
    unset($oItem);
    }
}


echo "Termino ";