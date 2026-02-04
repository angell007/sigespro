<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$query='SELECT IT.Id_Inventario_Inicial, IT.Alternativo FROm Inventario_Inicial_Temporal IT WHERE IT.Alternativo IS NOT NULL ';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);


foreach($resultado as $result){
    $oItem=new complex('Inventario_Inicial', 'Id_Inventario_Inicial', $result['Id_Inventario_Inicial']);  
    $oItem->Alternativo=$result['Alternativo'];                
    $oItem->save();
    unset($oItem);
}

?>