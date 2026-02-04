<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require('./funciones.php');

$query = "SELECT Id_Activo_Fijo, Id_Tipo_Activo_Fijo FROM Activo_Fijo WHERE YEAR(Fecha) = 2019 AND Estado = 'Activo' ORDER BY Fecha";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$activos = $oCon->getData();
unset($oCon);

foreach ($activos as $i => $value) {
    $consecutivo = generarConsecutivoTipoActivo($value['Id_Tipo_Activo_Fijo']);

    $oItem = new complex('Activo_Fijo','Id_Activo_Fijo',$value['Id_Activo_Fijo']);
    $oItem->Codigo_Activo_Fijo = $consecutivo;
    $oItem->save();
    unset($oItem);
}

echo "Realizado con exito";


          
?>