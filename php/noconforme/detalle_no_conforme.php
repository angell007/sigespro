<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT NC.*, CONCAT(F.Nombres, " ", F.Apellidos) as Funcionario, R.Codigo as Codigo_Rem, R.Tipo 
FROM No_Conforme NC 
INNER JOin Funcionario F
On NC.Persona_Reporta=F.Identificacion_Funcionario
INNER JOIN Remision R
ON NC.Id_Remision=R.Id_Remision
where NC.Id_No_Conforme='.$id ;

$oCon= new consulta();
$oCon->setQuery($query);
$dis = $oCon->getData();
unset($oCon);

$query2 = 'SELECT PNCR.*, CONCAT_WS(" ",PRD.Principio_Activo, PRD.Presentacion, PRD.Concentracion, "(",PRD.Nombre_Comercial,")",PRD.Cantidad,PRD.Unidad_Medida) as Nombre_Producto, CNC.Nombre as Nombre_Causal, R.Codigo
FROM Producto_No_Conforme_Remision PNCR
INNER JOIN Producto PRD
ON PNCR.Id_Producto=PRD.Id_Producto
INNER JOIN Causal_No_Conforme CNC
On PNCR.Id_Causal_No_Conforme=CNC.Id_Causal_No_Conforme
INNER JOIN Remision R
ON PNCR.Id_Remision=R.Id_Remision
WHERE PNCR.Id_No_Conforme='.$id ;



$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

$resultado["Datos"]=$dis;
$resultado["Productos"]=$productos;

echo json_encode($resultado);


?>