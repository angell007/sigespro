<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set('America/Bogota');

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$codigo = ( isset( $_REQUEST['codigo'] ) ? $_REQUEST['codigo'] : '' );
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$destino = ( isset( $_REQUEST['destino'] ) ? $_REQUEST['destino'] : '' );
 
$datos = (array) json_decode($datos);
$datos =json_encode($datos);


$query='SELECT B.Codigo as Codigo, B.Id_Borrador as Id_Borrador
FROM Borrador B
WHERE B.Codigo="'.$codigo.'"';
$oCon= new consulta();
$oCon->setQuery($query);
$respuesta = $oCon->getData();
unset($oCon);

if($respuesta!=''|| $respuesta!=null){
     $oItem = new complex($mod,"Id_".$mod,$respuesta['Id_Borrador']);
}else{
     $oItem = new complex($mod,"Id_".$mod);
}
$oItem->Tipo="Remision";
$oItem->Codigo=$codigo;
$oItem->Texto=$datos;
$oItem->Id_Funcionario=$funcionario;
$oItem->Fecha=date("Y-m-d H:i:s");
$oItem->Nombre_Destino=$destino;
$oItem->save();
unset($oItem);

$resultado="Guardado Automaticamente";
echo json_encode($resultado);
?>





