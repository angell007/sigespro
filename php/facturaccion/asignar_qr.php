<?php
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
  header('Content-Type: application/json');
  include_once('../../class/class.querybasedatos.php');
  require_once('../../class/class.qr.php'); 

   $id_factura=24246;

     /* AQUI GENERA QR */
     $qr = generarqr('factura',$id_factura,'/IMAGENES/QR/');;
     $oItem = new complex("Factura","Id_Factura",$id_factura);
     $oItem->Codigo_Qr=$qr;
     $oItem->save();
     unset($oItem);

?>