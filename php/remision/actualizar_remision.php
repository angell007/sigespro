<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set('America/Bogota');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );

$datos = (array) json_decode($datos);
$productos = (array) json_decode($productos , true);



$datos["Costo_Remision"]=number_format($datos["Costo_Remision"],2,".","");
$datos["Subtotal_Remision"]=number_format($datos["Subtotal_Remision"],2,".","");
$datos["Descuento_Remision"]=number_format($datos["Descuento_Remision"],2,".","");
$datos["Impuesto_Remision"]=number_format($datos["Impuesto_Remision"],2,".","");

$query = 'SELECT R.* FROM Remision R WHERE R.Id_Remision='. $id;

$oCon= new consulta();
$oCon->setQuery($query);
$remision = $oCon->getData();
unset($oCon);


if($remision['Estado_Alistamiento'] == "0"){

	
	$oItem = new complex('Remision','Id_Remision',$id);
	foreach($datos as $index=>$value) {
		$oItem->$index=$value;
	}
	$oItem->save();
	unset($oItem);


unset($productos[count($productos)-1]);

foreach($productos as $producto){$i++;
   if($producto["Id_Producto_Remision"]==''){
	if($producto['Lotes_Seleccionados']!=null){
	     foreach($producto['Lotes_Seleccionados'] as $lote){
	            $oItem = new complex('Inventario',"Id_Inventario",$lote['Id_Inventario']);
	            $inv = $oItem->getData();
	            
	            
	            $seleccionada = number_format($inv["Cantidad_Seleccionada"],0,"","");
	            $apartada=number_format($inv["Cantidad_Apartada"],0,"","");
	            $actual = number_format($lote["Cantidad"],0,"","");

	            $fin = $seleccionada - $actual;
	            $cantidad = $apartada + $actual;
                
	            $oItem->Cantidad_Apartada=number_format($cantidad,0,"","");
	            $oItem->Cantidad_Seleccionada=number_format($fin,0,"","");
	            $oItem->save();
	            unset($oItem);
	            
	            $subtotal = $producto["Precio_Venta"] * $lote["Cantidad"];
                    $total_descuento = $subtotal*($producto["Descuento"]/100);
                    $total_impuesto = (($subtotal-$total_descuento)*($producto["Impuesto"]/100));                
	            
	            $oItem = new complex('Producto_Remision',"Id_Producto_Remision");
	            $oItem->Id_Remision=$id;
	            $oItem->Id_Inventario = $lote["Id_Inventario"];
	            $oItem->Lote = $lote["Lote"];
	            $oItem->Fecha_Vencimiento = $lote["Fecha_Vencimiento"];
	            $oItem->Cantidad = $lote["Cantidad"];
	            $oItem->Id_Producto = $producto["Id_Producto"];
	            $oItem->Nombre_Producto = $producto["Nombre"];
	            $oItem->Cantidad_Total = $producto["Cantidad"];
	            $oItem->Precio = $producto["Precio_Venta"];
	            $oItem->Descuento = $producto["Descuento"];
	            $oItem->Impuesto = $producto["Impuesto"];
	            $oItem->Total_Descuento=number_format($total_descuento,2,".","");
	            $oItem->Total_Impuesto=number_format($total_impuesto,2,".","");
	            $oItem->Subtotal = number_format($subtotal,2,".","");
	            $oItem->save();
	            unset($oItem);
	     }
        }
   }
    
}

$oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
$oItem->Id_Remision=$id;
$oItem->Identificacion_Funcionario=$funcionario;
$oItem->Detalles="Se hicieron modificaciones a la remision con codigo ".$datos['Codigo'];
$oItem->Fecha=date("Y-m-d H:i:s");
$oItem->Estado ='Edicion';
$oItem->save();
unset($oItem);
}



if($remision['Estado_Alistamiento'] == "0"){
    $resultado['mensaje'] = "Se ha actualizado correctamente la remision con codigo: ". $datos['Codigo'];
    $resultado['tipo'] = "success";
}else{
    $resultado['mensaje'] = "No se puede Actualizar la remision por que ya esta en una Fase de Alistamiento ";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);

?>