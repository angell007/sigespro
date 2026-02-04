<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$oLista = new lista("Dependencia");
$oLista->setRestrict("Id_Grupo","=",$id);
$Dependencias= $oLista->getlist();

/*echo "<option value=''>Seleccione</option>";
foreach($dependencias as $dep){
	echo "<option value='".$dep["Id_Dependencia"]."'>".$dep["Nombre"]."</option>";
}*/

echo json_encode($Dependencias);

?>
