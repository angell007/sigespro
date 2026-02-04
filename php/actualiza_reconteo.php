<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');



$query='SELECT T.*, P.Codigo_Cum, IFNULL((SELECT Precio FROM Producto_Acta_Recepcion WHERE Id_Producto=T.Id_Producto ORDER BY Id_Producto_Acta_Recepcion DESC LIMIT 1),
(SELECT Costo FROM Inventario WHERE Id_Producto=T.Id_Producto LIMIT 1)) as Costo 
FROM Temporal_Reconteo_Material T 
INNER JOIN Producto P ON T.Id_Producto=P.Id_Producto';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

//var_dump($resultado);

//$query='';


foreach($resultado as $item){

	$query="SELECT Id_Inventario, Cantidad FROM Inventario WHERE Id_Bodega=2 AND Id_Producto=$item[Id_Producto] AND Lote='$item[Lote]'";
	$oCon= new consulta();
	$oCon->setQuery($query);
	$inventario = $oCon->getData();
	unset($oCon);

	if($inventario['Id_Inventario']){
		$total=$inventario['Cantidad']+$item['Cantidad'];
		$oItem = new complex("Inventario","Id_Inventario",$inventario["Id_Inventario"]);
		$oItem->Cantidad = number_format($total,0,"","");
		$oItem->Cantidad_Apartada = number_format(0,0,"","");
		$oItem->Cantidad_Seleccionada = number_format(0,0,"",""); 
		//$oItem->save();  
		unset($oItem);

		echo "se actualiza el ".$inventario['Id_Inventario']." con cantidad ".$inventario['Cantidad']." a la cantidad ".$total."<br>";
	}else{
		echo "se inserta el ".$inventario['Id_Inventario']." con cantidad ".$inventario['Cantidad']." a la cantidad ".$total."<br>";

		$oItem = new complex('Inventario','Id_Inventario');
		$oItem->Codigo = substr(hexdec(uniqid()),2,12);
		$oItem->Cantidad=$item["Cantidad"];
		$oItem->Id_Producto=$item["Id_Producto"];
		$oItem->Codigo_CUM=$item["Codigo_Cum"];
		$oItem->Lote=$item["Lote"];
		$oItem->Fecha_Vencimiento=$item["Fecha_Vencimiento"];
		$oItem->Id_Bodega = 2;
		$oItem->Costo = $item['Costo'];
		$oItem->Identificacion_Funcionario = 91499043;
		//$oItem->save();  
		unset($oItem);
	}
}




?>