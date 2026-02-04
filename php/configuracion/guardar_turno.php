<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$dias = ( isset( $_REQUEST['dias'] ) ? $_REQUEST['dias'] : '' );

$datos = (array) json_decode($datos);
$dias = (array) json_decode($dias,true);

if(isset($datos['Id']) && ($datos['Id']!=null || $datos['Id']!="")){
    $oItem = new complex("Turno","Id_Turno",$datos['Id']);
      
}else{
    $oItem = new complex("Turno","Id_Turno");
}
$datos['Nombre']=strtoupper($datos['Nombre']);
$datos['Tipo']='Diurno';
foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
$id_turno = $oItem->getId();
unset($oItem);

if($datos['Tipo_Turno']=='Fijo'){
  
    foreach ($dias as $key => $value) {
   
        if(isset($value['Id']) && ($value['Id']!=null || $value['Id']!="")){
            $oItem = new complex("Hora_Turno","Id_Hora_Turno",$value['Id']);
              
        }else{
            $oItem = new complex("Hora_Turno","Id_Hora_Turno");
        }
        foreach ($value as $k => $v) {
            $oItem->$k=$v=='' ? '00:00:00' :$v ;
        }
       
        $oItem->Id_Turno=$id_turno;       
        $oItem->save();
        unset($oItem);
    }
}

$resultado['mensaje'] = "¡Hoja de Vida Guardada Exitosamente!";
$resultado['tipo'] = "success";

echo json_encode($resultado);
?>