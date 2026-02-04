<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$concepto = ( isset( $_REQUEST['concepto'] ) ? $_REQUEST['concepto'] : '' );

$concepto = (array) json_decode($concepto);
if($concepto['Id_Concepto_Parametro_Nomina']!=''){
    $oItem = new complex("Concepto_Parametro_Nomina","Id_Concepto_Parametro_Nomina",$concepto['Id_Concepto_Parametro_Nomina']);
   foreach ($concepto as $index => $value) {
       $oItem->$index=$value;
   }
    $oItem->save();
    unset($oItem);
    $resultado['mensaje']="Guardado Correctamente";
    $resultado['tipo']="success";
}else{
    $oItem = new complex("Concepto_Parametro_Nomina","Id_Concepto_Parametro_Nomina");
   unset($concepto['Id_Concepto_Parametro_Nomina']);
   if($concepto['Id_Contrapartida']==''){
    unset($concepto['Id_Contrapartida']);
   }
    foreach ($concepto as $index => $value) {
        $oItem->$index=$value;
    }
     $oItem->save();
     unset($oItem);
     $resultado['mensaje']="Guardado Correctamente";
     $resultado['tipo']="success";
}






echo json_encode($resultado);
?>