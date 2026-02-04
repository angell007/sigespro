<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_remision = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT PR.Total_Descuento, PR.Total_Impuesto,PR.Id_Producto_Remision, C.Nombre as Categoria, PRD.Embalaje, PR.Cantidad_Total, PR.Id_Inventario, PRD.Id_Producto, PR.Cantidad_Total as Cantidad, PR.Descuento, PR.Subtotal, PR.Impuesto,  CONCAT(PRD.Nombre_Comercial, " (",PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion,") ", PRD.Cantidad," ", 			 
    	PRD.Unidad_Medida, ". ","LAB - ",PRD.Laboratorio_Generico ) as Nombre,  PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, PR.Precio as precio,PRD.Cantidad_Presentacion,
         CONCAT("{\'label\':", CONCAT("\'Lote: ", PR.Lote, " - Vencimiento: ",  PR.Fecha_Vencimiento," - Cantidad: ",(PR.Cantidad),"\'"),",\'value\':",PR.Id_Inventario,",\'Codigo_Cum\':\'",PRD.Codigo_Cum,"\',\'Fecha_Vencimiento\':\'",PR.Fecha_Vencimiento,"\',\'Id_Inventario\':\'",PR.Id_Inventario,"\',\'Lote\':\'",PR.Lote,"\',\'Cantidad_Total\':\'",PR.Cantidad_Total,"\',\'Cantidad\':\'",PR.Cantidad,"\'}") as Lote
	FROM Producto_Remision PR
    	INNER JOIN Producto PRD
    	On PR.Id_Producto=PRD.Id_Producto
    	LEFT JOIN Categoria C
        ON PRD.Id_Categoria = C.Id_Categoria
    	WHERE PR.Id_Remision='.$id_remision.' 
   	 ORDER BY PR.Id_Producto';
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos = $oCon->getData();
unset($oCon);

$i=-1;
$idproducto='';
$resultado=[];
$pos=-1;
$poslotes=0;
$lotes=[];
$lotesvisuales=[];

foreach($productos as $producto){ $i++;

	if ($producto['Id_Producto']!=$idproducto){
		if($pos>=0){
		   $resultado[$pos]["Lotes"]=$lotes;
		   $resultado[$pos]["Lotes_Seleccionados"]=$lotes;
		   $resultado[$pos]["Lotes_Auxiliar"]=$lotes;
		   $resultado[$pos]["Lotes_Visuales"]=$lotesvisuales;
		   $poslotes=0;
		}
		
		$pos++;
		if($producto["Nombre"]==''){
			$resultado[$pos]["Nombre"]=$producto["Nombre_Comercial"]." EMB- ".$producto["Embalaje"]." LAB- ".$producto["Laboratorio_Comercial"];
		}else{
			$resultado[$pos]["Nombre"]=$producto["Nombre"];
		}
		$resultado[$pos]["Id_Producto"]=$producto["Id_Producto"];
		$resultado[$pos]["precio"]=$producto["precio"];
		$resultado[$pos]["Precio_Venta"]=$producto["precio"];
		$resultado[$pos]["Cantidad_Presentacion"]=$producto["Cantidad_Presentacion"];
		$resultado[$pos]["Embalaje"]=$producto["Embalaje"];
		$resultado[$pos]["Id_Inventario"]=$producto["Id_Inventario"];
		$resultado[$pos]["Descuento"]=$producto["Descuento"];
		$resultado[$pos]["Subtotal"]=$producto["Subtotal"];
		$resultado[$pos]["Impuesto"]=$producto["Impuesto"];
		$resultado[$pos]["Total_Descuento"]=$producto["Total_Descuento"];
		$resultado[$pos]["Total_Impuesto"]=$producto["Total_Impuesto"];
		$resultado[$pos]["Categoria"]=$producto["Categoria"];
		$resultado[$pos]["Cantidad_Total"]=$producto["Cantidad_Total"];
		$resultado[$pos]["Id_Producto_Remision"]=$producto["Id_Producto_Remision"];
		
		$idproducto=$producto['Id_Producto'];
		$lotes=[];
		$lotesvisuales=[];
		$lotes[$poslotes]=(array)json_decode(str_replace("'",'"',$producto["Lote"]) , true);
		$lotes[$poslotes]["Id_Producto_Remision"]=$producto["Id_Producto_Remision"];
		$resultado[$pos]["Lotes_Auxiliar"]=$lotes;
		$resultado[$pos]["Cantidad"]=$producto["Cantidad"];
		$lotesvisuales[$poslotes][]=$lotes[$poslotes]["label"];
		$prod["Nombre"]=$resultado[$pos]["Nombre"];
	        $prod["Id_Producto"]=$producto["Id_Producto"];
	        $prod["Lotes"]=$lotes;
	        $prod["precio"]=$producto["precio"];
	        $resultado[$pos]["producto"]=$prod;
	
	}else{
		$poslotes++;
		$lotes[$poslotes]=(array) json_decode(str_replace("'",'"',$producto["Lote"]) , true);
		$lotes[$poslotes]["Id_Producto_Remision"]=$producto["Id_Producto_Remision"];
		$lotesvisuales[$poslotes][]=$lotes[$poslotes]["label"];
	}
}
if($pos>-1){
	$resultado[$pos]["Lotes"]=$lotes;
	$resultado[$pos]["Lotes_Seleccionados"]=$lotes;
	$resultado[$pos]["Lotes_Auxiliar"]=$lotes;
	$resultado[$pos]["Lotes_Visuales"]=$lotesvisuales;
}

 
 //var_dump($resultado);
echo json_encode($resultado);

?>