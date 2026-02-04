<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' ); 

$query1 = 'SELECT NC.Persona_reporta as Persona, NC.Descripcion as Descripcion, NC.Factura as Factura , TNC.Id_No_Conforme as Tratamiento_No_Conforme
          FROM No_Conforme NC 
          INNER JOIN Tratamiento_No_Conforme TNC
          on TNC.Id_No_Conforme=NC.Id_No_Conforme
          WHERE NC.Id_No_Conforme = '.$id;

$oCon= new consulta();
$oCon->setQuery($query1);
$dis = $oCon->getData();
unset($oCon);          
          
$query2 = 'SELECT T.Id_Tratamiento as IdTratamiento, T.Nombre_Tratamiento as NombreTratamiento, F.Fecha as Fecha, F.Responsable as Responsable, F.Id_Tratamiento_No_Conforme as IdTNC
          FROM Tratamiento T
          INNER JOIN Tratamiento_No_Conforme F
          on T.Id_Tratamiento=F.Id_Tratamiento 
          WHERE F.Id_No_Conforme = '.$id ;

$oCon= new consulta();
$oCon->setQuery($query2);
$productos = $oCon->getData();
unset($oCon);          

$resultado["Datos"]=$dis;
$resultado["Tratamiento"]=$productos;

echo json_encode($resultado);
?>