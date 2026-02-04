<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');



$query='SELECT I.Id_Inventario, I.Lote, I.Fecha_Vencimiento, I.Id_Producto, II.Cantidad as Cantidad_Inicial, I.Cantidad as Cantidad_Actual, I.Cantidad_Apartada, I.Cantidad_Seleccionada, 
(SELECT SUM(PAR.Cantidad) 
 FROM Producto_Acta_Recepcion PAR 
 WHERE PAR.Id_Producto = I.Id_Producto AND PAR.Lote=I.Lote AND PAR.Fecha_Vencimiento=I.Fecha_Vencimiento) Cantidad_Comprada,
(SELECT SUM(PR.Cantidad) 
 FROM Producto_Remision PR 
 INNER JOIN Remision R
 ON R.Id_Remision = PR.Id_Remision
 WHERE PR.Id_Inventario = I.Id_Inventario AND (R.Estado = "Enviada" OR R.Estado = "Alistada" OR R.Estado = "Facturada" )) Consumida,
 (SELECT SUM(PR.Cantidad) 
 FROM Producto_Remision PR 
 INNER JOIN Remision R
 ON R.Id_Remision = PR.Id_Remision
 WHERE PR.Id_Inventario = I.Id_Inventario AND (R.Estado = "Pendiente" )) Apartada2, 
(SELECT SUM(PR2.Cantidad) FROM Producto_Remision_Antigua PR2 WHERE PR2.Id_Inventario = I.Id_Inventario ) Inicial
FROM Inventario I
LEFT JOIN Inventario_Inicial II
ON I.Id_Producto = II.Id_Producto AND I.Lote = II.Lote AND I.Fecha_Vencimiento = II.Fecha_Vencimiento
Where I.Id_Bodega = 5 AND I.Id_Punto_Dispensacion = 0
HAVING Cantidad_Inicial IS NOT NULL';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

//var_dump($resultado);

//$query='';


foreach($resultado as $item){

$comprada 	= number_format((INT)$item["Cantidad_Comprada"],0,"","");

$primer_inv =   (INT)$item["Cantidad_Inicial"];
$actual =   (INT)$item["Cantidad_Actual"];
$apartada = (INT)$item["Cantidad_Apartada"];
$consumida = (INT)$item["Consumida"];
$inicial = (INT)$item["Inicial"];

//$total 		= (INT)$item["Cantidad_Inicial"]+(INT)$item["Cantidad_Compra"]-(INT)$item["Inicial"]-(INT)$item["Consumida"];
$apartada2 	= number_format((INT)$item["Apartada2"],0,"","");

$total = $primer_inv-$consumida-$inicial;
if($total<0){
    $total=0;
}
//if(($actual==$apartada) && ($actual==$consumida) && $apartada2==0){
   echo $item["Id_Inventario"]." , Lote ".$item["Lote"]." , Inicial ".(INT)$item["Cantidad_Inicial"]." , Actual ".(INT)$item["Cantidad_Actual"]." , Consumida ".(INT)$item["Consumida"]." , Apartada ".(INT)$item["Cantidad_Apartada"]." , Apartada2 ".(INT)$item["Apartada2"]." , Primer ".(INT)$item["Inicial"]."  : Total ".(INT)$total."<br>";
//   $total=0;
//}

$oItem = new complex("Inventario","Id_Inventario",$item["Id_Inventario"]);
$oItem->Cantidad = number_format($total,0,"","");
$oItem->Cantidad_Apartada = number_format($apartada2,0,"","");
//$oItem->Cantidad_Seleccionada = number_format(0,0,"",""); 
//$oItem->save();  
unset($oItem);

//echo "apartada: ".$apartada."<br>";
/*if($total<=0){
$total=0;
}*/

/*
if((INT)$item["Cantidad_Inicial"]==0&&(INT)$item["Cantidad_Compra"]==0){
$query.='UPDATE Inventario SET Cantidad='.$total.', Cantidad_Apartada ='.number_format($apartada,0,"","").'' WHERE Id_Inventario = '.$item["Id_Inventario"].';';
 */
}




/*
$oItem = new complex("Inventario","Id_Inventario",$item["Id_Inventario"]);
$oItem->Cantidad = $total;
$oItem->Cantidad_Apartada = number_format($apartada,0,"","");
$oItem->Cantidad_Seleccionada = number_format(0,0,"",""); 
$oItem->save();  
unset($oItem);
*/

//}
//echo $query;
/*
$oLista = new Lista("Remision");
$remisiones = $oLista->getList();
unset($oLista);


foreach($remisiones as $rem){
	$oItem = new complex($rem["Tipo_Origen"],"Id_".$rem["Tipo_Origen"],$rem["Id_Origen"]);
	$or = $oItem->getData();
	unset($oItem); 
	
	$oItem = new complex($rem["Tipo_Destino"],"Id_".$rem["Tipo_Destino"],$rem["Id_Destino"]);
	$des = $oItem->getData();
	unset($oItem); 



	$oItem = new complex("Remision","Id_Remision",$rem["Id_Remision"]);
	$oItem->Nombre_Origen = $or["Nombre"];
	$oItem->Nombre_Destino = $des["Nombre"];
	//$oItem->save();
	unset($oItem);
	

echo $rem["Codigo"]." - ".$or["Nombre"]." - ".$des["Nombre"]."<br>";*/
//}


?>