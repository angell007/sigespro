<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$link = mysql_connect('localhost', 'corvusla_proh', 'Proh2018') or die('No se pudo conectar: ' . mysql_error());
mysql_select_db('corvusla_proh') or die('No se pudo seleccionar la base de datos');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT D.Id_Dispensacion, PD.Id_Punto_Dispensacion, PRD.Id_Producto, CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," (",PRD.Nombre_Comercial, ") ", PRD.Cantidad," ", PRD.Unidad_Medida, " ") as Nombre_Producto , PR.Cantidad_Entregada, PR.Cantidad_Formulada
	FROM Dispensacion D
    INNER JOIN Punto_Dispensacion PD
    ON D.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion
    INNER JOIN Producto_Dispensacion PR
    ON D.Id_Dispensacion = PR.Id_Dispensacion
    INNER JOIN Producto PRD
    ON PR.Id_Producto = PRD.Id_Producto
    WHERE PD.Id_Punto_Dispensacion ='.$id .'
    GROUP BY PRD.Id_Producto';
    

$result = mysql_query($query) or die('Consulta fallida: ' . mysql_error());
$dis = mysql_fetch_assoc($result);
var_dump($dis);
mysql_close($link);

//@mysql_free_result($dis);

/*$query2 = 'SELECT T.Id_Tratamiento as IdTratamiento, T.Nombre_Tratamiento as NombreTratamiento, F.Fecha as Fecha, F.Responsable as Responsable, F.Id_Tratamiento_No_Conforme as IdTNC
          FROM Tratamiento T
          INNER JOIN Tratamiento_No_Conforme F
          on T.Id_Tratamiento=F.Id_Tratamiento 
          WHERE F.Id_No_Conforme = '.$id ;





$result2 = mysql_query($query2) or die('Consulta fallida: ' . mysql_error());

$productos = [];

while($lista=mysql_fetch_assoc($result2)){
    $productos[]=$lista;
}
@mysql_free_result($productos);


$resultado["Datos"]=$dis;
$resultado["Tratamiento"]=$productos;

echo json_encode($resultado);*/


?>