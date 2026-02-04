<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$turnero = ( isset( $_REQUEST['turnero'] ) ? $_REQUEST['turnero'] : '' );

$query = "SELECT * FROM Turneros WHERE Id_Turneros =".$turnero;

$oCon= new consulta();
$oCon->setQuery($query);
$detalle = $oCon->getData();
unset($oCon);

$query="SELECT *, '0' as Seleccionado FROM Prioridad_Turnero ";

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$prioridad = $oCon->getData();
unset($oCon);

$query="SELECT S.Nombre,'0' as Seleccionado,
(CASE 
WHEN S.Autorizacion='Si' THEN 'CON AUTORIZACION'
ELSE 'SIN AUTORIZACION'
END ) as Texto, S.Autorizacion, S.Nombre as Tipo
 FROM Servicio_Turnero ST INNER JOIN Servicio S ON ST.Id_Servicio=S.Id_Servicio  WHERE Id_Turnero=$turnero";

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$servicio = $oCon->getData();
unset($oCon);

$resultado['Detalle']=$detalle;
$resultado['Prioridad']=$prioridad;
$resultado['Servicios']=$servicio;


echo json_encode($resultado);


?>