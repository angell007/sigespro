<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');




$query='SELECT PDP.Id_Producto_Dispensacion_Pendiente,PD.Id_Dispensacion,P.Nombre_Comercial,PDP.Timestamp, (SELECT AD.Fecha FROM Actividades_Dispensacion AD WHERE AD.Id_Dispensacion=PD.Id_Dispensacion AND AD.Detalle LIKE "%Se entrego la dispensacion pendiente%" LIMIT 1) as Fecha_Entrega
FROM Producto_Dispensacion_Pendiente PDP 
INNER JOIN Producto_Dispensacion PD ON PDP.Id_Producto_Dispensacion=PD.Id_Producto_Dispensacion INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto
WHERE PDP.Timestamp LIKE "%2019-01-04%" 
HAVING Fecha_Entrega IS NOT  NULL AND Fecha_Entrega!=Timestamp';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

$i=-1;

foreach ($resultado as  $value) {
  if($value['Fecha_Entrega']!=''){
    $i++;
    $oItem = new complex('Producto_Dispensacion_Pendiente','Id_Producto_Dispensacion_Pendiente',$value['Id_Producto_Dispensacion_Pendiente']);
    $oItem->Timestamp= $value['Fecha_Entrega'];
    //$oItem->save();           
    unset($oItem);
  }

  
}

echo "Se actualizaron ".$i."\r\n";
?>