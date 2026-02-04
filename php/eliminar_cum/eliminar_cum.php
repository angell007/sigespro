<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query='SELECT GROUP_CONCAT(Id_Producto_Lista_Ganancia) as Id,`Id_Producto_Lista_Ganancia`, `Cum`, `Precio`, `Id_Lista_Ganancia`, COUNT(Cum)  FROM `Producto_Lista_Ganancia` GROUP BY Cum, Id_Lista_Ganancia   HAVING COUNT(Cum)>1 ORDER BY Id_Producto_Lista_Ganancia ASC';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$res = $oCon->getData();
unset($oCon);

foreach ($res as  $value) {
    $id=explode(",", $value['Id']);
    for ($i=0; $i < count($id); $i++) {         
       if($i!=0){
        $oItem=new complex ('Producto_Lista_Ganancia','Id_Producto_Lista_Ganancia',$id[$i] );
        //$oItem->delete();
        unset($oItem);
       }
    }
   
}



?>