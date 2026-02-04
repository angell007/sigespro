<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$codigo = ( isset( $_REQUEST['codigo'] ) ? $_REQUEST['codigo'] : false );
$nombre = ( isset( $_REQUEST['nombre'] ) ? $_REQUEST['nombre'] : false );

$query = "";

if ($codigo) {
  $query .= 'SELECT 
  CONCAT(
  Producto.Principio_Activo," ",
  Producto.Presentacion," ",
  Producto.Concentracion," (",
  Producto.Nombre_Comercial, ") ",
  Producto.Cantidad," ", 			 
  Producto.Unidad_Medida, ". ","LAB - ",
  Producto.Laboratorio_Generico, " (",Producto.Laboratorio_Comercial,")")  as Nombre,
  Producto.Id_Producto,
  Producto.Codigo_Cum, 
  Producto.Embalaje,
  Producto.Imagen,
  Producto.Cantidad_Presentacion,
  Producto.Peso_Presentacion_Minima as Peso_Minimo,
  Producto.Peso_Presentacion_Regular as Peso_Regular,
  Producto.Peso_Presentacion_Maxima as Peso_Maximo,
  Producto.Nombre_Comercial,
  Producto.Laboratorio_Comercial,
  Producto.Laboratorio_Generico
FROM Producto  
WHERE Producto.Codigo_Barras='.$codigo.'
Order by Nombre ASC';
} elseif ($nombre) {
  $query .= 'SELECT 
  CONCAT(
  
  Producto.Nombre_Comercial, 
 " LAB - ",
  Producto.Laboratorio_Generico, " (",Producto.Laboratorio_Comercial,")")  as Nombre,
  Producto.Id_Producto,
  Producto.Codigo_Cum, 
  Producto.Embalaje,
  Producto.Imagen,
  Producto.Cantidad_Presentacion,
  Producto.Peso_Presentacion_Minima as Peso_Minimo,
  Producto.Peso_Presentacion_Regular as Peso_Regular,
  Producto.Peso_Presentacion_Maxima as Peso_Maximo,
  Producto.Nombre_Comercial,
  Producto.Laboratorio_Comercial,
  Producto.Laboratorio_Generico
FROM Producto 
  WHERE Producto.Nombre_Comercial LIKE "%'.$nombre.'%"
  Order by Nombre ASC';
}



$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

$i=-1;
foreach($resultado as $res){ $i++;
	if($res["Nombre"]==""){
$resultado[$i]["Nombre"]=$res["Nombre_Comercial"]." LAB: ".$res["Laboratorio_Comercial"]." (".$res["Laboratorio_Generico"].")";
	}
$resultado[$i]["Laboratorio_Generico"]=$res["Laboratorio_Comercial"]." (".$res["Laboratorio_Generico"].")";
}

echo json_encode($resultado);

?>