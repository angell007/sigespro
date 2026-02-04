<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$oItem = new complex('Categoria_Nueva','Id_Categoria_Nueva',$id);
$data = $oItem->getData();
unset($oItem);

$resultado['Categoria_Nueva'] = $data;

$query = "SELECT C.Id_Subcategoria FROM Categoria_Nueva_Subcategoria C WHERE C.Id_Categoria_Nueva = $id";
$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$categorias_subcategorias = $oCon->getData();
unset($oCon);

$categorias_subcategorias = array_column($categorias_subcategorias,"Id_Subcategoria");

$resultado['Subcategorias'] = $categorias_subcategorias;

$resultado['Departamento'] = $data['Departamento'];

echo json_encode($resultado);
