<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT FP.Id_Punto_Dispensacion as id 
FROM Funcionario_Punto FP
WHERE FP.Identificacion_Funcionario='.$id;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$puntos = $oCon->getData();
unset($oCon);

$query1 = 'SELECT FB.Id_Bodega_Nuevo as id 
FROM Funcionario_Bodega_Nuevo FB
WHERE FB.Identificacion_Funcionario='.$id;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query1);
$bodegas = $oCon->getData();
unset($oCon);

$query1 = 'SELECT FB.Id_Categoria as id 
FROM Funcionario_Categoria FB
WHERE FB.Identificacion_Funcionario='.$id;
 
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query1);
$categoria = $oCon->getData();
unset($oCon);


$resultado=[];
$resultado["Bodegas"]=$bodegas;
$resultado["Puntos"]=$puntos;
$resultado["Categorias"]=$categoria;
echo json_encode($resultado);

?>
