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


$Codigo = substr(hexdec(uniqid()),2,12);
  
  
echo ($Codigo);

//$oitem = new Complex("Producto_Acta_Recepcion" , "Id_Producto_Acta_Recepcion");
?>