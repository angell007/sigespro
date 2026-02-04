<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');


$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$oLista = new lista("Tipo_Soporte");
$oLista->setRestrict("Id_Tipo_Servicio","=",$id);
$Soportes= $oLista->getlist();
$i=-1;
foreach($Soportes as $Sorpote){$i++;
if($Soportes[$i]['Pre_Auditoria']==="Si"){
    $Soportes[$i]['Pre_Auditoria']="true";
}else{
     $Soportes[$i]['Pre_Auditoria']="false";
}
    
}


echo json_encode($Soportes);

?>