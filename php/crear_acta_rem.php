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
$configuracion = new Configuracion();

/*$codigo=$configuracion->Consecutivo('Acta_Recepcion_Remision');
$oItem = new complex("Acta_Recepcion_Remision","Id_Acta_Recepcion_Remision");
$oItem->Id_Punto_Dispensacion=163;
$oItem->Identificacion_Funcionario=1095815196;
$oItem->Observaciones='Traslado del Inventario del punto ';
$oItem->Codigo=$codigo;
$oItem->Id_Remision=23086;
$oItem->save();
$id_acta = $oItem->getId();
unset($oItem);*/

$id_acta=16075;

$query="SELECT PR.Id_Producto,PR.Lote,PR.Cantidad,'Si' as Cumple, 'Si' as Revisado,PR.Id_Producto_Remision,PR. Id_Remision,PR.Fecha_Vencimiento, PR.Precio as Costo, P.Codigo_Cum FROm Producto_Remision PR INNER JOIn Producto P On PR.Id_Producto=P.Id_Producto WHERE Id_Remision=23087";

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos = $oCon->getData();
unset($oCon);

echo $id_acta." Codigo de la remision =".$codigo;

foreach ($productos as  $p) {
	
	$oItem=new complex('Producto_Acta_Recepcion_Remision',"Id_Producto_Acta_Recepcion_Remision");
	$p['Id_Acta_Recepcion_Remision']=$id_acta;
	foreach($p as $index=>$value) {
		$oItem->$index=$value;
	}
	$oItem->save();
	unset($oItem); 

	$query = 'SELECT I.*
                FROM Inventario I
                WHERE I.Id_Punto_Dispensacion=165 AND I.Id_Producto='.$p['Id_Producto'].' AND I.Lote="'.$p['Lote'].'"' ;
    
                $oCon= new consulta();
                $oCon->setQuery($query);
                $inventario = $oCon->getData();
				unset($oCon);
				
				if($inventario){
					$actual=number_format($p["Cantidad"],0,"","");
					$suma=number_format($inventario["Cantidad"],0,"","");
					$total=$suma+$actual;

					$oItem = new complex('Inventario','Id_Inventario',$inventario['Id_Inventario']);
					$oItem->Id_Producto= $p['Id_Producto'];
					//$oItem->Codigo_CUM = $item['Codigo_Cum'];
					$oItem->Cantidad = number_format($total,0,"","");
					$oItem->Lote = $p['Lote'];
					$oItem->Fecha_Carga =date("Y-m-d H:i:s"); 
					$oItem->Fecha_Vencimiento = $p['Fecha_Vencimiento'];            
					$oItem->save();
					unset($oItem);

					echo "Actualiza <br> \n\n";
				}else{
					$oItem = new complex('Inventario','Id_Inventario');
					$oItem->Identificacion_Funcionario = 1095815196;
					$oItem->Id_Punto_Dispensacion= 165;
					$oItem->Id_Producto= $p['Id_Producto'];
					$oItem->Costo = $p['Costo'];
					$oItem->Cantidad = number_format($p['Cantidad'],0,"","");
					//$oItem->Codigo_CUM = $item['Codigo_Cum'];
					$oItem->Codigo_CUM = $p['Codigo_Cum'];
					$oItem->Lote = $p['Lote'];
					$oItem->Fecha_Carga =date("Y-m-d H:i:s"); 
					$oItem->Fecha_Vencimiento = $p['Fecha_Vencimiento'];            
					$oItem->save();
					unset($oItem);
					echo "Actualiza <br> \n\n";
				}
}

echo "Termino "





?>