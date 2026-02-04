<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/PHPExcel/IOFactory.php');

$archivo = $_FILES['cotizacion']['tmp_name'];

$excelObject = PHPExcel_IOFactory::load($archivo);
$getShet = $excelObject->getActiveSheet()->toArray(null);

if(!(int)$getShet[0][1]){

	unset($getShet[0]);
	$getShet = array_values($getShet);
	validarProductos($getShet, 1);
}else{

	validarProductos($getShet);
}
$inactivos = validarCumsInactivos($getShet);
if($inactivos){
// 	http_response_code(406); 
	$respuesta['mensaje']= "Los Siguientes Cum no se pueden procesar por estar inactivos en base de datos($inactivos)" ;
	echo json_encode($respuesta);
	exit;
}

foreach ($getShet as $i=> $producto ) {

	$query = "SELECT  P.Nombre_Comercial,
		CONCAT_WS(' ', P.Principio_Activo, P.Presentacion, P.Concentracion, '-',P.Cantidad, P.Unidad_Medida) as Nombre,
	 	P.Id_Producto,
			P.Id_Producto,
			P.Codigo_Cum as Cum,
			P.Invima,
			P.Fecha_Vencimiento_Invima AS Fecha_Vencimiento,
			P.Laboratorio_Comercial,
			P.Laboratorio_Generico,
			P.Embalaje,
			P.Invima,
			P.Cantidad_Presentacion,
			P.Cantidad_Presentacion as Presentacion,
			P.Gravado,
			P.Imagen,
			P.Codigo_Cum,
			0 as TotalDescuento,
            0 as Subtotal,
            0 as Descuento,
			if( ifnull(PRG.Precio_Venta, 0) >0 and ifnull(PRG.Precio_Venta, 0)  < PLG.Precio, ifnull(PRG.Precio_Venta, 0) , CAST(PLG.Precio AS DECIMAL(16,2))) as Precio_Venta,
			ifnull(PRG.Precio_Venta, -1) as Precio_Regulado,
			REPLACE('$producto[2]', '.', ',') as Precio_Cotizacion,
			ifnull(PLG.Precio, -1) as Precio_Lista,
			$producto[1] as Cantidad 
		FROM Producto P 
		LEFT JOIN Producto_Lista_Ganancia PLG ON P.Codigo_CUM=PLG.Cum 
		LEFT JOIN Precio_Regulado PRG on PRG.Codigo_Cum = P.Codigo_Cum

		WHERE  P.Estado = 'Activo'
		AND P.Codigo_Cum = '$producto[0]'
		GROUP BY P.Id_Producto";
	
	$oCon = new consulta();
	$oCon->setQuery($query);
	$producto_ = $oCon->getData();



	if(!$producto_){
	   
	    $producto_ = array(
	    Nombre=>"No existe en base de datos",
	    Nombre_Comercial=>"Error",
	    Cum=>"$producto[0]",
	 	Cantidad =>'-',
	 	Precio_Venta =>'NaN',
			);
	}
	$producto_['Producto']= $producto_;
	    $resp[]= $producto_;
	
}



echo json_encode($resp); 

function validarProductos($productos, $paso=0){
	$filas_error=[];
    foreach ($productos as $key => $value) {
        if(
			!$value[0] ||$value[0]=='' || !$value[1] ||$value[1]=='' || $value[1]<0  ||$value[2]=='' || $value[2]<0 
		){
            array_push($filas_error, $key+1+$paso);
        }

		$pl = new complex('Producto_Lista_Ganancia', 'Cum', $value[0]);
		$precio = $pl->getData();
		if($precio['Precio']>$value[0]){}
    }
	if( count($filas_error)){
		$filas_error = implode(', ', $filas_error);
		http_response_code(406); 
		echo "Error en la(s) fila(s) ($filas_error)";
		exit;
	}

}

function validarCumsInactivos($productos){
	$cums = array_map(function($p){return json_encode($p[0]);}, $productos); 
	$cums = implode(',', $cums);

	$query = "SELECT Group_Concat(P.Codigo_Cum ) as Inactivos
		from Producto P 
		LEFT JOIN Producto_Lista_Ganancia PL on PL.Cum = P.Codigo_Cum 
		WHERE P.Codigo_Cum in ($cums)
		and P.Estado ='Inactivo'
		And P.Codigo_Cum not in (Select P.Codigo_Cum from Producto P Where P.Estado ='Activo' and P.Codigo_Cum in($cums) )
		";
	$oCon = new consulta();
	$oCon->setQuery($query);
	$inactivos = $oCon->getData();

	if($inactivos){
		return $inactivos['Inactivos'];
	}
	return false;
}

function validarCumsPrecios($productos){
	$cums = array_map(function($p){return json_encode($p[0]);}, $productos); 
	$cums = implode(',', $cums);

	$query = "SELECT Group_Concat(P.Codigo_Cum ) as Inactivos
		from Producto P 
		LEFT JOIN Producto_Lista_Ganancia PL on PL.Cum = P.Codigo_Cum 
		WHERE P.Codigo_Cum in ($cums)
		and P.Estado ='Inactivo'
		";
	$oCon = new consulta();
	$oCon->setQuery($query);
	$inactivos = $oCon->getData();

	if($inactivos){
		return $inactivos;
	}
	return false;
}