<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$lista = ( isset( $_REQUEST['lista'] ) ? $_REQUEST['lista'] : '' );

	$query = 'SELECT PRD.Id_Producto,I.Costo as Precio_Venta, PRD.Embalaje, I.Cantidad, I.Cantidad_Apartada,I.Cantidad_Seleccionada, CONCAT(PRD.Nombre_Comercial, " (",PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion, ") ", PRD.Cantidad," ", 			 
	PRD.Unidad_Medida, ". ","LAB - ",PRD.Laboratorio_Generico 
	) as Nombre,  PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, I.Id_Inventario, LG.Precio as precio,PRD.Cantidad_Presentacion, CONCAT("{\'label\':", CONCAT("\'Lote: ", I.Lote, " - Vencimiento: ",  I.Fecha_Vencimiento," - Cantidad: ",(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),"\'"),",\'value\':",I.Id_Inventario,",\'Codigo_Cum\':\'",I.Codigo_Cum,"\',\'Fecha_Vencimiento\':\'",I.Fecha_Vencimiento,"\',\'Lote\':\'",I.Lote,"\',\'Cantidad\':\'",(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),"\',\'Costo\':\'",LG.Precio,"\',\'Id_Inventario\':\'",I.Id_Inventario,"\',\'Id_Categoria\':\'",PRD.Id_Categoria,"\',\'Cantidad_Apartada\':\'",I.Cantidad_Apartada,"\',\'Cantidad_Seleccionada\':\'0\'}") as Lote,
	(CASE  
      WHEN PRD.Gravado = "Si" THEN "19" 
      ELSE "0" 
    END) as Impuesto
	FROM Inventario I
    	INNER JOIN Producto PRD
    	On I.Id_Producto=PRD.Id_Producto   
	INNER JOIN Producto_Lista_Ganancia LG
	ON PRD.Codigo_Cum = LG.Cum AND LG.Id_Lista_Ganancia ='.$lista.'
    	WHERE I.Id_Bodega='.$id.'
    	AND (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada)>0 
   	 ORDER BY  PRD.Id_Producto, I.Fecha_Vencimiento ASC';
   	    
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos= $oCon->getData();
unset($oCon);


$i=-1;
$idproducto='';
$resultado=[];
$pos=-1;
$poslotes=0;
$lotes=[];
$cantidad_disponible=0;
foreach($productos as $producto){ $i++;
	if ($producto['Id_Producto']!=$idproducto){
		if($pos>=0){
		   $resultado[$pos]["Lotes"]=$lotes;
		   $resultado[$pos]["Cantidad_Disponible"]=$cantidad_disponible;
		   $poslotes=0;
		}
		$pos++;
		$resultado[$pos]["Id_Producto"]=$producto["Id_Producto"];
		if($producto["Nombre"]==''){
			$resultado[$pos]["Nombre"]=$producto["Nombre_Comercial"]." LAB- ".$producto["Laboratorio_Comercial"];
		}else{
			$resultado[$pos]["Nombre"]=$producto["Nombre"];
		}
	
		
		$resultado[$pos]["precio"]=$producto["precio"];
		$resultado[$pos]["Precio_Venta"]=$producto["precio"];
		$resultado[$pos]["Id_Inventario"]=$producto["Id_Inventario"];
		$resultado[$pos]["Cantidad_Presentacion"]=$producto["Cantidad_Presentacion"];
		$resultado[$pos]["Embalaje"]=$producto["Embalaje"];
		$resultado[$pos]["Impuesto"]=$producto["Impuesto"];
		$idproducto=$producto['Id_Producto'];
		$lotes=[];
		$cantidad_disponible=0;
		$lotes[$poslotes]=(array) json_decode(str_replace("'",'"',$producto["Lote"]) , true);
		$cantidad_disponible+=($producto['Cantidad']-$producto['Cantidad_Apartada']-$producto['Cantidad_Seleccionada']);
	}else{
		$poslotes++;
		$lotes[$poslotes]=(array) json_decode(str_replace("'",'"',$producto["Lote"]) , true);
		$cantidad_disponible+=($producto['Cantidad']-$producto['Cantidad_Apartada']-$producto['Cantidad_Seleccionada']);
	}
}

 $resultado[$pos]["Lotes"]=$lotes;
 $resultado[$pos]["Cantidad_Disponible"]=$cantidad_disponible;
//$resultado[$pos]["Lotes"]=$lotes;
	//var_dump($resultado);
echo json_encode($resultado);


?>