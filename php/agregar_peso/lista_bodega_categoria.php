<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$query = 'SELECT C.Id_Categoria, C.Nombre FROM Categoria C' ;
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$categorias= $oCon->getData();
unset($oCon);

$query = 'SELECT B.Id_Bodega, B.Nombre
FROM  Bodega B' ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$bodegas = $oCon->getData();
unset($oCon); 
$resultado["Bodegas"]=$bodegas;
$resultado["Categorias"]=$categorias;

echo json_encode($resultado);
          
?>