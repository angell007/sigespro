<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');




$query='SELECT PD.Id_Dispensacion, PD.Id_Producto, PD.Fecha_Carga, PDP.Timestamp, PDP.Id_Producto_Dispensacion_Pendiente,PD.Id_Producto_Dispensacion
FROM Producto_Dispensacion_Pendiente PDP INNER JOIN Producto_Dispensacion PD ON PDP.Id_Producto_Dispensacion=PD.Id_Producto_Dispensacion ORDER BY PDP.Id_Producto_Dispensacion_Pendiente DESC '  ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

$i=-1;

foreach ($resultado as  $value) {
  $query='SELECT * FROM Producto_Dispensacion PD WHERE PD.Id_Producto='.$value['Id_Producto'].' AND PD.Id_Dispensacion='.$value['Id_Dispensacion'].' AND Id_Producto_Dispensacion!='.$value['Id_Producto_Dispensacion'].' AND PD.Fecha_Carga="'.$value['Timestamp'].'" ORDER BY PD.Id_Producto_Dispensacion ASC';

  $oCon= new consulta();
  $oCon->setQuery($query);
  $oCon->setTipo('Multiple');
  $productos = $oCon->getData();
  unset($oCon);

  if(count($productos)>1){$i++;
    echo $value['Id_Producto_Dispensacion_Pendiente']." ------ Contador ".$i."-----DISP --".$value['Id_Dispensacion']."   ".$value['Id_Producto_Dispensacion']."----- Items".count($productos)."----".$value['Timestamp']."<br>";

    foreach ($productos as  $item) {
      echo "---".$item['Fecha_Carga']." ----- ".$item['Id_Producto_Dispensacion']."<br>";
      $oItem=new complex('Producto_Dispensacion_Pendiente','Id_Producto_Dispensacion_Pendiente',$value['Id_Producto_Dispensacion_Pendiente']);
      $oItem->Id_Producto_Dispensacion=$item['Id_Producto_Dispensacion'];
      //$oItem->save();
      unset($oItem);
    }

  }

  
}

echo "Se actualizaron ".$i."\r\n";
?>