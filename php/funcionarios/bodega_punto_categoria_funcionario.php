<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$query = 'SELECT B.Nombre as label, B.Id_Bodega_Nuevo as value
FROM Bodega_Nuevo B ';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$bodega = $oCon->getData();
unset($oCon);

$query = 'SELECT B.Nombre as label, B.Id_Categoria as value
FROM Categoria B WHERE Separable="Si" ';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$categoria = $oCon->getData();
unset($oCon);

$query = 'SELECT B.Nombre as label, B.Id_Punto_Dispensacion as value
FROM Punto_Dispensacion B ';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$punto = $oCon->getData();
unset($oCon);

$resultado['Puntos']=$punto;
$resultado['Categorias']=$categoria;
$resultado['Bodegas']=$bodega;




echo json_encode($resultado);

?>