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

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$datos =  json_decode($datos,true );


$oItem=new complex('Precio_Regulado',"Id_Precio_Regulado");
foreach ($datos as $key => $value) {
	# code...
	if($key !='Codigo_Cum'){
		$oItem->$key= number_format($value,2,".","");
	}else{
		$oItem->$key=$value;
	}
}
$oItem->save();
unset($oItem);

$resultado['mensaje']="Se ha Guardado Correctamente!";
$resultado['tipo']="success";

echo json_encode($resultado);

?>