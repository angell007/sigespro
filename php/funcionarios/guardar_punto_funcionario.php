<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');


require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');

$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$punto = ( isset( $_REQUEST['punto'] ) ? $_REQUEST['punto'] : '' );


$query = 'SELECT FPA.* FROM Funcionario_Punto_Activo FPA WHERE FPA.Identificacion_Funcionario='.$funcionario;
        

$oCon= new consulta();
$oCon->setQuery($query);
$id_funcionario = $oCon->getData();
unset($oCon);

if($id_funcionario){
    $oItem=new complex('Funcionario_Punto_Activo',"Id_Funcionario_Punto_Activo",$id_funcionario['Id_Funcionario_Punto_Activo']);
    $oItem->Id_Punto_Dispensacion=$punto;
    $oItem->save();
    unset($oItem);
}else{
    $oItem=new complex('Funcionario_Punto_Activo',"Id_Funcionario_Punto_Activo");
    $oItem->Id_Punto_Dispensacion=$punto;
    $oItem->Identificacion_Funcionario=$funcionario;
    $oItem->save();
    unset($oItem);
}


$resultado['mensaje']="Se ha Guardado Correctamente!";
$resultado['tipo']="success";

echo json_encode($resultado);

?>