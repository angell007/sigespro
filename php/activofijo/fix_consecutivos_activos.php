<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = "SELECT * FROM Activo_Fijo WHERE Codigo = 'MANTIS'";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$activos = $oCon->getData();
unset($oCon);

$consecutivo = 12;
foreach ($activos as $act) {
    $oItem = new complex('Activo_Fijo','Id_Activo_Fijo',$act['Id_Activo_Fijo']);
    $codigo = 'AFS201901' . str_pad($consecutivo,3,'0',STR_PAD_LEFT);
    $oItem->Codigo = $codigo;
    $oItem->save();
    unset($oItem);

    $consecutivo++;
}

echo "Terminó";
          
?>