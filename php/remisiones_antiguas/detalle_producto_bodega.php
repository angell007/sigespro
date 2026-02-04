<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
switch($tipo){
    
     case "Bodega":{
     $query = 'SELECT   PRD.Id_Producto,I.Costo as Precio_Venta, PRD.Embalaje, I.Cantidad, I.Cantidad_Apartada, CONCAT(PRD.Nombre_Comercial, " (",PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," EMB-",PRD.Embalaje, ") ", PRD.Cantidad," ", 			 
	PRD.Unidad_Medida, ". ","LAB - ",PRD.Laboratorio_Generico 
	) as Nombre,  PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, I.Id_Inventario, I.Costo as precio,PRD.Cantidad_Presentacion, I.Lote
	FROM Inventario I
    INNER JOIN Producto PRD
    On I.Id_Producto=PRD.Id_Producto   
    WHERE I.Id_Bodega='.$id.' 
    ORDER BY  PRD.Id_Producto, I.Fecha_Vencimiento ASC';
           
        break;
    }
    case "Punto":{
        $query = 'ELECT   PRD.Id_Producto,I.Costo as Precio_Venta, PRD.Embalaje, I.Cantidad, I.Cantidad_Apartada, CONCAT(PRD.Nombre_Comercial, " (",PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," EMB-",PRD.Embalaje, ") ", PRD.Cantidad," ", 			 
	PRD.Unidad_Medida, ". ","LAB - ",PRD.Laboratorio_Generico 
	) as Nombre,  PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, I.Id_Inventario, I.Costo as precio,PRD.Cantidad_Presentacion, I.Lote
	FROM Inventario I
    INNER JOIN Producto PRD
    On I.Id_Producto=PRD.Id_Producto 
     WHERE I.Id_Punto_Dispensacion='.$id.'
      ORDER BY  PRD.Id_Producto, I.Fecha_Vencimiento ASC';
        break;
    }
}


       	  
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
			//var_dump($producto["Nombre_Comercial"]);
			//var_dump ($producto["Id_Producto"]);
			$resultado[$pos]["Nombre"]=$producto["Nombre_Comercial"]." LAB- ".$producto["Laboratorio_Comercial"];
		}else{
			$resultado[$pos]["Nombre"]=$producto["Nombre"];
		}
	
		
		$resultado[$pos]["precio"]=$producto["precio"];
		$resultado[$pos]["Precio_Venta"]=$producto["precio"];
		$resultado[$pos]["Id_Inventario"]=$producto["Id_Inventario"];
		$resultado[$pos]["Cantidad_Presentacion"]=$producto["Cantidad_Presentacion"];
		$resultado[$pos]["Embalaje"]=$producto["Embalaje"];
		$idproducto=$producto['Id_Producto'];
		$lotes=[];
		$cantidad_disponible=0;
		$lotes[$poslotes]=(array) json_decode(str_replace("'",'"',$producto["Lote"]) , true);
		$cantidad_disponible+=($producto['Cantidad']-$producto['Cantidad_Apartada']);
	}else{
		$poslotes++;
		$lotes[$poslotes]=(array) json_decode(str_replace("'",'"',$producto["Lote"]) , true);
		$cantidad_disponible+=$producto['Cantidad']-$producto['Cantidad_Apartada'];
	}
	
	

 
}

 $resultado[$pos]["Lotes"]=$lotes;

echo json_encode($resultado);


?>