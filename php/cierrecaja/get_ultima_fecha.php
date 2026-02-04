<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

$punto = ( isset( $_REQUEST['punto'] ) ? $_REQUEST['punto'] : '' );
		
$query="SELECT * FROM Soporte_Consignacion WHERE Id_Punto_Dispensacion =$punto  ORDER BY Id_Soporte_Consignacion DESC LIMIT 1 ";

$oCon= new consulta();
$oCon->setQuery($query);
$fecha= $oCon->getData();
unset($oCon);

echo json_encode($fecha);
	



?>