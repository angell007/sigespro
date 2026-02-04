<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$oItem = new complex('Bodega','Id_Bodega',$id);
$data = $oItem->getData();
unset($oItem);

$resultado['Bodega'] = $data;

$query = "SELECT B.Id_Categoria FROM Bodega_Categoria B WHERE B.Id_Bodega = $id";
$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$bodegas_categorias = $oCon->getData();
unset($oCon);

$bodegas_categorias = array_column($bodegas_categorias,"Id_Categoria");

$resultado['Categorias'] = $bodegas_categorias;

$resultado['Departamento'] = $data['Departamento'];

echo json_encode($resultado);
