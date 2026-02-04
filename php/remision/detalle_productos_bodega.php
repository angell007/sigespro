<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
$mes = ( isset( $_REQUEST['mes'] ) ? $_REQUEST['mes'] : '' );
if($mes=="0" || $mes==0 || $mes==''){
	$mes="0";
}
$hoy=date("Y-m-t", strtotime(date('Y-m-d')));
$nuevafecha = strtotime ( '+'.$mes.' months' , strtotime ( $hoy) ) ;
$nuevafecha= date('Y-m-d', $nuevafecha);
$fecha=date('Y-m-d');

switch($tipo){
 case "Bodega":{
	if($id=='6' || $id=='7'){
		$query = 'SELECT C.Nombre as Categoria, MOD((I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),PRD.Cantidad_Presentacion) AS Modulo, PRD.Id_Producto,I.Costo as Precio_Venta, PRD.Embalaje, I.Cantidad, I.Cantidad_Apartada, I.Cantidad_Seleccionada, (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) as Disponible, CONCAT(PRD.Nombre_Comercial, " (",PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion, ") ", PRD.Cantidad," ", 			 
	PRD.Unidad_Medida, ". ","LAB - ",PRD.Laboratorio_Generico 
	) as Nombre,  PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, I.Id_Inventario, I.Costo as precio,PRD.Cantidad_Presentacion, CONCAT("{\'label\':", CONCAT("\'Lote: ", I.Lote, " - Vencimiento: ",  I.Fecha_Vencimiento," - Cantidad: ",(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),"\'"),",\'value\':",I.Id_Inventario,",\'Codigo_Cum\':\'",I.Codigo_Cum,"\',\'Fecha_Vencimiento\':\'",I.Fecha_Vencimiento,"\',\'Lote\':\'",I.Lote,"\',\'Cantidad\':\'",(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),"\',\'Costo\':\'",I.Costo,"\',\'Id_Inventario\':\'",I.Id_Inventario,"\',\'Id_Categoria\':\'",PRD.Id_Categoria,"\',\'Cantidad_Apartada\':\'",I.Cantidad_Apartada,"\',\'Cantidad_Seleccionada\':\'0\'}") as Lote
	FROM Inventario I
    	INNER JOIN Producto PRD
    	On I.Id_Producto=PRD.Id_Producto 
    	LEFT JOIN Categoria C
    	ON PRD.Id_Categoria = C.Id_Categoria
    	WHERE I.Id_Bodega='.$id.' 
    	AND (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada)>0 /*AND (MOD((I.Cantidad),PRD.Cantidad_Presentacion)=0)*/ AND I.Fecha_Vencimiento>="'.$fecha.'"
   	 ORDER BY  PRD.Id_Producto, I.Fecha_Vencimiento ASC';
   	 
   	 #echo $query;
   	 #exit;
   	 break;
	}else{
		$query = 'SELECT C.Nombre as Categoria, MOD((I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),PRD.Cantidad_Presentacion) AS Modulo, PRD.Id_Producto,I.Costo as Precio_Venta, PRD.Embalaje, I.Cantidad, I.Cantidad_Apartada, I.Cantidad_Seleccionada, (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) as Disponible, CONCAT(PRD.Nombre_Comercial, " (",PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion, ") ", PRD.Cantidad," ", 			 
	PRD.Unidad_Medida, ". ","LAB - ",PRD.Laboratorio_Generico 
	) as Nombre,  PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, I.Id_Inventario, I.Costo as precio,PRD.Cantidad_Presentacion, CONCAT("{\'label\':", CONCAT("\'Lote: ", I.Lote, " - Vencimiento: ",  I.Fecha_Vencimiento," - Cantidad: ",(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),"\'"),",\'value\':",I.Id_Inventario,",\'Codigo_Cum\':\'",I.Codigo_Cum,"\',\'Fecha_Vencimiento\':\'",I.Fecha_Vencimiento,"\',\'Lote\':\'",I.Lote,"\',\'Cantidad\':\'",(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),"\',\'Costo\':\'",I.Costo,"\',\'Id_Inventario\':\'",I.Id_Inventario,"\',\'Id_Categoria\':\'",PRD.Id_Categoria,"\',\'Cantidad_Apartada\':\'",I.Cantidad_Apartada,"\',\'Cantidad_Seleccionada\':\'0\'}") as Lote
	FROM Inventario I
    	INNER JOIN Producto PRD
    	On I.Id_Producto=PRD.Id_Producto 
    	LEFT JOIN Categoria C
    	ON PRD.Id_Categoria = C.Id_Categoria
    	WHERE I.Id_Bodega='.$id.' 
    	AND (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada)>0 /*AND (MOD((I.Cantidad),PRD.Cantidad_Presentacion)=0)*/ AND I.Fecha_Vencimiento>="'.$nuevafecha.'"
   	 ORDER BY  PRD.Id_Producto, I.Fecha_Vencimiento ASC';
   	 
   	 #echo $query;
   	 #exit;
   	 break;
	}
	
    }
    case "Punto":{
    $query = 'SELECT C.Nombre as Categoria, MOD((I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),PRD.Cantidad_Presentacion) AS Modulo, PRD.Id_Producto,I.Costo as Precio_Venta, PRD.Embalaje, I.Cantidad, I.Cantidad_Apartada, I.Cantidad_Seleccionada, (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) as Disponible, CONCAT(PRD.Nombre_Comercial, " (",PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion, ") ", PRD.Cantidad," ", 			 
	PRD.Unidad_Medida, ". ","LAB - ",PRD.Laboratorio_Generico 
	) as Nombre,  PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, I.Id_Inventario, I.Costo as precio,PRD.Cantidad_Presentacion, CONCAT_WS("","{\'label\':", CONCAT("\'Lote: ", I.Lote, " - Vencimiento: ",  I.Fecha_Vencimiento," - Cantidad: ",(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),"\'"),",\'value\':",I.Id_Inventario,",\'Codigo_Cum\':\'",I.Codigo_Cum,"\',\'Fecha_Vencimiento\':\'",I.Fecha_Vencimiento,"\',\'Lote\':\'",I.Lote,"\',\'Cantidad\':\'",(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),"\',\'Costo\':\'",I.Costo,"\',\'Id_Inventario\':\'",I.Id_Inventario,"\',\'Id_Categoria\':\'",PRD.Id_Categoria,"\',\'Cantidad_Apartada\':\'",I.Cantidad_Apartada,"\',\'Cantidad_Seleccionada\':\'0\'}") as Lote
	FROM Inventario I
    	INNER JOIN Producto PRD
    	On I.Id_Producto=PRD.Id_Producto 
    	LEFT JOIN Categoria C
    	ON PRD.Id_Categoria = C.Id_Categoria
    	WHERE I.Id_Punto_Dispensacion='.$id.' 
    	AND (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada)>0 
		ORDER BY  PRD.Id_Producto, I.Fecha_Vencimiento ASC';
		break;
    } 
  } 
  
  //echo $query;     
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

	/*if($producto['Modulo'] != 0){
		$producto['Cantidad'] = $producto['Cantidad'] - $producto['Modulo'];
	}*/
	
	if ($producto['Id_Producto']!=$idproducto){
		if($pos>=0){
		   $resultado[$pos]["Lotes"]=$lotes;
		   $resultado[$pos]["Cantidad_Disponible"]=$cantidad_disponible;
		   $poslotes=0;
		}
		$pos++;
		$resultado[$pos]["Id_Producto"]=$producto["Id_Producto"];
		if($producto["Nombre"]==''){
			//var_dump($producto["Nombre_Comercial"]);
			//var_dump ($producto["Id_Producto"]);
			$resultado[$pos]["Nombre"]=$producto["Nombre_Comercial"]." EMB- ".$producto["Embalaje"]." LAB- ".$producto["Laboratorio_Comercial"];
		}else{
			$resultado[$pos]["Nombre"]=$producto["Nombre"];
		}
	
		$resultado[$pos]["precio"]=$producto["precio"];
		$resultado[$pos]["Precio_Venta"]=$producto["precio"];
		$resultado[$pos]["Id_Inventario"]=$producto["Id_Inventario"];
		$resultado[$pos]["Cantidad_Presentacion"]=$producto["Cantidad_Presentacion"];
		$resultado[$pos]["Embalaje"]=$producto["Embalaje"];
		$resultado[$pos]["Categoria"]=$producto["Categoria"];
		
		$idproducto=$producto['Id_Producto'];
		$lotes=[];
		$cantidad_disponible=0;
		$lotes[$poslotes]=(array) json_decode(str_replace("'",'"',$producto["Lote"]) , true);
		$cantidad_disponible+=$producto['Disponible'];
	}else{
		$poslotes++;
		$lotes[$poslotes]=(array) json_decode(str_replace("'",'"',$producto["Lote"]) , true);
		$cantidad_disponible+=$producto['Disponible'];
	}
}

 $resultado[$pos]["Lotes"]=$lotes;
 $resultado[$pos]["Cantidad_Disponible"]=$cantidad_disponible;
//$resultado[$pos]["Lotes"]=$lotes;
	//var_dump($resultado);
echo json_encode($resultado);


?>