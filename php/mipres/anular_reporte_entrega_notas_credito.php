<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

include_once('../../class/class.consulta.php');

include_once('../../class/class.complex.php');

require_once('../../class/class.configuracion.php');

include_once('../../class/class.mipres.php');

$mipres= new Mipres();


$query = ' SELECT 
 PDM.IdReporteEntrega, PDM.Id_Producto_Dispensacion_Mipres
FROM Producto_Factura PF 
INNER JOIN Producto_Nota_Credito_Global PN ON PN.Id_Producto = PF.Id_Producto_Factura AND PN.Tipo_Producto = "Producto_Factura"
INNER JOIN Producto_Dispensacion PD ON PD.Id_Producto_Dispensacion = PF.Id_Producto_Dispensacion
INNER JOIN Producto_Dispensacion_Mipres PDM ON PDM.Id_Producto_Dispensacion_Mipres = PD.Id_Producto_Dispensacion_Mipres

WHERE PDM.IdReporteEntrega > 0';

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');

$IdsReporte = $oCon->getData();

foreach($IdsReporte as $key => $value){
  echo json_encode($value);
  $mipres->AnularReporteEntrega($value['IdReporteEntrega']);  
  $oItem = new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$value['Id_Producto_Dispensacion_Mipres']);
  $oItem->IdReporteEntrega="0";
  $oItem->save();
  unset($oItem);
  break; 
  echo '</br>';
}


