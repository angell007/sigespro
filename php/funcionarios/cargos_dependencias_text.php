<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

 

$oItem = new complex("Cargo","Id_Dependencia",$id);

$Cargos = $oItem->save();
unset($oItem);

/*echo "<option value=''>Seleccione</option>";
foreach($cargos as $cargo){
	echo "<option value='".$cargo["Id_Cargo"]."'>".$cargo["Nombre"]."</option>";
}*/

echo json_encode($Cargos);
?>
