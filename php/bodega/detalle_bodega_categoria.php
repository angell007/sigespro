<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$idBodega = ( isset( $_REQUEST['idBodega'] ) ? $_REQUEST['idBodega'] : '' );

$query = 'SELECT C.Id_Categoria, C.Nombre, C.Id_Categoria AS value, C.Nombre AS label FROM Bodega_Categoria B INNER JOIN Categoria C ON B.Id_Categoria=C.Id_Categoria WHERE B.Id_Bodega ='.$idBodega;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);
if($idBodega=='9' || $idBodega=='8' || $idBodega=='7' || $idBodega=='6' ){
    $todas['Id_Categoria']='0';
    $todas['Nombre']='Todas';
    array_unshift($resultado,$todas);
}


echo json_encode($resultado);
