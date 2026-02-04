<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$datos = (array) json_decode($datos);


//Guardar actividad no confornme remision 
$oItem = new complex('Actividad_No_Conforme_Remision',"Id_Actividad_No_Conforme_Remision");
foreach($datos as $index=>$value){
   $oItem->$index=$value;
   if($datos["Acciones"]==="Revisada"){
   $oItem->Detalles = "La no Conformidad de la remision fue Revisada, Con la siguiente observacion ".$datos["Observaciones"];
   $oItem->Estado = "Revisada";
   }elseif($datos["Acciones"]==="Aprobada"){
    $oItem->Detalles = "La no Conformidad de la remision fue Aprobada, Con la siguiente observacion ".$datos["Observaciones"];
    $oItem->Estado = "Aprobada";
   }elseif($datos["Acciones"]==="Solucionada"){
    $oItem->Detalles = "La no Conformidad de la remision fue Solucionada, Con la siguiente observacion".$datos["Observaciones"];
    $oItem->Estado = "Solucionada";
    
   }
}

$oItem->save();
unset($oItem);

if($datos["Acciones"]==="Solucionada"){
    $oItem = new complex('No_Conforme',"Id_No_Conforme",$datos["Id_No_Conforme"]);
    $oItem->Estado="Solucionada";
    $oItem->save();
}elseif($datos["Acciones"]==="Aprobada"){
    $oItem = new complex('No_Conforme',"Id_No_Conforme",$datos["Id_No_Conforme"]);
    $oItem->Estado="Aprobada";
    $oItem->save();
}elseif($datos["Acciones"]==="Revisada"){
    $oItem = new complex('No_Conforme',"Id_No_Conforme",$datos["Id_No_Conforme"]);
    $oItem->Estado="Revisada";
    $oItem->save();
}
unset($oItem);

$query = 'SELECT NC.*, CONCAT(F.Nombres," ", F.Apellidos) as Nombre_Funcionario
FROM No_Conforme NC
INNER JOIN Funcionario F
On NC.Persona_Reporta=F.Identificacion_Funcionario
WHERE NC.Tipo = "Remision"';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$datos = $oCon->getData();
unset($oCon);

echo json_encode($datos);
?>