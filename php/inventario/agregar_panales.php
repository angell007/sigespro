<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT T.*, P.Id_Producto, P.Codigo_Cum
FROM Temporral4 T
INNER JOIN Producto P
ON T.Codigo_Mantis=P.Mantis' ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$panales = $oCon->getData();
unset($oCon);

foreach($panales as $panal){
	$oItem = new complex('Inventario',"Id_Inventario");
	$oItem->Id_Producto=$panal['Id_Producto'];
	$oItem->Codigo_CUM=$panal['Codigo_Cum'];
	$oItem->Lote=$panal['Lote'];
	$oItem->Fecha_Vencimiento=$panal['Fecha_Vencimiento'];
	$oItem->Id_Bodega=2;
	$oItem->Cantidad=$panal['Cantidad'];
	$oItem->Costo=$panal['Costo'];
	$oItem->save();
	unset($oItem);	
}

?>