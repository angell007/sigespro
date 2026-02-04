<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');



$query='SELECT I.*, (I.Cantidad%P.Cantidad_Presentacion) as Modulo, P.Cantidad_Presentacion
FROM Inventario I 
INNER JOIN Producto P
ON I.Id_Producto=P.Id_Producto
WHERE I.Id_Bodega=1 OR I.Id_Bodega=5
ORDER BY Modulo DESC';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

$i=0;
foreach($resultado as $res){ $i++;
    if($res["Modulo"]>0){
        $oItem = new complex("Inventario","Id_Inventario", $res['Id_Inventario']);
        $general=number_format($res["Cantidad"],0,"","");
        $menudeo=number_format($res["Modulo"],0,"","");
        $total=$general-$menudeo;
        $oItem->Cantidad=number_format($total,0,"","");
        echo $general."----".$menudeo.' - '.$total.' - '.$res['Cantidad_Presentacion']."-----".$res['Id_Inventario'].'<br>';
      // $oItem->save();
        unset($oItem);
        $oItem = new complex("Inventario","Id_Inventario");
        $oItem->Codigo=$res['Codigo'];
        $oItem->Id_Producto=$res['Id_Producto'];
        $oItem->Codigo_CUM=$res['Codigo_CUM'];
        $oItem->Lote=$res['Lote'];
        $oItem->Fecha_Vencimiento=$res['Fecha_Vencimiento'];
        $oItem->Id_Bodega=7;
        $oItem->Cantidad=number_format($menudeo,0,"","");
        $oItem->Costo=$res['Costo'];
        $oItem->Alternativo=$res['Alternativo'];
      //  $oItem->save();
        unset($oItem);
        
    }
}
?>