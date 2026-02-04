<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

$datos = (array) json_decode($datos , true);

$query = 'SELECT * FROM Eps WHERE Nombre = "'.$datos["EPS"].'"';
$oCon= new consulta();
$oCon->setQuery($query);
//$oCon->setTipo('Multiple');
$eps = $oCon->getData();
unset($oCon);

   $oItem = new complex('Paciente',"Id_Paciente");
   $datos["Nit"]=$eps["Nit"];
   foreach($datos as $index=>$value){
        $oItem->$index=$value;
   }
   $oItem->save();
   unset($oItem);
     
 
$resultado['mensaje'] = "Se ha guardado correctamente el paciente ";
$resultado['tipo'] = "success";
$resultado['titulo'] = "Paciente Creado Correctamente";

echo json_encode($resultado);
?>