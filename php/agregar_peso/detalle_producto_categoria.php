<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$codigo = ( isset( $_REQUEST['codigo'] ) ? $_REQUEST['codigo'] : '' );


$query = 'SELECT IFNULL(CONCAT(PRD.Nombre_Comercial, " (",PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion, ") ", PRD.Cantidad," ", 			 
PRD.Unidad_Medida, ". ","LAB - ",PRD.Laboratorio_Generico 
), CONCAT(PRD.Nombre_Comercial, " LAB: ", PRD.Laboratorio_Comercial)) as Nombre, PRD.Peso_Presentacion_Minima, PRD.Peso_Presentacion_Regular,PRD.Peso_Presentacion_Maxima, PRD.Embalaje, PRD.Imagen as Foto, PRD.Id_Producto, PRD.Cantidad_Presentacion, PRD.Tolerancia AS Torerancia, PRD.Id_Categoria, PRD.Nombre_Comercial, PRD.Imagen, PRD.Laboratorio_Comercial, PRD.Laboratorio_Generico
FROM  Producto PRD
WHERE PRD.Codigo_Barras= "'.$codigo.'" AND  PRD.Actualizado="No"'  ;

$oCon= new consulta();
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);


echo json_encode($resultado);

?>