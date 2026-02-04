<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_acta = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT AR.*, (SELECT Nombre FROM Bodega_Nuevo WHERE Id_Bodega_Nuevo=AR.Id_Bodega_Nuevo) as Nombre_Bodega, R.Codigo as Codigo_Remision, R.Nombre_Origen
FROM Acta_Recepcion_Remision AR
INNER JOIN Remision R
ON AR.ID_Remision=R.Id_Remision
WHERE AR.Id_Acta_Recepcion_Remision='.$id_acta;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$datos = $oCon->getData();
unset($oCon);

$query2 = 'SELECT P.*,IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida),CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Nombre_Producto,PRD.Nombre_Comercial, PRD.Embalaje, PRD.Invima, CONCAT_WS(" / ", PRD.Laboratorio_Comercial, PRD.Laboratorio_Generico) AS Laboratorios
FROM Producto_Acta_Recepcion_Remision P
INNER JOIN Producto PRD
ON P.Id_Producto=PRD.Id_Producto
WHERE P.Id_Acta_Recepcion_Remision='.$id_acta;
      
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query2);
$productos_acta = $oCon->getData();
unset($oCon);

$resultado=[];

$resultado["Datos"]=$datos;
$resultado["Productos"]=$productos_acta;  



echo json_encode($resultado);

?>