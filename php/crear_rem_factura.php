<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');
require_once('../class/class.configuracion.php');
require_once('../class/class.qr.php');

/*$configuracion = new Configuracion();
$codigo=$configuracion->Consecutivo('Remision');
*/
/*
$oItem = new complex("Remision","Id_Remision");
$oItem->Tipo='Interna';
$oItem->Identificacion_Funcionario=91494140;
$oItem->Observaciones='Traslado del Inventario del punto de Bogota a Bodega';
$oItem->Codigo="REM29239";
$oItem->Tipo_Origen='Punto_Dispensacion';
$oItem->Nombre_Origen='PUNTO BOGOTA';
$oItem->Id_Origen=21;
$oItem->Tipo_Destino='Bodega';
$oItem->Nombre_Destino='VENCIMIENTOS';
$oItem->Id_Destino=8;
$oItem->Estado='Enviado';
$oItem->Estado_Alistamiento=2;
$oItem->Prioridad='1';
$oItem->save();
$id_remision = $oItem->getId();
unset($oItem);

$qr = generarqr('remision',$id_remision,'/IMAGENES/QR/');
$oItem = new complex("Remision","Id_Remision",$id_remision);
$oItem->Codigo_Qr=$qr;
$oItem->save();
unset($oItem);
*/

/*$query="SELECT 
PD.*, I.Cantidad AS Cantidad_Inventario, IFNULL(ROUND(I.Costo),0) as Precio,(PD.Cantidad*IFNULL(ROUND(I.Costo),0)) as Subtotal, I.Id_Inventario, I.Id_Punto_Dispensacion
FROM
(SELECT 
	PF.Id_Producto,
		PF.Lote,
		PF.Fecha_Vencimiento,
		SUM(PF.Cantidad_Final) AS Cantidad,
		SUM(PF.Cantidad_Final) as Cantidad_Total,
		IF(P.Gravado='Si', 19,0) as Impuesto,
		P.Nombre_Comercial as Nombre_Producto,
		group_concat(PF.Id_Producto_Inventario_Fisico) as Id
FROM
	Producto_Inventario_Fisico_Punto PF
INNER JOIN Inventario_Fisico_Punto I ON PF.Id_Inventario_Fisico_Punto = I.Id_Inventario_Fisico_Punto
INNER JOIN Producto P On PF.Id_Producto=P.Id_Producto
WHERE
	PF.Id_Inventario_Fisico_Punto IN (260 , 261, 262)
GROUP BY Id_Producto, Lote) PD
	INNER JOIN
Inventario I ON PD.Id_Producto = I.Id_Producto
	AND PD.Lote = I.Lote
WHERE
I.Id_Punto_Dispensacion = 154 having PD.Cantidad<=I.Cantidad";*/

$query="SELECT I.Id_Producto,
I.Lote,
I.Fecha_Vencimiento,
I.Cantidad AS Cantidad,
I.Cantidad as Cantidad_Total,
IF(P.Gravado='Si', 19,0) as Impuesto,
P.Nombre_Comercial as Nombre_Producto,
IFNULL(ROUND(I.Costo),0) as Precio,
(I.Cantidad*IFNULL(ROUND(I.Costo),0)) as Subtotal, I.Id_Inventario

 FROM Inventario I 
 INNER JOIN Producto P ON I.Id_Producto=P.Id_Producto 
 WHERE Id_Punto_Dispensacion=21 AND I.Cantidad>0";

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos = $oCon->getData();
unset($oCon);

echo $id_remision." Codigo de la remision =REM29239<br>";

$i=0;
foreach ($productos as  $p) { $i++;
	echo $i." -- ".$p["Lote"]." - ".$p["Nombre_Producto"]." - ".$p["Cantidad"]."<br>";
	$oItem=new complex('Producto_Remision',"Id_Producto_Remision");
	$p['Id_Remision']=29299;
	foreach($p as $index=>$value) {
		$oItem->$index=$value;
	}
	//$oItem->save();
	unset($oItem); 


}

echo "Termino ";





?>