<?php
 header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json'); 

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');



$id = ( isset( $_REQUEST['punto'] ) ? $_REQUEST['punto'] : '' );


$query = 'SELECT R.Id_Remision, R.Codigo, R.Nombre_Origen, "0" as Seleccionada,R.Fecha
FROM Remision R
WHERE R.Tipo_Origen="Bodega" AND R.Entrega_Pendientes="No" AND R.Tipo_Destino="Punto_Dispensacion" AND (R.Estado="Alistada" OR R.Estado="Enviada" ) AND R.Id_Destino='.$id;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$remisiones = $oCon->getData();
unset($oCon);

echo json_encode($remisiones);

?>