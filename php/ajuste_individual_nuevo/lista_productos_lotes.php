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
     $query = 'SELECT   PRD.Id_Producto,IFNULL(C.Costo_Promedio,0) as Precio_Venta, PRD.Embalaje, I.Cantidad, I.Cantidad_Apartada, I.Id_Estiba,
	  CONCAT_WS(" ",PRD.Nombre_Comercial,CONCAT("(",PRD.Principio_Activo),PRD.Presentacion,PRD.Concentracion,
	   PRD.Cantidad, CONCAT(PRD.Unidad_Medida,")"),"LAB -",PRD.Laboratorio_Comercial, "CUM:",PRD.Codigo_Cum) as Nombre,
	     PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, I.Id_Inventario_Nuevo, IFNULL(C.Costo_Promedio,0) as precio,PRD.Cantidad_Presentacion, I.Fecha_Vencimiento
	FROM Inventario_Nuevo I
    INNER JOIN Producto PRD
    On I.Id_Producto=PRD.Id_Producto   
	LEFT JOIN Costo_Promedio C
	 ON C.Id_Producto = I.Id_Producto
    WHERE I.Id_Estiba='.$id.' AND (I.Cantidad - (I.Cantidad_Apartada + I.Cantidad_Seleccionada) )>0 
	GROUP BY I.Id_Producto, I.Estiba, I.Fecha_Vencimiento
    ORDER BY  PRD.Id_Producto, I.Fecha_Vencimiento ASC';
           
    break;
    }
    case "Punto":{
        $query = 'SELECT   PRD.Id_Producto,IFNULL(C.Costo_Promedio,0) as Precio_Venta, PRD.Embalaje, I.Cantidad, I.Cantidad_Apartada,
		CONCAT_WS(" ",PRD.Nombre_Comercial,CONCAT("(",PRD.Principio_Activo),PRD.Presentacion,PRD.Concentracion, PRD.Cantidad,
		 CONCAT(PRD.Unidad_Medida,")"),"LAB -",PRD.Laboratorio_Comercial, "CUM:",PRD.Codigo_Cum) as Nombre,  
		 PRD.Nombre_Comercial, PRD.Laboratorio_Comercial,I.Id_Inventario_Nuevo, IFNULL(C.Costo_Promedio,0) as precio,PRD.Cantidad_Presentacion, 
		 I.Fecha_Vencimiento as Fecha_Vencimiento_Nueva
		
		FROM Inventario_Nuevo I
		INNER JOIN Producto PRD
		On I.Id_Producto=PRD.Id_Producto   
		LEFT JOIN Costo_Promedio C
	 	ON C.Id_Producto = I.Id_Producto
     WHERE I.Id_Punto_Dispensacion='.$id.' AND (I.Cantidad - (I.Cantidad_Apartada + I.Cantidad_Seleccionada) )>0 
        GROUP BY I.Id_Producto, I.Fecha_Vencimiento
      ORDER BY  PRD.Id_Producto, I.Fecha_Vencimiento ASC';
        break;
    }
}


       	  
$oCon= new consulta();
$oCon->setTipo('Multiple');

$oCon->setQuery($query);
$productos = $oCon->getData();
unset($oCon);

if ($tipo=='Punto') {
	buscarLotesPunto();
}else if($tipo=='Bodega'){
	buscarLotesBodega();
}



echo json_encode($productos);

function buscarLotesPunto(){
	global $productos, $id;
	foreach ($productos as $key => $producto) {
		# code...

		$query = 'SELECT I.Lote , SUM( IF( (I.Cantidad - (I.Cantidad_Apartada + I.Cantidad_Seleccionada)) < 0, 0, (I.Cantidad - (I.Cantidad_Apartada + I.Cantidad_Seleccionada)) ) ) AS Cantidad ,
				I.Id_Producto ,
		
				  CONCAT_WS(" ",PRD.Nombre_Comercial,CONCAT("(",PRD.Principio_Activo),PRD.Presentacion,PRD.Concentracion,
                PRD.Cantidad, CONCAT(PRD.Unidad_Medida,")"),"LAB -",PRD.Laboratorio_Comercial, "CUM:",PRD.Codigo_Cum) as Nombre,
			
			
				CONCAT("Lote :",I.Lote," - Cantidad :",SUM( IF( (I.Cantidad - (I.Cantidad_Apartada + I.Cantidad_Seleccionada)) < 0, 0, (I.Cantidad - (I.Cantidad_Apartada + I.Cantidad_Seleccionada)) ) ) ) AS label,
				I.Id_Producto AS value,
				IFNULL( (SELECT Costo_Promedio FROM Costo_Promedio WHERE Id_Producto = I.Id_Producto), 0) AS Costo,
	            PRD.Laboratorio_Comercial
				FROM Inventario_Nuevo I 
				INNER JOIN Producto PRD
                    On I.Id_Producto=PRD.Id_Producto
                INNER JOIN Estiba E ON I.Id_Estiba = E.Id_Estiba   
				WHERE E.Id_Punto_Dispensacion = "'.$id.'" AND I.Id_Producto = '.$producto['Id_Producto'].'
				GROUP BY I.Id_Producto, I.Lote';

		$oCon= new consulta();
		$oCon->setTipo('Multiple');

		$oCon->setQuery($query);
		$lotes = $oCon->getData();
		unset($oCon);
		$productos[$key]['Lotes'] = $lotes;

				
			
	}
}


function buscarLotesBodega(){
	global $productos, $id;
	foreach ($productos as $key => $producto) {
		# code...
           
		$query = 'SELECT I.Lote , SUM( IF( (I.Cantidad - (I.Cantidad_Apartada + I.Cantidad_Seleccionada)) < 0, 0, (I.Cantidad - (I.Cantidad_Apartada + I.Cantidad_Seleccionada)) ) ) AS Cantidad ,
				I.Id_Producto ,
			    CONCAT_WS(" ",PRD.Nombre_Comercial,CONCAT("(",PRD.Principio_Activo),PRD.Presentacion,PRD.Concentracion,
                PRD.Cantidad, CONCAT(PRD.Unidad_Medida,")"),"LAB -",PRD.Laboratorio_Comercial, "CUM:",PRD.Codigo_Cum) as Nombre,
				CONCAT("Lote :",I.Lote," - Cantidad :",SUM( IF( (I.Cantidad - (I.Cantidad_Apartada + I.Cantidad_Seleccionada)) < 0, 0, (I.Cantidad - (I.Cantidad_Apartada + I.Cantidad_Seleccionada)) ) ) ) AS label,
				I.Fecha_Vencimiento,
				I.Id_Producto AS value,
				IFNULL(C.Costo_Promedio,0) AS Costo,
			    PRD.Laboratorio_Comercial
				FROM Inventario_Nuevo I 
				INNER JOIN Producto PRD
                On I.Id_Producto = PRD.Id_Producto   
            	LEFT JOIN Costo_Promedio C
	            ON C.Id_Producto = I.Id_Producto
				WHERE I.Id_Estiba = "'.$id.'" AND I.Id_Producto = '.$producto['Id_Producto'].'
				GROUP BY I.Id_Producto, I.Lote';

				

		$oCon= new consulta();
		$oCon->setTipo('Multiple');

		$oCon->setQuery($query);
		$lotes = $oCon->getData();
		unset($oCon);
		$productos[$key]['Lotes'] = $lotes;

				
			
	}
}

?>