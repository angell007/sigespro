<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');


require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT Balanza  From Configuracion WHERE Id_Configuracion = 1';

$oCon= new consulta();
$oCon->setQuery($query);
//$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

$res;
if ($resultado['Balanza']=='0') {
    $res=false;
}else{
    $res=true;
}

$data['Estado_Balanza']=$res;

echo json_encode($data);