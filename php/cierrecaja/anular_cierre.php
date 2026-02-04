<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$modelo  = (isset($_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );

$modelo = json_decode($modelo, true);



$oItem=new Complex('Diario_Cajas_Dispensacion', 'Id_Diario_Cajas_Dispensacion',$modelo['Id_Cierre']);
$oItem->Estado="Anulado";
$oItem->Funcionario_Anula=$modelo['Identificacion_Funcionario'];
$oItem->Fecha_Anulacion=date("Y-m-d H:i:s");;
$oItem->save();
unset($oItem);

$query='UPDATE Dispensacion SET Id_Diario_Cajas_Dispensacion= NULL WHERE Id_Diario_Cajas_Dispensacion='.$modelo['Id_Cierre'];
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->createData();
unset($oCon);
$resultado='Operacion exitosa !';


echo json_encode($resultado);
?>