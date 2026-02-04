<?php
ini_set('max_execution_time', 3600);
ini_set('memory_limit','256M');
/* header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token'); */
//header('Content-Type: application/json');

require_once("/home/sigespro/public_html/config/start.inc_cron.php");
include_once("/home/sigespro/public_html/class/class.lista.php");
include_once("/home/sigespro/public_html/class/class.complex.php");
include_once("/home/sigespro/public_html/class/class.consulta.php");



$query = "SELECT I.Lote, I.Fecha_Vencimiento, I.Id_Producto, I.Id_Punto_Dispensacion, I.Id_Inventario
FROM Inventarios_Borrados IB
INNER JOIN Inventario I 
ON I.Id_Inventario=IB.Id_Inventario
ORDER BY I.Id_Punto_Dispensacion
";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$inventarios = $oCon->getData();
unset($oCon);

foreach($inventarios as $inv){
	
	$query = "SELECT * 
		FROM Producto_Acta_Recepcion_Remision PAR 
		INNER JOIN Acta_Recepcion_Remision AR
		ON AR.Id_Acta_Recepcion_Remision=PAR.Id_Acta_Recepcion_Remision
		WHERE PAR.Lote LIKE '".$inv["Lote"]."'  AND PAR.Id_Producto =".$inv["Id_Producto"]." AND PAR.Fecha_Vencimiento!='".$inv["Fecha_Vencimiento"]."' AND AR.Id_Punto_Dispensacion=".$inv["Id_Punto_Dispensacion"];
		
		$oCon = new consulta();
		$oCon->setQuery($query);
		$oCon->setTipo('Multiple');
		$actas = $oCon->getData();
		unset($oCon);
		
		if(count($actas)>0){
			echo "Lote: ".$inv["Lote"]." - F.V.: ".$inv["Fecha_Vencimiento"]." - Producto.: ".$inv["Id_Producto"]." - Punto.: ".$inv["Id_Punto_Dispensacion"]." - Inv.: ".$inv["Id_Inventario"]."<br>";
			$oItem = new complex('Inventario', 'Id_Inventario', $inv["Id_Inventario"]);
			$oItem->Fecha_Vencimiento=$actas[0]["Fecha_Vencimiento"];
			//$oItem->save();
			unset($oItem);
			foreach($actas as $acta){
			$oItem = new complex('Producto_Acta_Recepcion_Remision', 'Id_Producto_Acta_Recepcion_Remision', $acta["Id_Producto_Acta_Recepcion_Remision"]);
			$oItem->Fecha_Vencimiento=$inv["Fecha_Vencimiento"];
			//$oItem->save();
			unset($oItem);
			
			echo "<strong>FV: ".$acta["Fecha_Vencimiento"]."</strong><br>";
			}
			echo "<br><br>";
		}
		

}



?>