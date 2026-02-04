<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query='SELECT lct.Id_Cliente, lct.Lista_Ganancia, lct.Porcentaje FROM Cliente c INNER JOIN Lista_Cliente_Temporal lct ON c.Id_Cliente=lct.Id_Cliente';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

foreach($resultado as $result){
    $oItem=new complex('Cliente', 'Id_Cliente', $result['Id_Cliente']);  
    $oItem->Id_Lista_Ganancia=$result['Lista_Ganancia'];                
    $oItem->Porcentaje=$result['Porcentaje'];                
    $oItem->save();
    unset($oItem);
}

?>