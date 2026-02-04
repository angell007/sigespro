<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


require_once('./../config/start.inc.php');
include_once('./../class/class.lista.php');
include_once('./../class/class.complex.php');
include_once('./../class/class.consulta.php');

$query = 'SELECT I.Lote, I.Id_Producto, I.Cantidad, I.Id_Bodega, I.Fecha_Vencimiento, I.Id_Inventario,
(SELECT Cantidad FROM Saldo_Inicial_Kardex SIK WHERE SIK.Lote = I.Lote AND SIK.Id_Producto=I.Id_Producto AND SIK.Id_Bodega=I.Id_Bodega AND SIK.Fecha="2018-09-30") as Cantidad_Inicial,
 
 
(SELECT SUM(PAR.Cantidad) 
 FROM Producto_Acta_Recepcion PAR 
 INNER JOIN Acta_Recepcion AR
 ON AR.Id_Acta_Recepcion = PAR.Id_Acta_Recepcion
 WHERE PAR.Id_Producto = I.Id_Producto AND PAR.Lote=I.Lote AND AR.Fecha_Creacion BETWEEN "2018-10-01 00:00:00" AND "2018-10-31 23:59:59") as Cantidad_Comprada,
 
 (SELECT SUM(PR.Cantidad)
        FROM Producto_Remision PR
        INNER JOIN Remision R
        ON R.Id_Remision = PR.Id_Remision
        WHERE R.Estado IN ("Alistada","Enviada","Facturada","Recibida") AND R.Id_Origen = I.Id_Bodega AND PR.Lote = I.Lote AND PR.Id_Producto = I.Id_Producto AND R.Fecha BETWEEN "2018-10-01 00:00:00" AND "2018-10-31 23:59:59" ) as Cantidad_Remisionada 
        
        FROM Inventario I WHERE Id_Punto_Dispensacion=0 ';
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$inventarios = $oCon->getData();
unset($oCon);

foreach($inventarios as $inv){
    if($inv["Id_Producto"]!=''){
        $total=(INT)$inv["Cantidad_Inicial"]+(INT)$inv["Cantidad_Comprada"]-(INT)$inv["Cantidad_Remisionada"];
        if($total<0){
            $total=0;
        }
        $oItem = new complex("Saldo_Inicial_Kardex","Id_Saldo_Inicial_Kardex");
        $oItem->Fecha="2018-10-31";
        $oItem->Id_Producto = $inv["Id_Producto"];
        $oItem->Cantidad = $total;
        $oItem->Lote = $inv["Lote"];
        if($inv["Fecha_Vencimiento"]!='0000-00-00'){
            $oItem->Fecha_Vencimiento = date("Y-m-d", strtotime($inv["Fecha_Vencimiento"]));
        }
        $oItem->Id_Inventario = $inv["Id_Inventario"];
        $oItem->Id_Bodega = $inv["Id_Bodega"];
        //$oItem->save();
        unset($oItem); 
        
        echo $inv["Fecha_Vencimiento"]." ->".$inv["Id_Producto"]." - ".$inv["Id_Bodega"] ." - ".$inv["Lote"]." - ".(INT)$inv["Cantidad_Inicial"]." - ".(INT)$inv["Cantidad_Comprada"]." - ".(INT)$inv["Cantidad_Remisionada"]."<b>".$total."</b><br>";
     
    }
    
}

?>