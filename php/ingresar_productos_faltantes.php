<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');



$query='SELECT T.*
FROM Temporal_Reconteo_Material T 
INNER JOIN Producto P ON T.Id_Producto=P.Id_Producto';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

//var_dump($resultado);

//$query='';

$fecha='2019-06-15';

foreach($resultado as $item){

    $query="SELECT * FROM Producto_Inventario_Fisico WHERE Id_Inventario_Fisico IN (717,718,719,720,721) AND Id_Producto=$item[Id_Producto] LImit 1";
    echo $query ."<br>";
    
	$oCon= new consulta();
	$oCon->setQuery($query);
	$inventario = $oCon->getData();
	unset($oCon);

	if($inventario['Id_Inventario_Fisico']){
		
		$oItem = new complex("Producto_Inventario_Fisico","Id_Producto_Inventario_Fisico");
		$oItem->Id_Producto = $item['Id_Producto'];
		$oItem->Id_Inventario = '0';
		$oItem->Primer_Conteo = $item['Cantidad'];
		$oItem->Fecha_Primer_Conteo = $fecha;
		$oItem->Fecha_Segundo_Conteo = $fecha;
		$oItem->Segundo_Conteo= $item['Cantidad'];
		$oItem->Id_Inventario_Fisico =$inventario['Id_Inventario_Fisico'] ;
		$oItem->Lote =$item['Lote'] ;
		$oItem->Fecha_Vencimiento = $item['Fecha_Vencimiento']; 
		//$oItem->save();  
        unset($oItem);
       

		echo "se actualiza el ".$inventario['Id_Inventario_Fisico']." con cantidad ".$inventario['Cantidad']." a la cantidad --- ".$total."<br>";
	}else{
		echo "se inserta el ".$inventario['Id_Inventario_Fisico']." con cantidad ".$inventario['Cantidad']." a la cantidad ---".$total."<br>";

		$oItem = new complex("Producto_Inventario_Fisico","Id_Producto_Inventario_Fisico");
		$oItem->Id_Producto = $item['Id_Producto'];
		$oItem->Id_Inventario = '0';
		$oItem->Primer_Conteo = $item['Cantidad'];
		$oItem->Fecha_Primer_Conteo = $fecha;
		$oItem->Segundo_Conteo = $item['Cantidad'];
		$oItem->Fecha_Segundo_Conteo = $fecha;
		$oItem->Id_Inventario_Fisico =721;
		$oItem->Lote =$item['Lote'] ;
		$oItem->Fecha_Vencimiento = $item['Fecha_Vencimiento']; 
	    //$oItem->save();  
		unset($oItem);
	}
}




?>